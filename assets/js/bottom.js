function expandFrameHelp(){
	var sizeHelp = parent.document.getElementById('mainframeset').rows;
	var displayHelp = 'block';
	var heightHelp = '140px';
	if(sizeHelp.indexOf('30')!=-1)
		sizeHelp = "*,170";
	else
	{
		sizeHelp = "*,30";
		displayHelp = 'none';
		heightHelp = '0px';
	}
	document.getElementById('BottomHelp').style.display = displayHelp;
	document.getElementById('BottomHelp').style.height = heightHelp;
	parent.document.getElementById('mainframeset').rows = sizeHelp;
}
function expandFrameMenu(){
	var sizeMenu = parent.document.getElementById('mainframeset').firstElementChild.cols;
	var displayMenu = 'block';
	var widthMenu = '100%';
	if(sizeMenu.indexOf('205')!=-1)
	{
		sizeMenu = "0,*";
		displayMenu = 'none';
		widthMenu = '0%';
	}
	else
		sizeMenu = "205,*";
	parent.side.document.body.style.width = widthMenu;
	parent.side.document.body.style.display = displayMenu;
	parent.document.getElementById('mainframeset').firstElementChild.cols = sizeMenu;
}

//touchScroll, enables overflow:auto on mobile
//https://gist.github.com/chrismbarr/4107472
function touchScroll(id){
	if(isTouchDevice()){ //if touch events exist...
		var el=document.getElementById(id);
		var scrollStartPos=0;

		document.getElementById(id).addEventListener("touchstart", function(event) {
			scrollStartPos=this.scrollTop+event.touches[0].pageY;
		},false);

		document.getElementById(id).addEventListener("touchmove", function(event) {
			this.scrollTop=scrollStartPos-event.touches[0].pageY;
			event.preventDefault();
		},false);
	}
}
function isTouchDevice(){
	try{
		document.createEvent("TouchEvent");
		return true;
	}catch(e){
		return false;
	}
}
window.onload = function() { touchScroll('BottomHelp'); };