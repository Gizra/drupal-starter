#!/usr/bin/env python3
"""Keep new comments short.

Only comment blocks a change touches are measured, so the long ones already
in the tree stay as they are and no baseline file goes stale.
"""

import argparse
import ast
import fnmatch
import os
import re
import subprocess
import sys
from dataclasses import dataclass
from pathlib import Path

# Words, not lines: a wrapped paragraph and a one-liner saying the same thing
# should score the same, and a short column of names is not slop.
CODE_BUDGET = 30
DOC_BUDGET = 60

# Drupal core's scaffold files and DDEV's config.yaml, which their updates
# rewrite. The comments in them are not ours to shorten.
SKIP = (
    "/.ddev/config.yaml",
    "/web/.ht.router.php",
    "/web/autoload.php",
    "/web/index.php",
    "/web/update.php",
    "/web/sites/default/default.*",
    "/web/sites/example.*",
)

# DDEV starts a line with this in the add-on files it owns, and rewrites them
# on update. Mentioning it elsewhere in a line does not count.
DDEV_GENERATED = re.compile(r"^#ddev-generated", re.MULTILINE)

# PHP has no `#` here: Drupal's standard forbids it, and `#[` opens an
# attribute.
LINE_COMMENT = {
    ".css": (),
    ".inc": ("//",),
    ".install": ("//",),
    ".js": ("//",),
    ".module": ("//",),
    ".neon": ("#",),
    ".php": ("//",),
    ".profile": ("//",),
    ".py": ("#",),
    ".sh": ("#",),
    ".theme": ("//",),
    ".twig": (),
    ".yaml": ("#",),
    ".yml": ("#",),
}

BLOCK_COMMENT = {
    ".css": ("/*", "*/"),
    ".inc": ("/*", "*/"),
    ".install": ("/*", "*/"),
    ".js": ("/*", "*/"),
    ".module": ("/*", "*/"),
    ".php": ("/*", "*/"),
    ".profile": ("/*", "*/"),
    ".theme": ("/*", "*/"),
    ".twig": ("{#", "#}"),
}

# Files a suffix does not name.
BY_NAME = {
    ".gitignore": ("#",),
}

DOC_SUFFIXES = (".md",)

LIST_ITEM = re.compile(r"^([-*+]|\d+[.)])\s+")
HUNK = re.compile(r"^@@ -\S+ \+(\d+)(?:,(\d+))? @@")
# Git ends a path holding a space with a tab.
DIFF_FILE = re.compile(r"^\+\+\+ b/(.*?)\t?$")
# A docblock's `/**`, `*/` and the `*` starting each of its lines.
GUTTER = re.compile(r"^/?\*+/?\s?")


@dataclass
class Block:
    """A run of comment lines, with the line numbers it covers."""

    path: str
    start: int
    end: int
    text: str
    budget: int

    @property
    def words(self):
        return len(self.text.split())


def syntax(path):
    """The line-comment tokens and block-comment pair a file uses."""
    name = Path(path).name
    if name in BY_NAME:
        return BY_NAME[name], None
    suffix = Path(path).suffix
    if suffix in LINE_COMMENT or suffix in BLOCK_COMMENT:
        return LINE_COMMENT.get(suffix, ()), BLOCK_COMMENT.get(suffix)
    return None, None


def wrote(written, block):
    """Whether the change wrote any line of `block`; None stands for all."""
    if written is None:
        return True
    return any(n in written for n in range(block.start, block.end + 1))


def join(pieces, written):
    """Pieces a blank line split, joined where the change wrote both.

    Splitting a new comment gains nothing, and an old one above it stays apart.
    """
    joined = None
    for piece in pieces:
        if joined and wrote(written, joined) and wrote(written, piece):
            joined.end = piece.end
            joined.text += " " + piece.text
            continue
        if joined:
            yield joined
        joined = piece
    if joined:
        yield joined


def prose(path, lines):
    """The words of a block comment, without its gutter.

    Reading stops at a docblock's first tag: `@param`, `@return` and plugin
    annotations are API detail Drupal asks for.
    """
    kept = []
    for line in lines:
        line = GUTTER.sub("", line.strip())
        # A template's `@file` docblock is core's, copied along with the
        # template it overrides, so there `@file` stops reading too.
        if line.startswith("@file") and not path.endswith(".twig"):
            line = line[len("@file"):]
        elif line.startswith("@"):
            break
        kept.append(line)
    return " ".join(kept)


def code_blocks(path, lines, written=None):
    """Comment blocks in a source file.

    A blank line inside a run of comments starts a new piece; `join` decides
    which pieces are one block.
    """
    line_tokens, block = syntax(path)
    if line_tokens is None and block is None:
        return
    run, blank = [], False
    inside, held, opened = False, [], 0
    for number, raw in enumerate(lines, 1):
        stripped = raw.strip()
        if inside:
            held.append(stripped)
            if block[1] in stripped:
                # What follows the closing token is code again.
                held[-1] = held[-1].split(block[1])[0]
                inside = False
                yield Block(path, opened, number, prose(path, held), CODE_BUDGET)
            continue
        if block and stripped.startswith(block[0]):
            rest = stripped[len(block[0]):]
            if block[1] in rest:
                text = prose(path, [rest.split(block[1])[0]])
                yield Block(path, number, number, text, CODE_BUDGET)
                continue
            inside, held, opened = True, [rest], number
            continue
        token = next((t for t in line_tokens if stripped.startswith(t)), None)
        if token and not stripped.startswith("#!"):
            body = stripped[len(token):].strip()
            if run and not blank:
                run[-1].end = number
                run[-1].text += " " + body
            else:
                run.append(Block(path, number, number, body, CODE_BUDGET))
            blank = False
        elif stripped:
            yield from join(run, written)
            run = []
        elif run:
            blank = True
    yield from join(run, written)


def docstrings(path, text):
    """Python's docstrings, which carry the prose a `#` comment would."""
    try:
        tree = ast.parse(text)
    except SyntaxError:
        return
    documented = (ast.Module, ast.FunctionDef, ast.AsyncFunctionDef, ast.ClassDef)
    for node in ast.walk(tree):
        if not isinstance(node, documented):
            continue
        body = ast.get_docstring(node)
        if body:
            first = node.body[0]
            yield Block(path, first.lineno, first.end_lineno, body, CODE_BUDGET)


def doc_blocks(path, lines):
    """Paragraphs in a Markdown file.

    A list item is its own paragraph. Fenced code, tables, headings and
    indented code outside a list are not prose.
    """
    run, start, end = [], 0, 0
    fence, in_list = None, False

    def flush():
        if run:
            return Block(path, start, end, " ".join(run), DOC_BUDGET)
        return None

    for number, raw in enumerate(lines, 1):
        stripped = raw.strip()
        if fence:
            if stripped.startswith(fence):
                fence = None
            continue
        done = None
        if stripped.startswith("```") or stripped.startswith("~~~"):
            done, fence = flush(), stripped[:3]
        elif not stripped:
            done = flush()
        elif stripped.startswith("#"):
            done, in_list = flush(), False
        elif stripped.startswith("|") or stripped.startswith("<"):
            done = flush()
        elif LIST_ITEM.match(stripped):
            done, in_list = flush(), True
            if done:
                yield done
            run, start, end = [LIST_ITEM.sub("", stripped)], number, number
            continue
        else:
            indent = len(raw) - len(raw.lstrip())
            if indent == 0:
                in_list = False
            if not run and indent >= 4 and not in_list:
                continue
            if not run:
                start = number
            run.append(re.sub(r"^>\s?", "", stripped))
            end = number
            continue
        if done:
            yield done
        run = []
    last = flush()
    if last:
        yield last


def blocks_of(path, text, written=None):
    """Every comment block in a file, whatever the file is written in."""
    if any(fnmatch.fnmatch("/" + path, pattern) for pattern in SKIP):
        return
    if DDEV_GENERATED.search(text):
        return
    lines = text.splitlines()
    if Path(path).suffix in DOC_SUFFIXES:
        yield from doc_blocks(path, lines)
        return
    yield from code_blocks(path, lines, written)
    if Path(path).suffix == ".py":
        yield from docstrings(path, text)


def added_lines(diff):
    """The lines a diff adds, per file, read from its hunk headers."""
    touched = {}
    path = None
    for line in diff.splitlines():
        match = DIFF_FILE.match(line)
        if match:
            path = match.group(1)
            touched.setdefault(path, set())
            continue
        hunk = HUNK.match(line)
        if hunk and path:
            first = int(hunk.group(1))
            count = int(hunk.group(2) or 1)
            touched[path].update(range(first, first + count))
    return touched


def git(*args):
    return subprocess.run(
        ("git", "-c", "core.quotepath=false") + args,
        capture_output=True, text=True, check=True,
    ).stdout


def ls_files(*args):
    return [path for path in git("ls-files", "-z", *args).split("\0") if path]


def changed(base):
    """What the working tree adds on top of its merge base with `base`."""
    try:
        point = git("merge-base", base, "HEAD").strip()
    except subprocess.CalledProcessError:
        point = base
    # Pinned, so no one's diff config changes what `added_lines` reads.
    diff = git(
        "diff", "--unified=0", "--diff-filter=ACMR", "--no-color",
        "--no-ext-diff", "--src-prefix=a/", "--dst-prefix=b/", point,
    )
    touched = added_lines(diff)
    # A file git has never seen is new whole. CI has none of these; someone
    # running this before `git add` does.
    for path in ls_files("--others", "--exclude-standard"):
        touched[path] = range(1, sys.maxsize)
    return touched


def report(block):
    """Print one block over budget, as a PR annotation too when CI is reading."""
    print(
        f"{block.path}:{block.start}: {block.words} words, "
        f"budget {block.budget} — {' '.join(block.text.split())[:60]}…"
    )
    if os.environ.get("GITHUB_ACTIONS"):
        print(
            f"::error file={block.path},line={block.start}::"
            f"{block.words} words, budget {block.budget}. Shorten it."
        )


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--base", help="measure only what this ref does not have")
    parser.add_argument("paths", nargs="*", help="files to read (default: all)")
    args = parser.parse_args(argv)

    # Git names paths from the repo root, so read them from there.
    top = git("rev-parse", "--show-toplevel").strip()
    explicit = [os.path.relpath(os.path.abspath(p), top) for p in args.paths]
    os.chdir(top)

    touched = changed(args.base) if args.base else None
    if explicit:
        paths = explicit
    elif touched is not None:
        paths = sorted(touched)
    else:
        paths = ls_files()

    over = []
    for path in paths:
        # AGENTS.md links to CLAUDE.md, which is read under its own name.
        if Path(path).is_symlink():
            continue
        try:
            text = Path(path).read_text(encoding="utf-8")
        except FileNotFoundError:
            # Otherwise a mistyped path passes quietly.
            print(f"{path}: no such file", file=sys.stderr)
            continue
        except (OSError, UnicodeDecodeError):
            continue
        written = None if touched is None else touched.get(path, ())
        for block in blocks_of(path, text, written):
            if block.words > block.budget and wrote(written, block):
                over.append(block)

    for block in sorted(over, key=lambda b: (b.path, b.start)):
        report(block)
    if over:
        print(f"\n{len(over)} comment(s) over budget. Say it in fewer words.")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
