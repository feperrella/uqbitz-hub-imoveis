<?php
/**
 * Admin: CEP auto-fill, auto-assign taxonomia, páginas admin, settings.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

/*
 * CEP AUTO-FILL — Admin JS via ViaCEP.
 */
add_action( 'admin_enqueue_scripts', 'uqbhi_admin_cep_script' );

/**
 * Enqueue inline JS for CEP auto-fill on the property edit screen.
 *
 * @param string $hook Current admin page hook.
 */
function uqbhi_admin_cep_script( $hook ) {
	global $post_type;
	if ( 'uqbhi_imovel' !== $post_type ) {
		return;
	}
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

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

/*
 * AUTO-ASSIGN TAXONOMIA cidade-e-bairro no save.
 */
add_action( 'acf/save_post', 'uqbhi_auto_assign_location_terms', 20 );

/**
 * Auto-assign cidade-e-bairro taxonomy terms on post save.
 *
 * @param int $post_id The post ID being saved.
 */
function uqbhi_auto_assign_location_terms( $post_id ) {
	if ( 'uqbhi_imovel' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$bairro = get_field( 'bairro', $post_id );
	$cidade = get_field( 'cidade', $post_id );

	if ( empty( $cidade ) ) {
		return;
	}

	$taxonomy = 'uqbhi_cidadebairro';

	// Buscar ou criar termo da cidade (parent=0).
	$cidade_term = get_term_by( 'name', $cidade, $taxonomy );
	if ( ! $cidade_term ) {
		$result = wp_insert_term( $cidade, $taxonomy, array( 'parent' => 0 ) );
		if ( is_wp_error( $result ) ) {
			return;
		}
		$cidade_term_id = $result['term_id'];
	} else {
		$cidade_term_id = $cidade_term->term_id;
	}

	$terms_to_set = array( (int) $cidade_term_id );

	// Se tem bairro, buscar ou criar como filho da cidade.
	if ( ! empty( $bairro ) ) {
		$bairro_term = get_term_by( 'name', $bairro, $taxonomy );
		if ( $bairro_term && $bairro_term->parent !== $cidade_term_id ) {
			$bairro_term = null; // Mesmo nome em outra cidade.
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

/*
 * ADMIN PAGES — UQBITZ Hub de Integração Imobiliária.
 */
add_action( 'admin_menu', 'uqbhi_admin_menu' );

/**
 * Register admin menu pages.
 */
function uqbhi_admin_menu() {
	add_menu_page(
		'UQBITZ Hub de Integração Imobiliária',
		'Hub Imóveis',
		'manage_options',
		'uqbhi-portal',
		'uqbhi_page_main',
		'dashicons-building'
	);
	add_submenu_page(
		'uqbhi-portal',
		'UQBITZ Hub de Integração Imobiliária',
		'Visão Geral',
		'manage_options',
		'uqbhi-portal',
		'uqbhi_page_main'
	);
	add_submenu_page(
		'uqbhi-portal',
		'Configurações — Hub Imóveis',
		'Configurações',
		'manage_options',
		'uqbhi-settings',
		'uqbhi_page_settings'
	);
	add_submenu_page(
		'uqbhi-portal',
		'Mapeamento — Hub Imóveis',
		'Mapeamento',
		'manage_options',
		'uqbhi-mapping',
		'uqbhi_page_mapping'
	);
}

add_action( 'admin_init', 'uqbhi_register_settings' );

/**
 * Register plugin settings.
 */
function uqbhi_register_settings() {
	register_setting( 'uqbhi_settings_group', 'uqbhi_settings', array( 'sanitize_callback' => 'uqbhi_sanitize_settings' ) );
}

/**
 * Sanitize plugin settings input.
 *
 * @param array $input Raw settings input.
 * @return array Sanitized settings.
 */
function uqbhi_sanitize_settings( $input ) {
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


/**
 * Render the main admin page — feed overview and status.
 */
function uqbhi_page_main() {
	$feed_url = rest_url( 'uqbhi/v1/feed' );
	$settings = get_option( 'uqbhi_settings', array() );
	$codigo   = ! empty( $settings['codigo_imobiliaria'] ) ? $settings['codigo_imobiliaria'] : '';

	// Contar imóveis válidos e inválidos.
	$posts   = get_posts(
		array(
			'post_type'      => 'uqbhi_imovel',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	$total   = count( $posts );
	$valid   = 0;
	$invalid = array();
	foreach ( $posts as $pid ) {
		$errs = uqbhi_validate_imovel( $pid );
		if ( empty( $errs ) ) {
			++$valid;
		} else {
			$invalid[] = array(
				'id'     => $pid,
				'title'  => get_the_title( $pid ),
				'errors' => $errs,
			);
		}
	}

	echo '<div class="wrap">';
	echo '<h1>🏠 UQBITZ Hub de Integração Imobiliária</h1>';
	echo '<p>Integração XML com portais imobiliários: <strong>ImovelWeb</strong>, <strong>Wimoveis</strong> e <strong>Casa Mineira</strong>.</p>';
	echo '<hr>';

	// Status.
	echo '<h2>📊 Status do Feed</h2>';
	echo '<table class="widefat" style="max-width:500px">';
	echo '<tr><td><strong>Imóveis publicados</strong></td><td>' . esc_html( $total ) . '</td></tr>';
	echo '<tr><td><strong>Sincronizando no feed</strong></td><td><span style="color:green;font-weight:bold">' . esc_html( $valid ) . '</span></td></tr>';
	if ( count( $invalid ) > 0 ) {
		echo '<tr><td><strong>Com pendências</strong></td><td><span style="color:red;font-weight:bold">' . count( $invalid ) . '</span></td></tr>';
	}
	echo '<tr><td><strong>Código da imobiliária</strong></td><td>' . ( $codigo ? '<code>' . esc_html( $codigo ) . '</code>' : '<span style="color:red">⚠️ Não configurado — <a href="' . esc_url( admin_url( 'admin.php?page=uqbhi-settings' ) ) . '">preencher</a></span>' ) . '</td></tr>';
	echo '<tr><td><strong>URL do Feed XML</strong></td><td><code><a href="' . esc_url( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a></code></td></tr>';
	echo '</table>';

	// Instruções.
	echo '<hr>';
	echo '<h2>📖 Como usar</h2>';
	echo '<div style="max-width:700px;background:#fff;border:1px solid #ccd0d4;padding:15px 20px;border-radius:4px">';
	echo '<h3 style="margin-top:0">1. Preencha as configurações</h3>';
	echo '<p>Acesse <a href="' . esc_url( admin_url( 'admin.php?page=uqbhi-settings' ) ) . '"><strong>Hub Imóveis → Configurações</strong></a> e preencha o código da imobiliária e dados de contato.</p>';
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

	// Imóveis com pendências.
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
	} elseif ( $total > 0 ) {
		echo '<hr>';
		echo '<p style="color:green;font-size:14px">✅ Todos os ' . esc_html( $total ) . ' imóveis publicados estão com os campos obrigatórios preenchidos e sincronizando no feed.</p>';
	}

	echo '</div>';
}

/**
 * Render the settings admin page.
 */
function uqbhi_page_settings() {
	echo '<div class="wrap">';
	echo '<h1>⚙️ Configurações — Hub Imóveis</h1>';
	echo '<p>Dados usados na integração XML com os portais imobiliários.</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'uqbhi_settings_group' );

	$opts   = get_option( 'uqbhi_settings', array() );
	$fields = array(
		'codigo_imobiliaria' => array(
			'label'    => 'Código da Imobiliária',
			'desc'     => 'Obrigatório. Código fornecido pelo portal.',
			'required' => true,
		),
		'email_contato'      => array(
			'label'    => 'E-mail de Contato',
			'desc'     => 'E-mail que aparece nos anúncios.',
			'required' => false,
		),
		'nome_contato'       => array(
			'label'    => 'Nome de Contato',
			'desc'     => 'Nome do responsável.',
			'required' => false,
		),
		'telefone_contato'   => array(
			'label'    => 'Telefone de Contato',
			'desc'     => 'Telefone que aparece nos anúncios.',
			'required' => false,
		),
	);

	echo '<table class="form-table">';
	foreach ( $fields as $key => $f ) {
		$val = isset( $opts[ $key ] ) ? esc_attr( $opts[ $key ] ) : '';
		$req = $f['required'] ? ' <span style="color:red">*</span>' : '';
		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . $req . '</label></th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $req is static HTML
		echo '<td>';
		echo '<input type="text" id="' . esc_attr( $key ) . '" name="uqbhi_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="regular-text"' . ( $f['required'] ? ' required' : '' ) . ' />';
		echo '<p class="description">' . esc_html( $f['desc'] ) . '</p>';
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button( 'Salvar Configurações' );
	echo '</form></div>';
}

/**
 * Render the field mapping reference page.
 */
function uqbhi_page_mapping() {
	echo '<div class="wrap">';
	echo '<h1>🗂️ Mapeamento — Hub Imóveis</h1>';
	echo '<p>Referência técnica: campos ACF e taxonomias usados na geração do XML do feed.</p>';

	echo '<h2>Campos ACF</h2>';
	echo '<p>Use estes nomes nos templates do Elementor (Dynamic Tags → ACF Field):</p>';
	echo '<table class="widefat striped" style="max-width:700px">';
	echo '<thead><tr><th>Campo</th><th>Nome técnico</th><th>Tipo</th><th>Obrigatório no XML</th></tr></thead><tbody>';
	$fields = array(
		array( 'Referência', 'referencia', 'number', 'Não' ),
		array( 'Preço Venda', 'sell_price', 'number', 'Sim*' ),
		array( 'Preço Locação', 'rent_price', 'number', 'Sim*' ),
		array( 'IPTU', 'iptu', 'number', 'Sim' ),
		array( 'Condomínio', 'condominium', 'number', 'Sim**' ),
		array( 'Área Privativa', 'metreage', 'number', 'Sim' ),
		array( 'Vagas', 'parking', 'number', 'Não' ),
		array( 'Quartos', 'rooms', 'number', 'Não' ),
		array( 'Banheiros', 'bathroom', 'number', 'Não' ),
		array( 'Suítes', 'suits', 'number', 'Não' ),
		array( 'Torres', 'torres', 'number', 'Não' ),
		array( 'Andares', 'andares', 'number', 'Não' ),
		array( 'Idade', 'idade', 'number', 'Sim' ),
		array( 'Amenidades', 'amenities', 'checkbox', 'Não' ),
		array( 'Infraestrutura', 'infraestrutura', 'checkbox', 'Não' ),
		array( 'Descrição', 'descricao', 'wysiwyg', 'Sim (mín. 50 chars)' ),
		array( 'CEP', 'cep', 'text', 'Sim' ),
		array( 'Rua', 'location', 'text', 'Sim' ),
		array( 'Número', 'numero', 'text', 'Não' ),
		array( 'Complemento', 'complemento', 'text', 'Não' ),
		array( 'Bairro', 'bairro', 'text', 'Sim' ),
		array( 'Cidade', 'cidade', 'text', 'Sim' ),
		array( 'Estado', 'estado', 'text', 'Sim' ),
		array( 'Galeria de Imagens', 'galeria_de_imagens', 'gallery', 'Sim (mín. 5 fotos)' ),
		array( 'Vídeo YouTube', 'video_youtube', 'text', 'Não' ),
		array( 'Plantas', 'plantas', 'gallery', 'Não' ),
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
	echo esc_html(
		'<Imovel>
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
</Imovel>'
	);
	echo '</pre>';
	echo '</div>';
}
