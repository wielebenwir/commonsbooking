module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		compass: {
			admin: {
				options: {
					sassDir: 'assets/admin/sass',
					cssDir: 'assets/admin/css',
					environment: 'production',
					relativeAssets: true
				}
			},
			themes: {
				options: {
					sassDir: 'assets/public/sass/themes',
					cssDir: 'assets/public/css/themes',
					environment: 'production',
					relativeAssets: true
				}
			},
			public: {
				options: {
					sassDir: 'assets/public/sass',
					cssDir: 'assets/public/css',
					environment: 'production',
					relativeAssets: true
				}
			},
			adminDev: {
				options: {
					environment: 'development',
					debugInfo: true,
					noLineComments: false,
					sassDir: 'assets/admin/sass',
					cssDir: 'assets/admin/css',
					outputStyle: 'expanded',
					relativeAssets: true,
					sourcemap: true
				}
			},
			publicDev: {
				options: {
					environment: 'development',
					debugInfo: true,
					noLineComments: false,
					sassDir: 'assets/public/sass',
					cssDir: 'assets/public/css',
					outputStyle: 'expanded',
					relativeAssets: true,
					sourcemap: true
				}
			}
		},
		// concat and minify our JS
		uglify: {
			dev: {
				options: {
					beautify: true,
					mangle: false
				},
				files: {
					'assets/public/js/public.js': [
						/* add path to js dependencies (ie in node_modules) here */
						'node_modules/tippy.js/dist/tippy.all.js',
						'assets/public/js/src/**/*.js',
					],
					'assets/admin/js/admin.js': [
						/* add path to js dependencies (ie in node_modules) here */
						'assets/admin/js/src/*.js'
					]
				}
			},
			dist: {
				options: {
					beautify: false,
					mangle: true
				},
				files: {
					'assets/public/js/public.min.js': [
						'assets/public/js/public.js'
					],
					'assets/admin/js/admin.min.js': [
						'assets/admin/js/admin.js'
					]
				}
			}
		},
		watch: {
			compass: {
				files: [
					'assets/admin/sass/**/*.scss',
					'assets/global/sass/**/*.scss',
					'assets/public/sass/**/*.scss'
				],
				tasks: [
					'compass:adminDev', 'compass:publicDev'
				]
			},
			js: {
				files: [
					'assets/public/js/src/**/*.js',
					'assets/global/js/src/**/*.js',
					'assets/admin/js/src/**/*.js'
				],
				tasks: [
					'uglify:dev'
				]
			}
		}
	});
	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-uglify-es');
	// Register tasks
	grunt.registerTask('default', [
		'compass:adminDev', 'compass:publicDev','uglify:dev','compass:themes'
	]);
	grunt.registerTask('dev', [
		'compass:adminDev',
		'compass:publicDev',
		'uglify:dev',
		'compass:themes',
		'watch'
	]);
	grunt.registerTask('dist', [
		'compass:admin',
		'compass:public',
		'compass:themes',
		'uglify:dist'
	]);
};
