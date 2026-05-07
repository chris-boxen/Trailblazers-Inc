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
 
 // =============================================================================
 // ENROLLMENT — ADMIN LIST COLUMNS
 // =============================================================================
 
 /**
  * Add New/Returning Athlete and Participation Type columns to the
  * Enrollment post list in WP Admin.
  */
 add_filter( 'manage_enrollment_posts_columns', function( $columns ) {
     // Insert after the title column.
     $new = [];
     foreach ( $columns as $key => $label ) {
         $new[ $key ] = $label;
         if ( $key === 'title' ) {
             $new['new_returning_athlete'] = 'New / Returning';
             $new['participation_type']    = 'Participation Type';
         }
     }
     return $new;
 } );
 
 add_action( 'manage_enrollment_posts_custom_column', function( $column, $post_id ) {
     switch ( $column ) {
         case 'new_returning_athlete':
             $val = get_field( 'new_returning_athlete', $post_id );
             echo $val ? esc_html( $val ) : '<span style="color:#999;">—</span>';
             break;
         case 'participation_type':
             $val = get_field( 'participation_type', $post_id );
             echo $val ? esc_html( $val ) : '<span style="color:#999;">—</span>';
             break;
     }
 }, 10, 2 );
 
 /**
  * Make both columns sortable.
  */
 add_filter( 'manage_edit-enrollment_sortable_columns', function( $columns ) {
     $columns['new_returning_athlete'] = 'new_returning_athlete';
     $columns['participation_type']    = 'participation_type';
     return $columns;
 } );
 
 add_action( 'pre_get_posts', function( $query ) {
     if ( ! is_admin() || ! $query->is_main_query() ) return;
     if ( $query->get( 'post_type' ) !== 'enrollment' ) return;
 
     $orderby = $query->get( 'orderby' );
     if ( in_array( $orderby, [ 'new_returning_athlete', 'participation_type' ], true ) ) {
         $query->set( 'meta_key', $orderby );
         $query->set( 'orderby', 'meta_value' );
     }
 } );
 
 // =============================================================================
 // ENROLLMENT — PAYMENT STATUS COLUMN
 // =============================================================================
 
 add_filter( 'manage_enrollment_posts_columns', function( $columns ) {
     $new = [];
     foreach ( $columns as $key => $label ) {
         $new[ $key ] = $label;
         if ( $key === 'title' ) {
             $new['payment_status'] = 'Payment Status';
         }
     }
     return $new;
 } );
 
 add_action( 'manage_enrollment_posts_custom_column', function( $column, $post_id ) {
     if ( $column !== 'payment_status' ) return;
     $val = get_post_meta( $post_id, 'status_payment_status', true );
     echo $val ? esc_html( $val ) : '<span style="color:#999;">—</span>';
 }, 10, 2 );
 
 add_filter( 'manage_edit-enrollment_sortable_columns', function( $columns ) {
     $columns['payment_status'] = 'payment_status';
     return $columns;
 } );
 
 add_action( 'pre_get_posts', function( $query ) {
     if ( ! is_admin() || ! $query->is_main_query() ) return;
     if ( $query->get( 'post_type' ) !== 'enrollment' ) return;
 
     if ( $query->get( 'orderby' ) === 'payment_status' ) {
         $query->set( 'meta_key', 'status_payment_status' );
         $query->set( 'orderby', 'meta_value' );
     }
 } );
 
 // =============================================================================
 // ENROLLMENT — ADMIN LIST FILTERS
 // =============================================================================
 
 add_action( 'restrict_manage_posts', function( $post_type ) {
     if ( $post_type !== 'enrollment' ) return;
 
     $new_returning_filter = $_GET['filter_new_returning_athlete'] ?? '';
     $participation_filter = $_GET['filter_participation_type']    ?? '';
     ?>
     <select name="filter_new_returning_athlete">
         <option value="">All New / Returning</option>
         <?php foreach ( [ 'New Athlete', 'Returning Athlete' ] as $choice ) : ?>
             <option value="<?php echo esc_attr( $choice ); ?>"
                 <?php selected( $new_returning_filter, $choice ); ?>>
                 <?php echo esc_html( $choice ); ?>
             </option>
         <?php endforeach; ?>
     </select>
 
     <select name="filter_participation_type">
         <option value="">All Participation Types</option>
         <?php foreach ( [ 'Athlete', 'Sibling Runner' ] as $choice ) : ?>
             <option value="<?php echo esc_attr( $choice ); ?>"
                 <?php selected( $participation_filter, $choice ); ?>>
                 <?php echo esc_html( $choice ); ?>
             </option>
         <?php endforeach; ?>
     </select>
     <?php
 } );

 
 add_action( 'restrict_manage_posts', function( $post_type ) {
     if ( $post_type !== 'enrollment' ) return;
 
     $current = $_GET['filter_enrollment_payment_status'] ?? '';
 
     ?>
     <select name="filter_enrollment_payment_status">
         <option value="">All Payment Statuses</option>
         <?php foreach ( [ 'Not Received', 'Partially Paid', 'Paid', 'Waived', 'Refunded' ] as $choice ) : ?>
             <option value="<?php echo esc_attr( $choice ); ?>"
                 <?php selected( $current, $choice ); ?>>
                 <?php echo esc_html( $choice ); ?>
             </option>
         <?php endforeach; ?>
     </select>
     <?php
 } );
 
 add_action( 'parse_query', function( $query ) {
     if ( ! is_admin() || ! $query->is_main_query() ) return;
     if ( $query->get( 'post_type' ) !== 'enrollment' ) return;
 
     if ( ! empty( $_GET['filter_enrollment_payment_status'] ) ) {
         $query->set( 'meta_query', [ [
             'key'     => 'status_payment_status',
             'value'   => sanitize_text_field( $_GET['filter_enrollment_payment_status'] ),
             'compare' => '=',
         ] ] );
     }
 } );
 
 // =============================================================================
 // APPLICATION — ADMIN LIST COLUMNS
 // =============================================================================
 
 add_filter( 'manage_application_posts_columns', function( $columns ) {
     $new = [];
     foreach ( $columns as $key => $label ) {
         $new[ $key ] = $label;
         if ( $key === 'title' ) {
             $new['payment_method'] = 'Payment Method';
             $new['payment_status'] = 'Payment Status';
         }
     }
     return $new;
 } );
 
 add_action( 'manage_application_posts_custom_column', function( $column, $post_id ) {
     switch ( $column ) {
         case 'payment_method':
             $val = get_field( 'payment_method', $post_id );
             echo $val ? esc_html( $val ) : '<span style="color:#999;">—</span>';
             break;
         case 'payment_status':
             $val = get_field( 'payment_status', $post_id );
             echo $val ? esc_html( $val ) : '<span style="color:#999;">—</span>';
             break;
     }
 }, 10, 2 );
 
 add_filter( 'manage_edit-application_sortable_columns', function( $columns ) {
     $columns['payment_method'] = 'payment_method';
     $columns['payment_status'] = 'payment_status';
     return $columns;
 } );
 
 add_action( 'pre_get_posts', function( $query ) {
     if ( ! is_admin() || ! $query->is_main_query() ) return;
     if ( $query->get( 'post_type' ) !== 'application' ) return;
 
     $orderby = $query->get( 'orderby' );
     if ( in_array( $orderby, [ 'payment_method', 'payment_status' ], true ) ) {
         $query->set( 'meta_key', $orderby );
         $query->set( 'orderby', 'meta_value' );
     }
 } );
 
 // =============================================================================
 // APPLICATION — ADMIN LIST FILTERS
 // =============================================================================
 
 add_action( 'restrict_manage_posts', function( $post_type ) {
     if ( $post_type !== 'application' ) return;
 
     $method_filter  = $_GET['filter_payment_method']  ?? '';
     $status_filter  = $_GET['filter_payment_status']  ?? '';
 
     ?>
     <select name="filter_payment_method">
         <option value="">All Payment Methods</option>
         <?php foreach ( [ 'Credit Card', 'Check/Cash' ] as $choice ) : ?>
             <option value="<?php echo esc_attr( $choice ); ?>"
                 <?php selected( $method_filter, $choice ); ?>>
                 <?php echo esc_html( $choice ); ?>
             </option>
         <?php endforeach; ?>
     </select>
 
     <select name="filter_payment_status">
         <option value="">All Payment Statuses</option>
         <?php foreach ( [ 'Not Received', 'Partially Paid', 'Paid', 'Waived', 'Refunded' ] as $choice ) : ?>
             <option value="<?php echo esc_attr( $choice ); ?>"
                 <?php selected( $status_filter, $choice ); ?>>
                 <?php echo esc_html( $choice ); ?>
             </option>
         <?php endforeach; ?>
     </select>
     <?php
 } );
 
 add_action( 'parse_query', function( $query ) {
     if ( ! is_admin() || ! $query->is_main_query() ) return;
     if ( $query->get( 'post_type' ) !== 'application' ) return;
 
     $meta_query = [];
 
     if ( ! empty( $_GET['filter_payment_method'] ) ) {
         $meta_query[] = [
             'key'     => 'payment_method',
             'value'   => sanitize_text_field( $_GET['filter_payment_method'] ),
             'compare' => '=',
         ];
     }
 
     if ( ! empty( $_GET['filter_payment_status'] ) ) {
         $meta_query[] = [
             'key'     => 'payment_status',
             'value'   => sanitize_text_field( $_GET['filter_payment_status'] ),
             'compare' => '=',
         ];
     }
 
     if ( ! empty( $meta_query ) ) {
         $query->set( 'meta_query', $meta_query );
     }
 } );