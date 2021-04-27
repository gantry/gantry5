'use strict';

var gulp            = require('gulp'),
    argv            = require('yargs').argv,
    gutil           = require('gulp-util'),
    gulpif          = require('gulp-if'),
    uglify          = require('gulp-uglify'),
    rename          = require('gulp-rename'),
    buffer          = require('vinyl-buffer'),
    source          = require('vinyl-source-stream'),
    merge           = require('merge-stream'),
    sourcemaps      = require('gulp-sourcemaps'),
    browserify      = require('browserify'),
    watchifyModule  = require('watchify'),
    sass            = require('gulp-sass'),

    prod            = !!(argv.p || argv.prod || argv.production),
    watch           = false;

var paths = {
    js: [
        { // admin
            in: './application/main.js',
            out: './js/main.js'
        }
    ],
    css: [
        { // admin
            in: './scss/admin.scss',
            out: './css-compiled/g-admin.css',
            load: '../../engines/common/nucleus/scss'
        }
    ]
};

// -- DO NOT EDIT BELOW --

var compileCSS = function(app) {
    var _in = app.in,
        _load = app.load || false,
        _dest = app.out.substring(0, app.out.lastIndexOf('/')),
        _out  = app.out.split(/[\\/]/).pop(),
        _maps = '../' + app.in.substring(0, app.in.lastIndexOf('/')).split(/[\\/]/).pop();

    gutil.log(gutil.colors.blue('*'), 'Compiling', _in);

    var options = {
        sourceMap: !prod,
        includePaths: _load,
        outputStyle: prod ? 'compact' : 'expanded'
    };

    return gulp.src(_in)
        .pipe(sass(options).on('error', sass.logError))
        .on('end', function() {
            gutil.log(gutil.colors.green('√'), 'Saved ' + _in);
        })
        .pipe(gulpif(!prod, sourcemaps.write('.', { sourceRoot: _maps, sourceMappingURL: function() { return _out + '.map'; } })))
        .pipe(rename(_out))
        .pipe(gulp.dest(_dest));
};

var compileJS = function(app, watching) {
    var _in = app.in,
        _out = app.out.split(/[\\/]/).pop(),
        _dest = app.out.substring(0, app.out.lastIndexOf('/')),
        _maps = './' + app.in.substring(0, app.in.lastIndexOf('/')).split(/[\\/]/).pop();

    if (!watching) {
        gutil.log(gutil.colors.blue('*'), 'Compiling', _in);
    }

    var bundle = browserify({
        entries: [_in],
        debug: !prod,

        cache: {},
        packageCache: {},
        fullPaths: false
    });

    if (watching) {
        bundle = watchifyModule(bundle);
        bundle.on('update', function(files) {
            gutil.log(gutil.colors.red('>'), 'Change detected in', files.join(', '), '...');
            bundleShare(bundle, _in, _out, _maps, _dest);
        });
    }

    return bundleShare(bundle, _in, _out, _maps, _dest);
};

var bundleShare = function(bundle, _in, _out, _maps, _dest) {
    return bundle.bundle()
        .on('end', function() {
            gutil.log(gutil.colors.green('√'), 'Saved ' + _in);
        })
        .pipe(source(_out))
        .pipe(buffer())
        // sourcemaps start
        .pipe(gulpif(!prod, sourcemaps.init({ loadMaps: true })))
        .pipe(gulpif(prod, uglify()))
        .on('error', gutil.log)
        .pipe(gulpif(!prod, sourcemaps.write('.', { sourceRoot: _maps })))
        // sourcemaps end
        .pipe(gulp.dest(_dest));
};

function watchify() {
    watch = true;

    // watch js
    paths.js.forEach(function(app) {
        var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        return compileJS(app, true);
    });
}

function js() {
    var streams = [];
    paths.js.forEach(function(app) {
        streams.push(compileJS(app));
    });

    return merge(streams);
}

function css(done) {
    var streams = [];
    paths.css.forEach(function(app) {
        streams.push(compileCSS(app, done));
    });

    return merge(streams);
}

exports.watchify = watchify;
exports.watch = gulp.series(watchify, function() {
    // watch css
    paths.css.forEach(function(app) {
        var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        gulp.watch(_path + '/**/*.scss', function(event) {
            gutil.log(gutil.colors.red('>'), 'File', event.path, 'was', event.type);
            return compileCSS(app);
        });
    });
});

exports.css = css;
exports.js = js;
exports.all = gulp.series(css, js);
exports.default = gulp.series(css, js);
