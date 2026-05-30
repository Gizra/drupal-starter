# Handoff: Port JEP AI Assistant → generic `server_ai` module

**Status:** In progress. Dependencies resolved & committed; module scaffolding started. ~2 of 21 implementation tasks done.
**Branch:** `feature/server-ai-assistant` (off `main`/`cc39154d`).
**Date written:** 2026-05-30.
**Audience:** The developer taking this over end-to-end.

> ⚠️ The detailed design spec and step-by-step plan live on disk at
> `docs/superpowers/specs/2026-05-30-server-ai-assistant-port-design.md` and
> `docs/superpowers/plans/2026-05-30-server-ai-assistant-port.md`, **but
> `docs/superpowers/` is gitignored** — a fresh clone will NOT have them. They
> exist in the current working tree only. This handoff is written to be
> self-contained; if you have the working tree, read those two files too (the
> plan has full per-task TDD steps with code).

---

## 1. Goal

Port a proof-of-concept AI content assistant from the JEP project into THIS
Drupal starter as ONE new generic, reusable module: `web/modules/custom/server_ai`.

- Strip every JEP- and Judaism-specific reference (no "Jewish Education Project",
  "Jewish", "Israel", "Purim", "Passover", etc.).
- Where the source tags/searches `resource_item` content, the port operates on
  THIS starter's **News** (`news`) content type and the existing **Tags**
  (`tags`) vocabulary.
- Make secrets env-var based (no committed key files).
- Move all secrets behind a standalone Node proxy (browser gets zero secrets).

## 2. The source (read-only reference)

- **Separate git repo**, root: `/home/amitaibu/Sites/jep-onedb`
- **Branch:** `343-poc`
- The module code lives UNDER `jep-portal/`, so EVERY source path is prefixed
  with `jep-portal/`. Read any source file with:
  ```
  git -C /home/amitaibu/Sites/jep-onedb show 343-poc:jep-portal/<path>
  ```
- Source modules:
  - `jep-portal/web/modules/custom/jep_ai_assistant` — chat UI (Elm), controllers,
    OpenAI client, session writer, sensitivity, SetSkillTool.
  - `jep-portal/web/modules/custom/jep_resource_ai_mcp` — MCP tool plugins +
    `ResourceCardBuilder`.
- Source config lives in `jep-portal/config/sync/` (NOT in the modules).
- Source README (excellent context): `git -C /home/amitaibu/Sites/jep-onedb show 343-poc:jep-portal/web/modules/custom/jep_ai_assistant/README.md`

## 3. What the feature does

Two Elm-based chat pages backed by the **OpenAI Responses API**:

1. **`/ai-content-assistant`** (staff) — drives MCP tools to review and tag News
   content. Tool-call JSON shown for debugging.
2. **`/ai-search`** (visitor) — RAG over News via a semantic-search MCP tool;
   results render as cards.

OpenAI runs the tool-calling loop **server-side**, calling this site's `/_mcp`
endpoint (Drupal's MCP server, provided by the `mcp_server` contrib module) with
an OAuth 2.1 Bearer token. Conversations are persisted as `ai_chat_session`
nodes (ChatGPT-style sidebar + resume). Each user question is screened against a
configurable sensitivity policy; violations are flagged for admin review in a View.

**Original POC security flaw (we are fixing it):** the source shipped the OpenAI
token AND the MCP Bearer token to the browser. In this port, the browser holds
NO secrets — see the proxy section.

## 4. KEY DECISIONS (agreed with product owner this session — do not re-litigate)

| # | Decision | Choice |
|---|----------|--------|
| 1 | Module layout | **One merged module** `server_ai` (folds in the MCP tools + card builder; the source's two modules become one) |
| 2 | Content indexed | **News** (`news`) instead of `resource_item` |
| 3 | Tags vocabulary | reuse existing **`tags`** vocab + `field_tags` (curated) |
| 4 | RAG backend | **Port as-is**: `ai_search` + `ai_vdb_provider_pinecone` (Pinecone). Non-functional until Pinecone account/index/key configured — accepted. |
| 5 | Content-model config home | THE STARTER's **`config/sync`** (managed via `drush cex/cim`), NOT module `config/install` |
| 6 | Collection feature | **DROPPED entirely** — no `collection`/`user_generated_collection` content type, no `create_ugc_collection` tool, no `field_chat_collection`, no collection cards |
| 7 | Secrets to browser | **None.** A standalone proxy holds them |
| 8 | Proxy runtime | **Standalone generic Node service** at PROJECT ROOT `/serverless/`. Browser streams to it DIRECTLY (bypasses Pantheon's CDN). See §6. |
| 9 | Secret storage | **Environment variables** (no committed key files). Drupal reads via Key module's **env provider**; proxy reads from its own env. Set in `.ddev/config.local.yaml` (git-ignored). |
| 10 | OAuth consumer | **Auto-created** in `server_ai.install` from env vars (no manual UI step) |
| 11 | Elm app | **Recompiled** via DDEV (Elm toolchain in container; not on host) |
| 12 | Title + sensitivity calls | Kept as **separate** server-side `OpenAiClient` calls (NOT combined into one model call — explicitly out of scope) |
| 13 | MCP auth | **simple_oauth** (OAuth 2.1). The `mcp_server` author also maintains simple_oauth — deep integration. Per-tool scope `Required`/`Disabled`. |
| 14 | Supporting work | composer deps + ExistingSite/Unit tests + config all included |
| 15 | Planning docs | `docs/superpowers/**` is **gitignored** — never commit it |

## 5. CRITICAL CORRECTION discovered this session — the MCP module

The source modules import:
```php
use Drupal\mcp_server\Attribute\Tool;
use Drupal\mcp_server\Plugin\ToolPluginBase;
use Mcp\Server\ClientGateway;   // from the mcp/sdk composer package
```
This is the **`drupal/mcp_server`** project (https://www.drupal.org/project/mcp_server),
**NOT** `drupal/mcp` (a different, incompatible module that an early step
mistakenly installed and we removed).

- **Use `drupal/mcp_server` version `2.x` (`2.x-dev`)** — confirmed by product owner.
- 2.x provides `src/Plugin/ToolPluginBase.php` and `src/Attribute/Tool.php`
  (verified on disk), matching the source's tool API → tools port near-verbatim.
- 2.x runtime requires: `mcp/sdk ^0.5`, `psr/simple-cache ^3` (+ `drupal/tool`
  auto-installed). `simple_oauth` is its OAuth integration.
- The `/_mcp` route is provided by mcp_server (path `/_mcp`, permission
  `access mcp server`, `_auth: ['cookie']` by default; OAuth via per-tool config
  `authentication_mode: required`).
- The optional `mcp_server_oauth` submodule enforces OAuth scopes per tool.
- DB tooling: `mcp/sdk` ClientGateway is what `SetSkillTool::execute()` receives.

## 6. The serverless proxy (`/serverless/`) — NOT YET BUILT

**Why it exists:** Production target is **Pantheon**, whose Global CDN (Fastly)
enforces a ~59s request timeout and can buffer responses — both break SSE
streaming for a multi-step tool-calling turn if routed through Drupal. So the
browser connects **directly** to a standalone proxy that holds the secrets and
streams the OpenAI Responses SSE back. The proxy needs **no cross-service auth**
because session-writing stays in Drupal (Elm POSTs `/ai-*/session` with its own
cookie); the proxy only relays the stream.

**Location:** project root `/serverless/` (it is a separate deployable, not
Drupal module code, so it lives OUTSIDE `web/`).

**Request contract (browser → proxy):** `POST <proxyUrl>` with the JSON body the
Elm app already assembles for OpenAI, minus any auth:
`{ model, instructions, input, previous_response_id?, mcpUrl, stream: true }`.
The proxy:
1. Injects `Authorization: Bearer <OPENAI_API_KEY>`.
2. Mints (and caches until expiry) an MCP Bearer token from Drupal's
   `/oauth/token` (`client_credentials` grant, scope `ai_assistant_mcp`), then
   injects the MCP tool block `{ type: 'mcp', server_label: 'server-ai',
   server_url: mcpUrl, authorization: <minted token>, require_approval: 'never' }`.
3. Forwards to `https://api.openai.com/v1/responses` and streams the SSE back.
4. Returns scoped CORS headers (`ALLOWED_ORIGIN`).

**Proxy env (secrets):** `SERVER_AI_OPENAI_TOKEN`, `SERVER_AI_MCP_CLIENT_ID`,
`SERVER_AI_MCP_CLIENT_SECRET`, `MCP_TOKEN_URL` (the site's `/oauth/token`),
optional `MCP_SERVER_URL`, `ALLOWED_ORIGIN`, `PORT`. Node 18+ (global fetch),
minimal deps. Local dev: DDEV add-on/extra container or `node serverless/server.js`
in a side terminal. Deploy: Cloud Run / Lambda Function URL / Fly / Render.

**Future (designed-for, NOT built):** per-user/month token-budget quota
enforcement lives in the proxy (browser sends a short-lived Drupal-signed
identity token; proxy tracks spend). Contract leaves room for it without browser
changes.

A reference implementation of the proxy `server.js` + `package.json` + `README`
is fully written out in the plan file (Task 19) — copy it from there.

## 7. Secrets model (no committed key files)

All env-var based, **same three variables** feed both Drupal and the proxy:
- `SERVER_AI_OPENAI_TOKEN` — OpenAI API token.
- `SERVER_AI_MCP_CLIENT_ID` / `SERVER_AI_MCP_CLIENT_SECRET` — OAuth consumer creds.

**Drupal side:**
- `key.key.server_ai_openai_token` — Key *env* provider over `SERVER_AI_OPENAI_TOKEN`
  (used by `OpenAiClient` for title + sensitivity, PHP-only, server-side).
- `server_ai.install` auto-creates the OAuth consumer reading
  `SERVER_AI_MCP_CLIENT_ID` / `SERVER_AI_MCP_CLIENT_SECRET` (consumers stores the
  secret hashed). Idempotent; if a consumer with that client_id exists, leave it;
  if env unset, log a notice and skip (non-fatal).

**Set in `.ddev/config.local.yaml`** (git-ignored; document in README):
```yaml
web_environment:
  - SERVER_AI_OPENAI_TOKEN=sk-...
  - SERVER_AI_MCP_CLIENT_ID=...
  - SERVER_AI_MCP_CLIENT_SECRET=...
```
Non-secret OpenAI model, MCP URL, and **proxy URL** live on the `ai_assistant`
config page (`field_ai_openai_model`, `field_ai_mcp_url`, `field_ai_proxy_url`).

> ⚠️ **Security note:** During this session the product owner pasted REAL JEP
> credentials into chat (an OpenAI-MCP consumer username/password). They must
> NEVER be committed and should be **rotated on the JEP side**. The port
> hardcodes nothing — every value comes from env.

## 8. WHAT HAS BEEN DONE (committed on `feature/server-ai-assistant`)

Two logical things, currently squashed into ONE commit (`0e5k9m3a`):

### 8a. Composer dependencies (the hard part — done & verified)
Commit `0e5k9m3a` contains `composer.json` + `composer.lock` + `server_ai.info.yml`.

The dependency resolution was NON-TRIVIAL. Final state (verified: `composer audit`
== "No security vulnerability advisories found", `drush status` bootstrap ==
Successful, `composer validate` == valid):

Added to root `require`:
- `drupal/mcp_server: 2.x-dev` → installed `dev-2.x`
- `drupal/simple_oauth: ^6` → installed `6.1.1`
- `drupal/key: ^1.22` → `1.22.0`
- `drupal/ai_vdb_provider_pinecone: ^1.1@beta` → `1.1.0-beta4`
- `laminas/laminas-diactoros: ^3` → `3.8.0`  (REPLACED the old fork)
- `psr/http-message: ^2` → `2.0`
- `drupal/consumers` → `1.24.0` (TRANSITIVE via simple_oauth; NOT a root require)

Removed: `longwave/laminas-diactoros: ^2.14` (an obsolete D9.5-era fork that
pinned `psr/http-message ^1`).

Incidental security bumps (required to resolve, and they FIX live CVEs):
- `drupal/core` 11.3.10 → **11.3.11**
- `twig/twig` 3.26.0 → **3.27.1** (5 sandbox CVEs fixed)
- `symfony/polyfill-intl-idn` 1.37.0 → **1.38.1** (CVE-2026-46644 fixed)
- `drupal/ai` 1.3.5 → 1.4.0, several symfony/* → 7.4.13.

**Why this was hard (so you understand the lock):** `simple_oauth 6` →
`league/oauth2-server 9` → needs `psr/http-message v2`. The old `longwave`
diactoros fork pinned v1. Removing it from `require` wasn't enough — it stayed in
the lock and its v1 pin blocked the solve. The winning command (run by a
subagent) put the fork itself + the advisory-flagged core packages into the
update scope so the solver could drop the fork and pull PATCHED twig/polyfill:
```
ddev composer remove longwave/laminas-diactoros --no-update
ddev composer require drupal/mcp_server:2.x-dev drupal/simple_oauth:^6 drupal/key:^1.22 \
  drupal/ai_vdb_provider_pinecone:^1.1@beta laminas/laminas-diactoros:^3 psr/http-message:^2 --no-update
ddev composer update drupal/mcp_server drupal/simple_oauth drupal/key drupal/ai_vdb_provider_pinecone \
  laminas/laminas-diactoros longwave/laminas-diactoros psr/http-message guzzlehttp/psr7 drupal/consumers \
  league/oauth2-server drupal/ai drupal/ai_provider_openai drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message symfony/polyfill-intl-idn twig/twig -W
```
**Constraint honored:** composer.json `config` block stays exactly
`['sort-packages','allow-plugins']` — NO `audit.ignore` block was committed (the
product owner explicitly did not want one; the advisories were resolved by
version bumps, not suppressed).

### 8b. Module skeleton
- `web/modules/custom/server_ai/server_ai.info.yml` — created & committed (in
  `0e5k9m3a`). Dependencies declared:
  `ai:ai`, `ai:ai_search`, `ai_provider_openai:ai_provider_openai`,
  `ai_vdb_provider_pinecone:ai_vdb_provider_pinecone`, `search_api:search_api`,
  `config_pages:config_pages`, `consumers:consumers`, `simple_oauth:simple_oauth`,
  `key:key`, `mcp_server:mcp_server`, `node:node`, `taxonomy:taxonomy`,
  `paragraphs:paragraphs`, `entity_reference_revisions:entity_reference_revisions`.
  (Note: `entity_reference_revisions` is its OWN project in this codebase, hence
  `entity_reference_revisions:entity_reference_revisions`, not `paragraphs:...`.)

### PENDING right now (do this first when you resume)
- **Run DB updates** from the dependency bump (NOT yet run):
  `ddev drush updb -y` (there are pending `ai` 14001 global_guardrails key +
  `ai_content_suggestions` 14001 update hooks from the `drupal/ai` 1.3.5→1.4.0
  bump). Then `ddev drush cr`.
- Consider splitting `0e5k9m3a` is unnecessary; it's fine as one commit.
- The module is NOT yet enabled (`server_ai` will fail to enable until its config
  exists — that's expected; see plan stages 4 & 14).

## 9. WHAT REMAINS — the implementation plan (21 tasks)

The full plan with copy-paste TDD steps and code is in
`docs/superpowers/plans/2026-05-30-server-ai-assistant-port.md` (on disk, gitignored).
Execution method chosen: **subagent-driven development** (fresh subagent per task,
then spec-compliance review, then code-quality review, then commit). Summary of
remaining tasks:

- **Task 1 (deps)** — ✅ DONE (see §8a). NOTE: the plan still names `drupal/mcp` —
  that was WRONG; it's `drupal/mcp_server` 2.x (see §5). The plan's info.yml also
  said `mcp:mcp_server` — the committed file correctly uses `mcp_server:mcp_server`.
- **Task 2 (info.yml)** — ✅ DONE (see §8b).
- **Task 3** — Port `SensitivityVerdict` + `OpenAiClientInterface` (namespace only).
- **Task 4** — Port `OpenAiClient` (TDD; Unit test with Guzzle MockHandler).
  Renames: `KEY_OPENAI_TOKEN='server_ai_openai_token'`, `DEFAULT_MODEL='gpt-4o'`
  (source had placeholder `gpt-5.5`), `CONFIG_PAGES_TYPE='ai_assistant'`.
- **Task 5** — Port `ResourceCardBuilder` → `CardBuilder` (News fields).
- **Task 6** — Port `SessionWriter` (DROP collection param + `field_chat_collection`).
- **Task 7** — Port 3 controllers (`AiChatControllerBase`, `AiAssistantController`,
  `AiSearchController`). **`appConfig()` must return NO secrets** — only
  `systemPrompt`, `openaiModel`, `mcpUrl`, `proxyUrl`, page chrome. DELETE the
  source's `mintMcpToken()`/`readUserPassword()`/token reads. Generic page chrome.
- **Task 8** — `services.yml`, `routing.yml` (6 routes), `permissions.yml`,
  `links.menu.yml`, `libraries.yml`.
- **Task 9** — `SetSkillTool` MCP plugin (id `set_assistant_skill`).
- **Task 10** — News read MCP tools: `GetNewsItemTool`, `ListNewsItemsTool`,
  `ListTagsTool`, `SearchNewsTool`.
- **Task 11** — `SetNewsTagsSuggestionTool` (drop moderation-state write — News
  has no workflow).
- **Task 12** — `server_ai.install`: generic prompts + sensitivity policy +
  auto-create OAuth consumer from env (see §7).
- **Task 13** — Content-model config into `config/sync` (see §10). Strip
  `uuid:`/`_core:` from copied source YAML so they import as new.
- **Task 14** — `key.key.server_ai_openai_token.yml` (env provider) + enable
  modules + `drush cim` + verify.
- **Task 15** — `js/boot.js` rewired to `proxyUrl` (no auth header, no
  server_label/MCP injection — proxy does that).
- **Task 16** — Elm sources into `elm/`, genericize (remove collection), recompile
  via a `ddev elm-build` custom command, output `js/elm-main.js`. Tailwind glob.
- **Task 17** — phpstan/phpcs pass over the whole module.
- **Task 18** — ExistingSite + Unit tests (ported, News-based, ExistingSiteBase).
- **Task 19** — Build the Node proxy at `/serverless/` (code is in the plan).
- **Task 20** — Module `README.md` (generic).
- **Task 21** — Final genericization sweep + full verification + final review.

## 10. Reference tables

### Global rename rules (apply to every ported file)
| From | To |
|------|-----|
| `Drupal\jep_ai_assistant\` and `Drupal\jep_resource_ai_mcp\` | `Drupal\server_ai\` |
| service `jep_ai_assistant.openai_client` | `server_ai.openai_client` |
| service `jep_ai_assistant.session_writer` | `server_ai.session_writer` |
| service `jep_resource_ai_mcp.resource_card_builder` | `server_ai.card_builder` |
| route/lib/perm/menu ids `jep_ai_assistant.*` | `server_ai.*` |
| JS behavior `Drupal.behaviors.jepAiAssistant` | `serverAiAssistant` |
| markup attr `data-jep-ai-app` | `data-server-ai-app` |
| settings key `drupalSettings.jepAiAssistant` | `drupalSettings.serverAi` |
| once tag `jep-ai-app` | `server-ai-app` |
| MCP `server_label: 'jep-portal'` | `'server-ai'` (now set in proxy, not boot.js) |
| Key id `ai_assistant_openai_token` | `server_ai_openai_token` |
| default model `gpt-5.5` | `gpt-4o` |

### Field / bundle mapping (News-based)
| Source (JEP) | Target (starter) | Notes |
|---|---|---|
| `resource_item` node | `news` node | |
| `body` / `field_res_item_description` | `field_body` | News body (text_long) + node title |
| `field_res_item_preview_image` / `_header_image` | `field_featured_image` | media:image |
| `resource_topic` vocabulary | `tags` vocabulary | existing |
| `field_resource_topic` (curated) | `field_tags` | existing on News |
| `field_res_topic_ai_suggestion` | `field_tags_ai_suggestion` | **NEW** field on News (entity_ref → tags, unlimited) |
| `user_generated_collection` + `field_collection_*` | — | DROPPED |
| search_api index `rag_resource_items` (an `ai_search.index.*`) | `rag_news` | new index over News |
| search_api server `rag_pinecone` | `rag_pinecone` | port as-is |

### MCP tool renames (collection tool dropped)
| Source tool id | Ported tool id | Operates on |
|---|---|---|
| `get_resource_item` | `get_news_item` | `news`: title, body, current + suggested tags |
| `list_resource_items` | `list_news_items` | `news`, paged, filter by has-suggestion |
| `search_resources` | `search_news` | `news` via search_api/Pinecone (`rag_news`) |
| `list_topic_terms` | `list_tags` | `tags` vocabulary |
| `set_resource_topic_ai_suggestion` | `set_news_tags_suggestion` | writes `field_tags_ai_suggestion` |
| `set_assistant_skill` | `set_assistant_skill` (kept) | overwrites admin system prompt |
| `create_ugc_collection` | **DROPPED** | — |

### Chat-session content model (config/sync, generic — keep shape)
- Node type `ai_chat_session` + fields `field_session_rows`,
  `field_session_response_id`, `field_session_flagged`, `field_session_flag_reason`,
  `field_session_flagged_row`, `field_session_policy_snapshot`, `field_session_is_admin`.
- Paragraph types `ai_user_question` (`field_chat_question`, `field_question_flagged`)
  and `ai_assistant_response` (`field_chat_answer`, `field_chat_resources` → refs
  **News**). DROP `field_chat_collection`.
- `config_pages` type `ai_assistant` with `field_ai_prompt_admin`,
  `field_ai_prompt_search`, `field_ai_sensitivity_policy`, `field_ai_openai_model`,
  `field_ai_mcp_url`, and NEW `field_ai_proxy_url`.
- View `flagged_ai_sessions` at `/admin/content/flagged-ai-sessions`.
- OAuth scope `simple_oauth.oauth2_scope.ai_assistant_mcp.yml`.
- Source config inventory (43 files): list with
  `git -C /home/amitaibu/Sites/jep-onedb ls-tree -r --name-only 343-poc config/sync/ | grep -iE 'ai_chat_session|ai_assistant|flagged_ai|ai_user_question|ai_assistant_response|res_topic_ai|rag_resource|chat_question|chat_answer|chat_resources|session_|simple_oauth.oauth2_scope.ai_assistant'`
  **Skip:** file-based `key.key.ai_assistant_*.yml` (replaced by env Key),
  `field_chat_collection` storage/field (dropped), `search_api.index.jep_portal_dev`
  / `search_api.server.jep` (JEP site search, out of scope),
  `views.view.ai_review_resource_items` (JEP review queue, out of scope).

## 11. Environment / tooling facts

- Working dir: `/home/amitaibu/Sites/drupal-starter`. DDEV-based. Run everything
  via `ddev`: `ddev composer`, `ddev drush`, `ddev phpunit`, `ddev phpcs`, `ddev phpstan`.
- `ddev phpunit <path>` / `ddev phpunit --filter <name>`. Tests use
  `weitzman\DrupalTestTraits\ExistingSiteBase` (project standard; prefer over
  Kernel/Unit except the pure OpenAiClient unit test).
- This is **Drupal 11**, PHP 8.3. Config sync dir = `config/sync`.
- The News content type (`news`) exists with: `field_body` (text_long, required),
  `field_featured_image` (entity_ref → media:image), `field_tags` (entity_ref →
  `tags` vocab), `field_publish_date`, `field_metatag`. Only ONE vocabulary
  exists: `tags`. `paragraphs` is enabled.
- mcp_server provides the `/_mcp` endpoint. For OpenAI to reach it during dev,
  use `ddev share` (ngrok) and set the MCP URL on the config page to
  `https://<id>.ngrok-free.app/_mcp`.

## 12. Gotchas observed this session

- **Shell output is flaky** in this environment — long Bash output sometimes
  duplicates or truncates lines. Prefer small, single-purpose commands; write
  results to a temp file and Read it back when in doubt.
- **`docs/superpowers/` is gitignored** (added to `.gitignore`). The design spec
  + plan live there on disk. Do NOT `git add docs/superpowers`. THIS handoff is
  under `/specs` precisely so it IS tracked/committed.
- The `consumers` entity field names (`client_id`, `secret`, `grant_types`,
  `scopes`, `user_id`, `confidential`, `third_party`) vary by version — verify
  against the installed `consumers 1.24` schema before writing the install hook
  (`ddev drush ev` to dump field definitions; or create one consumer in the UI to
  compare).
- `mcp_server` 2.x is a dev release (no stable tag) — pin `2.x-dev`.
- Pinecone (`ai_vdb_provider_pinecone`) is beta and inert until configured —
  tests must NOT depend on live Pinecone; `search_news` returns empty until wired.
- `composer install` on this composer build does NOT accept `--no-audit`;
  `composer require`/`update` do.

## 13. Definition of done

- All 21 plan tasks complete; `ddev phpcs web/modules/custom/server_ai` +
  `ddev phpstan` clean; `ddev phpunit web/modules/custom/server_ai` green.
- `ddev drush en server_ai -y && ddev drush cim -y && ddev drush cex -y` round-trips
  with no diff (config stable).
- Genericization sweep returns nothing:
  `grep -riE 'jep|jewish|israel|passover|purim|resource_item|resource_topic|gpt-5\.5|user_generated_collection|jep-portal|field_res_|field_chat_collection' web/modules/custom/server_ai serverless config/sync | grep -v elm-stuff`
- No secret committed: `git grep -nE 'sk-[A-Za-z0-9]{20}|"password"|"secret"' -- web/ serverless/ config/` → none; Key/consumer configs are env-only.
- Browser receives zero secrets: the `/ai-*/config` JSON has no token (verify in DevTools).
- Elm recompiled from source via `ddev elm-build`; no hand-patched blob.
- `/serverless/` proxy runs and streams; README documents env + deploy.
- Use `superpowers:finishing-a-development-branch` to wrap up (PR, etc.).
