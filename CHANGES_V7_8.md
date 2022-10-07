# CHANGES for versions 7 and 8
## RosarioSIS Student Information System

Changes in 8.9.6
----------------
- Fix Stored XSS security issue: escape textarea HTML in Inputs.php, thanks to @jo125ker

Changes in 8.9.5
----------------
- Fix stored XSS security issue: do not allow unsanitized XML & HTML in FileUpload.fnc.php, thanks to @nhienit2010
- Fix stored XSS security issue: escape HTML attribute in StudentAssignments.fnc.php, thanks to @dungtuanha
- Use big random number for parent password generation in NotifyParents.php & createParents.php, thanks to @intrapus
- Add microseconds to filename format to make it harder to predict in StudentAssignments.fnc.php, thanks to @dungtuanha

Changes in 8.9.4
----------------
- Fix SQL injection sanitize all `$_REQUEST` keys in Warehouse.php, thanks to @nhienit2010
- Fix reflected XSS via mime-type in FileUpload.fnc.php, thanks to @nhienit2010

Changes in 8.9.3
----------------
- Fix stored XSS security issue: do not allow unsanitized SVG in FileUpload.fnc.php, thanks to @scgajge12 & @crowdoverflow

Changes in 8.9.2
----------------
- Fix invalidate User School in session on login in index.php

Changes in 8.9.1
----------------
- Fix regression since 8.6 Mailing Labels widget HTML in Widgets.php

Changes in 8.9
--------------
- Fix GetTeacher() when newly inserted teacher in GetTeacher.fnc.php
- Remove Half Day option in AddAbsences.php, Administration.php, TakeAttendance.php, Courses.php, Courses.fnc.php & Rollover.php
- JS Hide "+ New Period" link onclick in Courses.php
- CSS FlatSIS fix bottom button line height in stylesheet.css
- Add help texts & translations for the Scheduling > Courses program in Help_en.php & help.po
- Correct typos in Spanish help texts in help.po
- Fix Locked column value on list export in Schedule.php
- Student / User Photo input: only accept .jpg,.jpeg,.png,.gif in General_Info.inc.php
- Increase Food Service icon width to 48px in FS_Icons.inc.php & MenuItems.php
- HTML add Add-on upload input title in Modules.inc.php & Plugins.inc.php
- Fix do not resubmit form on List Export in Incomes.php & Expenses.php
- Fix List Export columns: hide Delete & show File Attached in Expenses.php, Incomes.php, Salaries.php, StaffPayments.php, StudentFees.php & StudentPayments.php
- Check AllowEdit() on Event deletion in Calendar.php
- Food Service icon upload in MenuItems.php
- Add French & Spanish translation for "Icon successfully uploaded." in rosariosis.po

Changes in 8.8
--------------
- Fix proc_open() PHP function not allowed in PDF.php
- Fix PHP Warning A non-numeric value encountered in ReportCards.fnc.php
- Fix PHP Fatal error Unsupported operand types in Teacher Programs: do not search Students List, unset in CustomFields.fnc.php
- Add 'staff_' prefix to first & last inputs on Find a User form in GetStaffList.fnc.php & Search.fnc.php
- Remove icons from Ungraded column, use only number in StudentGrades.php
- Exclude 0 points assignments from Ungraded count in StudentGrades.php
- Date select increase years options from +5 to +20 in Date.php
- JS Raise height by 1 submenu item so we stay above browser URL in warehouse.js
- Add Min. and Max. GPA to Last row in Grades.fnc.php & ReportCards.fnc.php
- Add Class Rank to Last row in Grades.fnc.php & ReportCards.fnc.php

Changes in 8.7
--------------
- Rector fix bad code in functions/, classes/core/, ProgramFunctions/
- Update tested on: not compatible with Internet Explorer in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Add ProgramFunctions/SendEmail.fnc.php|send_error action hook in SendEmail.fnc.php
- EasyCodingStandard use short array notation in functions/, classes/core/, ProgramFunctions/, modules/ & plugins/
- Fix month + year format, remove day (regression since 7.1) in Dashboard.inc.php
- ProgramUserConfig() always return array, not null in Config.fnc.php & \_makeLetterGrade.fnc.php
- Allow redirect to Take Attendance, no fatal error if no current MP in Portal.php
- CSS fix checkbox & radio input vertical align on Firefox in stylesheet.css
- CSS fix menu hover right arrow position when module name on 2 lines in stylesheet.css
- CSS fix font-size auto-adjust on iPhone in stylesheet.css
- Fix typo in English string, update translations in Rollover.php & rosariosis.mo
- JS fix menu & scroll issue on smartphone landscape > 735px in warehouse.js & jquery-fixed.menu.js
- FlatSIS theme: use Grunt to minify in Gruntfile.js
- FlatSIS theme: do not import WPadmin theme stylesheet anymore in stylesheet.css, stylesheet_wkhtmltopdf.css
- Fix SQL transcript_grades view, grades were duplicated for each school year in rosariosis.sql & Update.fnc.php

Changes in 8.6.1
----------------
- Add .webp image to FileExtensionWhiteList() in FileUpload.fnc.php
- Fix SQL error table name "sam" specified more than once in Widget.php

Changes in 8.6
--------------
- Add (Student) Widgets class in classes/core/Widgets.php
- Add (Student) Widget interface and individual Widget classes in classes/core/Widget.php
- Use RosarioSIS\Widgets in Widgets.fnc.php
- Add StaffWidgets class in classes/core/StaffWidgets.php
- Add StaffWidget interface and individual StaffWidget classes in classes/core/StaffWidget.php
- Use RosarioSIS\StaffWidgets in StaffWidgets.fnc.php
- Admin Student Payments Delete restriction: also exclude Refund in StudentPayments.php & Student_Billing/functions.inc.php
- Fix PHP Fatal error unsupported operand types when (Staff)Widgets() & $extra used for Parent / Student in Widgets.fnc.php & StaffWidgets.fnc.php
- Fix PHP Fatal error canBuild() must be compatible with Widget::canBuild(array $modules) in Widget.php & StaffWidget.php
- Fix SQL error more than one row returned by a subquery in Search.fnc.php

Changes in 8.5.2
----------------
- Fix PHP Fatal error cannot redeclare `_rosarioLoginURL()` (regression since 8.3) in MarkDownHTML.fnc.php

Changes in 8.5.1
----------------
- Fix SQL syntax error in ORDER BY (regression since 8.3.1) in Substitutions.php

Changes in 8.5
--------------
- Fix SQL error duplicate key value violates unique constraint "food_service_menus_title" in Menus.php
- SQL add PRIMARY KEY to staff_exceptions table in rosariosis.sql
- SQL profile_exceptions & staff_exceptions tables: Add Admin Student Payments Delete restriction in Update.fnc.php & rosariosis.sql
- Add Admin Student Payments Delete restriction in Profiles.php & Exceptions.php
- Add Admin Student Payments Delete restriction in StudentPayments.php & Student_Billing/functions.inc.php
- Fix SQL error numeric field overflow when entering percent > 100 in MassCreateAssignments.php
- HTML Sort Order input type number in MarkingPeriods.php

Changes in 8.4
--------------
- SQL gradebook_grades table: Change comment column type to text in Update.fnc.php & rosariosis.sql
- Increase Grades Comment input maxlength to 500 chars in Grades.php
- Fix use more coherent number_format() precision & no thousand separator in Percent.php, Assignments.php, StudentGrades.php, Grades.fnc.php & EditReportCardGrades.php
- SQL order fields list by Category & SORT_ORDER in AssignOtherInfo.php
- Fix SQL error numeric field overflow when entering percent > 100 in Assignments.php
- Comments length > 60 chars, responsive table ColorBox in EditReportCardGrades.php, FinalGrades.php, Grades.php, InputFinalGrades.php & StudentGrades.php
- Add File Attached to Incomes in Incomes.php & Accounting/functions.inc.php
- Add File Attached to Expenses in Expenses.php
- SQL accounting_incomes table: Add FILE_ATTACHED column in Update.fnc.php & rosariosis.sql
- Fix SQL error when no user in session in Template.fnc.php
- Correct help text note for User deletion in Help_en.php & help.po

Changes in 8.3.1
----------------
- Fix SQL exclude fields of 'files' type in Substitutions.php
- SQL order fields list by Category & SORT_ORDER in Substitutions.php
- Fix force numeric separator "." when no en_US locale, use C locale in Warehouse.php
- Fix Advanced Search > General Info text fields when adding Username in Search.fnc.php

Changes in 8.3
--------------
- Fix PHP Warning non-numeric value encountered, use rounded percent grade in StudentGrades.php
- Security Fix reflected XSS: encode HTML special chars for search_term in Courses.php
- Add File Attached to Staff Payments in StaffPayments.php & Accounting/functions.inc.php
- Add File Attached to Payments in StudentPayments.php & Student_Billing/functions.inc.php
- SQL accounting_payments table: Add FILE_ATTACHED column in Update.fnc.php & rosariosis.sql
- SQL billing_payments table: Add FILE_ATTACHED column in Update.fnc.php & rosariosis.sql
- Add help note for student deletion & translate in Help_en.php & help.po
- Add RosarioSIS URL to image path in MarkDownHTML.fnc.php
- Fix SQL error invalid byte sequence for encoding "UTF8": 0xde 0x20 in Security.php

Changes in 8.2.1
----------------
- Fix SQL for Warning when only 0 points assignments in Assigments.php

Changes in 8.2
--------------
- Fix replace regex: remove slash & allow space in FileUpload.fnc.php
- Always Use Last Year's Picture if Missing in PrintClassLists.php
- Fix #329 SQL error division by zero in t_update_mp_stats(): set min Credits to 1 in Courses.fnc.php
- Fix SQL error when Teacher name has single quote in Courses.php
- CSS FlatSIS remove useless line-height for tabs in stylesheet.css

Changes in 8.1.1
----------------
- Fix security issue #328 unauthenticated access to Side.php in Warehouse.php, thanks to @ijdpuzon
- Fix security issue #328 sanitize `$_POST` school, syear, mp & period parameters in Side.php

Changes in 8.1
--------------
- Remove @ error control operator on pg_exec: allow PHP Warning in database.inc.php
- Fix Address Field sequence name in AddressFields.php
- Remove deprecated DBSeqConvertSerialName() function in database.inc.php
- Fix Conflict Warning displayed twice in Courses.php
- Fix PHP Notice Undefined index in miscExport.fnc.php
- Fix SQL error when Student / Staff ID is hacked / not an integer in URL in Current.php
- SQL accounting_salaries table: Add FILE_ATTACHED column in Update.fnc.php & rosariosis.sql
- Add File Attached to Salaries in Salaries.php & Accounting/functions.inc.php
- SQL billing_fees table: Add FILE_ATTACHED column in Update.fnc.php & rosariosis.sql
- Add File Attached to Fees in StudentFees.php & Student_Billing/functions.inc.php
- Fix Student Widgets for Advanced Search exports in GetStaffList.fnc.php, GetStuList.fnc.php & Search.inc.php
- Add Export fields list (form) & Export fields list + extra SQL (student list) action hooks in Export.php & Actions.php
- Do not remove Full Day and Half Day school periods from the Schedule table in PrintSchedules.php
- Fix 403 Forbidden error due to pipe "|" in URL when using Apache 5G rules in Widgets.fnc.php

Changes in 8.0.4
----------------
- Fix default Student/Parent program in Attendance/Menu.php

Changes in 8.0.3
----------------
- Fix #324 Show Student Photo in Transcripts.fnc.php

Changes in 8.0.2
----------------
- Fix User Widgets Search Terms in Users/Search.inc.php

Changes in 8.0.1
----------------
- Fix #322 PHP syntax error, unexpected ')' in DailySummary.php

Changes in 8.0
--------------
- Add Total from Payments & Total from Fees fields to Advanced Report in Export.php
- Upgrade grunt, grunt-contrib-cssmin, grunt-contrib-uglify & grunt-contrib-watch & remove grunt-phpdoc in package.json & Gruntfile.js
- CSS minification optimizations in stylesheet.css & stylesheet_wkhtmltopdf.css
- JS uglify optimizations in plugins.min.js & plugins.min.js.map
- Upgrade Chart.js from 2.9.3 to 3.4.1 & save 40KB in chart.min.js & Charts.fnc.php
- Fix "The gradebook configuration has been modified." note appearing twice in Grades/Configuration.php
- Add warning in case all Assignments in Type have 0 Points (Extra Credit) in Assignments.php
- Update French & Spanish translations in rosariosis.po
- CSS FlatSIS shorten menu width & submenu links height + better contrast in stylesheet.css
- CSS FlatSIS list square bullets in stylesheet.css
- Default theme is now FlatSIS in rosariosis.sql
- CSS remove .radio-attendance-code class in stylesheet.css, rtl.css & TakeAttendance.php
- CSS remove Open Sans SVG fonts, format is deprecated in font.css & WPadmin/fonts/open
- Upgrade marked.js 0.8.2 to version 1.2.9 in assets/js/marked/
- Fix SQL error when $staff_id is 0 (no user in session) in Config.fnc.php
- Remove Waived Fees from list in Student_Billing/functions.inc.php
- New ROSARIO_DISABLE_ADDON_DELETE optional config constant in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Add-on disable delete in Modules.inc.php & Plugins.inc.php
- Merge Daily Transactions & Daily Totals programs in DailyTransactions.php & DailyTotals.php
- Remove Daily Totals program from Student Billing & Accounting menus in Menu.php & rosariosis.sql
- Fix Totals calculation in Accounting/includes/DailyTotals.php
- Multibyte strings: check if not UTF-8 first to avoid cost of setting in Warehouse.php
- Fix false positive Hacking Attempt on Print button click when no user in session in Warehouse.php
- Merge Attendance Chart & Absence Summary programs in DailySummary.php & StudentSummary.php & Help_en.php
- Remove Absence Summary program from Attendance menu in Menu.php, Help_en.php & rosariosis.sql

Changes in 7.9.3
----------------
- Fix #318 PHP warning non-numeric value encountered for $LO_dir in ListOutput.fnc.php, thanks to @AhmadKakarr

Changes in 7.9.2
----------------
- Fix SQL error when single quote in Course Title in InputFinalGrades.php
- Fix include Semester course periods in the Schedule table in Schedule.inc.php
- Fix #316 CSRF security issue set cookie samesite to strict, thanks to @huntrdev

Changes in 7.9.1
----------------
- Fix remove file when has single quote in its name and actually delete file in Student.php, User.php & Schools.php
- Fix download backup filename when contains spaces: use double quotes in DatabaseBackup.php

Changes in 7.9
--------------
- Update default School Year to 2021 in rosariosis.sql & config.inc.sample.php

Changes in 7.8.4
----------------
- Fix User Marking Period title in GradeBreakdown.php
- SQL ORDER BY Teacher name in GradeBreakdown.php

Changes in 7.8.3
----------------
- Fix trim 0 (float) when percent > 1,000: do not use comma for thousand separator in Grades.php & ProgressReports.php

Changes in 7.8.2
----------------
- Fix try searching plural forms adding an 's' to singular form and with number set to 1 in Translator.php

Changes in 7.8.1
----------------
- CSS Edge browser fix: Do not merge focus-within styles with hover styles in stylesheet.css, stylesheet_wkhtmltopdf.css & rtl.css

Changes in 7.8
--------------
- Handle `multiple` files attribute in warehouse.js & Inputs.php
- Add FileUploadMultiple(). Handle `multiple` files attribute for FileUpload() in FileUpload.fnc.php
- Remove Reset button from Find a Student / User forms in Students/Search.inc.php & Users/Search.inc.php
- CSS & JS open submenu on focus & focus-within in warehouse.js, stylesheet.css & rtl.css
- CSS menu link & button color on focus in stylesheet.css & colors.css
- Fix check students Course Status in PrintClassLists.php, PrintClassPictures.php, ClassSearchWidget.fnc.php, Referrals.php, EmailReferral.fnc.php & Widgets.fnc.php
- Add Include Inactive Students checkbox in MasterScheduleReport.php & RequestsReport.php
- Fix unset current student (check Course Status) when MP updated in Side.php
- SQL fix Discipline Referrals using WHERE EXISTS in Widgets.fnc.php
- Numeric Discipline field: invert values so BETWEEN works in Widgets.fnc.php
- Numeric Discipline field: input type number in Widgets.fnc.php
- Fix SQL error missing FROM address table in GetStuList.fnc.php

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
- Fix SQL error table address specified more than once in GetStuList.fnc.php

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
