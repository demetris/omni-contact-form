
/*----------------------------------------------------------------------------*\
|                                                                              |
|   Filename: gulpfile.js                                                      |
|                                                                              |
\*----------------------------------------------------------------------------*/


/*------------------------------------*\
|   PACKAGES                           |
\*------------------------------------*/

    var autoprefixer    = require('gulp-autoprefixer');
    var cssnano         = require('gulp-cssnano');
    var gulp            = require('gulp');
    var pipeline        = require('readable-stream').pipeline;
    var rename          = require('gulp-rename');
    var sass            = require('gulp-sass');
    var ts              = require('gulp-typescript');
    var uglify          = require('gulp-uglify');

    var { series, parallel } = require('gulp');


/*------------------------------------*\
|   CONFIGURATION                      |
\*------------------------------------*/

var source = {
    css: [
        '../public/css/main.css',
        '../public/css/optional.css'
    ],
    js: [
        '../public/js/main.js'
    ],
    sass: [
        'sass/*.scss'
    ],
    ts: [
        'ts/*.ts'
    ]
};

var public = {
    css: '../public/css',
    js: '../public/js'
};

var sassConfig = {
    errLogToConsole: true,
    indentWidth: 4,
    linefeed: 'lf',
    outputStyle: 'expanded'
};

var autoprefixerConfig = {
    browsers: ['last 2 versions', '> 1%', 'Firefox ESR']
};

var cssnanoOptions = {
    zindex: false
};


/*------------------------------------*\
|   TASKS: TRANSFORMATIONS             |
\*------------------------------------*/

function styles() {
    return gulp
        .src(source.sass)
        .pipe(sass(sassConfig)
            .on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(gulp.dest(public.css))
    ;
}

function scripts() {
    var tsResult = gulp.src(source.ts).pipe(ts({ noImplicitAny: true, "target": "ES5" }));

    return tsResult.js.pipe(gulp.dest(public.js));
}

function compress() {
    return pipeline(
          gulp.src(source.js),
          rename({suffix: '.min'}),
          uglify(),
          gulp.dest(public.js)
    );
}


/*------------------------------------*\
|   TASKS: WATCHERS                    |
\*------------------------------------*/

function watch() {
    var stylesWatcher = gulp.watch(source.sass, { ignoreInitial: false }, styles);

    stylesWatcher.on('change', function(path) {
        console.log(`File ${path} was changed. Running styles task...`);
    });

    var scriptsWatcher = gulp.watch(source.ts, { ignoreInitial: false }, scripts);

    scriptsWatcher.on('change', function(path) {
        console.log(`File ${path} was changed. Running scripts task...`);
    });

    var compressWatcher = gulp.watch(source.js, { ignoreInitial: false }, compress);

    compressWatcher.on('change', function(path) {
        console.log(`File ${path} was changed. Running compress task...`);
    });
}


/*------------------------------------*\
|   TASKS: EXPORTS                     |
\*------------------------------------*/

exports.compress = compress;
exports.scripts = scripts;
exports.styles = styles;
exports.watch = watch;
exports.build = series(styles, scripts, compress);
