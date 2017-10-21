var gulp = require('gulp');
var minify = require('gulp-minify-css');
var sass = require('gulp-sass');

/*********************
 *
 * For bootstrap 4
 *
 *******************
 */

gulp.task('bootstrap', function() {
	  gulp.src('./bower_components/bootstrap/dist/css/*.css')
		  .pipe(minify())
		  .pipe(gulp.dest('./css'));
      gulp.src('./bower_components/bootstrap/dist/js/*.js')
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
 *******************
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
	gulp.src('./bower_components/font-awesome/css/*.css')
		//.pipe(minify)
		.pipe(gulp.dest('./css'));
	gulp.src('./bower_components/font-awesome/fonts/*.*')
		.pipe(gulp.dest('./fonts'))
});


/*********************************
 *
 * For compiling the theme.
 *
 * Compiles the complete theme,
 * including bootstrap.
 *
 *******************
 */


gulp.task('compile-theme', function(){
	gulp.src('./scss/compiled.scss')
		.pipe(sass().on('error', sass.logError))
		//.pipe(minify()) //TODO: not for development, enable for production.
		.pipe(gulp.dest('./css'));
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
    gulp.src('./bower_components/jquery/dist/jquery.js')
        .pipe(gulp.dest('./js'));
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
    gulp.src('./bower_components/popper.js/dist/umd/popper.js')
        .pipe(gulp.dest('./js'));
});