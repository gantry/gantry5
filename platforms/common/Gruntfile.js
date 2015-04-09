module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            dist: {
                options: {
                    trace: true,
                    style: 'expanded',
                    lineNumbers: false,
                    loadPath: '../../engines/common/nucleus/scss/'
                },
                files: [{
                    expand: true,
                    cwd: 'scss',
                    src: ['*.scss'],
                    dest: 'css-compiled',
                    ext: '.css'
                }]
            }
        },

        browserify: {
            options: {
                debug: true
            },
            dev: {
                options: {
                    watch: true,
                    browserifyOptions: {
                        debug: true
                    },
                    postBundleCB: function(err, src, cb) {
                        var through = require('through');
                        var stream = through().pause().queue(src).end();
                        var buffer = '';
                        stream.pipe(require('mold-source-map').transformSourcesRelativeTo(__dirname + '/application')).pipe(through(function(chunk) {
                            buffer += chunk.toString();
                        }, function() {
                            cb(err, buffer);
                        }));
                        stream.resume();

                        grunt.task.run('exorcise');
                    }
                },
                src: ['application/main.js'], // src: '<%= browserify.dev.src %>',
                dest: 'js/main.js'
            },
            prod: {
                options: {
                    postBundleCB: function(err, src, cb){
                        cb(err, src);
                        grunt.task.run('uglify');
                    }
                },
                src: ['application/main.js'], // src: '<%= browserify.dev.src %>',
                dest: 'js/main.js'
            }
        },

        exorcise: {
            bundle: {
                options: {
                    root: '/G5'
                },
                files: {
                    'js/main.js.map': ['js/main.js']
                }
            }
        },

        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> - <%= grunt.template.today("yyyy-mm-dd") %> */'
            },
            target: {
                files: {
                    'js/main.js': ['js/main.js']
                }
            }

        },

        watch: {
            css: {
                files: 'scss/**/*.scss',
                tasks: ['sass']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-exorcise');
    grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.registerTask('default', ['browserify', 'watch']);
    grunt.registerTask('js', ['browserify']);
    grunt.registerTask('css', ['sass']);
    grunt.registerTask('all', ['sass', 'browserify']);
};
