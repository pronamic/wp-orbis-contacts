/**
 * Contact Select2
 *
 * Turns `select.orbis-contact-id-control` elements into a contact picker,
 * a preselected `<option>` can provide `data-icon`, `data-type-label` and
 * `data-email` attributes.
 *
 * @package Pronamic\Orbis\Contacts
 */

/* global orbisContactSelect2 */

jQuery( function ( $ ) {
	const getData = ( item ) => {
		const dataset = item.element?.dataset ?? {};

		return {
			text: item.text,
			icon: item.icon ?? dataset.icon,
			typeLabel: item.type_label ?? dataset.typeLabel,
			email: item.email ?? dataset.email,
		};
	};

	const templateResult = ( item ) => {
		const data = getData( item );

		if ( item.loading || ! data.icon ) {
			return item.text;
		}

		const meta = [ data.typeLabel, data.email ].filter( Boolean ).join( ' · ' );

		return $( '<span class="orbis-contact-option"></span>' ).append(
			$( '<span class="dashicons" aria-hidden="true"></span>' ).addClass( data.icon ),
			$( '<span></span>' ).append(
				$( '<strong></strong>' ).text( data.text ),
				$( '<span class="orbis-contact-option-meta"></span>' ).text( meta )
			)
		);
	};

	const templateSelection = ( item ) => {
		const data = getData( item );

		if ( ! data.icon ) {
			return item.text;
		}

		return $( '<span class="orbis-contact-option"></span>' ).append(
			$( '<span class="dashicons" aria-hidden="true"></span>' )
				.addClass( data.icon )
				.attr( 'title', data.typeLabel ?? '' ),
			$( '<span></span>' ).text( data.text )
		);
	};

	$( '.orbis-contact-id-control' ).select2( {
		width: '100%',
		allowClear: true,
		placeholder: '',
		minimumInputLength: 2,
		ajax: {
			url: orbisContactSelect2.restUrl,
			dataType: 'json',
			delay: 250,
			headers: {
				'X-WP-Nonce': orbisContactSelect2.nonce,
			},
			data: ( params ) => ( {
				term: params.term,
			} ),
		},
		templateResult,
		templateSelection,
	} );
} );
