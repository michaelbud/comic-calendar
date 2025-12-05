<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the setting to store the "Comic Page ID".
 */
add_action( 'admin_init', function() {
    register_setting( 'cc_options_group', 'cc_comic_page_id', array(
        'sanitize_callback' => 'absint' // Sanitize as integer
    ));

    register_setting( 'cc_options_group', 'cc_calendar_title', array(
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    // NEW: Check if we need to flush rewrites (set by the update hook below)
    if ( get_transient( 'cc_flush_rewrites' ) ) {
        flush_rewrite_rules();
        delete_transient( 'cc_flush_rewrites' );
    }
});

/**
 * ACTION: Trigger a flush whenever the setting is updated.
 * This runs when you click "Save Changes" on the settings page.
 */
add_action( 'update_option_cc_comic_page_id', function( $old_val, $new_val ) {
    // We set a transient flag to flush rules on the NEXT load.
    // This ensures the new Page ID is fully saved and available 
    // when cc_register_rewrites() runs again.
    set_transient( 'cc_flush_rewrites', true );
}, 10, 2 );

/**
 * Add the menu item.
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=cc_comic',
        'Comic Calendar Settings',
        'Settings',
        'manage_options',
        'cc-settings',
        'cc_render_settings_page'
    );
});

/**
 * Render the settings form.
 */
function cc_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Comic Calendar Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'cc_options_group' ); ?>
            <?php do_settings_sections( 'cc_options_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Comic Display Page</th>
                    <td>
                        <?php
                        wp_dropdown_pages( array(
                            'name'             => 'cc_comic_page_id',
                            'selected'         => get_option('cc_comic_page_id'),
                            'show_option_none' => '-- Select the page holding the Shortcode --',
                        ) );
                        ?>
                        <p class="description">
                            Select the page where you have placed the <code>[comic_calendar]</code> shortcode.<br>
                            <strong>Note:</strong> Clicking "Save Changes" will automatically refresh your permalinks.
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Calendar Title</th>
                    <td>
                        <input type="text" name="cc_calendar_title" value="<?php echo esc_attr( get_option( 'cc_calendar_title', 'COMIC CALENDAR' ) ); ?>" class="regular-text" />
                        <p class="description">Text shown above the month navigation (defaults to "COMIC CALENDAR").</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}