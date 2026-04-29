<?php
/**
 * inc/login.php
 * WordPress login page customization — styling, logo, and branding.
 * Login redirect logic lives in inc/registration-helpers.php.
 */

// Enqueue login stylesheet
add_action( 'login_enqueue_scripts', function() {
	wp_enqueue_style( 'tb-login', get_stylesheet_directory_uri() . '/assets/css/login.css' );
} );

// Custom logo via inline style
add_action( 'login_enqueue_scripts', function() { ?>
	<style type="text/css">
		#login h1 a, .login h1 a {
			background-image: url(<?= get_stylesheet_directory_uri() ?>/assets/img/Trailblazers-Logomark.png);
		}
	</style>
<?php } );

// Logo links back to the main site
add_filter( 'login_headerurl', function() {
	return 'https://trailblazers.team';
} );

// Activation email — custom subject
add_filter( 'wpmu_signup_user_notification_subject', function( $text ) {
	return 'Activate Your New Trailblazers Account';
}, 10, 4 );

// Activation email — custom message
add_filter( 'wpmu_signup_user_notification_email', function( $message, $user, $user_email, $key, $meta ) {
	return sprintf(
		__( "To activate your new Trailblazers account, please click the following link:\n\n%s\n\nAfter you activate you will be able to log in.\n\n" ),
		site_url( "?page=gf_activation&key=$key" )
	);
}, 10, 5 );