let Encore = require('@symfony/webpack-encore');

Encore
    .disableSingleRuntimeChunk()
    .setOutputPath('public/ui/')
    .setPublicPath('/ui')
    .addEntry('app', './assets/js/App.js')
    .addStyleEntry('ui', './assets/scss/App.scss')
    .enableSassLoader(function(options) {}, {
        resolveUrlLoader: false
    })
;

let config = Encore.getWebpackConfig();
config.output.library = 'mogboard';
config.output.libraryExport = "default";
config.output.libraryTarget = 'var';
module.exports = config;
