/**
 * Contact details meta box
 *
 * Every `.orbis-contact-detail-list` (email addresses, phone numbers) has
 * its own add button and row template in the same table cell.
 *
 * @package Pronamic\Orbis\Contacts
 */

document.querySelectorAll( '#orbis_contact_details' ).forEach( ( metaBox ) => {
	const indexes = new Map();

	metaBox.querySelectorAll( '.orbis-contact-detail-list' ).forEach( ( list ) => {
		indexes.set( list, list.children.length );
	} );

	metaBox.addEventListener( 'click', ( event ) => {
		const addButton = event.target.closest( '.orbis-contact-detail-add' );

		if ( addButton ) {
			const cell = addButton.closest( 'td' );
			const list = cell?.querySelector( '.orbis-contact-detail-list' );
			const template = cell?.querySelector( '.orbis-contact-detail-template' );

			if ( ! list || ! template ) {
				return;
			}

			const index = indexes.get( list ) ?? list.children.length;

			indexes.set( list, index + 1 );

			list.insertAdjacentHTML( 'beforeend', template.innerHTML.replaceAll( '__index__', String( index ) ) );

			list.lastElementChild?.querySelector( 'input' )?.focus();
		}

		const removeButton = event.target.closest( '.orbis-contact-detail-remove' );

		if ( removeButton ) {
			removeButton.closest( '.orbis-contact-detail-row' )?.remove();
		}
	} );
} );
