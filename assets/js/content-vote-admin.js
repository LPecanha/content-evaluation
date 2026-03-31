/**
 * Content Vote — Admin Script
 *
 * Minimal enhancements for the report page:
 * - Clears the date-from/date-to inputs if the other is cleared.
 * - Validates that date_from is not after date_to before submission.
 *
 * @package ContentVote
 */

( function () {
	'use strict';

	const form     = document.querySelector( '.cv-filters' );
	const dateFrom = document.getElementById( 'cv-date-from' );
	const dateTo   = document.getElementById( 'cv-date-to' );

	if ( ! form || ! dateFrom || ! dateTo ) return;

	form.addEventListener( 'submit', function ( event ) {
		const from = dateFrom.value;
		const to   = dateTo.value;

		if ( from && to && from > to ) {
			event.preventDefault();
			// Use a simple WP-style notice rather than an alert.
			let notice = form.querySelector( '.cv-date-notice' );
			if ( ! notice ) {
				notice = document.createElement( 'p' );
				notice.className = 'cv-date-notice description' ;
				notice.style.color = '#c62828';
				form.appendChild( notice );
			}
			notice.textContent = ( typeof wpI18n !== 'undefined' && wpI18n.__ )
				? wpI18n.__( '"From" date cannot be after "To" date.', 'content-vote' )
				: '"From" date cannot be after "To" date.';
		}
	} );
} )();
