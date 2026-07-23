<?php
/**
 * Elementor dynamic tags bridge for imóvel gallery fields.
 *
 * Exposes the native metabox galleries as Elementor Dynamic Tags so editors
 * can pick them from the variable selector without ACF Pro.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

add_action( 'elementor/loaded', 'uqbhi_bootstrap_elementor_dynamic_tags' );

if ( did_action( 'elementor/loaded' ) ) {
	uqbhi_bootstrap_elementor_dynamic_tags();
}

/**
 * Bootstrap Elementor dynamic tag registration once Elementor is loaded.
 *
 * @return void
 */
function uqbhi_bootstrap_elementor_dynamic_tags() {
	add_action( 'elementor/dynamic_tags/register', 'uqbhi_register_elementor_dynamic_tags' );
}

/**
 * Register Elementor dynamic tags used by Hub Imóveis.
 *
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function uqbhi_register_elementor_dynamic_tags( $dynamic_tags_manager ) {
	if ( method_exists( $dynamic_tags_manager, 'register_group' ) ) {
		$dynamic_tags_manager->register_group(
			'uqbhi-imoveis',
			array(
				'title' => esc_html__( 'Hub Imóveis', 'uqbitz-hub-imoveis' ),
			)
		);
	}

	require_once __DIR__ . '/elementor-dynamic-tags/gallery-imovel-tag.php';
	$dynamic_tags_manager->register( new \UQBHI_Elementor_Dynamic_Tag_Galeria_Imovel() );
}
