<?php
/**
 * Contacts table
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_Post;
use wpdb;

/**
 * Contacts table class
 *
 * Keeps a row in `{$wpdb->prefix}orbis_contacts` for every post of a
 * post type that supports `orbis_contact`.
 */
final class ContactsTable {
	/**
	 * Construct contacts table object.
	 */
	public function __construct() {
		$this->maybe_install();

		\add_action( 'wp_after_insert_post', $this->sync_post( ... ), 10, 2 );

		\add_action( 'deleted_post', $this->delete_post( ... ) );
	}

	/**
	 * Get table name.
	 *
	 * @return string Table name.
	 */
	public static function get_table_name(): string {
		return self::get_wpdb()->prefix . 'orbis_contacts';
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
	 * Get contact ID by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return int|null Contact ID.
	 */
	public static function get_contact_id( int $post_id ): ?int {
		$wpdb = self::get_wpdb();

		$contact_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE post_id = %d',
				self::get_table_name(),
				$post_id
			)
		);

		return null === $contact_id ? null : (int) $contact_id;
	}

	/**
	 * Maybe install or upgrade the table.
	 *
	 * @return void
	 */
	private function maybe_install() {
		$version = '1.1.0';

		if ( \get_option( 'orbis_contacts_db_version' ) === $version ) {
			return;
		}

		$wpdb = self::get_wpdb();

		$table_name = self::get_table_name();

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta(
			"CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20) UNSIGNED NOT NULL,
				name VARCHAR(255) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY name (name(191))
			) $charset_collate;"
		);

		/**
		 * The `dbDelta()` function does not support foreign keys, so we
		 * add the foreign key on `post_id` ourselves if it doesn't exist.
		 *
		 * @link https://core.trac.wordpress.org/ticket/19207
		 */
		$foreign_key_name = $table_name . '_post_id_fk';

		$foreign_key_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s AND CONSTRAINT_TYPE = %s',
				$table_name,
				$foreign_key_name,
				'FOREIGN KEY'
			)
		);

		if ( null === $foreign_key_exists ) {
			$wpdb->query(
				(string) $wpdb->prepare(
					'ALTER TABLE %i ADD CONSTRAINT %i FOREIGN KEY ( post_id ) REFERENCES %i ( ID ) ON DELETE CASCADE', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
					$table_name,
					$foreign_key_name,
					$wpdb->posts
				)
			);
		}

		\update_option( 'orbis_contacts_db_version', $version );
	}

	/**
	 * Sync post to contacts table.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	private function sync_post( int $post_id, WP_Post $post ) {
		if ( ! \post_type_supports( $post->post_type, 'orbis-contact' ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$wpdb = self::get_wpdb();

		$now = \current_time( 'mysql', true );

		$name = \mb_substr( $post->post_title, 0, 255 );

		$wpdb->query(
			(string) $wpdb->prepare(
				'INSERT INTO %i ( post_id, name, created_at, updated_at ) VALUES ( %d, %s, %s, %s ) ON DUPLICATE KEY UPDATE name = VALUES( name ), updated_at = VALUES( updated_at )',
				self::get_table_name(),
				$post_id,
				$name,
				$now,
				$now
			)
		);
	}

	/**
	 * Delete post from contacts table.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function delete_post( int $post_id ) {
		$wpdb = self::get_wpdb();

		$wpdb->delete(
			self::get_table_name(),
			[
				'post_id' => $post_id,
			],
			[
				'%d',
			]
		);
	}
}
