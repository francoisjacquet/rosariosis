# CHANGES
## RosarioSIS Student Information System

Changes in 7.7
--------------
- Move Dashboard() call outside in Dashboard.fnc.php & Portal.php
- Add .xlsm,.key,.midi,.aif,.mpeg,.h264,.mkv,.log,.email,.eml,.emlx,.msg,.vcf extensions to white list in FileUpload.fnc.php
- Add "Last Name Middle Name First Name" option to Display Name in GetStuList.fnc.php & Configuration.php
- Fix SQL error escape course title in StudentGrades.php
- SQL Remove Salaries having a Payment (same Amount & Comments (Title), after or on Assigned Date) in Accounting/functions.inc.php
- SQL match Payment Comments LIKE Fee Title in Student_Billing/functions.inc.php
- CSS fix list line-height in FlatSIS/stylesheet_wkhtmltopdf.css

Changes in 7.6.1
----------------
- Fix #307 XSS update CodeIgniter Security class in classes/Security.php, thanks to @DustinBorn
- Move Portal Poll vote code to modfunc in PortalPollNotes.php & Portal.php
- Fix #308 Unauthenticated SQL injection. Use sanitized `$_REQUEST` in Portal.php, thanks to @DustinBorn
- Fix #308 sanitize key. Pass array keys through function in Warehouse.php, thanks to @DustinBorn
- Fix #309 unset `$_SESSION` so user cannot maintain dummy session in PasswordReset.php, thanks to @DustinBorn
- Remove use of `$_SESSION['STAFF_ID'] === '-1'` in User.fnc.php & PasswordReset.php

Changes in 7.6
--------------
- Fix login password with single quote, use POST in index.php & Preferences.php
- HTML Use #! instead of JS return false to not go back to top in Buttons.php & Profiles.php
- JS remove warehouse.min.js & include warehouse.js inside plugins.min.js in Gruntfile.js, assets/js/ & Warehouse.php
- Fix PHP8 compatibility issues (warnings & fatal errors), system wide
- Fix save new Grade with "0" as Title in ReportCardGrades.php
- PHP8 no xmlrpc ext: load xmlrpc compat functions in plugins/Moodle/client.php, xmlrpc.php, XML_RPC.php, XmlrpcDecoder.php & XmlrpcEncoder.php
- Fix xmlrpc nested arrays, use param & value elements instead in XmlrpcEncoder.php
- Fix SQL Total points only select assignments for CP teacher (teacher may have changed) in Grades.php, InputFinalGrades.php, StudentGrades.php & GradebookBreakdown.php
- Fix SQL Grades sort order in GradebookBreakdown.php
- Add Login form link action hook in index.php & Actions.php
- SQL fix Report Card Grades insert in rosariosis_fr.sql
- SQL fix ORDER Report Cards by Student name & Course list by Title in ReportCards.fnc.php
- SQL fix error invalid input syntax for integer in DailySummary.php
- Replace tested on Ubuntu 16.04 with 18.04 (Buster) in INSTALL.md, INSTALL_es.md & INSTALL_fr.md

Changes in 7.5
--------------
- HTML fix Student Assignment Submission display in StudentAssignments.fnc.php
- Percent rounding to 2 decimal places is new School default in \_makeLetterGrade.fnc.php
- CSS Fix widefat table border color when rendered in PDF inside Chrome in colors.css
- Add phpwkhtmltopdf class & remove Wkhtmltopdf class in classes/
- Use phpwkhtmltopdf class instead of Wkhtmltopdf (more reliable & faster) in PDF.php
- Add Report Cards PDF footer action hook in ReportCards.fnc.php & Actions.php
- Transcripts PDF header action hook: echo your custom text before or append it to $header_html to display it after in Transcripts.fnc.php
- Transcripts PDF footer action hook: echo your custom text before or append it to $footer_html to display it after in Transcripts.fnc.php
- Add .transcript-certificate-block1 & .transcript-certificate-block2 CSS classes in Transcripts.fnc.php
- Add .report-card-free-text CSS class in ReportCards.fnc.php
- Delete any attendance for this day & student prior to update in FixDailyAttendance.php
- Use \_makeLetterGrade() for Percent grade so it reflects Teacher's Score rounding configuration in Grades.php & ProgressReports.php
- Fix Add Credits only for Report Cards in ReportCards.fnc.php
- Fix SQL error invalid input syntax for integer (Class Rank input) in Widgets.fnc.php
- HTML Grades GPA Widget: use number input & check Weighted by default in Widgets.fnc.php

Changes in 7.4
--------------
- List sort comment: trim & fix position in ListOutput.fnc.php
- Fix #303 Raw value in comment so we can sort Percent column the right way in Grades.php, thanks to @dd02
- Add Database Backup link to header in Rollover.php
- Add Course Widget configuration option: Popup window or Pull-Down in Configuration.php & Help_en.php
- Add Course Widget: select / Pull-Down in Widgets.fnc.php
- Update French & Spanish translations in rosariosis.po, help.po
- Add Total Credits in ReportCards.fnc.php
- Do not display "General Comments" title if no comments in ReportCards.fnc.php
- HTML display rows of 3 School Period checkboxes in AddAbsences.php
- Comment input maxlength increased to 500 in InputFinalGrades.php
- Comment Code input field is required in ReportCardCommentCodes.php
- Add php-zip extension to list in INSTALL.md
- Check for zip extension in diagnostic.php
- Fix SQL error integer out of range in Food_Service/Students/Accounts.php
- French translation: replace "Effacer" with "Supprimer" in rosariosis.po & help.po
- Fix Teacher Programs Progress Report PDF, do not echo form in TeacherPrograms.php

Changes in 7.3.1
----------------
- Fix admin override: no input div when values are not saved yet in Grades/Configuration.php
- Fix #304 Do not include Excused (`*` or -1) grades in GradebookBreakdown.php, thanks to @dd02
- Fix #304 regression since 5.0 Count students in GradebookBreakdown.php, thanks to @dd02
- Fix #304 Totals count exclude Extra Credit assignments when Total Points is 0 for the Type, thanks to @dd02

Changes in 7.3
--------------
- SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL in Letters.php, StudentLabels.fnc.php, HonorRoll.fnc.php & Reminders.php
- SQL remove unused SELECT ROOM in HonorRoll.fnc.php
- Translate database on add-on install: run 'install_fr.sql' file in Modules.inc.php, Plugins.inc.php, modules/README.md & plugins/README.md
- CSS remove wildcard rules in stylesheet.css & wkhtmltopdf.css
- CSS remove browser input outline on focus in colors.css
- Fix Format Phone Number for US in GetStuList.fnc.php, thanks to @dzungdo
- Attendance dashboard limit Absences to past days in Dashboard.inc.php
- Fix #299 Remove trailing slash "/" or dash "-" or dot "." from date in DailySummary.php
- Fix #300 Include Full Day and Half Day school periods in the schedule table in PrintSchedules.php, thanks to @dzungdo
- Update translations complete % in locale/REFERENCE.md
- Add tested on CentOS & Google Chrome in INSTALL.md & INSTALL.pdf, thanks to @dd02
- Add Before First Login form action hook in index.php & Actions.php
- Fix regression since 7.0 not rolled items are checked in Rollover.php

Changes in 7.2.4
----------------
- Take in Account Calendar Day Minutes in UpdateAttendanceDaily.fnc.php
- Fix regression since 5.3 Return false if School Periods Length sum is 0 in UpdateAttendanceDaily.fnc.php, thanks to @dzungdo

Changes in 7.2.3
----------------
- Fix regression since 5.9 search text User Field in Search.fnc.php, thanks to @dzungdo

Changes in 7.2.2
----------------
- Fix SQL error foreign keys: Roll Schools before rolling Student Enrollment in Rollover.php
- Fix SQL error table ADDRESS specified more than once in GetStuList.fnc.php

Changes in 7.2.1
----------------
- Fix ParseMLField for Username field category in Preferences.php
- Fix PHP Warning check requested locale exists in Warehouse.php
- Add Attendance Codes help for (Lunch) Categories in Help_en.php & help.po
- Fix SQL error multiple rows returned by a subquery in CreateParents.php

Changes in 7.2
--------------
- Add Grade Level breakdown in StudentFieldBreakdown.php
- Add link to Student Info in AddDrop.php
- Limit students to User schools in AddDrop.php
- Order Day, Month & Year inputs depending on User date preference in Date.php
- SQL fix only display enrolled students in AddStudents.php
- Link to Student Info redirects to right school in AddStudents.php
- Reset password variable for each Contact in CreateParents.php

Changes in 7.1.4
----------------
- Fix infinite loop when username already exists in CreateParents.php

Changes in 7.1.3
----------------
- Fix #297 regression since 6.9 & SQL error in StudentSummary.php

Changes in 7.1.2
----------------
- Fix SQL error Include Inactive Students for admin in PrintClassLists.php

Changes in 7.1.1
----------------
- Fix #296 Include Inactive Students for admin in PrintClassLists.php

Changes in 7.1
--------------
- Final Grading Percentages: add "No quarters found" error in Configuration.php
- Add Start Date input in Scheduler.php
- Export (Excel) date to YYYY-MM-DD format (ISO) in Date.php & Preferences.php
- Select Date Format: Add Preferences( 'DATE' ) in User.fnc.php, Preferences.php, Date.php & Side.php
- Fix SQL error TITLE column limit to 50 characters in GradeLevels.php
- HTML remove radio buttons (File Attached or Embed Link) in PortalNotes.php & PortalPollsNotes.fnc.php
- Add Grade Level breakdown in StudentBreakdown.php
- Include Credits in ReportCards.fnc.php

Changes in 7.0.4
----------------
- Fix #295 regression since 7.0 cannot save N/A date in Date.php

Changes in 7.0.3
----------------
- Fix Multiple School Periods: Course Period School Period does not match, skip in Scheduler.php

Changes in 7.0.2
----------------
- JS Fix search form onsubmit in Export.php

Changes in 7.0.1
----------------
- Fix #292 System error "blocked access to local file" with wkhtmltopdf 0.12.6 in Wkhtmltopdf.php

Changes in 7.0
--------------
- Update Markdownify from v2.1.11 to v2.3.1 in classes/Markdownify/*
- Update Parsedown from v1.6.0 to v1.7.4 in classes/Parsedown.php
- Update MoTranslator from v3.4 to v4.0 in Warehouse.php, Help.fnc.php & classes/MoTranslator/*
- Fix 'School' translation when using MoTranslator in Schedule.inc.php & rosariosis.po
- Fix '%s Handbook' translation when using MoTranslator in Help.php
- CSS fix align "+" New Event icon to bottom in Calendar.php, CalendarDay.inc.php, stylesheet.css & zreponsive.css
- Fix Day Number when multiple calendars and school years in CalendarDay.inc.php, DayToNumber.inc.php
- Fix Numbered days display in SchoolPeriodsSelectInput.fnc.php & Courses.fnc.php
- SQL improve Numbered days in AddAbsences.php, Administration.php, DailySummary.php, TakeAttendance.php, TeacherCompletion.php, UpdateAttendanceDaily.fnc.php & Portal.php
- Place Rollover under Utilities separator in Menu.php
- Merge Schedule Report & Master Schedule Report in Menu.php, MasterScheduleReport.php, ScheduleReport.php & rosariosis.sql
- Add Students column to report in RequestsReport.php
- Merge Requests Report & Unfilled Requests in Menu.php, RequestsReport.php, UnfilledRequests.php, Scheduler.php, Help_en.php & rosariosis.sql
- Merge Average Daily Attendance & Average Attendance by Day in Menu.php, Percent.php, Help_en.php, help.po & rosariosis.sql
- Remove "Happy []..." text in Portal.php
- HTML remove "Demographics" header to gain space on PDF in AttendanceSummary.php
- SQL Update ATTENDANCE_CODE (admin) when is NULL in TakeAttendance.php
- CSS Add .widefat.files class in StudentsUsersInfo.fnc.php & stylesheet.css
- CSS WPadmin more padding for list rows, menu links & footer help in stylesheet.css
- CSS FlatSIS less padding for list row, header & popTable in stylesheet.css
- CSS FlatSIS reduce body line-height & fix Dashboard tipmsg border in stylesheet.css
- Format "Show Available Seats" & "Print Schedule" headers in Schedule.php
- Remove $fy_id global variable in Schedule.php
- HTML Add tooltips & notes in Rollover.php
- Fix current CP Marking Period check on update in Courses.php
- Fix limit list results to 1000, do not remove 1st result in ListOutput.fnc.php
- Add $RosarioErrorsAddress config variable in config.inc.sample.php
- Fix $RosarioNotifyAddress config variable description in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- SQL no access to Custom "My Report" program for admin by default in rosariosis.sql
- JS MarkdownToHTML No MarkDown in text, return raw text in warehouse.js
- Fix Delete from other Student/User Info tabs in Student.php & User.php
- Remove deprecated since 4.5 rollover_* action hooks in Rollover.php & Actions.php
- Fix Error: There is no column for The value for 0. This value was not saved in SaveData.fnc.php
- Fix Do not Save / Export Medical tab lists in Medical.inc.php


### Old versions CHANGES
- [CHANGES for versions 5 and 6](CHANGES_V5_6.md).
- [CHANGES for versions 3 and 4](CHANGES_V3_4.md).
- [CHANGES for versions 1 and 2](CHANGES_V1_2.md).
