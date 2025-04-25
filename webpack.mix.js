const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        //
    ])
    .version();

if (process.env.ASSET_URL) {
    mix.options({
        fileLoaderDirs: {
            images: 'img',
            fonts: 'fonts'
        }
    });

    // Set the CDN URL for assets
    mix.setResourceRoot(process.env.ASSET_URL);
}
