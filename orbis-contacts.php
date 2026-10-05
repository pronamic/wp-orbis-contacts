<?php
/**
 * Orbis Contacts
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 *
 * @wordpress-plugin
 * Plugin Name:       Orbis Contacts
 * Plugin URI:        https://github.com/pronamic/wp-orbis-contacts
 * Description:       WordPress plugin for Orbis that provides a shared contact layer for persons and organizations, including email addresses, phone numbers, social accounts and relations between contacts.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Author:            Pronamic
 * Author URI:        https://www.pronamic.eu/
 * Text Domain:       orbis-contacts
 * Domain Path:       /languages/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub URI:        https://github.com/pronamic/wp-orbis-contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

( static function (): void {
	$autoload_path = __DIR__ . '/vendor/autoload_packages.php';

	if ( \file_exists( $autoload_path ) ) {
		require_once $autoload_path;
	}

	Plugin::instance( __FILE__ );
} )();
