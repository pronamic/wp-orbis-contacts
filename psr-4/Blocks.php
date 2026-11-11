<?php
/**
 * Blocks
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_Block;

/**
 * Blocks class
 *
 * Registers the `orbis-contacts/phone-numbers` and
 * `orbis-contacts/email-addresses` blocks, which render the phone numbers
 * and email addresses of the current contact as links below each other.
 *
 * The blocks are PHP-only, the `autoRegister` support makes WordPress
 * register them in the block editor with a server side rendered preview.
 */
final class Blocks {
	/**
	 * Construct blocks object.
	 */
	public function __construct() {
		\add_action( 'init', $this->init( ... ) );
	}

	/**
	 * Init.
	 *
	 * @return void
	 */
	private function init() {
		\register_block_type(
			'orbis-contacts/phone-numbers',
			[
				'title'           => \__( 'Contact Phone Numbers', 'orbis-contacts' ),
				'description'     => \__( 'The phone numbers of the contact as links.', 'orbis-contacts' ),
				'category'        => 'widgets',
				'icon'            => 'phone',
				'uses_context'    => [
					'postId',
				],
				'supports'        => [
					'autoRegister' => true,
				],
				'render_callback' => $this->render_phone_numbers( ... ),
			]
		);

		\register_block_type(
			'orbis-contacts/email-addresses',
			[
				'title'           => \__( 'Contact Email Addresses', 'orbis-contacts' ),
				'description'     => \__( 'The email addresses of the contact as links.', 'orbis-contacts' ),
				'category'        => 'widgets',
				'icon'            => 'email',
				'uses_context'    => [
					'postId',
				],
				'supports'        => [
					'autoRegister' => true,
				],
				'render_callback' => $this->render_email_addresses( ... ),
			]
		);
	}

	/**
	 * Render the phone numbers block.
	 *
	 * Phone numbers that could not be standardized to E.164 are rendered as
	 * plain text, without a link.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 * @param string               $content    Content.
	 * @param WP_Block|null        $block      Block.
	 * @return string HTML.
	 */
	private function render_phone_numbers( array $attributes, string $content, ?WP_Block $block = null ): string {
		$items = [];

		foreach ( ContactJson::get_phone_numbers( $this->get_post_id( $block ) ) as $phone_number ) {
			if ( null === $phone_number['e164'] ) {
				$items[] = \esc_html( $phone_number['input'] );

				continue;
			}

			$items[] = \sprintf(
				'<a href="%s">%s</a>',
				\esc_url( 'tel:' . $phone_number['e164'] ),
				\esc_html( PhoneNumbers::format_international( $phone_number['e164'] ) )
			);
		}

		return $this->render_items( $items );
	}

	/**
	 * Render the email addresses block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 * @param string               $content    Content.
	 * @param WP_Block|null        $block      Block.
	 * @return string HTML.
	 */
	private function render_email_addresses( array $attributes, string $content, ?WP_Block $block = null ): string {
		$items = [];

		foreach ( ContactJson::get_email_addresses( $this->get_post_id( $block ) ) as $email_address ) {
			$items[] = \sprintf(
				'<a href="%s">%s</a>',
				\esc_url( 'mailto:' . $email_address['email'] ),
				\esc_html( $email_address['email'] )
			);
		}

		return $this->render_items( $items );
	}

	/**
	 * Get the post ID from the block context, falls back to the current post.
	 *
	 * @param WP_Block|null $block Block.
	 * @return int Post ID, `0` if there is no post.
	 */
	private function get_post_id( ?WP_Block $block ): int {
		$post_id = $block->context['postId'] ?? \get_the_ID();

		return \is_numeric( $post_id ) ? (int) $post_id : 0;
	}

	/**
	 * Render items below each other in the block wrapper.
	 *
	 * @param array<string> $items Items, escaped HTML.
	 * @return string HTML, an empty string if there are no items.
	 */
	private function render_items( array $items ): string {
		if ( [] === $items ) {
			return '';
		}

		return \sprintf(
			'<div %s>%s</div>',
			\get_block_wrapper_attributes(),
			\implode( '<br />', $items )
		);
	}
}
