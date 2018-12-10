'use strict';
module.exports = function (grunt) {

	// load all grunt tasks
	require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),
		
		// Making the ZIP with Gulp, even when renaming the ZIP, for some reason will always extract to a directory with the version number appended, which WordPress does not appreciate
		compress: {
			main: {
				options: {
					archive: './packaged/<%- textDomain -%>.zip'
				},
				files: [
					{expand: true, dot: true, src: ['./<%- textDomain -%>/**/*.*'], dest: './'}
				]
			}
		}

	});
	
};