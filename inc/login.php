<?php
/**
 * inc/login.php
 * WordPress login page customization — styling, logo, branding,
 * and registration form enhancements.
 *
 * Login redirect logic lives in inc/registration-helpers.php.
 *
 * Registration form additions:
 *   - First Name and Last Name fields added to the WP registration form
 *   - Username auto-generated as first initial + last name (lowercase)
 *     e.g. "John Smith" → "jsmith", with numeric suffix for duplicates
 *   - First/last name saved to WP user meta on successful registration
 */


// ---------------------------------------------------------------------------
// 1. Enqueue login stylesheet
// ---------------------------------------------------------------------------

add_action( 'login_enqueue_scripts', function() {
	wp_enqueue_style( 'tb-login', get_stylesheet_directory_uri() . '/assets/css/login.css' );

	// On the registration page only, hide the username field — it's auto-generated
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'register' ) {
		wp_add_inline_style( 'tb-login',
			'#registerform > p:first-child { display: none !important; }
			 #registerform p.submit { display: block !important; }'
		);
	}
} );


// ---------------------------------------------------------------------------
// 2. Custom logo via inline style
// ---------------------------------------------------------------------------

add_action( 'login_enqueue_scripts', function() { ?>
	<style type="text/css">
		#login h1 a, .login h1 a {
			background-image: url(<?= get_stylesheet_directory_uri() ?>/assets/img/Trailblazers-Logomark.png);
		}
	</style>
<?php } );


// ---------------------------------------------------------------------------
// 3. Logo links back to the main site
// ---------------------------------------------------------------------------

add_filter( 'login_headerurl', function() {
	return 'https://trailblazers.team';
} );


// ---------------------------------------------------------------------------
// 4. Add First Name and Last Name fields to the registration form
//    Username field is hidden — auto-generated from first + last name
// ---------------------------------------------------------------------------

add_action( 'register_form', function() { ?>
	<p>
		<label for="first_name"><?= esc_html__( 'First Name' ) ?><br>
			<input type="text"
				   name="first_name"
				   id="first_name"
				   class="input"
				   value="<?= esc_attr( $_POST['first_name'] ?? '' ) ?>"
				   size="25"
				   autocomplete="given-name"
				   required />
		</label>
	</p>
	<p>
		<label for="last_name"><?= esc_html__( 'Last Name' ) ?><br>
			<input type="text"
				   name="last_name"
				   id="last_name"
				   class="input"
				   value="<?= esc_attr( $_POST['last_name'] ?? '' ) ?>"
				   size="25"
				   autocomplete="family-name"
				   required />
		</label>
	</p>
	<script>
		document.addEventListener( 'DOMContentLoaded', function() {
			var form      = document.getElementById( 'registerform' );
			var userLogin = document.getElementById( 'user_login' );
			var email     = document.querySelector( '#registerform p:has(#user_email)' );
			var firstName = document.querySelector( '#registerform p:has(#first_name)' );
			var lastName  = document.querySelector( '#registerform p:has(#last_name)' );

			// Move name fields above email
			if ( form && email && firstName && lastName ) {
				form.insertBefore( firstName, email );
				form.insertBefore( lastName,  email );
			}

			// Auto-populate the hidden username field from first + last name
			// so WP's own validation passes. Server-side pre_user_login
			// handles deduplication with a numeric suffix if needed.
			function generateUsername() {
				var first = document.getElementById( 'first_name' ).value.trim();
				var last  = document.getElementById( 'last_name' ).value.trim();
				if ( first && last && userLogin ) {
					var base = ( first + last ).toLowerCase().replace( /[^a-z0-9]/g, '' );
					userLogin.value = base;
				}
			}

			var firstInput = document.getElementById( 'first_name' );
			var lastInput  = document.getElementById( 'last_name' );
			if ( firstInput ) firstInput.addEventListener( 'input', generateUsername );
			if ( lastInput )  lastInput.addEventListener( 'input', generateUsername );
		} );
	</script>
<?php } );


// ---------------------------------------------------------------------------
// 5. Validate first and last name on registration
// ---------------------------------------------------------------------------

add_filter( 'registration_errors', function( $errors, $sanitized_user_login, $user_email ) {
	$first = trim( $_POST['first_name'] ?? '' );
	$last  = trim( $_POST['last_name']  ?? '' );

	if ( empty( $first ) ) {
		$errors->add( 'first_name_error', __( 'Please enter your first name.' ) );
	}

	if ( empty( $last ) ) {
		$errors->add( 'last_name_error', __( 'Please enter your last name.' ) );
	}

	return $errors;
}, 10, 3 );


// ---------------------------------------------------------------------------
// 6. Generate username from first initial + last name before registration
//    Runs at priority 5 so it fires before WP checks for duplicate usernames
// ---------------------------------------------------------------------------

add_filter( 'pre_user_login', function( $user_login ) {
	$first = sanitize_text_field( trim( $_POST['first_name'] ?? '' ) );
	$last  = sanitize_text_field( trim( $_POST['last_name']  ?? '' ) );

	if ( empty( $first ) || empty( $last ) ) return $user_login;

	// Build base: first initial + last name, lowercase, no spaces/special chars
	$base     = strtolower( $first . $last );
	$base     = preg_replace( '/[^a-z0-9]/', '', $base );
	$username = $base;
	$suffix   = 2;

	// Increment suffix until unique
	while ( username_exists( $username ) ) {
		$username = $base . $suffix;
		$suffix++;
	}

	return $username;
} );


// ---------------------------------------------------------------------------
// 7. Save first and last name to user meta after successful registration
// ---------------------------------------------------------------------------

add_action( 'user_register', function( $user_id ) {
	$first = sanitize_text_field( trim( $_POST['first_name'] ?? '' ) );
	$last  = sanitize_text_field( trim( $_POST['last_name']  ?? '' ) );

	if ( $first ) {
		update_user_meta( $user_id, 'first_name', $first );
	}

	if ( $last ) {
		update_user_meta( $user_id, 'last_name', $last );
	}
} );


// ---------------------------------------------------------------------------
// 8. Activation email — custom subject
// ---------------------------------------------------------------------------

add_filter( 'wpmu_signup_user_notification_subject', function( $text ) {
	return 'Activate Your New Trailblazers Account';
}, 10, 4 );


// ---------------------------------------------------------------------------
// 9. Activation email — custom message
// ---------------------------------------------------------------------------

add_filter( 'wpmu_signup_user_notification_email', function( $message, $user, $user_email, $key, $meta ) {
	return sprintf(
		__( "To activate your new Trailblazers account, please click the following link:\n\n%s\n\nAfter you activate you will be able to log in.\n\n" ),
		site_url( "?page=gf_activation&key=$key" )
	);
}, 10, 5 );