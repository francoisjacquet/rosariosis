// ** I18N
Calendar._DN = new Array
("Zondag",
 "Maandag",
 "Dinsdag",
 "Woensdag",
 "Donderdag",
 "Vrijdag",
 "Zaterdag",
 "Zondag");
 
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
("Januari",
 "Februari",
 "Maart",
 "April",
 "Mei",
 "Juni",
 "Juli",
 "Augustus",
 "September",
 "Oktober",
 "November",
 "December");

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

Calendar._TT["TOGGLE"] = "Toggle startdag van de week";
Calendar._TT["PREV_YEAR"] = "Vorig jaar (indrukken voor menu)";
Calendar._TT["PREV_MONTH"] = "Vorige month (indrukken voor menu)";
Calendar._TT["GO_TODAY"] = "Naar Vandaag";
Calendar._TT["NEXT_MONTH"] = "Volgende Maand (indrukken voor menu)";
Calendar._TT["NEXT_YEAR"] = "Volgend jaar (indrukken voor menu)";
Calendar._TT["SEL_DATE"] = "Selecteer datum";
Calendar._TT["DRAG_TO_MOVE"] = "Sleep om te verplaatsen";
Calendar._TT["PART_TODAY"] = " (vandaag)";
Calendar._TT["MON_FIRST"] = "Toon Maandag eerst";
Calendar._TT["SUN_FIRST"] = "Toon Zondag eerst";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Display %s first";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Sluiten";
Calendar._TT["TODAY"] = "Vandaag";
Calendar._TT["TIME_PART"] = "(Shift-)Click or drag to change value";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "y-mm-dd";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %B %e";

Calendar._TT["WK"] = "wk";
Calendar._TT["TIME"] = "Time:";
