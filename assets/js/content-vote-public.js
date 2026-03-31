/**
 * Content Vote — Public Script
 *
 * Handles vote submission via fetch() for all .cv-widget instances on the page.
 * - Event delegation from document root
 * - Optimistic UI: updates counts immediately, reverts on error
 * - Persists voted state to localStorage to survive page reloads
 * - No jQuery dependency
 *
 * Globals injected by wp_localize_script:
 *   window.ContentVote = { ajaxurl, nonce, i18n: { error, thanks } }
 *
 * @package ContentVote
 */

( function () {
	'use strict';

	// Guard — localised data must be present.
	if ( typeof window.ContentVote === 'undefined' ) {
		return;
	}

	const CV = window.ContentVote;

	/**
	 * localStorage key for a given section ID.
	 *
	 * @param {string} sectionId
	 * @returns {string}
	 */
	function storageKey( sectionId ) {
		return 'cv_vote_' + sectionId;
	}

	/**
	 * Reads the previously stored vote type for a section.
	 *
	 * @param {string} sectionId
	 * @returns {number|null} 1, -1, or null
	 */
	function getStoredVote( sectionId ) {
		try {
			const raw = localStorage.getItem( storageKey( sectionId ) );
			return raw !== null ? parseInt( raw, 10 ) : null;
		} catch {
			return null;
		}
	}

	/**
	 * Persists a vote type to localStorage.
	 *
	 * @param {string} sectionId
	 * @param {number} voteType  1 or -1
	 */
	function storeVote( sectionId, voteType ) {
		try {
			localStorage.setItem( storageKey( sectionId ), String( voteType ) );
		} catch {
			// localStorage unavailable (private browsing etc.) — silently ignore.
		}
	}

	/**
	 * Applies the "is-active" class to the correct button within a widget.
	 *
	 * @param {HTMLElement} widget   .cv-widget element
	 * @param {number|null} voteType 1, -1, or null (clear all)
	 */
	function setActiveButton( widget, voteType ) {
		const buttons = widget.querySelectorAll( '.cv-widget__btn' );
		buttons.forEach( function ( btn ) {
			const btnType = parseInt( btn.dataset.voteType, 10 );
			const isActive = voteType !== null && btnType === voteType;
			btn.classList.toggle( 'is-active', isActive );
			btn.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );
	}

	/**
	 * Updates the count display within a button.
	 *
	 * @param {HTMLElement} widget
	 * @param {number}      up
	 * @param {number}      down
	 */
	function updateCounts( widget, up, down ) {
		const upBtn   = widget.querySelector( '.cv-widget__btn--up' );
		const downBtn = widget.querySelector( '.cv-widget__btn--down' );

		if ( upBtn ) {
			const counter = upBtn.querySelector( '.cv-widget__count' );
			if ( counter ) counter.textContent = up;
		}
		if ( downBtn ) {
			const counter = downBtn.querySelector( '.cv-widget__count' );
			if ( counter ) counter.textContent = down;
		}
	}

	/**
	 * Shows a temporary feedback message inside the widget.
	 *
	 * @param {HTMLElement} widget
	 * @param {string}      message
	 * @param {boolean}     isError
	 */
	function showFeedback( widget, message, isError ) {
		const el = widget.querySelector( '.cv-widget__feedback' );
		if ( ! el ) return;

		el.textContent = message;
		el.classList.remove( 'cv-widget__feedback--success', 'cv-widget__feedback--error' );
		el.classList.add( isError ? 'cv-widget__feedback--error' : 'cv-widget__feedback--success' );

		// Auto-clear after 3 seconds.
		clearTimeout( el._cvTimeout );
		el._cvTimeout = setTimeout( function () {
			el.textContent = '';
			el.classList.remove( 'cv-widget__feedback--success', 'cv-widget__feedback--error' );
		}, 3000 );
	}

	/**
	 * Triggers the vote animation on a button, then removes the class.
	 *
	 * @param {HTMLElement} btn
	 */
	function triggerAnimation( btn ) {
		btn.classList.remove( 'cv-animating' );
		// Force reflow so re-adding the class restarts the animation.
		void btn.offsetWidth;
		btn.classList.add( 'cv-animating' );
		btn.addEventListener(
			'animationend',
			function onEnd() {
				btn.classList.remove( 'cv-animating' );
				btn.removeEventListener( 'animationend', onEnd );
			}
		);
	}

	/**
	 * Replaces a button's inner content with a loading spinner.
	 *
	 * @param {HTMLElement} btn
	 * @returns {string} Original innerHTML for restoration.
	 */
	function setLoading( btn ) {
		const original = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<span class="cv-spinner" aria-hidden="true"></span>';
		return original;
	}

	/**
	 * Restores a button's content after loading.
	 *
	 * @param {HTMLElement} btn
	 * @param {string}      originalHTML
	 */
	function clearLoading( btn, originalHTML ) {
		btn.innerHTML = originalHTML;
		btn.disabled = false;
	}

	/**
	 * Resolves the section ID for a widget, falling back to JS parent-search.
	 *
	 * The widget PHP renders data-cv-section-id from the user setting.
	 * If empty, we traverse up the DOM to find the nearest Elementor section
	 * element that has a non-auto-generated ID.
	 *
	 * @param {HTMLElement} widget
	 * @returns {string}
	 */
	function resolveSectionId( widget ) {
		const explicit = ( widget.dataset.cvSectionId || '' ).trim();
		if ( explicit ) return explicit;

		// Walk up the DOM looking for the nearest Elementor section/container with an id.
		let node = widget.parentElement;
		while ( node && node !== document.body ) {
			if (
				node.id &&
				! node.id.startsWith( 'elementor-' ) &&
				(
					node.classList.contains( 'elementor-section' ) ||
					node.classList.contains( 'elementor-container' ) ||
					node.classList.contains( 'e-container' ) ||
					node.classList.contains( 'e-con' )
				)
			) {
				return node.id;
			}
			node = node.parentElement;
		}

		return '';
	}

	/**
	 * Submits a vote via fetch.
	 *
	 * @param {HTMLElement} widget
	 * @param {HTMLElement} clickedBtn
	 * @param {string}      sectionId
	 * @param {number}      voteType
	 */
	function submitVote( widget, clickedBtn, sectionId, voteType ) {
		const pageUrl = widget.dataset.cvPageUrl || window.location.href;

		// Snapshot current counts for possible rollback.
		const upCountEl   = widget.querySelector( '.cv-widget__btn--up .cv-widget__count' );
		const downCountEl = widget.querySelector( '.cv-widget__btn--down .cv-widget__count' );
		const prevUp      = upCountEl   ? parseInt( upCountEl.textContent,   10 ) || 0 : 0;
		const prevDown    = downCountEl ? parseInt( downCountEl.textContent, 10 ) || 0 : 0;
		const prevVote    = getStoredVote( sectionId );

		// --- Optimistic UI update ---
		let newUp   = prevUp;
		let newDown = prevDown;

		if ( voteType === 0 ) {
			// Toggle off: subtract the previous vote.
			if ( prevVote ===  1 ) newUp   = Math.max( 0, prevUp   - 1 );
			if ( prevVote === -1 ) newDown = Math.max( 0, prevDown - 1 );
		} else {
			// New vote or change: add to target, subtract previous if switching.
			if ( voteType ===  1 ) { newUp   = prevUp   + 1; if ( prevVote === -1 ) newDown = Math.max( 0, prevDown - 1 ); }
			if ( voteType === -1 ) { newDown = prevDown + 1; if ( prevVote ===  1 ) newUp   = Math.max( 0, prevUp   - 1 ); }
		}

		updateCounts( widget, newUp, newDown );
		setActiveButton( widget, voteType );
		storeVote( sectionId, voteType );

		// --- Disable buttons during request ---
		const allButtons = widget.querySelectorAll( '.cv-widget__btn' );
		allButtons.forEach( function ( b ) { b.disabled = true; } );
		const originalHTML = setLoading( clickedBtn );

		// --- Build form data ---
		const body = new URLSearchParams( {
			action:     'content_vote_submit',
			nonce:      CV.nonce,
			section_id: sectionId,
			page_url:   pageUrl,
			vote_type:  voteType,
		} );

		fetch( CV.ajaxurl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString(),
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( json ) {
				clearLoading( clickedBtn, originalHTML );
				allButtons.forEach( function ( b ) { b.disabled = false; } );

				if ( json.success && json.data ) {
					// Server is the source of truth for final counts.
					updateCounts( widget, json.data.up, json.data.down );
					setActiveButton( widget, json.data.user_vote );
					storeVote( sectionId, json.data.user_vote );
					// Only animate when a vote was cast (not when removed).
					if ( json.data.user_vote !== 0 ) {
						triggerAnimation( clickedBtn );
					}
				} else {
					// Revert optimistic update.
					updateCounts( widget, prevUp, prevDown );
					setActiveButton( widget, prevVote );
					if ( prevVote !== null ) storeVote( sectionId, prevVote );
					const errMsg = ( json.data && json.data.message ) ? json.data.message : CV.i18n.error;
					showFeedback( widget, errMsg, true );
				}
			} )
			.catch( function () {
				clearLoading( clickedBtn, originalHTML );
				allButtons.forEach( function ( b ) { b.disabled = false; } );

				// Revert optimistic update on network error.
				updateCounts( widget, prevUp, prevDown );
				setActiveButton( widget, prevVote );
				if ( prevVote !== null ) storeVote( sectionId, prevVote );
				showFeedback( widget, CV.i18n.error, true );
			} );
	}

	/**
	 * Initialises a single widget: restores stored state, wires click handlers.
	 *
	 * @param {HTMLElement} widget
	 */
	function initWidget( widget ) {
		const sectionId = resolveSectionId( widget );
		if ( ! sectionId ) {
			// No section ID — widget cannot function.
			return;
		}

		// Restore voted state from localStorage.
		const storedVote = getStoredVote( sectionId );
		if ( storedVote !== null ) {
			setActiveButton( widget, storedVote );
		}
	}

	// =========================================================
	// Event delegation — single listener on document.
	// =========================================================
	document.addEventListener( 'click', function ( event ) {
		const btn = event.target.closest( '.cv-widget__btn' );
		if ( ! btn ) return;

		const widget = btn.closest( '.cv-widget' );
		if ( ! widget ) return;

		const rawVoteType = parseInt( btn.dataset.voteType, 10 );
		if ( rawVoteType !== 1 && rawVoteType !== -1 ) return;

		const sectionId = resolveSectionId( widget );
		if ( ! sectionId ) {
			showFeedback( widget, 'Section ID is not configured.', true );
			return;
		}

		const prevVoteType = getStoredVote( sectionId );
		// If clicking the same button that is already active → toggle off (send 0).
		const voteType = ( prevVoteType === rawVoteType ) ? 0 : rawVoteType;

		submitVote( widget, btn, sectionId, voteType );
	} );

	// =========================================================
	// Initialise all widgets already in the DOM.
	// =========================================================
	document.querySelectorAll( '.cv-widget' ).forEach( initWidget );

	// Support Elementor's dynamic rendering (editor or lazy-loaded widgets).
	if ( typeof window.elementorFrontend !== 'undefined' ) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/content_vote.default', function ( $scope ) {
			const widget = $scope[ 0 ] ? $scope[ 0 ].querySelector( '.cv-widget' ) : null;
			if ( widget ) initWidget( widget );
		} );
	}

} )();
