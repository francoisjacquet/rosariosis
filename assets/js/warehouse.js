//Modules.php JS
var locked;

function addHTML(html, id, replace) {
	if (locked !== false) {
		if (replace === true) document.getElementById(id).innerHTML = html;
		else document.getElementById(id).innerHTML = document.getElementById(id).innerHTML + html;
	}
}

function checkAll(form, value, name_like) {
	for (i = 0; i < form.elements.length; i++) {
		chk = form.elements[i];
		if (chk.type == 'checkbox' && chk.name.substr(0, name_like.length) == name_like) chk.checked = value;
	}
}

function switchMenu(el) {
	$(el).nextAll('table').first().toggle();
	$(el).toggleClass('switched');
}

// Toggle user photo & upload form
function switchUserPhoto() {
	$('.user-photo-form,.user-photo').toggle();
	return false;
}

//IE8 HTML5 tags fix
var tags = 'article|aside|footer|header|hgroup|nav|section'.split('|'),
	i = 0,
	max = tags.length;
for (; i < max; i++) {
	document.createElement(tags[i]);
}

//popups
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
		for(var i=0, max=this.childs.length; i<max; i++)
		{
			if (!this.childs[i].closed)
				this.childs[i].close();
		}
	};
}

//touchScroll, enables overflow:auto on mobile
//https://gist.github.com/chrismbarr/4107472
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
else // add .no-touch CSS class
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

	// send AJAX request only if input modified
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

	// toggle MD preview & Input
	md_prev.toggle();
	input.toggle();
	// disable Write / Preview tab
	md_prev.siblings('.tab').toggleClass('disabled');
}

function MarkDownToHTML()
{
	$('.markdown-to-html').html(function(i, html){

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

		return sdc.makeHtml( html );
	});
}

//JSCalendar
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
			$('.loading').css('visibility', 'visible');
		},
		success: function (data) {
			if (form && form.method == 'get') {
				var getStr = [];

				// Fix advanced search forms (student & user) URL > 2000 chars
				if (form.name == 'search') {
					var formArray = $(form).formToArray();

					$(formArray).each(function(i,el){
						// only add not empty values
						if (el.value !== '')
							getStr.push(el.name + '=' + el.value);
					});

					getStr = getStr.join('&');
				}
				else {
					getStr = $(form).formSerialize();
				}

				url += (url.indexOf('?') != -1 ? '&' : '?') + getStr;
			}
			ajaxSuccess(data, target, url);
		},
		error: function (x, st, err) {
			alert("Ajax get error\nStatus: " + st + "\nHTTP status: " + err + "\nURL: " + url);
		},
		complete: function () {
			$('.loading').css('visibility', 'hidden');

			hideHelp();
		}
	};
}

function ajaxLink(link) {
	//will work only if in the onclick there is no error!

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

	if (href.indexOf('#') != -1 || target == '_blank' || target == '_top') //internal/external/index.php anchor
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

	if (form.action.indexOf('_ROSARIO_PDF') != -1) //print PDF
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
	//change URL after AJAX
	//http://stackoverflow.com/questions/5525890/how-to-change-url-after-an-ajax-request#5527095
	$('#' + target).html(data);

	if (history.pushState && target == 'body' && document.URL != url) history.pushState(null, document.title, url);

	ajaxPrepare('#' + target);
}

function ajaxPrepare(target) {
	if (scrollTop == 'Y' && target == '#body') body.scrollIntoView();

	$(target + ' form').each(function () {
		ajaxPostForm(this, false);
	});
	$(target + ' a').click(function (e) {
		return $(this).css('pointer-events') == 'none' ? e.preventDefault() : ajaxLink(this);
	});

	if (target == '#menu' && window.modname) openMenu(modname);

	if (isTouchDevice()) $('.rt').each(function (i, e) {
		touchScroll(e.tBodies[0]);
	});

	var h3 = $('#body h3.title').text();
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
});
$(document).ajaxStop(function () {
	$('input[type="submit"],input[type="button"],a').css('pointer-events', '').attr('disabled', false);
});

//onload
window.onload = function () {
	ajaxPrepare('body');

	//load body after browser history
	if (history.pushState) window.setTimeout(function () {
		window.addEventListener('popstate', function (e) {
			ajaxLink(document.URL);
		}, false);
	}, 1);
};

//ListOutput JS
function LOSearch( event, val, url ) {

	if ( !event || event.keyCode == 13 ) {
		return ajaxLink( url + ( val ? '&LO_search=' + encodeURIComponent(val) : '' ) );
	}
}

//Repeat long list table header
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
			for( i = (tr_num > tr_max*2 ? tr_max : tr_num-1), trs2th = []; i < tr_num; i += tr_max ) {
				trs2th.push(trs[i]);
			}

			// clone header
			$(th).clone().addClass('thead-repeat').insertAfter( trs2th );
		}
	});
}


//Side.php JS
var old_modcat = false,
	menu_link = 'Side.php';

function openMenu(modname) {
	if (modname != 'misc/Portal.php') {
		if ((oldA = document.getElementById("selectedMenuLink"))) oldA.id = "";
		$('.wp-submenu a[href$="' + modname + '"]:first').each(function () {
			this.id = "selectedMenuLink";
		});
		//add selectedModuleLink
		if ((oldA = document.getElementById("selectedModuleLink"))) oldA.id = "";

		var modcat;
		if (modname === '') modcat = old_modcat;
		else $('#selectedMenuLink').parents('.wp-submenu').each(function () {
			modcat = this.id.replace('menu_', '');
		});

		$('a[href*="' + modcat + '"].menu-top').each(function () {
			this.id = "selectedModuleLink";
		});

		old_modcat = modcat;
	}
}

// adjust Side.php submenu bottom offset
function submenuOffset() {
	$(".adminmenu .menu-top").mouseover(function(){
		var submenu = $(this).next(".wp-submenu");
		var moveup = $("#footer").offset().top - $(this).offset().top - submenu.outerHeight();
		submenu.css("margin-top", (moveup < 0 ? moveup : 0) + 'px');
	});
}

//Bottom.php JS
function toggleHelp() {
	if ($('#footerhelp').css('display') !== 'block') showHelp();
	else hideHelp();
}

var old_modname = false;

function showHelp() {
	if (modname !== old_modname) {
		$.get("Bottom.php?modfunc=help&modname=" + modname, function (data) {
			$('#footerhelp').html(data);
			if (isTouchDevice()) touchScroll(document.getElementById('footerhelp'));
		}).fail(function () {
			alert('Error: expandHelp ' + modname);
		});
		old_modname = modname;
	}
	$('#footerhelp').show();
	$('#footer').css('height', function (i, val) {
		return parseInt(val,10) + parseInt($('#footerhelp').css('height'),10);
	});
}

function hideHelp() {
	$('#footerhelp').hide();
	$('#footer').css('height', '');
}

function expandMenu() {
	$('#menu,#menuback').toggleClass('hide');
}
