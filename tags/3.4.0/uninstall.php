<?php
/**
 * Uninstall UQBITZ Hub de Integração Imobiliária
 *
 * Fired when the plugin is uninstalled.
 *
 * @package UQBITZ_Hub_Imoveis
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Remove plugin options
delete_option( 'uqbhi_settings' );

// Note: We intentionally do NOT remove:
// - Custom post type data (imóveis)
// - Taxonomy terms (tipos, finalidades, cidades)
// - ACF field data
// These are user content and should be preserved.
