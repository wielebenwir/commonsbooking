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
					mangle: false,
					compress: {
						unused: false
					}
				},
				files: {
					'assets/public/js/public.js': [
						/* add path to js dependencies (ie in node_modules) here */
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
					mangle: true,
					compress: {
						unused: false
					}
				},
				files: {
					'assets/public/js/public.min.js': [
						'assets/public/js/public.js'
					],
					'assets/admin/js/admin.min.js': [
						'assets/admin/js/admin.js'
					],
					'assets/global/js/vendor.min.js': [
						'assets/global/js/vendor.js'
					]
				}
			}
		},
		concat: {
			// options : {
			// 	sourceMap :true
			// },
			distJs: {
				src: [
					'node_modules/moment/moment.js',
					'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
					'node_modules/bootstrap-table/dist/bootstrap-table.js',
					'node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.js',
					'node_modules/bootstrap-table/dist/extensions/cookie/bootstrap-table-cookie.js',
				],
				dest: 'assets/global/js/vendor.js',
			},
			distCss: {
				src: [
					'node_modules/bootstrap/dist/css/bootstrap.min.css',
					'node_modules/bootstrap-table/dist/bootstrap-table.min.css',
					'node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.css'
				],
				dest: 'assets/global/css/vendor.css',
			},
		},
		watch: {
			compass: {
				files: [
					'assets/admin/sass/**/*.scss',
					'assets/global/sass/**/*.scss',
					'assets/public/sass/**/*.scss'
				],
				tasks: [
					'compass:adminDev', 'compass:publicDev', 'concat:distCss'
				]
			},
			js: {
				files: [
					'assets/public/js/src/**/*.js',
					'assets/global/js/src/**/*.js',
					'assets/admin/js/src/**/*.js'
				],
				tasks: [
					'uglify:dev', 'concat:distJs'
				]
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-uglify-es');
	grunt.loadNpmTasks('grunt-contrib-concat');


	// Register tasks
	grunt.registerTask('default', [
		'compass:adminDev',
		'compass:publicDev',
		'compass:themes',
		'uglify:dev',
		'uglify:dist',
		'concat:distJs',
		'concat:distCss'
	]);
	grunt.registerTask('dev', [
		'compass:adminDev',
		'compass:publicDev',
		'compass:themes',
		'uglify:dev',
		'concat:distJs',
		'concat:distCss',
		'watch'
	]);
	grunt.registerTask('dist', [
		'compass:admin',
		'compass:public',
		'compass:themes',
		'uglify:dist',
		'concat:distJs',
		'concat:distCss'
	]);
};
