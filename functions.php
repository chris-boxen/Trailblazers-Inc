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
require_once get_stylesheet_directory() . '/inc/registration-helpers.php';
require_once get_stylesheet_directory() . '/inc/login.php';

/*------------------------------------------------------------------------------------*/
/*	Load child theme stylesheets & scripts
/*------------------------------------------------------------------------------------*/

add_action("wp_enqueue_scripts", "load_childTheme_styles", 11);
function load_childTheme_styles()
{
  wp_enqueue_script(
  'tb-scripts',
  get_stylesheet_directory_uri() . '/assets/js/tb.js?v=1.0',
  array('jquery'), false, true
  );
}