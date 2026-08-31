/**
 * Functions (global, always loaded) JS
 *
 * @since 12.5
 *
 * @package RosarioSIS
 */

csp.functions.onEvents = function() {
	$('#body,#colorbox').on(
		(isTouchDevice() ? 'click keypress' : 'focus'),
		'.onclick',
		csp.functions.inputDivOnclick
	);

	/**
	 * Prevent focusing input when opening tooltip on mobile devices
	 *
	 * @since 12.8
	 */
	if (isTouchDevice()) {
		$('#body,#colorbox').on('click', '.tooltip', function(){
			event.preventDefault();
		});
	}

	csp.functions.listOutput.prepare();

	/**
	 * ajaxPrepare #body (or #colorbox) after AJAX call
	 * Use when delegated events are not possible
	 * or not recommended (mouseover fires too frequently)
	 *
	 * @see ajaxPrepare()
	 */
	$('#body,#colorbox').on('ajaxPrepare', csp.functions.listOutput.prepare);
}

$(csp.functions.onEvents);

/**
 * Add input HTML when clicking on <div onclick> (input value & title)
 * Listen to focus event: can be triggered by the Tab key (keyboard navigation)
 *
 * @see InputDivOnclick()
 *
 * @uses addHTML() function
 */
csp.functions.inputDivOnclick = function() {
	var divOnclickId = this.parentNode.id;

	if (! divOnclickId) {
		return;
	}

	var inputId = divOnclickId.substring(3),
		inputHtml = document.getElementById('html' + inputId);

	if (! inputHtml) {
		return;
	}

	/**
	 * Prevent focusing input when opening tooltip on mobile devices
	 *
	 * @since 12.8
	 */
	if (isTouchDevice()
		&& event.target.className === 'tooltip') {
		return;
	}

	addHTML(inputHtml.innerHTML, divOnclickId, true);

	if (typeof inputHtml.remove === 'function') { // Fix for Internet Explorer
		inputHtml.remove();
	}

	$('#' + inputId).focus();
	$('#' + divOnclickId).click();
}

/**
 * ListOutput() function JS
 */
csp.functions.listOutput = {
	verticalTabNavigation: function() {
		/**
		 * Navigate table inputs vertically using tab key.
		 *
		 * @link https://stackoverflow.com/questions/38575817/set-tabindex-in-vertical-order-of-columns
		 */
		var tabindex = 1;

		$('tbody', this).each(function(i, tbl) {
			$(tbl).find('tr').first().find('td').each(function(clmn, el) {
				$(tbl).find('tr td:nth-child(' + (clmn + 1) + ') :input').each(function(j, input) {
					$(input).attr('tabindex', tabindex++);
				});
			});
		});
	},
	prepare: function() {
		$('#' + this.id + ' .list.vertical-tab-navigation').each(csp.functions.listOutput.verticalTabNavigation);
	}
}
