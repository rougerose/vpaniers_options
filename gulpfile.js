// *************************************
//
// Gulpfile
// (cf. https://github.com/drewbarontini/noise/blob/master/gulpfile.js)
//
// *************************************

"use strict";

// =====================================
// plugins
// =====================================

var gulp            = require("gulp");
var rename          = require("gulp-rename");
var uglify          = require("gulp-uglify");
var concat          = require("gulp-concat");
var wrap            = require("gulp-wrap");
var pump = require("pump");
var run = require('run-sequence');

// =====================================
// options
// =====================================

var options = {
	
	// ----- task default ----- //
	default: {
		tasks: ['build']
	},
	
	// ----- build ----- //
	
	build: {
		tasks: ['compile:js', 'minify:unique', 'minify:js'],
		destination: 'js'
	},
	
	js: {
		srcfiles: '_src/js/*.js',
		file: 'js/vpaniers_options.js',
		fileName: 'vpaniers_options.js',
		destination: 'js'
	},
	
	unique: {
		srcfiles: '_src/js/unique/*.js',
		destination: 'js'
	},
	
	watch: {
		files: function() {
			return [
				options.js.srcfiles, 
				options.unique.srcfiles
			]
		},
		run: function() {
			return [ 
				['compile:js', 'minify:js'],
				['minify:unique']
			]
		}
	}
};


// =====================================
// task: default
// =====================================
gulp.task("default", options.default.tasks);


//
// task: build
// -------------------------------------
gulp.task( 'build', function() {
	options.build.tasks.forEach(function(task) {
		gulp.start(task);
	});
});

// =====================================
// task: compile:js
// =====================================
gulp.task( 'compile:js', function () {
	gulp.src([options.js.srcfiles] )
		.pipe(concat(options.js.fileName))
		// .pipe(wrap('$(function(){\n\'use strict\';\n<%= contents %>\n});'))
		.pipe(wrap(';(function ($, window, document, undefined) {\n<%= contents %>\n}(jQuery, window, document));'))
		.pipe( gulp.dest(options.js.destination));
});

// =====================================
// task: minify:unique
// =====================================
gulp.task('minify:unique', function(cb) {
	pump([
		gulp.src(options.unique.srcfiles),
		uglify(),
		rename({suffix: '.min'}),
		gulp.dest(options.unique.destination)
	], cb);
});


// =====================================
// task: minify:js
// =====================================
gulp.task("minify:js", function(cb) {
	pump([
		gulp.src(options.js.file),
		uglify(),
		rename({suffix: '.min'}),
		gulp.dest(options.js.destination)
	], cb);
});


// =====================================
// task: watch
// =====================================
gulp.task('watch', function() {
	var watchFiles = options.watch.files();
	watchFiles.forEach( function( files, index ) {
		gulp.watch( files, options.watch.run()[ index ]  );
	});
});
