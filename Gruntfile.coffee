module.exports = ->

	@initConfig
		sandbox: '/users/jason/sites/statamic-sandbox'
		copy:
			sandbox:
				files: [
					expand: true
					cwd: '_add-ons/calendar/'
					src: '**'
					dest: '<%= sandbox %>/_add-ons/calendar/'
				]
		watch:
			options:
				livereload: true
			php:
				files: ['_add-ons/calendar/*.php']
				tasks: ['copy']
			sandbox:
			  files: ['<%= sandbox %>/{_content,_themes}/**/*.*']

	@loadNpmTasks 'grunt-contrib-watch'
	@loadNpmTasks 'grunt-contrib-copy'
	@registerTask 'default', ['watch']