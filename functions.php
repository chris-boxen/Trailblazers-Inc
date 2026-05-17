<?php
/**
 * functions.php
 * Theme function loader for Trailblazers 2026 child theme.
 *
 * All functional code lives in inc/ — this file requires each module.
 * Add new includes here as new functional areas are introduced.
 */

require_once get_stylesheet_directory() . '/inc/divi.php';
require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/cpt-hooks.php';
require_once get_stylesheet_directory() . '/inc/query-mods.php';
require_once get_stylesheet_directory() . '/inc/acf-helpers.php';
require_once get_stylesheet_directory() . '/inc/gravity-helpers.php';
require_once get_stylesheet_directory() . '/inc/event-helpers.php';
require_once get_stylesheet_directory() . '/inc/registration-helpers.php';
require_once get_stylesheet_directory() . '/inc/results-helpers.php';
require_once get_stylesheet_directory() . '/inc/login.php';
require_once get_stylesheet_directory() . '/inc/admin-widgets.php';


/*------------------------------------------------------------------------------------*/
/*	Load child theme stylesheets & scripts
/*------------------------------------------------------------------------------------*/

add_action( 'wp_enqueue_scripts', 'tb_enqueue_assets' );
function tb_enqueue_assets() {

    $ver = '1.1'; // bump this when you push changes

    wp_enqueue_style(
        'tb-styles',
        get_stylesheet_directory_uri() . '/assets/css/styles.css',
        array(),
        $ver
    );
    wp_enqueue_style(
        'tb-templates',
        get_stylesheet_directory_uri() . '/assets/css/templates.css',
        array( 'tb-styles' ),
        $ver
    );
    wp_enqueue_style(
        'tb-events',
        get_stylesheet_directory_uri() . '/assets/css/events.css',
        array( 'tb-styles' ),
        $ver
    );
    wp_enqueue_style(
        'tb-isotope',
        get_stylesheet_directory_uri() . '/assets/css/isotope.css',
        array( 'tb-styles' ),
        $ver
    );

    wp_enqueue_script(
        'tb-scripts',
        get_stylesheet_directory_uri() . '/assets/js/tb.js',
        array( 'jquery' ),
        $ver,
        true
    );
}

add_action("wp_enqueue_scripts", "load_childTheme_styles", 11);
function load_childTheme_styles()
{
  wp_enqueue_script(
  'tb-scripts',
  get_stylesheet_directory_uri() . '/assets/js/tb.js?v=1.1',
  array('jquery'), false, true
  );
}