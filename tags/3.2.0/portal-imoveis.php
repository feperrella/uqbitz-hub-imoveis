<?php
/**
 * Plugin Name: Portal Imóveis
 * Plugin URI:  https://github.com/feperrella/portal-imoveis
 * Description: Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).
 * Version:     3.2.0
 * Author:      Fernando Perrella (UQBITZ)
 * Author URI:  https://uqbitz.com
 * License:     GPL-2.0+
 * Text Domain: portal-imoveis
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

/* ──────────────────────────────────────────────
 * 1. CONSTANTES
 * ────────────────────────────────────────────── */
define( 'PTIM_VERSION', '3.1.0' );
define( 'PTIM_FEED_SLUG', 'feed-imovelweb' );
define( 'PTIM_BEARER_TOKEN', '259313f5-2c84-4f6c-bd2c-eabad2a8bc83' );


/* ──────────────────────────────────────────────
 * 1b. CPT + TAXONOMIAS — Registrar via código
 * ────────────────────────────────────────────── */
add_action( 'init', 'ptim_register_post_type_and_taxonomies', 5 );
function ptim_register_post_type_and_taxonomies() {

    /* ── CPT: imovel ── */
    register_post_type( 'imovel', array(
        'labels' => array(
            'name'                  => 'Imóveis',
            'singular_name'         => 'Imóvel',
            'menu_name'             => 'Imóveis',
            'all_items'             => 'Todos os Imóveis',
            'edit_item'             => 'Editar Imóvel',
            'view_item'             => 'Ver Imóvel',
            'view_items'            => 'Ver Imóveis',
            'add_new_item'          => 'Novo Imóvel',
            'add_new'               => 'Novo Imóvel',
            'new_item'              => 'Novo Imóvel',
            'parent_item_colon'     => 'Imóvel ascendente:',
            'search_items'          => 'Pesquisar Imóveis',
            'not_found'             => 'Não foi possível encontrar imóveis',
            'not_found_in_trash'    => 'Não foi possível encontrar imóveis na lixeira',
            'archives'              => 'Arquivos de Imóvel',
            'attributes'            => 'Atributos de Imóvel',
            'insert_into_item'      => 'Inserir no imóvel',
            'uploaded_to_this_item' => 'Enviado para este imóvel',
            'filter_items_list'     => 'Filtrar lista de imóveis',
            'filter_by_date'        => 'Filtrar imóveis por data',
            'items_list_navigation' => 'Navegação na lista de Imóveis',
            'items_list'            => 'Lista de Imóveis',
            'item_published'        => 'Imóvel publicado.',
            'item_published_privately' => 'Imóvel publicado de forma privada.',
            'item_reverted_to_draft'   => 'Imóvel revertido para rascunho.',
            'item_scheduled'        => 'Imóvel agendado.',
            'item_updated'          => 'Imóvel atualizado.',
            'item_link'             => 'Link de Imóvel',
            'item_link_description' => 'Um link para um imóvel.',
        ),
        'description'         => 'Imóveis para venda e locação.',
        'public'              => true,
        'hierarchical'        => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'show_in_rest'        => true,
        'rest_namespace'      => 'wp/v2',
        'menu_position'       => 3,
        'menu_icon'           => 'dashicons-admin-multisite',
        'supports'            => array( 'title', 'thumbnail' ),
        'has_archive'         => false,
        'rewrite'             => array( 'slug' => 'imovel', 'with_front' => true, 'feeds' => false, 'pages' => true ),
        'can_export'          => true,
        'delete_with_user'    => false,
    ) );

    /* ── Taxonomia: tipo (Tipos de imóvel) ── */
    register_taxonomy( 'tipo', array( 'imovel' ), array(
        'labels' => array(
            'name'              => 'Tipos',
            'singular_name'     => 'Tipo',
            'menu_name'         => 'Tipos',
            'all_items'         => 'Todos os Tipos',
            'edit_item'         => 'Editar Tipo',
            'view_item'         => 'Ver Tipo',
            'update_item'       => 'Atualizar Tipo',
            'add_new_item'      => 'Adicionar novo Tipo',
            'new_item_name'     => 'Novo nome de Tipo',
            'parent_item'       => 'Tipo ascendente',
            'parent_item_colon' => 'Tipo ascendente:',
            'search_items'      => 'Pesquisar Tipos',
            'not_found'         => 'Não foi possível encontrar tipos',
            'no_terms'          => 'Não há tipos',
            'filter_by_item'    => 'Filtrar por tipo',
            'items_list_navigation' => 'Navegação na lista de Tipos',
            'items_list'            => 'Lista de Tipos',
            'back_to_items'         => '← Ir para tipos',
            'item_link'             => 'Link de Tipo',
            'item_link_description' => 'Um link para um tipo',
        ),
        'description'       => 'Tipos de imóveis',
        'public'            => true,
        'publicly_queryable' => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'show_tagcloud'     => true,
        'show_in_quick_edit' => true,
        'show_admin_column' => false,
        'rewrite'           => array( 'slug' => 'tipo', 'with_front' => true, 'hierarchical' => false ),
        'capabilities'      => array(
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),
    ) );

    /* ── Taxonomia: finalidade (Finalidades) ── */
    register_taxonomy( 'finalidade', array( 'imovel' ), array(
        'labels' => array(
            'name'              => 'Finalidades',
            'singular_name'     => 'Finalidade',
            'menu_name'         => 'Finalidades',
            'all_items'         => 'Todos os Finalidades',
            'edit_item'         => 'Editar Finalidade',
            'view_item'         => 'Ver Finalidade',
            'update_item'       => 'Atualizar Finalidade',
            'add_new_item'      => 'Adicionar novo Finalidade',
            'new_item_name'     => 'Novo nome de Finalidade',
            'parent_item'       => 'Finalidade ascendente',
            'parent_item_colon' => 'Finalidade ascendente:',
            'search_items'      => 'Pesquisar Finalidades',
            'not_found'         => 'Não foi possível encontrar finalidades',
            'no_terms'          => 'Não há finalidades',
            'filter_by_item'    => 'Filtrar por finalidade',
            'items_list_navigation' => 'Navegação na lista de Finalidades',
            'items_list'            => 'Lista de Finalidades',
            'back_to_items'         => '← Ir para finalidades',
            'item_link'             => 'Link de Finalidade',
            'item_link_description' => 'Um link para um finalidade',
        ),
        'public'            => true,
        'publicly_queryable' => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'show_tagcloud'     => true,
        'show_in_quick_edit' => true,
        'show_admin_column' => false,
        'rewrite'           => array( 'slug' => 'finalidade', 'with_front' => true, 'hierarchical' => false ),
        'capabilities'      => array(
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),
    ) );

    /* ── Taxonomia: cidade-e-bairro (Cidades e Bairros) ── */
    register_taxonomy( 'cidade-e-bairro', array( 'imovel' ), array(
        'labels' => array(
            'name'              => 'Cidades e Bairros',
            'singular_name'     => 'Cidade e Bairro',
            'menu_name'         => 'Cidades e Bairros',
            'all_items'         => 'Todos os Cidades e Bairros',
            'edit_item'         => 'Editar Cidade e Bairro',
            'view_item'         => 'Ver Cidade e Bairro',
            'update_item'       => 'Atualizar Cidade e Bairro',
            'add_new_item'      => 'Adicionar novo Cidade e Bairro',
            'new_item_name'     => 'Novo nome de Cidade e Bairro',
            'parent_item'       => 'Cidade e Bairro ascendente',
            'parent_item_colon' => 'Cidade e Bairro ascendente:',
            'search_items'      => 'Pesquisar Cidades e Bairros',
            'not_found'         => 'Não foi possível encontrar cidades e bairros',
            'no_terms'          => 'Não há cidades e bairros',
            'filter_by_item'    => 'Filtrar por cidade e bairro',
            'items_list_navigation' => 'Navegação na lista de Cidades e Bairros',
            'items_list'            => 'Lista de Cidades e Bairros',
            'back_to_items'         => '← Ir para cidades e bairros',
            'item_link'             => 'Link de Cidade e Bairro',
            'item_link_description' => 'Um link para um cidade e bairro',
        ),
        'description'       => 'Crie as Cidades que possui imóvel e como subcategoria o Bairro.',
        'public'            => true,
        'publicly_queryable' => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'show_tagcloud'     => true,
        'show_in_quick_edit' => true,
        'show_admin_column' => false,
        'rewrite'           => array( 'slug' => 'cidade-e-bairro', 'with_front' => true, 'hierarchical' => false ),
        'capabilities'      => array(
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),
    ) );
}

/* ──────────────────────────────────────────────
 * 2. REWRITE RULES — URL limpa /feed-imovelweb/
 * ────────────────────────────────────────────── */
add_action( 'init', 'ptim_register_rewrite' );
function ptim_register_rewrite() {
    add_rewrite_rule(
        '^' . PTIM_FEED_SLUG . '/?$',
        'index.php?ptim_feed=1',
        'top'
    );
}

add_filter( 'query_vars', 'ptim_query_vars' );
function ptim_query_vars( $vars ) {
    $vars[] = 'ptim_feed';
    return $vars;
}

/* Flush rewrite rules na ativação/desativação */
register_activation_hook( __FILE__, 'ptim_activate' );
function ptim_activate() {
    ptim_register_rewrite();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'ptim_deactivate' );
function ptim_deactivate() {
    flush_rewrite_rules();
}

/* ──────────────────────────────────────────────
 * 3. INTERCEPTAR REQUEST — servir XML
 * ────────────────────────────────────────────── */
/* ──────────────────────────────────────────────
 * 3a. REST API — /wp-json/portalimoveis/v1/feed
 * ────────────────────────────────────────────── */
add_action( "rest_api_init", "ptim_register_rest_route" );
function ptim_register_rest_route() {
    register_rest_route( "portalimoveis/v1", "/feed", array(
        "methods"             => "GET",
        "callback"            => "ptim_rest_callback",
        "permission_callback" => "__return_true",
    ) );
}
function ptim_rest_callback( $request ) {
    while ( ob_get_level() ) { ob_end_clean(); }
    header( "Content-Type: application/xml; charset=utf-8" );
    header( "Cache-Control: public, max-age=3600" );
    header( "X-Robots-Tag: noindex, nofollow" );
    echo ptim_generate_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML feed output
    exit;
}

add_action( 'template_redirect', 'ptim_serve_feed' );
function ptim_serve_feed() {
    if ( ! get_query_var( 'ptim_feed' ) ) {
        return;
    }

    // Limpar qualquer buffer pendente
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    // Headers
    header( 'Content-Type: application/xml; charset=utf-8' );
    header( 'Cache-Control: public, max-age=3600' );
    header( 'X-Robots-Tag: noindex, nofollow' );

    // Gerar e enviar
    echo ptim_generate_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML feed output
    exit;
}

/* ──────────────────────────────────────────────
 * 4. GERAÇÃO DO XML
 * ────────────────────────────────────────────── */
function ptim_generate_xml() {
    $posts = get_posts( array(
        'post_type'      => 'imovel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<OpenNavent>' . "\n";
    $xml .= '  <dataModificacao>' . ptim_cdata( round( microtime( true ) * 1000 ) ) . '</dataModificacao>' . "\n";
    $xml .= '  <Imoveis>' . "\n";

    foreach ( $posts as $post ) {
        $errs = ptim_validate_imovel( $post->ID );
        if ( ! empty( $errs ) ) continue; // Pular imóveis com campos obrigatórios faltando
        $xml .= ptim_render_imovel( $post );
    }

    $xml .= '  </Imoveis>' . "\n";
    $xml .= '</OpenNavent>';

    return $xml;
}


/* ──────────────────────────────────────────────
 * 4b. VALIDAR CAMPOS OBRIGATÓRIOS
 * ────────────────────────────────────────────── */

function ptim_validate_imovel( $post_id ) {
    $errors = array();
    $title = get_the_title( $post_id );
    if ( mb_strlen( $title ) < 5 )   $errors[] = 'Título muito curto (mín. 5 caracteres)';

    $desc = get_field( 'descricao', $post_id );
    if ( empty( $desc ) ) $desc = wp_strip_all_tags( get_post_field( 'post_content', $post_id ), true );
    if ( mb_strlen( $desc ) < 50 )   $errors[] = 'Descrição muito curta (mín. 50 caracteres)';

    $sell = get_field( 'sell_price', $post_id );
    $rent = get_field( 'rent_price', $post_id );
    if ( empty( $sell ) && empty( $rent ) ) $errors[] = 'Preço de venda ou locação obrigatório';

    $gallery = get_field( 'galeria_de_imagens', $post_id );
    $img_count = is_array( $gallery ) ? count( $gallery ) : 0;
    if ( $img_count < 5 )            $errors[] = 'Mínimo 5 fotos na galeria (tem ' . $img_count . ')';

    // Tipo de propriedade (Obrigatorio)
    $tipos = wp_get_post_terms( $post_id, 'tipo', array( 'fields' => 'ids' ) );
    if ( empty( $tipos ) || is_wp_error( $tipos ) ) $errors[] = 'Tipo do imóvel não selecionado';

    // Finalidade (Obrigatorio)
    $fins = wp_get_post_terms( $post_id, 'finalidade', array( 'fields' => 'ids' ) );
    if ( empty( $fins ) || is_wp_error( $fins ) ) $errors[] = 'Finalidade não selecionada';

    // Endereço completo (Obrigatorio)
    if ( empty( get_field( 'cep', $post_id ) ) )      $errors[] = 'CEP não preenchido';
    if ( empty( get_field( 'location', $post_id ) ) )  $errors[] = 'Rua não preenchida';
    if ( empty( get_field( 'bairro', $post_id ) ) )    $errors[] = 'Bairro não preenchido';
    if ( empty( get_field( 'cidade', $post_id ) ) )    $errors[] = 'Cidade não preenchida';
    if ( empty( get_field( 'estado', $post_id ) ) )    $errors[] = 'Estado não preenchido';

    if ( empty( get_field( 'metreage', $post_id ) ) )  $errors[] = 'Área privativa (m²) não preenchida';

    // IPTU (importante para posicionamento)
    if ( empty( get_field( 'iptu', $post_id ) ) )      $errors[] = 'IPTU não preenchido';

    // Idade do imóvel
    if ( empty( get_field( 'idade', $post_id ) ) )     $errors[] = 'Idade do imóvel não preenchida';

    // Condomínio — obrigatório para Apartamento e subtipos de Casa em condomínio
    $tipo_slugs = wp_get_post_terms( $post_id, 'tipo', array( 'fields' => 'slugs' ) );
    $needs_condo = false;
    if ( ! is_wp_error( $tipo_slugs ) ) {
        $condo_slugs = array( 'apartamento', 'studio', 'loft', 'flat', 'cobertura', 'duplex', 'triplex', 'garden', 'casa-de-condominio' );
        foreach ( $tipo_slugs as $slug ) {
            if ( in_array( $slug, $condo_slugs ) ) { $needs_condo = true; break; }
        }
    }
    if ( $needs_condo && empty( get_field( 'condominium', $post_id ) ) ) {
        $errors[] = 'Condomínio obrigatório para este tipo de imóvel';
    }

    return $errors;
}


/* ──────────────────────────────────────────────
 * 5. RENDERIZAR UM IMÓVEL
 * ────────────────────────────────────────────── */
function ptim_render_imovel( $post ) {
    $id = $post->ID;

    // Campos ACF
    $referencia = get_field( 'referencia', $id );
    $location   = get_field( 'location', $id );
    $numero     = get_field( 'numero', $id );
    $sell_price = get_field( 'sell_price', $id );
    $rent_price = get_field( 'rent_price', $id );
    $rooms      = get_field( 'rooms', $id );
    $suits      = get_field( 'suits', $id );
    $bathroom   = get_field( 'bathroom', $id );
    $metreage   = get_field( 'metreage', $id );
    $parking    = get_field( 'parking', $id );
    $descricao  = get_field( 'descricao', $id );
    $iptu       = get_field( 'iptu', $id );
    $condominium = get_field( 'condominium', $id );
    $idade      = get_field( 'idade', $id );
    $amenities  = get_field( 'amenities', $id );
    $infra      = get_field( 'Infraestrutura', $id );
    $complemento = get_field( 'complemento', $id );

    // Fallback descrição: campo ACF > post_content > título
    if ( empty( $descricao ) ) {
        $descricao = wp_strip_all_tags( $post->post_content, true );
    }
    if ( empty( $descricao ) ) {
        $descricao = $post->post_title;
    }
    $descricao = mb_substr( $descricao, 0, 4000 );

    // Tipo de propriedade
    $tipo = ptim_get_tipo( $id );

    // Operação (Venta/Alquiler)
    $operacao = ptim_get_operacao( $id );

    // Localidade (cidade/bairro)
    // Localidade no formato: Bairro,Cidade,Estado,País
    $loc_parts = ptim_get_localizacao_parts( $id );
    $bairro = $loc_parts['bairro'];
    $cidade = $loc_parts['cidade'];
    $localidade_parts = array_filter( array( $bairro, $cidade, 'São Paulo', 'Brasil' ) );
    $localidade = implode( ',', $localidade_parts );

    // Montar endereço: rua + número + complemento
    $endereco = rtrim( trim( $location ), ',' );
    if ( $numero ) {
        $endereco .= ', ' . $numero;
    }
    if ( $complemento ) {
        $endereco .= ' - ' . $complemento;
    }

    // CEP
    $cep = ptim_extract_cep( $location ?: '' );

    // Montar XML do imóvel
    $x  = '    <Imovel>' . "\n";
    $x .= '      <codigoAnuncio>' . ptim_cdata( $id ) . '</codigoAnuncio>' . "\n";
    $x .= '      <codigoReferencia>' . ptim_cdata( $referencia ?: $id ) . '</codigoReferencia>' . "\n";

    // Tipo
    $x .= '      <tipoPropriedade>' . "\n";
    $x .= '        <idTipo>' . ptim_cdata( $tipo['id'] ) . '</idTipo>' . "\n";
    if ( ! empty( $tipo['subtipo'] ) ) {
        $x .= '        <idSubTipo>' . ptim_cdata( $tipo['subtipo'] ) . '</idSubTipo>' . "\n";
    }
    $x .= '        <tipo>' . ptim_cdata( $tipo['nome'] ) . '</tipo>' . "\n";
    $x .= '      </tipoPropriedade>' . "\n";

    // Título e descrição
    $x .= '      <titulo>' . ptim_cdata( mb_substr( esc_html( $post->post_title ), 0, 80 ) ) . '</titulo>' . "\n";
    $x .= '      <descricao>' . ptim_cdata( ptim_clean_text( $descricao ) ) . '</descricao>' . "\n";

    // Preços
    $x .= '      <precos>' . "\n";
    if ( $sell_price ) {
        $x .= '        <preco>' . "\n";
        $x .= '          <quantidade>' . ptim_cdata( intval( $sell_price ) ) . '</quantidade>' . "\n";
        $x .= '          <moeda>' . ptim_cdata( 'BRL' ) . '</moeda>' . "\n";
        $x .= '          <operacao>' . ptim_cdata( 'VENTA' ) . '</operacao>' . "\n";
        $x .= '        </preco>' . "\n";
    }
    if ( $rent_price ) {
        $x .= '        <preco>' . "\n";
        $x .= '          <quantidade>' . ptim_cdata( intval( $rent_price ) ) . '</quantidade>' . "\n";
        $x .= '          <moeda>' . ptim_cdata( 'BRL' ) . '</moeda>' . "\n";
        $x .= '          <operacao>' . ptim_cdata( 'ALQUILER' ) . '</operacao>' . "\n";
        $x .= '        </preco>' . "\n";
    }
    $x .= '      </precos>' . "\n";

    // Publicação (obrigatório)
    $x .= '      <publicacao>' . "\n";
    $x .= '        <tipoPublicacao>' . ptim_cdata( 'SIMPLE' ) . '</tipoPublicacao>' . "\n";
    $x .= '      </publicacao>' . "\n";

    // Publicador
    $settings = get_option( 'ptim_settings', array() );
    $cod_imob = ! empty( $settings['codigo_imobiliaria'] ) ? $settings['codigo_imobiliaria'] : '';
    $email_contato = ! empty( $settings['email_contato'] ) ? $settings['email_contato'] : '';
    $nome_contato = ! empty( $settings['nome_contato'] ) ? $settings['nome_contato'] : '';
    $telefone_contato = ! empty( $settings['telefone_contato'] ) ? $settings['telefone_contato'] : '';
    $x .= '      <publicador>' . "\n";
    $x .= '        <codigoImobiliaria>' . ptim_cdata( $cod_imob ) . '</codigoImobiliaria>' . "\n";
    if ( $email_contato )    $x .= '        <emailContato>' . ptim_cdata( $email_contato ) . '</emailContato>' . "\n";
    if ( $nome_contato )     $x .= '        <nomeContato>' . ptim_cdata( $nome_contato ) . '</nomeContato>' . "\n";
    if ( $telefone_contato ) $x .= '        <telefoneContato>' . ptim_cdata( $telefone_contato ) . '</telefoneContato>' . "\n";
    $x .= '      </publicador>' . "\n";


    // Localização
    $x .= '      <localizacao>' . "\n";
    $x .= '        <endereco>' . ptim_cdata( $endereco ) . '</endereco>' . "\n";
    $x .= '        <localidade>' . ptim_cdata( $localidade ) . '</localidade>' . "\n";
    if ( $cep ) {
        $x .= '        <codigoPostal>' . ptim_cdata( $cep ) . '</codigoPostal>' . "\n";
    }
    $x .= '        <mostrarMapa>' . ptim_cdata( 'APROXIMADO' ) . '</mostrarMapa>' . "\n";
    $x .= '      </localizacao>' . "\n";

    // Multimídia (fotos + vídeos + plantas)
    $gallery = get_field( 'galeria_de_imagens', $id );
    if ( is_array( $gallery ) && count( $gallery ) > 50 ) $gallery = array_slice( $gallery, 0, 50 );
    $video_raw = get_field( 'video_youtube', $id );
    $video_code = ptim_extract_youtube_code( $video_raw );
    $plantas = get_field( 'plantas', $id );

    $has_media = ( ! empty( $gallery ) && is_array( $gallery ) ) || $video_code || ( ! empty( $plantas ) && is_array( $plantas ) );
    if ( $has_media ) {
        $x .= '      <multimidia>' . "\n";

        // Imagens
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
                    $x .= '            <urlImagem>' . ptim_cdata( $url ) . '</urlImagem>' . "\n";
                    $x .= '          </imagem>' . "\n";
                }
            }
            $x .= '        </imagens>' . "\n";
        }

        // Vídeos (YouTube)
        if ( $video_code ) {
            $x .= '        <videos>' . "\n";
            $x .= '          <video>' . "\n";
            $x .= '            <codigoVideo>' . ptim_cdata( $video_code ) . '</codigoVideo>' . "\n";
            $x .= '            <titulo>' . ptim_cdata( mb_substr( $post->post_title, 0, 80 ) ) . '</titulo>' . "\n";
            $x .= '          </video>' . "\n";
            $x .= '        </videos>' . "\n";
        }

        // Plantas
        if ( ! empty( $plantas ) && is_array( $plantas ) ) {
            $x .= '        <plantas>' . "\n";
            foreach ( $plantas as $pl ) {
                $pl_url = '';
                $pl_title = '';
                if ( is_array( $pl ) ) {
                    $pl_url = ! empty( $pl['url'] ) ? $pl['url'] : '';
                    $pl_title = ! empty( $pl['title'] ) ? $pl['title'] : ( ! empty( $pl['alt'] ) ? $pl['alt'] : 'Planta' );
                } elseif ( is_numeric( $pl ) ) {
                    $pl_url = wp_get_attachment_url( $pl );
                    $pl_title = get_the_title( $pl ) ?: 'Planta';
                }
                if ( $pl_url ) {
                    $x .= '          <planta>' . "\n";
                    $x .= '            <urlImagem>' . ptim_cdata( $pl_url ) . '</urlImagem>' . "\n";
                    $x .= '            <titulo>' . ptim_cdata( $pl_title ) . '</titulo>' . "\n";
                    $x .= '          </planta>' . "\n";
                }
            }
            $x .= '        </plantas>' . "\n";
        }

        $x .= '      </multimidia>' . "\n";
    }

    // Características
    $x .= '      <caracteristicas>' . "\n";
    if ( $rooms )    $x .= '        <caracteristica><id>' . ptim_cdata('CFT2') . '</id><nome>' . ptim_cdata('PRINCIPALES|QUARTO') . '</nome><valor>' . ptim_cdata( intval($rooms) ) . '</valor></caracteristica>' . "\n";
    if ( $suits )    $x .= '        <caracteristica><id>' . ptim_cdata('CFT4') . '</id><nome>' . ptim_cdata('PRINCIPALES|SUITE') . '</nome><valor>' . ptim_cdata( intval($suits) ) . '</valor></caracteristica>' . "\n";
    if ( $bathroom ) $x .= '        <caracteristica><id>' . ptim_cdata('CFT3') . '</id><nome>' . ptim_cdata('PRINCIPALES|BANHEIRO') . '</nome><valor>' . ptim_cdata( intval($bathroom) ) . '</valor></caracteristica>' . "\n";
    if ( $metreage ) {
        $x .= '        <caracteristica><id>' . ptim_cdata('CFT101') . '</id><nome>' . ptim_cdata('MEDIDAS|AREA_UTIL') . '</nome><valor>' . ptim_cdata( intval($metreage) ) . '</valor></caracteristica>' . "\n";
        $x .= '        <caracteristica><id>' . ptim_cdata('CFT100') . '</id><nome>' . ptim_cdata('MEDIDAS|AREA_TOTAL') . '</nome><valor>' . ptim_cdata( intval($metreage) ) . '</valor></caracteristica>' . "\n";
        $x .= '        <caracteristica><id>' . ptim_cdata('CON1') . '</id><nome>' . ptim_cdata('MEDIDAS|UNIDAD_DE_MEDIDA') . '</nome><idValor>' . ptim_cdata( 'M2' ) . '</idValor></caracteristica>' . "\n";
    }
    if ( $parking )  $x .= '        <caracteristica><id>' . ptim_cdata('CFT7') . '</id><nome>' . ptim_cdata('PRINCIPALES|VAGA') . '</nome><valor>' . ptim_cdata( intval($parking) ) . '</valor></caracteristica>' . "\n";
    if ( $iptu )     $x .= '        <caracteristica><id>' . ptim_cdata('CFT400') . '</id><nome>' . ptim_cdata('PRINCIPALES|IPTU') . '</nome><valor>' . ptim_cdata( intval($iptu) ) . '</valor></caracteristica>' . "\n";
    if ( $condominium ) $x .= '        <caracteristica><id>' . ptim_cdata('CFT6') . '</id><nome>' . ptim_cdata('PRINCIPALES|CONDOMINIO') . '</nome><valor>' . ptim_cdata( intval($condominium) ) . '</valor></caracteristica>' . "\n";
    if ( $idade )    $x .= '        <caracteristica><id>' . ptim_cdata('CFT5') . '</id><nome>' . ptim_cdata('PRINCIPALES|IDADE') . '</nome><valor>' . ptim_cdata( intval($idade) ) . '</valor></caracteristica>' . "\n";

    // Amenidades (área privativa → 20xxx)
    $amenity_map = array(
        'Aquecedor' => 20010, 'Ar condicionado' => 20012, 'Biblioteca' => 20026,
        'Churrasqueira' => 20048, 'Closet' => 20050, 'Cozinha americana' => 20056,
        'Cozinha gourmet' => 20057, 'Cozinha independente' => 20058,
        'Dependência de empregados' => 20062, 'Despensa' => 20065,
        'Entrada independente' => 20228, 'Escritório' => 20077,
        'Espaço gourmet' => 20080, 'Jardim' => 20110, 'Lareira' => 20114,
        'Lava-louça' => 20116, 'Lavanderia' => 20117, 'Mezanino' => 20124,
        'Mobiliado' => 20126, 'Permite animais' => 20135,
        'Piscina (privativa)' => 20140, 'Playground (privativo)' => 20152,
        'Quintal' => 20166, 'Sala de jantar' => 20177, 'Varanda' => 20199,
        'Área de serviço' => 20017,
    );
    if ( is_array( $amenities ) ) {
        foreach ( $amenities as $a ) {
            if ( isset( $amenity_map[ $a ] ) ) {
                $aid = $amenity_map[ $a ];
                $x .= '        <caracteristica><id>' . ptim_cdata( $aid ) . '</id><nome>' . ptim_cdata('AREA_PRIVATIVA|' . strtoupper(str_replace(' ', '_', $a))) . '</nome></caracteristica>' . "\n";
            }
        }
    }

    // Infraestrutura (áreas comuns → 10xxx / 30xxx)
    $infra_map = array(
        'Academia' => 10090, 'Acesso PNE' => 10005, 'Aquecimento central' => 20011,
        'Bicicletário' => 10027, 'Brinquedoteca' => 10028, 'Campo de futebol' => 10031,
        'Churrasqueira (comum)' => 10048, 'Câmeras de segurança' => 10030,
        'Elevador' => 10071, 'Entrada de serviço' => 20074,
        'Espaço gourmet (comum)' => 10080,
        'Estacionamento para visitantes' => 10084,
        'Guarita' => 10103, 'Hidromassagem' => 20106, 'Horta' => 20107,
        'Piscina' => 30025, 'Piscina (comum)' => 30025, 'Playground' => 10152,
        'Portaria 24h' => 10158, 'Quadra de tênis' => 10164,
        'Quadra poliesportiva' => 10165, 'SPA' => 10189,
        'Salão de festas' => 10181, 'Salão de jogos' => 10182,
        'Sauna' => 10183, 'Solarium' => 10187, 'Vestiário' => 10206,
        'Vigilância 24h' => 10208, 'Área de lazer' => 10016, 'Área verde' => 10018,
    );
    if ( is_array( $infra ) ) {
        foreach ( $infra as $i ) {
            if ( isset( $infra_map[ $i ] ) ) {
                $iid = $infra_map[ $i ];
                $prefix = $iid >= 20000 ? 'AREA_PRIVATIVA' : ( $iid >= 10000 ? 'AREAS_COMUNS' : 'AGRUPADA' );
                $x .= '        <caracteristica><id>' . ptim_cdata( $iid ) . '</id><nome>' . ptim_cdata($prefix . '|' . strtoupper(str_replace(' ', '_', $i))) . '</nome></caracteristica>' . "\n";
            }
        }
    }

    $x .= '      </caracteristicas>' . "\n";

    $x .= '    </Imovel>' . "\n";
    return $x;
}

/* ──────────────────────────────────────────────
 * 6. FUNÇÕES AUXILIARES
 * ────────────────────────────────────────────── */
/**
 * Extrair código do vídeo YouTube de uma URL ou código direto.
 */
function ptim_extract_youtube_code( $input ) {
    if ( empty( $input ) ) return '';
    $input = trim( $input );
    // Se já é só o código (11 chars, alfanumérico + - _)
    if ( preg_match( '/^[a-zA-Z0-9_-]{10,12}$/', $input ) ) return $input;
    // youtube.com/watch?v=XXXXX
    if ( preg_match( '/[?&]v=([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) return $m[1];
    // youtu.be/XXXXX
    if ( preg_match( '/youtu\.be\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) return $m[1];
    // youtube.com/embed/XXXXX
    if ( preg_match( '/embed\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) return $m[1];
    // youtube.com/shorts/XXXXX
    if ( preg_match( '/shorts\/([a-zA-Z0-9_-]{10,12})/', $input, $m ) ) return $m[1];
    return '';
}

function ptim_cdata( $val ) {
    return '<![CDATA[' . $val . ']]>';
}

function ptim_get_tipo( $post_id ) {
    $terms = wp_get_post_terms( $post_id, 'tipo' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array( 'id' => '2', 'nome' => 'Apartamento', 'subtipo' => '1' );
    }

    // Mapeamento completo: slug da taxonomia → idTipo + idSubTipo da API OpenNavent
    // Subtipos buscam o tipo pai automaticamente via hierarquia da taxonomia
    $map = array(
        // === RESIDENCIAL: Casa (idTipo=1) ===
        'casa'             => array( 'id' => '1', 'nome' => 'Casa',         'subtipo' => '5' ),  // Padrão
        'casa-condominio'  => array( 'id' => '1', 'nome' => 'Casa',         'subtipo' => '6' ),  // Casa de Condomínio
        'casa-de-vila'     => array( 'id' => '1', 'nome' => 'Casa',         'subtipo' => '7' ),  // Casa de Vila
        'sobrado'          => array( 'id' => '1', 'nome' => 'Casa',         'subtipo' => '33' ), // Sobrado
        'quarto-casa'      => array( 'id' => '1', 'nome' => 'Casa',         'subtipo' => '37' ), // Quarto

        // === RESIDENCIAL: Apartamento (idTipo=2) ===
        'apartamento'      => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '1' ),  // Padrão
        'studio'           => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '2' ),  // Kitchenette/Studio
        'loft'             => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '3' ),  // Loft
        'flat'             => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '4' ),  // Flat
        'cobertura'        => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '26' ), // Cobertura
        'duplex'           => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '34' ), // Duplex
        'triplex'          => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '35' ), // Triplex
        'quarto-apt'       => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '36' ), // Quarto
        'garden'           => array( 'id' => '2', 'nome' => 'Apartamento',  'subtipo' => '38' ), // Garden

        // === RESIDENCIAL: Terreno (idTipo=1003) ===
        'terreno'          => array( 'id' => '1003', 'nome' => 'Terreno',   'subtipo' => '8' ),  // Terreno Padrão
        'loteamento'       => array( 'id' => '1003', 'nome' => 'Terreno',   'subtipo' => '9' ),  // Loteamento/Condomínio

        // === RESIDENCIAL: Rural (idTipo=1004) ===
        'rural'            => array( 'id' => '1004', 'nome' => 'Rural',     'subtipo' => '10' ), // Chácara (default)
        'chacara'          => array( 'id' => '1004', 'nome' => 'Rural',     'subtipo' => '10' ), // Chácara
        'sitio'            => array( 'id' => '1004', 'nome' => 'Rural',     'subtipo' => '11' ), // Sítio
        'fazenda'          => array( 'id' => '1004', 'nome' => 'Rural',     'subtipo' => '12' ), // Fazenda
        'haras'            => array( 'id' => '1004', 'nome' => 'Rural',     'subtipo' => '13' ), // Haras

        // === COMERCIAL (idTipo=1005) ===
        'comercial'            => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '16' ), // Conj. Comercial (default)
        'box-garagem'          => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '14' ), // Box/Garagem
        'predio-inteiro'       => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '15' ), // Prédio Inteiro
        'conjunto-comercial'   => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '16' ), // Conjunto Comercial/Sala
        'casa-comercial'       => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '17' ), // Casa Comercial
        'loja-shopping'        => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '18' ), // Loja de Shopping
        'loja-salao'           => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '19' ), // Loja/Salão
        'galpao'               => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '20' ), // Galpão/Depósito
        'hotel'                => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '22' ), // Hotel
        'motel'                => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '23' ), // Motel
        'pousada'              => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '24' ), // Pousada/Chalé
        'industria'            => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '25' ), // Indústria
        'area-industrial'      => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '27' ), // Área Industrial
        'consultorio'          => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '28' ), // Consultório
        'clinica'              => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '29' ), // Clínica
        'andar-corrido'        => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '30' ), // Andar
        'ponto-comercial'      => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '31' ), // Ponto Comercial
        'area-comercial'       => array( 'id' => '1005', 'nome' => 'Comercial', 'subtipo' => '32' ), // Área Comercial
    );

    // Busca por slug exato primeiro, depois por match parcial
    $slug = $terms[0]->slug;
    if ( isset( $map[ $slug ] ) ) {
        return $map[ $slug ];
    }
    foreach ( $map as $key => $val ) {
        if ( strpos( $slug, $key ) !== false ) return $val;
    }
    return array( 'id' => '2', 'nome' => 'Apartamento', 'subtipo' => '1' );
}

function ptim_get_operacao( $post_id ) {
    $terms = wp_get_post_terms( $post_id, 'finalidade' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) return 'Venta';
    $nome = strtolower( $terms[0]->name );
    if ( strpos( $nome, 'alug' ) !== false || strpos( $nome, 'loca' ) !== false ) return 'Alquiler';
    return 'Venta';
}

function ptim_clean_text( $text ) {
    $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, "UTF-8" );
    $text = wp_strip_all_tags( $text, true );
    $text = preg_replace( "/[\r\n]+/", "\n", $text );
    $text = trim( $text );
    return $text;
}

function ptim_get_localizacao_parts( $post_id ) {
    // Prioridade 1: campos ACF preenchidos via CEP
    $bairro = get_field( 'bairro', $post_id );
    $cidade = get_field( 'cidade', $post_id );
    if ( ! empty( $cidade ) ) {
        return array( 'bairro' => $bairro ?: '', 'cidade' => $cidade );
    }

    // Prioridade 2: taxonomia cidade-e-bairro (legado)
    $bairro = '';
    $cidade = '';
    $terms = wp_get_post_terms( $post_id, 'cidade-e-bairro' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array( 'bairro' => '', 'cidade' => '' );
    }
    foreach ( $terms as $t ) {
        if ( $t->parent > 0 ) {
            $bairro = $t->name;
            $parent = get_term( $t->parent, 'cidade-e-bairro' );
            if ( ! is_wp_error( $parent ) ) {
                $cidade = $parent->name;
            }
        } else {
            if ( empty( $cidade ) ) {
                $cidade = $t->name;
            }
        }
    }
    return array( 'bairro' => $bairro, 'cidade' => $cidade );
}

function ptim_extract_rua( $location ) {
    if ( empty( $location ) ) return '';
    // Remove CEP do final (ex: "- 09895-400")
    $location = preg_replace( '/\s*-?\s*\d{5}-?\d{3}\s*$/', '', $location );
    $parts = explode( ',', $location );
    // Retorna só a rua (primeira parte)
    return trim( $parts[0] );
}

function ptim_extract_cep( $location ) {
    if ( preg_match( '/(\d{5})-?(\d{3})/', $location, $m ) ) {
        return $m[1] . '-' . $m[2];
    }
    return '';
}


/* ──────────────────────────────────────────────
 * 7. ACF FIELD GROUP — Registrar todos os campos via código
 * ────────────────────────────────────────────── */
add_action( 'acf/init', 'ptim_register_acf_fields' );
function ptim_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group( array(
        'key'                   => 'group_ptim_dados_imovel',
        'title'                 => 'Dados do Imóvel',
        'fields'                => array(
            array(
                'key' => 'field_69346d00741b8',
                'label' => 'Referência',
                'name' => 'referencia',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a34f9ef21',
                'label' => 'Preço Venda',
                'name' => 'sell_price',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => 'Apenas números, sem pontuação.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 0,
                'placeholder' => 1000000,
                'step' => '',
                'prepend' => 'R$',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a49b9ef2a',
                'label' => 'Preço Locação',
                'name' => 'rent_price',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => 'Apenas números, sem pontuação.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 0,
                'placeholder' => 1000,
                'step' => '',
                'prepend' => 'R$',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a5109ef2c',
                'label' => 'IPTU',
                'name' => 'iptu',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => 'Apenas números, sem pontuação.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => 'R$',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a4889ef29',
                'label' => 'Condomínio',
                'name' => 'condominium',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => 'Apenas números, sem pontuação.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => 'R$',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a3c29ef22',
                'label' => 'Área privativa',
                'name' => 'metreage',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '16',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => 1,
                'prepend' => '',
                'append' => 'm²',
            ),
            array(
                'key' => 'field_6206a4379ef25',
                'label' => 'Vagas',
                'name' => 'parking',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a40b9ef23',
                'label' => 'Quartos',
                'name' => 'rooms',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a4709ef28',
                'label' => 'Banheiros',
                'name' => 'bathroom',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a42b9ef24',
                'label' => 'Suítes',
                'name' => 'suits',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_69344a6423ef3',
                'label' => 'Torres',
                'name' => 'torres',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_69344a7323ef4',
                'label' => 'Andares',
                'name' => 'andares',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_69344a7d23ef5',
                'label' => 'Idade',
                'name' => 'idade',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '12',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'min' => '',
                'max' => '',
                'allow_in_bindings' => 1,
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_6206a4409ef26',
                'label' => 'Amenidades',
                'name' => 'amenities',
                'aria-label' => '',
                'type' => 'checkbox',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'Aquecedor' => 'Aquecedor',
                    'Ar condicionado' => 'Ar condicionado',
                    'Biblioteca' => 'Biblioteca',
                    'Churrasqueira' => 'Churrasqueira',
                    'Closet' => 'Closet',
                    'Cozinha americana' => 'Cozinha americana',
                    'Cozinha gourmet' => 'Cozinha gourmet',
                    'Cozinha independente' => 'Cozinha independente',
                    'Cozinha planejada' => 'Cozinha planejada',
                    'Dependência de empregados' => 'Dependência de empregados',
                    'Despensa' => 'Despensa',
                    'Entrada independente' => 'Entrada independente',
                    'Escritório' => 'Escritório',
                    'Espaço gourmet' => 'Espaço gourmet',
                    'Jardim' => 'Jardim',
                    'Lareira' => 'Lareira',
                    'Lava-louça' => 'Lava-louça',
                    'Lavabo' => 'Lavabo',
                    'Lavanderia' => 'Lavanderia',
                    'Mezanino' => 'Mezanino',
                    'Mobiliado' => 'Mobiliado',
                    'Permite animais' => 'Permite animais',
                    'Piscina (privativa)' => 'Piscina (privativa)',
                    'Playground (privativo)' => 'Playground (privativo)',
                    'Quintal' => 'Quintal',
                    'Sala de estar' => 'Sala de estar',
                    'Sala de jantar' => 'Sala de jantar',
                    'Varanda' => 'Varanda',
                    'Varanda gourmet' => 'Varanda gourmet',
                    'Área de serviço' => 'Área de serviço',
                ),
                'default_value' => array(),
                'return_format' => 'value',
                'allow_custom' => 1,
                'save_custom' => 1,
                'allow_in_bindings' => 0,
                'layout' => 'horizontal',
                'toggle' => 0,
                'custom_choice_button_text' => 'Adicionar nova escolha',
            ),
            array(
                'key' => 'field_6934236aada5c',
                'label' => 'Descrição',
                'name' => 'descricao',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'allow_in_bindings' => 0,
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_ptim_cep',
                'label' => 'CEP',
                'name' => 'cep',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '20',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => 9,
                'placeholder' => '00000-000',
                'prepend' => '',
                'append' => '',
            ),

            array(
                'key' => 'field_ptim_bairro',
                'label' => 'Bairro',
                'name' => 'bairro',
                'type' => 'text',
                'instructions' => 'Auto-preenchido pelo CEP.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '30',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'readonly' => 1,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ),

            array(
                'key' => 'field_ptim_cidade',
                'label' => 'Cidade',
                'name' => 'cidade',
                'type' => 'text',
                'instructions' => 'Auto-preenchido pelo CEP.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '30',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'readonly' => 1,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ),

            array(
                'key' => 'field_ptim_estado',
                'label' => 'Estado',
                'name' => 'estado',
                'type' => 'text',
                'instructions' => 'Auto-preenchido pelo CEP.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '20',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'readonly' => 1,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ),

            array(
                'key' => 'field_6206a5c2635cb',
                'label' => 'Rua',
                'name' => 'location',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => 'Auto-preenchido pelo CEP.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '70',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'placeholder' => 'Preenchido pelo CEP',
                'prepend' => '',
                'append' => '',
                'readonly' => 0,
            ),

            array(
                'key' => 'field_ptim_numero',
                'label' => 'Número',
                'name' => 'numero',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => 'Opcional, mas recomendado para melhor posicionamento nas buscas.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '15',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '10',
                'allow_in_bindings' => 0,
                'placeholder' => 'Nº',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_693423b6a9e79',
                'label' => 'Infraestrutura',
                'name' => 'infraestrutura',
                'aria-label' => '',
                'type' => 'checkbox',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'Academia' => 'Academia',
                    'Acesso PNE' => 'Acesso PNE',
                    'Aquecimento central' => 'Aquecimento central',
                    'Banheiro na área comum' => 'Banheiro na área comum',
                    'Bicicletário' => 'Bicicletário',
                    'Biometria' => 'Biometria',
                    'Biribol' => 'Biribol',
                    'Brinquedoteca' => 'Brinquedoteca',
                    'Campo de futebol' => 'Campo de futebol',
                    'Churrasqueira (comum)' => 'Churrasqueira (comum)',
                    'Cinema' => 'Cinema',
                    'Condomínio fechado' => 'Condomínio fechado',
                    'Câmeras de segurança' => 'Câmeras de segurança',
                    'Depósito privativo' => 'Depósito privativo',
                    'Ducha' => 'Ducha',
                    'Elevador' => 'Elevador',
                    'Elevador de serviço' => 'Elevador de serviço',
                    'Entrada de serviço' => 'Entrada de serviço',
                    'Espaço car' => 'Espaço car',
                    'Espaço gourmet (comum)' => 'Espaço gourmet (comum)',
                    'Espaço mulher' => 'Espaço mulher',
                    'Estacionamento coberto' => 'Estacionamento coberto',
                    'Estacionamento para visitantes' => 'Estacionamento para visitantes',
                    'Facial' => 'Facial',
                    'Guarita' => 'Guarita',
                    'Hidromassagem' => 'Hidromassagem',
                    'Horta' => 'Horta',
                    'Interfone' => 'Interfone',
                    'Jardim' => 'Jardim',
                    'Lava rápido' => 'Lava rápido',
                    'Mercadinho 24h' => 'Mercadinho 24h',
                    'Parede de escalada' => 'Parede de escalada',
                    'Piscina' => 'Piscina',
                    'Piscina (comum)' => 'Piscina (comum)',
                    'Piscina infantil' => 'Piscina infantil',
                    'Pista de skate' => 'Pista de skate',
                    'Pista para caminhada' => 'Pista para caminhada',
                    'Playground' => 'Playground',
                    'Portaria' => 'Portaria',
                    'Portaria 24h' => 'Portaria 24h',
                    'Quadra de basquete' => 'Quadra de basquete',
                    'Quadra de tênis' => 'Quadra de tênis',
                    'Quadra poliesportiva' => 'Quadra poliesportiva',
                    'SPA' => 'SPA',
                    'Salão de festas' => 'Salão de festas',
                    'Salão de jogos' => 'Salão de jogos',
                    'Sauna' => 'Sauna',
                    'Solarium' => 'Solarium',
                    'Vagas PNE' => 'Vagas PNE',
                    'Vestiário' => 'Vestiário',
                    'Vigilância 24h' => 'Vigilância 24h',
                    'Zelador' => 'Zelador',
                    'Área de lazer' => 'Área de lazer',
                    'Área pet' => 'Área pet',
                    'Área verde' => 'Área verde',
                ),
                'default_value' => array(),
                'return_format' => 'value',
                'allow_custom' => 1,
                'save_custom' => 1,
                'allow_in_bindings' => 0,
                'layout' => 'horizontal',
                'toggle' => 0,
                'custom_choice_button_text' => 'Adicionar nova escolha',
            ),
            array(
                'key' => 'field_69341be209f2d',
                'label' => 'Galeria de Imagens',
                'name' => 'galeria_de_imagens',
                'aria-label' => '',
                'type' => 'gallery',
                'instructions' => 'Recomendamos pelo menos 22 fotos de ângulos e ambientes diferentes. Imóveis com mais fotos têm até 17% mais chance de aparecer melhor posicionados nos resultados de busca.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min' => '',
                'max' => '',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'insert' => 'append',
                'preview_size' => 'medium',
            ),
            array(
                'key' => 'field_6960faeaba8d9',
                'label' => 'Vídeo do YouTube',
                'name' => 'video_youtube',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => 'Cole o link do YouTube do vídeo do imóvel. O código será extraído automaticamente. Ex: https://www.youtube.com/watch?v=rs12I4SfBkCY',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://www.youtube.com/watch?v=...',
                'maxlength' => '',
                'allow_in_bindings' => 0,
            ),
            array(
                'key' => 'field_ptim_plantas',
                'label' => 'Plantas do Imóvel',
                'name' => 'plantas',
                'aria-label' => '',
                'type' => 'gallery',
                'instructions' => 'Envie as plantas (plantas baixas) do imóvel. Cada imagem será exportada com seu título no XML do feed.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min' => '',
                'max' => '',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => 'jpg,jpeg,png,webp',
                'insert' => 'append',
                'preview_size' => 'medium',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'imovel',
                ),
            ),
        ),
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
    ) );
}

/* ──────────────────────────────────────────────
 * 8. CEP AUTO-FILL — Admin JS via ViaCEP
 * ────────────────────────────────────────────── */
add_action( 'admin_enqueue_scripts', 'ptim_admin_cep_script' );
function ptim_admin_cep_script( $hook ) {
    global $post_type;
    if ( $post_type !== 'imovel' ) return;
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) return;

    $js = "
    (function($) {
        function maskCep(val) {
            val = val.replace(/\\D/g, '');
            if (val.length > 5) val = val.substr(0,5) + '-' + val.substr(5,3);
            return val;
        }

        function fetchCep(cep) {
            cep = cep.replace(/\\D/g, '');
            if (cep.length !== 8) return;

            $('[data-name=\"bairro\"] input').val('Buscando...').css('opacity','0.5');
            $('[data-name=\"cidade\"] input').val('Buscando...').css('opacity','0.5');
            $('[data-name=\"estado\"] input').val('...').css('opacity','0.5');

            $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(data) {
                if (data.erro) {
                    $('[data-name=\"bairro\"] input').val('CEP não encontrado').css('opacity','1');
                    $('[data-name=\"cidade\"] input').val('').css('opacity','1');
                    $('[data-name=\"estado\"] input').val('').css('opacity','1');
                    return;
                }
                $('[data-name=\"bairro\"] input').val(data.bairro || '').css('opacity','1').prop('readonly', false);
                $('[data-name=\"cidade\"] input').val(data.localidade || '').css('opacity','1').prop('readonly', false);
                $('[data-name=\"estado\"] input').val(data.estado || '').css('opacity','1').prop('readonly', false);

                // Preencher campo Rua com logradouro do ViaCEP
                var locField = $('[data-name=\"location\"] input');
                if (data.logradouro) {
                    locField.val(data.logradouro);
                }
            }).fail(function() {
                $('[data-name=\"bairro\"] input').val('Erro na consulta').css('opacity','1');
                $('[data-name=\"cidade\"] input').val('').css('opacity','1');
                $('[data-name=\"estado\"] input').val('').css('opacity','1');
            });
        }

        $(document).ready(function() {
            $(document).on('input', '[data-name=\"cep\"] input', function() {
                var val = maskCep($(this).val());
                $(this).val(val);
                if (val.replace(/\\D/g, '').length === 8) {
                    fetchCep(val);
                }
            });

            $(document).on('paste', '[data-name=\"cep\"] input', function() {
                var el = this;
                setTimeout(function() {
                    var val = maskCep($(el).val());
                    $(el).val(val);
                    if (val.replace(/\\D/g, '').length === 8) {
                        fetchCep(val);
                    }
                }, 100);
            });

            // Auto-fill se já tiver CEP ao carregar
            var existingCep = $('[data-name=\"cep\"] input').val();
            if (existingCep && existingCep.replace(/\\D/g, '').length === 8) {
                fetchCep(existingCep);
            }
        });
    })(jQuery);
    ";

    wp_add_inline_script( 'jquery', $js );
}

/* ──────────────────────────────────────────────
 * 9. AUTO-ASSIGN TAXONOMIA cidade-e-bairro no save
 * ────────────────────────────────────────────── */
add_action( 'acf/save_post', 'ptim_auto_assign_location_terms', 20 );
function ptim_auto_assign_location_terms( $post_id ) {
    if ( get_post_type( $post_id ) !== 'imovel' ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    $bairro = get_field( 'bairro', $post_id );
    $cidade = get_field( 'cidade', $post_id );

    if ( empty( $cidade ) ) return;

    $taxonomy = 'cidade-e-bairro';

    // Buscar ou criar termo da cidade (parent=0)
    $cidade_term = get_term_by( 'name', $cidade, $taxonomy );
    if ( ! $cidade_term ) {
        $result = wp_insert_term( $cidade, $taxonomy, array( 'parent' => 0 ) );
        if ( is_wp_error( $result ) ) return;
        $cidade_term_id = $result['term_id'];
    } else {
        $cidade_term_id = $cidade_term->term_id;
    }

    $terms_to_set = array( (int) $cidade_term_id );

    // Se tem bairro, buscar ou criar como filho da cidade
    if ( ! empty( $bairro ) ) {
        $bairro_term = get_term_by( 'name', $bairro, $taxonomy );
        if ( $bairro_term && $bairro_term->parent != $cidade_term_id ) {
            $bairro_term = null; // Mesmo nome em outra cidade
        }
        if ( ! $bairro_term ) {
            $result = wp_insert_term( $bairro, $taxonomy, array( 'parent' => $cidade_term_id ) );
            if ( ! is_wp_error( $result ) ) {
                $terms_to_set[] = (int) $result['term_id'];
            }
        } else {
            $terms_to_set[] = (int) $bairro_term->term_id;
        }
    }

    wp_set_object_terms( $post_id, $terms_to_set, $taxonomy );
}

/* ──────────────────────────────────────────────
 * ADMIN PAGES — Portal Imóveis
 * ────────────────────────────────────────────── */
add_action( 'admin_menu', 'ptim_admin_menu' );
function ptim_admin_menu() {
    add_menu_page(
        'Portal Imóveis',
        'Portal Imóveis',
        'manage_options',
        'ptim-portal',
        'ptim_page_main',
        'dashicons-building',
        4
    );
    add_submenu_page(
        'ptim-portal',
        'Portal Imóveis',
        'Visão Geral',
        'manage_options',
        'ptim-portal',
        'ptim_page_main'
    );
    add_submenu_page(
        'ptim-portal',
        'Configurações — Portal Imóveis',
        'Configurações',
        'manage_options',
        'ptim-settings',
        'ptim_page_settings'
    );
    add_submenu_page(
        'ptim-portal',
        'Mapeamento — Portal Imóveis',
        'Mapeamento',
        'manage_options',
        'ptim-mapping',
        'ptim_page_mapping'
    );
}

add_action( 'admin_init', 'ptim_register_settings' );
function ptim_register_settings() {
    register_setting( 'ptim_settings_group', 'ptim_settings', array( 'sanitize_callback' => 'ptim_sanitize_settings' ) );
}

function ptim_sanitize_settings( $input ) {
    $sanitized = array();
    if ( isset( $input['codigo_imobiliaria'] ) ) {
        $sanitized['codigo_imobiliaria'] = sanitize_text_field( $input['codigo_imobiliaria'] );
    }
    if ( isset( $input['email_contato'] ) ) {
        $sanitized['email_contato'] = sanitize_email( $input['email_contato'] );
    }
    if ( isset( $input['nome_contato'] ) ) {
        $sanitized['nome_contato'] = sanitize_text_field( $input['nome_contato'] );
    }
    if ( isset( $input['telefone_contato'] ) ) {
        $sanitized['telefone_contato'] = sanitize_text_field( $input['telefone_contato'] );
    }
    return $sanitized;
}


/* ── Página Principal: Visão Geral ── */
function ptim_page_main() {
    $feed_url = home_url( '/wp-json/portalimoveis/v1/feed' );
    $settings = get_option( 'ptim_settings', array() );
    $codigo   = ! empty( $settings['codigo_imobiliaria'] ) ? $settings['codigo_imobiliaria'] : '';

    // Contar imóveis válidos e inválidos
    $posts = get_posts( array(
        'post_type'      => 'imovel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    $total   = count( $posts );
    $valid   = 0;
    $invalid = array();
    foreach ( $posts as $pid ) {
        $errs = ptim_validate_imovel( $pid );
        if ( empty( $errs ) ) {
            $valid++;
        } else {
            $invalid[] = array( 'id' => $pid, 'title' => get_the_title( $pid ), 'errors' => $errs );
        }
    }

    echo '<div class="wrap">';
    echo '<h1>🏠 Portal Imóveis</h1>';
    echo '<p>Integração XML com portais imobiliários: <strong>ImovelWeb</strong>, <strong>Wimoveis</strong> e <strong>Casa Mineira</strong>.</p>';
    echo '<hr>';

    // Status
    echo '<h2>📊 Status do Feed</h2>';
    echo '<table class="widefat" style="max-width:500px">';
    echo '<tr><td><strong>Imóveis publicados</strong></td><td>' . esc_html( $total ) . '</td></tr>';
    echo '<tr><td><strong>Sincronizando no feed</strong></td><td><span style="color:green;font-weight:bold">' . esc_html( $valid ) . '</span></td></tr>';
    if ( count( $invalid ) > 0 ) {
        echo '<tr><td><strong>Com pendências</strong></td><td><span style="color:red;font-weight:bold">' . count( $invalid ) . '</span></td></tr>';
    }
    echo '<tr><td><strong>Código da imobiliária</strong></td><td>' . ( $codigo ? '<code>' . esc_html( $codigo ) . '</code>' : '<span style="color:red">⚠️ Não configurado — <a href="' . esc_url( admin_url( 'admin.php?page=ptim-settings' ) ) . '">preencher</a></span>' ) . '</td></tr>';
    echo '<tr><td><strong>URL do Feed XML</strong></td><td><code><a href="' . esc_url( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a></code></td></tr>';
    echo '</table>';

    // Instruções
    echo '<hr>';
    echo '<h2>📖 Como usar</h2>';
    echo '<div style="max-width:700px;background:#fff;border:1px solid #ccd0d4;padding:15px 20px;border-radius:4px">';
    echo '<h3 style="margin-top:0">1. Preencha as configurações</h3>';
    echo '<p>Acesse <a href="' . esc_url( admin_url( 'admin.php?page=ptim-settings' ) ) . '"><strong>Portal Imóveis → Configurações</strong></a> e preencha o código da imobiliária e dados de contato.</p>';
    echo '<h3>2. Cadastre os imóveis</h3>';
    echo '<p>Preencha todos os campos obrigatórios de cada imóvel (descrição com no mínimo 50 caracteres, pelo menos 5 fotos, preço, tipo, finalidade, CEP e área).</p>';
    echo '<p><strong>Importante:</strong></p>';
    echo '<ul>';
    echo '<li>Somente imóveis <strong>publicados</strong> serão sincronizados. Rascunhos e revisões pendentes não aparecem no feed.</li>';
    echo '<li>Imóveis com campos obrigatórios não preenchidos <strong>não serão exportados</strong> no XML. Veja a lista de pendências abaixo.</li>';
    echo '</ul>';
    echo '<h3>3. Configure o portal</h3>';
    echo '<p>Acesse o portal desejado (<strong>ImovelWeb</strong>, <strong>Wimoveis</strong> ou <strong>Casa Mineira</strong>):</p>';
    echo '<ol>';
    echo '<li>Vá em <strong>Integração de Anúncios</strong></li>';
    echo '<li>Selecione a opção <strong>XML</strong></li>';
    echo '<li>Cole a URL do feed: <code>' . esc_html( $feed_url ) . '</code></li>';
    echo '<li>No campo <strong>Nome do Integrador</strong>, preencha: <code>UQBITZ</code></li>';
    echo '<li>Salve. Os anúncios serão sincronizados automaticamente.</li>';
    echo '</ol>';
    echo '<p><em>Referência: <a href="https://help.imovelweb.com.br/s/article/Como-habilitar-uma-integra%C3%A7%C3%A3o-de-an%C3%BAncios" target="_blank">Central de Ajuda ImovelWeb</a></em></p>';
    echo '</div>';

    // Imóveis com pendências
    if ( ! empty( $invalid ) ) {
        echo '<hr>';
        echo '<h2>⚠️ Imóveis com pendências (' . count( $invalid ) . ')</h2>';
        echo '<p>Estes imóveis estão publicados mas <strong>não serão sincronizados</strong> porque têm campos obrigatórios faltando:</p>';
        echo '<table class="widefat striped" style="max-width:800px">';
        echo '<thead><tr><th>Imóvel</th><th>Pendências</th><th>Ação</th></tr></thead><tbody>';
        foreach ( $invalid as $inv ) {
            $edit_link = get_edit_post_link( esc_html( $inv['id'] ) );
            echo '<tr>';
            echo '<td><strong>' . esc_html( $inv['title'] ) . '</strong><br><small>#' . esc_html( $inv['id'] ) . '</small></td>';
            echo '<td><ul style="margin:0">';
            foreach ( $inv['errors'] as $e ) {
                echo '<li style="color:#d63638">❌ ' . esc_html( $e ) . '</li>';
            }
            echo '</ul></td>';
            echo '<td><a href="' . esc_url( $edit_link ) . '" class="button button-small">Editar</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else if ( $total > 0 ) {
        echo '<hr>';
        echo '<p style="color:green;font-size:14px">✅ Todos os ' . esc_html( $total ) . ' imóveis publicados estão com os campos obrigatórios preenchidos e sincronizando no feed.</p>';
    }

    echo '</div>';
}

/* ── Subpágina: Configurações ── */
function ptim_page_settings() {
    echo '<div class="wrap">';
    echo '<h1>⚙️ Configurações — Portal Imóveis</h1>';
    echo '<p>Dados usados na integração XML com os portais imobiliários.</p>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'ptim_settings_group' );
    
    $opts = get_option( 'ptim_settings', array() );
    $fields = array(
        'codigo_imobiliaria' => array( 'label' => 'Código da Imobiliária', 'desc' => 'Obrigatório. Código fornecido pelo portal.', 'required' => true ),
        'email_contato'      => array( 'label' => 'E-mail de Contato', 'desc' => 'E-mail que aparece nos anúncios.', 'required' => false ),
        'nome_contato'       => array( 'label' => 'Nome de Contato', 'desc' => 'Nome do responsável.', 'required' => false ),
        'telefone_contato'   => array( 'label' => 'Telefone de Contato', 'desc' => 'Telefone que aparece nos anúncios.', 'required' => false ),
    );

    echo '<table class="form-table">';
    foreach ( $fields as $key => $f ) {
        $val = isset( $opts[ $key ] ) ? esc_attr( $opts[ $key ] ) : '';
        $req = $f['required'] ? ' <span style="color:red">*</span>' : '';
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . $req . '</label></th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $req is static HTML
        echo '<td>';
        echo '<input type="text" id="' . esc_attr( $key ) . '" name="ptim_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="regular-text"' . ( $f['required'] ? ' required' : '' ) . ' />';
        echo '<p class="description">' . esc_html( $f['desc'] ) . '</p>';
        echo '</td></tr>';
    }
    echo '</table>';
    submit_button( 'Salvar Configurações' );
    echo '</form></div>';
}

/* ── Subpágina: Mapeamento ── */
function ptim_page_mapping() {
    echo '<div class="wrap">';
    echo '<h1>🗂️ Mapeamento — Portal Imóveis</h1>';
    echo '<p>Referência técnica: campos ACF e taxonomias usados na geração do XML do feed.</p>';

    echo '<h2>Campos ACF</h2>';
    echo '<p>Use estes nomes nos templates do Elementor (Dynamic Tags → ACF Field):</p>';
    echo '<table class="widefat striped" style="max-width:700px">';
    echo '<thead><tr><th>Campo</th><th>Nome técnico</th><th>Tipo</th><th>Obrigatório no XML</th></tr></thead><tbody>';
    $fields = array(
        array( 'Referência',        'referencia',         'number',   'Não' ),
        array( 'Preço Venda',       'sell_price',         'number',   'Sim*' ),
        array( 'Preço Locação',     'rent_price',         'number',   'Sim*' ),
        array( 'IPTU',              'iptu',               'number',   'Sim' ),
        array( 'Condomínio',        'condominium',        'number',   'Sim**' ),
        array( 'Área Privativa',    'metreage',           'number',   'Sim' ),
        array( 'Vagas',             'parking',            'number',   'Não' ),
        array( 'Quartos',           'rooms',              'number',   'Não' ),
        array( 'Banheiros',         'bathroom',           'number',   'Não' ),
        array( 'Suítes',            'suits',              'number',   'Não' ),
        array( 'Torres',            'torres',             'number',   'Não' ),
        array( 'Andares',           'andares',            'number',   'Não' ),
        array( 'Idade',             'idade',              'number',   'Sim' ),
        array( 'Amenidades',        'amenities',          'checkbox', 'Não' ),
        array( 'Infraestrutura',    'infraestrutura',     'checkbox', 'Não' ),
        array( 'Descrição',         'descricao',          'wysiwyg',  'Sim (mín. 50 chars)' ),
        array( 'CEP',               'cep',                'text',     'Sim' ),
        array( 'Rua',               'location',           'text',     'Sim' ),
        array( 'Número',            'numero',             'text',     'Não' ),
        array( 'Complemento',       'complemento',        'text',     'Não' ),
        array( 'Bairro',            'bairro',             'text',     'Sim' ),
        array( 'Cidade',            'cidade',             'text',     'Sim' ),
        array( 'Estado',            'estado',             'text',     'Sim' ),
        array( 'Galeria de Imagens','galeria_de_imagens', 'gallery',  'Sim (mín. 5 fotos)' ),
        array( 'Vídeo YouTube',     'video_youtube',      'text',     'Não' ),
        array( 'Plantas',           'plantas',            'gallery',  'Não' ),
    );
    foreach ( $fields as $f ) {
        $req_style = ( strpos( $f[3], 'Sim' ) !== false ) ? 'color:#d63638;font-weight:bold' : '';
        echo '<tr><td><strong>' . esc_html( $f[0] ) . '</strong></td><td><code>' . esc_html( $f[1] ) . '</code></td><td>' . esc_html( $f[2] ) . '</td><td style="' . esc_attr( $req_style ) . '">' . esc_html( $f[3] ) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><small>* Pelo menos um preço (venda ou locação) é obrigatório.<br>** Condomínio obrigatório para Apartamento e Casa de Condomínio.</small></p>';

    echo '<hr><h2>Taxonomias</h2>';
    echo '<table class="widefat striped" style="max-width:500px">';
    echo '<thead><tr><th>Taxonomia</th><th>Slug</th><th>Obrigatório</th></tr></thead><tbody>';
    echo '<tr><td><strong>Tipo do Imóvel</strong></td><td><code>tipo</code></td><td style="color:#d63638;font-weight:bold">Sim</td></tr>';
    echo '<tr><td><strong>Finalidade</strong></td><td><code>finalidade</code></td><td style="color:#d63638;font-weight:bold">Sim</td></tr>';
    echo '<tr><td><strong>Cidade e Bairro</strong></td><td><code>cidade-e-bairro</code></td><td>Não</td></tr>';
    echo '</tbody></table>';

    echo '<hr><h2>Estrutura XML</h2>';
    echo '<p>O feed segue o formato <strong>OpenNavent</strong>. Tags principais por imóvel:</p>';
    echo '<pre style="background:#f6f7f7;padding:15px;border:1px solid #ddd;max-width:600px;overflow-x:auto">';
    echo esc_html('<Imovel>
  <codigoAnuncio>ID</codigoAnuncio>
  <titulo>Título (máx. 80 chars)</titulo>
  <descricao>Descrição (50–4000 chars)</descricao>
  <precos><preco>
    <quantidade>Valor</quantidade>
    <moeda>BRL</moeda>
    <operacao>VENTA|ALQUILER</operacao>
  </preco></precos>
  <localizacao>
    <endereco>Rua, Nº - Complemento</endereco>
    <codigoPostal>CEP</codigoPostal>
    <localidade>Bairro,Cidade,SP,Brasil</localidade>
  </localizacao>
  <multimidia>
    <imagens>...</imagens>
    <videos><video><codigoVideo>YouTube ID</codigoVideo></video></videos>
    <plantas><planta><urlImagem>URL</urlImagem><titulo>Título</titulo></planta></plantas>
  </multimidia>
  <caracteristicas>CFT2, CFT3, CFT4, CFT5, CFT6, CFT7, CFT100, CFT101, CFT400, amenidades, infraestrutura</caracteristicas>
  <publicador><codigoImobiliaria>Código</codigoImobiliaria></publicador>
</Imovel>');
    echo '</pre>';
    echo '</div>';
}

