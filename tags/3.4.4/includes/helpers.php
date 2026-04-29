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
 * Whether ACF Pro's `gallery` field type is available.
 *
 * The gallery field is Pro-only. When absent, the plugin registers native
 * WordPress metaboxes as a fallback (see includes/gallery-fallback.php).
 *
 * @return bool
 */
function uqbhi_has_acf_pro_gallery() {
	return function_exists( 'acf_get_field_type' ) && null !== acf_get_field_type( 'gallery' );
}

/**
 * Get the property type mapping for OpenNavent XML.
 *
 * Source of truth is the term meta set by the seed:
 *   - uqbhi_id_tipo     → OpenNavent idTipo
 *   - uqbhi_id_subtipo  → OpenNavent idSubTipo
 *
 * If the selected term is missing meta (e.g. user-created custom term), walks
 * up the hierarchy to inherit the nearest ancestor's IDs. The `nome` field is
 * always the root ancestor's term name (the OpenNavent category label).
 *
 * @param int $post_id The property post ID.
 * @return array Array with id, nome, and subtipo keys.
 */
function uqbhi_get_tipo( $post_id ) {
	$default = array(
		'id'      => '2',
		'nome'    => 'Apartamento',
		'subtipo' => '1',
	);

	$terms = wp_get_post_terms( $post_id, 'uqbhi_tipo' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return $default;
	}

	$chosen = $terms[0];

	// Walk up to find the nearest ancestor that has the OpenNavent meta set.
	$walker     = $chosen;
	$id_tipo    = get_term_meta( $walker->term_id, 'uqbhi_id_tipo', true );
	$id_subtipo = get_term_meta( $walker->term_id, 'uqbhi_id_subtipo', true );
	while ( '' === $id_tipo && $walker->parent > 0 ) {
		$walker = get_term( $walker->parent, 'uqbhi_tipo' );
		if ( ! $walker || is_wp_error( $walker ) ) {
			break;
		}
		$id_tipo    = get_term_meta( $walker->term_id, 'uqbhi_id_tipo', true );
		$id_subtipo = get_term_meta( $walker->term_id, 'uqbhi_id_subtipo', true );
	}

	if ( '' === $id_tipo ) {
		return $default;
	}

	// Root ancestor of the selected term — used as the OpenNavent "nome".
	$root = $chosen;
	while ( $root->parent > 0 ) {
		$parent = get_term( $root->parent, 'uqbhi_tipo' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$root = $parent;
	}

	return array(
		'id'      => (string) $id_tipo,
		'nome'    => $root->name,
		'subtipo' => (string) $id_subtipo,
	);
}

/**
 * Get the operation type (Venta/Alquiler) for a property.
 *
 * Reads from the `uqbhi_opennavent` term meta set by the seed. Falls back to
 * the term name (pt/es/en) if meta is missing on a user-created term.
 *
 * @param int $post_id The property post ID.
 * @return string Operation type string.
 */
function uqbhi_get_operacao( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'uqbhi_finalidade' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return 'Venta';
	}

	$op = get_term_meta( $terms[0]->term_id, 'uqbhi_opennavent', true );
	if ( 'ALQUILER' === $op ) {
		return 'Alquiler';
	}
	if ( 'VENTA' === $op ) {
		return 'Venta';
	}

	$nome = strtolower( $terms[0]->name );
	if ( strpos( $nome, 'alug' ) !== false || strpos( $nome, 'loca' ) !== false || strpos( $nome, 'alqu' ) !== false || strpos( $nome, 'rent' ) !== false ) {
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
	if ( ! uqbhi_has_value( $sell ) && ! uqbhi_has_value( $rent ) ) {
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

	if ( ! uqbhi_has_value( get_field( 'metreage', $post_id ) ) ) {
		$errors[] = 'Área privativa (m²) não preenchida';
	}

	// IPTU (importante para posicionamento).
	if ( ! uqbhi_has_value( get_field( 'iptu', $post_id ) ) ) {
		$errors[] = 'IPTU não preenchido';
	}

	// Idade do imóvel.
	if ( ! uqbhi_has_value( get_field( 'idade', $post_id ) ) ) {
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
	if ( $needs_condo && ! uqbhi_has_value( get_field( 'condominium', $post_id ) ) ) {
		$errors[] = 'Condomínio obrigatório para este tipo de imóvel';
	}

	return $errors;
}

/**
 * Check whether a field has a meaningful value.
 *
 * Accepts numeric zero as a valid value.
 *
 * @param mixed $value Field value.
 * @return bool
 */
function uqbhi_has_value( $value ) {
	if ( is_array( $value ) ) {
		return ! empty( $value );
	}

	if ( is_string( $value ) ) {
		return trim( $value ) !== '';
	}

	return null !== $value;
}
