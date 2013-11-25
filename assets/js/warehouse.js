//Modules.php JS
var locked;
function addHTML(html,id,replace){
	if(locked!=false){
		if(replace==true) document.getElementById(id).innerHTML = html;
		else document.getElementById(id).innerHTML = document.getElementById(id).innerHTML + html;
	}
}
function changeHTML(show,hide){
	for(key in show)
		document.getElementById(key).innerHTML = document.getElementById(show[key]).innerHTML;
	for(i=0;i<hide.length;i++)
		document.getElementById(hide[i]).innerHTML = '';
}
function checkAll(form,value,name_like){
	if(value==true) checked = true;
	else checked = false;

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
function setMLvalue(id,loc,value){
	res = document.getElementById(id).value.split('|');
	if(loc=='') {
		if (value == '') {
			alert('The first translation string cannot be empty.');
			value = 'Something';
		}
		res[0] = value;
	} else {
		found = 0;
		for (i=1;i<res.length;i++) {
			if (res[i].substring(0,loc.length) == loc) {
				found = 1;
				if (value == '') {
					for (j=i+1;j<res.length;j++)
						res[j-1] = res[j];
					res.pop();
				} else {
					res[i] = loc+':'+value;
				}
			}
		}    
		if ((found == 0) && (value != '')) res.push(loc+':'+value);
	}
	document.getElementById(id).value = res.join('|');                                
}

//tipmessage
var TipId="Migoicons";var FiltersEnabled = 0;
var tipmessageStyle = ["#21759b","#ececec","","","Georgia,Times New Roman",,"#555","#f9f9f9","","","sans-serif",,,,2,"#ececec",2,,,,,"",,,0,23];

//touchScroll, enables overflow:auto on mobile
//https://gist.github.com/chrismbarr/4107472
function touchScroll(el){
	var scrollStartPosY=0;
	var scrollStartPosX=0;

	el.addEventListener("touchstart", function(event) {
		scrollStartPosY=this.scrollTop+event.touches[0].pageY;
		scrollStartPosX=this.scrollLeft+event.touches[0].pageX;
	},false);

	el.addEventListener("touchmove", function(event) {
		if ((this.scrollTop < this.scrollHeight-this.offsetHeight &&
			this.scrollTop+event.touches[0].pageY < scrollStartPosY-5) ||
			(this.scrollTop != 0 && this.scrollTop+event.touches[0].pageY > scrollStartPosY+5))
				event.preventDefault(); 
		if ((this.scrollLeft < this.scrollWidth-this.offsetWidth &&
			this.scrollLeft+event.touches[0].pageX < scrollStartPosX-5) ||
			(this.scrollLeft != 0 && this.scrollLeft+event.touches[0].pageX > scrollStartPosX+5))
				event.preventDefault(); 
		this.scrollTop=scrollStartPosY-event.touches[0].pageY;
		this.scrollLeft=scrollStartPosX-event.touches[0].pageX;
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
	if (link.href.indexOf('#')==1 || target=='_blank' || target=='_top') //internal/external/index.php anchor
		return true;
	if (!target)
	{
		if (link.href.indexOf('Modules.php')!=-1)
			target = 'body';
		else
			return true;
	}
	
	$.get(link.href, function(data){
		ajaxSuccess(data,target);
	})
	.fail(function(){
		alert('Error: ajaxLink get '+link.href);
	})
	return false;
}
function ajaxPostForm(form,submit){
	var target = form.target;
	if (!target)
		//if (form.action.indexOf('Modules.php')!=-1)
			target = 'body';
	if (form.action.indexOf('_ROSARIO_PDF')!=-1) //print PDF
	{
		form.target = '_blank';
		return true;
	}
	
	var options = {
		beforeSubmit: function(a,f,o){
			//disable all submit buttons
			$('#'+target+' input[type="submit"]').disabled;
		},
		success: function(data){
			ajaxSuccess(data,target);
		}
	};
	if (submit)
		$(form).ajaxSubmit(options);
	else
		$(form).ajaxForm(options);
	return false;
}
function ajaxSuccess(data,target){
	$('#'+target).html(data);
	if (scrollTop=='Y')
		$('html, body').animate({scrollTop:$('#body').offset().top - 20});
	$('#'+target+' a').each(function(){ $(this).bind('click', function(){ return ajaxLink(this); }); });
	$('#'+target+' form').each(function(){ ajaxPostForm(this,false); });
}

//onload
window.onload = function(){
	if (typeof(mig_clay) == "function")
		mig_clay();
		
	if (isTouchDevice())
	{
		var els = document.getElementsByClassName('rt');
		Array.prototype.forEach.call(els, function(el) {
			touchScroll(el.tBodies[0]);
		});
		touchScroll(document.getElementById('footerhelp'));
	}
	$('a').each(function(){ $(this).bind('click', function(){ return ajaxLink(this); }); });
	$('form').each(function(){ ajaxPostForm(this,false); });
};

//Side.php JS
var old_modcat = false;
function openMenu(modname)
{
	modname=decodeURIComponent(modname);
	var modcat = modname.substr(0, modname.indexOf('/'));
	visible = document.getElementById("menu_"+modcat);
	visible.style.display = "block";
	if(old_modcat!=false && old_modcat!=modcat){
		oldVisible = document.getElementById("menu_"+old_modcat);
		oldVisible.style.display = "none";					
	}
	old_modcat = modcat;
	selMenuA(modname);
}
function selMenuA(modname)
{
	if (oldA = document.getElementById("selectedMenuLink"))
		oldA.id = "";
	 $('#menu a[href$="'+modname+'"]:first').each(function(){this.id = "selectedMenuLink";});
	document.getElementById("modname_input").value=encodeURIComponent(modname);
}

//Bottom.php JS
var modname='default', old_modname='';
function expandHelp(){
	var heightFooter = document.getElementById('footer').style.height;
	var displayHelp = 'block';
	var heightHelp = '140px';
	if(heightFooter == '170px')
	{
		heightFooter = '30px';
		displayHelp = 'none';
		heightHelp = '0px';
	}
	else
	{
		heightFooter = '170px';
		if (modname!=old_modname)
		{
			$.get("Bottom.php?modfunc=help&modname="+modname, function(data){
				$('#footerhelp').html(data);
			})
			.fail(function(){
				alert('Error: expandHelp ajax get '+modname);
			})
			old_modname = modname;
		}
	}
	document.getElementById('footerhelp').style.display = displayHelp;
	document.getElementById('footerhelp').style.height = heightHelp;
	document.getElementById('footer').style.height = heightFooter;
}
function expandMenu(){
	var heightMenu = document.getElementById('menu').style.height;
	var widthMenu = '205px';
	var return_val = true;
	if (heightMenu == '0px')
		heightMenu = '';
	else
	{
		heightMenu = '0px';
		widthMenu = '0px';
		return_val = false;
	}
	document.getElementById('menu').style.height = heightMenu;
	document.getElementById('menuback').style.height = heightMenu;
	document.getElementById('menu').style.width = widthMenu;
	document.getElementById('menuback').style.width = widthMenu;
	return return_val;
}