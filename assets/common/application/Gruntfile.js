module.exports = function(grunt) {
    grunt.initConfig({
        wrapup: {
            build: {
                requires: {
                    'G5': './main.js'
                },
                options: {
                    output: '../js/main.js',
                    sourcemap: '../js/main.js.map',
                    sourcemapRoot: '../',
                    sourcemapURL: 'main.js.map',
                    compress: false
                }
            }
        },
        watch: {
            js: {
                files: ['**/*.js', 'Gruntfile.js'],
                tasks: ['wrapup']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-wrapup');
    grunt.registerTask('default', ['watch']);
    grunt.registerTask('all', ['wrapup']);
};
