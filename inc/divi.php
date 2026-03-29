<?php
/**
 * inc/divi.php
 * Divi-specific filters and modifications.
 */

/*-----------------------------------------------------------------------------------*/
/* Hide Divi Projects CPT
/* Divi registers its own Projects CPT which is not used in this theme.
/*-----------------------------------------------------------------------------------*/
add_filter( 'et_project_posttype_args', function( $args ) {
	return array_merge( $args, [
		'public'             => false,
		'show_ui'            => false,
		'publicly_queryable' => false,
		'show_in_nav_menus'  => false,
	] );
} );