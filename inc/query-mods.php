<?php
/**
 * inc/query-mods.php
 * Query modifications for archives and custom post type listings.
 *
 * Use this file for:
 * - pre_get_posts filters to adjust archive queries
 * - Default ordering for CPT archives
 * - Posts per page overrides for specific post types
 * - Excluding CPTs from search results
 */
 
 // =============================================================================
 // ATHLETE CPT — ADMIN LIST COLUMNS
 // =============================================================================
 // Adds a "Family" column to the Athlete post type list view in wp-admin.
 // The family field (group_tb_athlete.json) is a top-level ACF post object
 // with return_format: id, so get_field() returns a raw integer.
 // Includes a fallback for orphaned records where the Family post is missing.
 // =============================================================================
 
 add_filter( 'manage_athlete_posts_columns', function( $columns ) {
     $new = [];
     foreach ( $columns as $key => $label ) {
         $new[ $key ] = $label;
         if ( $key === 'title' ) {
             $new['family'] = 'Family';
         }
     }
     return $new;
 } );
 
 add_action( 'manage_athlete_posts_custom_column', function( $column, $post_id ) {
     if ( $column !== 'family' ) return;
 
     $family_id = get_field( 'family', $post_id );
     if ( ! $family_id ) {
         echo '—';
         return;
     }
 
     $family = get_post( $family_id );
     if ( ! $family ) {
         echo '<em>Missing (' . esc_html( $family_id ) . ')</em>';
         return;
     }
 
     $edit_url = get_edit_post_link( $family_id );
     echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $family->post_title ) . '</a>';
 }, 10, 2 );
 
 
 // =============================================================================
 // USERS — ADMIN LIST COLUMNS
 // =============================================================================
 // Adds a "Family" column to the Users list view in wp-admin.
 // The family field (group_tb_user.json) lives in wp_usermeta and is accessed
 // via ACF using the 'user_{id}' prefix. Uses manage_users_custom_column
 // (filter, not action) — return the output string rather than echoing.
 // =============================================================================
 
 add_filter( 'manage_users_columns', function( $columns ) {
     $columns['family'] = 'Family';
     return $columns;
 } );
 
 add_filter( 'manage_users_custom_column', function( $output, $column, $user_id ) {
     if ( $column !== 'family' ) return $output;
 
     $family_id = get_field( 'family', 'user_' . $user_id );
     if ( ! $family_id ) return '—';
 
     $family = get_post( $family_id );
     if ( ! $family ) return '<em>Missing (' . esc_html( $family_id ) . ')</em>';
 
     return '<a href="' . esc_url( get_edit_post_link( $family_id ) ) . '">' . esc_html( $family->post_title ) . '</a>';
 }, 10, 3 );
 
 
 // =============================================================================
 // USER PROFILE — LOCK FAMILY FIELDS FOR NON-ADMINS
 // =============================================================================
 // Prevents non-admin users from editing their own family and family_id fields
 // on the WP user profile screen. ACF has no built-in read-only toggle, so
 // acf/prepare_field is used to set disabled+readonly before the field renders.
 //
 // Scoped to profile and user-edit screens only — no effect on front-end or
 // post edit contexts where identically-named fields exist on CPTs.
 //
 // Note: disabled+readonly is sufficient for this use case. If a server-side
 // write guard is ever needed, add an acf/update_field filter.
 // =============================================================================
 
 add_filter( 'acf/prepare_field/name=family', function( $field ) {
     if ( ! function_exists( 'get_current_screen' ) ) return $field;
     $screen = get_current_screen();
     if ( ! $screen || ! in_array( $screen->id, [ 'profile', 'user-edit' ], true ) ) {
         return $field;
     }
 
     if ( ! current_user_can( 'manage_options' ) ) {
         $field['disabled'] = true;
         $field['readonly'] = true;
     }
 
     return $field;
 } );
 
 add_filter( 'acf/prepare_field/name=family_id', function( $field ) {
     if ( ! function_exists( 'get_current_screen' ) ) return $field;
     $screen = get_current_screen();
     if ( ! $screen || ! in_array( $screen->id, [ 'profile', 'user-edit' ], true ) ) {
         return $field;
     }
 
     if ( ! current_user_can( 'manage_options' ) ) {
         $field['disabled'] = true;
         $field['readonly'] = true;
     }
 
     return $field;
 } );