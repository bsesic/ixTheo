gulp.task("bower:copyfiles", function(cb){
    return gulp.src(mainBowerFiles())
        .pipe(gulp.dest('./src/lib'))
    cb();
});