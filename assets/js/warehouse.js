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
	if(document.getElementById(id).style.display=='none'){
		document.getElementById(id).style.display = 'block';
		document.getElementById(id+'_arrow').src = 'assets/arrow_down.gif';
		document.getElementById(id+'_arrow').height = 9;
	}else{
		document.getElementById(id).style.display = 'none';
		document.getElementById(id+'_arrow').src = 'assets/arrow_right.gif';
		document.getElementById(id+'_arrow').height = 12;
	}
}

//IE8 HTML5 tags fix
var tags='article|aside|footer|header|hgroup|nav|section'.split('|'), i=0, max=tags.length;
for(;i<max;i++) {
    document.createElement(tags[i]);
}

//tipmessage
var TipId="Migoicons";
var tipmessageStyle = ["#21759b","#ececec","","","Georgia,Times New Roman",,"#555","#f9f9f9","","","sans-serif",,,,2,"#ececec",2,,,,,"",,,0,23];

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
	$.get(link.href, function(data){
		ajaxSuccess(data,target,link.href);
	})
	.fail(function(x,st,err){
		alert("ajaxLink get Status: "+st+" - Error: "+err);
    });
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
	var options = {
		success: function(data){
			ajaxSuccess(data,target,form.action);
		},
		error: function(x,st,err){
			alert("ajaxPostForm get Status: "+st+" - Error: "+err);
		}
	};
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
	
	if (typeof(history.pushState) == "function")
	{
		if (window.location.href == url)
			history.replaceState('', '', url);
		else if (target == 'body')
			history.pushState('', '', url);
	}
		
	ajaxPrepare('#'+target);
}

//change URL after AJAX
if (!(navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) && window.addEventListener)
	window.addEventListener('popstate', function (e) {
		document.location.href = document.URL;
	}, true);

function ajaxPrepare(target){
	$(target+' form').each(function(){ ajaxPostForm(this,false); });
	$(target+' a').click(function(e){ if(disableLinks){e.preventDefault(); return false;} return ajaxLink(this); });
	scroll();
	if (scrollTop=='Y')
		$('html, body').animate({scrollTop:$('#body').offset().top - 20});
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
	if (typeof(mig_clay) == "function")
		mig_clay();
	scroll();
	var h3 = $('#body h3.title').text();
	document.title = $('#body h2').text()+(h3 ? ' | '+h3 : '');
	$('#adminmenu a').click(function(e){
		modname=this.href.replace(this.href.substr(0,this.href.indexOf('Modules.php?modname=')+20), '');
		if ($(this).hasClass('menu-top'))
			openMenu(modname);
		else
			selMenuA(modname);
	});
	$('a').click(function(e){ if(disableLinks){e.preventDefault(); return false;} return ajaxLink(this); });
	$('form').each(function(){ ajaxPostForm(this,false); });
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
function openMenu(modname)
{
	if (modname!='misc/Portal.php')
	{
		var modcat = modname.substr(0, modname.indexOf('/'));
		if (!(visible = document.getElementById("menu_"+modcat)))
			visible = document.getElementById("menu_"+(modcat = old_modcat));
		visible.style.display = "block";
		if(old_modcat!=false && old_modcat!=modcat)
			$("#menu_"+old_modcat).hide();
		old_modcat = modcat;
		selMenuA(modname);
	}
	else if(old_modcat!=false)
		$("#menu_"+old_modcat).hide();
}
function selMenuA(modname)
{
	if (oldA = document.getElementById("selectedMenuLink"))
		oldA.id = "";
	$('.wp-submenu a[href$="'+modname+'"]:first').each(function(){this.id = "selectedMenuLink";});
	//add selectedModuleLink
	if (oldA = document.getElementById("selectedModuleLink"))
		oldA.id = "";
	var modcat = modname=='' ? old_modcat : modname.substr(0, modname.indexOf('/'));
	$('a[href*="'+modcat+'"].menu-top').each(function(){this.id = "selectedModuleLink";});
}

//Bottom.php JS
var old_modname='';
function expandHelp(){
var heightFooter = (document.getElementById('footer').style.height=='178px')?'38px':'178px';
	if (heightFooter=='178px')
	{
		if (modname!=old_modname)
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
	}
	else
		$('#footerhelp').hide();
	document.getElementById('footer').style.height = heightFooter;
}
function expandMenu(){
	$('#menu,#menuback').toggle();
}