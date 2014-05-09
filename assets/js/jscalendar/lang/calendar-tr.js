//////////////////////////////////////////////////////////////////////////////////////////////
//	Turkish Translation by Nuri AKMAN
//	Location: Ankara/TURKEY
//	e-mail	: nuriakman@hotmail.com
//	Date	: April, 9 2003
//
//	Note: if Turkish Characters does not shown on you screen
//		  please include falowing line your html code:
//
//		  <meta http-equiv="Content-Type" content="text/html; charset=windows-1254">
//
//////////////////////////////////////////////////////////////////////////////////////////////

// ** I18N
Calendar._DN = new Array
("Pazar",
 "Pazartesi",
 "Salý",
 "Çarþamba",
 "Perþembe",
 "Cuma",
 "Cumartesi",
 "Pazar");
 
// short day names
Calendar._SDN = new Array
("Sun",
 "Mon",
 "Tue",
 "Wed",
 "Thu",
 "Fri",
 "Sat",
 "Sun");

Calendar._MN = new Array
("Ocak",
 "Þubat",
 "Mart",
 "Nisan",
 "Mayýs",
 "Haziran",
 "Temmuz",
 "Aðustos",
 "Eylül",
 "Ekim",
 "Kasým",
 "Aralýk");

// short month names
Calendar._SMN = new Array
("Jan",
 "Feb",
 "Mar",
 "Apr",
 "May",
 "Jun",
 "Jul",
 "Aug",
 "Sep",
 "Oct",
 "Nov",
 "Dec");

// tooltips
Calendar._TT = {};
Calendar._TT["TOGGLE"] = "Haftanýn ilk gününü kaydýr";
Calendar._TT["PREV_YEAR"] = "Önceki Yýl (Menü için basýlý tutunuz)";
Calendar._TT["PREV_MONTH"] = "Önceki Ay (Menü için basýlý tutunuz)";
Calendar._TT["GO_TODAY"] = "Bugün'e git";
Calendar._TT["NEXT_MONTH"] = "Sonraki Ay (Menü için basýlý tutunuz)";
Calendar._TT["NEXT_YEAR"] = "Sonraki Yýl (Menü için basýlý tutunuz)";
Calendar._TT["SEL_DATE"] = "Tarih seçiniz";
Calendar._TT["DRAG_TO_MOVE"] = "Taþýmak için sürükleyiniz";
Calendar._TT["PART_TODAY"] = " (bugün)";
Calendar._TT["MON_FIRST"] = "Takvim Pazartesi gününden baþlasýn";
Calendar._TT["SUN_FIRST"] = "Takvim Pazar gününden baþlasýn";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Display %s first";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Kapat";
Calendar._TT["TODAY"] = "Bugün";
Calendar._TT["TIME_PART"] = "(Shift-)Click or drag to change value";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "dd-mm-y";
Calendar._TT["TT_DATE_FORMAT"] = "%A, %B %e";

Calendar._TT["WK"] = "Hafta";
Calendar._TT["TIME"] = "Time:";
