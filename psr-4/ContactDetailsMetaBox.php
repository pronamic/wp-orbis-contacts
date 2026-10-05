<?php
/**
 * Contact details meta box
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_Post;

/**
 * Contact details meta box class
 *
 * Adds a "Contact Details" meta box to every post type that supports
 * `orbis-contact`, with multiple email addresses each with a label.
 */
final class ContactDetailsMetaBox {
	/**
	 * Construct contact details meta box object.
	 *
	 * @param string $plugin_file The plugin file.
	 */
	public function __construct(
		/**
		 * Plugin file.
		 */
		private readonly string $plugin_file
	) {
		\add_action( 'add_meta_boxes', $this->add_meta_boxes( ... ) );

		\add_action( 'admin_enqueue_scripts', $this->admin_enqueue_scripts( ... ) );

		\add_action( 'save_post', $this->save_post( ... ), 10, 2 );
	}

	/**
	 * Add meta boxes.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	private function add_meta_boxes( string $post_type ) {
		if ( ! \post_type_supports( $post_type, 'orbis-contact' ) ) {
			return;
		}

		\add_meta_box(
			'orbis_contact_details',
			\__( 'Contact Details', 'orbis-contacts' ),
			$this->render( ... ),
			$post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Admin enqueue scripts.
	 *
	 * @param string $hook_suffix Hook suffix.
	 * @return void
	 */
	private function admin_enqueue_scripts( string $hook_suffix ) {
		if ( ! \in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = \get_current_screen();

		if ( null === $screen || ! \post_type_supports( $screen->post_type, 'orbis-contact' ) ) {
			return;
		}

		\wp_enqueue_script(
			'orbis-contact-details-meta-box',
			\plugins_url( 'js/contact-details-meta-box.js', $this->plugin_file ),
			[],
			'1.0.0',
			[
				'in_footer' => true,
			]
		);

		\wp_add_inline_style(
			'common',
			'.orbis-contact-email-address { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; }
			.orbis-contact-email-address-label { width: 10em; }
			.orbis-contact-email-address-remove { color: #646970; }
			.orbis-contact-email-address-remove:hover { color: #d63638; }'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	private function render( WP_Post $post ) {
		\wp_nonce_field( 'orbis_save_contact_details', 'orbis_contact_details_nonce' );

		$email_addresses = ContactJson::get_email_addresses( $post->ID );

		$email_addresses[] = [
			'email' => '',
			'label' => '',
		];

		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php \esc_html_e( 'Email Addresses', 'orbis-contacts' ); ?></th>
				<td>
					<div class="orbis-contact-email-addresses">
						<?php

						foreach ( $email_addresses as $index => $email_address ) {
							$this->render_email_address_row( (string) $index, $email_address['email'], $email_address['label'] );
						}

						?>
					</div>

					<button type="button" class="button-link orbis-contact-email-address-add">
						<?php \esc_html_e( 'Add email address', 'orbis-contacts' ); ?>
					</button>

					<template class="orbis-contact-email-address-template">
						<?php $this->render_email_address_row( '__index__', '', '' ); ?>
					</template>

					<datalist id="orbis-contact-email-labels">
						<option value="<?php echo \esc_attr__( 'Work', 'orbis-contacts' ); ?>"></option>
						<option value="<?php echo \esc_attr__( 'Home', 'orbis-contacts' ); ?>"></option>
						<option value="<?php echo \esc_attr__( 'Other', 'orbis-contacts' ); ?>"></option>
					</datalist>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render email address row.
	 *
	 * @param string $index Index.
	 * @param string $email Email address.
	 * @param string $label Label.
	 * @return void
	 */
	private function render_email_address_row( string $index, string $email, string $label ) {
		$name = 'orbis_contact_email_addresses[' . $index . ']';

		?>
		<p class="orbis-contact-email-address">
			<input type="email" name="<?php echo \esc_attr( $name . '[email]' ); ?>" value="<?php echo \esc_attr( $email ); ?>" class="regular-text" placeholder="<?php echo \esc_attr__( 'Email Address', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Email Address', 'orbis-contacts' ); ?>" />
			<input type="text" name="<?php echo \esc_attr( $name . '[label]' ); ?>" value="<?php echo \esc_attr( $label ); ?>" class="orbis-contact-email-address-label" list="orbis-contact-email-labels" placeholder="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" />
			<button type="button" class="button-link orbis-contact-email-address-remove">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php \esc_html_e( 'Remove', 'orbis-contacts' ); ?></span>
			</button>
		</p>
		<?php
	}

	/**
	 * Save post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	private function save_post( int $post_id, WP_Post $post ) {
		if ( \defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE ) {
			return;
		}

		if ( ! \is_string( $_POST['orbis_contact_details_nonce'] ?? null ) ) {
			return;
		}

		$nonce = \sanitize_key( \wp_unslash( $_POST['orbis_contact_details_nonce'] ) );

		if ( false === \wp_verify_nonce( $nonce, 'orbis_save_contact_details' ) ) {
			return;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! \post_type_supports( $post->post_type, 'orbis-contact' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below.
		$input = \wp_unslash( $_POST['orbis_contact_email_addresses'] ?? [] );

		$email_addresses = [];

		foreach ( \is_array( $input ) ? $input : [] as $item ) {
			if ( ! \is_array( $item ) ) {
				continue;
			}

			$email = \trim( \is_string( $item['email'] ?? null ) ? $item['email'] : '' );

			/*
			 * Validate before sanitizing, `sanitize_email()` silently strips
			 * invalid characters (e.g. `privé@example.com` → `priv@example.com`).
			 */
			if ( false === \is_email( $email ) ) {
				continue;
			}

			$email = \sanitize_email( $email );

			$key = \strtolower( $email );

			if ( \array_key_exists( $key, $email_addresses ) ) {
				continue;
			}

			$email_addresses[ $key ] = [
				'email' => $email,
				'label' => \sanitize_text_field( \is_string( $item['label'] ?? null ) ? $item['label'] : '' ),
			];
		}

		$email_addresses = \array_values( $email_addresses );

		\delete_post_meta( $post_id, '_orbis_contact_email' );

		foreach ( $email_addresses as $email_address ) {
			\add_post_meta( $post_id, '_orbis_contact_email', $email_address['email'] );
		}

		ContactJson::update( $post_id, $email_addresses );
	}
}
