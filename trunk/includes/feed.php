<?php
/**
 * Rewrite rules, interceptação de request e geração do XML feed.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

/*
 * REWRITE RULES — URL limpa /feed-imovelweb/.
 */
add_action( 'init', 'uqbhi_register_rewrite' );

/**
 * Add rewrite rule for the feed URL.
 */
function uqbhi_register_rewrite() {
	add_rewrite_rule(
		'^' . UQBHI_FEED_SLUG . '/?$',
		'index.php?uqbhi_feed=1',
		'top'
	);
}

add_filter( 'query_vars', 'uqbhi_query_vars' );

/**
 * Register custom query vars.
 *
 * @param array $vars Existing query vars.
 * @return array Modified query vars.
 */
function uqbhi_query_vars( $vars ) {
	$vars[] = 'uqbhi_feed';
	return $vars;
}

/*
 * REST API — /wp-json/uqbhi/v1/feed.
 */
add_action( 'rest_api_init', 'uqbhi_register_rest_route' );

/**
 * Register the REST API feed route.
 */
function uqbhi_register_rest_route() {
	register_rest_route(
		'uqbhi/v1',
		'/feed',
		array(
			'methods'             => 'GET',
			'callback'            => 'uqbhi_rest_callback',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * REST API callback — output the XML feed.
 *
 * @param WP_REST_Request $request The REST request object.
 */
function uqbhi_rest_callback( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	while ( ob_get_level() ) {
		ob_end_clean(); }
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	echo uqbhi_generate_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML feed output
	exit;
}

/*
 * TEMPLATE REDIRECT — servir XML via URL limpa.
 */
add_action( 'template_redirect', 'uqbhi_serve_feed' );

/**
 * Serve XML feed on the clean URL.
 */
function uqbhi_serve_feed() {
	if ( ! get_query_var( 'uqbhi_feed' ) ) {
		return;
	}

	// Limpar qualquer buffer pendente.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	// Headers.
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	header( 'X-Robots-Tag: noindex, nofollow' );

	// Gerar e enviar.
	echo uqbhi_generate_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML feed output
	exit;
}

/**
 * Generate the full OpenNavent XML feed.
 *
 * @return string XML feed content.
 */
function uqbhi_generate_xml() {
	$posts = get_posts(
		array(
			'post_type'      => 'uqbhi_imovel',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<OpenNavent>' . "\n";
	$xml .= '  <dataModificacao>' . uqbhi_cdata( round( microtime( true ) * 1000 ) ) . '</dataModificacao>' . "\n";
	$xml .= '  <Imoveis>' . "\n";

	foreach ( $posts as $post ) {
		$errs = uqbhi_validate_imovel( $post->ID );
		if ( ! empty( $errs ) ) {
			continue; // Pular imóveis com campos obrigatórios faltando.
		}
		$xml .= uqbhi_render_imovel( $post );
	}

	$xml .= '  </Imoveis>' . "\n";
	$xml .= '</OpenNavent>';

	return $xml;
}

/**
 * Render a single property as XML.
 *
 * @param WP_Post $post The property post object.
 * @return string XML fragment for the property.
 */
function uqbhi_render_imovel( $post ) {
	$id = $post->ID;

	// Campos ACF.
	$referencia  = get_field( 'referencia', $id );
	$location    = get_field( 'location', $id );
	$numero      = get_field( 'numero', $id );
	$sell_price  = get_field( 'sell_price', $id );
	$rent_price  = get_field( 'rent_price', $id );
	$rooms       = get_field( 'rooms', $id );
	$suits       = get_field( 'suits', $id );
	$bathroom    = get_field( 'bathroom', $id );
	$metreage    = get_field( 'metreage', $id );
	$parking     = get_field( 'parking', $id );
	$descricao   = get_field( 'descricao', $id );
	$iptu        = get_field( 'iptu', $id );
	$condominium = get_field( 'condominium', $id );
	$idade       = get_field( 'idade', $id );
	$amenities   = get_field( 'amenities', $id );
	$infra       = get_field( 'infraestrutura', $id );
	$complemento = get_field( 'complemento', $id );

	// Fallback descrição: campo ACF > post_content > título.
	if ( empty( $descricao ) ) {
		$descricao = wp_strip_all_tags( $post->post_content, true );
	}
	if ( empty( $descricao ) ) {
		$descricao = $post->post_title;
	}
	$descricao = mb_substr( $descricao, 0, 4000 );

	// Tipo de propriedade.
	$tipo = uqbhi_get_tipo( $id );

	// Operação (Venta/Alquiler).
	$operacao = uqbhi_get_operacao( $id );

	// Localidade no formato: Bairro,Cidade,Estado,País.
	$loc_parts        = uqbhi_get_localizacao_parts( $id );
	$bairro           = $loc_parts['bairro'];
	$cidade           = $loc_parts['cidade'];
	$localidade_parts = array_filter( array( $bairro, $cidade, 'São Paulo', 'Brasil' ) );
	$localidade       = implode( ',', $localidade_parts );

	// Montar endereço: rua + número + complemento.
	$endereco = rtrim( trim( $location ), ',' );
	if ( $numero ) {
		$endereco .= ', ' . $numero;
	}
	if ( $complemento ) {
		$endereco .= ' - ' . $complemento;
	}

	// CEP.
	$cep = uqbhi_extract_cep( $location ? $location : '' );

	// Montar XML do imóvel.
	$x  = '    <Imovel>' . "\n";
	$x .= '      <codigoAnuncio>' . uqbhi_cdata( $id ) . '</codigoAnuncio>' . "\n";
	$x .= '      <codigoReferencia>' . uqbhi_cdata( $referencia ? $referencia : $id ) . '</codigoReferencia>' . "\n";

	// Tipo.
	$x .= '      <tipoPropriedade>' . "\n";
	$x .= '        <idTipo>' . uqbhi_cdata( $tipo['id'] ) . '</idTipo>' . "\n";
	if ( ! empty( $tipo['subtipo'] ) ) {
		$x .= '        <idSubTipo>' . uqbhi_cdata( $tipo['subtipo'] ) . '</idSubTipo>' . "\n";
	}
	$x .= '        <tipo>' . uqbhi_cdata( $tipo['nome'] ) . '</tipo>' . "\n";
	$x .= '      </tipoPropriedade>' . "\n";

	// Título e descrição.
	$x .= '      <titulo>' . uqbhi_cdata( mb_substr( esc_html( $post->post_title ), 0, 80 ) ) . '</titulo>' . "\n";
	$x .= '      <descricao>' . uqbhi_cdata( uqbhi_clean_text( $descricao ) ) . '</descricao>' . "\n";

	// Preços.
	$x .= '      <precos>' . "\n";
	if ( uqbhi_has_value( $sell_price ) ) {
		$x .= '        <preco>' . "\n";
		$x .= '          <quantidade>' . uqbhi_cdata( intval( $sell_price ) ) . '</quantidade>' . "\n";
		$x .= '          <moeda>' . uqbhi_cdata( 'BRL' ) . '</moeda>' . "\n";
		$x .= '          <operacao>' . uqbhi_cdata( 'VENTA' ) . '</operacao>' . "\n";
		$x .= '        </preco>' . "\n";
	}
	if ( uqbhi_has_value( $rent_price ) ) {
		$x .= '        <preco>' . "\n";
		$x .= '          <quantidade>' . uqbhi_cdata( intval( $rent_price ) ) . '</quantidade>' . "\n";
		$x .= '          <moeda>' . uqbhi_cdata( 'BRL' ) . '</moeda>' . "\n";
		$x .= '          <operacao>' . uqbhi_cdata( 'ALQUILER' ) . '</operacao>' . "\n";
		$x .= '        </preco>' . "\n";
	}
	$x .= '      </precos>' . "\n";

	// Publicação (obrigatório).
	$x .= '      <publicacao>' . "\n";
	$x .= '        <tipoPublicacao>' . uqbhi_cdata( 'SIMPLE' ) . '</tipoPublicacao>' . "\n";
	$x .= '      </publicacao>' . "\n";

	// Publicador.
	$settings         = get_option( 'uqbhi_settings', array() );
	$cod_imob         = ! empty( $settings['codigo_imobiliaria'] ) ? $settings['codigo_imobiliaria'] : '';
	$email_contato    = ! empty( $settings['email_contato'] ) ? $settings['email_contato'] : '';
	$nome_contato     = ! empty( $settings['nome_contato'] ) ? $settings['nome_contato'] : '';
	$telefone_contato = ! empty( $settings['telefone_contato'] ) ? $settings['telefone_contato'] : '';
	$x               .= '      <publicador>' . "\n";
	$x               .= '        <codigoImobiliaria>' . uqbhi_cdata( $cod_imob ) . '</codigoImobiliaria>' . "\n";
	if ( $email_contato ) {
		$x .= '        <emailContato>' . uqbhi_cdata( $email_contato ) . '</emailContato>' . "\n";
	}
	if ( $nome_contato ) {
		$x .= '        <nomeContato>' . uqbhi_cdata( $nome_contato ) . '</nomeContato>' . "\n";
	}
	if ( $telefone_contato ) {
		$x .= '        <telefoneContato>' . uqbhi_cdata( $telefone_contato ) . '</telefoneContato>' . "\n";
	}
	$x .= '      </publicador>' . "\n";

	// Localização.
	$x .= '      <localizacao>' . "\n";
	$x .= '        <endereco>' . uqbhi_cdata( $endereco ) . '</endereco>' . "\n";
	$x .= '        <localidade>' . uqbhi_cdata( $localidade ) . '</localidade>' . "\n";
	if ( $cep ) {
		$x .= '        <codigoPostal>' . uqbhi_cdata( $cep ) . '</codigoPostal>' . "\n";
	}
	$x .= '        <mostrarMapa>' . uqbhi_cdata( 'APROXIMADO' ) . '</mostrarMapa>' . "\n";
	$x .= '      </localizacao>' . "\n";

	// Multimídia (fotos + vídeos + plantas).
	$gallery = get_field( 'galeria_de_imagens', $id );
	if ( is_array( $gallery ) && count( $gallery ) > 50 ) {
		$gallery = array_slice( $gallery, 0, 50 );
	}
	$video_raw  = get_field( 'video_youtube', $id );
	$video_code = uqbhi_extract_youtube_code( $video_raw );
	$plantas    = get_field( 'plantas', $id );

	$has_media = ( ! empty( $gallery ) && is_array( $gallery ) ) || $video_code || ( ! empty( $plantas ) && is_array( $plantas ) );
	if ( $has_media ) {
		$x .= '      <multimidia>' . "\n";

		// Imagens.
		if ( ! empty( $gallery ) && is_array( $gallery ) ) {
			$x .= '        <imagens>' . "\n";
			foreach ( $gallery as $img ) {
				$url = '';
				if ( is_array( $img ) && ! empty( $img['url'] ) ) {
					$url = $img['url'];
				} elseif ( is_numeric( $img ) ) {
					$url = wp_get_attachment_url( $img );
				}
				if ( $url ) {
					$x .= '          <imagem>' . "\n";
					$x .= '            <urlImagem>' . uqbhi_cdata( $url ) . '</urlImagem>' . "\n";
					$x .= '          </imagem>' . "\n";
				}
			}
			$x .= '        </imagens>' . "\n";
		}

		// Vídeos (YouTube).
		if ( $video_code ) {
			$x .= '        <videos>' . "\n";
			$x .= '          <video>' . "\n";
			$x .= '            <codigoVideo>' . uqbhi_cdata( $video_code ) . '</codigoVideo>' . "\n";
			$x .= '            <titulo>' . uqbhi_cdata( mb_substr( $post->post_title, 0, 80 ) ) . '</titulo>' . "\n";
			$x .= '          </video>' . "\n";
			$x .= '        </videos>' . "\n";
		}

		// Plantas.
		if ( ! empty( $plantas ) && is_array( $plantas ) ) {
			$x .= '        <plantas>' . "\n";
			foreach ( $plantas as $pl ) {
				$pl_url   = '';
				$pl_title = '';
				if ( is_array( $pl ) ) {
					$pl_url   = ! empty( $pl['url'] ) ? $pl['url'] : '';
					$pl_title = ! empty( $pl['title'] ) ? $pl['title'] : ( ! empty( $pl['alt'] ) ? $pl['alt'] : 'Planta' );
				} elseif ( is_numeric( $pl ) ) {
					$pl_url   = wp_get_attachment_url( $pl );
					$pl_title = get_the_title( $pl ) ? get_the_title( $pl ) : 'Planta';
				}
				if ( $pl_url ) {
					$x .= '          <planta>' . "\n";
					$x .= '            <urlImagem>' . uqbhi_cdata( $pl_url ) . '</urlImagem>' . "\n";
					$x .= '            <titulo>' . uqbhi_cdata( $pl_title ) . '</titulo>' . "\n";
					$x .= '          </planta>' . "\n";
				}
			}
			$x .= '        </plantas>' . "\n";
		}

		$x .= '      </multimidia>' . "\n";
	}

	// Características.
	$x .= '      <caracteristicas>' . "\n";
	if ( uqbhi_has_value( $rooms ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT2' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|QUARTO' ) . '</nome><valor>' . uqbhi_cdata( intval( $rooms ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $suits ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT4' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|SUITE' ) . '</nome><valor>' . uqbhi_cdata( intval( $suits ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $bathroom ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT3' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|BANHEIRO' ) . '</nome><valor>' . uqbhi_cdata( intval( $bathroom ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $metreage ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT101' ) . '</id><nome>' . uqbhi_cdata( 'MEDIDAS|AREA_UTIL' ) . '</nome><valor>' . uqbhi_cdata( intval( $metreage ) ) . '</valor></caracteristica>' . "\n";
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT100' ) . '</id><nome>' . uqbhi_cdata( 'MEDIDAS|AREA_TOTAL' ) . '</nome><valor>' . uqbhi_cdata( intval( $metreage ) ) . '</valor></caracteristica>' . "\n";
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CON1' ) . '</id><nome>' . uqbhi_cdata( 'MEDIDAS|UNIDAD_DE_MEDIDA' ) . '</nome><idValor>' . uqbhi_cdata( 'M2' ) . '</idValor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $parking ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT7' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|VAGA' ) . '</nome><valor>' . uqbhi_cdata( intval( $parking ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $iptu ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT400' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|IPTU' ) . '</nome><valor>' . uqbhi_cdata( intval( $iptu ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $condominium ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT6' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|CONDOMINIO' ) . '</nome><valor>' . uqbhi_cdata( intval( $condominium ) ) . '</valor></caracteristica>' . "\n";
	}
	if ( uqbhi_has_value( $idade ) ) {
		$x .= '        <caracteristica><id>' . uqbhi_cdata( 'CFT5' ) . '</id><nome>' . uqbhi_cdata( 'PRINCIPALES|IDADE' ) . '</nome><valor>' . uqbhi_cdata( intval( $idade ) ) . '</valor></caracteristica>' . "\n";
	}

	// Amenidades (área privativa → 20xxx).
	$amenity_map = array(
		'Aquecedor'                 => 20010,
		'Ar condicionado'           => 20012,
		'Biblioteca'                => 20026,
		'Churrasqueira'             => 20048,
		'Closet'                    => 20050,
		'Cozinha americana'         => 20056,
		'Cozinha gourmet'           => 20057,
		'Cozinha independente'      => 20058,
		'Dependência de empregados' => 20062,
		'Despensa'                  => 20065,
		'Entrada independente'      => 20228,
		'Escritório'                => 20077,
		'Espaço gourmet'            => 20080,
		'Jardim'                    => 20110,
		'Lareira'                   => 20114,
		'Lava-louça'                => 20116,
		'Lavanderia'                => 20117,
		'Mezanino'                  => 20124,
		'Mobiliado'                 => 20126,
		'Permite animais'           => 20135,
		'Piscina (privativa)'       => 20140,
		'Playground (privativo)'    => 20152,
		'Quintal'                   => 20166,
		'Sala de jantar'            => 20177,
		'Varanda'                   => 20199,
		'Área de serviço'           => 20017,
	);
	if ( is_array( $amenities ) ) {
		foreach ( $amenities as $a ) {
			if ( isset( $amenity_map[ $a ] ) ) {
				$aid = $amenity_map[ $a ];
				$x  .= '        <caracteristica><id>' . uqbhi_cdata( $aid ) . '</id><nome>' . uqbhi_cdata( 'AREA_PRIVATIVA|' . strtoupper( str_replace( ' ', '_', $a ) ) ) . '</nome></caracteristica>' . "\n";
			}
		}
	}

	// Infraestrutura (áreas comuns → 10xxx / 30xxx).
	$infra_map = array(
		'Academia'                       => 10090,
		'Acesso PNE'                     => 10005,
		'Aquecimento central'            => 20011,
		'Bicicletário'                   => 10027,
		'Brinquedoteca'                  => 10028,
		'Campo de futebol'               => 10031,
		'Churrasqueira (comum)'          => 10048,
		'Câmeras de segurança'           => 10030,
		'Elevador'                       => 10071,
		'Entrada de serviço'             => 20074,
		'Espaço gourmet (comum)'         => 10080,
		'Estacionamento para visitantes' => 10084,
		'Guarita'                        => 10103,
		'Hidromassagem'                  => 20106,
		'Horta'                          => 20107,
		'Piscina'                        => 30025,
		'Piscina (comum)'                => 30025,
		'Playground'                     => 10152,
		'Portaria 24h'                   => 10158,
		'Quadra de tênis'                => 10164,
		'Quadra poliesportiva'           => 10165,
		'SPA'                            => 10189,
		'Salão de festas'                => 10181,
		'Salão de jogos'                 => 10182,
		'Sauna'                          => 10183,
		'Solarium'                       => 10187,
		'Vestiário'                      => 10206,
		'Vigilância 24h'                 => 10208,
		'Área de lazer'                  => 10016,
		'Área verde'                     => 10018,
	);
	if ( is_array( $infra ) ) {
		foreach ( $infra as $i ) {
			if ( isset( $infra_map[ $i ] ) ) {
				$iid    = $infra_map[ $i ];
				$prefix = $iid >= 20000 ? 'AREA_PRIVATIVA' : ( $iid >= 10000 ? 'AREAS_COMUNS' : 'AGRUPADA' );
				$x     .= '        <caracteristica><id>' . uqbhi_cdata( $iid ) . '</id><nome>' . uqbhi_cdata( $prefix . '|' . strtoupper( str_replace( ' ', '_', $i ) ) ) . '</nome></caracteristica>' . "\n";
			}
		}
	}

	$x .= '      </caracteristicas>' . "\n";

	$x .= '    </Imovel>' . "\n";
	return $x;
}
