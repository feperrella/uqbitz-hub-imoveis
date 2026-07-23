<?php
/**
 * Native WordPress fallback for the ACF Pro `gallery` field.
 *
 * ACF free does not ship the gallery field type. When Pro is unavailable, this
 * module registers two native metaboxes (`galeria_de_imagens` and `plantas`)
 * that use `wp.media` for selection and jQuery UI Sortable for reordering.
 * Data is stored as an array of attachment IDs in post meta under the same
 * keys ACF Pro would use, so the feed and validation code paths — which
 * already accept numeric IDs via `is_numeric( $img )` — work unchanged.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_uqbhi_imovel', 'uqbhi_fallback_gallery_register' );
add_action( 'save_post_uqbhi_imovel', 'uqbhi_fallback_gallery_save', 10, 2 );
add_action( 'admin_enqueue_scripts', 'uqbhi_fallback_gallery_enqueue' );

if ( ! uqbhi_has_acf_pro_gallery() ) {
	add_filter( 'acf/load_value/name=galeria_de_imagens', 'uqbhi_fallback_gallery_acf_format_value', 20, 3 );
	add_filter( 'acf/format_value/name=galeria_de_imagens', 'uqbhi_fallback_gallery_acf_format_value', 20, 3 );
	add_filter( 'acf/load_value/name=plantas', 'uqbhi_fallback_gallery_acf_format_value', 20, 3 );
	add_filter( 'acf/format_value/name=plantas', 'uqbhi_fallback_gallery_acf_format_value', 20, 3 );
}

/**
 * Register the two fallback gallery metaboxes on the imóvel edit screen.
 */
function uqbhi_fallback_gallery_register() {
	if ( uqbhi_has_acf_pro_gallery() ) {
		return;
	}
	add_meta_box(
		'uqbhi_galeria_de_imagens',
		'Galeria de Imagens',
		'uqbhi_fallback_gallery_render_images',
		'uqbhi_imovel',
		'normal',
		'default'
	);
	add_meta_box(
		'uqbhi_plantas',
		'Plantas do Imóvel',
		'uqbhi_fallback_gallery_render_plantas',
		'uqbhi_imovel',
		'normal',
		'default'
	);
}

/**
 * Render the "Galeria de Imagens" metabox.
 *
 * @param WP_Post $post Current post object.
 */
function uqbhi_fallback_gallery_render_images( $post ) {
	uqbhi_fallback_gallery_render_field(
		$post,
		'galeria_de_imagens',
		'Recomendamos pelo menos 22 fotos de ângulos e ambientes diferentes. Imóveis com mais fotos têm até 17% mais chance de aparecer melhor posicionados nos resultados de busca.'
	);
}

/**
 * Render the "Plantas do Imóvel" metabox.
 *
 * @param WP_Post $post Current post object.
 */
function uqbhi_fallback_gallery_render_plantas( $post ) {
	uqbhi_fallback_gallery_render_field(
		$post,
		'plantas',
		'Envie as plantas (plantas baixas) do imóvel. Cada imagem será exportada com seu título no XML do feed.'
	);
}

/**
 * Render a generic gallery metabox body.
 *
 * @param WP_Post $post         Current post.
 * @param string  $meta_key     Post meta key for storage.
 * @param string  $instructions Help text shown above the grid.
 */
function uqbhi_fallback_gallery_render_field( $post, $meta_key, $instructions ) {
	$stored = get_post_meta( $post->ID, $meta_key, true );
	$ids    = is_array( $stored ) ? array_values( array_filter( array_map( 'intval', $stored ) ) ) : array();

	wp_nonce_field( 'uqbhi_gallery_' . $meta_key, 'uqbhi_gallery_' . $meta_key . '_nonce' );

	$csv        = implode( ',', $ids );
	$input_name = 'uqbhi_gallery_' . $meta_key;
	?>
	<div class="uqbhi-gallery" data-meta="<?php echo esc_attr( $meta_key ); ?>">
		<p class="description"><?php echo esc_html( $instructions ); ?></p>
		<ul class="uqbhi-gallery-list">
			<?php
			foreach ( $ids as $attachment_id ) {
				$thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
				if ( ! $thumb_url ) {
					continue;
				}
				$title = get_the_title( $attachment_id );
				?>
				<li data-id="<?php echo esc_attr( $attachment_id ); ?>">
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
					<button type="button" class="uqbhi-gallery-remove" aria-label="Remover">&times;</button>
				</li>
				<?php
			}
			?>
		</ul>
		<p class="uqbhi-gallery-actions">
			<button type="button" class="button button-primary uqbhi-gallery-add">Adicionar imagens</button>
			<button type="button" class="button uqbhi-gallery-clear">Remover todas</button>
			<span class="uqbhi-gallery-count"><?php echo count( $ids ); ?> imagem(ns)</span>
		</p>
		<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $csv ); ?>" class="uqbhi-gallery-input" />
	</div>
	<?php
}

/**
 * Format the fallback gallery value like ACF Pro would do for a gallery field.
 *
 * This keeps the stored value as attachment IDs, but exposes an array of
 * attachment objects for consumers such as Elementor / ACF integrations.
 *
 * @param mixed $value  Stored meta value.
 * @param int   $post_id Post ID.
 * @param array $field  ACF field definition.
 * @return array
 */
function uqbhi_fallback_gallery_acf_format_value( $value, $post_id = 0, $field = array() ) {
	return uqbhi_fallback_gallery_normalize_items( $value );
}

/**
 * Normalize any gallery-like value into an ACF-style array of attachments.
 *
 * @param mixed $value Raw meta value or already formatted field value.
 * @return array
 */
function uqbhi_fallback_gallery_normalize_items( $value ) {
	if ( empty( $value ) || ! is_array( $value ) ) {
		return array();
	}

	$items = array();
	foreach ( $value as $item ) {
		if ( is_array( $item ) && ! empty( $item['ID'] ) ) {
			$normalized = uqbhi_fallback_gallery_attachment_to_acf( (int) $item['ID'] );
		} elseif ( is_array( $item ) && ! empty( $item['id'] ) ) {
			$normalized = uqbhi_fallback_gallery_attachment_to_acf( (int) $item['id'] );
		} elseif ( is_numeric( $item ) ) {
			$normalized = uqbhi_fallback_gallery_attachment_to_acf( (int) $item );
		} else {
			$normalized = array();
		}

		if ( ! empty( $normalized ) ) {
			$items[] = $normalized;
		}
	}

	return $items;
}

/**
 * Convert an attachment ID into an ACF Pro-like gallery item array.
 *
 * @param int $attachment_id Attachment ID.
 * @return array
 */
function uqbhi_fallback_gallery_attachment_to_acf( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
		return array();
	}

	$attachment = get_post( $attachment_id );
	if ( ! $attachment ) {
		return array();
	}

	$mime_type = get_post_mime_type( $attachment_id );
	$meta      = wp_get_attachment_metadata( $attachment_id );
	$url       = wp_get_attachment_url( $attachment_id );
	$alt       = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	$sizes = array();
	foreach ( array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ) as $size ) {
		$img = wp_get_attachment_image_src( $attachment_id, $size );
		if ( ! empty( $img[0] ) ) {
			$sizes[ $size ] = array(
				'url'    => $img[0],
				'width'  => ! empty( $img[1] ) ? (int) $img[1] : 0,
				'height' => ! empty( $img[2] ) ? (int) $img[2] : 0,
				'mime-type' => $mime_type,
			);
		}
	}

	return array(
		'ID'          => $attachment_id,
		'id'          => $attachment_id,
		'title'       => get_the_title( $attachment_id ),
		'filename'    => basename( get_attached_file( $attachment_id ) ),
		'url'         => $url,
		'alt'         => $alt,
		'author'      => (int) $attachment->post_author,
		'description' => $attachment->post_content,
		'caption'     => $attachment->post_excerpt,
		'name'        => $attachment->post_name,
		'status'      => $attachment->post_status,
		'uploaded_to' => (int) $attachment->post_parent,
		'date'        => $attachment->post_date,
		'modified'    => $attachment->post_modified,
		'menu_order'  => (int) $attachment->menu_order,
		'mime_type'   => $mime_type,
		'type'        => 0 === strpos( (string) $mime_type, 'image/' ) ? 'image' : 'file',
		'subtype'     => $mime_type ? str_replace( 'image/', '', (string) $mime_type ) : '',
		'icon'        => wp_mime_type_icon( $mime_type ),
		'width'       => ! empty( $meta['width'] ) ? (int) $meta['width'] : 0,
		'height'      => ! empty( $meta['height'] ) ? (int) $meta['height'] : 0,
		'sizes'       => $sizes,
	);
}

/**
 * Persist fallback gallery data to post meta as an array of attachment IDs.
 *
 * @param int     $post_id Post ID being saved.
 * @param WP_Post $post    Post object.
 */
function uqbhi_fallback_gallery_save( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'uqbhi_imovel' !== $post->post_type ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( uqbhi_has_acf_pro_gallery() ) {
		return;
	}

	foreach ( array( 'galeria_de_imagens', 'plantas' ) as $key ) {
		$nonce_field = 'uqbhi_gallery_' . $key . '_nonce';
		if ( empty( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $nonce_field ] ) ), 'uqbhi_gallery_' . $key ) ) {
			continue;
		}

		$raw = isset( $_POST[ 'uqbhi_gallery_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'uqbhi_gallery_' . $key ] ) ) : '';
		$ids = array();
		if ( '' !== $raw ) {
			$ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );
		}

		update_post_meta( $post_id, $key, $ids );
	}
}

/**
 * Enqueue wp.media, jQuery UI Sortable and the inline assets for the grid.
 *
 * @param string $hook Current admin page hook.
 */
function uqbhi_fallback_gallery_enqueue( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'uqbhi_imovel' !== $screen->post_type ) {
		return;
	}
	if ( uqbhi_has_acf_pro_gallery() ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );

	$css = '
		.uqbhi-gallery-list { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; padding: 10px; list-style: none; min-height: 130px; background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 4px; }
		.uqbhi-gallery-list:empty::before { content: "Nenhuma imagem adicionada."; color: #8c8f94; font-style: italic; display: flex; align-items: center; justify-content: center; width: 100%; padding: 40px 0; }
		.uqbhi-gallery-list li { position: relative; width: 110px; height: 110px; border: 1px solid #c3c4c7; background: #fff; border-radius: 4px; overflow: hidden; cursor: move; }
		.uqbhi-gallery-list li img { width: 100%; height: 100%; object-fit: cover; display: block; }
		.uqbhi-gallery-list li .uqbhi-gallery-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; line-height: 20px; padding: 0; font-size: 16px; color: #fff; background: rgba(0,0,0,0.65); border: 0; border-radius: 50%; text-align: center; cursor: pointer; font-weight: bold; }
		.uqbhi-gallery-list li .uqbhi-gallery-remove:hover { background: #d63638; }
		.uqbhi-gallery-list .ui-sortable-placeholder { visibility: visible !important; background: #e9eaec; border: 1px dashed #8c8f94; border-radius: 4px; width: 110px; height: 110px; }
		.uqbhi-gallery-actions { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
		.uqbhi-gallery-count { color: #50575e; font-size: 13px; }
	';
	wp_add_inline_style( 'wp-admin', $css );

	$js = <<<'JS'
(function($){
	$(function(){
		$('.uqbhi-gallery').each(function(){
			var $box   = $(this);
			var $list  = $box.find('.uqbhi-gallery-list');
			var $input = $box.find('.uqbhi-gallery-input');
			var $count = $box.find('.uqbhi-gallery-count');
			var frame;

			function syncInput(){
				var ids = $list.children('li').map(function(){ return String($(this).data('id')); }).get();
				$input.val(ids.join(','));
				$count.text(ids.length + ' imagem(ns)');
			}

			function addAttachment(att){
				var existing = $list.children('li').map(function(){ return String($(this).data('id')); }).get();
				if (existing.indexOf(String(att.id)) !== -1) { return; }
				var thumbUrl = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) ? att.sizes.thumbnail.url : att.url;
				var $li = $('<li>').attr('data-id', att.id);
				$('<img>').attr({ src: thumbUrl, alt: att.title || '' }).appendTo($li);
				$('<button>').attr({ type: 'button', 'class': 'uqbhi-gallery-remove', 'aria-label': 'Remover' }).html('&times;').appendTo($li);
				$list.append($li);
			}

			$list.sortable({
				update: syncInput,
				placeholder: 'ui-sortable-placeholder',
				tolerance: 'pointer'
			});

			$box.on('click', '.uqbhi-gallery-add', function(e){
				e.preventDefault();
				frame = wp.media({
					title:    'Selecionar imagens',
					button:   { text: 'Adicionar à galeria' },
					library:  { type: 'image' },
					multiple: 'add'
				});
				frame.on('select', function(){
					frame.state().get('selection').each(function(att){
						addAttachment(att.toJSON());
					});
					syncInput();
				});
				frame.open();
			});

			$box.on('click', '.uqbhi-gallery-remove', function(e){
				e.preventDefault();
				$(this).closest('li').remove();
				syncInput();
			});

			$box.on('click', '.uqbhi-gallery-clear', function(e){
				e.preventDefault();
				if (!window.confirm('Remover todas as imagens desta galeria?')) { return; }
				$list.empty();
				syncInput();
			});
		});
	});
})(jQuery);
JS;
	wp_add_inline_script( 'jquery-ui-sortable', $js );
}
