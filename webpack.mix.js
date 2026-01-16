const mix = require('laravel-mix');
require('laravel-mix-serve');

mix.setPublicPath('www');

mix
  // Frontend
  .copyDirectory('src/assets', 'www/assets')
  .js('src/scripts/app.js', 'www/scripts')
  .sass('src/styles/app.scss', 'www/styles')

  // PHP et includes
  .copyDirectory('src/includes', 'www/includes')
  .copyDirectory('src/data', 'www/data')
  .copyDirectory('src/actions', 'www/actions')
  .copyDirectory('src/auth', 'www/auth')
  .copyDirectory('src/config', 'www/config')
  .copyDirectory('src/cache', 'www/cache')
  .copy('src/*.php', 'www')

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
  .browserSync({
    proxy: '127.0.0.1:5000',
    files: ['www/**/*']
  })
  .serve('php -S 127.0.0.1:5000 -t ./www', {
    verbose: true,
    watch: true,
    dev: true,
    prod: false
  })
  .webpackConfig({
    mode: 'development',  // ou 'production'
    devtool: 'source-map',
    resolve: {
      modules: ['src/scripts', 'node_modules']
    },
})

// disable manifest
Mix.manifest.refresh = function(){ return void(0); };
