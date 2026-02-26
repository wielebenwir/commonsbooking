module.exports = function (grunt) {
	const pkg = grunt.file.readJSON('package.json')
	const nodePackagesDestDir = 'assets/packaged/'
	grunt.util.linefeed = '\n';
	grunt.initConfig(
		{
		pkg: pkg,
		'dart-sass': {
			admin: {
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/admin/sass',
					dest: 'assets/admin/css',
				}],
			},
			themes: {
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/public/sass/themes',
					dest: 'assets/public/css/themes',
				}],
			},
			public: {
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/public/sass',
					dest: 'assets/public/css',
				}],
			},
			global: {
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/global/sass',
					dest: 'assets/global/css'
				}]
			},
			adminDev: {
				options: {
					outputStyle: 'expanded',
				},
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/admin/sass',
					dest: 'assets/admin/css',
				}],
			},
			publicDev: {
				options: {
					outputStyle: 'expanded',
				},
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/public/sass',
					dest: 'assets/public/css',
				}],
			},
			globalDev: {
				options: {
					outputStyle: 'expanded',
					sourceMap: true,
				},
				files: [{
					expand: true,
					src: ['*.scss'],
					ext: '.css',
					cwd: 'assets/global/sass',
					dest: 'assets/global/css'
				}]
			}
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
						'assets/admin/js/src/*.js',
						'node_modules/feiertagejs/build/feiertage.umd.cjs'
					]
				}
			},
			dist: {
				options: {
					mangle: true,
					compress: true
				},
				files: {
					'assets/public/js/public.js': [
						/* add path to js dependencies (ie in node_modules) here */
						'assets/public/js/src/**/*.js',
					],
					'assets/admin/js/admin.js': [
						/* add path to js dependencies (ie in node_modules) here */
						'assets/admin/js/src/*.js',
						'node_modules/feiertagejs/build/feiertage.umd.cjs'
					],
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
					{
						dest: nodePackagesDestDir + 'commons-search/',
						expand: true,
						cwd: 'node_modules/@commonsbooking/frontend/dist/lib/commons-search/',
						src: ['commons-search.umd.js', 'style.css'],
					},
					{
						dest: nodePackagesDestDir + 'vue/',
						expand: true,
						cwd: 'node_modules/vue/dist/',
						src: 'vue.runtime.global.prod.js',
					},
                    {
                        dest: 'includes/commons-api-json-schema/',
                        expand: true,
                        cwd: 'node_modules/commons-api/',
                        src: '**schema.json',
                    },
					{
						dest: nodePackagesDestDir + 'select2/',
						expand: true,
						cwd: 'node_modules/select2/dist/',
						src: ['**/select2.min.js', '**/select2.min.css']
					},
					{
						dest: nodePackagesDestDir + 'moment/',
						expand: true,
						cwd: 'node_modules/moment/min/',
						src: ['moment.min.js']
					},
				],
			},
		},
		clean: {
			dist: [
				'assets/packaged/*',
				'assets/admin/css/*',
				'assets/public/css/*',
				'assets/public/js/public.*',
				'assets/global/js/vendor.*',
				'assets/admin/js/admin.*'
			]

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
			'dart-sass': {
				files: [
					'assets/admin/sass/**/*.scss',
					'assets/global/sass/**/*.scss',
					'assets/public/sass/**/*.scss'
				],
				tasks: [
					'dart-sass:adminDev', 'dart-sass:publicDev', 'dart-sass:globalDev'
				],
                options: {
                    livereload: true
                }
			},
			js: {
				files: [
					'assets/public/js/src/**/*.js',
					'assets/global/js/src/**/*.js',
					'assets/admin/js/src/**/*.js'
				],
				tasks: [
					'uglify:dev', 'babel'
				],
                options: {
                    livereload: true
                }
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-dart-sass');
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
		'dart-sass:adminDev',
		'dart-sass:publicDev',
		'dart-sass:globalDev',
		'dart-sass:themes',
		'uglify:dev',
		'uglify:dist',
		'babel',
		'copy',
		'node_versions',
	]);
	grunt.registerTask('dev', [
		'dart-sass:adminDev',
		'dart-sass:publicDev',
		'dart-sass:globalDev',
		'dart-sass:themes',
		'uglify:dev',
		'babel',
		'copy',
		'node_versions',
		'watch',
	]);
	grunt.registerTask('dist', [
		'clean',
		'dart-sass:admin',
		'dart-sass:public',
		'dart-sass:global',
		'dart-sass:themes',
		'babel',
		'uglify:dist',
		'copy',
		'node_versions',
	]);
};
