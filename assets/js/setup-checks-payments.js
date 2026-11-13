/**
 * Moves the store setup notices into the rows of their payment methods on the WooCommerce payments settings page.
 *
 * The list is rendered by React and has no extension point, so the notices are printed hidden by PHP and moved
 * into place here. The script is printed inline, directly after the container with the notices, and only touches
 * that container. Every plugin that ships this package prints its own copy, so the copies cannot affect each other.
 *
 * It is wrapped in a function instead of jQuery( function ( $ ) {} ): document.currentScript is only set while the
 * script runs, not in a ready callback, and the function scope keeps the const from clashing with the other copies.
 */
( function ( script ) {
	'use strict';

	const krokedil_setup_checks_payments = {
		/**
		 * The element with the notices, printed directly before this script.
		 */
		container: null,

		/**
		 * The element WooCommerce renders the list of payment methods into.
		 */
		list: null,

		/**
		 * The notices to move.
		 */
		notices: [],

		/**
		 * How long to wait for the rows, in milliseconds, before the notices are shown where they were printed.
		 */
		fallbackDelay: 5000,

		/**
		 * Initializes the script.
		 */
		init: function () {
			if ( ! krokedil_setup_checks_payments.loadNotices() ) {
				return;
			}

			krokedil_setup_checks_payments.bindEvents();
			krokedil_setup_checks_payments.placeNotices();
			krokedil_setup_checks_payments.observeList();

			window.setTimeout( krokedil_setup_checks_payments.showUnplacedNotices, krokedil_setup_checks_payments.fallbackDelay );
		},

		/**
		 * Finds the notices that were printed before this script.
		 *
		 * @return {boolean} Whether there are any notices to move.
		 */
		loadNotices: function () {
			const container = script ? script.previousElementSibling : null;

			if ( ! container || ! container.classList.contains( 'krokedil-setup-checks-row-notices' ) ) {
				return false;
			}

			krokedil_setup_checks_payments.container = container;
			krokedil_setup_checks_payments.list = document.getElementById( 'experimental_wc_settings_payments_main' ) || document.body;
			krokedil_setup_checks_payments.notices = Array.from( container.querySelectorAll( '.krokedil-setup-checks-row-notice' ) );

			return krokedil_setup_checks_payments.notices.length > 0;
		},

		/**
		 * Binds the events of the notices.
		 */
		bindEvents: function () {
			krokedil_setup_checks_payments.notices.forEach( function ( notice ) {
				// Some rows are clickable as a whole. A click on a link in the notice must not also open the row.
				notice.addEventListener( 'click', function ( event ) {
					event.stopPropagation();
				} );
			} );
		},

		/**
		 * Finds the element in the row of a payment method that the notice goes into.
		 *
		 * @param {string} gatewayId The id of the payment method.
		 * @return {Element|null} Null while the row is not rendered.
		 */
		findTarget: function ( gatewayId ) {
			const rows = Array.from( krokedil_setup_checks_payments.list.querySelectorAll( '.woocommerce-list__item' ) );
			const row = rows.find( function ( candidate ) {
				return candidate.id === gatewayId;
			} );

			return row ? row.querySelector( '.woocommerce-list__item-text' ) : null;
		},

		/**
		 * Moves the notices into the rows of their payment methods.
		 */
		placeNotices: function () {
			krokedil_setup_checks_payments.notices.forEach( function ( notice ) {
				const target = krokedil_setup_checks_payments.findTarget( notice.getAttribute( 'data-gateway-id' ) );

				if ( ! target ) {
					return;
				}

				if ( notice.parentNode !== target ) {
					target.appendChild( notice );
				}

				notice.hidden = false;
			} );
		},

		/**
		 * Places the notices again whenever the list changes. React can render a row again as a new element,
		 * which drops the notice that was moved into it.
		 */
		observeList: function () {
			const observer = new MutationObserver( krokedil_setup_checks_payments.placeNotices );

			observer.observe( krokedil_setup_checks_payments.list, { childList: true, subtree: true } );
		},

		/**
		 * Shows the notices that could not be moved where they were printed, above the list. Happens when a row
		 * cannot be found, e.g. after WooCommerce changes its markup.
		 */
		showUnplacedNotices: function () {
			krokedil_setup_checks_payments.notices.forEach( function ( notice ) {
				notice.hidden = false;
			} );
		},
	};

	krokedil_setup_checks_payments.init();
} )( document.currentScript );
