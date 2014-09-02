/*
 DHTML tip message version 1.5.4 copyright Essam Gamal 2003 
 Home Page: (http://migoicons.tripod.com)
 Email: (migoicons@hotmail.com)
 Updated on :7/30/2003
*/ 

var MI_IE=MI_IE4=MI_NN4=MI_ONN=MI_NN=MI_pSub=MI_sNav=0;mig_dNav()
var Style=[],Text=[],Count=0,move=0,fl=0,isOK=1,hs,e_d,tb,w=window,PX=(MI_pSub)?"px":""
var d_r=(MI_IE&&document.compatMode=="CSS1Compat")? "document.documentElement":"document.body"
var ww=w.innerWidth
var wh=w.innerHeight
var sbw=MI_ONN? 15:0

function mig_hand(){
if(MI_sNav){
w.onresize=mig_re
document.onmousemove=mig_mo
if(MI_NN4) document.captureEvents(Event.MOUSEMOVE)
}}		

function mig_dNav(){
var ua=navigator.userAgent.toLowerCase()
MI_pSub=navigator.productSub
MI_OPR=ua.indexOf("opera")>-1?parseInt(ua.substring(ua.indexOf("opera")+6,ua.length)):0
MI_IE=document.all&&!MI_OPR?parseFloat(ua.substring(ua.indexOf("msie")+5,ua.length)):0
MI_IE4=parseInt(MI_IE)==4
MI_NN4=navigator.appName.toLowerCase()=="netscape"&&!document.getElementById
MI_NN=MI_NN4||document.getElementById&&!document.all
MI_ONN=MI_NN4||MI_pSub<20020823
MI_sNav=MI_NN||MI_IE||MI_OPR>=7
}

function stm(t,s){
if(MI_sNav&&isOK){	
if(document.onmousemove!=mig_mo||w.onresize!=mig_re) mig_hand()
if(fl&&s[17]>-1&&s[18]>0)mig_layCss().visibility="hidden"
var ab="";var ap=""	
var titCol=s[0]?"COLOR='"+s[0]+"'":""
var titBgCol=s[1]&&!s[2]?"BGCOLOR='"+s[1]+"'":""
var titBgImg=s[2]?"BACKGROUND='"+s[2]+"'":""
var titTxtAli=s[3]?"ALIGN='"+s[3]+"'":""
var txtCol=s[6]?"COLOR='"+s[6]+"'":""
var txtBgCol=s[7]&&!s[8]?"BGCOLOR='"+s[7]+"'":""
var txtBgImg=s[8]?"BACKGROUND='"+s[8]+"'":""
var txtTxtAli=s[9]?"ALIGN='"+s[9]+"'":""
var tipHeight=s[13]? "HEIGHT='"+s[13]+"'":""
var brdCol=s[15]? "BGCOLOR='"+s[15]+"'":""
if(!s[4])s[4]="Verdana,Arial,Helvetica" 
if(!s[5])s[5]=1 
if(!s[10])s[10]="Verdana,Arial,Helvetica" 
if(!s[11])s[11]=1
if(!s[12])s[12]=200
if(!s[14])s[14]=0
if(!s[16])s[16]=0
if(!s[24])s[24]=10
if(!s[25])s[25]=10
hs=s[22]
if(hs==5)
setInterval("mig_mo2()",500)
if(MI_pSub==20001108){
if(s[14])ab="STYLE='border:"+s[14]+"px solid"+" "+s[15]+"'";
ap="STYLE='padding:"+s[16]+"px "+s[16]+"px "+s[16]+"px "+s[16]+"px'"}
var closeLink=hs==3||hs==5?"<TD ALIGN='right'><FONT SIZE='"+s[5]+"' FACE='"+s[4]+"'><A HREF='javascript:void(0)' ONCLICK='mig_hide(0)' STYLE='text-decoration:none;color:"+s[0]+"'><B>Close</B></A></FONT></TD>":""
var title=t[0]||hs==3||hs==5?"<TABLE WIDTH='100%' BORDER='0' CELLPADDING='0' CELLSPACING='0' "+titBgCol+" "+titBgImg+"><TR><TD "+titTxtAli+"><FONT SIZE='"+s[5]+"' FACE='"+s[4]+"' "+titCol+"><B>"+t[0]+"</B></FONT></TD>"+closeLink+"</TR></TABLE>":"";
var txt="<TABLE "+ab+" WIDTH='"+s[12]+"' BORDER='0' CELLSPACING='0' CELLPADDING='"+s[14]+"' "+brdCol+"><TR><TD>"+title+"<TABLE WIDTH='100%' "+tipHeight+" BORDER='0' CELLPADDING='"+s[16]+"' CELLSPACING='0' "+txtBgCol+" "+txtBgImg+"><TR><TD "+txtTxtAli+" "+ap+" VALIGN='top'><FONT SIZE='"+s[11]+"' FACE='"+s[10]+"' "+txtCol +">"+t[1]+"</FONT></TD></TR></TABLE></TD></TR></TABLE>"
mig_wlay(txt)
tb={trans:s[17],dur:s[18],opac:s[19],st:s[20],sc:s[21],pos:s[23],xpos:s[24],ypos:s[25]}
if(MI_IE4)mig_layCss().width=s[12]
e_d=mig_ed()
Count=0
move=1
}}

function mig_mo(e){
if(move){
var X=0,Y=0,s_d=mig_scd(),w_d=mig_wd()
var mx=MI_NN?e.pageX:MI_IE4?event.x:event.x+s_d[0]
var my=MI_NN?e.pageY:MI_IE4?event.y:event.y+s_d[1]
if(MI_IE4)e_d=mig_ed()
switch(tb.pos){
case 1:X=mx-e_d[0]-tb.xpos+6;Y=my+tb.ypos;break
case 2:X=mx-(e_d[0]/2);Y=my+tb.ypos;break
case 3:X=tb.xpos+s_d[0];Y=tb.ypos+s_d[1];break
case 4:X=tb.xpos;Y=tb.ypos;break
default:X=mx+tb.xpos;Y=my+tb.ypos}
if(w_d[0]+s_d[0]<e_d[0]+X+sbw)X=w_d[0]+s_d[0]-e_d[0]-sbw
if(w_d[1]+s_d[1]<e_d[1]+Y+sbw){if(tb.pos>2)Y=w_d[1]+s_d[1]-e_d[1]-sbw;else Y=my-e_d[1]}
if(X<s_d[0])X=s_d[0]
with(mig_layCss()){left=X+PX;top=Y+PX}
mig_dis()
}}

function mig_mo2(){
var X=0,Y=0,s_d=mig_scd(),w_d=mig_wd()
if(MI_IE4)e_d=mig_ed()
switch(tb.pos){
case 3:X=tb.xpos+s_d[0];Y=tb.ypos+s_d[1];break
case 4:X=tb.xpos;Y=tb.ypos;break
default:X=tb.xpos;Y=tb.ypos}
if(w_d[0]+s_d[0]<e_d[0]+X+sbw)X=w_d[0]+s_d[0]-e_d[0]-sbw
if(X<s_d[0])X=s_d[0]
with(mig_layCss()){left=X+PX;top=Y+PX}
mig_dis()
}

function mig_dis(){Count++
if(Count==1){
if(fl){	
if(tb.trans==51)tb.trans=parseInt(Math.random()*50)
var at=tb.trans>-1&&tb.trans<24&&tb.dur>0 
var af=tb.trans>23&&tb.trans<51&&tb.dur>0
var t=mig_lay().filters[af?tb.trans-23:0]
for(var p=28;p<31;p++){mig_lay().filters[p].enabled=0}
for(var s=0;s<28;s++){if(mig_lay().filters[s].status)mig_lay().filters[s].stop()}
for(var e=1;e<3;e++){if(tb.sc&&tb.st==e){with(mig_lay().filters[28+e]){enabled=1;color=tb.sc}}}
if(tb.opac>0&&tb.opac<100){with(mig_lay().filters[28]){enabled=1;opacity=tb.opac}}
if(at||af){if(at)mig_lay().filters[0].transition=tb.trans;t.duration=tb.dur;t.apply()}}
mig_layCss().visibility=MI_NN4?"show":"visible"
if(fl&&(at||af))t.play()
if(hs>0&&hs<4)move=0
}}

function mig_layCss(){return MI_NN4?mig_lay():mig_lay().style}
function mig_lay(){with(document)return MI_NN4?layers[TipId]:MI_IE4?all[TipId]:getElementById(TipId)}
function mig_wlay(txt){if(MI_NN4){with(mig_lay().document){open();write(txt);close()}}else mig_lay().innerHTML=txt}
function mig_hide(C){if(!MI_NN4||MI_NN4&&C)mig_wlay("");with(mig_layCss()){visibility=MI_NN4?"hide":"hidden";left=0;top=-800}}
function mig_scd(){return [parseInt(MI_IE?eval(d_r).scrollLeft:w.pageXOffset),parseInt(MI_IE?eval(d_r).scrollTop:w.pageYOffset)]}
function mig_re(){var w_d=mig_wd();if(MI_NN4&&(w_d[0]-ww||w_d[1]-wh))location.reload();else if(hs==3||hs==2) mig_hide(1)}
function mig_wd(){return [parseInt(MI_ONN?w.innerWidth:eval(d_r).clientWidth),parseInt(MI_ONN?w.innerHeight:eval(d_r).clientHeight)]}
function mig_ed(){return [parseInt(MI_NN4?mig_lay().clip.width:mig_lay().offsetWidth)+3,parseInt(MI_NN4?mig_lay().clip.height:mig_lay().offsetHeight)+5]}
function htm(){if(MI_sNav&&isOK){if(hs!=4){move=0;if(hs!=3&&hs!=2){mig_hide(1)}}}}

function mig_clay(){
if(!mig_lay()){isOK=0  
//alert("DHTML TIP MESSAGE VERSION 1.5 ERROR NOTICE.\n<DIV ID=\""+TipId+"\"></DIV> tag missing or its ID has been altered")
} 
else{mig_hand()}}