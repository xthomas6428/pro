const mix = require('laravel-mix');
const path = require('path');

mix.setPublicPath(path.resolve('./'))

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix
    .scripts([
        'node_modules/@fortawesome/fontawesome-free/js/fontawesome.js',
        'node_modules/@fortawesome/fontawesome-free/js/all.js',
        'node_modules/jquery/dist/jquery.js',
        'app/js/external/jquery-ui.js',
        'node_modules/popper.js/dist/umd/popper.js',
        'node_modules/bootstrap/dist/js/bootstrap.js',
        'node_modules/moment/moment.js',
        'node_modules/chart.js/dist/Chart.bundle.js',
        'node_modules/bootstrap-slider/dist/bootstrap-slider.js',
        'node_modules/jquery-colpick/js/colpick.js',
    ], 'compiled/js/essential.js')

    .scripts([
        'node_modules/jquery-colpick/js/colpick.js',
        'node_modules/bootbox/bootbox.all.js',
        'node_modules/bootstrap-select/dist/js/bootstrap-select.js',
        'app/js/external/snowstorm.js',
        'app/js/external/halloween-bats.js',
        'app/js/site.js',
        'node_modules/icheck/icheck.js',
    ], 'compiled/js/site.js')

    .copy('app/js/external/tinymce', 'compiled/js/tinymce')

    .sass('app/scss/themes/prometheus/site.scss', 'compiled/css/site.css')

    .options({
        processCssUrls: false
    })

    .version();
