// ** I18N Afrikaans
Calendar._DN = new Array
("Sondag",
 "Maandag",
 "Dinsdag",
 "Woensdag",
 "Donderdag",
 "Vrydag",
 "Saterdag",
 "Sondag");

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
("Januarie",
 "Februarie",
 "Maart",
 "April",
 "Mei",
 "Junie",
 "Julie",
 "Augustus",
 "September",
 "Oktober",
 "November",
 "Desember");

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
Calendar._TT["INFO"] = "About the calendar";

Calendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2003\n" + // don't translate this this ;-)
"For latest version visit: http://dynarch.com/mishoo/calendar.epl\n" +
"Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
"\n\n" +
"Date selection:\n" +
"- Use the \xab, \xbb buttons to select year\n" +
"- Use the " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " buttons to select month\n" +
"- Hold mouse button on any of the above buttons for faster selection.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Time selection:\n" +
"- Click on any of the time parts to increase it\n" +
"- or Shift-click to decrease it\n" +
"- or click and drag for faster selection.";

Calendar._TT["TOGGLE"] = "Verander eerste dag van die week";
Calendar._TT["PREV_YEAR"] = "Vorige jaar (hou vir keuselys)";
Calendar._TT["PREV_MONTH"] = "Vorige maand (hou vir keuselys)";
Calendar._TT["GO_TODAY"] = "Gaan na vandag";
Calendar._TT["NEXT_MONTH"] = "Volgende maand (hou vir keuselys)";
Calendar._TT["NEXT_YEAR"] = "Volgende jaar (hou vir keuselys)";
Calendar._TT["SEL_DATE"] = "Kies datum";
Calendar._TT["DRAG_TO_MOVE"] = "Sleep om te skuif";
Calendar._TT["PART_TODAY"] = " (vandag)";
Calendar._TT["MON_FIRST"] = "Vertoon Maandag eerste";
Calendar._TT["SUN_FIRST"] = "Display Sunday first";
Calendar._TT["CLOSE"] = "Close";
Calendar._TT["TODAY"] = "Today";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Display %s first";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Close";
Calendar._TT["TODAY"] = "Today";
Calendar._TT["TIME_PART"] = "(Shift-)Click or drag to change value";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %B %e";

Calendar._TT["WK"] = "wk";
Calendar._TT["TIME"] = "Time:";