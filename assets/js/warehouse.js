//file included in Warehouse.php
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
window.onload = function() { mig_clay(); };