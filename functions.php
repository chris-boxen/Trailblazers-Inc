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

add_filter( 'gpnf_session_path', function( $path ) {
    if ( empty( $path ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        return parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_PATH );
    }
    return $path;
} );