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
 
 /**
  * Add Family column to Athlete list.
  */
 add_filter( 'manage_athlete_posts_columns', function( $columns ) {
     // Insert after the title column
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
 
     $family_id = get_field( 'family', $post_id ); // returns ID (return_format: id)
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