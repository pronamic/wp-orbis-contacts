# Orbis Contacts

WordPress plugin for Orbis that provides a shared contact layer for [Orbis Persons](https://github.com/pronamic/wp-orbis-persons) and [Orbis Organizations](https://github.com/pronamic/wp-orbis-organizations), including email addresses, phone numbers, social accounts and relations between contacts.

This plugin is based on the [SolveBeam WordPress plugin boilerplate](https://github.com/solvebeam/solvebeam-wordpress-plugin-boilerplate), see its README for the architecture and coding conventions.

## Contact post types

A post type becomes a contact by supporting `orbis-contact`, Orbis Persons and Orbis Organizations register their post types like this:

```php
\register_post_type(
	'orbis_person',
	[
		'supports' => [
			'title',
			'editor',
			'orbis-contact',
		],
	]
);
```

- `orbis_contact` is a post type without posts of its own. A query for `orbis_contact` queries all contact post types, which gives a contacts archive (`/contacts/`) and a "Contacts" admin menu with all contacts.
- Every contact post gets a row in the `{prefix}orbis_contacts` table (`id`, `post_id`, `created_at`, `updated_at`).
- Contact details are stored in post meta, connections between contacts use [Posts 2 Posts](https://github.com/scribu/wp-posts-to-posts).

## Contact details

Every contact post type gets a "Contact Details" meta box with multiple email addresses, each with a label (e.g. Work, Home, Other).

| Meta key | Value |
|---|---|
| `_orbis_contact_email` | One row per email address, a plain email address string. |
| `_orbis_contact_json` | The whole contact as one JSON string, see [`json-schemas/contact.json`](json-schemas/contact.json). |

Because every email address is a separate meta row, contacts are easy to find with a meta query:

```php
$query = new \WP_Query(
	[
		'post_type'  => 'orbis_contact',
		'meta_key'   => '_orbis_contact_email',
		'meta_value' => 'info@example.com',
	]
);
```

The `_orbis_contact_json` post meta looks like this:

```json
{
	"$schema": "https://github.com/pronamic/wp-orbis-contacts/json-schemas/contact.json",
	"post_id": 123,
	"contact_id": 45,
	"type": "orbis_person",
	"name": "John Doe",
	"email_addresses": [
		{
			"email": "info@example.com",
			"label": "Work"
		}
	]
}
```

Use `ContactJson::get( $post_id )` and `ContactJson::get_email_addresses( $post_id )` to read the contact details.

## Contact picker (Select2)

Other plugins can let users pick a contact with a `select` element with the `orbis-contact-id-control` class. The value is an ID from the `{prefix}orbis_contacts` table.

```php
\wp_enqueue_script( 'orbis-contact-select2' );
```

```html
<select name="contact_id" class="orbis-contact-id-control">
	<option value="45" data-icon="dashicons-businessman" data-type-label="Person" data-email="info@example.com">John Doe</option>
</select>
```

Use `ContactSelect2Controller::get_option_data( $contact_id )` for the data of a preselected option. The script searches through the `orbis-contacts/v1/contacts/select2?term=…` REST API endpoint, which returns the Select2 data format:

```json
{
	"results": [
		{
			"id": 45,
			"text": "John Doe",
			"type": "orbis_person",
			"type_label": "Person",
			"icon": "dashicons-businessman",
			"email": "info@example.com"
		}
	]
}
```

The `select2` script and style are registered by the Orbis core plugin.

## Templates

The plugin ships an archive contact template, modelled after the person archive template of [Orbis Persons](https://github.com/pronamic/wp-orbis-persons). It is used unless the theme has its own `archive-orbis_contact.php`.

| Template | Shown on | Content |
|---|---|---|
| `templates/archive-orbis_contact.php` | Contacts archive (`/contacts/`) | Table with photo, name, type, email addresses, categories and author |

## Requirements

- PHP 8.3+
- WordPress 7.1+
- Composer
- Node.js / npm

## Installation

```sh
composer install
npm install
```

## Development

```sh
npx wp-env start
```

| Mount path | Source |
|---|---|
| `wp-content/plugins/orbis-contacts-dev` | `./` |
| `wp-content/plugins/orbis-contacts` | `./build/orbis-contacts/` |

```sh
composer run phpcs
composer run phpstan
composer run build
composer run make-pot
```

## Deploy

```sh
vendor/bin/dep deploy
```

## License

GPL-2.0-or-later
