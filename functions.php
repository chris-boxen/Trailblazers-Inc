<?php

/*-----------------------------------------------------------------------------------*/
/*	Hide Divi Projects (CPT)
/*-----------------------------------------------------------------------------------*/
add_filter('et_project_posttype_args', function($args) {
  return array_merge($args, [
	'public'              => false,
	'show_ui'             => false,
	'publicly_queryable'  => false,
	'show_in_nav_menus'   => false,
  ]);
});