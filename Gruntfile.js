module.exports = function (grunt) {
    const pkg = grunt.file.readJSON('package.json')
    const nodePackagesDestDir = 'assets/packaged/'
	grunt.util.linefeed = '\n';
	grunt.initConfig(
		{
		pkg: pkg,
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
					noLineComments: false,
					sassDir: 'assets/public/sass',
					cssDir: 'assets/public/css',
					outputStyle: 'expanded',
					relativeAssets: true,
					sourcemap: true
				}
			},
            clean: {
                options: {
                    clean: true
                },
            },
		},
		// concat and minify our JS
		uglify: {
			dev: {
				options: {
					beautify: true,
					mangle: false,
					compress: false
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
					mangle: true,
					compress: true
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
		copy: {
			main: {
				files: [
					{
						dest: nodePackagesDestDir + 'leaflet/',
						expand: true,
						cwd: 'node_modules/leaflet/dist/',
						src: '**',
					},
					{
						dest: nodePackagesDestDir + 'leaflet-markercluster/',
						expand: true,
						cwd: 'node_modules/leaflet.markercluster/dist/',
						src: '**',
					},
					{
						dest: nodePackagesDestDir + 'leaflet-easybutton/',
						expand: true,
						cwd: 'node_modules/leaflet-easybutton/src/',
						src: '**',
					},
					{
						dest: nodePackagesDestDir + 'leaflet-spin/',
						expand: true,
						cwd: 'node_modules/leaflet-spin/',
						src: '**'
					},
					{
						dest: nodePackagesDestDir + 'spin-js/',
						expand: true,
						cwd: 'node_modules/spin.js/',
						src: 'spin.min.js'
					},
				],
			},
		},
		babel: {
			options: {
				sourceMap: true,
				presets: ['@babel/preset-env']
			},
			dist: {
				files: {
					'assets/global/js/vendor.js': 'node_modules/shufflejs/dist/shuffle.js'
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
					'uglify:dev', 'babel'
				]
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-babel');
    grunt.registerTask('node_versions', 'Generates a version map for dependencies', function() {
        const deps = pkg.dependencies;
        const versionMap = Object.fromEntries(
            Object
                .entries(deps)
                .map(([name, version]) => [name, version.replace(/^\D/, '')])
        )
        grunt.file.write(nodePackagesDestDir + 'dist.json', JSON.stringify(versionMap))
    })

	// Register tasks
	grunt.registerTask('default', [
		'compass:adminDev',
		'compass:publicDev',
		'compass:themes',
		'uglify:dev',
		'uglify:dist',
		'babel',
		'copy',
		'node_versions',
	]);
	grunt.registerTask('dev', [
		'compass:adminDev',
		'compass:publicDev',
		'compass:themes',
		'uglify:dev',
		'babel',
		'copy',
		'node_versions',
		'watch',
	]);
	grunt.registerTask('dist', [
        'compass:clean',
		'compass:admin',
		'compass:public',
		'compass:themes',
		'uglify:dist',
		'babel',
		'copy',
		'node_versions',
	]);
};