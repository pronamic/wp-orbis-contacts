<?php
/**
 * Template controller
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

/**
 * Template controller class
 */
final class TemplateController {
	/**
	 * Construct.
	 */
	public function __construct() {
		\add_filter( 'template_include', $this->template_include( ... ) );
	}

	/**
	 * Template include.
	 *
	 * Uses the contacts archive template of this plugin, unless the theme has one.
	 *
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include( $template ) {
		if ( \is_post_type_archive( 'orbis_contact' ) && '' === \locate_template( 'archive-orbis_contact.php' ) ) {
			return __DIR__ . '/../templates/archive-orbis_contact.php';
		}

		return $template;
	}
}
