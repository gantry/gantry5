module.exports = function(grunt) {
    grunt.initConfig({
        sass: {
            dist: {
                options: {
                    //trace: true,
                    //style: 'expanded'
                    //lineNumbers: true
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
        wrapup: {
            build: {
                requires: {
                    'G5': './application/main.js'
                },
                options: {
                    output: 'js/main.js',
                    sourcemap: 'js/main.js.map',
                    sourcemapRoot: '../',
                    sourcemapURL: 'main.js.map',
                    compress: false
                }
            }
        },
        watch: {
            css: {
                files: 'scss/**/*.scss',
                tasks: ['sass']
            },

            js: {
                files: ['application/**/*.js', 'Gruntfile.js'],
                tasks: ['wrapup']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-wrapup');
    grunt.registerTask('default', ['watch']);
};
