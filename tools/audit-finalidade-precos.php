<?php
/**
 * DRY-RUN — Auditoria do impacto de fazer <precos> respeitar uqbhi_finalidade.
 *
 * NÃO altera nada. Apenas lê e relata.
 *
 * INSTRUMENTO PRÉ-MUDANÇA. A coluna ATUAL assume o comportamento antigo, em
 * que <precos> era guiado só pelo preço. A partir da versão em que a finalidade
 * passou a governar, rodar isto num site já atualizado compara o novo
 * comportamento com ele mesmo — a coluna ATUAL deixa de refletir o feed. Serve
 * para medir o impacto ANTES de atualizar, ou contra um site ainda na versão
 * anterior.
 *
 * Compara, para cada imóvel publicado no feed hoje:
 *   ATUAL    — operações emitidas por trunk/includes/feed.php (guiado só por preço)
 *   PROPOSTO — operações após "finalidade governa, com fallback"
 *
 * Regra proposta auditada:
 *   1. Mapeia cada termo de uqbhi_finalidade para VENTA e/ou ALQUILER, nesta ordem:
 *      term meta uqbhi_opennavent > mesma meta herdada de um ancestral >
 *      heurística por nome (pt/es/en). Um único termo PODE autorizar as duas
 *      operações (ex.: "Venda e Locação").
 *   2. Se o imóvel não tem finalidade, ou nenhum termo mapeou, cai no
 *      comportamento atual (guiado por preço).
 *   3. Caso contrário: emite VENTA só se VENTA é permitido E sell_price tem
 *      valor; idem ALQUILER com rent_price.
 *
 * Como o proposto é sempre um subconjunto do atual, nenhum imóvel ganha
 * anúncio — só pode perder. Por isso o foco do relatório é o que se perde.
 *
 * Uso:
 *   wp eval-file tools/audit-finalidade-precos.php
 *   wp eval-file tools/audit-finalidade-precos.php format=csv > auditoria.csv
 *   wp eval-file tools/audit-finalidade-precos.php all
 *
 * Flags (SEM hífens — o WP-CLI captura `--flag` como parâmetro dele próprio e
 * aborta com "unknown parameter", inclusive depois de um `--`):
 *   format=table|csv  Formato da listagem (padrão: table).
 *   all               Lista também os imóveis sem mudança.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "Este script precisa ser executado via WP-CLI (wp eval-file).\n";
	exit( 1 );
}

// uqbhi_validate_imovel() chama get_field() sem guarda, então sem ACF a
// auditoria morreria com fatal no primeiro imóvel. Falha cedo e explica.
if ( ! function_exists( 'get_field' ) ) {
	WP_CLI::error( 'ACF não está ativo. A auditoria depende de get_field(), igual ao feed. Ative o ACF e rode de novo.' );
}
if ( ! function_exists( 'uqbhi_validate_imovel' ) ) {
	WP_CLI::error( 'Plugin uqbitz-hub-imoveis não está ativo nesta instalação.' );
}

/**
 * Mapeia um termo de finalidade para as operações OpenNavent que ele autoriza.
 *
 * Difere de uqbhi_get_operacao() em três pontos deliberados:
 *   - devolve um CONJUNTO, porque um termo pode autorizar venda e locação;
 *   - NÃO assume 'Venta' para termos desconhecidos — devolve conjunto vazio,
 *     para o chamador cair no fallback em vez de restringir em silêncio;
 *   - percorre ancestrais atrás da meta, como uqbhi_get_tipo() já faz para
 *     uqbhi_tipo (helpers.php:94-105), já que a taxonomia é hierárquica.
 *
 * @param WP_Term $term Termo de uqbhi_finalidade.
 * @return array{ops:string[],how:string} Operações autorizadas e como foram determinadas.
 */
function uqbhi_audit_map_term( $term ) {
	$meta = get_term_meta( $term->term_id, 'uqbhi_opennavent', true );
	if ( 'ALQUILER' === $meta || 'VENTA' === $meta ) {
		return array(
			'ops' => array( $meta ),
			'how' => 'meta',
		);
	}

	// Herança: sobe a hierarquia atrás da meta, como uqbhi_get_tipo faz.
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
			return array(
				'ops' => array( $herdado ),
				'how' => 'heranca',
			);
		}
	}

	// Heurística por nome. Testa as DUAS listas: um termo como "Venda e Locação"
	// autoriza as duas operações, e curto-circuitar na primeira produziria um
	// falso "FICA SEM PRECO".
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
	if ( ! empty( $ops ) ) {
		return array(
			'ops' => $ops,
			'how' => 'nome',
		);
	}

	return array(
		'ops' => array(),
		'how' => 'sem-mapeamento',
	);
}

// WP-CLI entrega os argumentos posicionais em $args. Aceita as flags com ou
// sem os hífens, porque o `--` do wp eval-file nem sempre os preserva.
$opts     = isset( $args ) && is_array( $args ) ? $args : array();
$format   = 'table';
$list_all = false;
foreach ( $opts as $opt ) {
	$opt = ltrim( (string) $opt, '-' );
	if ( 0 === strpos( $opt, 'format=' ) ) {
		$format = substr( $opt, 7 );
	}
	if ( 'all' === $opt ) {
		$list_all = true;
	}
}
if ( ! in_array( $format, array( 'table', 'csv' ), true ) ) {
	WP_CLI::error( 'Formato inválido. Use format=table ou format=csv.' );
}

$posts = get_posts(
	array(
		'post_type'      => 'uqbhi_imovel',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	)
);

$rows            = array();
$n_total         = count( $posts );
$n_invalid       = 0;
$n_no_change     = 0;
$n_lose_some     = 0;
$n_lose_all      = 0;
$n_fallback      = 0;
$n_termo_ignorado = 0;
$n_zero_no_ar    = 0;
$n_zero_fica     = 0;
$n_multi_term    = 0;

foreach ( $posts as $post ) {
	$id = $post->ID;

	// Mesma porta de entrada do feed: inválidos já não são publicados hoje.
	if ( ! empty( uqbhi_validate_imovel( $id ) ) ) {
		++$n_invalid;
		continue;
	}

	$sell = get_field( 'sell_price', $id );
	$rent = get_field( 'rent_price', $id );

	// ATUAL — exatamente o que feed.php faz hoje.
	$atual = array();
	if ( uqbhi_has_value( $sell ) ) {
		$atual[] = 'VENTA';
	}
	if ( uqbhi_has_value( $rent ) ) {
		$atual[] = 'ALQUILER';
	}

	// Finalidades atribuídas.
	$terms = wp_get_post_terms( $id, 'uqbhi_finalidade' );
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}
	if ( count( $terms ) > 1 ) {
		++$n_multi_term;
	}

	$permitido   = array();
	$nomes_terms = array();
	$sem_mapa    = array();
	foreach ( $terms as $term ) {
		$nomes_terms[] = $term->name;
		$mapped        = uqbhi_audit_map_term( $term );
		if ( empty( $mapped['ops'] ) ) {
			$sem_mapa[] = $term->name;
			continue;
		}
		$permitido = array_merge( $permitido, $mapped['ops'] );
	}
	$permitido = array_values( array_unique( $permitido ) );

	// Fallback: sem finalidade ou nenhum termo mapeado → comportamento atual.
	$usou_fallback = empty( $permitido );
	if ( $usou_fallback ) {
		$permitido = array( 'VENTA', 'ALQUILER' );
		++$n_fallback;
	} elseif ( ! empty( $sem_mapa ) ) {
		// Termo não mapeado convivendo com um mapeado: ele foi descartado em
		// silêncio e pode ser a causa real da perda. Precisa aparecer.
		++$n_termo_ignorado;
	}

	// PROPOSTO — interseção de atual com permitido.
	$proposto = array_values( array_intersect( $atual, $permitido ) );
	$perdidas = array_values( array_diff( $atual, $proposto ) );

	// Preço zero no ar. Espelha o feed: uqbhi_has_value() abre o bloco e
	// intval() escreve a quantidade — sem guarda is_numeric, então 'R$ 0'
	// também vai a zero na linha.
	$zero_flags = array();
	foreach ( array(
		'sell' => array( $sell, 'VENTA' ),
		'rent' => array( $rent, 'ALQUILER' ),
	) as $rotulo => $par ) {
		list( $valor, $op ) = $par;
		if ( ! in_array( $op, $atual, true ) || 0 !== intval( $valor ) ) {
			continue;
		}
		++$n_zero_no_ar;
		$sobrevive = in_array( $op, $proposto, true );
		if ( $sobrevive ) {
			++$n_zero_fica;
		}
		$zero_flags[] = $rotulo . '=0' . ( $sobrevive ? '(PERMANECE)' : '(sai)' );
	}

	if ( empty( $perdidas ) ) {
		$situacao = 'IGUAL';
		++$n_no_change;
	} elseif ( empty( $proposto ) ) {
		$situacao = 'FICA SEM PRECO';
		++$n_lose_all;
	} else {
		$situacao = 'PERDE ' . implode( '+', $perdidas );
		++$n_lose_some;
	}

	if ( 'IGUAL' === $situacao && ! $list_all ) {
		continue;
	}

	$obs = array();
	if ( ! empty( $sem_mapa ) ) {
		$obs[] = ( $usou_fallback ? 'fallback:' : 'IGNORADO:' ) . implode( '/', $sem_mapa );
	}
	$obs = array_merge( $obs, $zero_flags );

	$rows[] = array(
		'ID'         => $id,
		'situacao'   => $situacao,
		'titulo'     => mb_substr( $post->post_title, 0, 45 ),
		'finalidade' => $nomes_terms ? implode( '+', $nomes_terms ) : '(nenhuma)',
		'sell_price' => uqbhi_has_value( $sell ) ? (string) intval( $sell ) : '',
		'rent_price' => uqbhi_has_value( $rent ) ? (string) intval( $rent ) : '',
		'atual'      => implode( '+', $atual ),
		'proposto'   => $proposto ? implode( '+', $proposto ) : '(vazio)',
		'obs'        => implode( ' ', $obs ),
	);
}

// Ordena: casos críticos primeiro.
$peso = array(
	'FICA SEM PRECO' => 0,
	'IGUAL'          => 2,
);
usort(
	$rows,
	function ( $a, $b ) use ( $peso ) {
		$pa = isset( $peso[ $a['situacao'] ] ) ? $peso[ $a['situacao'] ] : 1;
		$pb = isset( $peso[ $b['situacao'] ] ) ? $peso[ $b['situacao'] ] : 1;
		if ( $pa === $pb ) {
			return $a['ID'] <=> $b['ID'];
		}
		return $pa <=> $pb;
	}
);

$cols = array( 'ID', 'situacao', 'titulo', 'finalidade', 'sell_price', 'rent_price', 'atual', 'proposto', 'obs' );

if ( 'csv' === $format ) {
	WP_CLI\Utils\format_items( 'csv', $rows, $cols );
	exit( 0 );
}

WP_CLI::log( '' );
WP_CLI::log( '=== AUDITORIA (DRY-RUN) — finalidade x <precos> ===' );
WP_CLI::log( '' );
WP_CLI::log( sprintf( 'Imóveis publicados .................. %d', $n_total ) );
WP_CLI::log( sprintf( '  já fora do feed (validação) ...... %d', $n_invalid ) );
WP_CLI::log( sprintf( '  no feed hoje ..................... %d', $n_total - $n_invalid ) );
WP_CLI::log( '' );
WP_CLI::log( 'Impacto da mudança nos que estão no feed:' );
WP_CLI::log( sprintf( '  sem alteração .................... %d', $n_no_change ) );
WP_CLI::log( sprintf( '  perdem UMA operação .............. %d', $n_lose_some ) );
WP_CLI::log( sprintf( '  FICAM SEM NENHUM PREÇO ........... %d   <-- exige decisão', $n_lose_all ) );
WP_CLI::log( '' );
WP_CLI::log( 'Sinais de qualidade de dados:' );
WP_CLI::log( sprintf( '  caem no fallback (nenhum termo mapeou) . %d', $n_fallback ) );
WP_CLI::log( sprintf( '  com termo IGNORADO na decisão .......... %d   <-- conferir antes de confiar na linha', $n_termo_ignorado ) );
WP_CLI::log( sprintf( '  com finalidade múltipla marcada ........ %d', $n_multi_term ) );
WP_CLI::log( sprintf( '  preços zerados no ar hoje .............. %d', $n_zero_no_ar ) );
WP_CLI::log( sprintf( '  ... que CONTINUAM no ar após a mudança . %d', $n_zero_fica ) );
WP_CLI::log( '' );

if ( empty( $rows ) ) {
	WP_CLI::success( 'Nenhum imóvel mudaria de comportamento. Seguro aplicar.' );
	exit( 0 );
}

WP_CLI\Utils\format_items( 'table', $rows, $cols );
WP_CLI::log( '' );
WP_CLI::warning( 'Dry-run: nada foi alterado.' );
