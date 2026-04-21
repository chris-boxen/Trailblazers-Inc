<?php
/**
 * inc/registration-helpers.php
 * Registration system: season sync hook and shortcodes.
 *
 * Shortcodes:
 *   [tb_reg_hub]
 *       Hub page: two buttons with date-driven state and open/close sub-labels.
 *
 *   [tb_reg_form type="new_family"]
 *   [tb_reg_form type="returning_family"]
 *   [tb_reg_form type="physicals"]
 *       Renders the GF form for the given registration type, or a status
 *       message if registration is closed/coming soon or the form ID is unset.
 *
 *   [tb_reg_confirmation type="new_family"]
 *   [tb_reg_confirmation type="returning_family"]
 *   [tb_reg_confirmation type="physicals"]
 *       Renders the confirmation WYSIWYG content for the given type.
 *
 * Accepted type values: new_family | returning_family | physicals
 */


// ---------------------------------------------------------------------------
// 1. Sync tb_active_season_id when reg_active_season is saved on options page
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', function( $post_id ) {
    if ( $post_id !== 'options' ) return;

    $season = get_field( 'reg_active_season', 'option' );
    if ( ! $season ) return;

    // get_field with Post Object return type "object" gives a WP_Post
    $season_id = is_object( $season ) ? $season->ID : (int) $season;
    update_option( 'tb_active_season_id', $season_id );
}, 20 ); // Priority 20 — after ACF writes the option fields


// ---------------------------------------------------------------------------
// 2. Helper: resolve date-driven button state
// ---------------------------------------------------------------------------

/**
 * Returns the state of a registration button given open/close datetimes.
 *
 * @param string|null $open   Datetime string (Y-m-d H:i:s) or null if unset
 * @param string|null $close  Datetime string (Y-m-d H:i:s) or null if unset
 * @return string  'enabled' | 'pending' | 'closed'
 */
function tb_reg_button_state( $open, $close ) {
    $now = current_time( 'timestamp' ); // Respects WP configured timezone

    if ( $close && $now >= strtotime( $close ) ) return 'closed';
    if ( ! $open || $now < strtotime( $open ) )  return 'pending';
    return 'enabled';
}

/**
 * Returns the sub-label displayed beneath a registration button.
 *
 * @param string      $state  Output of tb_reg_button_state()
 * @param string|null $open   Open datetime string
 * @param string|null $close  Close datetime string
 * @return string             Human-readable label, or empty string
 */
function tb_reg_date_label( $state, $open, $close ) {
    switch ( $state ) {
        case 'closed':
            return 'Registration is closed.';
        case 'pending':
            return $open ? 'Opens ' . date( 'F j \a\t g:i a', strtotime( $open ) ) : '';
        case 'enabled':
            return $close ? 'Closes ' . date( 'F j', strtotime( $close ) ) : '';
    }
    return '';
}


// ---------------------------------------------------------------------------
// 3. Shortcode: [tb_reg_hub]
// ---------------------------------------------------------------------------

add_shortcode( 'tb_reg_hub', function() {
    $status   = get_field( 'reg_status', 'option' );
    $ret_open = get_field( 'reg_returning_open', 'option' );
    $new_open = get_field( 'reg_new_family_open', 'option' );
    $close    = get_field( 'reg_close', 'option' );

    // Manual override: coming_soon — trumps all date logic
    if ( $status === 'coming_soon' ) {
        $msg = get_field( 'reg_coming_soon_message', 'option' );
        return $msg ? wpautop( $msg ) : '<p>Registration is not yet open.</p>';
    }

    // Manual override: closed — trumps all date logic
    if ( $status === 'closed' ) {
        $msg = get_field( 'reg_closed_message', 'option' );
        return $msg ? wpautop( $msg ) : '<p>Registration is closed.</p>';
    }

    // Date-driven states (only reached when reg_status === 'open')
    $ret_state = tb_reg_button_state( $ret_open, $close );
    $new_state = tb_reg_button_state( $new_open, $close );

    $ret_sub = tb_reg_date_label( $ret_state, $ret_open, $close );
    $new_sub = tb_reg_date_label( $new_state, $new_open, $close );

    // Permanent slugs — these pages are never recreated or renamed
    $ret_url = home_url( '/registration/returning-families/' );
    $new_url = home_url( '/registration/new-families/' );

    ob_start(); ?>
    <div class="tb-reg-hub">

        <div class="tb-reg-hub__option">
            <a href="<?= esc_url( $ret_url ) ?>"
               class="tb-reg-btn<?= $ret_state === 'enabled' ? '' : ' tb-reg-btn--disabled' ?>"
               <?= $ret_state !== 'enabled' ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                Returning Family Registration
            </a>
            <?php if ( $ret_sub ) : ?>
                <p class="tb-reg-hub__date"><?= esc_html( $ret_sub ) ?></p>
            <?php endif; ?>
        </div>

        <div class="tb-reg-hub__option">
            <a href="<?= esc_url( $new_url ) ?>"
               class="tb-reg-btn<?= $new_state === 'enabled' ? '' : ' tb-reg-btn--disabled' ?>"
               <?= $new_state !== 'enabled' ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                New Family Registration
            </a>
            <?php if ( $new_sub ) : ?>
                <p class="tb-reg-hub__date"><?= esc_html( $new_sub ) ?></p>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
} );


// ---------------------------------------------------------------------------
// 4. Shortcode: [tb_reg_form type="..."]
// ---------------------------------------------------------------------------

add_shortcode( 'tb_reg_form', function( $atts ) {
    $atts = shortcode_atts( [ 'type' => 'new_family' ], $atts );

    $status = get_field( 'reg_status', 'option' );

    if ( $status === 'closed' ) {
        $msg = get_field( 'reg_closed_message', 'option' );
        return $msg ? wpautop( $msg ) : '<p>Registration is closed.</p>';
    }

    if ( $status === 'coming_soon' ) {
        $msg = get_field( 'reg_coming_soon_message', 'option' );
        return $msg ? wpautop( $msg ) : '<p>Registration is not yet open.</p>';
    }

    $field_map = [
        'new_family'       => 'reg_new_family_form_id',
        'returning_family' => 'reg_returning_family_form_id',
        'physicals'        => 'reg_physicals_form_id',
    ];

    if ( ! isset( $field_map[ $atts['type'] ] ) ) return '';

    $form_id = (int) get_field( $field_map[ $atts['type'] ], 'option' );

    if ( ! $form_id ) return '<p>This form is not yet available.</p>';

    return do_shortcode( "[gravityforms id='{$form_id}']" );
} );


// ---------------------------------------------------------------------------
// 5. Shortcode: [tb_reg_confirmation type="..."]
// ---------------------------------------------------------------------------

add_shortcode( 'tb_reg_confirmation', function( $atts ) {
    $atts = shortcode_atts( [ 'type' => 'new_family' ], $atts );

    $field_map = [
        'new_family'       => 'reg_new_family_confirmation',
        'returning_family' => 'reg_returning_family_confirmation',
        'physicals'        => 'reg_physicals_confirmation',
    ];

    if ( ! isset( $field_map[ $atts['type'] ] ) ) return '';

    $content = get_field( $field_map[ $atts['type'] ], 'option' );
    return $content ? wpautop( $content ) : '';
} );
