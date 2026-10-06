<?php
/**
 * Contact Select2 controller
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_REST_Request;
use WP_REST_Response;
use wpdb;

/**
 * Contact Select2 controller class
 *
 * Provides a REST API endpoint in the Select2 data format and a script
 * that turns `select.orbis-contact-id-control` elements into a contact
 * picker. The values are IDs from the `{prefix}orbis_contacts` table.
 */
final readonly class ContactSelect2Controller {
	/**
	 * Construct contact Select2 controller object.
	 *
	 * @param string $plugin_file The plugin file.
	 */
	public function __construct(
		/**
		 * Plugin file.
		 */
		private string $plugin_file
	) {
		\add_action( 'rest_api_init', $this->rest_api_init( ... ) );

		/*
		 * Register early, so other plugins can enqueue the script on the
		 * default priority.
		 */
		\add_action( 'admin_enqueue_scripts', $this->register_scripts( ... ), 5 );

		\add_action( 'admin_enqueue_scripts', $this->maybe_enqueue_style( ... ), 100 );
	}

	/**
	 * Get WordPress database object.
	 *
	 * @return wpdb WordPress database object.
	 */
	private static function get_wpdb(): wpdb {
		/**
		 * WordPress database object.
		 *
		 * @var wpdb $wpdb
		 */
		global $wpdb;

		return $wpdb;
	}

	/**
	 * REST API initialize.
	 *
	 * @return void
	 */
	private function rest_api_init() {
		\register_rest_route(
			'orbis-contacts/v1',
			'/contacts/select2',
			[
				'methods'             => 'GET',
				'callback'            => $this->rest_select2( ... ),
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'args'                => [
					'term' => [
						'description'       => \__( 'Search term, matched against the contact name.', 'orbis-contacts' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * REST Select2.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	private function rest_select2( WP_REST_Request $request ): WP_REST_Response {
		$wpdb = self::get_wpdb();

		$term = $request->get_param( 'term' );

		$term = \is_string( $term ) ? $term : '';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT contact.id, contact.post_id, contact.name FROM %i AS contact INNER JOIN %i AS post ON post.ID = contact.post_id WHERE post.post_status = 'publish' AND contact.name LIKE %s ORDER BY contact.name LIMIT 20",
				ContactsTable::get_table_name(),
				$wpdb->posts,
				'%' . $wpdb->esc_like( $term ) . '%'
			),
			\ARRAY_A
		);

		$results = [];

		foreach ( \is_array( $rows ) ? $rows : [] as $row ) {
			$item = self::get_item_from_row( $row );

			if ( null !== $item ) {
				$results[] = $item;
			}
		}

		return new WP_REST_Response(
			[
				'results' => $results,
			]
		);
	}

	/**
	 * Get Select2 option data for a contact.
	 *
	 * Useful to render a preselected `<option>` with the same data as the
	 * REST API endpoint, as `data-*` attributes.
	 *
	 * @param int $contact_id Contact ID.
	 * @return array{id: int, text: string, type: string, type_label: string, icon: string, email: string|null}|null
	 */
	public static function get_option_data( int $contact_id ): ?array {
		$wpdb = self::get_wpdb();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, post_id, name FROM %i WHERE id = %d',
				ContactsTable::get_table_name(),
				$contact_id
			),
			\ARRAY_A
		);

		return self::get_item_from_row( $row );
	}

	/**
	 * Get Select2 item from a database row.
	 *
	 * @param mixed $row Database row with `id`, `post_id` and `name`.
	 * @return array{id: int, text: string, type: string, type_label: string, icon: string, email: string|null}|null
	 */
	private static function get_item_from_row( mixed $row ): ?array {
		if ( ! \is_array( $row ) ) {
			return null;
		}

		if ( ! \is_numeric( $row['id'] ?? null ) || ! \is_numeric( $row['post_id'] ?? null ) || ! \is_string( $row['name'] ?? null ) ) {
			return null;
		}

		return self::get_item( (int) $row['id'], (int) $row['post_id'], $row['name'] );
	}

	/**
	 * Get Select2 item.
	 *
	 * @param int    $contact_id Contact ID.
	 * @param int    $post_id    Post ID.
	 * @param string $name       Name.
	 * @return array{id: int, text: string, type: string, type_label: string, icon: string, email: string|null}
	 */
	private static function get_item( int $contact_id, int $post_id, string $name ): array {
		$post_type = (string) \get_post_type( $post_id );

		$post_type_object = \get_post_type_object( $post_type );

		$type_label = $post_type;
		$icon       = 'dashicons-groups';

		if ( null !== $post_type_object ) {
			$label = $post_type_object->labels->singular_name ?? $post_type_object->label;

			if ( \is_string( $label ) ) {
				$type_label = $label;
			}

			if ( \str_starts_with( $post_type_object->menu_icon, 'dashicons-' ) ) {
				$icon = $post_type_object->menu_icon;
			}
		}

		$email_addresses = ContactJson::get_email_addresses( $post_id );

		return [
			'id'         => $contact_id,
			'text'       => $name,
			'type'       => $post_type,
			'type_label' => $type_label,
			'icon'       => $icon,
			'email'      => $email_addresses[0]['email'] ?? null,
		];
	}

	/**
	 * Register scripts.
	 *
	 * The `select2` script and style are registered by the Orbis core plugin.
	 *
	 * @return void
	 */
	private function register_scripts() {
		$src = \plugins_url( 'js/contact-select2.js', $this->plugin_file );

		if ( '' === $src ) {
			return;
		}

		\wp_register_script(
			'orbis-contact-select2',
			$src,
			[
				'jquery',
				'select2',
			],
			'1.0.0',
			[
				'in_footer' => true,
			]
		);

		\wp_localize_script(
			'orbis-contact-select2',
			'orbisContactSelect2',
			[
				'restUrl' => \rest_url( 'orbis-contacts/v1/contacts/select2' ),
				'nonce'   => \wp_create_nonce( 'wp_rest' ),
			]
		);

		\wp_register_style( 'orbis-contact-select2', false, [ 'select2' ], '1.0.0' );

		\wp_add_inline_style(
			'orbis-contact-select2',
			'.orbis-contact-option { display: flex; align-items: center; gap: 6px; }
			.orbis-contact-option .dashicons { flex: none; color: #646970; }
			.orbis-contact-option-meta { display: block; font-size: 12px; color: #646970; }
			.select2-results__option--highlighted .orbis-contact-option .dashicons,
			.select2-results__option--highlighted .orbis-contact-option-meta { color: inherit; }'
		);
	}

	/**
	 * Enqueue the style when another plugin enqueued the script.
	 *
	 * @return void
	 */
	private function maybe_enqueue_style() {
		if ( \wp_script_is( 'orbis-contact-select2', 'enqueued' ) ) {
			\wp_enqueue_style( 'orbis-contact-select2' );
		}
	}
}
