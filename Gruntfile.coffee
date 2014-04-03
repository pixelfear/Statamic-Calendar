module.exports = ->

	@initConfig
		copy:
			sandbox:
				files: [
					expand: true
					cwd: '_add-ons/calendar/'
					src: '**'
					dest: '/users/jason/sites/statamic-sandbox/_add-ons/calendar/'
				]
		watch:
			options:
				livereload: true
			php:
				files: ['_add-ons/calendar/*.php']
				tasks: ['copy']

	@loadNpmTasks 'grunt-contrib-watch'
	@loadNpmTasks 'grunt-contrib-copy'
	@registerTask 'default', ['watch']