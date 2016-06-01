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
 */

function fixedMenu() {

	// It has not... perform the initialization
	fixedMenu.init = typeof fixedMenu.init == 'undefined';

	if ( fixedMenu.init ) {

		/**
		 * Add ghost div after menu
		 * to compensate menu width and keep content right.
		 */
		$( '#menu' ).after(
			'<div style="display: none; width: ' + $( '#menu' ).width() +
				'px; height: ' + $( '#menu' ).height() +
				'px; float: left;"></div>'
		);

		/**
		 * Case 3: Add fixedMenu check on resize
		 */
		$( window ).resize( fixedMenu );
	}

	/**
	 * Case 1: body height > window height && menu height < body height
	 * Add fix logic on scroll
	 */
	if ( $( '#body' ).height() > $( window ).height() &&
		$( '#menu' ).height() < $( '#body' ).height() ) {

		if ( fixedMenu.init ) {

			// Onload, eventually fix if not on top of page.
			fixMenuLogic();
		}

		$( window ).scroll( fixMenuLogic );
	}
	/**
	 * Case 2: body height <= window height || menu height >= body height
	 * Remove CSS; remove on scroll
	 */
	else {
		unfixMenu();

		$( window ).unbind( 'scroll', fixMenuLogic );
	}

}



/**
 * Fix logic
 * a) Y from top + window height > menu height
 * Fix menu (add CSS)
 * b) Y from top + window height <= menu height
 * Remove CSS
 */
function fixMenuLogic() {
	if ( $( window ).scrollTop() + $( window ).height() > $( '#menu' ).height() ) {

		fixMenu();
	} else {

		unfixMenu();
	}
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
function fixMenu() {
	var bottom = 0;

	if ( $( '#menu' ).height() < $( window ).height() ) {

		bottom = $( window ).height() - $( '#menu' ).height();
	}

	$( '#menu' ).css({
		'position': 'fixed',
		'bottom': bottom + 'px',
		'left': '0px'
	}).addClass( 'fixedmenu-fixed' ).next().show();
}



/**
 * Unfix Menu
 *
 * Remove fixed CSS.
 * Remove .fixedmenu-fixed CSS class from menu
 * Hide ghost div.
 */
function unfixMenu() {
	$( '#menu' ).css({
		'position': '',
		'bottom': '',
		'left': ''
	}).removeClass( 'fixedmenu-fixed' ).next().hide();
}
