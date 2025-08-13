const mix = require('laravel-mix');
const path = require('path');
const fs = require('fs');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, nous compilons les fichiers Sass
 | et JS, et maintenant nous copions également les fichiers PHP.
 |
 */

// Function to copy PHP files excluding 'includes' directory
function copyPHPExcludingIncludes(src, dest) {
  fs.readdirSync(src).forEach(file => {
    const fullPath = path.join(src, file);
    if (fs.lstatSync(fullPath).isDirectory()) {
      if (file !== 'includes') {
        copyPHPExcludingIncludes(fullPath, path.join(dest, file));
      }
    } else if (path.extname(file) === '.php') {
      mix.copy(fullPath, path.join(dest, file));
    }
  });
}

// Copy PHP files at the root of 'src' to the root of 'dist'
fs.readdirSync('src').forEach(file => {
  const fullPath = path.join('src', file);
  if (fs.lstatSync(fullPath).isFile() && path.extname(file) === '.php') {
    mix.copy(fullPath, path.join('dist', file));
  }
});

copyPHPExcludingIncludes('src', 'dist');

mix
  .copy('src/includes/**/*.php', 'dist/includes/')
  .copyDirectory('src/assets', 'dist/assets')
  .js('src/scripts/app.js', 'dist/scripts/')
  .sass('src/styles/app.scss', 'styles/', { sassOptions: { outputStyle: 'expanded' } })
  .options({
    processCssUrls: false,
    autoprefixer: {
      options: {
        browsers: [
          'chrome <= 60, last 2 firefox versions, last 2 safari versions'
        ],
        grid: true
      }
    }
  })
  .sourceMaps()
  .setPublicPath('dist')
  .browserSync({
    proxy: false,
    server: 'dist',
    files: [
      'dist/**/*'
    ]
  })
  .webpackConfig({
    devtool: 'source-map',
    resolve: {
      modules: [
        'src/scripts',
        'node_modules'
      ]
    }
  })
  .disableSuccessNotifications();
