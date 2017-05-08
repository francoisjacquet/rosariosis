/**
 * Grunt
 *
 * @see http://gruntjs.com/api/grunt to learn more about how grunt works
 * @since 3.3
 */

module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		watch: {
			options: {
				livereload: true,
			},
			css: {
				files: ['assets/themes/WPadmin/css/*.css'],
				tasks: ['cssmin'],/*'autoprefixer', */
				options: {
					livereload: true
				},
			},
			js: {
				files: ['assets/js/**/*.js'],
				tasks: ['uglify'],
				options: {
					livereload: true
				},
			},
			livereload: {
				// Reload page when css, js, images or php files change.
				files: [
					'assets/themes/WPadmin/css/*.css',
					'assets/js/**/*.js',
					'assets/**/*.{png,jpg,jpeg,gif,webp,svg}',
					'**/*.php'
				]
			},
		},

		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
			},
			my_target: {
				files: {
					'assets/js/warehouse.min.js': ['assets/js/warehouse.js'],
					'assets/js/plugins.min.js': [
						'assets/js/jquery.form.js',
						'assets/js/tipmessage/main16.js',
						'assets/js/jscalendar/calendar.js',
						'assets/js/jscalendar/calendar-setup.js',
						'assets/js/jscalendar/calendar-setup.js',
						'assets/js/colorbox/jquery.colorbox-min.js',
						'assets/js/showdown/showdown.min.js',
						'assets/js/jquery-fixedmenu/jquery-fixedmenu.min.js'
					]
				}
			}
		},

		autoprefixer: {
			options: {
				browsers: ['last 2 versions', 'ie 8', 'ie 9']
			},
			multiple_files: {
                expand: true,
                flatten: true,
                src: 'assets/themes/WPadmin/css/*.css',
                dest: 'assets/themes/WPadmin/css/build/'
            }
		},

		cssmin: {
			options: {
				level: {
					2: {
						mergeIntoShorthands: false,
						roundingPrecision: false
					}
				}
			},
			target: {
				files: {
					'assets/themes/WPadmin/stylesheet.css': ['assets/themes/WPadmin/css/*.css'],
					'assets/themes/WPadmin/stylesheet_wkhtmltopdf.css': [
						'assets/themes/WPadmin/css/colors.css',
						'assets/themes/WPadmin/css/font.css',
						'assets/themes/WPadmin/css/stylesheet.css'
					]
				}
			}
		}

	});

	/**
	 * Load all plugins required
	 */
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	// Default task(s).
	grunt.registerTask( 'default', ['watch'] );

};
