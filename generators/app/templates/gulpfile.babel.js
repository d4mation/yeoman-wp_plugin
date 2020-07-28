'use strict';

import plugins       from 'gulp-load-plugins';
import yargs         from 'yargs';
import browser       from 'browser-sync';
import gulp          from 'gulp';
import rimraf        from 'rimraf';
import yaml          from 'js-yaml';
import fs            from 'fs';
import dateFormat    from 'dateformat';
import webpackStream from 'webpack-stream';
import webpack2      from 'webpack';
import named         from 'vinyl-named';
import log           from 'fancy-log';
import colors        from 'ansi-colors';
import compareVersions from 'compare-versions';

var pkg = JSON.parse( fs.readFileSync( './package.json' ) );
var textDomain = pkg.name.toLowerCase().replace( /_/g, '-' ).replace( /\s/g, '-' ).trim();

// Load all Gulp plugins into one variable
const $ = plugins();

// Check for --production flag
var PRODUCTION = !!(yargs.argv.production);

// Check for --development flag unminified with sourcemaps
var DEV = !!(yargs.argv.dev);

// Load settings from settings.yml
const { BROWSERSYNC, COMPATIBILITY, REVISIONING, PATHS } = loadConfig();

// Check if file exists synchronously
function checkFileExists(filepath) {
  let flag = true;
  try {
    fs.accessSync(filepath, fs.F_OK);
  } catch(e) {
    flag = false;
  }
  return flag;
}

// Load default or custom YML config file
function loadConfig() {
  log('Loading config file...');

  if (checkFileExists('config.yml')) {
    // config.yml exists, load it
    log(colors.cyan('config.yml'), 'exists, loading', colors.cyan('config.yml'));
    let ymlFile = fs.readFileSync('config.yml', 'utf8');
    return yaml.load(ymlFile);

  } else if(checkFileExists('config-default.yml')) {
    // config-default.yml exists, load it
    log(colors.cyan('config.yml'), 'does not exist, loading', colors.cyan('config-default.yml'));
    let ymlFile = fs.readFileSync('config-default.yml', 'utf8');
    return yaml.load(ymlFile);

  } else {
    // Exit if config.yml & config-default.yml do not exist
    log('Exiting process, no config file exists.');
    log('Error Code:', err.code);
    process.exit(1);
  }
}

// Delete the "dist" folder
// This happens every time a build starts
function clean(done) {
  rimraf(PATHS.dist, done);
}

// Copy files out of the assets folder
// This task skips over the "img", "js", and "scss" folders, which are parsed separately
function copy() {
  return gulp.src(PATHS.assets)
    .pipe(gulp.dest(PATHS.dist + '/assets'));
}

// Compile Sass into CSS
// In production, the CSS is compressed
function sass() {

  return gulp.src( PATHS.entries.scss, { allowEmpty: true } )
    .pipe( named() )
    .pipe( $.sourcemaps.init() )
    .pipe(
      $.sass( {
        includePaths: PATHS.sass,
        outputStyle: 'compressed'
      } )
      .on( 'error', $.sass.logError )
    )
    .pipe(
      $.autoprefixer( {
        browsers: COMPATIBILITY
      } )
    )
    .pipe(
      $.if( PRODUCTION, $.cleanCss( { compatibility: 'ie9' } ) )
    )
    .pipe(
      $.if( ! PRODUCTION, $.sourcemaps.write() )
    )
    .pipe(
      $.if( REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev() )
    )
    .pipe(
      gulp.dest( PATHS.dist + '/assets/css' )
    )
    .pipe(
      $.if( REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev.manifest() )
    )
    .pipe(
      gulp.dest( PATHS.dist + '/assets/css' )
    );

}

// Combine JavaScript into one file
// In production, the file is minified
const webpack = {
  config: {
    module: {
      rules: [
        {
          test: /.js$/,
          loader: 'babel-loader',
          exclude: /node_modules(?![\\\/]foundation-sites)/,
        },
      ],
    },
    externals: {
      jquery: 'jQuery',
    },
  },

  changeHandler(err, stats) {
    log('[webpack]', stats.toString({
      colors: true,
    }));

    browser.reload();
  },

  build() {
    return gulp.src(PATHS.entries.js, { allowEmpty: true } )
      .pipe(named())
      .pipe(webpackStream(webpack.config, webpack2))
      .pipe($.uglify())
      .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev()))
      .pipe(gulp.dest(PATHS.dist + '/assets/js'))
      .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev.manifest()))
      .pipe(gulp.dest(PATHS.dist + '/assets/js'));
  },

  watch() {
    const watchConfig = Object.assign(webpack.config, {
      watch: true,
      devtool: 'inline-source-map',
    });

    return gulp.src(PATHS.entries.js, { allowEmpty: true } )
      .pipe(named())
      .pipe(webpackStream(watchConfig, webpack2, webpack.changeHandler)
        .on('error', (err) => {
          log('[webpack:error]', err.toString({
            colors: true,
          }));
        }),
      )
      .pipe(gulp.dest(PATHS.dist + '/assets/js'));
  },
};

gulp.task('webpack:build', webpack.build);
gulp.task('webpack:watch', webpack.watch);

function tinymce() {

  return gulp.src( "src/assets/js/admin/tinymce/**/*.js" )
    .pipe( $.foreach( function( stream, file ) {
      return stream
        .pipe( $.babel() )
        .pipe( $.uglify() )
        .pipe( gulp.dest( PATHS.dist + '/assets/js/tinymce' ) )
    } ) );

}

// Copy images to the "dist" folder
// In production, the images are compressed
function images() {
  return gulp.src('src/assets/img/**/*')
    .pipe($.if(PRODUCTION, $.imagemin({
      progressive: true
    })))
    .pipe(gulp.dest(PATHS.dist + '/assets/img'));
}

// Start BrowserSync to preview the site in
function server(done) {
  browser.init({
    proxy: BROWSERSYNC.url,

    ui: {
      port: 8080
    },

  });
  done();
}

// Reload the browser with BrowserSync
function reload(done) {
  browser.reload();
  done();
}

// Watch for changes to static assets, pages, Sass, and JavaScript
function watch() {
  gulp.watch(PATHS.assets, copy);
  gulp.watch('src/assets/scss/**/*.scss').on('all', sass);
  //gulp.watch('**/*.php').on('all', browser.reload);
  gulp.watch('src/assets/js/**/*.js').on('all', gulp.series('webpack:build', tinymce));
  gulp.watch('src/assets/img/**/*').on('all', gulp.series(images));
}

// Build the "dist" folder by running all of the below tasks
gulp.task('build',
 gulp.series(clean, sass, 'webpack:build', tinymce, images, copy));

// Build the site, run the server, and watch for file changes
gulp.task('default',
  gulp.series('build', server, watch));

function version() {
  
    return gulp.src([
        'admin/**/*',
        'assets/src/**/*',
        'core/**/*',
        'vendor/**/*',
        'languages/**/*',
        'templates/**/*',
        textDomain + '.php',
        'readme.txt'
    ], { base: './', allowEmpty: true } )
    // Doc block versions, only update on non-Betas and 1.0.0+ releases
        .pipe( $.if( ( pkg.version.indexOf( 'b' ) == -1 && compareVersions( pkg.version, '1.0.0' ) !== -1 ), $.replace( /\{\{VERSION}}/g, pkg.version ) ) )
        // Plugin header
        .pipe($.replace(/(\* Version: ).*/, "$1" + pkg.version))
        // readme.txt
    .pipe( $.replace( /(Stable tag: ).*/, function( match, captureGroup, offset, file ) {
      return captureGroup + pkg.version; // This really shouldn't be necessary, but it wouldn't work otherwise
    } ) )
        .pipe(gulp.dest('./'));
}

gulp.task( 'version', version );

function setProd( done ) {
  
  PRODUCTION = true;
  
  done();
  
}

function removeProd( done ) {
  
  PRODUCTION = false;
  
  done();
  
}

function generate_pot() {
    return gulp.src('./**/*.php')
        .pipe($.sort())
        .pipe($.wpPot({
            domain: textDomain,
            package: pkg.name,
        }))
        .pipe(gulp.dest('./languages/' + textDomain +'.pot'));
}

require( 'gulp-grunt' )( gulp, {
  prefix: 'release:grunt-',
} ); // add all the gruntfile tasks to gulp

// Copy relevant files to another directory
function releaseCopy() {

  return gulp.src([
    '!.git/**/*',
        'admin/**/*',
        'dist/assets/**/*',
        'core/**/*',
        'core/library/**/*',
    '!core/library/rbp-support/{build,build/**}',
    '!core/library/rbp-support/{gulp,gulp/**}',
    '!core/library/rbp-support/{node_modules,node_modules/**}',
    '!core/library/rbp-support/**/*.zip',
    '!core/library/rbm-field-helpers/{bin,bin/**}',
    '!core/library/rbm-field-helpers/{node_modules,node_modules/**}',
    '!core/library/rbm-field-helpers/**/*.zip',
        'languages/**/*',
        'templates/**/*',
        textDomain + '.php',
        'readme.txt',
    '!./**/package.json',
    '!./**/package-lock.json',
    '!./**/config.yml',
    '!./**/webpack.config.js',
    '!./**/gulpfile.js',
    '!./**/gulpfile.babel.js',
    '!./**/gruntfile.js',
    '!./**/README.md'
    ], { base: './', allowEmpty: true } )
    .pipe(gulp.dest( textDomain ));
}

function releaseCleanup() {
  
  return gulp.src( './' + textDomain, { read: false } )
    .pipe( $.clean() )
  
}

function rename( done ) {
  
  fs.renameSync( './packaged/' + textDomain + '.zip', './packaged/' + textDomain + '-' + pkg.version + '.zip' );
  
  done();
  
}

// Package task
gulp.task('package',
  gulp.series(setProd, 'version', 'build', generate_pot, releaseCopy, 'release:grunt-compress', rename, releaseCleanup, removeProd, 'build'));