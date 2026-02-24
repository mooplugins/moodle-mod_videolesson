module.exports = function (grunt) {
    grunt.initConfig({
      watch: {
        scripts: {
          files: ['**/*.js', '**/*.scss'],
          tasks: ['default'],
          options: {
            spawn: false,
          },
        },
      },
    });
  
    grunt.loadNpmTasks('grunt-contrib-watch');
  
    grunt.registerTask('default', []);
  };
  