module.exports = function(grunt) {
    grunt.initConfig({
        sass: {
            dist: {
                options: {
                    sourcemap: true,
                    trace: true,
                    style: 'expanded',
                    lineNumbers: true
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
        watch: {
            css: {
                files: 'scss/**/*.scss',
                tasks: ['sass']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['watch']);
};
