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

	// Here we use jQuery so inline Javascript gets evaluated!
	if (replace) {
		$(el).html(html);
	} else {
		$(el).append(html);
	}
}

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
 * @param  {DOMelement} el The element, this.
 */
var switchMenu = function(el) {
	$(el).toggleClass('switched').nextAll('table').first().toggle();
}

// Popups
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
	return Math.round($('#menu').width()) === window.innerWidth;
}

if (!isTouchDevice()) {
	// Add .no-touch CSS class.
	document.documentElement.className += " no-touch";
}

var ColorBox = function() {
	var cWidth = 640,
		cHeight = 390;
	if (screen.width < 768) {
		cWidth = 320;
		cHeight = 195;
	}

	$('.rt2colorBox').before(function(i) {
		if (this.id) {
			var $el = $(this),
				childrenHeight = 0;

			$el.children().each(function() {
				childrenHeight += $(this).height();
			});

			// only if content > 1 line & text <= 36 chars.
			if ($el.text().length > 36 || childrenHeight > $el.parent().height()) {
				return '<div class="link2colorBox"><a class="colorboxinline" href="#' + this.id + '"></a></div>';
			}
		}
	});
	$('.colorbox').colorbox();
	$('.colorboxiframe').colorbox({
		iframe: true,
		innerWidth: cWidth,
		innerHeight: cHeight
	});
	$('.colorboxinline').colorbox({
		inline: true,
		maxWidth: '95%',
		maxHeight: '85%',
		minWidth: 306,
		minHeight: 153,
		scrolling: true
	});
}

// MarkDown.
var GetMDConverter = function() {
	if (typeof GetMDConverter.marked === 'undefined') {
		GetMDConverter.marked = function(markDown) {
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
			});

			return DOMPurify.sanitize(md);
		};
	}

	return GetMDConverter.marked;
}

var MarkDownInputPreview = function(input_id) {
	var input = $('#' + input_id),
		md = input.val(),
		md_prev = $('#divMDPreview' + input_id);

	if (!md_prev.is(":visible")) {
		var mdc = GetMDConverter();

		// Convert MarkDown to HTML.
		md_prev.html(mdc(md));

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

var MarkDownToHTML = function() {
	$('.markdown-to-html').html(function(i, txt) {

		var mdc = GetMDConverter();

		// Fix decode > HTML characters so blockquote are converted.
		var md = mdc( txt.replace(/&gt;/g, '>') );

		// Add paragraph to text.
		var txtP = '<p>' + txt + '</p>';

		if ( txtP == md.trim() ) {
			// No MarkDown in text, return raw text.
			return txt;
		}

		return md;
	});
}

// JSCalendar.
var JSCalendarSetup = function() {
	$('.button.cal').each(function(i, el) {
		var j = el.id.replace('trigger', '');

		Calendar.setup({
			monthField: "monthSelect" + j,
			dayField: "daySelect" + j,
			yearField: "yearSelect" + j,
			ifFormat: "%d-%b-%y",
			button: el.id,
			align: "Tl",
			singleClick: true,
			cache: true
		});
	});
}

var ajaxOptions = function(target, url, form) {
	return {
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
				var getStr = [];

				// Fix advanced search forms (student & user) URL > 2000 chars.
				if (form.name == 'search') {
					var formArray = $(form).formToArray();

					$(formArray).each(function(i, el) {
						// Only add not empty values.
						if (el.value !== '')
							getStr.push(el.name + '=' + el.value);
					});

					getStr = getStr.join('&');
				} else {
					getStr = $(form).formSerialize();
				}

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

	if (href.indexOf('#') != -1 || target == '_blank' || target == '_top') // Internal/external/top anchor.
		return true;

	if (!target) {
		if (href.indexOf('Modules.php') != -1) target = 'body';
		else return true;
	}

	$.ajax(href, ajaxOptions(target, href, false));
	return false;
}

var ajaxPostForm = function(form, submit) {
	var target = form.target || 'body';

	if (form.action.indexOf('_ROSARIO_PDF') != -1) // Print PDF.
	{
		form.target = '_blank';
		form.method = 'post';
		return true;
	}
	if (target == '_top')
		return true;

	if (form.enctype === 'multipart/form-data' &&
		!$(form).has('input[type="file"]').length) {
		// IE9 fix, unset enctype="multipart/form-data" if no file input in form.
		form.enctype = 'application/x-www-form-urlencoded';
	}

	var options = ajaxOptions(target, form.action, form);
	if (submit) $(form).ajaxSubmit(options);
	else $(form).ajaxForm(options);
	return false;
}

var ajaxSuccess = function(data, target, url) {

	if (target == 'body') {
		// Reset focus after AJAX so "Skip to main content" a11y link has focus first.
		$('html').focus();
	}

	// Change URL after AJAX.
	//http://stackoverflow.com/questions/5525890/how-to-change-url-after-an-ajax-request#5527095
	$('#' + target).html(data);

	var doc = document;

	if (history.pushState && target == 'body' && doc.URL != url) history.pushState(null, doc.title, url);

	ajaxPrepare('#' + target, true);
}

var ajaxPrepare = function(target, scrollTop) {
	$(target + ' form').each(function() {
		ajaxPostForm(this, false);
	});

	if (target == '#menu') {
		if (window.modname) {
			openMenu(modname);
		}
		if (!isMobileMenu()) {
			submenuOffset();
		}
	}

	var h3 = $('#body h3.title').first().text(),
		h2 = $('#body h2').first().text();

	document.title = (h2 && h3 ? h2 + ' | ' + h3 : h2 + h3);

	if (target == '#body' || target == 'body') {

		openMenu();

		if (isMobileMenu()) {
			$('#menu').addClass('hide');

			$('body').css('overflow', '');
		}

		if (scrollTop) {
			document.body.scrollIntoView();
			$('#body').scrollTop(0);
		}

		popups.closeAll();

		MarkDownToHTML();

		ColorBox();

		JSCalendarSetup();

		repeatListTHead($('table.list'));
	}
}


// Disable links while AJAX (do NOT use disabled attribute).
// http://stackoverflow.com/questions/5985839/bug-with-firefox-disabled-attribute-of-input-not-resetting-when-refreshing
$(document).ajaxStart(function() {
	$('input[type="submit"],input[type="button"],a').css('pointer-events', 'none');
}).ajaxStop(function() {
	$('input[type="submit"],input[type="button"],a').css('pointer-events', '');
});


// On load.
window.onload = function() {
	// Cache <script> resources loaded in AJAX.
	$.ajaxPrefilter('script', function(options) {
		options.cache = true;
	});

	// @since 4.4 Open submenu on touch (mobile & tablet).
	if (isTouchDevice()) {
		$(".adminmenu .menu-top").on('click touch', function(e) {
			e.preventDefault();

			$("#selectedModuleLink").attr('id', '');
			$(this).attr('id', 'selectedModuleLink');

			if ($(this).offset().top < this.scrollHeight) {
				/* Mobile: Adjust scroll position to selectedModuleLink when X position is < 0 */
				$('#menu').scrollTop($('#menu')[0].scrollTop - Math.abs($(this).offset().top) - this.scrollHeight);
			}

			return false;
		});
	}

	$(document).on('click', 'a', function(e) {
		return $(this).css('pointer-events') == 'none' ? e.preventDefault() : ajaxLink(this);
	});

	if (!isMobileMenu()) {
		fixedMenu();

		submenuOffset();
	}

	$(window).resize(function(){
		if (!isMobileMenu()) {
			// @since 8.7 Allow scrolling body whether Menu is open or not.
			$('body').css('overflow', '');

			fixedMenu();
		}
	});

	// Do NOT scroll to top onload.
	ajaxPrepare('body', false);

	// Load body after browser history.
	if (history.pushState) window.setTimeout(ajaxPopState(), 1);
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

// ListOutput JS.
var LOSearch = function(ev, val, url) {
	if (ev.type === 'click' || ev.keyCode == 13) {
		ev.preventDefault();
		return ajaxLink(url + (val ? '&LO_search=' + encodeURIComponent(val) : ''));
	}
}

// Repeat long list table header.
var repeatListTHead = function($lists) {
	if (!$lists.length)
		return;

	$lists.each(function(i, tbl) {
		var trs = $(tbl).children("thead,tbody").children("tr"),
			tr_num = trs.length,
			tr_max = 20;

		// If more than 20 rows.
		if (tr_num > tr_max) {
			var th = trs[0];

			// Each 20 rows, or at the end if number of rows <= 40.
			for (var j = (tr_num > tr_max * 2 ? tr_max : tr_num - 1), trs2th = []; j < tr_num; j += tr_max) {
				var tr = trs[j];
				trs2th.push(tr);
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

// Bottom.php JS.
var toggleHelp = function() {
	if ($('#footerhelp').css('display') !== 'block') showHelp();
	else hideHelp();
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
		}).fail(ajaxError).always(function() {
			$('.loading.BottomButton').css('visibility', 'hidden');
		});

		showHelp.tmp = modname;
	} else if (showHelp.tmpdata && !$fh.html()) {
		$fhc.html(showHelp.tmpdata);
	}

	$fh.show();
}

var hideHelp = function() {
	$('#footerhelp').hide();
}

var expandMenu = function() {
	$('#menu').toggleClass('hide');

	$('body').css('overflow', '');

	if (isMobileMenu() &&
		!$('#menu').hasClass('hide')) {
		// @since 5.1 Prevent scrolling body while Menu is open.
		$('body').css('overflow', 'hidden');
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
