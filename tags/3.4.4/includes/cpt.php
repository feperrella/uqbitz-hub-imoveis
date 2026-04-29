<?php
/**
 * CPT + Taxonomias — Registrar via código.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'uqbhi_register_post_type_and_taxonomies', 5 );

/**
 * Register the uqbhi_imovel CPT and its taxonomies.
 */
function uqbhi_register_post_type_and_taxonomies() {

	/* ── CPT: imovel ── */
	register_post_type(
		'uqbhi_imovel',
		array(
			'labels'              => array(
				'name'                     => 'Imóveis',
				'singular_name'            => 'Imóvel',
				'menu_name'                => 'Imóveis',
				'all_items'                => 'Todos os Imóveis',
				'edit_item'                => 'Editar Imóvel',
				'view_item'                => 'Ver Imóvel',
				'view_items'               => 'Ver Imóveis',
				'add_new_item'             => 'Novo Imóvel',
				'add_new'                  => 'Novo Imóvel',
				'new_item'                 => 'Novo Imóvel',
				'parent_item_colon'        => 'Imóvel ascendente:',
				'search_items'             => 'Pesquisar Imóveis',
				'not_found'                => 'Não foi possível encontrar imóveis',
				'not_found_in_trash'       => 'Não foi possível encontrar imóveis na lixeira',
				'archives'                 => 'Arquivos de Imóvel',
				'attributes'               => 'Atributos de Imóvel',
				'insert_into_item'         => 'Inserir no imóvel',
				'uploaded_to_this_item'    => 'Enviado para este imóvel',
				'filter_items_list'        => 'Filtrar lista de imóveis',
				'filter_by_date'           => 'Filtrar imóveis por data',
				'items_list_navigation'    => 'Navegação na lista de Imóveis',
				'items_list'               => 'Lista de Imóveis',
				'item_published'           => 'Imóvel publicado.',
				'item_published_privately' => 'Imóvel publicado de forma privada.',
				'item_reverted_to_draft'   => 'Imóvel revertido para rascunho.',
				'item_scheduled'           => 'Imóvel agendado.',
				'item_updated'             => 'Imóvel atualizado.',
				'item_link'                => 'Link de Imóvel',
				'item_link_description'    => 'Um link para um imóvel.',
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
			'rewrite'             => array(
				'slug'       => 'imovel',
				'with_front' => true,
				'feeds'      => false,
				'pages'      => true,
			),
			'can_export'          => true,
			'delete_with_user'    => false,
		)
	);

	/* ── Taxonomia: tipo (Tipos de imóvel) ── */
	register_taxonomy(
		'uqbhi_tipo',
		array( 'uqbhi_imovel' ),
		array(
			'labels'             => array(
				'name'                  => 'Tipos',
				'singular_name'         => 'Tipo',
				'menu_name'             => 'Tipos',
				'all_items'             => 'Todos os Tipos',
				'edit_item'             => 'Editar Tipo',
				'view_item'             => 'Ver Tipo',
				'update_item'           => 'Atualizar Tipo',
				'add_new_item'          => 'Adicionar novo Tipo',
				'new_item_name'         => 'Novo nome de Tipo',
				'parent_item'           => 'Tipo ascendente',
				'parent_item_colon'     => 'Tipo ascendente:',
				'search_items'          => 'Pesquisar Tipos',
				'not_found'             => 'Não foi possível encontrar tipos',
				'no_terms'              => 'Não há tipos',
				'filter_by_item'        => 'Filtrar por tipo',
				'items_list_navigation' => 'Navegação na lista de Tipos',
				'items_list'            => 'Lista de Tipos',
				'back_to_items'         => '← Ir para tipos',
				'item_link'             => 'Link de Tipo',
				'item_link_description' => 'Um link para um tipo',
			),
			'description'        => 'Tipos de imóveis',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
			'rewrite'            => array(
				'slug'         => 'tipo',
				'with_front'   => true,
				'hierarchical' => false,
			),
			'capabilities'       => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		)
	);

	/* ── Taxonomia: finalidade (Finalidades) ── */
	register_taxonomy(
		'uqbhi_finalidade',
		array( 'uqbhi_imovel' ),
		array(
			'labels'             => array(
				'name'                  => 'Finalidades',
				'singular_name'         => 'Finalidade',
				'menu_name'             => 'Finalidades',
				'all_items'             => 'Todos os Finalidades',
				'edit_item'             => 'Editar Finalidade',
				'view_item'             => 'Ver Finalidade',
				'update_item'           => 'Atualizar Finalidade',
				'add_new_item'          => 'Adicionar novo Finalidade',
				'new_item_name'         => 'Novo nome de Finalidade',
				'parent_item'           => 'Finalidade ascendente',
				'parent_item_colon'     => 'Finalidade ascendente:',
				'search_items'          => 'Pesquisar Finalidades',
				'not_found'             => 'Não foi possível encontrar finalidades',
				'no_terms'              => 'Não há finalidades',
				'filter_by_item'        => 'Filtrar por finalidade',
				'items_list_navigation' => 'Navegação na lista de Finalidades',
				'items_list'            => 'Lista de Finalidades',
				'back_to_items'         => '← Ir para finalidades',
				'item_link'             => 'Link de Finalidade',
				'item_link_description' => 'Um link para um finalidade',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
			'rewrite'            => array(
				'slug'         => 'finalidade',
				'with_front'   => true,
				'hierarchical' => false,
			),
			'capabilities'       => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		)
	);

	/* ── Taxonomia: cidade-e-bairro (Cidades e Bairros) ── */
	register_taxonomy(
		'uqbhi_cidadebairro',
		array( 'uqbhi_imovel' ),
		array(
			'labels'             => array(
				'name'                  => 'Cidades e Bairros',
				'singular_name'         => 'Cidade e Bairro',
				'menu_name'             => 'Cidades e Bairros',
				'all_items'             => 'Todos os Cidades e Bairros',
				'edit_item'             => 'Editar Cidade e Bairro',
				'view_item'             => 'Ver Cidade e Bairro',
				'update_item'           => 'Atualizar Cidade e Bairro',
				'add_new_item'          => 'Adicionar novo Cidade e Bairro',
				'new_item_name'         => 'Novo nome de Cidade e Bairro',
				'parent_item'           => 'Cidade e Bairro ascendente',
				'parent_item_colon'     => 'Cidade e Bairro ascendente:',
				'search_items'          => 'Pesquisar Cidades e Bairros',
				'not_found'             => 'Não foi possível encontrar cidades e bairros',
				'no_terms'              => 'Não há cidades e bairros',
				'filter_by_item'        => 'Filtrar por cidade e bairro',
				'items_list_navigation' => 'Navegação na lista de Cidades e Bairros',
				'items_list'            => 'Lista de Cidades e Bairros',
				'back_to_items'         => '← Ir para cidades e bairros',
				'item_link'             => 'Link de Cidade e Bairro',
				'item_link_description' => 'Um link para um cidade e bairro',
			),
			'description'        => 'Crie as Cidades que possui imóvel e como subcategoria o Bairro.',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
			'rewrite'            => array(
				'slug'         => 'cidade-e-bairro',
				'with_front'   => true,
				'hierarchical' => false,
			),
			'capabilities'       => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		)
	);
}
