/**
 * Contact details meta box
 *
 * @package Pronamic\Orbis\Contacts
 */

document.querySelectorAll( '#orbis_contact_details' ).forEach( ( metaBox ) => {
	const tbody = metaBox.querySelector( '.orbis-contact-email-addresses tbody' );
	const template = metaBox.querySelector( '.orbis-contact-email-address-template' );

	if ( ! tbody || ! template ) {
		return;
	}

	let index = tbody.rows.length;

	metaBox.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '.orbis-contact-email-address-add' ) ) {
			const html = template.innerHTML.replaceAll( '__index__', String( index++ ) );

			tbody.insertAdjacentHTML( 'beforeend', html );

			tbody.lastElementChild?.querySelector( 'input' )?.focus();
		}

		const removeButton = event.target.closest( '.orbis-contact-email-address-remove' );

		if ( removeButton ) {
			removeButton.closest( 'tr' )?.remove();
		}
	} );
} );
