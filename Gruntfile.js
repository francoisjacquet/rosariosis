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
				files: ['assets/themes/**/css/*.css'],
				tasks: ['cssmin'],
				/*'autoprefixer', */
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
					'assets/themes/**/css/*.css',
					'assets/js/**/*.js',
					'assets/**/*.{png,jpg,jpeg,gif,webp,svg}',
					'**/*.php'
				]
			},
		},

		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
				// https://www.html5rocks.com/en/tutorials/developertools/sourcemaps/
				sourceMap: true
			},
			my_target: {
				files: {
					'assets/js/plugins.min.js': [
						'assets/js/jquery.form.js',
						'assets/js/tipmessage/main17.js',
						'assets/js/jscalendar/calendar.js',
						'assets/js/jscalendar/calendar-setup.js',
						'assets/js/colorbox/jquery.colorbox-min.js',
						'assets/js/marked/marked.min.js',
						'assets/js/DOMPurify/purify.min.js',
						'assets/js/jquery-fixedmenu/jquery-fixedmenu.js',
						'assets/js/jquery-captcha/jquery-captcha.js',
						'assets/js/jquery-passwordstrength/jquery-passwordstrength.js',
						'assets/js/warehouse.js',
					]
				}
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
					'assets/themes/WPadmin/stylesheet.css': [
						'assets/themes/WPadmin/css/calendar-blue.css',
						'assets/themes/WPadmin/css/colorbox.css',
						'assets/themes/WPadmin/css/colors.css',
						'assets/themes/WPadmin/css/font.css',
						'assets/themes/WPadmin/css/icons.css',
						'assets/themes/WPadmin/css/stylesheet.css',
						'assets/themes/WPadmin/css/zresponsive.css',
						'assets/themes/WPadmin/css/rtl.css'
					],
					'assets/themes/WPadmin/stylesheet_wkhtmltopdf.css': [
						'assets/themes/WPadmin/css/colors.css',
						'assets/themes/WPadmin/css/font.css',
						'assets/themes/WPadmin/css/icons.css',
						'assets/themes/WPadmin/css/stylesheet.css',
						'assets/themes/WPadmin/css/rtl.css',
						'assets/themes/WPadmin/css/wkhtmltopdf.css'
					],
					'assets/themes/FlatSIS/stylesheet.css': [
						'assets/themes/FlatSIS/css/calendar-blue.css',
						'assets/themes/FlatSIS/css/colorbox.css',
						'assets/themes/FlatSIS/css/colors.css',
						'assets/themes/FlatSIS/css/font.css',
						'assets/themes/FlatSIS/css/icons.css',
						'assets/themes/FlatSIS/css/stylesheet.css',
						'assets/themes/FlatSIS/css/zresponsive.css',
						'assets/themes/FlatSIS/css/rtl.css'
					],
					'assets/themes/FlatSIS/stylesheet_wkhtmltopdf.css': [
						'assets/themes/FlatSIS/css/colors.css',
						'assets/themes/FlatSIS/css/font.css',
						'assets/themes/FlatSIS/css/icons.css',
						'assets/themes/FlatSIS/css/stylesheet.css',
						'assets/themes/FlatSIS/css/rtl.css',
						'assets/themes/FlatSIS/css/wkhtmltopdf.css'
					]
				}
			}
		},
	});

	/**
	 * Load all plugins required
	 */
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	// Default task(s).
	grunt.registerTask('default', ['watch']);
};
