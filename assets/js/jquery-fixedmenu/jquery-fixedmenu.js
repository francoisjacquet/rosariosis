/**
 * FixedMenu jQuery plugin
 *
 * Fix menu to bottom when scrolling past menu's height.
 *
 * Inspired by & made to replace jQuery ScrollToFixed plugin (21Kb vs. 2Kb)
 *
 * @package RosarioSIS
 * @subpackage assets/js
 * @since 2.9.3
 *
 * @since 3.4.2 Handle RTL languages (menu on the right).
 */

function fixedMenu() {

	var menu = $( '#menu' ),
	$window = $( window ),
	body = $( '#body' ),
	// Handle RTL languages (menu on the right).
	leftOrRight = ( $( 'html' ).attr('dir') === 'RTL' ? 'right' : 'left' );

	var init = function() {
		// It has not... perform the initialization
		fixedMenu.init = typeof fixedMenu.init == 'undefined';

		if ( fixedMenu.init ) {

			/**
			 * Add ghost div after menu
			 * to compensate menu width and keep content right.
			 */
			menu.after(
				'<div style="display: none; width: ' + menu.outerWidth() +
					'px; height: ' + menu.height() + 'px; float: ' +
					leftOrRight + ';"></div>'
			);

			/**
			 * Case 3: Add fixedMenu check on resize
			 */
			$window.resize( fixedMenu );
		}

		/**
		 * Case 1: body height > window height && menu height < body height
		 * Add fix logic on scroll
		 */
		if ( body.height() > $window.height() &&
			menu.height() < body.height() ) {

			if ( fixedMenu.init ) {

				// Onload, eventually fix if not on top of page.
				fixMenuLogic();
			}

			$window.scroll( fixMenuLogic );
		}
		/**
		 * Case 2: body height <= window height || menu height >= body height
		 * Remove CSS; remove on scroll
		 */
		else {
			unfixMenu();

			$window.unbind( 'scroll', fixMenuLogic );
		}

	};



	/**
	 * Fix logic
	 * a) Y from top + window height > menu height
	 * Fix menu (add CSS)
	 * b) Y from top + window height <= menu height
	 * Remove CSS
	 */
	var fixMenuLogic = function() {
		var windowHeight = $window.height();

		if ( $window.scrollTop() + windowHeight > menu.outerHeight() ) {

			/**
			 * Fix Menu
			 *
			 * Adjust bottom if Menu height < window height.
			 *
			 * Add fixed CSS.
			 * Add .fixedmenu-fixed CSS class to menu
			 * Show ghost div.
			 */
			var bottom = windowHeight - menu.outerHeight();

			var css = {
				'position': 'fixed',
				'bottom': ( bottom < 0 ? 0 : bottom ) + 'px'
			};

			css[ leftOrRight ] = '0px';

			menu.css( css ).addClass( 'fixedmenu-fixed' ).next().show();

		} else {

			unfixMenu();
		}
	};



	/**
	 * Unfix Menu
	 *
	 * Remove fixed CSS.
	 * Remove .fixedmenu-fixed CSS class from menu
	 * Hide ghost div.
	 */
	var unfixMenu = function() {
			var css = {
				'position': '',
				'bottom': ''
			};

			css[ leftOrRight ] = '';

			menu.css( css ).removeClass( 'fixedmenu-fixed' ).next().hide();
	};

	init();
}
