<?php
/**
 * Contact JSON
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
 * Contact JSON class
 *
 * Keeps the `_orbis_contact_json` post meta, the whole contact as one JSON
 * string conforming to `json-schemas/contact.json`, in sync. The email
 * addresses themselves are stored one per `_orbis_contact_email` post meta
 * row, the JSON adds the labels.
 */
final class ContactJson {
	/**
	 * Construct contact JSON object.
	 */
	public function __construct() {
		/*
		 * Priority 20, after `ContactsTable::sync_post()` so the contact ID exists.
		 */
		\add_action( 'wp_after_insert_post', $this->after_insert_post( ... ), 20, 2 );
	}

	/**
	 * Get the contact data from the `_orbis_contact_json` post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array<mixed> Contact data.
	 */
	public static function get( int $post_id ): array {
		$json = \get_post_meta( $post_id, '_orbis_contact_json', true );

		if ( ! \is_string( $json ) || '' === $json ) {
			return [];
		}

		$data = \json_decode( $json, true );

		return \is_array( $data ) ? $data : [];
	}

	/**
	 * Get email addresses.
	 *
	 * The `_orbis_contact_email` post meta rows are leading, so email
	 * addresses added with `add_post_meta()` are included too, the labels
	 * come from the `_orbis_contact_json` post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array<array{email: string, label: string}> Email addresses.
	 */
	public static function get_email_addresses( int $post_id ): array {
		$labels = [];

		$data = self::get( $post_id );

		if ( \array_key_exists( 'email_addresses', $data ) && \is_array( $data['email_addresses'] ) ) {
			foreach ( $data['email_addresses'] as $item ) {
				if ( ! \is_array( $item ) || ! \is_string( $item['email'] ?? null ) ) {
					continue;
				}

				$labels[ \strtolower( $item['email'] ) ] = \is_string( $item['label'] ?? null ) ? $item['label'] : '';
			}
		}

		$email_addresses = [];

		$values = \get_post_meta( $post_id, '_orbis_contact_email', false );

		foreach ( \is_array( $values ) ? $values : [] as $email ) {
			if ( ! \is_string( $email ) || '' === $email ) {
				continue;
			}

			$email_addresses[] = [
				'email' => $email,
				'label' => $labels[ \strtolower( $email ) ] ?? '',
			];
		}

		return $email_addresses;
	}

	/**
	 * Update the `_orbis_contact_json` post meta.
	 *
	 * @param int                                             $post_id         Post ID.
	 * @param array<array{email: string, label: string}>|null $email_addresses Email addresses, `null` for the current email addresses.
	 * @return void
	 */
	public static function update( int $post_id, ?array $email_addresses = null ) {
		$post = \get_post( $post_id );

		if ( null === $post ) {
			return;
		}

		$data = [
			'$schema'         => 'https://github.com/pronamic/wp-orbis-contacts/json-schemas/contact.json',
			'post_id'         => $post->ID,
			'contact_id'      => ContactsTable::get_contact_id( $post->ID ),
			'type'            => $post->post_type,
			'name'            => $post->post_title,
			'email_addresses' => $email_addresses ?? self::get_email_addresses( $post->ID ),
		];

		$json = \wp_json_encode( $data, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return;
		}

		\update_post_meta( $post->ID, '_orbis_contact_json', \wp_slash( $json ) );
	}

	/**
	 * After insert post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	private function after_insert_post( int $post_id, WP_Post $post ) {
		if ( ! \post_type_supports( $post->post_type, 'orbis-contact' ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		self::update( $post_id );
	}
}
