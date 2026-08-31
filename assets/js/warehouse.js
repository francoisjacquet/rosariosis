// Modules.php JS.
/**
 * Add/replace HTML given the markup and the target ID.
 *
 * @param {string}  html
 * @param {string}  id
 * @param {boolean} replace Replace or add the HTML (optional).
 */
var addHTML = function(html, id, replace) {
	// Get element in pure Javascript
	// jQuery does not handle IDs with brackets [], check _makeMultipleInput().
	var el = document.getElementById(id);

	if (replace) {
		el.innerHTML = '';
	}

	// Append HTML
	if (html.indexOf('<script') != -1) {
		// Here we use setInnerHTML() (not jQuery) so Javascript gets loaded
		setInnerHTML(el, el.innerHTML + html);
	} else {
		// Here we use insertAdjacentHTML() so select input selected element is retained
		el.insertAdjacentHTML('beforeend', html);
	}
}

// @since 12.0 Wrapper for addHTML() used by InputDivOnclick()
// @deprecated since 12.5 Use csp.functions.inputDivOnclick() instead
var inputAddHTML = function(divId) {
	addHTML(iHtml[divId], 'div' + divId, true);

	$('#' + divId).focus();
	$('#div' + divId).click();
};

/**
 * Check all checkboxes given the form,
 * the value/state and the checkboxes name (beginning with).
 *
 * @param  {[type]} form      Form element.
 * @param  {string} value     Checked value.
 * @param  {string} name_like Checkbox name begins with.
 */
var checkAll = function(form, value, name_like) {
	for (var i = 0, max = form.elements.length; i < max; i++) {
		var chk = form.elements[i];

		if (chk.type == 'checkbox' &&
			chk.name.substr(0, name_like.length) == name_like) {

			chk.checked = value;
		}
	}
}

/**
 * Switch menu,
 * used for the Advanced search widgets.
 * Toggles the next adjacent table element visibility.
 *
 * @deprecated since 12.5 CSP remove unsafe-inline Javascript: use `<details><summary>` instead
 *
 * @param  {DOMelement} el The element, this.
 */
var switchMenu = function(el) {
	$(el).toggleClass('switched').nextAll('table').first().toggle();
}

/**
 * Popups
 *
 * @deprecated since 12.0 Use colorBox instead of popup window
 */
var popups = new popups();

function popups() {
	this.childs = [];

	this.open = function(url, params) {
		if (!params)
			params = 'scrollbars=yes,resizable=yes,width=1200,height=450';

		this.childs.push(window.open(url, '', params));
	};

	this.closeAll = function() {
		for (var i = 0, max = this.childs.length; i < max; i++) {
			var child = this.childs[i];
			if (!child.closed)
				child.close();
		}
	};
}

function isTouchDevice() {
	try {
		document.createEvent("TouchEvent");
		return true;
	} catch (e) {
		return false;
	}
}

function isMobileMenu() {
	// #menu width is 100% viewport width.
	return Math.ceil($('#menu').width()) === window.innerWidth
		|| Math.floor($('#menu').width()) === window.innerWidth;
}

if (!isTouchDevice()) {
	// Add .no-touch CSS class.
	document.documentElement.className += " no-touch";
}

/**
 * Detect user browser
 *
 * @since 11.8
 *
 * @link https://stackoverflow.com/questions/9847580/how-to-detect-safari-chrome-ie-firefox-and-opera-browsers
 *
 * @return {string} User browser or 'unknown'
 */
navigator.browser = (function() {
	var ua = navigator.userAgent;

	if ((ua.indexOf("Opera") || ua.indexOf('OPR')) != -1) {
		return 'opera';
	}
	if (ua.indexOf("Edg") != -1) {
		return 'edge';
	}
	if (ua.indexOf("Chrome") != -1) {
		return 'chrome';
	}
	if (ua.indexOf("Safari") != -1) {
		return 'safari';
	}
	if (ua.indexOf("Firefox") != -1) {
		return 'firefox';
	}
	if ((ua.indexOf("MSIE") != -1) || (!!document.documentMode == true)) {
		return 'ie';
	}
	return 'unknown';
})();

// @since 11.8 Add .browser-[name] CSS class to html
document.documentElement.className += ' browser-' + navigator.browser;


var ColorBox = function() {
	$('.rt2colorBox').before(function(i) {
		if (this.id) {
			var $el = $(this),
				childrenHeight = 0;

			$el.children().each(function() {
				childrenHeight += $(this).height();
			});

			// only if content > 1 line & text <= 36 chars.
			if ($el.has('.onclick').length || $el.text().length > 36 || childrenHeight > $el.parent().height()) {
				return '<div class="link2colorBox"><a class="colorboxinline" href="#' + this.id + '"></a></div>';
			}
		}
	});

	$('.colorbox').closest('a').attr('target', '_top').colorbox({
		onComplete: function() {
			// Force processing any MarkDownToHTML(), JS calendar & list inside colorBox
			ajaxPrepare('#cboxLoadedContent', false);

			$.colorbox.resize();
		},
		title: '',
		maxWidth: '98%',
		maxHeight: '85%',
		minWidth: 306,
		minHeight: 153,
		scrolling: true
	});

	$('.colorboxiframe').colorbox({
		iframe: true,
		innerWidth: (screen.width < 768 ? 320 : 640),
		innerHeight: (screen.width < 768 ? 195 : 390)
	});

	$('.colorboxinline').colorbox({
		onComplete: function() {
			// @link https://stackoverflow.com/questions/280049/how-can-i-run-a-javascript-callback-when-an-image-is-loaded
			$('#cboxLoadedContent img').on('load', function() {
				$.colorbox.resize();
			});
		},
		inline: true,
		maxWidth: '98%',
		maxHeight: '85%',
		minWidth: 306,
		minHeight: 153,
		scrolling: true
	});
}

// Convert MarkDown to HTML & sanitize.
var MDConverter = function(markDown) {
	// @since 6.0 JS MarkDown use marked instead of showdown (15KB smaller).
	// Set options.
	// @link https://marked.js.org/#/USING_ADVANCED.md
	marked.setOptions({
		breaks: true, // Add <br> on a single line break. Requires gfm be true.
		gfm: true, // GitHub Flavored Markdown (GFM).
		headerIds: false, // Include an id attribute when emitting headings (h1, h2, h3, etc).
	});

	var md = marked.parse(markDown);

	// Open links in new window.
	// @link https://github.com/cure53/DOMPurify/issues/317
	DOMPurify.addHook('afterSanitizeAttributes', function (node) {
		// set all elements owning target to target=_blank
		if ('target' in node) {
			node.setAttribute('target', '_blank');
			node.setAttribute('rel', 'noopener');
		}

		// Lazy loading for images inside inline colorBox
		if ('loading' in node) {
			node.setAttribute('loading', 'lazy');
		}
	});

	return DOMPurify.sanitize(md);
};

// @deprecated since 12.1 use MDConverter()
var GetMDConverter = MDConverter;

var MarkDownInputPreview = function(input_id) {
	var input = $('#' + input_id),
		md = input.val(),
		md_prev = $('#divMDPreview' + input_id);

	if (!md_prev.is(":visible")) {
		// Convert MarkDown to HTML.
		md_prev.html(MDConverter(md));

		// MD preview = Input size.
		md_prev.css('height', input.css('height'));
		//md_prev.parent('.md-preview').css({'max-width': input.css('width')});
	}

	// Toggle MD preview & Input.
	md_prev.toggle();
	input.toggle();
	input.next('br').toggle();

	// Disable Write / Preview tab.
	md_prev.siblings('.tab').toggleClass('disabled');
}

/**
 * MarkDown to HTML.
 *
 * @uses marked JS library for conversion
 *
 * @since 12.0 Add target param
 *
 * @param {string} target CSS ID or class selector. Example: #body
 */
var MarkDownToHTML = function(target) {
	var target = (typeof target !== 'undefined') ? target + ' ' : '';

	$(target + '.markdown-to-html').html(function(i, txt) {
		// Fix MD blockquotes, decode `>` + fix double encoding `&`
		var md = MDConverter( txt.replace(/&gt;/g, '>').replace(/&amp;/g, '&') );

		// Add paragraph to text.
		var txtP = '<p>' + txt + '</p>';

		if ( txtP == md.trim() ) {
			// No MarkDown in text, return raw text.
			return txt;
		}

		return md;
	});
}

/**
 * JSCalendar.
 *
 * @since 12.0 Add target param
 *
 * @param {string} target CSS ID or class selector. Example: #body
 */
var JSCalendarSetup = function(target) {

	var target = (typeof target !== 'undefined') ? target + ' ' : '';

	$(target + '.button.cal').each(function(i, el) {
		var j = el.id.replace('trigger', '');

		Calendar.setup({
			monthField: $(target + "#monthSelect" + j)[0],
			dayField: $(target + "#daySelect" + j)[0],
			yearField: $(target + "#yearSelect" + j)[0],
			ifFormat: "%d-%b-%y",
			button: el,
			align: "Tl",
			singleClick: true,
			cache: true
		});
	});
}

/**
 * AJAX request options
 *
 * @since 12.0 Use FormData instead of jQuery Form Plugin
 * @link https://stackoverflow.com/questions/21044798/how-to-use-formdata-for-ajax-file-upload#answer-21045034
 *
 * @param  {string} target Target where to output request result: usually '#body'.
 * @param  {string} url    URL (form action).
 * @param  {mixed}  form   Form object or false.
 *
 * @return {object}        AJAX options for jQuery.ajax()
 */
var ajaxOptions = function(target, url, form) {
	var options = {
		beforeSend: function(data) {
			// AJAX error hide.
			$('.ajax-error').hide();

			$('.loading.BottomButton').css('visibility', 'visible');

			$('input[type="file"]').each(function(){
				if (this.files.length) {
					// Only show loading spinner if file input has selected files.
					$(this).next('.loading').css('visibility', 'visible');
				}
			});
		},
		success: function(data, s, xhr) {
			// See PHP RedirectURL().
			var redirectUrl = xhr.getResponseHeader("X-Redirect-Url");
			if (redirectUrl) {
				url = redirectUrl;
			} else if (form && form.method == 'get') {
				var getStr = $(form).serialize();

				/**
				 * Remove empty GET params from URL
				 *
				 * @link https://stackoverflow.com/questions/62989310/how-to-remove-empty-query-params-using-urlsearchparams
				 */
				getStr = getStr.replace(/(?:\&|^)[^\&]*?\=(?=\&|$)/g, '');

				url += (url.indexOf('?') != -1 ? '&' : '?') + getStr;
			}

			ajaxSuccess(data, target, url);
		},
		error: function(xhr, status, error) {
			ajaxError(xhr, status, error, url, target, form);
		},
		complete: function() {
			$('.loading').css('visibility', 'hidden');

			hideHelp();
		}
	};

	if (form && form.method == 'post') {
		/**
		 * Exclude file input with no files selected: disable before creating FormData
		 * Do not set PHP `$_FILES[ $input ]` when no files are uploaded
		 *
		 * @since 12.0
		 *
		 * @link https://stackoverflow.com/questions/57468389/create-formdata-excluding-not-provided-input-file
		 */
		$(form).find('input[type="file"]').prop('disabled', function(){
			return !this.files.length;
		});
		options.data = new FormData(form);
		// Re-enable after creating FormData
		$(form).find('input[type="file"]:disabled').prop('disabled', function(){
			return this.files.length;
		});
		options.type = 'post';
		options.contentType = false; // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
		options.processData = false; // NEEDED, DON'T OMIT THIS
	} else if (form && form.method) {
		options.data = $(form).serialize();
		options.type = form.method;
	}

	return options;
}

var ajaxError = function(xhr, status, error, url, target, form) {
	var code = xhr.status,
		errorMsg = 'AJAX error. ' + code + ' ';

	if (typeof ajaxError.num === 'undefined') {
		ajaxError.num = 0;
	}

	ajaxError.num++;

	if (code === 0) {
		errorMsg += 'Check your Network';

		if (url && ajaxError.num === 1) {
			window.setTimeout(function() {
				// Retry once on AJAX error 0, maybe a micro Wifi interruption.
				$.ajax(url, ajaxOptions(target, url, form));
			}, 1000);
			return;
		}
	} else if (status == 'parsererror') {
		errorMsg += 'JSON parse failed';
	} else if (status == 'timeout') {
		errorMsg += 'Request Timeout';
	} else if (status == 'abort') {
		errorMsg += 'Request Aborted';
	} else {
		errorMsg += error;
	}

	errorMsg += '. ' + url;

	ajaxError.num = 0;

	// AJAX error popup.
	$('.ajax-error').html(errorMsg).fadeIn();
}

var ajaxLink = function(link) {
	// Will work only if in the onclick there is no error!

	var href, target;

	if (typeof link == 'string') {
		href = link;
		target = 'body';
		if (href == 'Side.php') target = 'menu';
		else if (href == 'Side.php?sidefunc=update') target = 'menu-top';
		else if (href.indexOf('Bottom.php') === 0) target = 'footer';
	} else {
		href = link.href;
		target = link.target;
	}

	if (href.indexOf('#') != -1 || target.indexOf('_') == 0) // Internal/external/top anchor.
		return true;

	if (!target) {
		if (href.indexOf('Modules.php') != -1) target = 'body';
		else return true;
	}

	$.ajax(href, ajaxOptions(target, href, false));
	return false;
}

/**
 * AJAX update #body with URL GET params removed or replaced
 *
 * @see Side.php for example
 *
 * @since 12.0
 *
 * @uses ajaxLink()
 * @uses getURLParam()
 *
 * @param  {object} params Params to remove or replace in URL.
 * @return {bool}          ajaLink() with updated URL.
 */
var ajaxUpdateBody = function(params) {
	var link = document.URL;

	for(var key in params) {
		// TODO use url.searchParams.set() in version 13.0 but do not remove getURLParam(), used elsewhere
		var paramOld = '&' + key + '=' + getURLParam(link, key),
			replace = params[key] ? '&' + key + '=' + params[key] : '';

		// Remove from URL if empty value or replace value in URL.
		link = link.replace(paramOld, replace);
	}

	// Fix update body on mobile: menu button adds #! to URL, remove hash
	link = link.split('#')[0];

	return ajaxLink(link);
};

// @link https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getURLParam(url, name) {
	name = name.replace(/[\[\]]/g, '\\$&');
	var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
		results = regex.exec(url);
	if (!results || !results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

/**
 * AJAX Post Form
 * Note: form method can be get or post
 *
 * @since 12.0 Use FormData instead of jQuery Form Plugin
 * Breaking change: FormData will not send the submit input name, please use modfunc instead
 *
 * @since 12.5 Prevent submitting form if no checkboxes are checked
 * @see PHP MakeChooseCheckbox() function
 *
 * @deprecated submit param since 12.0, still set it to `true` if your are developing an add-on.
 *
 * @see ajaxPrepare below
 * @example `<select onchange="ajaxPostForm(this.form);">`
 *
 * @param  {object}  form   Form.
 * @param  {boolean} event  Submit event. Optional param.
 * @return {boolean}        True if Print PDF, or if target=_top. Else false.
 */
var ajaxPostForm = function(form, event) {
	var target = form.target || 'body',
		event = (typeof event !== 'undefined') ? event : false,
		error = false;

	$('.onclick-checkall[data-error]', form).first().each(function() {
		// Prevent submitting form if no checkboxes are checked
		if ( $('input[name="' + this.dataset.nameLike + '"]:checked').length ) {
			return true;
		}

		if (event && typeof event.preventDefault === 'function') {
			event.preventDefault();

			event.stopImmediatePropagation();
		}

		this.scrollIntoView({behavior: "smooth"});

		alert(this.dataset.error);

		error = true;

		return false;
	});

	if (error) {
		return false;
	}

	if (form.action.indexOf('_ROSARIO_PDF') != -1) // Print PDF.
	{
		form.target = '_blank';
		form.method = 'post';
		return true;
	}
	if (target.indexOf('_') == 0) // External or top target: _top, _blank.
		return true;

	if (form.enctype === 'multipart/form-data' &&
		!$(form).has('input[type="file"]').length) {
		// IE9 fix, unset enctype="multipart/form-data" if no file input in form.
		form.enctype = 'application/x-www-form-urlencoded';
	}

	if (event && typeof event.preventDefault === 'function') {
		event.preventDefault();
	}

	$.ajax(form.action, ajaxOptions(target, form.action, form));

	return false;
}

var ajaxSuccess = function(data, target, url) {

	if (target == 'body') {
		// Reset focus after AJAX so "Skip to main content" a11y link has focus first.
		$('html').focus();
	}

	// Fix CSP inline script violation: do NOT use jQuery .html() function here
	setInnerHTML(document.getElementById(target), data);

	// Change URL after AJAX.
	//http://stackoverflow.com/questions/5525890/how-to-change-url-after-an-ajax-request#5527095
	if (history.pushState && target == 'body' && document.URL != url) history.pushState(null, document.title, url);

	ajaxPrepare('#' + target, true);
}

/**
 * Set inner HTML
 * Fix CSP inline script violation: do NOT use jQuery .html() function here
 * while retaining the possibility to load <script> inside the injected HTML
 *
 * @since 12.5
 *
 * @link https://stackoverflow.com/questions/2592092/executing-script-elements-inserted-with-innerhtml
 *
 * @param {object} el   DOM element.
 * @param {string} html HTML to inject into the element. May contain <script>.
 */
var setInnerHTML = function(el, html) {
	if (! el) {
		return;
	}

	/**
	 * Remove "JS not available, reload page" script: save 1 http request
	 *
	 * @since 12.5
	 *
	 * @see PHP Warehouse() function
	 */
	el.innerHTML = html.replace('<script src="assets/js/csp/noJsReload.js?v=12.5"></script>', '');

	// Fix for Internet Explorer: do not use the ES6 version of the code
	var scripts = el.getElementsByTagName('script');

	// If we don't clone the results then "scripts"
	// will actually update live as we insert the new
	// tags, and we'll get caught in an endless loop
	var scriptsClone = [];

	for (var i = 0; i < scripts.length; i++) {
		scriptsClone.push(scripts[i]);
	}

	for (var i = 0; i < scriptsClone.length; i++) {
		var currentScript = scriptsClone[i],
			s = document.createElement('script');

		// Copy all the attributes from the original script
		for (var j = 0; j < currentScript.attributes.length; j++) {
			var a = currentScript.attributes[j];
			s.setAttribute(a.name, a.value);
		}

		/**
		 * Fix JS error object is not defined: set async="false" so scripts are loaded in the right order
		 *
		 * @link https://stackoverflow.com/questions/7308908/waiting-for-dynamically-loaded-script
		 */
		s.async = false;

		s.appendChild(document.createTextNode(currentScript.innerHTML));
		currentScript.parentNode.replaceChild(s, currentScript);
	}
}

var ajaxPrepare = function(target, scrollTop) {
	if (target == '#menu') {
		if (window.modname) {
			openMenu(modname);
		}
		if (!isMobileMenu()) {
			submenuOffset();
		}
		if (isTouchDevice()) {
			$(".adminmenu .menu-top").on('click touch', submenuOnTouch);
		}
	}

	if (target == '#menu-top') {
		if ($('#ajax_update_body').length) {
			// @see Side.php
			ajaxUpdateBody($('#ajax_update_body').data('value'));
		}
	}

	if ($('#menu_user_session').length) {
		// @see Side.php
		menuUserSession = $('#menu_user_session').data('value');
	}

	if (target == '#body') {
		if ($('#warehouse_user_session').length) {
			// @see Warehouse.php
			var warehouseUserSession = $('#warehouse_user_session').data('value');

			modname = warehouseUserSession.modname;

			if (typeof menuUserSession.studentId !== 'undefined'
				&& (menuUserSession.studentId != warehouseUserSession.studentId
					|| menuUserSession.staffId != warehouseUserSession.staffId
					|| menuUserSession.school != warehouseUserSession.school
					|| menuUserSession.mp != warehouseUserSession.mp
					|| menuUserSession.period != warehouseUserSession.period)) {
				// Current Student / User / School / Marking Period / CoursePeriod has changed
				// Update left menu
				ajaxLink('Side.php?sidefunc=update');
			}
		}

		if (window.modname) {
			// @since 12.0 CSS Add modname class to body, ie .modname-grades-reportcards-php for modname=Grades/ReportCards.php
			var modnameClass = 'modname-' + modname.replace(/([^\-a-z0-9]+)/gi, '-').toLowerCase();

			document.body.setAttribute('class', 'modules ' + modnameClass);
		}

		var h3 = $('#body h3.title').first().text(),
			h2 = $('#body h2').first().text();

		document.title = (h2 && h3 ? h2 + ' | ' + h3 : h2 + h3);

		openMenu();

		if (isMobileMenu()) {
			$('#menu').addClass('hide').removeClass('slide');

			$('body').css('overflow', '');
		}

		if (scrollTop) {
			document.body.scrollIntoView();
			$('#body').scrollTop(0);
		}

		popups.closeAll();

		$.colorbox.close();

		if (! previousNextStudent.list()) {
			// @since 13.0 add Previous Next Student plugin to core
			previousNextStudent.buttons();
		}
	}

	if (target != '#menu' && target != '#menu-top' && target != '#footer') {
		MarkDownToHTML(target);

		if (target != '#cboxLoadedContent') {
			ColorBox();
		}

		JSCalendarSetup(target);

		repeatListTHead($(target + ' table.list'));

		/**
		 * Make onclick div focusable when accessed by tab key
		 *
		 * @since 12.0
		 * @see PHP InputDivOnclick() function
		 *
		 * Use vanilla JS for speed
		 */
		var onclickDivs = document.querySelectorAll(target + ' .onclick');
		onclickDivs.forEach(function(i){
			i.setAttribute('tabindex', '0');
		});
	}

	// @since 12.5 Trigger custom ajaxPrepare event
	$(target).trigger('ajaxPrepare');
}


// Disable links while AJAX (do NOT use disabled attribute).
// http://stackoverflow.com/questions/5985839/bug-with-firefox-disabled-attribute-of-input-not-resetting-when-refreshing
$(document).on({
	'ajaxStart': function() {
		$('input[type="submit"],input[type="button"],a').css('pointer-events', 'none');
	},
	'ajaxStop': function() {
		$('input[type="submit"],input[type="button"],a').css('pointer-events', '');
	}
});


// On load.
window.onload = function() {
	// Cache <script> resources loaded in AJAX.
	$.ajaxPrefilter('script', function(options) {
		options.cache = true;
	});

	if (typeof NodeList.prototype.forEach !== 'function') {
		// @link https://stackoverflow.com/questions/52268886/object-doesnt-support-property-or-method-foreach-ie-11
		NodeList.prototype.forEach = Array.prototype.forEach;
	}

	if (isTouchDevice()) {
		$(".adminmenu .menu-top").on('click touch', submenuOnTouch);
	}

	$(document).on('click', 'a', function(e) {
		return $(this).css('pointer-events') == 'none' ? e.preventDefault() : ajaxLink(this);
	});

	// @since 12.0 Move form submit handler from ajaxPrepare() to onload
	$(document).on('submit', 'form', function(e) {
		ajaxPostForm(this, e);
	});

	/**
	 * Bottom.php buttons click related events
	 *
	 * @since 12.5
	 *
	 * @uses bottomButtonClick() function
	 */
	$('.BottomButton').on('click', bottomButtonClick);

	if (!isMobileMenu()) {
		fixedMenu();

		submenuOffset();
	}

	$(window).on('resize', function() {
		if (!isMobileMenu()) {
			// @since 8.7 Allow scrolling body whether Menu is open or not.
			$('body').css('overflow', '');

			fixedMenu();
		}
	});

	// Do NOT scroll to top onload.
	ajaxPrepare('#body', false);

	// Load body after browser history.
	if (history.pushState) window.setTimeout(ajaxPopState(), 1);

	if ($('#x_redirect_url').val()) {
		/**
		 * @since 11.4 Add XRedirectUrl JS global var for soft redirection when not an AJAX request
		 * @since 12.5 CSP remove unsafe-inline Javascript: use #x_redirect_url value instead of XRedirectUrl JS global var
		 *
		 * @see PHP RedirectURL() function
		 */
		history.replaceState({}, document.title, $('#x_redirect_url').val());
	}

	/**
	 * Add X-CSRF-Token header to $.ajax() or fetch requests containing `modfunc=`
	 *
	 * @since 12.9 Security fix #371 Request Forgery add CSRF token to links/forms containing `modfunc=`
	 *
	 * @link https://stackoverflow.com/questions/44820568/set-default-header-for-every-fetch-request#answer-72621331
	 * @link https://stackoverflow.com/questions/37963758/how-do-i-set-a-default-header-for-all-xmlhttprequests
	 */
	var token = $('meta[name=token]').attr('content');

	const originalFetch = window.fetch;

	window.fetch = function(input, init) {
		if (!init) init = {};

		if (!init.headers) init.headers = new Headers();

		if (init.headers instanceof Headers) {
			init.headers.append('X-CSRF-Token', token);
		} else if (init.headers instanceof Array) {
			init.headers.push(['X-CSRF-Token', token]);
		} else {
			// object ?
			init.headers['X-CSRF-Token'] = token;
		}

		return originalFetch(input, init);
	};

	XMLHttpRequest.prototype.origOpen = XMLHttpRequest.prototype.open;
	XMLHttpRequest.prototype.open = function() {
		this.origOpen.apply(this, arguments);
		this.setRequestHeader('X-CSRF-Token', token);
	};
};

var ajaxPopState = function() {
	window.addEventListener('popstate', function(e) {
		ajaxLink(document.URL);
	}, false);
};

/**
 * Fix browser loading cached page when page full reload (F5) + logout + Back button
 * This will reload the page
 *
 * @link https://stackoverflow.com/questions/17432899/javascript-bfcache-pageshow-event-event-persisted-always-set-to-false
 * @link https://huntr.dev/bounties/efe6ef47-d17c-4773-933a-4836c32db85c/
 */
function browserHistoryCacheBuster(event) {
	if (location.href.indexOf('Modules.php?') === -1) {
		// Current page is not Modules.php, no login required, skip.
		return;
	}

	// persisted indicates if the document is loading from a cache (not reliable)
	if ((event && event.persisted)
		|| window.performance && (performance.navigation.type == 2
			|| (performance.getEntriesByType
				&& performance.getEntriesByType("navigation")[0]
				&& performance.getEntriesByType("navigation")[0].type === 'back_forward'))) {
		location.reload();
	}
}

browserHistoryCacheBuster();

/**
 * onpageshow: Same as above for Safari (does not execute Javascript on history back)
 *
 * @link https://web.dev/bfcache/
 */
window.onpageshow=function(event) {
	browserHistoryCacheBuster(event);
};

// onunload: Fix for Firefox to execute Javascript on history back.
window.onunload = function() {};

/**
 * ListOutput search
 *
 * @deprecated since 13.0 Please use instantList.search() instead
 */
var LOSearch = function(ev, val, url) {
	console.warn( 'LOSearch() function is deprecated since RosarioSIS 13.0. Please use instantList.search() instead.' );
}

// Repeat long list table header.
var repeatListTHead = function($lists) {
	if (!$lists.length)
		return;

	$lists.each(function(i, tbl) {
		var trs = $(tbl).children("thead,tbody").children("tr:visible"),
			tr_num = trs.length,
			tr_max = 20;

		// If more than 20 rows.
		if (tr_num > tr_max) {
			var th = trs[0];

			// Each 20 rows, or at the end if number of rows <= 40.
			for (var j = (tr_num > tr_max * 2 ? tr_max : tr_num - 1), trs2th = []; j < tr_num; j += tr_max) {
				trs2th.push(trs[j]);
			}

			// Clone header.
			$(th).clone().addClass('thead-repeat').insertAfter(trs2th);
		}
	});
}


// Side.php JS.
var openMenu = function() {

	$("#selectedMenuLink,#selectedModuleLink").attr('id', '');

	if (!window.modname || !modname || modname == 'misc/Portal.php') return;

	// Fix #319 Try a full match first to identify selected menu link.
	var $menuLink = $('.wp-submenu a[href="Modules.php' + window.location.search + '"]');

	if ( ! $menuLink.length ) {
		$menuLink = $('.wp-submenu a[href$="' + modname + '"]');
	}

	$menuLink.first().attr('id', 'selectedMenuLink');

	// Add selectedModuleLink.
	$('#selectedMenuLink').parents('.menu-module').children('.menu-top').attr('id', 'selectedModuleLink');
}

// Adjust Side.php submenu bottom offset.
function submenuOffset() {
	$(".adminmenu .menu-top").on('mouseover focus', function() {
		var submenu = $(this).next(".wp-submenu"),
			offsetTop = $("#footer").offset().top;

		if ( $("#footer").css('bottom') != '0px' ) {
			// Footer is on top of the screen.
			offsetTop += window.innerHeight;

			// Unless module is the last visible on screen.
			if ( $(this).parent()[0].getBoundingClientRect().top < ( window.innerHeight - $(this).parent().outerHeight() * 2 ) ) {
				// Raise height by 1 submenu item so we stay above browser URL.
				offsetTop -= submenu.children("li").first().outerHeight();
			}
		}

		moveup = offsetTop - $(this).offset().top - submenu.outerHeight();
		submenu.css("margin-top", (moveup < 0 ? moveup : 0) + 'px');
	});
}

var submenuOnTouch = function(e) {
	// @since 4.4 Open submenu on touch (mobile & tablet).
	e.preventDefault();

	$("#selectedModuleLink").attr('id', '');
	$(this).attr('id', 'selectedModuleLink');

	if ($(this).offset().top < this.scrollHeight) {
		/* Mobile: Adjust scroll position to selectedModuleLink when X position is < 0 */
		$('#menu').scrollTop($('#menu')[0].scrollTop - Math.abs($(this).offset().top) - this.scrollHeight);
	}

	return false;
};

// Bottom.php JS.
var toggleHelp = function() {
	if ($('#footerhelp').hasClass('slide')) hideHelp();
	else showHelp();
}

var showHelp = function() {
	var $fh = $('#footerhelp'),
		$fhc = $('#footerhelp .footerhelp-content');

	if (modname !== showHelp.tmp) {
		$('.loading.BottomButton').css('visibility', 'visible');
		$.get("Bottom.php?bottomfunc=help&modname=" + encodeURIComponent(modname), function(data) {
			showHelp.tmpdata = data;
			$fhc.html(data);
			$fh.scrollTop(0);
			$fh.addClass('slide');
		}).fail(ajaxError).always(function() {
			$('.loading.BottomButton').css('visibility', 'hidden');
		});

		showHelp.tmp = modname;

		return;
	} else if (showHelp.tmpdata && !$fh.html()) {
		$fhc.html(showHelp.tmpdata);
	}

	$fh.toggleClass('slide');
}

var hideHelp = function() {
	$('#footerhelp').removeClass('slide');
}

var expandMenu = function() {
	var $menu = $('#menu');

	if (isMobileMenu()) {
		if ($menu.hasClass('hide')) {
			$menu.removeClass('hide');

			setTimeout(function() {
				$menu.addClass('slide');

				// @since 5.1 Prevent scrolling body while Menu is open.
				$('body').css('overflow', 'hidden');
			}, 10);
		} else {
			$menu.removeClass('slide');

			setTimeout(function() {
				$menu.addClass('hide');

				$('body').css('overflow', '');
			},
			// Get transition duration from CSS
			(parseFloat(window.getComputedStyle($menu[0]).transitionDuration)) * 1000);
		}

		return;
	}

	if ($menu.hasClass('hide')) {
		$menu.removeClass('slide hide');
	} else {
		$menu.addClass('slide');

		setTimeout(function() {
			$menu.addClass('hide');
		},
		// Get transition duration from CSS
		(parseFloat(window.getComputedStyle($menu[0]).transitionDuration)) * 1000);
	}

	$('body').css('overflow', '');
}

/**
 * Bottom.php buttons click related events
 *
 * @since 12.5
 *
 * @uses expandMenu() & toggleHelp() functions
 */
bottomButtonClick = function() {
	if (this.id === 'BottomButtonMenu') {
		return expandMenu();
	}

	if (this.id === 'BottomButtonPrint') {
		this.href = 'Bottom.php?bottomfunc=print&' + window.location.search.substring(1);

		return;
	}

	if (this.id === 'BottomButtonHelp') {
		return toggleHelp();
	}
}

/**
 * File input max file size validation
 * If file size > max:
 * - Alert file input title attribute, ie. "Maximum file size: 3Mb".
 * - Clear input.
 *
 * @since 5.2
 * @since 7.8 Handle `multiple`` files attribute.
 *
 * @see PHP FileInput() function.
 *
 * @param  {object} file File input object.
 * @param  {int}    max  Max file size in Mb.
 */
var fileInputSizeValidate = function(file, max) {
	var fileSize = 0;

	for (var i = 0; i < file.files.length; i++) {
		fileSize += file.files[ i ].size / 1024 / 1024; // In Mb.
	}

	if (fileSize > max) {
		alert(file.title);
		$(file).val(''); // Clear input.
	}
};
