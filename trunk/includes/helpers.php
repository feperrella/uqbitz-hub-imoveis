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
 * Resolve the OpenNavent IDs carried by a single uqbhi_tipo term.
 *
 * Source of truth is the term meta set by the seed:
 *   - uqbhi_id_tipo     → OpenNavent idTipo
 *   - uqbhi_id_subtipo  → OpenNavent idSubTipo
 *
 * A term without meta (e.g. user-created custom term) inherits from the nearest
 * ancestor that has it, so a custom child of an official type maps correctly.
 *
 * @param WP_Term $term Term to resolve.
 * @return array|null Array with id and subtipo keys, or null when neither the
 *                    term nor any ancestor carries the meta.
 */
function uqbhi_resolve_tipo_ids( $term ) {
	$walker     = $term;
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
		return null;
	}

	return array(
		'id'      => (string) $id_tipo,
		'subtipo' => (string) $id_subtipo,
	);
}

/**
 * Pick the assigned uqbhi_tipo term that carries the OpenNavent mapping.
 *
 * Terms are considered in name order and the first one that resolves wins.
 * Selecting by resolvability rather than by position matters when a property
 * carries more than one type: a user-created term without meta (e.g.
 * "Residencial") would otherwise shadow a mapped one purely because it sorts
 * first, and the property would be exported under the wrong category.
 *
 * @param int $post_id The property post ID.
 * @return array|null Array with term, id and subtipo keys, or null when no
 *                    assigned term resolves to an OpenNavent mapping.
 */
function uqbhi_get_tipo_term( $post_id ) {
	$terms = wp_get_post_terms(
		$post_id,
		'uqbhi_tipo',
		array(
			'orderby' => 'name',
			'order'   => 'ASC',
		)
	);
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		$ids = uqbhi_resolve_tipo_ids( $term );
		if ( null !== $ids ) {
			return array(
				'term'    => $term,
				'id'      => $ids['id'],
				'subtipo' => $ids['subtipo'],
			);
		}
	}

	return null;
}

/**
 * Get the property type mapping for OpenNavent XML.
 *
 * The `nome` field is always the root ancestor's term name (the OpenNavent
 * category label).
 *
 * @param int $post_id The property post ID.
 * @return array Array with id, nome, and subtipo keys.
 */
function uqbhi_get_tipo( $post_id ) {
	$resolved = uqbhi_get_tipo_term( $post_id );

	if ( null === $resolved ) {
		/*
		 * Nothing resolved. uqbhi_validate_imovel() rejects these properties, so
		 * the feed never reaches this branch — it only guards direct callers,
		 * which is why it stays a value rather than an error.
		 */
		return array(
			'id'      => '2',
			'nome'    => 'Apartamento',
			'subtipo' => '1',
		);
	}

	// Root ancestor of the selected term — used as the OpenNavent "nome".
	$root = $resolved['term'];
	while ( $root->parent > 0 ) {
		$parent = get_term( $root->parent, 'uqbhi_tipo' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$root = $parent;
	}

	return array(
		'id'      => $resolved['id'],
		'nome'    => $root->name,
		'subtipo' => $resolved['subtipo'],
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
 * Map a single `uqbhi_finalidade` term to the operations it authorises.
 *
 * Unlike uqbhi_get_operacao(), this never guesses: a term it cannot recognise
 * yields an empty array so the caller can fall back instead of silently
 * restricting the property. It also returns a set, because a single term may
 * authorise both operations (e.g. "Venda e Locação").
 *
 * @param WP_Term $term Term from `uqbhi_finalidade`.
 * @return string[] Subset of VENTA / ALQUILER. Empty when unrecognised.
 */
function uqbhi_map_finalidade_term( $term ) {
	$op = get_term_meta( $term->term_id, 'uqbhi_opennavent', true );
	if ( 'ALQUILER' === $op || 'VENTA' === $op ) {
		return array( $op );
	}

	// Inherit from an ancestor, mirroring uqbhi_get_tipo().
	$walker = $term;
	$guard  = 0;
	while ( ! empty( $walker->parent ) && $guard < 10 ) {
		++$guard;
		$walker = get_term( $walker->parent, 'uqbhi_finalidade' );
		if ( ! $walker || is_wp_error( $walker ) ) {
			break;
		}
		$herdado = get_term_meta( $walker->term_id, 'uqbhi_opennavent', true );
		if ( 'ALQUILER' === $herdado || 'VENTA' === $herdado ) {
			return array( $herdado );
		}
	}

	// Name heuristic. Both lists are tested: stopping at the first match would
	// map "Venda e Locação" to rental only and drop a legitimate sale.
	$nome = strtolower( $term->name );
	$ops  = array();
	foreach ( array( 'alug', 'loca', 'alqu', 'rent' ) as $needle ) {
		if ( false !== strpos( $nome, $needle ) ) {
			$ops[] = 'ALQUILER';
			break;
		}
	}
	foreach ( array( 'venda', 'vend', 'vent', 'sale', 'sell' ) as $needle ) {
		if ( false !== strpos( $nome, $needle ) ) {
			$ops[] = 'VENTA';
			break;
		}
	}

	return $ops;
}

/**
 * Operations authorised by a property's `uqbhi_finalidade` terms.
 *
 * @param int $post_id The property post ID.
 * @return string[] Subset of VENTA / ALQUILER. Empty when the finalidade is
 *                  missing or none of its terms could be mapped.
 */
function uqbhi_get_operacoes( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'uqbhi_finalidade' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$ops = array();
	foreach ( $terms as $term ) {
		$ops = array_merge( $ops, uqbhi_map_finalidade_term( $term ) );
	}

	return array_values( array_unique( $ops ) );
}

/**
 * Operations a property actually publishes: priced AND authorised.
 *
 * The finalidade governs which `<preco>` blocks are emitted, but only as a
 * filter over the prices that are filled in — so this is always a subset of
 * what the price fields alone would produce. When the finalidade cannot be
 * determined, it falls back to the price-driven behaviour rather than
 * restricting the property on a guess.
 *
 * @param int $post_id The property post ID.
 * @return string[] Subset of VENTA / ALQUILER.
 */
function uqbhi_get_operacoes_publicaveis( $post_id ) {
	// uqbhi_has_price e não uqbhi_has_value: um imóvel sem finalidade cai no
	// fallback abaixo, e com has_value um rent_price de "0" viraria anúncio de
	// locação a R$ 0 — que o OpenNavent recusa no Brasil.
	$atual = array();
	if ( uqbhi_has_price( get_field( 'sell_price', $post_id ) ) ) {
		$atual[] = 'VENTA';
	}
	if ( uqbhi_has_price( get_field( 'rent_price', $post_id ) ) ) {
		$atual[] = 'ALQUILER';
	}

	$permitido = uqbhi_get_operacoes( $post_id );
	if ( empty( $permitido ) ) {
		return $atual;
	}

	return array_values( array_intersect( $atual, $permitido ) );
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
 * Get location parts (bairro, cidade and estado) for a property.
 *
 * @param int $post_id The property post ID.
 * @return array Array with bairro, cidade and estado keys.
 */
function uqbhi_get_localizacao_parts( $post_id ) {
	$estado = get_field( 'estado', $post_id );

	// Prioridade 1: campos ACF preenchidos via CEP.
	$bairro = get_field( 'bairro', $post_id );
	$cidade = get_field( 'cidade', $post_id );
	if ( ! empty( $cidade ) ) {
		return array(
			'bairro' => $bairro ? $bairro : '',
			'cidade' => $cidade,
			'estado' => $estado ? $estado : '',
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
			'estado' => $estado ? $estado : '',
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
		'estado' => $estado ? $estado : '',
	);
}

/**
 * Build the OpenNavent `<localidade>` string: Bairro,Cidade,Estado,País.
 *
 * The portal resolves the string from right to left, so a missing bairro is
 * tolerated but a wrong/missing estado makes the whole location invalid
 * ("O imóvel não tem uma cidade válida"). Commas inside a part would shift
 * every following level, so they are stripped.
 *
 * @param array $parts Output of uqbhi_get_localizacao_parts().
 * @return string Localidade string, or empty when it cannot be resolved.
 */
function uqbhi_build_localidade( $parts ) {
	$bairro = uqbhi_clean_loc_part( isset( $parts['bairro'] ) ? $parts['bairro'] : '' );
	$cidade = uqbhi_clean_loc_part( isset( $parts['cidade'] ) ? $parts['cidade'] : '' );
	$estado = uqbhi_normalize_estado( isset( $parts['estado'] ) ? $parts['estado'] : '' );

	// Sem cidade ou sem estado a string vira ambígua — melhor não enviar.
	if ( '' === $cidade || '' === $estado ) {
		return '';
	}

	$pieces = array();
	if ( '' !== $bairro ) {
		$pieces[] = $bairro;
	}
	$pieces[] = $cidade;
	$pieces[] = $estado;
	$pieces[] = 'Brasil';

	return implode( ',', $pieces );
}

/**
 * Normalize a location part — trim and drop commas (the OpenNavent separator).
 *
 * @param string $value Raw part.
 * @return string Cleaned part.
 */
function uqbhi_clean_loc_part( $value ) {
	return trim( str_replace( ',', ' ', (string) $value ) );
}

/**
 * Normalize a Brazilian state to the full name expected by OpenNavent.
 *
 * Accepts both the UF code (SP) and the full name (São Paulo).
 *
 * @param string $estado Raw state value.
 * @return string Full state name, or empty string when unknown.
 */
function uqbhi_normalize_estado( $estado ) {
	$estado = trim( (string) $estado );
	if ( '' === $estado ) {
		return '';
	}

	$ufs = array(
		'AC' => 'Acre',
		'AL' => 'Alagoas',
		'AP' => 'Amapá',
		'AM' => 'Amazonas',
		'BA' => 'Bahia',
		'CE' => 'Ceará',
		'DF' => 'Distrito Federal',
		'ES' => 'Espírito Santo',
		'GO' => 'Goiás',
		'MA' => 'Maranhão',
		'MT' => 'Mato Grosso',
		'MS' => 'Mato Grosso do Sul',
		'MG' => 'Minas Gerais',
		'PA' => 'Pará',
		'PB' => 'Paraíba',
		'PR' => 'Paraná',
		'PE' => 'Pernambuco',
		'PI' => 'Piauí',
		'RJ' => 'Rio de Janeiro',
		'RN' => 'Rio Grande do Norte',
		'RS' => 'Rio Grande do Sul',
		'RO' => 'Rondônia',
		'RR' => 'Roraima',
		'SC' => 'Santa Catarina',
		'SP' => 'São Paulo',
		'SE' => 'Sergipe',
		'TO' => 'Tocantins',
	);

	$upper = mb_strtoupper( $estado );
	if ( isset( $ufs[ $upper ] ) ) {
		return $ufs[ $upper ];
	}

	// Já é o nome por extenso (ViaCEP devolve "São Paulo" no campo `estado`).
	return uqbhi_clean_loc_part( $estado );
}

/**
 * Build the feed-level `dataModificacao` value (Unix timestamp, milliseconds).
 *
 * The portal compares this timestamp against the last change made to each ad
 * via API or via the ImovelWeb panel: if the XML is older, the ad is rejected
 * with "a data do XML é anterior à mudança". The margin below (in seconds) can
 * be used to make the XML always win over panel edits.
 *
 * Formatted with `sprintf` rather than cast to int: `round()` returns a float,
 * and a 13-digit float is rendered in scientific notation (1.7847E+12) when the
 * host sets `precision` below 13, while an int cast overflows on 32-bit builds.
 * Arithmetic stays in float, which is exact for integers well past year 2286.
 *
 * @return string Timestamp in milliseconds, digits only.
 */
function uqbhi_data_modificacao() {
	$margem = (int) apply_filters( 'uqbhi_data_modificacao_margem', uqbhi_get_margem_seguranca() );
	$agora  = round( ( microtime( true ) + $margem ) * 1000 );

	// Nunca andar para trás. Se o portal guardar o valor recebido, desligar a
	// margem faria as cargas seguintes perderem a comparação até o relógio
	// alcançar o último valor emitido.
	$ultimo = (float) get_option( 'uqbhi_ultima_data_modificacao', 0 );
	if ( $ultimo >= $agora ) {
		$agora = $ultimo + 1;
	}

	// Só persiste enquanto a margem está ativa — é o único caso que cria a
	// dívida a ser paga depois. Sem margem, o relógio já é monotônico.
	if ( $margem > 0 ) {
		update_option( 'uqbhi_ultima_data_modificacao', sprintf( '%.0f', $agora ), false );
	}

	return sprintf( '%.0f', $agora );
}

/**
 * Safety margin (in seconds) added to the feed's dataModificacao.
 *
 * @return int Margin in seconds. Zero when the option is off.
 */
function uqbhi_get_margem_seguranca() {
	$settings = get_option( 'uqbhi_settings', array() );

	return ! empty( $settings['forcar_atualizacao'] ) ? DAY_IN_SECONDS * 2 : 0;
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
	if ( mb_strlen( $title ) < 10 ) {
		$errors[] = 'Título muito curto (mín. 10 caracteres)';
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
	if ( ! uqbhi_has_price( $sell ) && ! uqbhi_has_price( $rent ) ) {
		// Preço com máscara ("4.350.000,00") vira 4 no intval() do feed — publicar
		// assim anuncia um imóvel de milhões por alguns reais. Falhar alto.
		$mal_formatados = array();
		foreach ( array( $sell, $rent ) as $valor ) {
			if ( uqbhi_has_value( $valor ) && ! is_numeric( $valor ) ) {
				$mal_formatados[] = $valor;
			}
		}
		if ( ! empty( $mal_formatados ) ) {
			$errors[] = 'Preço em formato inválido (' . implode( ', ', $mal_formatados ) . ') — informe só números, sem ponto ou vírgula';
		} else {
			$errors[] = 'Preço de venda ou locação obrigatório (maior que zero)';
		}
	} elseif ( empty( uqbhi_get_operacoes_publicaveis( $post_id ) ) ) {
		// Há preço válido, mas nenhum que a finalidade autorize — publicar assim
		// geraria um anúncio sem preço no portal.
		$errors[] = 'Preço preenchido não corresponde à finalidade selecionada';
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
	} elseif ( null === uqbhi_get_tipo_term( $post_id ) ) {
		/*
		 * No assigned type maps to an OpenNavent category. Exporting anyway would
		 * silently advertise the property under the wrong category, so it is held
		 * back until the type is fixed.
		 */
		$nomes = wp_get_post_terms( $post_id, 'uqbhi_tipo', array( 'fields' => 'names' ) );
		$lista = is_wp_error( $nomes ) ? '' : ' (' . implode( ', ', $nomes ) . ')';

		$errors[] = 'Tipo sem mapeamento OpenNavent' . $lista . ' — selecione um tipo oficial ou torne este uma subcategoria de um';
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
	} elseif ( '' === uqbhi_normalize_estado( get_field( 'estado', $post_id ) ) ) {
		$errors[] = 'Estado inválido (use a UF ou o nome por extenso)';
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
		$condo_slugs = array( 'apartamento', 'studio', 'loft', 'flat', 'cobertura', 'duplex', 'triplex', 'garden', 'casa-condominio' );
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

/**
 * Check whether a price field can be sent to the portal.
 *
 * Unlike the other numeric fields, zero is not a valid price in Brazil —
 * OpenNavent rejects `<quantidade>0</quantidade>` for BR portals.
 *
 * @param mixed $value Price field value.
 * @return bool
 */
function uqbhi_has_price( $value ) {
	return uqbhi_has_value( $value ) && is_numeric( $value ) && intval( $value ) > 0;
}
