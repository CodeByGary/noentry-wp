<?php

/**
 * Plugin Name: NoEntryWP: Admin Page Access Control
 * Plugin URI: https://noentrywp.codebygary.org
 * Description: Restrict access to specific admin pages for selected users individually — no need to rely on roles or capabilities.
 * Version: 1.0
 * Author: Hakobyan Garegin (@CodeByGary)
 * Author URI: https://github.com/CodeByGary
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: noentry-wp
 */



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('NOENTRYWP_VERSION', '1');
define('NOENTRY_MINIMUM_WP_VERSION', '5.3');


// Load plugin textdomain for translations (used by WordPress.org translation system)
add_action('plugins_loaded', function () {
    load_plugin_textdomain('noentry-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
});


// Enqueue styles and scripts only on the plugin settings page
add_action('admin_enqueue_scripts', function ($hook) {

    // Load assets only on this plugin's settings page
    if ($hook === 'settings_page_noentry-wp') {

        // Plugin admin stylesheet
        wp_enqueue_style(
            'noentry-wp-admin-style',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            NOENTRYWP_VERSION,
            'all'
        );

        // Plugin admin JavaScript
        wp_enqueue_script(
            'noentry-wp-admin-script',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery', 'wp-i18n'], // include wp-i18n
            NOENTRYWP_VERSION,
            true
        );

        // Prepare translations
        wp_set_script_translations('noentry-wp-admin-script', 'noentry-wp', plugin_dir_path(__FILE__) . 'languages');

        // jQuery UI Accordion (used for user rule sections)
        wp_enqueue_script('jquery-ui-accordion');

        // jQuery UI CSS theme
        wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'assets/jquery-ui.min.css', [], '1.13.3', 'all');
    }
});



// Register plugin settings page under the "Settings" admin menu
add_action('admin_menu', function () {
    add_options_page(
        esc_html__('User Page Access Control', 'noentry-wp'), // Page title
        esc_html__('User Page Access Control', 'noentry-wp'), // Menu title
        'manage_options',                                                   // Capability required to access
        'noentry-wp',                             // Menu slug
        'noentry_wp_render_settings_page'                                 // Callback function to render the page
    );
});





// Register plugin settings and enforce access restrictions based on user rules
add_action('admin_init', function () {

    add_filter('plugin_action_links_noentry-wp/noentry-wp.php', function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=noentry-wp')) . '">' . __('Settings', 'noentry-wp') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

    // Register a settings option for user access rules with sanitization and default value
    register_setting('noentry-wp_settings_group', 'noentry-wp_user_rules', [
        'type' => 'object',
        'sanitize_callback' => 'noentry_wp_sanitize_user_rules',
        'default' => [],
        'show_in_rest' => false,
    ]);

    $current_url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : NULL;


    // Exit early if not in admin area
    if (!is_admin() || !$current_url) {
        return;
    }

    // Get current user and current requested URL path
    $current_user = wp_get_current_user();



    $user_id = $current_user->ID;

    // Fetch access rules from options
    $rules = get_option('noentry-wp_user_rules', []);

    // If no rules defined for current user, do nothing
    if (empty($rules[$user_id])) {
        return;
    }

    // Iterate over each rule and apply matching logic
    foreach ($rules[$user_id] as $rule) {
        $type  = $rule['type'] ?? '';
        $value = $rule['value'] ?? '';

        // Skip invalid or empty rules
        if (!$type || !$value) continue;

        // Check current URL against rule type and block access if matched
        switch ($type) {
            case 'contains':
                if (stripos($current_url, $value) !== false) {
                    noentry_wp_block_access();
                }
                break;

            case 'equals':
                if ($current_url === $value) {
                    noentry_wp_block_access();
                }
                break;

            case 'starts_with':
                if (stripos($current_url, $value) === 0) {
                    noentry_wp_block_access();
                }
                break;

            case 'regex':
                // Suppress warnings with @; run preg_match twice to confirm
                if (@preg_match($value, $current_url)) {
                    if (preg_match($value, $current_url)) {
                        noentry_wp_block_access();
                    }
                }
                break;
        }
    }
});



/**
 * Terminate page load with an access denied message and a link to the dashboard.
 * 
 * Uses wp_die() to display a user-friendly error with HTTP 403 status.
 */
function noentry_wp_block_access()
{


    // Display access denied message with a "Return to Dashboard" button
    wp_die(
        sprintf(
            '<p>%s</p><p><a href="%s" class="button">%s</a></p>',
            esc_html__('You are not allowed to access this page.', 'noentry-wp'),
            esc_url(admin_url()), // Get sanitized admin dashboard URL
            esc_html__('Return to Dashboard', 'noentry-wp')
        ),
        esc_html__('Access Denied', 'noentry-wp'),
        ['response' => 403]
    );
}


/**
 * Sanitizes and validates user rule input from settings form.
 *
 * @param array $input Raw input from settings form.
 * @return array Cleaned and validated user rules.
 */
function noentry_wp_sanitize_user_rules($input)
{
    $output = [];

    foreach ($input as $user_id => $rules) {
        $user_id = absint($user_id);
        if (!get_userdata($user_id)) continue;

        $output[$user_id] = [];

        foreach ($rules as $rule) {
            $type_raw  = $rule['type'] ?? '';
            $value_raw = $rule['value'] ?? '';

            // Sanitize input
            $type = sanitize_text_field($type_raw);
            $value = sanitize_text_field($value_raw);

            // Validate rule type and value
            if (in_array($type, ['contains', 'equals', 'starts_with', 'regex']) && $value !== '') {
                // Normalize full URL to relative path
                $normalized_value = noentry_wp_extract_path($value);

                $output[$user_id][] = [
                    'type' => $type,
                    'value' => $normalized_value
                ];
            }
        }
    }

    return $output;
}



/**
 * Get a formatted user display name string.
 *
 * Shows display name, optionally adds login if different,
 * and appends user ID for clarity.
 *
 * @param WP_User $user User object.
 * @return string Formatted user name.
 */
function noentry_wp_user_display_name($user)
{
    $name = $user->display_name;

    // Append login name if different from display name
    if ($user->display_name !== $user->user_login) {
        $name .= ', ' . $user->user_login;
    }

    // Append user ID for unambiguous identification
    $name .= " (ID: {$user->ID})";

    return $name;
}

/**
 * Extracts the path and query part from a full URL or returns the input as-is if it's already a relative path.
 *
 * @param string $url_or_path Full URL or relative path.
 * @return string Extracted path and query string.
 */
function noentry_wp_extract_path($url_or_path)
{
    // If the input is a valid URL, parse and return the path with query string.
    if (false !== filter_var($url_or_path, FILTER_VALIDATE_URL)) {
        $parts = wp_parse_url($url_or_path);
        if (!$parts) {
            return $url_or_path; // Fallback if URL can't be parsed
        }
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        return $path . $query;
    }

    // Return the input directly if it's not a full URL
    return $url_or_path;
}


/**
 * Render the plugin settings page with user access rules editor.
 *
 * Displays an accordion of users where each user can have multiple URL access rules.
 * Supports adding/removing rules dynamically via JavaScript.
 */
function noentry_wp_render_settings_page()
{
    // Retrieve saved user rules from options
    $user_rules = get_option('noentry-wp_user_rules', []);
    // Get all users for listing
    $users = get_users();
?>
    <div class="wrap" id="noentry-wp">
        <h1><?php esc_html_e('Admin Access Control', 'noentry-wp'); ?></h1>

        <div class="notice notice-info" style="margin-top: 20px; padding: 15px;">
            <h2 style="margin-top: 0;"><?php echo esc_html__('🛠 Instructions', 'noentry-wp'); ?></h2>
            <p><?php esc_html_e('To restrict access to specific WordPress admin pages for selected users, follow these steps:', 'noentry-wp'); ?></p>
            <ol style="padding-left: 20px; list-style: decimal;">
                <li><?php esc_html_e('Find the user in the list below. Each accordion section represents a user account.', 'noentry-wp'); ?></li>
                <li><?php esc_html_e('For each user, add one or more', 'noentry-wp'); ?> <strong><?php esc_html_e('URL match rules', 'noentry-wp'); ?></strong> <?php esc_html_e('by selecting a match type and entering the page path or pattern.', 'noentry-wp'); ?></li>
                <li><?php esc_html_e('You can add multiple rules per user. A user will be blocked if any rule matches the current admin page URL.', 'noentry-wp'); ?></li>
            </ol>

            <p><strong><?php esc_html_e('Match types available:', 'noentry-wp'); ?></strong></p>
            <ul style="padding-left: 20px; list-style: disc;">
                <li><strong><?php esc_html_e('Contains', 'noentry-wp'); ?></strong> – <?php esc_html_e('Blocks if the URL contains the given string', 'noentry-wp'); ?></li>
                <li><strong><?php esc_html_e('Equals', 'noentry-wp'); ?></strong> – <?php esc_html_e('Blocks if the URL exactly matches the string', 'noentry-wp'); ?></li>
                <li><strong><?php esc_html_e('Starts with', 'noentry-wp'); ?></strong> – <?php esc_html_e('Blocks if the URL starts with the given string', 'noentry-wp'); ?></li>
                <li><strong><?php esc_html_e('Regular expression', 'noentry-wp'); ?></strong> – <?php esc_html_e('Blocks if the URL matches the regex pattern', 'noentry-wp'); ?></li>
            </ul>

            <p><?php esc_html_e('Click the', 'noentry-wp'); ?> <strong>“+”</strong> <?php esc_html_e('button to add a new rule, or the', 'noentry-wp'); ?> <strong>“–”</strong> <?php esc_html_e('button to remove a rule.', 'noentry-wp'); ?></p>
            <p><?php esc_html_e('Changes are saved automatically when you click the', 'noentry-wp'); ?> <strong><?php esc_html_e('Save Changes', 'noentry-wp'); ?></strong> <?php esc_html_e('button at the bottom.', 'noentry-wp'); ?></p>

            <p class="noentry-wp-coffee">
                <?php
                /* Translators: Link to support plugin development */
                echo sprintf(
                    /* translators:  Buy Me a Coffee link text */
                    esc_html__('If you find this plugin helpful, consider supporting its development: %s', 'noentry-wp'),
                    '<a href="https://buymeacoffee.com/codebygary" target="_blank" rel="noopener noreferrer">Buy Me a Coffee</a>'
                );
                ?>
            </p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('noentry-wp_settings_group'); ?>

            <div id="noentry-wp-accordion">
                <?php foreach ($users as $user):
                    $uid = $user->ID;
                    $rules = $user_rules[$uid] ?? [];
                ?>
                    <h3><?php echo esc_html(noentry_wp_user_display_name($user)); ?></h3>
                    <div>
                        <div class="noentry-wp-rules" data-user="<?php echo esc_attr($uid); ?>">
                            <?php foreach ($rules as $index => $rule): ?>
                                <div class="noentry-wp-rule-row">
                                    <select name="noentry-wp_user_rules[<?php echo esc_attr($uid); ?>][<?php echo esc_attr($index); ?>][type]">
                                        <?php foreach (['contains', 'equals', 'starts_with', 'regex'] as $type): ?>
                                            <option value="<?php echo esc_attr($type); ?>" <?php selected($rule['type'], $type); ?>>
                                                <?php echo esc_html(str_replace('_', ' ', ucfirst($type))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input
                                        type="text"
                                        name="noentry-wp_user_rules[<?php echo esc_attr($uid); ?>][<?php echo esc_attr($index); ?>][value]"
                                        value="<?php echo esc_attr($rule['value']); ?>" />
                                    <button type="button" class="button noentry-wp-remove-row" aria-label="<?php esc_attr('Remove rule', 'noentry-wp'); ?>">−</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button
                            type="button"
                            class="button noentry-wp-add-row"
                            data-user="<?php echo esc_attr($uid); ?>"
                            aria-label="<?php esc_attr('Add rule for user', 'noentry-wp'); ?>">
                            + <?php esc_html_e('Add Rule', 'noentry-wp'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="submit">
                <button type="submit" class="button-primary"><?php esc_html_e('Save Changes', 'noentry-wp'); ?></button>
            </p>
        </form>
    </div>
<?php
}
