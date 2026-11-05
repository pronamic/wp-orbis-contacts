<?php
/**
 * CLI controller
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_CLI;
use WP_Query;

/**
 * CLI controller class
 */
final class CliController {
	/**
	 * Construct CLI controller object.
	 */
	public function __construct() {
		\add_action( 'cli_init', $this->cli_init( ... ) );
	}

	/**
	 * CLI initialize.
	 *
	 * @return void
	 */
	private function cli_init() {
		$synopsis = [
			[
				'type'        => 'assoc',
				'name'        => 'post_type',
				'description' => 'Only migrate posts of this post type, defaults to all post types that support `orbis-contact`.',
				'optional'    => true,
			],
			[
				'type'        => 'flag',
				'name'        => 'dry-run',
				'description' => 'Show what would be migrated without saving anything.',
				'optional'    => true,
			],
		];

		WP_CLI::add_command(
			'orbis-contacts migrate-email-addresses',
			$this->migrate_email_addresses( ... ),
			[
				'shortdesc' => 'Migrate the `_orbis_email` post meta to the contact email addresses.',
				'longdesc'  => 'Adds the old email address to the contact email addresses (`_orbis_contact_email` and `_orbis_contact_json` post meta), without label. Invalid email addresses and email addresses the contact already has are skipped, so the command can be run multiple times. The old post meta is kept.',
				'synopsis'  => $synopsis,
			]
		);

		WP_CLI::add_command(
			'orbis-contacts migrate-phone-numbers',
			$this->migrate_phone_numbers( ... ),
			[
				'shortdesc' => 'Migrate the `_orbis_phone_number` and `_orbis_mobile_number` post meta to the contact phone numbers.',
				'longdesc'  => 'Adds the old phone numbers to the contact phone numbers (`_orbis_contact_phone` and `_orbis_contact_json` post meta), standardized to E.164 with the default phone country. Phone numbers that could not be standardized are kept with `e164` set to `null`. Phone numbers the contact already has are skipped, so the command can be run multiple times. The old post meta is kept.',
				'synopsis'  => $synopsis,
			]
		);
	}

	/**
	 * Get the post types to migrate.
	 *
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return array<string> Post types.
	 */
	private function get_post_types( array $assoc_args ): array {
		$post_types = ContactPostType::get_contact_post_types();

		if ( \array_key_exists( 'post_type', $assoc_args ) ) {
			if ( ! \in_array( $assoc_args['post_type'], $post_types, true ) ) {
				WP_CLI::error( \sprintf( 'Post type `%s` does not support `orbis-contact`.', $assoc_args['post_type'] ) );
			}

			$post_types = [
				$assoc_args['post_type'],
			];
		}

		if ( [] === $post_types ) {
			WP_CLI::error( 'No post types support `orbis-contact`.' );
		}

		return $post_types;
	}

	/**
	 * Get the posts with one of the meta keys.
	 *
	 * Logs the number of posts found and a line per post, before the post
	 * is yielded, so the caller can log what happens to it.
	 *
	 * @param array<string> $post_types Post types.
	 * @param array<string> $meta_keys  Meta keys.
	 * @return \Generator<int, int> Post IDs.
	 */
	private function each_post( array $post_types, array $meta_keys ): \Generator {
		$meta_query = [
			'relation' => 'OR',
		];

		foreach ( $meta_keys as $meta_key ) {
			$meta_query[] = [
				'key'     => $meta_key,
				'compare' => 'EXISTS',
			];
		}

		$index = 0;

		/*
		 * The old post meta is kept, so the paged query results don't shift
		 * while migrating.
		 */
		$page = 1;

		do {
			$query = new WP_Query(
				[
					'post_type'      => $post_types,
					'post_status'    => 'any',
					'posts_per_page' => 100,
					'paged'          => $page,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One-time migration.
					'meta_query'     => $meta_query,
				]
			);

			if ( 1 === $page ) {
				WP_CLI::log( \sprintf( 'Found %d posts with `%s` post meta.', $query->found_posts, \implode( '` or `', $meta_keys ) ) );
			}

			foreach ( \wp_parse_id_list( $query->posts ?? [] ) as $post_id ) {
				++$index;

				WP_CLI::log( \sprintf( '[%d/%d] Post %d (%s)', $index, $query->found_posts, $post_id, \get_the_title( $post_id ) ) );

				yield $post_id;
			}

			++$page;
		} while ( $page <= $query->max_num_pages );
	}

	/**
	 * Add a post result to the counts.
	 *
	 * @param array{posts: int, added: int, invalid: int, existing: int} $counts Counts.
	 * @param array{added: int, invalid: int, existing: int}             $result Post result.
	 * @return array{posts: int, added: int, invalid: int, existing: int} Counts.
	 */
	private function add_counts( array $counts, array $result ): array {
		return [
			'posts'    => $counts['posts'] + ( $result['added'] > 0 ? 1 : 0 ),
			'added'    => $counts['added'] + $result['added'],
			'invalid'  => $counts['invalid'] + $result['invalid'],
			'existing' => $counts['existing'] + $result['existing'],
		];
	}

	/**
	 * Migrate email addresses.
	 *
	 * @param array<string>         $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	private function migrate_email_addresses( array $args, array $assoc_args ) {
		$dry_run = \array_key_exists( 'dry-run', $assoc_args );

		$counts = [
			'posts'    => 0,
			'added'    => 0,
			'invalid'  => 0,
			'existing' => 0,
		];

		$post_ids = $this->each_post(
			$this->get_post_types( $assoc_args ),
			[
				'_orbis_email',
			]
		);

		foreach ( $post_ids as $post_id ) {
			$counts = $this->add_counts( $counts, $this->migrate_post_email_addresses( $post_id, $dry_run ) );
		}

		WP_CLI::success(
			\sprintf(
				'%s %d email addresses for %d posts, %d invalid, %d already existed.',
				$dry_run ? 'Would migrate' : 'Migrated',
				$counts['added'],
				$counts['posts'],
				$counts['invalid'],
				$counts['existing']
			)
		);
	}

	/**
	 * Migrate the email address of one post.
	 *
	 * Logs a line with what happens to the old email address.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $dry_run Dry run.
	 * @return array{added: int, invalid: int, existing: int} Counts.
	 */
	private function migrate_post_email_addresses( int $post_id, bool $dry_run ): array {
		$result = [
			'added'    => 0,
			'invalid'  => 0,
			'existing' => 0,
		];

		$value = \get_post_meta( $post_id, '_orbis_email', true );

		$input = \trim( \is_string( $value ) ? $value : '' );

		if ( '' === $input ) {
			WP_CLI::log( '  _orbis_email: empty, skipped' );

			return $result;
		}

		/*
		 * Validate before sanitizing, `sanitize_email()` silently strips
		 * invalid characters (e.g. `privé@example.com` → `priv@example.com`).
		 */
		if ( false === \is_email( $input ) ) {
			++$result['invalid'];

			WP_CLI::log( \sprintf( '  _orbis_email: `%s`, not a valid email address, skipped', $input ) );

			return $result;
		}

		$email = \sanitize_email( $input );

		$email_addresses = ContactJson::get_email_addresses( $post_id );

		$existing = \array_map(
			fn( $email_address ) => \strtolower( $email_address['email'] ),
			$email_addresses
		);

		if ( \in_array( \strtolower( $email ), $existing, true ) ) {
			++$result['existing'];

			WP_CLI::log( \sprintf( '  _orbis_email: `%s`, already exists, skipped', $email ) );

			return $result;
		}

		$result['added'] = 1;

		WP_CLI::log( \sprintf( '  _orbis_email: `%s`, %s without label', $email, $dry_run ? 'would be added' : 'added' ) );

		if ( $dry_run ) {
			return $result;
		}

		\add_post_meta( $post_id, '_orbis_contact_email', $email );

		$email_addresses[] = [
			'email' => $email,
			'label' => '',
		];

		ContactJson::update( $post_id, $email_addresses );

		return $result;
	}

	/**
	 * Migrate phone numbers.
	 *
	 * @param array<string>         $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	private function migrate_phone_numbers( array $args, array $assoc_args ) {
		$dry_run = \array_key_exists( 'dry-run', $assoc_args );

		$labels = [
			'_orbis_phone_number'  => '',
			'_orbis_mobile_number' => \__( 'Mobile', 'orbis-contacts' ),
		];

		$counts = [
			'posts'    => 0,
			'added'    => 0,
			'invalid'  => 0,
			'existing' => 0,
		];

		$post_ids = $this->each_post(
			$this->get_post_types( $assoc_args ),
			\array_keys( $labels )
		);

		foreach ( $post_ids as $post_id ) {
			$counts = $this->add_counts( $counts, $this->migrate_post_phone_numbers( $post_id, $labels, $dry_run ) );
		}

		WP_CLI::success(
			\sprintf(
				'%s %d phone numbers for %d posts, %d could not be standardized, %d already existed.',
				$dry_run ? 'Would migrate' : 'Migrated',
				$counts['added'],
				$counts['posts'],
				$counts['invalid'],
				$counts['existing']
			)
		);
	}

	/**
	 * Migrate the phone numbers of one post.
	 *
	 * Logs a line for every old phone number, with what happens to it.
	 *
	 * @param int                   $post_id Post ID.
	 * @param array<string, string> $labels  Labels by old meta key.
	 * @param bool                  $dry_run Dry run.
	 * @return array{added: int, invalid: int, existing: int} Number of migrated phone numbers, of those that could not be standardized and of those that already existed.
	 */
	private function migrate_post_phone_numbers( int $post_id, array $labels, bool $dry_run ): array {
		$result = [
			'added'    => 0,
			'invalid'  => 0,
			'existing' => 0,
		];

		$phone_numbers = ContactJson::get_phone_numbers( $post_id );

		$keys = \array_map(
			fn( $phone_number ) => $phone_number['e164'] ?? $phone_number['input'],
			$phone_numbers
		);

		$new_phone_numbers = [];

		foreach ( $labels as $meta_key => $label ) {
			$value = \get_post_meta( $post_id, $meta_key, true );

			$input = \sanitize_text_field( \is_string( $value ) ? $value : '' );

			if ( '' === $input ) {
				WP_CLI::log( \sprintf( '  %s: empty, skipped', $meta_key ) );

				continue;
			}

			$e164 = PhoneNumbers::to_e164( $input );

			$key = $e164 ?? $input;

			$line = \sprintf( '  %s: `%s` → %s', $meta_key, $input, $e164 ?? 'null' );

			if ( \in_array( $key, $keys, true ) ) {
				++$result['existing'];

				WP_CLI::log( $line . ', already exists, skipped' );

				continue;
			}

			$keys[] = $key;

			$new_phone_numbers[] = [
				'input' => $input,
				'label' => $label,
				'e164'  => $e164,
			];

			$action = $dry_run ? 'would be added' : 'added';

			$action .= '' === $label ? ' without label' : \sprintf( ' with label "%s"', $label );

			if ( null === $e164 ) {
				++$result['invalid'];

				WP_CLI::log( \sprintf( '%s, could not be standardized, %s and without E.164', $line, $action ) );

				continue;
			}

			WP_CLI::log( \sprintf( '%s, %s', $line, $action ) );
		}

		$result['added'] = \count( $new_phone_numbers );

		if ( $dry_run || [] === $new_phone_numbers ) {
			return $result;
		}

		foreach ( $new_phone_numbers as $phone_number ) {
			if ( null !== $phone_number['e164'] ) {
				\add_post_meta( $post_id, '_orbis_contact_phone', $phone_number['e164'] );
			}
		}

		ContactJson::update( $post_id, null, \array_merge( $phone_numbers, $new_phone_numbers ) );

		return $result;
	}
}
