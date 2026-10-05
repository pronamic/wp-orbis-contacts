<?php
/**
 * Contact post type
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_Post;
use WP_Query;

/**
 * Contact post type class
 *
 * The `orbis_contact` post type has no posts of its own. Post types that
 * support `orbis_contact` (e.g. `orbis_person` and `orbis_organization`)
 * are the contacts, and a query for `orbis_contact` queries all of them.
 */
final class ContactPostType {
	/**
	 * Construct contact post type object.
	 */
	public function __construct() {
		\add_action( 'init', $this->register_post_type( ... ), 5 );

		\add_action( 'pre_get_posts', $this->pre_get_posts( ... ) );

		\add_filter( 'the_posts', $this->the_posts( ... ), 10, 2 );

		\add_filter( 'wp_count_posts', $this->count_posts( ... ), 10, 3 );

		\add_filter( 'manage_orbis_contact_posts_columns', $this->manage_posts_columns( ... ) );

		/*
		 * WordPress fires `manage_{$post_type}_posts_custom_column` with the
		 * post type of each post (e.g. `orbis_person`), not `orbis_contact`.
		 */
		\add_action( 'manage_posts_custom_column', $this->posts_custom_column( ... ), 10, 2 );
		\add_action( 'manage_pages_custom_column', $this->posts_custom_column( ... ), 10, 2 );
	}

	/**
	 * Get the post types that support `orbis_contact`.
	 *
	 * @return array<string> Post types.
	 */
	public static function get_contact_post_types(): array {
		return \array_values( \get_post_types_by_support( 'orbis-contact' ) );
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	private function register_post_type() {
		\register_post_type(
			'orbis_contact',
			[
				'label'               => \__( 'Contacts', 'orbis-contacts' ),
				'labels'              => [
					'name'          => \_x( 'Contacts', 'post type general name', 'orbis-contacts' ),
					'singular_name' => \_x( 'Contact', 'post type singular name', 'orbis-contacts' ),
					'menu_name'     => \_x( 'Contacts', 'admin menu', 'orbis-contacts' ),
					'all_items'     => \__( 'All Contacts', 'orbis-contacts' ),
					'search_items'  => \__( 'Search Contacts', 'orbis-contacts' ),
					'not_found'     => \__( 'No contacts found.', 'orbis-contacts' ),
					'archives'      => \__( 'Contacts', 'orbis-contacts' ),
				],
				'public'              => true,
				'exclude_from_search' => true,
				'show_in_rest'        => false,
				'menu_position'       => 30,
				'menu_icon'           => 'dashicons-groups',
				'supports'            => [ 'title' ],
				'has_archive'         => true,
				'rewrite'             => [
					'slug' => \_x( 'contacts', 'slug', 'orbis-contacts' ),
				],
				'map_meta_cap'        => true,
				'capabilities'        => [
					'create_posts' => 'do_not_allow',
				],
			]
		);
	}

	/**
	 * Pre get posts.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	private function pre_get_posts( WP_Query $query ) {
		if ( 'orbis_contact' !== $query->get( 'post_type' ) ) {
			return;
		}

		$post_types = self::get_contact_post_types();

		if ( [] === $post_types ) {
			return;
		}

		$query->set( 'post_type', $post_types );
	}

	/**
	 * Restore the `orbis_contact` post type query var after querying, so
	 * the queried object, template hierarchy and admin screen stay `orbis_contact`.
	 *
	 * @param array<WP_Post|int> $posts Posts.
	 * @param WP_Query           $query Query.
	 * @return array<WP_Post|int> Posts.
	 */
	private function the_posts( array $posts, WP_Query $query ): array {
		if ( 'orbis_contact' === ( $query->query['post_type'] ?? null ) ) {
			$query->set( 'post_type', 'orbis_contact' );
		}

		return $posts;
	}

	/**
	 * Count posts of all contact post types for `orbis_contact`.
	 *
	 * @param object $counts    Counts.
	 * @param string $post_type Post type.
	 * @param string $perm      Permission.
	 * @return object Counts.
	 */
	private function count_posts( object $counts, string $post_type, string $perm ): object {
		if ( 'orbis_contact' !== $post_type ) {
			return $counts;
		}

		$totals = [];

		foreach ( self::get_contact_post_types() as $contact_post_type ) {
			foreach ( \get_object_vars( \wp_count_posts( $contact_post_type, $perm ) ) as $status => $count ) {
				$totals[ $status ] = ( $totals[ $status ] ?? 0 ) + ( \is_numeric( $count ) ? (int) $count : 0 );
			}
		}

		return (object) \array_merge( \get_object_vars( $counts ), $totals );
	}

	/**
	 * Add a type column to the contacts admin list.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string> Columns.
	 */
	private function manage_posts_columns( array $columns ): array {
		$result = [];

		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;

			if ( 'title' === $key ) {
				$result['orbis_contact_type'] = \__( 'Type', 'orbis-contacts' );
			}
		}

		if ( ! \array_key_exists( 'orbis_contact_type', $result ) ) {
			$result['orbis_contact_type'] = \__( 'Type', 'orbis-contacts' );
		}

		return $result;
	}

	/**
	 * Render the type column in the contacts admin list.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	private function posts_custom_column( string $column, int $post_id ) {
		if ( 'orbis_contact_type' !== $column ) {
			return;
		}

		$screen = \get_current_screen();

		if ( null === $screen || 'orbis_contact' !== $screen->post_type ) {
			return;
		}

		$post_type_object = \get_post_type_object( (string) \get_post_type( $post_id ) );

		if ( null === $post_type_object ) {
			return;
		}

		$label = $post_type_object->labels->singular_name ?? $post_type_object->label;

		if ( ! \is_string( $label ) ) {
			return;
		}

		echo \esc_html( $label );
	}
}
