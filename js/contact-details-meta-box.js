/**
 * Contact details meta box
 *
 * @package Pronamic\Orbis\Contacts
 */

document.querySelectorAll( '#orbis_contact_details' ).forEach( ( metaBox ) => {
	const list = metaBox.querySelector( '.orbis-contact-email-addresses' );
	const template = metaBox.querySelector( '.orbis-contact-email-address-template' );

	if ( ! list || ! template ) {
		return;
	}

	let index = list.children.length;

	metaBox.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '.orbis-contact-email-address-add' ) ) {
			const html = template.innerHTML.replaceAll( '__index__', String( index++ ) );

			list.insertAdjacentHTML( 'beforeend', html );

			list.lastElementChild?.querySelector( 'input' )?.focus();
		}

		const removeButton = event.target.closest( '.orbis-contact-email-address-remove' );

		if ( removeButton ) {
			removeButton.closest( '.orbis-contact-email-address' )?.remove();
		}
	} );
} );
