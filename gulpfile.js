'use strict';

const gulp = require('gulp');
const fs = require('fs');

let paths;
const convertBytes = function(bytes) {
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes === 0) {
        return '0 Byte';
    }

    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));

    return Math.round(((bytes / Math.pow(1024, i) * 100)) / 100) + ' ' + sizes[i];
};


// You can install or update NPM dependencies across the whole project via the supported commands:
//      -update, --update, -up, --up, -install, --install, -inst, --inst, -go, --go, -deps, --deps
// They all execute the same command and it will be smart enough to know whether to install or update the deps
if (process.argv.slice(2).join(',').match(/(-{1,2}update|-{1,2}up|-{1,2}install|-{1,2}inst|-{1,2}go|-{1,2}deps)/)) {
    gulp.task('default', function() {
        paths = ['./', 'platforms/common', 'assets/common', 'engines/common/nucleus'];
        var exec = require('child_process').exec, child;
        paths.forEach(function(path) {
            var nodes  = path.replace(/(\/$)/g, '') + '/' + 'node_modules',
                method = 'install',
                exists = false;

            try { exists = fs.lstatSync(nodes).isDirectory(); }
            catch (e) {}
            if (exists) { method = 'update --save --save-dev'; }

            console.log((exists ? 'Updating' : "Installing") + " JS dependencies in: " + path);
            child = exec('cd ' + path + ' && npm ' + method + ' --silent',
                function(error, stdout, stderr) {
                    if (stdout) { console.log('Completed `' + path + '`:', "\n", stdout); }
                    if (stderr) { console.log('Error `' + path + '`:' + stderr); }
                    if (error !== null) { console.log('Exec error `' + path + '`:' + error); }
                });
        });
    });

    return;
}

var argv            = require('yargs').argv,
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
    jsonminify      = require('gulp-jsonminify'),
    sass            = require('gulp-sass'),

    prod            = !!(argv.p || argv.prod || argv.production),
    watchType       = (argv.css && argv.js) ? 'all' : (argv.css ? 'css' : (argv.js ? 'js' : 'all')),
    watch           = false;

paths = {
    js: [
        { // admin
            in: './platforms/common/application/main.js',
            out: './platforms/common/js/main.js',
            expose: [{ lib: './platforms/common/js/tooltips.js', require: 'ext/tooltips' }]
        },
        { // frontend
            in: './assets/common/application/main.js',
            out: './assets/common/js/main.js'
        }
    ],
    css: [
        { // admin
            in: './platforms/common/scss/admin.scss',
            out: './platforms/common/css-compiled/g-admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // admin - joomla
            in: './platforms/joomla/com_gantry5/admin/scss/joomla-admin.scss',
            out: './platforms/joomla/com_gantry5/admin/css-compiled/joomla-g-admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // admin - wordpress
            in: './platforms/wordpress/gantry5/admin/scss/wordpress-admin.scss',
            out: './platforms/wordpress/gantry5/admin/css-compiled/wordpress-g-admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // admin - grav
            in: './platforms/grav/gantry5/admin/scss/grav-admin.scss',
            out: './platforms/grav/gantry5/admin/css-compiled/grav-g-admin.css',
            load: './engines/common/nucleus/scss'
        },
        { // nucleus
            in: './engines/common/nucleus/scss/nucleus.scss',
            out: './engines/common/nucleus/css-compiled/nucleus.css'
        },
        { // nucleus - joomla 3
            in: './engines/joomla/nucleus/scss/joomla.scss',
            out: './engines/joomla/nucleus/css-compiled/joomla.css',
            load: './engines/common/nucleus/scss'
        },
        { // bootstrap - joomla 4
            in: './engines/joomla/nucleus/scss/bootstrap5.scss',
            out: './engines/joomla/nucleus/css-compiled/bootstrap5.css',
            load: './engines/common/nucleus/scss'
        },
        { // nucleus - wordpress
            in: './engines/wordpress/nucleus/scss/wordpress.scss',
            out: './engines/wordpress/nucleus/css-compiled/wordpress.css',
            load: './engines/common/nucleus/scss'
        }
    ],
    minify: [
        { // google fonts
            in: './platforms/common/js/google-fonts.json',
            out: './platforms/common/js/google-fonts.json'
        }
    ]
};

// -- DO NOT EDIT BELOW --

var compileCSS = function(app, done) {
    var _in   = app.in,
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

    var stream = gulp.src(_in, { sourcemaps: !prod })
        .pipe(sass(options).on('error', function(err) {
            sass.logError.call(this, err);
            if (done) done(err);
        }))
        .on('end', function() {
            gutil.log(gutil.colors.green('√'), 'Saved ' + _in);
            if (done) done();
        })
        .pipe(gulpif(!prod, sourcemaps.write('.', { sourceRoot: _maps, sourceMappingURL: function() { return _out + '.map'; }})))
        .pipe(rename(_out))
        .pipe(gulp.dest(_dest));
    
    return stream;
};

var compileJS = function(app, watching) {
    var _in   = app.in,
        _out  = app.out.split(/[\\/]/).pop(),
        _exp  = app.expose,
        _dest = app.out.substring(0, app.out.lastIndexOf('/')),
        _maps = './' + app.in.substring(0, app.in.lastIndexOf('/')).split(/[\\/]/).pop();

    if (!watching) {
        gutil.log(gutil.colors.blue('*'), 'Compiling', _in);
    }

    var bundle = browserify({
        entries: [_in],
        debug: !prod,
        watch: watching,

        cache: {},
        packageCache: {},
        fullPaths: false
    });

    if (_exp) {
        _exp.forEach(function(expose) {
            bundle.require(expose.lib, { expose: expose.require });
        });
    }


    if (watching) {
        bundle = watchifyModule(bundle);
        bundle.on('log', function(msg) {
            var bytes = msg.match(/^(\d{1,})\s/)[1];
            msg = msg.replace(/^\d{1,}\sbytes/, convertBytes(bytes));
            gutil.log(gutil.colors.green('√'), 'Done, ', msg, '...');
        });
        bundle.on('update', function(files) {
            gutil.log(gutil.colors.red('>'), 'Change detected in', files.join(', '), '...');
            return bundleShare(bundle, _in, _out, _maps, _dest);
        });
    }

    return bundleShare(bundle, _in, _out, _maps, _dest);
};

var bundleShare = function(bundle, _in, _out, _maps, _dest) {
    return bundle.bundle()
        .on('error', function(error) {
            gutil.log('Browserify', '' + error);
        })
        .on('end', function() {
            gutil.log(gutil.colors.green('√'), 'Saved ' + _in);
        })
        .pipe(source(_out))
        .pipe(buffer())
        // sourcemaps start
        .pipe(gulpif(!prod, sourcemaps.init({ loadMaps: true })))
        .pipe(gulpif(prod, uglify()))
        .pipe(gulpif(!prod, sourcemaps.write('.')))
        // sourcemaps end
        .pipe(gulp.dest(_dest));
};

var minifyJS = function() {
    var streams = [];
    paths.minify.forEach(function(app) {
        var _file = app.in.substring(app.in.lastIndexOf('/')).split(/[\\/]/).pop(),
            _dest = app.out.substring(0, app.out.lastIndexOf('/')),
            _ext  = _file.split('.').pop();

        gutil.log(gutil.colors.blue('*'), 'Minifying', app.in);

        streams.push(gulp.src(app.in)
            .on('end', function() {
                gutil.log(gutil.colors.green('√'), 'Saved ' + app.in);
            })
            .on('error', gutil.log)
            .pipe(gulpif(_ext == 'json', jsonminify(), uglify()))
            .pipe(gulp.dest(_dest)));
    });

    return merge(streams);
};

function minify(done) {
    if (!prod) { 
        done();
        return; 
    }

    return minifyJS();
}

function watchify(done) {
    if (watchType != 'js' && watchType != 'all') { 
        // Signal task completion if not processing JS
        if (done) done();
        return; 
    }
    
    watch = true;

    // watch js
    const streams = [];
    paths.js.forEach(function(app) {
        // var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        streams.push(compileJS(app, true));
    });
    
    // Signal task completion
    if (done) done();
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
        streams.push(compileCSS(app));
    });

    // If there are no streams, call done and return
    if (streams.length === 0) {
        if (done) done();
        return;
    }

    // Merge all streams and signal completion when finished
    return merge(streams)
        .on('end', function() {
            if (done) done();
        })
        .on('error', function(err) {
            if (done) done(err);
        });
}

exports.watchify = watchify;
exports.watch = gulp.series(watchify, function(done) {
    if (watchType != 'css' && watchType != 'all') { 
        // Signal task completion if not processing CSS
        done();
        return; 
    }

    // watch css
    const watchers = [];
    
    paths.css.forEach(function(app) {
        var _path = app.in.substring(0, app.in.lastIndexOf('/'));
        
        // Get all potential scss directories to watch
        var watchPaths = [
            _path + '/**/*.scss',  // Watch the current app's directory
        ];
        
        // If the app has additional load paths, watch those too
        if (app.load) {
            watchPaths.push(app.load + '/**/*.scss');
        }
        
        // Create a watch function for this app
        function watchAndCompile(cb) {
            gutil.log(gutil.colors.blue('*'), 'Compiling CSS for', app.out);
            return compileCSS(app, cb);
        }
        
        // Initial message
        gutil.log(gutil.colors.blue('*'), 'Watching', watchPaths.join(', '));
        
        // Create watch tasks for each path
        const watcher = gulp.watch(watchPaths);
        
        // Add event handlers
        watcher.on('change', function(path) {
            gutil.log(gutil.colors.red('>'), 'File', path, 'was changed');
            watchAndCompile();
        });
        
        watcher.on('add', function(path) {
            gutil.log(gutil.colors.green('+'), 'File', path, 'was added');
            watchAndCompile();
        });
        
        watcher.on('unlink', function(path) {
            gutil.log(gutil.colors.yellow('-'), 'File', path, 'was removed');
            watchAndCompile();
        });
        
        watchers.push(watcher);
    });
    
    // Compile all CSS files initially
    css(function() {
        gutil.log(gutil.colors.green('√'), 'Initial CSS compilation complete');
    });
    
    // This is an ongoing task that doesn't complete
    // Signal async completion to continue the gulp task
    done();
});

exports.css = css;
exports.js = js;
exports.minify = minify;
exports.all = gulp.series(css, js, minify);
exports.defaults = gulp.series(css, js, minify)
