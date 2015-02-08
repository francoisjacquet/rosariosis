//Modules.php JS
var locked;
function addHTML(html,id,replace){
	if(locked!=false){
		if(replace==true) document.getElementById(id).innerHTML = html;
		else document.getElementById(id).innerHTML = document.getElementById(id).innerHTML + html;
	}
}
function checkAll(form,value,name_like){
	var checked = (value==true)?true:false;

	for(i=0;i<form.elements.length;i++){
		if(form.elements[i].type=='checkbox' && form.elements[i].name!='controller' && form.elements[i].name.substr(0,name_like.length)==name_like)
			form.elements[i].checked = checked;
	}
}
function switchMenu(id){
	var $id = $('#'+id);
	if($id.css('display')=='none'){
		$id.show();
		$('#'+id+'_arrow').attr({
			src: 'assets/arrow_down.gif',
			height: 9
		});
	}else{
		$id.hide();
		$('#'+id+'_arrow').attr({
			src: 'assets/arrow_right.gif',
			height: 12
		});
	}
}

//IE8 HTML5 tags fix
var tags='article|aside|footer|header|hgroup|nav|section'.split('|'), i=0, max=tags.length;
for(;i<max;i++) {
    document.createElement(tags[i]);
}

//touchScroll, enables overflow:auto on mobile
//https://gist.github.com/chrismbarr/4107472
function touchScroll(el){
	var scrollStartPosY=0;
	var scrollStartPosX=0;

	el.addEventListener("touchstart", function(e) {
		scrollStartPosY=this.scrollTop+e.touches[0].pageY;
		scrollStartPosX=this.scrollLeft+e.touches[0].pageX;
	},false);

	el.addEventListener("touchmove", function(e) {
		if ((this.scrollTop < this.scrollHeight-this.offsetHeight &&
			this.scrollTop+e.touches[0].pageY < scrollStartPosY-5) ||
			(this.scrollTop != 0 && this.scrollTop+e.touches[0].pageY > scrollStartPosY+5))
				e.preventDefault(); 
		if ((this.scrollLeft < this.scrollWidth-this.offsetWidth &&
			this.scrollLeft+e.touches[0].pageX < scrollStartPosX-5) ||
			(this.scrollLeft != 0 && this.scrollLeft+e.touches[0].pageX > scrollStartPosX+5))
				e.preventDefault(); 
		this.scrollTop=scrollStartPosY-e.touches[0].pageY;
		this.scrollLeft=scrollStartPosX-e.touches[0].pageX;
	},false);
}
function isTouchDevice(){
	try{
		document.createEvent("TouchEvent");
		return true;
	}catch(e){
		return false;
	}
}

function ajaxOptions(target,url)
{
	return {
		beforeSend: function(data){
			$('#BottomSpinner').css('visibility','visible');
		},
		success: function(data){
			ajaxSuccess(data,target,url);
		},
		error: function(x,st,err){
			alert("ajax get Status: "+st+" - Error: "+err+" - URL: "+url);
		},
		complete: function(){
			$('#BottomSpinner').css('visibility','hidden');

			hideHelp();
		}
	};
}

function ajaxLink(link){	
	//will work only if in the onclick there is no error!
	var target = link.target;
	if (link.href.indexOf('#')!=-1 || target=='_blank' || target=='_top') //internal/external/index.php anchor
		return true;
	if (!target)
	{
		if (link.href.indexOf('Modules.php')!=-1)
			target = 'body';
		else
			return true;
	}

	$.ajax(link.href, ajaxOptions(target,link.href));
	return false;
}

function ajaxPostForm(form,submit){
	var target = form.target;
	if (!target)
			target = 'body';
	if (form.action.indexOf('_ROSARIO_PDF')!=-1) //print PDF
	{
		form.target = '_blank';
		return true;
	}

	var options = ajaxOptions(target,form.action);
	if (submit)
		$(form).ajaxSubmit(options);
	else
		$(form).ajaxForm(options);
	return false;
}

function ajaxSuccess(data,target,url){
	//change URL after AJAX
	//http://stackoverflow.com/questions/5525890/how-to-change-url-after-an-ajax-request#5527095
	$('#'+target).html(data);
	var h3 = $('#body h3.title').text();
	document.title = $('#body h2').text()+(h3 ? ' | '+h3 : '');
	var body = $('body').html();
	
	if (history.pushState && target == 'body')
		history.pushState(null, document.title, url);
		
	ajaxPrepare('#'+target);
}

function ajaxPrepare(target){
	if (scrollTop=='Y')
		document.getElementById('body').scrollIntoView();
	$(target+' form').each(function(){ ajaxPostForm(this,false); });
	$(target+' a').click(function(e){ if(disableLinks){e.preventDefault(); return false;} return ajaxLink(this); });
	scroll();
	if (target == '#menu' && window.modname)
		openMenu(modname);
}

//disable links while AJAX
var disableLinks = false;
$(document).ajaxStart(function(){
	disableLinks = true;
	$('input[type="submit"],input[type="button"]').disabled;
});
$(document).ajaxStop(function(){
	disableLinks = false;
	$('input[type="submit"],input[type="button"]').enabled;
});

//onload
window.onload = function(){
	scroll();
	var h3 = $('#body h3.title').text();
	document.title = $('#body h2').text()+(h3 ? ' | '+h3 : '');
	$('a').click(function(e){ if(disableLinks){e.preventDefault(); return false;} return ajaxLink(this); });
	$('form').each(function(){ ajaxPostForm(this,false); });

	//reload page after browser history
	if (history.pushState)
		window.setTimeout(function() {
			window.addEventListener('popstate', function (e) {
				document.location.href = document.URL;
			}, false);
		}, 1);
};

function scroll(){
	if (isTouchDevice())
	{
		var els = document.getElementsByClassName('rt');
		Array.prototype.forEach.call(els, function(el) {
			touchScroll(el.tBodies[0]);
		});
	}
}
if (isTouchDevice())
	$(document).bind("cbox_complete", function(){ touchScroll(document.getElementById("cboxLoadedContent")); });


//Side.php JS
var old_modcat = false;
var menu_link = document.createElement("a"); menu_link.href = "Side.php"; menu_link.target = "menu";

function openMenu(modname)
{
	if (modname!='misc/Portal.php')
	{
		if (oldA = document.getElementById("selectedMenuLink"))
			oldA.id = "";
		$('.wp-submenu a[href$="'+modname+'"]:first').each(function(){this.id = "selectedMenuLink";});
		//add selectedModuleLink
		if (oldA = document.getElementById("selectedModuleLink"))
			oldA.id = "";

		var modcat;
		if (modname=='')
			modcat = old_modcat;
		else
			$('#selectedMenuLink').parents('div.wp-submenu').each(function(){ modcat = this.id.replace('menu_', ''); });

		$('a[href*="'+modcat+'"].menu-top').each(function(){this.id = "selectedModuleLink";});
		
		$("#menu_"+modcat).show();
		if(old_modcat!=false && old_modcat!=modcat)
			$("#menu_"+old_modcat).hide();
		old_modcat = modcat;
	}
	else if(old_modcat!=false)
		$("#menu_"+old_modcat).hide();
}

//Bottom.php JS
function toggleHelp(){
	if ($('#footerhelp').css('display') !== 'block')
		showHelp();
	else
		hideHelp();
}

var old_modname=false;
function showHelp(){
	if (modname!==old_modname)
	{
		$.get("Bottom.php?modfunc=help&modname="+modname, function(data){
			$('#footerhelp').html(data);
			if (isTouchDevice())
				touchScroll(document.getElementById('footerhelp'));
		})
		.fail(function(){
			alert('Error: expandHelp '+modname);
		})
		old_modname = modname;
	}
	$('#footerhelp').show();
	$('#footer').css('height', function(i, val){
		return parseInt(val) + parseInt($('#footerhelp').css('height'));
	});
}

function hideHelp(){
	$('#footerhelp').hide();
	$('#footer').css('height', '');
}

function expandMenu(){
	$('#menu,#menuback').toggle();
}
