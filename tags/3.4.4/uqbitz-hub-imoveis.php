<?php
/**
 * Plugin Name: UQBITZ Hub de Integracao Imobiliaria
 * Plugin URI:  https://github.com/feperrella/uqbitz-hub-imoveis
 * Description: Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).
 * Version:     3.4.4
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
 * DEFAULT TERMS SEED — tipos e finalidades oficiais.
 */
add_action( 'admin_init', 'uqbhi_maybe_seed_default_terms' );

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
define( 'UQBHI_VERSION', '3.4.4' );
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
	require UQBHI_PATH . 'includes/gallery-fallback.php';
}

/*
 * ACTIVATION / DEACTIVATION.
 */
register_activation_hook( __FILE__, 'uqbhi_activate' );

/**
 * Flush rewrite rules on plugin activation.
 */
function uqbhi_activate() {
	uqbhi_register_post_type_and_taxonomies();
	uqbhi_seed_default_terms();
	uqbhi_migrate_legacy_tipo_meta();
	update_option( 'uqbhi_seed_version', UQBHI_VERSION, false );
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

/**
 * Seed the default terms once per plugin version.
 */
function uqbhi_maybe_seed_default_terms() {
	if ( get_option( 'uqbhi_seed_version' ) === UQBHI_VERSION ) {
		return;
	}

	uqbhi_seed_default_terms();
	uqbhi_migrate_legacy_tipo_meta();
	update_option( 'uqbhi_seed_version', UQBHI_VERSION, false );
}

/**
 * Backfill OpenNavent meta on custom uqbhi_tipo terms created before 3.4.3.
 *
 * The pre-3.4.3 helper resolved OpenNavent IDs via a slug substring match
 * (e.g. "cobertura-duplex" matched "cobertura"). This one-time migration
 * replicates that match against the same keyword set and persists the result
 * as term meta, so existing installs keep emitting the same IDs.
 *
 * Runs once per install, guarded by the `uqbhi_legacy_tipo_migrated` option.
 * Terms that already have `uqbhi_id_tipo` are left untouched.
 */
function uqbhi_migrate_legacy_tipo_meta() {
	if ( get_option( 'uqbhi_legacy_tipo_migrated' ) ) {
		return;
	}

	// Keywords ordered specific → generic (matches the legacy iteration order).
	$legacy_keywords = array(
		'casa-condominio'    => array( '1', '6' ),
		'casa-de-vila'       => array( '1', '7' ),
		'casa-comercial'     => array( '1005', '17' ),
		'quarto-casa'        => array( '1', '37' ),
		'quarto-apt'         => array( '2', '36' ),
		'conjunto-comercial' => array( '1005', '16' ),
		'predio-inteiro'     => array( '1005', '15' ),
		'box-garagem'        => array( '1005', '14' ),
		'loja-shopping'      => array( '1005', '18' ),
		'loja-salao'         => array( '1005', '19' ),
		'area-industrial'    => array( '1005', '27' ),
		'area-comercial'     => array( '1005', '32' ),
		'andar-corrido'      => array( '1005', '30' ),
		'ponto-comercial'    => array( '1005', '31' ),
		'sobrado'            => array( '1', '33' ),
		'cobertura'          => array( '2', '26' ),
		'studio'             => array( '2', '2' ),
		'loft'               => array( '2', '3' ),
		'flat'               => array( '2', '4' ),
		'duplex'             => array( '2', '34' ),
		'triplex'            => array( '2', '35' ),
		'garden'             => array( '2', '38' ),
		'loteamento'         => array( '1003', '9' ),
		'chacara'            => array( '1004', '10' ),
		'sitio'              => array( '1004', '11' ),
		'fazenda'            => array( '1004', '12' ),
		'haras'              => array( '1004', '13' ),
		'galpao'             => array( '1005', '20' ),
		'hotel'              => array( '1005', '22' ),
		'motel'              => array( '1005', '23' ),
		'pousada'            => array( '1005', '24' ),
		'industria'          => array( '1005', '25' ),
		'consultorio'        => array( '1005', '28' ),
		'clinica'            => array( '1005', '29' ),
		'apartamento'        => array( '2', '1' ),
		'terreno'            => array( '1003', '8' ),
		'rural'              => array( '1004', '10' ),
		'comercial'          => array( '1005', '16' ),
		'casa'               => array( '1', '5' ),
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'uqbhi_tipo',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		update_option( 'uqbhi_legacy_tipo_migrated', 1, false );
		return;
	}

	foreach ( $terms as $term ) {
		if ( '' !== get_term_meta( $term->term_id, 'uqbhi_id_tipo', true ) ) {
			continue;
		}
		foreach ( $legacy_keywords as $keyword => $ids ) {
			if ( strpos( $term->slug, $keyword ) !== false ) {
				update_term_meta( $term->term_id, 'uqbhi_id_tipo', $ids[0] );
				update_term_meta( $term->term_id, 'uqbhi_id_subtipo', $ids[1] );
				break;
			}
		}
	}

	update_option( 'uqbhi_legacy_tipo_migrated', 1, false );
}

/**
 * Seed the official type and purpose taxonomies.
 *
 * Term `name` is the Portuguese public label. `meta` carries the OpenNavent
 * API values (numeric idTipo/idSubTipo for uqbhi_tipo, operation code for
 * uqbhi_finalidade) plus Spanish and English translations.
 */
function uqbhi_seed_default_terms() {
	$seed = array(
		'uqbhi_tipo'       => array(
			array(
				'name'     => 'Casa',
				'slug'     => 'casa',
				'meta'     => array(
					'uqbhi_id_tipo'    => '1',
					'uqbhi_id_subtipo' => '5',
					'uqbhi_name_es'    => 'Casa',
					'uqbhi_name_en'    => 'House',
				),
				'children' => array(
					array(
						'name' => 'Casa de Condomínio',
						'slug' => 'casa-condominio',
						'meta' => array(
							'uqbhi_id_tipo'    => '1',
							'uqbhi_id_subtipo' => '6',
							'uqbhi_name_es'    => 'Casa en Condominio',
							'uqbhi_name_en'    => 'Gated Community House',
						),
					),
					array(
						'name' => 'Casa de Vila',
						'slug' => 'casa-de-vila',
						'meta' => array(
							'uqbhi_id_tipo'    => '1',
							'uqbhi_id_subtipo' => '7',
							'uqbhi_name_es'    => 'Casa de Pueblo',
							'uqbhi_name_en'    => 'Townhouse',
						),
					),
					array(
						'name' => 'Sobrado',
						'slug' => 'sobrado',
						'meta' => array(
							'uqbhi_id_tipo'    => '1',
							'uqbhi_id_subtipo' => '33',
							'uqbhi_name_es'    => 'Casa de Dos Plantas',
							'uqbhi_name_en'    => 'Two-story House',
						),
					),
					array(
						'name' => 'Quarto (Casa)',
						'slug' => 'quarto-casa',
						'meta' => array(
							'uqbhi_id_tipo'    => '1',
							'uqbhi_id_subtipo' => '37',
							'uqbhi_name_es'    => 'Habitación (Casa)',
							'uqbhi_name_en'    => 'Room (House)',
						),
					),
				),
			),
			array(
				'name'     => 'Apartamento',
				'slug'     => 'apartamento',
				'meta'     => array(
					'uqbhi_id_tipo'    => '2',
					'uqbhi_id_subtipo' => '1',
					'uqbhi_name_es'    => 'Departamento',
					'uqbhi_name_en'    => 'Apartment',
				),
				'children' => array(
					array(
						'name' => 'Studio / Kitchenette',
						'slug' => 'studio',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '2',
							'uqbhi_name_es'    => 'Monoambiente',
							'uqbhi_name_en'    => 'Studio',
						),
					),
					array(
						'name' => 'Loft',
						'slug' => 'loft',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '3',
							'uqbhi_name_es'    => 'Loft',
							'uqbhi_name_en'    => 'Loft',
						),
					),
					array(
						'name' => 'Flat',
						'slug' => 'flat',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '4',
							'uqbhi_name_es'    => 'Flat',
							'uqbhi_name_en'    => 'Flat',
						),
					),
					array(
						'name' => 'Cobertura',
						'slug' => 'cobertura',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '26',
							'uqbhi_name_es'    => 'Penthouse',
							'uqbhi_name_en'    => 'Penthouse',
						),
					),
					array(
						'name' => 'Duplex',
						'slug' => 'duplex',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '34',
							'uqbhi_name_es'    => 'Dúplex',
							'uqbhi_name_en'    => 'Duplex',
						),
					),
					array(
						'name' => 'Triplex',
						'slug' => 'triplex',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '35',
							'uqbhi_name_es'    => 'Tríplex',
							'uqbhi_name_en'    => 'Triplex',
						),
					),
					array(
						'name' => 'Quarto (Apartamento)',
						'slug' => 'quarto-apt',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '36',
							'uqbhi_name_es'    => 'Habitación (Departamento)',
							'uqbhi_name_en'    => 'Room (Apartment)',
						),
					),
					array(
						'name' => 'Garden',
						'slug' => 'garden',
						'meta' => array(
							'uqbhi_id_tipo'    => '2',
							'uqbhi_id_subtipo' => '38',
							'uqbhi_name_es'    => 'Garden',
							'uqbhi_name_en'    => 'Garden Apartment',
						),
					),
				),
			),
			array(
				'name'     => 'Terreno',
				'slug'     => 'terreno',
				'meta'     => array(
					'uqbhi_id_tipo'    => '1003',
					'uqbhi_id_subtipo' => '8',
					'uqbhi_name_es'    => 'Terreno',
					'uqbhi_name_en'    => 'Land',
				),
				'children' => array(
					array(
						'name' => 'Loteamento / Condomínio',
						'slug' => 'loteamento',
						'meta' => array(
							'uqbhi_id_tipo'    => '1003',
							'uqbhi_id_subtipo' => '9',
							'uqbhi_name_es'    => 'Loteo',
							'uqbhi_name_en'    => 'Subdivision Lot',
						),
					),
				),
			),
			array(
				'name'     => 'Rural',
				'slug'     => 'rural',
				'meta'     => array(
					'uqbhi_id_tipo'    => '1004',
					'uqbhi_id_subtipo' => '10',
					'uqbhi_name_es'    => 'Rural',
					'uqbhi_name_en'    => 'Rural',
				),
				'children' => array(
					array(
						'name' => 'Chácara',
						'slug' => 'chacara',
						'meta' => array(
							'uqbhi_id_tipo'    => '1004',
							'uqbhi_id_subtipo' => '10',
							'uqbhi_name_es'    => 'Chacra',
							'uqbhi_name_en'    => 'Small Farm',
						),
					),
					array(
						'name' => 'Sítio',
						'slug' => 'sitio',
						'meta' => array(
							'uqbhi_id_tipo'    => '1004',
							'uqbhi_id_subtipo' => '11',
							'uqbhi_name_es'    => 'Finca',
							'uqbhi_name_en'    => 'Country Estate',
						),
					),
					array(
						'name' => 'Fazenda',
						'slug' => 'fazenda',
						'meta' => array(
							'uqbhi_id_tipo'    => '1004',
							'uqbhi_id_subtipo' => '12',
							'uqbhi_name_es'    => 'Hacienda',
							'uqbhi_name_en'    => 'Ranch',
						),
					),
					array(
						'name' => 'Haras',
						'slug' => 'haras',
						'meta' => array(
							'uqbhi_id_tipo'    => '1004',
							'uqbhi_id_subtipo' => '13',
							'uqbhi_name_es'    => 'Haras',
							'uqbhi_name_en'    => 'Stud Farm',
						),
					),
				),
			),
			array(
				'name'     => 'Comercial',
				'slug'     => 'comercial',
				'meta'     => array(
					'uqbhi_id_tipo'    => '1005',
					'uqbhi_id_subtipo' => '16',
					'uqbhi_name_es'    => 'Comercial',
					'uqbhi_name_en'    => 'Commercial',
				),
				'children' => array(
					array(
						'name' => 'Box / Garagem',
						'slug' => 'box-garagem',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '14',
							'uqbhi_name_es'    => 'Cochera',
							'uqbhi_name_en'    => 'Garage',
						),
					),
					array(
						'name' => 'Prédio Inteiro',
						'slug' => 'predio-inteiro',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '15',
							'uqbhi_name_es'    => 'Edificio Completo',
							'uqbhi_name_en'    => 'Whole Building',
						),
					),
					array(
						'name' => 'Conjunto Comercial / Sala',
						'slug' => 'conjunto-comercial',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '16',
							'uqbhi_name_es'    => 'Oficina',
							'uqbhi_name_en'    => 'Office Suite',
						),
					),
					array(
						'name' => 'Casa Comercial',
						'slug' => 'casa-comercial',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '17',
							'uqbhi_name_es'    => 'Casa Comercial',
							'uqbhi_name_en'    => 'Commercial House',
						),
					),
					array(
						'name' => 'Loja de Shopping',
						'slug' => 'loja-shopping',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '18',
							'uqbhi_name_es'    => 'Local en Shopping',
							'uqbhi_name_en'    => 'Shopping Mall Store',
						),
					),
					array(
						'name' => 'Loja / Salão',
						'slug' => 'loja-salao',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '19',
							'uqbhi_name_es'    => 'Local Comercial',
							'uqbhi_name_en'    => 'Retail Store',
						),
					),
					array(
						'name' => 'Galpão / Depósito',
						'slug' => 'galpao',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '20',
							'uqbhi_name_es'    => 'Galpón',
							'uqbhi_name_en'    => 'Warehouse',
						),
					),
					array(
						'name' => 'Hotel',
						'slug' => 'hotel',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '22',
							'uqbhi_name_es'    => 'Hotel',
							'uqbhi_name_en'    => 'Hotel',
						),
					),
					array(
						'name' => 'Motel',
						'slug' => 'motel',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '23',
							'uqbhi_name_es'    => 'Motel',
							'uqbhi_name_en'    => 'Motel',
						),
					),
					array(
						'name' => 'Pousada / Chalé',
						'slug' => 'pousada',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '24',
							'uqbhi_name_es'    => 'Posada',
							'uqbhi_name_en'    => 'Inn',
						),
					),
					array(
						'name' => 'Indústria',
						'slug' => 'industria',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '25',
							'uqbhi_name_es'    => 'Industria',
							'uqbhi_name_en'    => 'Industrial Building',
						),
					),
					array(
						'name' => 'Área Industrial',
						'slug' => 'area-industrial',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '27',
							'uqbhi_name_es'    => 'Área Industrial',
							'uqbhi_name_en'    => 'Industrial Area',
						),
					),
					array(
						'name' => 'Consultório',
						'slug' => 'consultorio',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '28',
							'uqbhi_name_es'    => 'Consultorio',
							'uqbhi_name_en'    => 'Medical Office',
						),
					),
					array(
						'name' => 'Clínica',
						'slug' => 'clinica',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '29',
							'uqbhi_name_es'    => 'Clínica',
							'uqbhi_name_en'    => 'Clinic',
						),
					),
					array(
						'name' => 'Andar Corrido',
						'slug' => 'andar-corrido',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '30',
							'uqbhi_name_es'    => 'Piso Completo',
							'uqbhi_name_en'    => 'Full Floor',
						),
					),
					array(
						'name' => 'Ponto Comercial',
						'slug' => 'ponto-comercial',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '31',
							'uqbhi_name_es'    => 'Fondo de Comercio',
							'uqbhi_name_en'    => 'Business Premises',
						),
					),
					array(
						'name' => 'Área Comercial',
						'slug' => 'area-comercial',
						'meta' => array(
							'uqbhi_id_tipo'    => '1005',
							'uqbhi_id_subtipo' => '32',
							'uqbhi_name_es'    => 'Área Comercial',
							'uqbhi_name_en'    => 'Commercial Area',
						),
					),
				),
			),
		),
		'uqbhi_finalidade' => array(
			array(
				'name' => 'Venda',
				'slug' => 'venda',
				'meta' => array(
					'uqbhi_opennavent' => 'VENTA',
					'uqbhi_name_es'    => 'Venta',
					'uqbhi_name_en'    => 'Sale',
				),
			),
			array(
				'name' => 'Aluguel',
				'slug' => 'aluguel',
				'meta' => array(
					'uqbhi_opennavent' => 'ALQUILER',
					'uqbhi_name_es'    => 'Alquiler',
					'uqbhi_name_en'    => 'Rent',
				),
			),
		),
	);

	foreach ( $seed as $taxonomy => $terms ) {
		foreach ( $terms as $term ) {
			uqbhi_seed_term_tree( $taxonomy, $term, 0 );
		}
	}
}

/**
 * Insert (or update) a term tree, persisting meta when present.
 *
 * @param string $taxonomy Taxonomy name.
 * @param array  $term     Term definition (name, slug, optional meta, optional children).
 * @param int    $parent   Parent term ID.
 */
function uqbhi_seed_term_tree( $taxonomy, array $term, $parent = 0 ) {
	$existing = term_exists( $term['slug'], $taxonomy );
	if ( $existing ) {
		$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
	} else {
		$result = wp_insert_term(
			$term['name'],
			$taxonomy,
			array(
				'slug'   => $term['slug'],
				'parent' => (int) $parent,
			)
		);
		if ( is_wp_error( $result ) ) {
			return;
		}
		$term_id = (int) $result['term_id'];
	}

	if ( ! empty( $term['meta'] ) && is_array( $term['meta'] ) ) {
		foreach ( $term['meta'] as $meta_key => $meta_value ) {
			update_term_meta( $term_id, $meta_key, $meta_value );
		}
	}

	if ( empty( $term['children'] ) ) {
		return;
	}

	foreach ( $term['children'] as $child ) {
		uqbhi_seed_term_tree( $taxonomy, $child, $term_id );
	}
}
