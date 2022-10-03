# Theme Development

## The directory structure
 - `src/` - put all source stylesheets images, fonts, etc here.
 - `dist/` - `.gitignore`-ed path where the compiled / optimized files live,
the theme should refer the assets from that directory.

For theme development, it's advisable to entirely turn off
caching: https://www.drupal.org/node/2598914

## Compiling assests
All assets are compiled at DDEV startup using `ddev robo theme:compile-debug`.

To compile assets during development run:
```bash
ddev theme:watch
```
This will compile Tailwind styles, JS & Images and keep watching for any
changes. This also rebuilds Drupal cache on any change and thus could be a
little slower.

If you just want to compile & watch tailwind, run `ddev theme:watch-css`.
This will be much faster.

## Compilation & Watch process
### CSS
We use postcss (with tailwind plugin) to compile CSS assets.
See `postcss.config.js` for the compile pipeline. To oversimplify:
1. Tailwind plugin is used to compile the files.
2. Followed by nanocss plugin to minify.

### JS & Images
We have two compilation modes for JS & Images:
1. Simple compilation
2. Optimized compilation

#### Simple compilation
In simple compilation, the js & images files are simply copied from `/src` to
their corresponding `/dist` directories.

For simple compilation, run `ddev robo theme:compile`

#### Optimized compilation
In optimized compilation:
- The js files from `/src/js` are first minified using Robo's `taskMinify` task
and then copied over to `dist/js`. This is done using `Patchwork/JSqueeze`
package.
- The image files are also optimized using Robo's `taskImageMinify` task and
then copied over to `dist/images`. See `/Robo/Tasks/Assets/ImageMinify` to see
the list of optimizers used.

For optimized compilation, run `ddev robo theme:compile-debug`

Please note that in both cases, the css is also compiled using
postcss (with tailwind plugin).
