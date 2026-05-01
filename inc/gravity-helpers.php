<?php
/**
 * inc/gravity-helpers.php
 * Reusable helpers for Gravity Forms and GravityPerks (Gravity Wiz).
 *
 * Use this file for:
 * - gform_pre_render filters for dynamic field population
 * - gform_after_submission hooks for post-submission logic
 * - Notification and confirmation customizations
 * - GravityPerks/GP integration hooks
 * - Helper functions that retrieve or format Gravity Forms entry data
 *
 * Form IDs (local dev — update Registration Settings options page after each import):
 *   Form 11 — Register New Athlete       (nested, permanent)
 *   Form 12 — 2026 Registration: New Family
 *   Form 13 — 2026 Registration: Returning Family
 *   Form 14 — Register Returning Athlete  (nested, permanent)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// =============================================================================
// SECTION 1 — HELPER FUNCTIONS
// =============================================================================

/**
 * Get a registration form ID from the ACF options page.
 *
 * @param  string $type  'new_family' | 'returning_family'
 * @return int           Form ID, or 0 if not configured.
 */
function tb_reg_get_form_id( $type ) {
    $key_map = [
        'new_family'       => 'reg_new_family_form_id',
        'returning_family' => 'reg_returning_family_form_id',
    ];
    if ( ! isset( $key_map[ $type ] ) ) {
        return 0;
    }
    return (int) get_field( $key_map[ $type ], 'option' );
}


/**
 * Retrieve nested (child) GF entries for a GP Nested Forms field.
 *
 * The nested form field stores child entry IDs as a comma-separated string
 * in the parent entry. We fetch each child entry individually via GFAPI.
 *
 * @param  array $entry     Parent GF entry array.
 * @param  int   $field_id  ID of the nested form field on the parent form.
 * @return array            Array of child GF entry arrays (may be empty).
 */
function tb_get_nested_entries( $entry, $field_id ) {
    $raw = rgar( $entry, (string) $field_id );
    if ( empty( $raw ) ) {
        return [];
    }

    $child_ids      = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
    $nested_entries = [];

    foreach ( $child_ids as $child_id ) {
        $child_entry = GFAPI::get_entry( (int) $child_id );
        if ( ! is_wp_error( $child_entry ) ) {
            $nested_entries[] = $child_entry;
        }
    }

    return $nested_entries;
}


/**
 * Build the parents_guardians repeater array for a Family post.
 *
 * Field IDs are identical on both parent forms:
 *   Primary:   9 (first), 10 (last), 11 (relationship), 12 (email), 13 (phone)
 *   Secondary: 15 (first), 16 (last), 17 (relationship), 18 (email), 19 (phone), 20 (notifications)
 *
 * Primary guardian_notifications is always set to 1 (true_false) — not collected on form.
 * Primary is_primary_contact is always set to 1; secondary is always set to 0.
 *
 * @param  array $entry  GF entry array.
 * @return array         ACF repeater row data.
 */
function tb_build_guardians( $entry ) {
    $guardians = [];

    // Primary contact (always present)
    $guardians[] = [
        'guardian_first_name'    => rgar( $entry, '9' ),
        'guardian_last_name'     => rgar( $entry, '10' ),
        'guardian_relationship'  => rgar( $entry, '11' ),
        'guardian_email'         => rgar( $entry, '12' ),
        'guardian_phone'         => rgar( $entry, '13' ),
        'guardian_notifications' => 1,
        'is_primary_contact' => 1,
    ];

    // Secondary contact (optional — only add row if first name is present)
    if ( ! empty( rgar( $entry, '15' ) ) ) {
        $guardians[] = [
            'guardian_first_name'    => rgar( $entry, '15' ),
            'guardian_last_name'     => rgar( $entry, '16' ),
            'guardian_relationship'  => rgar( $entry, '17' ),
            'guardian_email'         => rgar( $entry, '18' ),
            'guardian_phone'         => rgar( $entry, '19' ),
            'guardian_notifications' => rgar( $entry, '20' ) === 'Yes' ? 1 : 0,
            'is_primary_contact' => 0,
        ];
    }

    return $guardians;
}


/**
 * Determine if eligibility is confirmed for a Register New Athlete entry (Form 11).
 *
 * Participation Type field: 1
 * Athlete eligibility checkboxes:  10 (Residency), 12 (Academic), 13 (Running), 14 (Policy)
 *   — Field 11 (Homeschooled) is conditional; skipped in this check.
 * Sibling Runner checkboxes: 16 (Parent Supervision), 17 (Policy)
 *
 * A checkbox choice is confirmed if its entry value is non-empty (any checked value).
 * GF stores checkbox inputs as field_id.choice_index (e.g., "10.1").
 *
 * @param  array $nested_entry  Child GF entry for a new athlete.
 * @return bool
 */
function tb_new_athlete_eligibility_confirmed( $nested_entry ) {
    $participation_type = rgar( $nested_entry, '1' );

    if ( $participation_type === 'Sibling Runner' ) {
        return ! empty( $nested_entry['16.1'] );
    }

    // Athlete
    return ! empty( $nested_entry['10.1'] )
        && ! empty( $nested_entry['12.1'] )
        && ! empty( $nested_entry['13.1'] );
}


/**
 * Determine if eligibility is confirmed for a Register Returning Athlete entry (Form 14).
 *
 * Participation Type field: 9
 * Athlete eligibility checkboxes:  11 (Residency), 13 (Academic), 14 (Running), 15 (Policy)
 *   — Field 12 (Homeschooled) is conditional; skipped in this check.
 * Sibling Runner checkboxes: 17 (Parent Supervision), 18 (Policy)
 *
 * @param  array $nested_entry  Child GF entry for a returning athlete.
 * @return bool
 */
function tb_returning_athlete_eligibility_confirmed( $nested_entry ) {
    $participation_type = rgar( $nested_entry, '9' );

    if ( $participation_type === 'Sibling Runner' ) {
        return ! empty( $nested_entry['17.1'] )
            && ! empty( $nested_entry['18.1'] );
    }

    // Athlete
    return ! empty( $nested_entry['11.1'] )
        && ! empty( $nested_entry['13.1'] )
        && ! empty( $nested_entry['14.1'] )
        && ! empty( $nested_entry['15.1'] );
}


/**
 * Create a new Athlete post from a Register New Athlete nested entry (Form 11).
 *
 * Field map:
 *   1  — Participation Type
 *   3  — First Name
 *   4  — Last Name
 *   5  — Preferred Name / Nickname
 *   6  — Gender
 *   7  — Date of Birth
 *   8  — Grade
 *
 * ACF group structure (group_tb_athlete.json):
 *   field_69c9d99bc44e8  — Names group    (first_name, last_name, preferred_name, slug)
 *   field_69c9dadf75c2d  — Demographics   (gender, dob, athletic_net_id)
 *   field_69c9db84b5b0f  — Status         (account_status, participation_type)
 *   top-level            — family         (post object, written by name)
 *
 * update_field() by sub-field key silently fails for ACF Group sub-fields.
 * Write each group as an array keyed by the parent group's field key.
 *
 * @param  array $nested_entry  Child GF entry (Form 11).
 * @param  int   $family_id     ID of the Family post to associate.
 * @return int|false            New Athlete post ID, or false on failure.
 */
function tb_create_athlete_post( $nested_entry, $family_id ) {
    $first_name = rgar( $nested_entry, '3' );
    $last_name  = rgar( $nested_entry, '4' );

    $athlete_id = wp_insert_post( [
        'post_title'  => trim( "$first_name $last_name" ),
        'post_type'   => 'athlete',
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $athlete_id ) ) {
        error_log( 'TB Registration: Failed to create Athlete post — ' . $athlete_id->get_error_message() );
        return false;
    }

    $gender_map = [ 'Male' => 'M', 'Female' => 'F' ];

    // Names group — must write parent group as array; sub-field key writes silently fail.
    update_field( 'field_69c9d99bc44e8', [
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'preferred_name' => rgar( $nested_entry, '5' ),
        'slug'           => sanitize_title( trim( "$first_name $last_name" ) ),
    ], $athlete_id );

    // Demographics group — same pattern.
    update_field( 'field_69c9dadf75c2d', [
        'gender'          => $gender_map[ rgar( $nested_entry, '6' ) ] ?? '',
        'dob'             => rgar( $nested_entry, '7' ),
        'athletic_net_id' => '',
    ], $athlete_id );

    // Status group — account_status and participation_type are both sub-fields here.
    update_field( 'field_69c9db84b5b0f', [
        'account_status'   => 'Active',
        'participation_type' => rgar( $nested_entry, '1' ) ?: 'Athlete',
    ], $athlete_id );

    // family is top-level — write by name as before.
    update_field( 'family', $family_id, $athlete_id );

    return $athlete_id;
}


/**
 * Create an Enrollment post linking an athlete to the current season registration.
 *
 * @param  array $args {
 *   @type int    $athlete_id             Athlete post ID.
 *   @type int    $family_id              Family post ID.
 *   @type int    $application_id         Application post ID.
 *   @type int    $season_id              Athletic Season post ID.
 *   @type string $new_returning          'New Athlete' | 'Returning Athlete'
 *   @type bool   $eligibility_confirmed  True if all required eligibility boxes were checked.
 *   @type string $singlet_requested      'Yes' | 'No'
 * }
 * @return int|false  New Enrollment post ID, or false on failure.
 */
function tb_create_enrollment_post( $args ) {
    $defaults = [
        'athlete_id'             => 0,
        'family_id'              => 0,
        'application_id'         => 0,
        'season_id'              => 0,
        'new_returning'          => 'New Athlete',
        'eligibility_confirmed'  => false,
        'participation_type'     => 'Athlete',
        'grade'                  => '',
        'singlet_requested'      => 'No',
        'singlet_sizing_group'   => '',
        'singlet_size'           => '',
        'submitted_by'           => 0,
        'digital_signature'      => '',
    ];
    $args = wp_parse_args( $args, $defaults );

    $athlete_post  = get_post( $args['athlete_id'] );
    $athlete_title = $athlete_post ? $athlete_post->post_title : 'Athlete ' . $args['athlete_id'];

    $enrollment_id = wp_insert_post( [
        'post_title'  => "$athlete_title — 2026 XC",
        'post_type'   => 'enrollment',
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $enrollment_id ) ) {
        error_log( 'TB Registration: Failed to create Enrollment post — ' . $enrollment_id->get_error_message() );
        return false;
    }

    // Relationships — top-level post object fields, name works fine.
    update_field( 'season',                        $args['season_id'],                                           $enrollment_id );
    update_field( 'application',                   $args['application_id'],                                      $enrollment_id );
    update_field( 'family',                        $args['family_id'],                                           $enrollment_id );
    update_field( 'athlete',                       $args['athlete_id'],                                          $enrollment_id );
    
    // Top-level fields — name works fine.
    update_field( 'new_returning',                 $args['new_returning'],                                       $enrollment_id );
    update_field( 'eligibility_confirmed',         $args['eligibility_confirmed'] ? 1 : 0,                      $enrollment_id );
    update_field( 'submitted_by',                  $args['submitted_by'],                                        $enrollment_id );
    update_field( 'digital_signature',             $args['digital_signature'],                                   $enrollment_id );
    update_field( 'participation_type',            $args['participation_type'],                                   $enrollment_id );
    update_field( 'grade',                         $args['grade'],                                                $enrollment_id );
    
    // physical_status — written by key; if this is also blank after testing,
    // it is inside a group and needs the same array treatment (search group_tb_enrollment.json).
    update_field( 'field_69c9e3f08452e', [
        'physical_status'   => 'Not Received',
        'enrollment_status' => 'Pending',
    ], $enrollment_id );

    // Singlet group — must write parent group as array; sub-field key writes silently fail.
    update_field( 'field_69c9de8888649', [
        'singlet_requested'    => $args['singlet_requested'] === 'Yes' ? 1 : 0,
        'singlet_sizing_group' => $args['singlet_sizing_group'],
        'singlet_size'         => $args['singlet_size'],
        'singlet_status'       => $args['singlet_requested'] === 'Yes' ? 'Ordered' : 'Not Needed',
    ], $enrollment_id );
    
    return $enrollment_id;
}


// =============================================================================
// SECTION 2 — DISPATCH HOOK
// =============================================================================

/**
 * Route gform_after_submission to the correct handler based on Registration Settings.
 *
 * Form IDs are stored in the ACF options page (Registration Settings) so this
 * works correctly regardless of what IDs GF assigns after import.
 */
add_action( 'gform_after_submission', 'tb_handle_registration_submission', 10, 2 );
function tb_handle_registration_submission( $entry, $form ) {
    $new_family_form_id       = tb_reg_get_form_id( 'new_family' );
    $returning_family_form_id = tb_reg_get_form_id( 'returning_family' );

    if ( ! $new_family_form_id && ! $returning_family_form_id ) {
        return; // Registration Settings not yet configured — bail silently.
    }

    if ( $new_family_form_id && (int) $form['id'] === $new_family_form_id ) {
        tb_handle_new_family( $entry, $form );
    } elseif ( $returning_family_form_id && (int) $form['id'] === $returning_family_form_id ) {
        tb_handle_returning_family( $entry, $form );
    }
}


// =============================================================================
// SECTION 3 — NEW FAMILY HANDLER
// =============================================================================

/**
 * Handle submission of 2026 Registration — New Family (Form 12).
 *
 * Creates:
 *   - 1 Family post
 *   - 1 Application post
 *   - 1 Athlete post per nested athlete entry
 *   - 1 Enrollment post per nested athlete entry
 *
 * New Family form field reference:
 *   1   — Family Name
 *   52  – Family ID (hidden)
 *   2   — Season ID (hidden)
 *   3   — Current User ID (hidden)
 *   5   — Street Address
 *   6   — City
 *   7   — State
 *   9   — Primary First Name
 *   10  — Primary Last Name
 *   11  — Primary Relationship
 *   12  — Primary Email
 *   13  — Primary Phone
 *   15  — Secondary First Name
 *   16  — Secondary Last Name
 *   17  — Secondary Relationship
 *   18  — Secondary Email
 *   19  — Secondary Phone
 *   20  — Secondary Notifications
 *   23  — Register Athletes (nested form field → Form 11)
 *   31  — Digital Signature
 *   48  — Payment Method
 *
 * @param  array $entry  GF entry.
 * @param  array $form   GF form.
 */
function tb_handle_new_family( $entry, $form ) {
    $family_name = rgar( $entry, '1' );
    $family_unique_id = rgar( $entry, '52' );
    $season_id   = (int) rgar( $entry, '2' ) ?: (int) get_option( 'tb_active_season_id' );
    $user_id     = (int) rgar( $entry, '3' ) ?: get_current_user_id();
        
    // Guard: abort if CC payment failed — prevents orphaned posts.
    if ( rgar( $entry, '48' ) === 'Credit Card'
         && in_array( $entry['payment_status'] ?? '', [ 'Failed', 'Void' ] ) ) {
        error_log( 'TB Registration: New Family CC payment failed — aborting post creation. Entry ID: ' . $entry['id'] );
        return;
    }

    // -------------------------------------------------------------------------
    // 1. Create Family post
    // -------------------------------------------------------------------------
    $family_id = wp_insert_post( [
        'post_title'  => $family_name,
        'post_type'   => 'family',
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $family_id ) ) {
        error_log( 'TB Registration: Failed to create Family post — ' . $family_id->get_error_message() );
        return;
    }

    update_field( 'account_user',        $user_id,                           $family_id );
    update_field( 'family_status',       'Active',                           $family_id );
    update_field( 'family_display_name', $family_name,                       $family_id );
    update_field( 'family_id',           $family_unique_id,                  $family_id );
    update_field( 'street_address',      rgar( $entry, '5' ),                $family_id );
    update_field( 'city',                rgar( $entry, '6' ),                $family_id );
    update_field( 'state',               rgar( $entry, '7' ),                $family_id );
    update_field( 'zip_code',            rgar( $entry, '51' ),               $family_id );
    update_field( 'parents_guardians',   tb_build_guardians( $entry ),       $family_id );
    
    
    // Set reverse reference on the WP user per SCHEMA.md 3-way linkage rule.
    update_field( 'family',    $family_id,        'user_' . $user_id );
    update_field( 'family_id', $family_unique_id, 'user_' . $user_id );

    // -------------------------------------------------------------------------
    // 2. Create Application post
    // -------------------------------------------------------------------------
    $application_id = wp_insert_post( [
        'post_title'  => $family_name . ' — 2026 XC Registration',
        'post_type'   => 'application',
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $application_id ) ) {
        error_log( 'TB Registration: Failed to create Application post (New Family) — ' . $application_id->get_error_message() );
        return;
    }

    update_field( 'family',                $family_id,                      $application_id );
    update_field( 'season',                $season_id,                      $application_id );
    update_field( 'submission_date',       date( 'Y-m-d' ),                 $application_id );
    update_field( 'submitted_by',          $user_id,                        $application_id );
    update_field( 'new_returning',         'New',                           $application_id );
    update_field( 'application_status',    'Completed',                     $application_id );
    update_field( 'payment_status',        'Not Received',                  $application_id );
    // For Check/Cash, entry['payment_amount'] is 0. Read the Total field directly.
    $payment_amount = (float) ( $entry['payment_amount'] ?? 0 );
    if ( $payment_amount === 0.0 ) {
        foreach ( $form['fields'] as $field ) {
            if ( $field->type === 'total' ) {
                $payment_amount = (float) rgar( $entry, (string) $field->id );
                break;
            }
        }
    }
    update_field( 'payment_amount',        $payment_amount,                 $application_id );
    update_field( 'gravity_form_entry_id', $entry['id'],                    $application_id );

    update_field( 'digital_signature',     rgar( $entry, '31' ),            $application_id );
    update_field( 'payment_method',        rgar( $entry, '48' ),            $application_id );

    // -------------------------------------------------------------------------
    // 3. Process nested athlete entries (all are new athletes — Form 11)
    // -------------------------------------------------------------------------
    $nested_entries = tb_get_nested_entries( $entry, 23 );

    foreach ( $nested_entries as $nested_entry ) {
        $athlete_id = tb_create_athlete_post( $nested_entry, $family_id );
        if ( ! $athlete_id ) {
            continue;
        }

        // IDs group — must write via parent group key; sub-field key writes silently fail.
        update_field( 'field_69c9da2cbac29', [
            'family_id'    => $family_unique_id,
            'milesplit_id' => '',
        ], $athlete_id );

        tb_create_enrollment_post( [
            'athlete_id'            => $athlete_id,
            'family_id'             => $family_id,
            'application_id'        => $application_id,
            'season_id'             => $season_id,
            'new_returning'         => 'New Athlete',
            'eligibility_confirmed' => tb_new_athlete_eligibility_confirmed( $nested_entry ),
            'participation_type'    => rgar( $nested_entry, '1' ) ?: 'Athlete',
            'grade'                 => rgar( $nested_entry, '8' ),
            'singlet_requested'     => rgar( $nested_entry, '19' ),
            'singlet_sizing_group'  => rgar( $nested_entry, '20' ),
            'singlet_size'          => rgar( $nested_entry, '21' ),
            'submitted_by'          => $user_id,
            'digital_signature'     => rgar( $entry, '31' ),
        ] );
    }
}


// =============================================================================
// SECTION 4 — RETURNING FAMILY HANDLER
// =============================================================================

/**
 * Handle submission of 2026 Registration — Returning Family (Form 13).
 *
 * Updates:
 *   - Existing Family post (address + guardians only — display name NOT overwritten)
 * Creates:
 *   - 1 Application post
 *   - 1 Athlete post per new athlete (Form 11 nested entries only)
 *   - 1 Enrollment post per returning athlete (no new Athlete post)
 *   - 1 Enrollment post per new athlete
 *
 * Returning Family form field reference:
 *   60  — Family Post ID (hidden, GPPA-resolved anchor)
 *   1   — Family Name (read-only, NOT updated by hook)
 *   2   — Season ID (hidden)
 *   3   — Current User ID (hidden)
 *   5   — Street Address
 *   6   — City
 *   7   — State
 *   62  — Zip Code (TODO: verify 'zip_code' ACF field exists on Family CPT)
 *   9   — Primary First Name
 *   10  — Primary Last Name
 *   11  — Primary Relationship
 *   12  — Primary Email
 *   13  — Primary Phone
 *   15  — Secondary First Name
 *   16  — Secondary Last Name
 *   17  — Secondary Relationship
 *   18  — Secondary Email
 *   19  — Secondary Phone
 *   20  — Secondary Notifications
 *   24  — Register Returning Athletes (nested form field → Form 14)
 *   27  — Register New Athletes (nested form field → Form 11)
 *   35  — Digital Signature
 *   55  — Payment Method
 *
 * Returning Athlete nested form (Form 14) key fields:
 *   2   — Select Athlete (athlete post ID, GPPA-resolved)
 *   8   — Grade (updated on existing Athlete post)
 *   9   — Participation Type (updated on existing Athlete post)
 *   20  — Requesting New Singlet?
 *
 * @param  array $entry  GF entry.
 * @param  array $form   GF form.
 */
function tb_handle_returning_family( $entry, $form ) {
    $family_id = (int) rgar( $entry, '60' ); // GPPA-resolved Family post ID
    $season_id = (int) rgar( $entry, '2' ) ?: (int) get_option( 'tb_active_season_id' );
    $user_id   = (int) rgar( $entry, '3' ) ?: get_current_user_id();
        
    // Guard: abort if CC payment failed — prevents orphaned posts.
    if ( rgar( $entry, '55' ) === 'Credit Card'
         && in_array( $entry['payment_status'] ?? '', [ 'Failed', 'Void' ] ) ) {
        error_log( 'TB Registration: Returning Family CC payment failed — aborting post creation. Entry ID: ' . $entry['id'] );
        return;
    }

    if ( ! $family_id ) {
        error_log( 'TB Registration: Returning Family submission missing Family Post ID (field 60). Entry ID: ' . $entry['id'] );
        return;
    }

    // -------------------------------------------------------------------------
    // 1. Update Family post (address + guardians only — NOT family_display_name)
    // -------------------------------------------------------------------------
    update_field( 'street_address',    rgar( $entry, '5' ),          $family_id );
    update_field( 'city',              rgar( $entry, '6' ),          $family_id );
    update_field( 'state',             rgar( $entry, '7' ),          $family_id );
    update_field( 'zip_code',          rgar( $entry, '62' ),         $family_id );
    update_field( 'parents_guardians', tb_build_guardians( $entry ), $family_id );

    // -------------------------------------------------------------------------
    // 2. Create Application post
    // -------------------------------------------------------------------------
    $family_post  = get_post( $family_id );
    $family_title = $family_post ? $family_post->post_title : 'Family ' . $family_id;

    $application_id = wp_insert_post( [
        'post_title'  => $family_title . ' — 2026 XC Registration',
        'post_type'   => 'application',
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $application_id ) ) {
        error_log( 'TB Registration: Failed to create Application post (Returning Family) — ' . $application_id->get_error_message() );
        return;
    }

    update_field( 'family',                $family_id,                      $application_id );
    update_field( 'season',                $season_id,                      $application_id );
    update_field( 'submission_date',       date( 'Y-m-d' ),                 $application_id );
    update_field( 'submitted_by',          $user_id,                        $application_id );
    update_field( 'new_returning',         'Returning',                     $application_id );
    update_field( 'application_status',    'Completed',                     $application_id );
    update_field( 'payment_status',        'Not Received',                  $application_id );
    
    // For Check/Cash, entry['payment_amount'] is 0. Read the Total field directly.
    $payment_amount = (float) ( $entry['payment_amount'] ?? 0 );
    if ( $payment_amount === 0.0 ) {
        foreach ( $form['fields'] as $field ) {
            if ( $field->type === 'total' ) {
                $payment_amount = (float) rgar( $entry, (string) $field->id );
                break;
            }
        }
    }
    update_field( 'payment_amount', $payment_amount, $application_id );
    update_field( 'gravity_form_entry_id', $entry['id'],                    $application_id );

    update_field( 'digital_signature',     rgar( $entry, '35' ),            $application_id );
    update_field( 'payment_method',        rgar( $entry, '55' ),            $application_id );

    // -------------------------------------------------------------------------
    // 3. Process returning athlete entries (Form 14)
    //    — Athlete post already exists; do NOT create a new one.
    //    — Update grade and participation_type on the existing Athlete post.
    //    — Create Enrollment post only.
    // -------------------------------------------------------------------------
    $returning_entries = tb_get_nested_entries( $entry, 24 );

    foreach ( $returning_entries as $nested_entry ) {
        $athlete_id = (int) rgar( $nested_entry, '2' ); // GPPA-resolved athlete post ID

        if ( ! $athlete_id ) {
            error_log( 'TB Registration: Returning athlete nested entry missing athlete post ID (field 2). Skipping.' );
            continue;
        }

        // Update mutable fields on the existing Athlete post.
        update_field( 'grade',              rgar( $nested_entry, '8' ),           $athlete_id );
        update_field( 'participation_type', rgar( $nested_entry, '9' ) ?: 'Athlete', $athlete_id );

        tb_create_enrollment_post( [
            'athlete_id'            => $athlete_id,
            'family_id'             => $family_id,
            'application_id'        => $application_id,
            'season_id'             => $season_id,
            'new_returning'         => 'Returning Athlete',
            'eligibility_confirmed' => tb_returning_athlete_eligibility_confirmed( $nested_entry ),
            'participation_type'    => rgar( $nested_entry, '9' ) ?: 'Athlete',
            'grade'                 => rgar( $nested_entry, '8' ),
            'singlet_requested'     => rgar( $nested_entry, '20' ),
            'singlet_sizing_group'  => rgar( $nested_entry, '21' ),
            'singlet_size'          => rgar( $nested_entry, '22' ),
            'submitted_by'          => $user_id,
            'digital_signature'     => rgar( $entry, '35' ),
        ] );
    }

    // -------------------------------------------------------------------------
    // 4. Process new athlete entries on a returning family form (Form 11)
    //    — Same as new family athlete processing.
    // -------------------------------------------------------------------------
    $new_athlete_entries = tb_get_nested_entries( $entry, 27 );

    foreach ( $new_athlete_entries as $nested_entry ) {
        $athlete_id = tb_create_athlete_post( $nested_entry, $family_id );
        if ( ! $athlete_id ) {
            continue;
        }

        tb_create_enrollment_post( [
            'athlete_id'            => $athlete_id,
            'family_id'             => $family_id,
            'application_id'        => $application_id,
            'season_id'             => $season_id,
            'new_returning'         => 'New Athlete',
            'eligibility_confirmed' => tb_new_athlete_eligibility_confirmed( $nested_entry ),
            'participation_type'    => rgar( $nested_entry, '1' ) ?: 'Athlete',
            'grade'                 => rgar( $nested_entry, '8' ),
            'singlet_requested'     => rgar( $nested_entry, '19' ),
            'singlet_sizing_group'  => rgar( $nested_entry, '20' ),
            'singlet_size'          => rgar( $nested_entry, '21' ),
            'submitted_by'          => $user_id,
            'digital_signature'     => rgar( $entry, '35' ),
        ] );
    }
}


// =============================================================================
// SECTION 5 — STRIPE PAYMENT CONFIRMED HOOK (STUB)
// =============================================================================

/**
 * Update Application payment_status to 'Paid' when Stripe confirms a charge.
 *
 * TODO: Confirm the correct hook. Candidates:
 *   - gform_stripe_fulfillment          (fires when GF Stripe fulfillment runs)
 *   - gform_stripe_after_payment_intent_succeeded
 *
 * Uncomment and test once Stripe is connected on the live site.
 *
 * The hook should locate the Application post by gravity_form_entry_id
 * and update payment_status → 'Paid'.
 */
/*
add_action( 'gform_stripe_fulfillment', 'tb_handle_stripe_payment_confirmed', 10, 4 );
function tb_handle_stripe_payment_confirmed( $entry, $action, $previous_status, $current_status ) {
    if ( $current_status !== 'paid' ) {
        return;
    }

    $entry_id = $entry['id'] ?? 0;
    if ( ! $entry_id ) {
        return;
    }

    // Find the Application post for this GF entry.
    $applications = get_posts( [
        'post_type'      => 'application',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'   => 'gravity_form_entry_id',
                'value' => $entry_id,
            ],
        ],
    ] );

    if ( empty( $applications ) ) {
        error_log( "TB Registration: Stripe payment confirmed for entry $entry_id but no matching Application post found." );
        return;
    }

    update_field( 'payment_status', 'Paid', $applications[0]->ID );
}
*/