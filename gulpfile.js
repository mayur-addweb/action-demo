var gulp            = require('gulp'),
    autoprefixer    = require('gulp-autoprefixer'),
    browserSync     = require('browser-sync'),
    changed         = require('gulp-changed'),
    cssmin          = require('gulp-cssmin'),
    del             = require('del'),
    eol             = require('gulp-eol-enforce'),
    imagemin        = require('gulp-imagemin'),
    jshint          = require('gulp-jshint'),
    phpcs           = require('gulp-phpcs'),
    sass            = require('gulp-sass'),
    sassLint        = require('gulp-sass-lint'),
    sourcemaps      = require('gulp-sourcemaps'),
    phplint         = require('phplint').lint,
    phpunit         = require('gulp-phpunit'),
    plumber         = require('gulp-plumber'),
    rename          = require('gulp-rename'),
    uglify          = require('gulp-uglify'),
    _               = require('lodash')
    ;


var themeName = 'vscpa_bootstrap';
var devSiteUrl = 'vscpa.test';

var themeBasePath = 'web/themes/custom/' + themeName;

var paths = {
    img: [themeBasePath + '/images/**/*.{gif,png,jpg,svg}'],
    js:  [themeBasePath + '/js/**/*.js', '!web/themes/custom/vscpa_bootstrap/js/bootstrap-tabcollapse.js'],
    font: [themeBasePath + '/fonts/**/*.{otf,eot,svg,ttf,woff,woff2}'],
    php: ['web/{modules,themes}/**/*.{php,module,inc,install,test,profile,theme}', '!web/{modules,themes}/contrib/**/*.*'],
    phpLint: ['web/{modules,themes}/**/*.{php,module,inc,install,test,profile,theme}', '!web/{modules,themes}/contrib/**/*.*'],
    sass:[themeBasePath + '/scss/**/*.scss', themeBasePath + '/templates/**/*.scss'],
    distCSS: themeBasePath + '/css/',
    distImg: themeBasePath + '/dist/img/',
    distJS: themeBasePath + '/dist/js/',
    distFont: themeBasePath + '/fonts/'
};

var sassPaths = [
  themeBasePath + '/templates/**/*.scss'
];

// Error notification settings for plumber
var plumberErrorHandler = {

};

gulp.task('clean', function(cb) {
    // Delete dynamically-generated files
    del([paths.distCSS, paths.distImg, paths.distJS]);
    cb();
});

gulp.task('eol', function() {
    return gulp.src([].concat(paths.sass, paths.php, paths.js))
        .pipe(eol('\n'));
});

gulp.task('images', function() {
    return gulp.src(paths.img)
        .pipe(plumber(plumberErrorHandler))
        .pipe(changed(paths.distImg))
        .pipe(imagemin({
            svgoPlugins: [{removeViewBox: false}]
        }))
        .pipe(gulp.dest(paths.distImg));
});

gulp.task('js', function() {
    return gulp.src(paths.js)
        .pipe(plumber(plumberErrorHandler))
        .pipe(changed(paths.distJS))

        // Minify and save
        .pipe(uglify())
        .pipe(gulp.dest(paths.distJS))
});

gulp.task('jshint', function() {
    return gulp.src(paths.js)
        .pipe(plumber(plumberErrorHandler))
        .pipe(jshint())
        .pipe(jshint.reporter())
        .pipe(jshint.reporter('fail'));
});

gulp.task('phpcs', function() {
    return gulp.src(paths.php)
        .pipe(plumber(plumberErrorHandler))
        .pipe(phpcs({
            bin: 'vendor/bin/phpcs',
            standard: 'vendor/drupal/coder/coder_sniffer/Drupal',
            warningSeverity: 0
        }))
        .pipe(phpcs.reporter('log'))
        .pipe(phpcs.reporter('fail'));
});

gulp.task('phplint', function(cb) {
    phplint(paths.php, {limit: 10}, function (err, stdout, stderr) {
        if (err) {
            cb(err);
        } else {
            cb();
        }
    });
});

gulp.task('phpunit', function() {
    return gulp.src('phpunit.xml*')
        .pipe(phpunit('', {notify: true}));
});

gulp.task('sass', function() {
    return gulp.src(paths.sass)
        .pipe(sass({
            includePaths: sassPaths
        }))
        .pipe(plumber(plumberErrorHandler))
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(autoprefixer({
            browsers: ['last 3 versions', 'ie >= 9']
        }))
        .pipe(gulp.dest(paths.distCSS))
        .pipe(cssmin())
        .pipe(rename({suffix: '.min'}))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(paths.distCSS));
});

gulp.task('sass-dev', function() {
    return gulp.src(paths.sass)
        .pipe(sass({
            includePaths: sassPaths
        }))
        .pipe(plumber(plumberErrorHandler))
        .pipe(sass())
        .pipe(autoprefixer({
            browsers: ['last 3 versions', 'ie >= 9']
        }))
        .pipe(gulp.dest(paths.distCSS))
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(paths.distCSS))
        .pipe(browserSync.stream());
});


gulp.task('lint:sass', function() {
    return gulp.src(paths.sass)
        .pipe(sassLint())
        .pipe(sassLint.format())
        .pipe(sassLint.failOnError())
});

// BrowserSync
gulp.task('browser-sync', function() {
    //initialize browsersync
    browserSync.init({
        proxy: devSiteUrl,
        host: devSiteUrl ,
        online: true,
        open: false,
        port: 3000,
        // injectChanges: false, // Uncomment this to line to use browser-sync when logged in.
        files: paths.distCSS + '*.css'
    });

    gulp.watch(paths.sass, ['sass-dev']);
});

// Combined tasks
gulp.task('lint', function() {
    gulp.start('eol', 'phpcs', 'jshint', 'lint:sass');
});

gulp.task('build', ['clean'], function () {
  gulp.start('sass', 'js', 'images');
});

gulp.task('test', function() {
    gulp.start('lint', 'phpcs', 'phpunit');
});

gulp.task('test:ci', function() {
    gulp.start('eol', 'jshint', 'lint:sass');
});

gulp.task('default', function () {
    gulp.start('test', 'build');
});

gulp.task('watch', function() {
    gulp.watch(paths.php, ['eol', 'phpcs']);
    gulp.watch(paths.sass, ['sass']);
    gulp.watch(paths.js, ['eol', 'jshint', 'js']);
    gulp.watch(paths.img, ['images']);
});

// gulp.task('pre-commit', ['test']);
