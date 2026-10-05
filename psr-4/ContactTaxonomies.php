<?php
/**
 * Contact taxonomies
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use WP_Post_Type;

/**
 * Contact taxonomies class
 *
 * Registers the taxonomies shared by all contacts and attaches them to
 * `orbis_contact` and every post type that supports `orbis_contact`.
 */
final class ContactTaxonomies {
	/**
	 * Construct contact taxonomies object.
	 */
	public function __construct() {
		\add_action( 'init', $this->register_taxonomies( ... ), 5 );

		\add_action( 'registered_post_type', $this->registered_post_type( ... ), 10, 2 );
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	private function register_taxonomies() {
		$object_types = \array_merge( [ 'orbis_contact' ], ContactPostType::get_contact_post_types() );

		\register_taxonomy(
			'orbis_contact_category',
			$object_types,
			[
				'hierarchical' => true,
				'labels'       => [
					'name'              => \_x( 'Categories', 'taxonomy general name', 'orbis-contacts' ),
					'singular_name'     => \_x( 'Category', 'taxonomy singular name', 'orbis-contacts' ),
					'search_items'      => \__( 'Search Categories', 'orbis-contacts' ),
					'all_items'         => \__( 'All Categories', 'orbis-contacts' ),
					'parent_item'       => \__( 'Parent Category', 'orbis-contacts' ),
					'parent_item_colon' => \__( 'Parent Category:', 'orbis-contacts' ),
					'edit_item'         => \__( 'Edit Category', 'orbis-contacts' ),
					'update_item'       => \__( 'Update Category', 'orbis-contacts' ),
					'add_new_item'      => \__( 'Add New Category', 'orbis-contacts' ),
					'new_item_name'     => \__( 'New Category Name', 'orbis-contacts' ),
					'menu_name'         => \__( 'Categories', 'orbis-contacts' ),
				],
				'show_ui'      => true,
				'query_var'    => true,
				'rewrite'      => [
					'slug' => \_x( 'contact-category', 'slug', 'orbis-contacts' ),
				],
			]
		);

		\register_taxonomy(
			'orbis_payment_method',
			$object_types,
			[
				'hierarchical' => true,
				'labels'       => [
					'name'              => \_x( 'Payment Methods', 'taxonomy general name', 'orbis-contacts' ),
					'singular_name'     => \_x( 'Payment Method', 'taxonomy singular name', 'orbis-contacts' ),
					'search_items'      => \__( 'Search Payment Methods', 'orbis-contacts' ),
					'all_items'         => \__( 'All Payment Methods', 'orbis-contacts' ),
					'parent_item'       => \__( 'Parent Payment Method', 'orbis-contacts' ),
					'parent_item_colon' => \__( 'Parent Payment Method:', 'orbis-contacts' ),
					'edit_item'         => \__( 'Edit Payment Method', 'orbis-contacts' ),
					'update_item'       => \__( 'Update Payment Method', 'orbis-contacts' ),
					'add_new_item'      => \__( 'Add New Payment Method', 'orbis-contacts' ),
					'new_item_name'     => \__( 'New Payment Method Name', 'orbis-contacts' ),
					'menu_name'         => \__( 'Payment Methods', 'orbis-contacts' ),
				],
				'show_ui'      => true,
				'query_var'    => true,
				'rewrite'      => [
					'slug' => \_x( 'payment-methods', 'slug', 'orbis-contacts' ),
				],
				'meta_box_cb'  => false,
			]
		);

		\register_taxonomy(
			'orbis_invoice_shipping_method',
			$object_types,
			[
				'hierarchical' => true,
				'labels'       => [
					'name'              => \_x( 'Invoice Shipping Methods', 'taxonomy general name', 'orbis-contacts' ),
					'singular_name'     => \_x( 'Invoice Shipping Method', 'taxonomy singular name', 'orbis-contacts' ),
					'search_items'      => \__( 'Search Invoice Shipping Methods', 'orbis-contacts' ),
					'all_items'         => \__( 'All Invoice Shipping Methods', 'orbis-contacts' ),
					'parent_item'       => \__( 'Parent Invoice Shipping Method', 'orbis-contacts' ),
					'parent_item_colon' => \__( 'Parent Invoice Shipping Method:', 'orbis-contacts' ),
					'edit_item'         => \__( 'Edit Invoice Shipping Method', 'orbis-contacts' ),
					'update_item'       => \__( 'Update Invoice Shipping Method', 'orbis-contacts' ),
					'add_new_item'      => \__( 'Add New Invoice Shipping Method', 'orbis-contacts' ),
					'new_item_name'     => \__( 'New Invoice Shipping Method Name', 'orbis-contacts' ),
					'menu_name'         => \__( 'Invoice Shipping Methods', 'orbis-contacts' ),
				],
				'show_ui'      => true,
				'query_var'    => true,
				'rewrite'      => [
					'slug' => \_x( 'invoice-shipping-methods', 'slug', 'orbis-contacts' ),
				],
				'meta_box_cb'  => false,
			]
		);
	}

	/**
	 * Attach the contact taxonomies to post types that support `orbis_contact`
	 * and are registered after the taxonomies (e.g. on `init` priority 10).
	 *
	 * @param string       $post_type        Post type.
	 * @param WP_Post_Type $post_type_object Post type object.
	 * @return void
	 */
	private function registered_post_type( string $post_type, WP_Post_Type $post_type_object ) {
		if ( ! \post_type_supports( $post_type_object->name, 'orbis-contact' ) ) {
			return;
		}

		foreach ( \get_object_taxonomies( 'orbis_contact' ) as $taxonomy ) {
			\register_taxonomy_for_object_type( $taxonomy, $post_type );
		}
	}
}
