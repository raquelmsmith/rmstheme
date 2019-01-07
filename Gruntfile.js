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

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

	grunt.registerTask( 'default', [ 'styles' ] );
	grunt.registerTask( 'styles', [ 'sass', 'postcss' ] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	grunt.util.linefeed = '\n';

}
