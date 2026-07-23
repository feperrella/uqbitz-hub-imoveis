<?php
/**
 * Elementor dynamic tag for imóvel galleries.
 *
 * @package UQBITZ_Hub_Imoveis
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'UQBHI_Elementor_Dynamic_Tag_Galeria_Imovel' ) ) {
	return;
}

/**
 * Dynamic tag that lets editors choose between the imóvel gallery and plants.
 */
class UQBHI_Elementor_Dynamic_Tag_Galeria_Imovel extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'uqbhi-galeria-imovel';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Galeria imóvel', 'uqbitz-hub-imoveis' );
	}

	/**
	 * Tag group.
	 *
	 * @return array
	 */
	public function get_group(): array {
		return array( 'uqbhi-imoveis' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		return array(
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY,
		);
	}

	/**
	 * Register the selector control.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$this->add_control(
			'variavel',
			array(
				'label'   => esc_html__( 'Variável', 'uqbitz-hub-imoveis' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'galeria_de_imagens' => esc_html__( 'Galeria imóvel', 'uqbitz-hub-imoveis' ),
					'plantas'            => esc_html__( 'Planta imóvel', 'uqbitz-hub-imoveis' ),
				),
				'default' => 'galeria_de_imagens',
			)
		);
	}

	/**
	 * Render the chosen gallery.
	 *
	 * @return void
	 */
	public function render(): void {
		$meta_key = (string) $this->get_settings( 'variavel' );
		$post_id  = get_queried_object_id();

		if ( $post_id <= 0 ) {
			$post_id = get_the_ID();
		}

		if ( $post_id <= 0 || ! in_array( $meta_key, array( 'galeria_de_imagens', 'plantas' ), true ) ) {
			return;
		}

		$stored = get_post_meta( $post_id, $meta_key, true );
		$items  = function_exists( 'uqbhi_fallback_gallery_normalize_items' ) ? uqbhi_fallback_gallery_normalize_items( is_array( $stored ) ? $stored : array() ) : array();

		if ( empty( $items ) ) {
			return;
		}

		echo '<div class="uqbhi-elementor-gallery uqbhi-elementor-gallery--' . esc_attr( $meta_key ) . '">';
		foreach ( $items as $item ) {
			$attachment_id = ! empty( $item['ID'] ) ? (int) $item['ID'] : ( ! empty( $item['id'] ) ? (int) $item['id'] : 0 );
			if ( $attachment_id <= 0 ) {
				continue;
			}

			echo '<figure class="uqbhi-elementor-gallery__item">';
			echo wp_get_attachment_image( $attachment_id, 'large' );
			echo '</figure>';
		}
		echo '</div>';
	}
}

