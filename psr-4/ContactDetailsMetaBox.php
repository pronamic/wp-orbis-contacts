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
 * `orbis-contact`, with multiple email addresses and phone numbers each
 * with a label.
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
			'1.1.0',
			[
				'in_footer' => true,
			]
		);

		\wp_add_inline_style(
			'common',
			'.orbis-contact-detail-row { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; }
			.orbis-contact-detail-label { width: 10em; }
			.orbis-contact-detail-remove { color: #646970; }
			.orbis-contact-detail-remove:hover { color: #d63638; }
			.orbis-contact-detail-warning { color: #996800; }'
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

		$phone_numbers = ContactJson::get_phone_numbers( $post->ID );

		$phone_numbers[] = [
			'input' => '',
			'label' => '',
			'e164'  => null,
		];

		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php \esc_html_e( 'Email Addresses', 'orbis-contacts' ); ?></th>
				<td>
					<div class="orbis-contact-detail-list">
						<?php

						foreach ( $email_addresses as $index => $email_address ) {
							$this->render_email_address_row( (string) $index, $email_address['email'], $email_address['label'] );
						}

						?>
					</div>

					<button type="button" class="button-link orbis-contact-detail-add">
						<?php \esc_html_e( 'Add email address', 'orbis-contacts' ); ?>
					</button>

					<template class="orbis-contact-detail-template">
						<?php $this->render_email_address_row( '__index__', '', '' ); ?>
					</template>

					<datalist id="orbis-contact-email-labels">
						<option value="<?php echo \esc_attr__( 'Work', 'orbis-contacts' ); ?>"></option>
						<option value="<?php echo \esc_attr__( 'Home', 'orbis-contacts' ); ?>"></option>
						<option value="<?php echo \esc_attr__( 'Other', 'orbis-contacts' ); ?>"></option>
					</datalist>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Phone Numbers', 'orbis-contacts' ); ?></th>
				<td>
					<div class="orbis-contact-detail-list">
						<?php

						foreach ( $phone_numbers as $index => $phone_number ) {
							$this->render_phone_number_row( (string) $index, $phone_number['input'], $phone_number['label'], $phone_number['e164'] );
						}

						?>
					</div>

					<button type="button" class="button-link orbis-contact-detail-add">
						<?php \esc_html_e( 'Add phone number', 'orbis-contacts' ); ?>
					</button>

					<template class="orbis-contact-detail-template">
						<?php $this->render_phone_number_row( '__index__', '', '', null ); ?>
					</template>

					<datalist id="orbis-contact-phone-labels">
						<option value="<?php echo \esc_attr__( 'Work', 'orbis-contacts' ); ?>"></option>
						<option value="<?php echo \esc_attr__( 'Mobile', 'orbis-contacts' ); ?>"></option>
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
		<p class="orbis-contact-detail-row">
			<input type="email" name="<?php echo \esc_attr( $name . '[email]' ); ?>" value="<?php echo \esc_attr( $email ); ?>" class="regular-text" placeholder="<?php echo \esc_attr__( 'Email Address', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Email Address', 'orbis-contacts' ); ?>" />
			<input type="text" name="<?php echo \esc_attr( $name . '[label]' ); ?>" value="<?php echo \esc_attr( $label ); ?>" class="orbis-contact-detail-label" list="orbis-contact-email-labels" placeholder="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" />
			<button type="button" class="button-link orbis-contact-detail-remove">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php \esc_html_e( 'Remove', 'orbis-contacts' ); ?></span>
			</button>
		</p>
		<?php
	}

	/**
	 * Render phone number row.
	 *
	 * @param string      $index Index.
	 * @param string      $input Phone number as entered.
	 * @param string      $label Label.
	 * @param string|null $e164  E.164 phone number, `null` if not standardized.
	 * @return void
	 */
	private function render_phone_number_row( string $index, string $input, string $label, ?string $e164 ) {
		$name = 'orbis_contact_phone_numbers[' . $index . ']';

		?>
		<p class="orbis-contact-detail-row">
			<input type="tel" name="<?php echo \esc_attr( $name . '[input]' ); ?>" value="<?php echo \esc_attr( $input ); ?>" class="regular-text" placeholder="<?php echo \esc_attr__( 'Phone Number', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Phone Number', 'orbis-contacts' ); ?>" />
			<input type="text" name="<?php echo \esc_attr( $name . '[label]' ); ?>" value="<?php echo \esc_attr( $label ); ?>" class="orbis-contact-detail-label" list="orbis-contact-phone-labels" placeholder="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" aria-label="<?php echo \esc_attr__( 'Label', 'orbis-contacts' ); ?>" />
			<button type="button" class="button-link orbis-contact-detail-remove">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php \esc_html_e( 'Remove', 'orbis-contacts' ); ?></span>
			</button>

			<?php if ( null !== $e164 ) : ?>

				<code><?php echo \esc_html( $e164 ); ?></code>

			<?php elseif ( '' !== $input ) : ?>

				<span class="orbis-contact-detail-warning">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<?php \esc_html_e( 'Could not be standardized', 'orbis-contacts' ); ?>
				</span>

			<?php endif; ?>
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

		$this->sync_meta( $post_id, '_orbis_contact_email', \array_column( $email_addresses, 'email' ) );

		$phone_numbers = $this->get_phone_numbers_input();

		$this->sync_meta( $post_id, '_orbis_contact_phone', \array_values( \array_filter( \array_column( $phone_numbers, 'e164' ), 'is_string' ) ) );

		ContactJson::update( $post_id, $email_addresses, $phone_numbers );
	}

	/**
	 * Get the phone numbers from the meta box input.
	 *
	 * Every phone number is standardized to E.164, phone numbers that
	 * could not be standardized are kept with `e164` set to `null`.
	 *
	 * @return array<array{input: string, label: string, e164: string|null}> Phone numbers.
	 */
	private function get_phone_numbers_input(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in `save_post()`, sanitized per field below.
		$input = \wp_unslash( $_POST['orbis_contact_phone_numbers'] ?? [] );

		$phone_numbers = [];

		foreach ( \is_array( $input ) ? $input : [] as $item ) {
			if ( ! \is_array( $item ) ) {
				continue;
			}

			$value = \sanitize_text_field( \is_string( $item['input'] ?? null ) ? $item['input'] : '' );

			if ( '' === $value ) {
				continue;
			}

			$e164 = PhoneNumbers::to_e164( $value );

			$key = $e164 ?? $value;

			if ( \array_key_exists( $key, $phone_numbers ) ) {
				continue;
			}

			$phone_numbers[ $key ] = [
				'input' => $value,
				'label' => \sanitize_text_field( \is_string( $item['label'] ?? null ) ? $item['label'] : '' ),
				'e164'  => $e164,
			];
		}

		return \array_values( $phone_numbers );
	}

	/**
	 * Sync post meta rows with the given values.
	 *
	 * Deletes the rows not in the values and adds the values without a row,
	 * so every value is one post meta row.
	 *
	 * @param int           $post_id  Post ID.
	 * @param string        $meta_key Meta key.
	 * @param array<string> $values   Values.
	 * @return void
	 */
	private function sync_meta( int $post_id, string $meta_key, array $values ) {
		$current = \get_post_meta( $post_id, $meta_key, false );
		$current = \array_filter( \is_array( $current ) ? $current : [], 'is_string' );

		foreach ( \array_diff( $current, $values ) as $value ) {
			\delete_post_meta( $post_id, $meta_key, $value );
		}

		foreach ( \array_diff( $values, $current ) as $value ) {
			\add_post_meta( $post_id, $meta_key, $value );
		}
	}
}
