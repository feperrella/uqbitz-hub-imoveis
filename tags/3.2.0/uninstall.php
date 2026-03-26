<?php
/**
 * Uninstall Portal Imóveis
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Portal_Imoveis
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Remove plugin options
delete_option( 'ptim_settings' );

// Note: We intentionally do NOT remove:
// - Custom post type data (imóveis)
// - Taxonomy terms (tipos, finalidades, cidades)
// - ACF field data
// These are user content and should be preserved.
