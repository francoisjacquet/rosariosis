/**
 * jQuery Pin Menu plugin
 *
 * @see Wordpress `wp-admin/js/common.js` file
 *
 * @copyright 2015 Wordpress (http://wordpress.org/)
 * @copyright 2016 François Jacquet
 *
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */
$( document ).ready( function() {

	var isIOS = /iPhone|iPad|iPod/.test( navigator.userAgent ),
		lastScrollPosition = 0,
		pinnedMenuTop = false,
		pinnedMenuBottom = false,
		menuTop = 0,
		menuIsPinned = false,
		height = {},
		$menu = $( '#menu' );

	function pinMenu( event ) {
		var windowPos = $( window ).scrollTop(),
			resizing = ! event || event.type !== 'scroll';

		if ( isIOS ||
			(window.attachEvent && !window.addEventListener) ) { // IE8-
			return;
		}

		if ( height.menu < height.window ) {
			unpinMenu();
			return;
		}

		menuIsPinned = true;

		if ( height.menu > height.window ) {
			// Check for overscrolling
			if ( windowPos < 0 ) {
				if ( ! pinnedMenuTop ) {
					pinnedMenuTop = true;
					pinnedMenuBottom = false;

					$menu.css({
						position: 'fixed',
						top: '',
						bottom: ''
					});
				}

				return;
			} else if ( windowPos + height.window > $( document ).height() - 1 ) {
				if ( ! pinnedMenuBottom ) {
					pinnedMenuBottom = true;
					pinnedMenuTop = false;

					$menu.css({
						position: 'fixed',
						top: '',
						bottom: 0
					});
				}

				return;
			}

			if ( windowPos > lastScrollPosition ) {
				// Scrolling down
				if ( pinnedMenuTop ) {
					// let it scroll
					pinnedMenuTop = false;
					menuTop = $menu.offset().top - ( windowPos - lastScrollPosition );

					if ( menuTop + height.menu + height.footer < windowPos + height.window ) {
						menuTop = windowPos + height.window - height.menu;
					}

					$menu.css({
						position: 'absolute',
						top: menuTop,
						bottom: ''
					});
				} else if ( ! pinnedMenuBottom && $menu.offset().top + height.menu < windowPos + height.window ) {
					// pin the bottom
					pinnedMenuBottom = true;

					$menu.css({
						position: 'fixed',
						top: '',
						bottom: 0
					});
				}
			} else if ( windowPos < lastScrollPosition ) {
				// Scrolling up
				if ( pinnedMenuBottom ) {
					// let it scroll
					pinnedMenuBottom = false;
					menuTop = $menu.offset().top + ( lastScrollPosition - windowPos );

					if ( menuTop + height.menu > windowPos + height.window ) {
						menuTop = windowPos;
					}

					$menu.css({
						position: 'absolute',
						top: menuTop,
						bottom: ''
					});
				} else if ( ! pinnedMenuTop && $menu.offset().top >= windowPos - height.footer ) {
					// pin the top
					pinnedMenuTop = true;

					$menu.css({
						position: 'fixed',
						top: '',
						bottom: ''
					});
				}
			} else if ( resizing ) {
				// Resizing
				pinnedMenuTop = pinnedMenuBottom = false;
				menuTop = windowPos + height.window - height.menu + height.footer*2;

				if ( menuTop > 0 ) {
					$menu.css({
						position: 'absolute',
						top: menuTop,
						bottom: ''
					});
				} else {
					unpinMenu();
				}
			}
		}

		lastScrollPosition = windowPos;
	}

	function resetHeights() {
		height = {
			window: $( window ).height(),
			footer: $( '#footer' ).height(),
			menu: $menu.height()
		};
	}


	function unpinMenu() {
		if ( isIOS || ! menuIsPinned ) {
			return;
		}

		pinnedMenuTop = pinnedMenuBottom = menuIsPinned = false;
		$menu.css({
			position: '',
			top: '',
			bottom: ''
		});
	}

	function setPinMenu() {
		resetHeights();

		if ( screen.width <= 640 ) { // TODO: if theme does not want to pin menu? or if different media query...
			$( document.body ).removeClass( 'sticky-menu' );
			unpinMenu();
		} else if ( height.menu > height.window &&
			height.menu < $( '#body' ).height() ) {
			pinMenu();
			$( document.body ).removeClass( 'sticky-menu' );
		} else {
			$( document.body ).addClass( 'sticky-menu' );
			unpinMenu();
		}
	}

	if ( ! isIOS &&
		( screen.width > 640 ||
		screen.height > 640 ) ) { // Not on iOS or mobiles.
		$( window ).on( "resize scroll", setPinMenu );
	}

	setPinMenu();

});