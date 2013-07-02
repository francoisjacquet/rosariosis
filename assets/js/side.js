//file included in Side.php
var old_modcat = false;
function openMenu(modcat)
{
	visible = document.getElementById("menu_visible"+modcat);
	visible.innerHTML = document.getElementById("menu_hidden"+modcat).innerHTML;
	visible.style.display = "block";
	if(old_modcat!=false && old_modcat!=modcat){
		oldVisible = document.getElementById("menu_visible"+old_modcat);
		oldVisible.innerHTML = "";
		oldVisible.style.display = "none";					
	}
	document.getElementById("modcat_input").value=modcat;
	old_modcat = modcat;
}
function selectedMenuLink(a)
{
	if (oldA = document.getElementById("selectedMenuLink"))
		oldA.id = "";
	a.id = "selectedMenuLink";
}