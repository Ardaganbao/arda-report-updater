<?php
/**
 * Plugin Name: WP Arda-Report Updater
 * Plugin URI:  https://github.com/Ardaganbao/mose-wordpress
 * Description: Runs the GitHub update checker for WP Arda-Report from the network, so the "Check for updates" link and automatic updates work in Network Admin without network-activating WP Arda-Report itself. Network-activate ONLY this plugin; keep WP Arda-Report activated per site.
 * Version:     1.1.0
 * Author:      Damien Grassone
 * License:     GPL 3.0
 * Network:     true
 * Update URI:  https://github.com/Ardaganbao/arda-report-updater
 *
 * Why this exists: on WordPress multisite, the Network Admin screens only run
 * network-activated (and must-use) plugins. WP Arda-Report's own update checker
 * therefore never runs there when the plugin is activated per-site, so no update
 * link or auto-update appears. This tiny network-activated plugin loads the same
 * checker against WP Arda-Report's files, wherever it is installed.
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Find the installed "WP Arda-Report" plugin, whatever its folder is named.
 * Returns the plugin file relative to the plugins directory (e.g. "multi-block/multi-block.php"), or ''.
 */
function arda_report_updater_locate_plugin() {
    if ( ! function_exists('get_plugins') ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    foreach ( get_plugins() as $pluginFile => $data ) {
        $textDomain = isset($data['TextDomain']) ? $data['TextDomain'] : '';
        $updateUri  = isset($data['UpdateURI']) ? $data['UpdateURI'] : '';
        $name       = isset($data['Name']) ? $data['Name'] : '';
        if (
            $textDomain === 'multi-block'
            || ( $updateUri !== '' && strpos($updateUri, 'Ardaganbao/mose-wordpress') !== false )
            || $name === 'WP Arda-Report'
        ) {
            return $pluginFile;
        }
    }
    return '';
}

function arda_report_updater_boot() {
    // Locate WP Arda-Report and borrow its bundled plugin-update-checker (this companion ships none of
    // its own to stay tiny; it is useless without the main plugin anyway).
    $pluginFile = arda_report_updater_locate_plugin();
    if ( $pluginFile === '' ) {
        add_action('network_admin_notices', 'arda_report_updater_missing_notice');
        return;
    }

    $pluginDir = WP_PLUGIN_DIR . '/' . dirname($pluginFile);
    $pucLoader = $pluginDir . '/plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists($pucLoader) ) {
        add_action('network_admin_notices', 'arda_report_updater_missing_notice');
        return;
    }

    require_once $pucLoader;

    // Self-update: this companion updates itself from its OWN GitHub repo under its OWN slug
    // ('arda-report-updater'). That slug never collides with the main plugin's 'multi-block', so it is
    // safe to register in every context. The puc_is_slug_in_use filter keeps it idempotent.
    if ( ! apply_filters('puc_is_slug_in_use-arda-report-updater', false) ) {
        $self = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/Ardaganbao/arda-report-updater/',
            __FILE__,
            'arda-report-updater'
        );
        $self->getVcsApi()->enableReleaseAssets('/arda-report-updater\.zip($|[?&#])/i');
    }

    // Manage the MAIN plugin's updates (slug 'multi-block') from Network Admin only. Skip when the main
    // plugin is loaded in THIS request (front end, per-site admin, per-site cron): it has already
    // registered that slug at file-include time, and a second registration makes PUC call
    // trigger_error(E_USER_ERROR) ("Slugs must be unique") and fatals the whole request. Guarded twice:
    // by the main plugin's own constant and by PUC's duplicate-detection filter.
    if ( defined('ARDAREPORT_POST_TYPE') ) {
        return;
    }
    if ( apply_filters('puc_is_slug_in_use-multi-block', false) ) {
        return;
    }

    $checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Ardaganbao/mose-wordpress/',
        WP_PLUGIN_DIR . '/' . $pluginFile,
        'multi-block'
    );
    $checker->getVcsApi()->enableReleaseAssets('/multi-block\.zip($|[?&#])/i');
}
add_action('plugins_loaded', 'arda_report_updater_boot');

function arda_report_updater_missing_notice() {
    echo '<div class="notice notice-warning"><p><strong>WP Arda-Report Updater:</strong> the WP Arda-Report plugin (with its plugin-update-checker folder) was not found. Install WP Arda-Report first, then updates will be managed from here.</p></div>';
}
