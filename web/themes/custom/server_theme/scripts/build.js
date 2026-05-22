'use strict';

// One-shot build for JS, fonts, and images. CSS is handled separately by the
// tailwindcss CLI (build:css) since it has its own optimized pipeline.
// All three asset types run in parallel via Promise.all.

const { minify } = require('terser');
const { optimize: optimizeSvg } = require('svgo');
const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const svgoConfig = require('../svgo.config.js');
// Resolved at startup so the path is correct regardless of cwd at call time.
const imageminBin = path.resolve('node_modules/.bin/imagemin');

// Recursively walk a directory, invoking callback for every file.
function walkDir(dir, callback) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walkDir(fullPath, callback);
    } else {
      callback(fullPath);
    }
  }
}

// Copy a directory tree, creating destination directories as needed.
function copyRecursive(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const srcPath = path.join(src, entry.name);
    const destPath = path.join(dest, entry.name);
    if (entry.isDirectory()) {
      copyRecursive(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
    }
  }
}

async function buildJs() {
  fs.mkdirSync('dist/js', { recursive: true });
  const jsFiles = [];
  walkDir('src/js', (f) => { if (f.endsWith('.js')) jsFiles.push(f); });

  // Minify all files concurrently; terser is async so Promise.all gives a
  // meaningful speedup when there are many JS files.
  await Promise.all(jsFiles.map(async (src) => {
    const dest = path.join('dist/js', path.relative('src/js', src));
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    const result = await minify(fs.readFileSync(src, 'utf-8'), { compress: true, mangle: true });
    fs.writeFileSync(dest, result.code);
    console.log(`  JS:    ${src}`);
  }));
}

function buildFonts() {
  copyRecursive('src/fonts', 'dist/fonts');
  console.log('  Fonts: src/fonts -> dist/fonts');
}

function buildImages() {
  // Copy everything first so directory structure exists before optimization.
  copyRecursive('src/images', 'dist/images');

  // imagemin-cli flattens output when given a glob, so we group files by their
  // containing directory and run imagemin once per directory, passing --out-dir
  // equal to that same directory so optimized files overwrite the copies in place.
  const rasterByDir = new Map();
  walkDir('dist/images', (file) => {
    if (/\.(jpg|jpeg|png)$/i.test(file)) {
      const dir = path.dirname(file);
      if (!rasterByDir.has(dir)) rasterByDir.set(dir, []);
      rasterByDir.get(dir).push(file);
    }
  });
  for (const [dir, files] of rasterByDir) {
    const { status } = spawnSync(imageminBin, [...files, `--out-dir=${dir}`], { stdio: 'inherit' });
    if (status !== 0) throw new Error(`imagemin failed in ${dir}`);
  }

  // svgo is used via its JS API (already a direct dependency) rather than
  // spawning a subprocess, so we can pass the project's svgo.config.js directly.
  walkDir('dist/images', (file) => {
    if (file.endsWith('.svg')) {
      const { data } = optimizeSvg(fs.readFileSync(file, 'utf-8'), svgoConfig);
      fs.writeFileSync(file, data);
      console.log(`  SVG:   ${file}`);
    }
  });
}

async function main() {
  console.log('Building assets...');
  await Promise.all([
    buildJs(),
    Promise.resolve().then(buildFonts),
    Promise.resolve().then(buildImages),
  ]);
  console.log('Done.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
