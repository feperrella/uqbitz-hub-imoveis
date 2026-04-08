<?php
/**
 * Plugin Name: UQBITZ Hub de Integracao Imobiliaria
 * Plugin URI:  https://github.com/feperrella/uqbitz-hub-imoveis
 * Description: Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).
 * Version:     3.4.1
 * Author:      Fernando Perrella (UQBITZ)
 * Author URI:  https://uqbitz.com
 * License:     GPL-2.0+
 * Text Domain: uqbitz-hub-imoveis
 * Requires at least: 6.5
 * Requires PHP: 8.0
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

/*
 * ACF DEPENDENCY CHECK — aceita ACF free ou ACF Pro.
 */
add_action( 'admin_init', 'uqbhi_check_acf_dependency' );

/**
 * Display admin notice if ACF is not installed.
 */
function uqbhi_check_acf_dependency() {
	if ( class_exists( 'ACF' ) ) {
		return;
	}
	add_action(
		'admin_notices',
		function () {
			$install_url = wp_nonce_url(
				admin_url( 'update.php?action=install-plugin&plugin=advanced-custom-fields' ),
				'install-plugin_advanced-custom-fields'
			);
			echo '<div class="notice notice-error"><p>';
			echo '<strong>UQBITZ Hub de Integração Imobiliária</strong> requer o plugin ';
			echo '<strong>Advanced Custom Fields</strong> (gratuito ou Pro) para funcionar. ';
			echo '<a href="' . esc_url( $install_url ) . '">Clique aqui para instalar o ACF gratuitamente</a>.';
			echo '</p></div>';
		}
	);
}

/*
 * CONSTANTES.
 */
define( 'UQBHI_VERSION', '3.4.1' );
define( 'UQBHI_FEED_SLUG', 'feed-imovelweb' );
define( 'UQBHI_BEARER_TOKEN', '259313f5-2c84-4f6c-bd2c-eabad2a8bc83' );
define( 'UQBHI_PATH', plugin_dir_path( __FILE__ ) );

/*
 * INCLUDES.
 */
require UQBHI_PATH . 'includes/cpt.php';
require UQBHI_PATH . 'includes/helpers.php';
require UQBHI_PATH . 'includes/feed.php';
require UQBHI_PATH . 'includes/acf-fields.php';

if ( is_admin() ) {
	require UQBHI_PATH . 'includes/admin.php';
}

/*
 * ACTIVATION / DEACTIVATION.
 */
register_activation_hook( __FILE__, 'uqbhi_activate' );

/**
 * Flush rewrite rules on plugin activation.
 */
function uqbhi_activate() {
	uqbhi_register_rewrite();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'uqbhi_deactivate' );

/**
 * Flush rewrite rules on plugin deactivation.
 */
function uqbhi_deactivate() {
	flush_rewrite_rules();
}
