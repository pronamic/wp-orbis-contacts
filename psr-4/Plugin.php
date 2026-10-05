<?php
/**
 * Plugin
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

/**
 * Plugin class
 */
final class Plugin {
	/**
	 * Instance.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Return instance of this class.
	 *
	 * @param string $plugin_file The plugin file.
	 * @return self A single instance of this class.
	 */
	public static function instance( string $plugin_file ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $plugin_file );
		}

		return self::$instance;
	}

	/**
	 * Construct.
	 *
	 * @param string $plugin_file The plugin file.
	 */
	private function __construct(
		/**
		 * Plugin file.
		 */
		private readonly string $plugin_file
	) {
		\register_activation_hook( $this->plugin_file, $this->activate( ... ) );

		\add_action( 'plugins_loaded', $this->plugins_loaded( ... ) );
	}

	/**
	 * Activate.
	 *
	 * @return void
	 */
	private function activate() {
		\delete_option( 'rewrite_rules' );
	}

	/**
	 * Plugins loaded.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		new ContactPostType();
		new ContactTaxonomies();
		new ContactsTable();
	}
}
