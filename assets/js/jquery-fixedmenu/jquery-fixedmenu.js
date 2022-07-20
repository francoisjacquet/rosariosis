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
 * @since 3.4.2 Handle RTL languages (menu on the right).
 * @since 4.4 Load once on page load & always check height on resize & scroll.
 * @since 8.7 Unfix menu on resize if is mobile menu.
 */

function fixedMenu() {

	var menu = $('#menu'),
		$window = $(window),
		body = $('#body'),
		// Handle RTL languages (menu on the right).
		leftOrRight = ($('html').attr('dir') === 'RTL' ? 'right' : 'left'),
		menuIsFixed = false;

	var init = function() {
		// It has not... perform the initialization
		fixedMenu.init = typeof fixedMenu.init == 'undefined';

		if (fixedMenu.init) {

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
			$window.resize(fixedMenu);

			/**
			 * Case 1: body height > window height && menu height < body height
			 * Add fix logic on scroll
			 */
			$window.scroll(fixMenuLogic);
		}

		// Onload, eventually fix if not on top of page.
		fixMenuLogic();
	};



	/**
	 * Fix logic
	 * a) Y from top + window height > menu height
	 * Fix menu (add CSS)
	 * b) Y from top + window height <= menu height
	 * Remove CSS
	 */
	var fixMenuLogic = function() {

		var windowHeight = $window.height(),
			bodyHeight = body.outerHeight();

		if (! menu.is(':visible') ||
			Math.round(menu.width()) === window.innerWidth || // isMobileMenu(), #menu width is 100% viewport width.
			bodyHeight <= windowHeight ||
			menu.height() >= bodyHeight ||
			($window.scrollTop() + windowHeight <= menu.outerHeight())) {
			return unfixMenu();
		}

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
			'bottom': (bottom < 0 ? 0 : bottom) + 'px'
		};

		css[leftOrRight] = '0px';

		menu.css(css).addClass('fixedmenu-fixed').next().show();

		menuIsFixed = true;
	};



	/**
	 * Unfix Menu
	 *
	 * Remove fixed CSS.
	 * Remove .fixedmenu-fixed CSS class from menu
	 * Hide ghost div.
	 */
	var unfixMenu = function() {
		if (!menuIsFixed) {
			return;
		}

		var css = {
			'position': '',
			'bottom': ''
		};

		css[leftOrRight] = '';

		menu.css(css).removeClass('fixedmenu-fixed').next().hide();

		menuIsFixed = false;
	};

	init();
}
