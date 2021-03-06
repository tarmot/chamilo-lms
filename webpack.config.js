var Encore = require('@symfony/webpack-encore');
const CopyPlugin = require('copy-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setManifestKeyPrefix('public/build/')
    // .setPublicPath('../')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()

    .addEntry('app', './assets/js/app.js')
    .addEntry('bootstrap', './assets/js/bootstrap.js')
    .addEntry('exercise', './assets/js/exercise.js')
    .addEntry('free-jqgrid', './assets/js/free-jqgrid.js')
    .addEntry('vue', './assets/vue/main.js')

    .addStyleEntry('css/app', './assets/css/app.scss')
    .addStyleEntry('css/chat', './assets/css/chat.css')
    .addStyleEntry('css/document', './assets/css/document.css')
    .addStyleEntry('css/editor', './assets/css/editor.css')
    .addStyleEntry('css/editor_content', './assets/css/editor_content.css')
    .addStyleEntry('css/markdown', './assets/css/markdown.css')
    .addStyleEntry('css/print', './assets/css/print.css')
    .addStyleEntry('css/responsive', './assets/css/responsive.css')
    .addStyleEntry('css/scorm', './assets/css/scorm.css')

    .enableSingleRuntimeChunk()
    .enableIntegrityHashes()

    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    //.enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabel((babelConfig) => {
        babelConfig.plugins.push('@babel/plugin-transform-runtime');
    }, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    .enableSassLoader()
    .enableVueLoader(function (options) {}, {runtimeCompilerBuild: false})
    .autoProvidejQuery()
    /*.enablePostCssLoader(function (options) {
        options.config = {
            path: 'postcss.config.js'
        }
    })*/
    .copyFiles([
        {
            from: './node_modules/fullcalendar/',
            pattern: /(main.js)$/,
            to: 'libs/fullcalendar/main.js'
        },
        {
            from: './node_modules/fullcalendar/',
            pattern: /(main.css)$/,
            to: 'libs/fullcalendar/main.css'
        },
        {
            from: './node_modules/multiselect-two-sides/dist/js',
            pattern: /(multiselect.js)$/,
            to: 'libs/multiselect-two-sides/dist/js/multiselect.js'
        },
        {
            from: './node_modules/pwstrength-bootstrap/dist/',
            pattern: /(pwstrength-bootstrap.js)$/,
            to: 'libs/pwstrength-bootstrap/dist/pwstrength-bootstrap.js'
        },
        {
            from: './node_modules/readmore-js',
            pattern: /(readmore.js)$/,
            to: 'libs/readmore-js/readmore.js'
        },
        {
            from: './node_modules/js-cookie/src/',
            pattern: /(js.cookie.js)$/,
            to: 'libs/js-cookie/src/js.cookie.js'
        },
        {from: './node_modules/ckeditor/', to: 'libs/ckeditor/[path][name].[ext]', pattern: /\.(js|css)$/, includeSubdirectories: false},
        {from: './node_modules/ckeditor/adapters', to: 'libs/ckeditor/adapters/[path][name].[ext]'},
        {from: './node_modules/ckeditor/lang', to: 'libs/ckeditor/lang/[path][name].[ext]'},
        {from: './node_modules/ckeditor/plugins', to: 'libs/ckeditor/plugins/[path][name].[ext]'},
        {from: './node_modules/ckeditor/skins', to: 'libs/ckeditor/skins/[path][name].[ext]'},
        /*,
        {
            from: './node_modules/mathjax/',
            pattern: /(MathJax.js)$/,
            to: 'libs/mathjax/MathJax.js'
        },*/
    ])
    // define the environment variables
    .configureDefinePlugin(options => {
        /*const env = dotEnv.config({ path: '.env.local' });
        if (env.parsed) {
            options['process.env'].APP_API_PLATFORM_URL = JSON.stringify(env.parsed.APP_API_PLATFORM_URL);
        }*/
    })
    // enable ESLint
    // .addLoader({
    //     enforce: 'pre',
    //     test: /\.(js|vue)$/,
    //     loader: 'eslint-loader',
    //     exclude: /node_modules/,
    //     options: {
    //         fix: true,
    //         emitError: false,
    //         emitWarning: true,
    //
    //     },
    // })
;

Encore.addPlugin(new CopyPlugin({
        patterns: [
            {
                from: './node_modules/mediaelement/build',
                to: 'libs/mediaelement'
            },
            {
                from: './node_modules/mediaelement-plugins/dist',
                to: 'libs/mediaelement/plugins'
            },
            {
                from: './node_modules/mathjax/config',
                to: 'libs/mathjax/config'
            },
            {
                from: './node_modules/tinymce/skins',
                to: 'libs/tinymce/skins'
            },
            {
                context: 'node_modules/moment/locale',
                from: '**/*',
                to: 'libs/locale/'
            },
        ]
    }
));


// Encore.addPlugin(new CopyPlugin([{
//     from: 'assets/css/themes/' + theme + '/images',
//     to: 'css/themes/' + theme + '/images'
// };

var themes = [
    'chamilo'
];

// Add Chamilo themes
themes.forEach(function (theme) {
    Encore.addStyleEntry('css/themes/' + theme + '/default', './assets/css/themes/' + theme + '/default.css');

    // Copy images from themes into public/build
    Encore.addPlugin(new CopyPlugin({
        patterns: [{
            from: 'assets/css/themes/' + theme + '/images',
            to: 'css/themes/' + theme + '/images'
        }]
    },
    ));
});

// Fix free-jqgrid languages files
// Encore.addPlugin(new FileManagerPlugin({
//     onEnd: {
//         move: [
//             {
//                 source: './public/public/build/free-jqgrid/',
//                 destination: './public/build/free-jqgrid/'
//             }
//         ],
//         delete: [
//             './public/public/'
//         ]
//     }
// }));

//module.exports = Encore.getWebpackConfig();

const config = Encore.getWebpackConfig();

config.resolve.alias =  {
    // If using the runtime only build
    vue$: 'vue/dist/vue.runtime.esm.js' // 'vue/dist/vue.runtime.common.js' for webpack 1
    // Or if using full build of Vue (runtime + compiler)
    // vue$: 'vue/dist/vue.esm.js'      // 'vue/dist/vue.common.js' for webpack 1
};

module.exports = config;