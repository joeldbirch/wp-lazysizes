(function () {
	'use strict';

	module.exports = function (grunt) {
		// Project configuration.
		grunt.initConfig({
			copy: {
				update: {
					files: [
						{
							expand: true,
							cwd: 'node_modules/',
							src: ['lazysizes/**/*.js'],
							dest: 'js/'
						}
					]
				}
			},

			concat: {
				dist: {
					src: ['js/ls.setup.js', 'js/lazysizes/lazysizes.min.js'],
					dest: 'build/wp-lazysizes.min.js',
				},
			}
		});

		grunt.loadNpmTasks('grunt-contrib-copy');
		grunt.loadNpmTasks('grunt-contrib-concat');


		// Default task.
		grunt.registerTask('default', [ 'copy' ]);

		// Concatenate lazysizes JS with mobile detection JS files.
		grunt.registerTask('build', [ 'concat' ]);
	};
})();
