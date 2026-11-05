<?php
/**
 * Phone numbers
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Contacts;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Phone numbers class
 *
 * Standardizes phone numbers to E.164 (e.g. `+31612345678`) with
 * `giggsey/libphonenumber-for-php`. Numbers without a country calling code
 * (e.g. `06 12345678`) are parsed with the default country from the
 * `orbis_contacts_default_country` option on Settings → General.
 *
 * @link https://github.com/giggsey/libphonenumber-for-php
 * @link https://en.wikipedia.org/wiki/E.164
 */
final class PhoneNumbers {
	/**
	 * Construct phone numbers object.
	 */
	public function __construct() {
		\add_action( 'admin_init', $this->admin_init( ... ) );
	}

	/**
	 * Admin init.
	 *
	 * @return void
	 */
	private function admin_init() {
		\register_setting(
			'general',
			'orbis_contacts_default_country',
			[
				'type'              => 'string',
				'sanitize_callback' => $this->sanitize_country( ... ),
				'default'           => '',
			]
		);

		\add_settings_field(
			'orbis_contacts_default_country',
			\__( 'Default Phone Country', 'orbis-contacts' ),
			$this->render_country_field( ... ),
			'general',
			'default',
			[
				'label_for' => 'orbis_contacts_default_country',
			]
		);
	}

	/**
	 * Sanitize country.
	 *
	 * @param mixed $value Value.
	 * @return string Country code, or an empty string for the default.
	 */
	private function sanitize_country( $value ): string {
		$country = \strtoupper( \is_string( $value ) ? \trim( $value ) : '' );

		return \in_array( $country, self::get_supported_countries(), true ) ? $country : '';
	}

	/**
	 * Render country field.
	 *
	 * @return void
	 */
	private function render_country_field() {
		$current = self::get_default_country();

		$countries = [];

		foreach ( self::get_supported_countries() as $country ) {
			$name = \class_exists( \Locale::class ) ? \Locale::getDisplayRegion( 'und_' . $country, \get_user_locale() ) : '';

			$countries[ $country ] = ( '' === $name || false === $name ) ? $country : $name;
		}

		\asort( $countries, \SORT_NATURAL | \SORT_FLAG_CASE );

		?>
		<select id="orbis_contacts_default_country" name="orbis_contacts_default_country">
			<?php foreach ( $countries as $country => $name ) : ?>
				<option value="<?php echo \esc_attr( $country ); ?>" <?php \selected( $country, $current ); ?>>
					<?php echo \esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<p class="description">
			<?php \esc_html_e( 'Country used to standardize contact phone numbers entered without a country calling code.', 'orbis-contacts' ); ?>
		</p>
		<?php
	}

	/**
	 * Get supported countries.
	 *
	 * @return array<string> ISO 3166-1 alpha-2 country codes.
	 */
	private static function get_supported_countries(): array {
		return \array_values( PhoneNumberUtil::getInstance()->getSupportedRegions() );
	}

	/**
	 * Get default country.
	 *
	 * The `orbis_contacts_default_country` option, falls back to the
	 * country of the site locale (e.g. `nl_NL` → `NL`) and then `NL`.
	 *
	 * @return string ISO 3166-1 alpha-2 country code.
	 */
	public static function get_default_country(): string {
		$supported = self::get_supported_countries();

		$option = \get_option( 'orbis_contacts_default_country' );

		if ( \is_string( $option ) && \in_array( $option, $supported, true ) ) {
			return $option;
		}

		$parts = \explode( '_', \get_locale() );

		$country = \strtoupper( $parts[1] ?? '' );

		if ( \in_array( $country, $supported, true ) ) {
			return $country;
		}

		return 'NL';
	}

	/**
	 * Standardize a phone number to E.164.
	 *
	 * @param string $input Phone number as entered, e.g. `06 12345678` or `+32 2 123 45 67`.
	 * @return string|null E.164 phone number, e.g. `+31612345678`, `null` if the input is not a valid phone number.
	 */
	public static function to_e164( string $input ): ?string {
		$phone_util = PhoneNumberUtil::getInstance();

		try {
			$phone_number = $phone_util->parse( $input, self::get_default_country() );
		} catch ( NumberParseException ) {
			return null;
		}

		if ( ! $phone_util->isValidNumber( $phone_number ) ) {
			return null;
		}

		return $phone_util->format( $phone_number, PhoneNumberFormat::E164 );
	}

	/**
	 * Format an E.164 phone number in international format.
	 *
	 * @param string $e164 E.164 phone number, e.g. `+31612345678`.
	 * @return string International format, e.g. `+31 6 12345678`, or the E.164 value if it can't be parsed.
	 */
	public static function format_international( string $e164 ): string {
		$phone_util = PhoneNumberUtil::getInstance();

		try {
			$phone_number = $phone_util->parse( $e164 );
		} catch ( NumberParseException ) {
			return $e164;
		}

		return $phone_util->format( $phone_number, PhoneNumberFormat::INTERNATIONAL );
	}
}
