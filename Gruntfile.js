module.exports = function( grunt ) {

	'use strict';

	grunt.initConfig({

		pkg:    grunt.file.readJSON( 'package.json' ),

		sass: {
			options: {
				implementation: require('node-sass'),
				sourceComments: false
			},
			compile: {
				files: {
					'assets/css/style.css' : 'assets/css/scss/style.scss',
				}
			}
		},

		postcss: {
			options: {
				processors: [
					require('autoprefixer')(),
					require('postcss-discard-comments')({removeAll:true}),
				]
			},
			dist: {
				src: 'assets/css/*.css'
			}
		},

		watch: {
			css: {
				files: '**/*.scss',
				tasks: [ 'sass', 'postcss' ]
			}
		},

	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask( 'default', [ 'styles' ] );
	grunt.registerTask( 'styles', [ 'sass', 'postcss' ] );

	grunt.util.linefeed = '\n';

}
