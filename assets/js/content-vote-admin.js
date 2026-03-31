/**
 * Content Vote — Admin Script
 *
 * - Expand/collapse section rows per page row (accordion).
 * - "Expand All" / "Collapse All" global buttons.
 *
 * @package ContentVote
 */

( function () {
	'use strict';

	/**
	 * Expands a page group: shows its section rows and updates ARIA/icon state.
	 *
	 * @param {HTMLElement} pageRow  The .cv-row--page element.
	 */
	function expandGroup( pageRow ) {
		const groupId = pageRow.dataset.group;
		if ( ! groupId ) return;

		pageRow.setAttribute( 'aria-expanded', 'true' );

		const toggleBtn = pageRow.querySelector( '.cv-toggle-btn' );
		if ( toggleBtn ) {
			toggleBtn.setAttribute( 'aria-expanded', 'true' );
		}

		document.querySelectorAll( `tr[data-parent="${ groupId }"]` ).forEach( ( row ) => {
			row.removeAttribute( 'hidden' );
		} );
	}

	/**
	 * Collapses a page group: hides its section rows and resets ARIA/icon state.
	 *
	 * @param {HTMLElement} pageRow  The .cv-row--page element.
	 */
	function collapseGroup( pageRow ) {
		const groupId = pageRow.dataset.group;
		if ( ! groupId ) return;

		pageRow.setAttribute( 'aria-expanded', 'false' );

		const toggleBtn = pageRow.querySelector( '.cv-toggle-btn' );
		if ( toggleBtn ) {
			toggleBtn.setAttribute( 'aria-expanded', 'false' );
		}

		document.querySelectorAll( `tr[data-parent="${ groupId }"]` ).forEach( ( row ) => {
			row.setAttribute( 'hidden', '' );
		} );
	}

	/**
	 * Toggles the expanded state of a page group.
	 *
	 * @param {HTMLElement} pageRow
	 */
	function toggleGroup( pageRow ) {
		const isExpanded = pageRow.getAttribute( 'aria-expanded' ) === 'true';
		isExpanded ? collapseGroup( pageRow ) : expandGroup( pageRow );
	}

	// ---- Per-row toggle (click on expandable row or its toggle button) ----
	document.querySelectorAll( '.cv-row--expandable' ).forEach( ( pageRow ) => {
		// Clicking anywhere on the page row toggles it.
		pageRow.addEventListener( 'click', function ( event ) {
			// Prevent toggling when clicking the external link inside the cell.
			if ( event.target.closest( 'a' ) ) return;
			toggleGroup( pageRow );
		} );
	} );

	// ---- Expand All / Collapse All ----
	document.querySelectorAll( '.cv-expand-all' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', function () {
			const action = btn.dataset.action;
			document.querySelectorAll( '.cv-row--expandable' ).forEach( ( pageRow ) => {
				action === 'expand' ? expandGroup( pageRow ) : collapseGroup( pageRow );
			} );
		} );
	} );

} )();
