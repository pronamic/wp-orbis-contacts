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
