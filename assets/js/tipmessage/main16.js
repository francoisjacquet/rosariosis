/*
 DHTML tip message version 1.6 copyright Essam Gamal 2003 Francois Jacquet 2015
 Updated on: 2015.02.08
 Usage:
 <a href="#" onmouseover="stm([tiptitle,tipmsg])" onmouseout="htm()" onclick="return false;">Tip</a>
*/

var Style=[],Text=[],Count=0,move=0,isOK=1,e_d,tb,w=window,PX="px"
var d_r="document.body"
var ww=w.innerWidth
var wh=w.innerHeight
var sbw=0

function mig_hand(){
w.onresize=mig_re
document.onmousemove=mig_mo
}

function stm(t){
if(isOK){
if(document.onmousemove!=mig_mo||w.onresize!=mig_re) mig_hand()

var title=t[0]?"<THEAD><TR><TH>"+t[0]+"</TH></TR></THEAD>":"";
var txt="<TABLE class='widefat cellspacing-0'>"+title+"<TR><TD>"+t[1]+"</TD></TR></TABLE>";
mig_wlay(txt);
//offset
tb={xpos:10,ypos:10};

e_d=mig_ed()
Count=0
move=1
return false;
}}

function mig_mo(e){
if(move){
var X=0,Y=0,s_d=mig_scd(),w_d=mig_wd()
var mx=e.pageX
var my=e.pageY
X=mx+tb.xpos;Y=my+tb.ypos
if(w_d[0]+s_d[0]<e_d[0]+X+sbw)X=w_d[0]+s_d[0]-e_d[0]-sbw
if(w_d[1]+s_d[1]<e_d[1]+Y+sbw){Y=my-e_d[1]}
if(X<s_d[0])X=s_d[0]
with(mig_layCss()){left=X+PX;top=Y+PX}
mig_dis()
}}

function mig_dis(){Count++
if(Count==1) mig_layCss().visibility="visible"
}

function mig_layCss(){return mig_lay().style}
function mig_lay(){return document.getElementById(TipId)}
function mig_wlay(txt){mig_lay().innerHTML=txt}
function mig_hide(C){mig_wlay("");with(mig_layCss()){visibility="hidden";left=0;top=-800+PX}}
function mig_scd(){return [parseInt(w.pageXOffset),parseInt(w.pageYOffset)]}
function mig_re(){var w_d=mig_wd();}
function mig_wd(){return [parseInt(eval(d_r).clientWidth),parseInt(eval(d_r).clientHeight)]}
function mig_ed(){return [parseInt(mig_lay().offsetWidth)+3,parseInt(mig_lay().offsetHeight)+5]}
function htm(){if(isOK){move=0;mig_hide(1)}}

function mig_clay(){
if(!mig_lay())isOK=0
else mig_hand()}

//init
var TipId="tipMsg";
$(document).ready(function (){
$('<div id="'+TipId+'"></div>').appendTo('body');
mig_clay();
});
