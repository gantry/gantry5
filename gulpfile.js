'use strict';

var gulp       = require('gulp'),
    argv       = require('yargs').argv,
    gutil      = require('gulp-util'),
    gulpif     = require('gulp-if'),
    uglify     = require('gulp-uglify'),
    buffer     = require('vinyl-buffer'),
    source     = require('vinyl-source-stream'),
    merge      = require('merge-stream'),
    sourcemaps = require('gulp-sourcemaps'),
    browserify = require('browserify'),
    watchify   = require('watchify'),
    sass       = require('gulp-ruby-sass'),

    prod       = !!(argv.p || argv.prod || argv.production),
    watch      = false;

var paths = {
    js: [
        { // admin
            in: './platforms/common/application/main.js',
            out: './platforms/common/js/main.js'
        },
        { // frontend
            in: './assets/common/application/main.js',
            out: './assets/common/js/main.js'
        }
    ],
    css: [
        { // admin
            in: './platforms/common/scss/admin.scss',
            out: './platforms/common/css-compiled/admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // admin - joomla
            in: './platforms/joomla/com_gantry5/admin/scss/joomla-admin.scss',
            out: './platforms/joomla/com_gantry5/admin/css-compiled/joomla-admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // nucleus
            in: './engines/common/nucleus/scss/nucleus.scss',
            out: './engines/common/nucleus/css-compiled/nucleus.css'
        }
    ]
};

// -- DO NOT EDIT BELOW --

var compileCSS = function(app) {
    var _in = app.in,
        _out = app.out.split(/[\\/]/).pop(),
        _load = app.load || false,
        _dest = app.out.substring(0, app.out.lastIndexOf('/')),
        _maps = '../' + app.in.substring(0, app.in.lastIndexOf('/')).split(/[\\/]/).pop();

    gutil.log(gutil.colors.blue('*'), 'Compiling', _in);

    var options = {
        container: _out,
        sourcemap: !prod,
        loadPath: _load,
        style: prod ? 'compact' : 'expanded',
        lineNumbers: !prod,
        trace: !prod
    };

    return sass(_in, options)
        .on('end', function() {
            gutil.log(gutil.colors.green('√'), 'Saved ' + _in);
        })
        .on('error', gutil.log)
        .pipe(gulpif(!prod, sourcemaps.write('.', { sourceRoot: _maps })))
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
        fullPaths: true
    });

    if (watching) {
        bundle = watchify(bundle);
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

gulp.task('watchify', function() {
    watch = true;

    // watch js
    paths.js.forEach(function(app) {
        var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        return compileJS(app, true);
    });

});

gulp.task('js', function() {
    var streams = [];
    paths.js.forEach(function(app) {
        streams.push(compileJS(app));
    });

    return merge(streams);
});

gulp.task('css', function(done) {
    var streams = [];
    paths.css.forEach(function(app) {
        streams.push(compileCSS(app, done));
    });

    return merge(streams);
});

gulp.task('watch', ['watchify'], function() {
    // watch css
    paths.css.forEach(function(app) {
        var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        gulp.watch(_path + '/**/*.scss', function(event) {
            gutil.log(gutil.colors.red('>'), 'File', event.path, 'was', event.type);
            return compileCSS(app);
        });
    });
});

gulp.task('all', ['css', 'js']);
gulp.task('default', ['all']);