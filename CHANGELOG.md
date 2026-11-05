# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial plugin scaffold based on the [SolveBeam WordPress plugin boilerplate](https://github.com/solvebeam/solvebeam-wordpress-plugin-boilerplate).
- `orbis_contact` post type that queries all post types supporting `orbis_contact`, with a contacts archive and admin menu.
- `orbis_contacts` table with a row for every contact post.
- `orbis-contacts/v1/contacts/select2` REST API endpoint and `orbis-contact-select2` script for a contact picker.
- Multiple phone numbers with labels in the "Contact Details" meta box, standardized to E.164 with `giggsey/libphonenumber-for-php` and stored one per `_orbis_contact_phone` post meta row.
- "Default Phone Country" setting on Settings → General.

[unreleased]: https://github.com/pronamic/wp-orbis-contacts/commits/main
