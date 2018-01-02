/*****************************
 *
 * gulpfile.js
 *
 * For running gulp tasks.
 *
 * by Benjamin Schnabel
 *
 *************************/

'use strict';

var fs = require('fs');
var gulp = require('gulp');
var minify = require('gulp-minify-css');
var sass = require('gulp-sass');
var log = require('fancy-log');
var colors = require('ansi-colors');
var beeper = require('beeper');
var logSymbols = require('log-symbols');
var GulpSSH = require('gulp-ssh');

//configuration
var config = {
    bootstrapDir: '../../node_modules/bootstrap',
    cssDir: './css',
    jsDir: '.js',

	//for uploads to the local server on virtualbox
    host: '127.0.0.1',              //localhost
    port: 2222,                     //port for the virtual machine
    username: 'root',               // username
    password: 'rootpw123',          //password
    ftpDest: '/usr/local/vufind/'   //folder for vufind
};


/*********************
 *
 * For bootstrap 4
 *
 *******************
 */

gulp.task('bootstrap', function() {
	  gulp.src('../../node_modules/bootstrap/dist/css/*.css')
		  .pipe(minify())
		  .pipe(gulp.dest('./css'));
      gulp.src('../../node_modules/bootstrap/dist/js/*.js')
		  .pipe(minify())
		  .pipe(gulp.dest('./js'));
	 });

/**********************************************
 *
 * For Bootstrap4 sass
 *
 * Bootstrap4 does not include less anymore.
 * Get Sass files.
 *
 ***********************************************
 */

gulp.task('bootstrap-sass', function() {
    gulp.src('./bower_components/bootstrap/scss/**/*')
        .pipe(gulp.dest('./scss/bootstrap'));
});

/*********************
 *
 * For Font-Awesome
 *
 *******************
 */

gulp.task('font-awesome', function() {
	gulp.src('../../node_modules/font-awesome/css/*.css')
		//.pipe(minify)
		.pipe(gulp.dest('./css'));
	gulp.src('../../node_modules/font-awesome/fonts/*.*')
		.pipe(gulp.dest('./fonts'))
});


/*********************************
 *
 * For compiling the theme.
 *
 * Compiles the complete theme,
 * including bootstrap.
 *
 *********************************
 */


gulp.task('compile-theme', function(){
	log(logSymbols.success, colors.green("🥤 Compiling theme..."));
	return gulp.src('./scss/compiled.scss')
		.pipe(sass({
            includePaths: [config.bootstrapDir]
        }).on('error', sass.logError))
		//.pipe(minify()) //TODO: not for development, enable for production.
		.pipe(gulp.dest(config.cssDir));
});


/*********************************
 *
 * For jQuery.
 *
 * Copies jQuery.
 *
 *******************
 */


gulp.task('jquery', function(){
    gulp.src('../../node_modules/jquery/dist/jquery.js')
        .pipe(gulp.dest('./js'));
});

/*********************************
 *
 * For watching the scss files
 *
 *******************
 */

gulp.task('watch',function() {
    log(colors.green("👓 Gulp is watching"));

    gulp.watch('scss/**/*.scss',['compile-theme'])
        .on('error', function() {
            log(logSymbols.error + colors.red("⚡️ Watch failed."))
        })
        .on('end', function() {
            log(logSymbols.info + colors.green("🍭 Watch successfull."))
        });

    gulp.watch('css/**/*.css',['compile-upload'])
        .on('error', function() {
            log(logSymbols.error + colors.red("⚡️ Watch failed."))
        })
        .on('end', function() {
            log(logSymbols.info + colors.green("🍭 Watch successfull."))
        });
});


/*********************************
 *
 * For popper.js.
 *
 * Popper is required
 * by bootstrap 4.
 *
 *******************
 */


gulp.task('popper', function(){
    gulp.src('../../node_modules/popper.js/dist/umd/popper.js')
        .pipe(gulp.dest('./js'));
});

/************************************************
 *
 * For uploading the files to the local server
 *
 *******************************
 */

var gulpSSH = new GulpSSH({
    ignoreErrors: false,
    sshConfig: config
});

gulp.task('compile-upload', function () {
    log(logSymbols.success, colors.yellow('📦 Uploading compile.css to server.'));
    gulp.src('./css/compiled.css')
        .pipe(gulpSSH.dest(config.ftpDest + 'themes/ixTheoThemeNew/css'))
        .on('error', function() {
            log(logSymbols.error + colors.red("⚡️ Uploading failed."))
        })
        .on('end', function() {
            log(logSymbols.info + colors.green("🍭 Successfully uploaded."))
        });
});

gulp.task('remote-npm-install', function () {
    return gulpSSH
        .shell(['cd ' + config.ftpDest, 'npm install'], {filePath: 'shell.log'})
        .pipe(gulp.dest('logs'))
});

