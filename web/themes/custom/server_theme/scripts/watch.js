'use strict';

// File watcher for JS, fonts, and images. CSS is handled separately by the
// tailwindcss CLI (watch:css) since it has its own optimized watch mode.
// ignoreInitial: true on all watchers because an initial build is expected to
// have been run first (theme:watch does this automatically).

const chokidar = require('chokidar');
const { minify } = require('terser');
const { optimize: optimizeSvg } = require('svgo');
const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const svgoConfig = require('../svgo.config.js');
// Resolved at startup so the path is correct regardless of cwd at call time.
const imageminBin = path.resolve('node_modules/.bin/imagemin');

// Returns the dist path that mirrors a given src path.
function destPath(src, srcBase, destBase) {
  return path.join(destBase, path.relative(srcBase, src));
}

// JS: minify on add/change.
chokidar
  .watch('src/js/**/*.js', { ignoreInitial: true })
  .on('add', processJs)
  .on('change', processJs);

async function processJs(src) {
  try {
    const dest = destPath(src, 'src/js', 'dist/js');
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    const { code } = await minify(fs.readFileSync(src, 'utf-8'), { compress: true, mangle: true });
    fs.writeFileSync(dest, code);
    console.log(`JS:    ${src}`);
  } catch (err) {
    // Log but don't crash the watcher process on a syntax error in one file.
    console.error(`Error processing ${src}:`, err.message);
  }
}

// Fonts: copy on add/change.
chokidar
  .watch('src/fonts/**/*', { ignoreInitial: true })
  .on('add', processFont)
  .on('change', processFont);

function processFont(src) {
  const dest = destPath(src, 'src/fonts', 'dist/fonts');
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.copyFileSync(src, dest);
  console.log(`Font:  ${src}`);
}

// Images: handle new directories and file add/change.
// addDir is listened to separately so that creating a subdirectory immediately
// makes the dist mirror available, even before any files are added inside it.
chokidar
  .watch('src/images/**/*', { ignoreInitial: true })
  .on('addDir', (src) => {
    const dest = destPath(src, 'src/images', 'dist/images');
    fs.mkdirSync(dest, { recursive: true });
    console.log(`Dir:   ${src}`);
  })
  .on('add', processImage)
  .on('change', processImage);

function processImage(src) {
  try {
    const dest = destPath(src, 'src/images', 'dist/images');
    // mkdir -p here handles the case where a file is added to a new subdirectory
    // before the addDir event has been processed.
    fs.mkdirSync(path.dirname(dest), { recursive: true });

    if (/\.(jpg|jpeg|png)$/i.test(src)) {
      // Pass the src file and write the optimized result directly to the same
      // dest directory so the optimized version replaces the unoptimized copy.
      const { status } = spawnSync(imageminBin, [src, `--out-dir=${path.dirname(dest)}`], { stdio: 'inherit' });
      if (status !== 0) console.error(`imagemin failed: ${src}`);
    } else if (/\.svg$/i.test(src)) {
      const { data } = optimizeSvg(fs.readFileSync(src, 'utf-8'), svgoConfig);
      fs.writeFileSync(dest, data);
    } else {
      fs.copyFileSync(src, dest);
    }

    console.log(`Image: ${src}`);
  } catch (err) {
    // Log but don't crash the watcher process on a bad file.
    console.error(`Error processing ${src}:`, err.message);
  }
}
