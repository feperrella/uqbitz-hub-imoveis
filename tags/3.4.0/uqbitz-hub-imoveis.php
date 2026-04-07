<?php
/**
 * Plugin Name: UQBITZ Hub de Integracao Imobiliaria
 * Plugin URI:  https://github.com/feperrella/uqbitz-hub-imoveis
 * Description: Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).
 * Version:     3.4.0
 * Author:      Fernando Perrella (UQBITZ)
 * Author URI:  https://uqbitz.com
 * License:     GPL-2.0+
 * Text Domain: uqbitz-hub-imoveis
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: advanced-custom-fields
 */

defined( 'ABSPATH' ) || exit;

/* ──────────────────────────────────────────────
 * CONSTANTES
 * ────────────────────────────────────────────── */
define( 'UQBHI_VERSION', '3.4.0' );
define( 'UQBHI_FEED_SLUG', 'feed-imovelweb' );
define( 'UQBHI_BEARER_TOKEN', '259313f5-2c84-4f6c-bd2c-eabad2a8bc83' );
define( 'UQBHI_PATH', plugin_dir_path( __FILE__ ) );

/* ──────────────────────────────────────────────
 * INCLUDES
 * ────────────────────────────────────────────── */
require UQBHI_PATH . 'includes/cpt.php';
require UQBHI_PATH . 'includes/helpers.php';
require UQBHI_PATH . 'includes/feed.php';
require UQBHI_PATH . 'includes/acf-fields.php';

if ( is_admin() ) {
    require UQBHI_PATH . 'includes/admin.php';
}

/* ──────────────────────────────────────────────
 * ACTIVATION / DEACTIVATION
 * ────────────────────────────────────────────── */
register_activation_hook( __FILE__, 'uqbhi_activate' );
function uqbhi_activate() {
    uqbhi_register_rewrite();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'uqbhi_deactivate' );
function uqbhi_deactivate() {
    flush_rewrite_rules();
}
