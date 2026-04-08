<?php
/**
 * Funções auxiliares (validação, mapeamento, utilitários).
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extract YouTube video code from a URL or direct code.
 *
 * @param string $input YouTube URL or video code.
 * @return string The video code or empty string.
 */
function uqbhi_extract_youtube_code( $input ) {
	if ( empty( $input ) ) {
		return '';
	}
	$input = trim( $input );
	// Se já é só o código (11 chars, alfanumérico + - _ ).
	if ( preg_match( '/^[a-zA-Z0-9_-]{10,12}$/', $input ) ) {
		return $input;
	}
	// URL: youtube.com/watch?v=XXXXX.
	if ( preg_match( '/[?&]v=([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) {
		return $m[1];
	}
	// URL: youtu.be/XXXXX.
	if ( preg_match( '/youtu\.be\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) {
		return $m[1];
	}
	// URL: youtube.com/embed/XXXXX.
	if ( preg_match( '/embed\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) {
		return $m[1];
	}
	// URL: youtube.com/shorts/XXXXX.
	if ( preg_match( '/shorts\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Wrap a value in CDATA tags.
 *
 * @param mixed $val Value to wrap.
 * @return string CDATA-wrapped value.
 */
function uqbhi_cdata( $val ) {
	return '<![CDATA[' . $val . ']]>';
}

/**
 * Get the property type mapping for OpenNavent XML.
 *
 * @param int $post_id The property post ID.
 * @return array Array with id, nome, and subtipo keys.
 */
function uqbhi_get_tipo( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'uqbhi_tipo' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '1',
		);
	}

	// Mapeamento completo: slug da taxonomia para idTipo + idSubTipo da API OpenNavent.
	$map = array(
		// === RESIDENCIAL: Casa (idTipo=1) ===
		'casa'               => array(
			'id'      => '1',
			'nome'    => 'Casa',
			'subtipo' => '5',
		),
		'casa-condominio'    => array(
			'id'      => '1',
			'nome'    => 'Casa',
			'subtipo' => '6',
		),
		'casa-de-vila'       => array(
			'id'      => '1',
			'nome'    => 'Casa',
			'subtipo' => '7',
		),
		'sobrado'            => array(
			'id'      => '1',
			'nome'    => 'Casa',
			'subtipo' => '33',
		),
		'quarto-casa'        => array(
			'id'      => '1',
			'nome'    => 'Casa',
			'subtipo' => '37',
		),

		// === RESIDENCIAL: Apartamento (idTipo=2) ===
		'apartamento'        => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '1',
		),
		'studio'             => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '2',
		),
		'loft'               => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '3',
		),
		'flat'               => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '4',
		),
		'cobertura'          => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '26',
		),
		'duplex'             => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '34',
		),
		'triplex'            => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '35',
		),
		'quarto-apt'         => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '36',
		),
		'garden'             => array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '38',
		),

		// === RESIDENCIAL: Terreno (idTipo=1003) ===
		'terreno'            => array(
			'id'      => '1003',
			'nome'    => 'Terreno',
			'subtipo' => '8',
		),
		'loteamento'         => array(
			'id'      => '1003',
			'nome'    => 'Terreno',
			'subtipo' => '9',
		),

		// === RESIDENCIAL: Rural (idTipo=1004) ===
		'rural'              => array(
			'id'      => '1004',
			'nome'    => 'Rural',
			'subtipo' => '10',
		),
		'chacara'            => array(
			'id'      => '1004',
			'nome'    => 'Rural',
			'subtipo' => '10',
		),
		'sitio'              => array(
			'id'      => '1004',
			'nome'    => 'Rural',
			'subtipo' => '11',
		),
		'fazenda'            => array(
			'id'      => '1004',
			'nome'    => 'Rural',
			'subtipo' => '12',
		),
		'haras'              => array(
			'id'      => '1004',
			'nome'    => 'Rural',
			'subtipo' => '13',
		),

		// === COMERCIAL (idTipo=1005) ===
		'comercial'          => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '16',
		),
		'box-garagem'        => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '14',
		),
		'predio-inteiro'     => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '15',
		),
		'conjunto-comercial' => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '16',
		),
		'casa-comercial'     => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '17',
		),
		'loja-shopping'      => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '18',
		),
		'loja-salao'         => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '19',
		),
		'galpao'             => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '20',
		),
		'hotel'              => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '22',
		),
		'motel'              => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '23',
		),
		'pousada'            => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '24',
		),
		'industria'          => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '25',
		),
		'area-industrial'    => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '27',
		),
		'consultorio'        => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '28',
		),
		'clinica'            => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '29',
		),
		'andar-corrido'      => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '30',
		),
		'ponto-comercial'    => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '31',
		),
		'area-comercial'     => array(
			'id'      => '1005',
			'nome'    => 'Comercial',
			'subtipo' => '32',
		),
	);

	// Busca por slug exato primeiro, depois por match parcial.
	$slug = $terms[0]->slug;
	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}
	foreach ( $map as $key => $val ) {
		if ( strpos( $slug, $key ) !== false ) {
			return $val;
		}
	}
	return array(
		'id'      => '2',
		'nome'    => 'Apartamento',
		'subtipo' => '1',
	);
}

/**
 * Get the operation type (Venta/Alquiler) for a property.
 *
 * @param int $post_id The property post ID.
 * @return string Operation type string.
 */
function uqbhi_get_operacao( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'uqbhi_finalidade' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return 'Venta';
	}
	$nome = strtolower( $terms[0]->name );
	if ( strpos( $nome, 'alug' ) !== false || strpos( $nome, 'loca' ) !== false ) {
		return 'Alquiler';
	}
	return 'Venta';
}

/**
 * Clean text for XML output — decode entities, strip tags, normalize whitespace.
 *
 * @param string $text Raw text to clean.
 * @return string Cleaned text.
 */
function uqbhi_clean_text( $text ) {
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = wp_strip_all_tags( $text, true );
	$text = preg_replace( "/[\r\n]+/", "\n", $text );
	$text = trim( $text );
	return $text;
}

/**
 * Get location parts (bairro and cidade) for a property.
 *
 * @param int $post_id The property post ID.
 * @return array Array with bairro and cidade keys.
 */
function uqbhi_get_localizacao_parts( $post_id ) {
	// Prioridade 1: campos ACF preenchidos via CEP.
	$bairro = get_field( 'bairro', $post_id );
	$cidade = get_field( 'cidade', $post_id );
	if ( ! empty( $cidade ) ) {
		return array(
			'bairro' => $bairro ? $bairro : '',
			'cidade' => $cidade,
		);
	}

	// Prioridade 2: taxonomia cidade-e-bairro (legado).
	$bairro = '';
	$cidade = '';
	$terms  = wp_get_post_terms( $post_id, 'uqbhi_cidadebairro' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array(
			'bairro' => '',
			'cidade' => '',
		);
	}
	foreach ( $terms as $t ) {
		if ( $t->parent > 0 ) {
			$bairro = $t->name;
			$parent = get_term( $t->parent, 'uqbhi_cidadebairro' );
			if ( ! is_wp_error( $parent ) ) {
				$cidade = $parent->name;
			}
		} elseif ( empty( $cidade ) ) {
				$cidade = $t->name;
		}
	}
	return array(
		'bairro' => $bairro,
		'cidade' => $cidade,
	);
}

/**
 * Extract street name from a location string.
 *
 * @param string $location Full location string.
 * @return string Street name only.
 */
function uqbhi_extract_rua( $location ) {
	if ( empty( $location ) ) {
		return '';
	}
	// Remove CEP pattern from the end of the string.
	$location = preg_replace( '/\s*-?\s*\d{5}-?\d{3}\s*$/', '', $location );
	$parts    = explode( ',', $location );
	// Retorna só a rua (primeira parte).
	return trim( $parts[0] );
}

/**
 * Extract CEP (postal code) from a location string.
 *
 * @param string $location Location string that may contain a CEP.
 * @return string Formatted CEP or empty string.
 */
function uqbhi_extract_cep( $location ) {
	if ( preg_match( '/(\d{5})-?(\d{3})/', $location, $m ) ) {
		return $m[1] . '-' . $m[2];
	}
	return '';
}

/**
 * Validate a property post for required feed fields.
 *
 * @param int $post_id The property post ID.
 * @return array List of validation error messages. Empty if valid.
 */
function uqbhi_validate_imovel( $post_id ) {
	$errors = array();
	$title  = get_the_title( $post_id );
	if ( mb_strlen( $title ) < 5 ) {
		$errors[] = 'Título muito curto (mín. 5 caracteres)';
	}

	$desc = get_field( 'descricao', $post_id );
	if ( empty( $desc ) ) {
		$desc = wp_strip_all_tags( get_post_field( 'post_content', $post_id ), true );
	}
	if ( mb_strlen( $desc ) < 50 ) {
		$errors[] = 'Descrição muito curta (mín. 50 caracteres)';
	}

	$sell = get_field( 'sell_price', $post_id );
	$rent = get_field( 'rent_price', $post_id );
	if ( empty( $sell ) && empty( $rent ) ) {
		$errors[] = 'Preço de venda ou locação obrigatório';
	}

	$gallery   = get_field( 'galeria_de_imagens', $post_id );
	$img_count = is_array( $gallery ) ? count( $gallery ) : 0;
	if ( $img_count < 5 ) {
		$errors[] = 'Mínimo 5 fotos na galeria (tem ' . $img_count . ')';
	}

	// Tipo de propriedade (obrigatório).
	$tipos = wp_get_post_terms( $post_id, 'uqbhi_tipo', array( 'fields' => 'ids' ) );
	if ( empty( $tipos ) || is_wp_error( $tipos ) ) {
		$errors[] = 'Tipo do imóvel não selecionado';
	}

	// Finalidade (obrigatório).
	$fins = wp_get_post_terms( $post_id, 'uqbhi_finalidade', array( 'fields' => 'ids' ) );
	if ( empty( $fins ) || is_wp_error( $fins ) ) {
		$errors[] = 'Finalidade não selecionada';
	}

	// Endereço completo (obrigatório).
	if ( empty( get_field( 'cep', $post_id ) ) ) {
		$errors[] = 'CEP não preenchido';
	}
	if ( empty( get_field( 'location', $post_id ) ) ) {
		$errors[] = 'Rua não preenchida';
	}
	if ( empty( get_field( 'bairro', $post_id ) ) ) {
		$errors[] = 'Bairro não preenchido';
	}
	if ( empty( get_field( 'cidade', $post_id ) ) ) {
		$errors[] = 'Cidade não preenchida';
	}
	if ( empty( get_field( 'estado', $post_id ) ) ) {
		$errors[] = 'Estado não preenchido';
	}

	if ( empty( get_field( 'metreage', $post_id ) ) ) {
		$errors[] = 'Área privativa (m²) não preenchida';
	}

	// IPTU (importante para posicionamento).
	if ( empty( get_field( 'iptu', $post_id ) ) ) {
		$errors[] = 'IPTU não preenchido';
	}

	// Idade do imóvel.
	if ( empty( get_field( 'idade', $post_id ) ) ) {
		$errors[] = 'Idade do imóvel não preenchida';
	}

	// Condomínio — obrigatório para Apartamento e subtipos de Casa em condomínio.
	$tipo_slugs  = wp_get_post_terms( $post_id, 'uqbhi_tipo', array( 'fields' => 'slugs' ) );
	$needs_condo = false;
	if ( ! is_wp_error( $tipo_slugs ) ) {
		$condo_slugs = array( 'apartamento', 'studio', 'loft', 'flat', 'cobertura', 'duplex', 'triplex', 'garden', 'casa-de-condominio' );
		foreach ( $tipo_slugs as $slug ) {
			if ( in_array( $slug, $condo_slugs, true ) ) {
				$needs_condo = true;
				break; }
		}
	}
	if ( $needs_condo && empty( get_field( 'condominium', $post_id ) ) ) {
		$errors[] = 'Condomínio obrigatório para este tipo de imóvel';
	}

	return $errors;
}
