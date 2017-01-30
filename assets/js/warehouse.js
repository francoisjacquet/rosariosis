// Modules.php JS
function addHTML(html, id, replace) {
	var el = document.getElementById( id );

	el.innerHTML = replace ? html : el.innerHTML + html;
}

function checkAll(form, value, name_like) {
	for (var i = 0, max = form.elements.length; i < max; i++) {
		var chk = form.elements[i];
		if (chk.type == 'checkbox' && chk.name.substr(0, name_like.length) == name_like) chk.checked = value;
	}
}

function switchMenu(el) {
	$(el).toggleClass('switched').nextAll('table').first().toggle();
}

/**
 * IE8 HTML5 tags fix
 *
 * @deprecated Remove when IE8 usage < 1%
 */
for (var tags = 'article|aside|footer|header|hgroup|nav|section'.split('|'), i = 0, max = tags.length; i < max; i++) {
	document.createElement(tags[i]);
}

// Popups
var popups = new popups();

function popups()
{
	this.childs = [];

	this.open = function(url, params) {
		if (!params)
			params = 'scrollbars=yes,resizable=yes,width=800,height=400';

		this.childs.push(window.open(url, '', params));
	};

	this.closeAll = function() {
		for (var i=0, max=this.childs.length; i<max; i++) {
			var child = this.childs[i];
			if (!child.closed)
				child.close();
		}
	};
}

/**
 * touchScroll, enables overflow:auto on mobile
 *
 * @link https://gist.github.com/chrismbarr/4107472
 *
 * @deprecated Remove when Android v.2.3 (Gingerbread) & old Safari (iOS < 5) usage < 1%
 * @link http://chris-barr.com/2010/05/scrolling_a_overflowauto_element_on_a_touch_screen_device/
 */
function touchScroll(el) {
	var startY = 0,
		startX = 0;

	el.addEventListener("touchstart", function (e) {
		startY = this.scrollTop + e.touches[0].pageY;
		startX = this.scrollLeft + e.touches[0].pageX;
	}, false);

	el.addEventListener("touchmove", function (e) {
		var tch = e.touches[0];
		if ((this.scrollTop < this.scrollHeight - this.offsetHeight && this.scrollTop + tch.pageY < startY - 5) || (this.scrollTop !== 0 && this.scrollTop + tch.pageY > startY + 5)) e.preventDefault();
		if ((this.scrollLeft < this.scrollWidth - this.offsetWidth && this.scrollLeft + tch.pageX < startX - 5) || (this.scrollLeft !== 0 && this.scrollLeft + tch.pageX > startX + 5)) e.preventDefault();
		this.scrollTop = startY - tch.pageY;
		this.scrollLeft = startX - tch.pageX;
	}, false);
}

function isTouchDevice() {
	try {
		document.createEvent("TouchEvent");
		return true;
	} catch (e) {
		return false;
	}
}

// ColorBox
if (isTouchDevice()) $(document).bind("cbox_complete", function () {
	touchScroll(document.getElementById("cboxLoadedContent"));
});
else // Add .no-touch CSS class
	document.documentElement.className += " no-touch";

function ColorBox() {
	var cWidth = 640, cHeight = 390;
	if ( screen.width < 768 ) {
		cWidth = 300; cHeight = 183;
	}

	$('.rt2colorBox').before(function(i){
		if ( this.id ) {
			var $el = $(this);
			// only if content > 1 line & text <= 36 chars.
			if ( $el.text().length > 36 || $el.children().height() > $el.parent().height() ) {
				return '<div class="link2colorBox"><a class="colorboxinline" href="#' + this.id + '"></a></div>';
			}
		}
	});
	$('.colorbox').colorbox();
	$('.colorboxiframe').colorbox({iframe:true, innerWidth:cWidth, innerHeight:cHeight});
	$('.colorboxinline').colorbox({inline:true, maxWidth:'95%', maxHeight:'85%', scrolling:true});

}

// MarkDown
var md_last_val = {},
	sdc;

function MarkDownInputPreview( input_id )
{
	var input = $('#' + input_id),
		html = input.val(),
		md_prev = $('#divMDPreview' + input_id);

	// Send AJAX request only if input modified
	if ( !md_prev.is(":visible") && html !== '' && md_last_val[input_id] !== html )
	{
		md_last_val[input_id] = html;

		if ( typeof( sdc ) !== 'object' )
		{
			sdc = new showdown.Converter({
				tables: true,
				simplifiedAutoLink: true,
				parseImgDimensions: true,
				tasklists: true,
				literalMidWordUnderscores: true,
			});
		}

		// Convert MarkDown to HTML
		md_prev.html( sdc.makeHtml( html ) );
	}

	// MD preview = Input size
	if ( !md_prev.is(":visible") ) {

		md_prev.css({'height': input.css('height'), 'width': input.css('width')});
		//md_prev.parent('.md-preview').css({'max-width': input.css('width')});
	}

	// Toggle MD preview & Input
	md_prev.toggle();
	input.toggle();
	// Disable Write / Preview tab
	md_prev.siblings('.tab').toggleClass('disabled');
}

function MarkDownToHTML()
{
	$('.markdown-to-html').html(function(i, html){

		if ( typeof( sdc ) !== 'object' ) {
			sdc = new showdown.Converter({
				tables: true,
				simplifiedAutoLink: true,
				parseImgDimensions: true,
				tasklists: true,
				literalMidWordUnderscores: true,
			});
		}

		return sdc.makeHtml( html );
	});
}

// JSCalendar
function JSCalendarSetup()
{
	$('.button.cal').each(function(i, el){
		var j = el.id.replace( 'trigger', '' );

		Calendar.setup({
			monthField: "monthSelect" + j,
			dayField: "daySelect" + j,
			yearField: "yearSelect" + j,
			ifFormat: "%d-%b-%y",
			button: el.id,
			align: "Tl",
			singleClick: true
		});
	});
}

function ajaxOptions(target, url, form) {
	return {
		beforeSend: function (data) {
			// AJAX error hide.
			$('.ajax-error').hide();

			$('.loading').css('visibility', 'visible');
		},
		success: function (data) {
			if (form && form.method == 'get') {
				var getStr = [];

				// Fix advanced search forms (student & user) URL > 2000 chars
				if (form.name == 'search') {
					var formArray = $(form).formToArray();

					$(formArray).each(function(i,el){
						// Only add not empty values
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
		error: ajaxError,
		complete: function () {
			$('.loading').css('visibility', 'hidden');

			hideHelp();
		}
	};
}

function ajaxError(x, st, err) {
	var code = x.status,
		errorMsg = 'AJAX error. ' + code + ' ';

	if (code === 0) {
		errorMsg += 'Check your Network';
	} else if (code == 404) {
		errorMsg += 'Requested URL not found: ' + url;
	} else if (st == 'parsererror') {
		errorMsg += 'JSON parse failed';
	} else if (st == 'timeout') {
		errorMsg += 'Request Timeout';
	} else if (st == 'abort') {
		errorMsg += 'Request Aborted';
	} else {
		errorMsg += err;
	}

	// AJAX error popup.
	$('.ajax-error').html(errorMsg).fadeIn();
}

function ajaxLink(link) {
	// Will work only if in the onclick there is no error!

	var href,target;

	if ( typeof link == 'string' ) {
		href = link;
		target = 'body';
		if ( href == 'Side.php' ) target = 'menu';
		else if ( href == 'Bottom.php' ) target = 'footer';
	} else {
		href = link.href;
		target = link.target;
	}

	if (href.indexOf('#') != -1 || target == '_blank' || target == '_top') // Internal/external/top anchor
		return true;

	if (!target) {
		if (href.indexOf('Modules.php') != -1) target = 'body';
		else return true;
	}

	$.ajax(href, ajaxOptions(target, href, false));
	return false;
}

function ajaxPostForm(form, submit) {
	var target = form.target || 'body';

	if (form.action.indexOf('_ROSARIO_PDF') != -1) // Print PDF
	{
		form.target = '_blank';
		form.method = 'post';
		return true;
	}
	if (target == '_top')
		return true;

	var options = ajaxOptions(target, form.action, form);
	if (submit) $(form).ajaxSubmit(options);
	else $(form).ajaxForm(options);
	return false;
}

function ajaxSuccess(data, target, url) {
	// Change URL after AJAX
	//http://stackoverflow.com/questions/5525890/how-to-change-url-after-an-ajax-request#5527095
	$('#' + target).html(data);

	var doc = document;

	if (history.pushState && target == 'body' && doc.URL != url) history.pushState(null, doc.title, url);

	ajaxPrepare('#' + target);
}

function ajaxPrepare(target) {
	if (scrollTop == 'Y' && target == '#body') body.scrollIntoView();

	$(target + ' form').each(function () {
		ajaxPostForm(this, false);
	});

	if (target == '#menu' && window.modname) openMenu(modname);

	if (isTouchDevice()) $('.rt').each(function (i, e) {
		touchScroll(e.tBodies[0]);
	});

	var h3 = $('#body h3.title').first().text();
	document.title = $('#body h2').text() + (h3 ? ' | ' + h3 : '');

	submenuOffset();

	if ( target == '#body' || target == 'body' ) {

		if ( screen.width > 767 ) {
			fixedMenu();
		}

		popups.closeAll();

		MarkDownToHTML();

		ColorBox();

		JSCalendarSetup();

		repeatListTHead( $('table.list') );
	}
}


//disable links while AJAX
$(document).ajaxStart(function () {
	$('input[type="submit"],input[type="button"],a').css('pointer-events', 'none').attr('disabled', true);
}).ajaxStop(function () {
	$('input[type="submit"],input[type="button"],a').css('pointer-events', '').attr('disabled', false);
});



// onload
window.onload = function () {
	$(document).on('click', 'a', function (e) {
		return $(this).css('pointer-events') == 'none' ? e.preventDefault() : ajaxLink(this);
	});

	ajaxPrepare('body');

	// Load body after browser history
	if (history.pushState) window.setTimeout(function () {
		window.addEventListener('popstate', function (e) {
			ajaxLink(document.URL);
		}, false);
	}, 1);
};

// ListOutput JS
function LOSearch( event, val, url ) {

	if ( !event || event.keyCode == 13 ) {
		return ajaxLink( url + ( val ? '&LO_search=' + encodeURIComponent(val) : '' ) );
	}
}

// Repeat long list table header
function repeatListTHead( $lists )
{
	if ( !$lists.length )
		return;

	$lists.each(function( i, tbl ){
		var trs = $(tbl).children("thead,tbody").children("tr"),
			tr_num = trs.length,
			tr_max = 20;

		// If more than 20 rows
		if ( tr_num > tr_max ) {
			var th = trs[0];

			// each 20 rows, or at the end if number of rows <= 40
			for( var j = (tr_num > tr_max*2 ? tr_max : tr_num-1), trs2th = []; j < tr_num; j += tr_max ) {
				var tr = trs[j];
				trs2th.push(tr);
			}

			// clone header
			$(th).clone().addClass('thead-repeat').insertAfter( trs2th );
		}
	});
}


// Side.php JS
function openMenu(modname) {

	if (modname == 'misc/Portal.php') return;

	var oldA,
		modcat;

	$("#selectedMenuLink").attr('id', '');

	$('.wp-submenu a[href$="' + modname + '"]').first().attr('id', 'selectedMenuLink');

	// Add selectedModuleLink
	$("#selectedModuleLink").attr('id', '');

	if (modname === '') modcat = openMenu.tmpCat;
	else $('#selectedMenuLink').parents('.wp-submenu').each(function () {
		modcat = this.id.replace('menu_', '');
	});

	$('#menu_' + modcat).prev().attr('id', 'selectedModuleLink');

	openMenu.tmpCat = modcat;
}

// Adjust Side.php submenu bottom offset
function submenuOffset() {
	$(".adminmenu .menu-top").mouseover(function(){
		var submenu = $(this).next(".wp-submenu"),
			moveup = $("#footer").offset().top - $(this).offset().top - submenu.outerHeight();
		submenu.css("margin-top", (moveup < 0 ? moveup : 0) + 'px');
	});
}

// Bottom.php JS
function toggleHelp() {
	if ($('#footerhelp').css('display') !== 'block') showHelp();
	else hideHelp();
}

function showHelp() {
	var $fh = $('#footerhelp');
	if (modname !== showHelp.tmp) {
		$('.loading').css('visibility', 'visible');
		$.get("Bottom.php?modfunc=help&modname=" + modname, function (data) {
			showHelp.tmpdata = data;
			$fh.html(data).scrollTop(0);
			if (isTouchDevice()) touchScroll( $fh[0] );
		}).fail( ajaxError ).always( function() {
			$('.loading').css('visibility', 'hidden');
		});

		showHelp.tmp = modname;
	} else if (showHelp.tmpdata && ! $fh.html()) {
		$fh.html(showHelp.tmpdata);
	}
	$fh.show();
	$('#footer').css('height', function (i, val) {
		return parseInt(val,10) + parseInt($fh.css('height'),10);
	});
}

function hideHelp() {
	$('#footerhelp').hide();
	$('#footer').css('height', '');
}

function expandMenu() {
	$('#menu,#menuback').toggleClass('hide');
}
