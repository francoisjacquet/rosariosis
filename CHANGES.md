# CHANGES
## RosarioSIS Student Information System

Changes in 12.7.3
-----------------
- Fix Help button not showing help, compile error in plugins.min.js

Changes in 12.7.2
-----------------
- Fix Checkbox input, use `_makeCheckboxInput()` function in Schools.php
- Fix CSS responsive header, use .rseparator class in Grades.php, AnomalousGrades.php & Statements.php
- Fix SQL error invalid input syntax for type date: "" in AssignOtherInfo.php
- Fix Final Grade when assignments are weighted in StudentGrades.php
- Fix Multilingual course title: use ParseMLField() in ProgressReports.php, thanks to @Macadoshis
- Fix CSS responsive List width: do NOT use the .fixed-col class, use pure CSS in Schedule.inc.php
- Fix regression since 12.5 JS update body on mobile: menu button adds #! to URL, remove hash in warehouse.js
- Fix regression since 12.5 JS do not setInnerHTML if no target in warehouse.js
- Fix CSS theme WPadmin menu loading icon height in stylesheet.css & zresponsive.css

Changes 12.7.1
--------------
- Fix reassign existing Barcode to student Food Service account in Food_Service/Students/Accounts.php
- Add `_skipDie()` function, used when skipping CSP report in SaveReport.php
- Multilingual course title: use ParseMLField() in Schedule.inc.php
- CSS responsive fix Schedule table, use .rbr class in Schedule.inc.php
- Skip CSP violation triggered by safari-web-extension & sandbox eval code in SaveReport.php
- Fix Chrome user logged out after CSP violation in SaveReport.php

Changes in 12.7
---------------
- SQL allow array in $where_columns: WHERE COLUMN IN(val1,val2) in DBUpsert.php
- Fix #363 Pull-Down field: add "No Value" option in AssignOtherInfo.php
- Fix #363 Checkbox Field: save unchecked state in AssignOtherInfo.php
- Fix #363 Auto Pull-Down field: add "-Edit-" option in AssignOtherInfo.php
- Rework save logic & use DBUpdate() function in AssignOtherInfo.php
- Move PhpMyAdmin\MoTranslator classes to match their namespace in classes/PhpMyAdmin/MoTranslator/
- Move Symfony\Polyfill classes to match their namespace in classes/Symfony/Polyfill/
- Remove XML-RPC compatibility classes in classes/PHPCompatibility/Xmlrpc/
- Remove XML-RPC compatibility functions in ProgramFunctions/PHPCompatibility/xmlrpc.php & plugins/Moodle/client.php
- Move Services_JSON class to match its namespace (none) in classes/
- Move PHPMailer\PHPMailer classes to match their namespace in classes/PHPMailer/PHPMailer/
- Move mikehaertl classes to match their namespace in classes/mikehaertl/
- Move Shuchkin classes to match their namespace in classes/Shuchkin/
- Move StaffWidgets & Widgets classes to match their namespace in classes/RosarioSIS/
- Move StaffWidget classes to match their namespace in classes/RosarioSIS/StaffWidget/
- Move Widget classes to match their namespace in classes/RosarioSIS/Widget/
- Autoload classes (PSR-4): classes/[Namespace]/[Class].php in Warehouse.php
- Autoload classes (PSR-4): remove require_once, program wide
- Custom widget classes should now use \Addon_Name\StaffWidget\ or \Addon_Name\Widget\ namespace
- Fix "Last Year Course" widget responsive: add .st class in modules/Scheduling/functions.inc.php
- Deprecate PhpDebugBar() & Kint() functions in Debug.fnc.php
- Minor corrections to the Russian and Ukrainian translations in rosariosis.po & rosariosis.sql
- Simplify PHP logic of HackingLog(): 17 lines gain in HackingLog.fnc.php
- Use MultipleCheckboxInput() function in `_makeMultipleInput()` in StudentsUsersInfo.fnc.php
- Fix PHP8.5 deprecated curl_close() function in curl.php
- Double (free) Comment input size when MP does not do Comments in InputFinalGrades.php
- Misconfiguration hint: Scale Value different from max GPA Value in ReportCardGrades.php
- Misconfiguration hint: Minimum Passing Grade > Scale Value in ReportCardGrades.php
- CSS themes enlarge custom Numeric field in stylesheet.css
- HTML increase "Course" input maxlength to 100 in EditReportCardGrades.php
- HTML use number type for Grade and Credit inputs in EditReportCardGrades.php
- Do not save CSP violations triggered by browser extensions, some domains & custom ones in SaveReport.php
- Add Czech (Czech Republic) translation in locale/cz_CZ.utf8/
- Fix regression since 12.5 Calendar select input was erasing calendar days in Calendar.php
- Fix PHP8.5 deprecated imagedestroy() function in ImageResizeGD.php

Changes in 12.6
---------------
- HTML responsive stackable tables in Export.php
- Export list: add Total row in ListOutput.fnc.php
- Add rounded flag icons in locale/[locale_code]/flag.png
- Fix #362 Order by Points as Letter Grade is alphabetically sorted (may be inaccurate) in Grades.php
- Add Activity field is required + "Remove" activity (instead of "Delete") in Eligibility/Student.php
- Fix responsive image height, rename $width param to $height in FS_Icons.inc.php & MenuItems.php
- HTML add fieldset & padding to PopTable in MassRequests.php
- CSS add .class-picture class in PrintClassPictures.php
- CSS fix #365 pictures width, height & name overflow in PrintClassPictures.php
- Remove `_saveRosarioModules()` local function, use Config() in Modules.inc.php
- Remove `_saveRosarioPlugins()` local function, use Config() in Plugins.inc.php
- CSS Fix first column (list, table) in DailySummary.php, Grades.php, Search.inc.php, rtl.css, stylesheet.css & zresponsive.css
- CSS FlatSIS theme reduce bottom buttons height in stylesheet.css & zresponsive.css
- Display short month and year labels instead of short month only in CategoryBreakdownTime.php
- SQL order Transcript Grades by Course Title in Transcripts.fnc.php
- Add Administrator option to Visible To User Profiles in Resources.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Create Student Account: Force Default School in index.php, Configuration.php, Student.php, General_Info.inc.php, rosariosis.sql & rosariosis_mysql.sql
- SQL add csp_reports table in rosariosis.sql & rosariosis_mysql.sql
- Add Content Security Policy plugin in plugins/Content_Security_Policy/, rosariosis.sql & rosariosis_mysql.sql
- Update to version 12.6 in Update.fnc.php
- Add Content-Security-Policy-Report-Only HTTP header in Warehouse.php
- Fix SQL error foreign keys: force roll Schools in Rollover.php
- Update French & Spanish translations in rosariosis.po
- Add Russian & Ukrainian translations in InstallDatabase.php, locale/ru_RU.utf8/ & locale/uk_UA.utf8/

Changes in 12.5
---------------
- Add '&modfunc=save' to form URL in Expenses.php, Incomes.php, Salaries.php, StaffPayments.php, DisciplineForm.php, Referrals.php, EnterEligibility.php, InputFinalGrades.php, StudentFees.php, StudentPayments.php & Preferences.php
- SQL case-insensitive username uniqueness check in ImportUsers.fnc.php
- Fix HTML error label's for attribute doesn't match any element id in General_Info.php
- Fix add .tinymce CSS class when class already set in $extra param in Inputs.php
- JS add CSP root, functions & programFunctions (global, always loaded) in Gruntfile.js, assets/js/csp/
- JS add setInnerHTML() function in warehouse.js
- JS add submenuOnTouch() function in warehouse.js
- JS Prevent submitting form if no checkboxes are checked in warehouse.js & Inputs.php
- JS add bottomButtonClick() Bottom.php buttons click related events in warehouse.js & Bottom.php
- JS deprecate switchMenu() function: use `<details><summary>` instead in warehouse.js, General_Info.inc.php, Search.fnc.php & Widgets.php
- JS ajaxPrepare(): handle #ajax_update_body, #menu_user_session & #warehouse_user_session in warehouse.js, Side.php & Warehouse.php
- JS use #x_redirect_url value instead of XRedirectUrl global var in warehouse.js & PreparePHP_SELF.fnc.php
- JS Trigger custom ajaxPrepare event in warehouse.js
- Deprecate `_printPageHead()` function & rework display logic in PasswordHelp.php
- JS add backPrompt() function in BackPrompt.js & Prompts.php
- JS remove inline code from CaptchaInput() in Inputs.php & CaptchaInput.js
- JS remove inline code from FileInput() in Inputs.php & FileInput.js
- JS remove inline code from MLTextInput() in Inputs.php & MLTextInput.js
- JS remove inline code from MarkDownInputPreview() in Inputs.php & MarkDownInputPreview.js
- JS required group of checkboxes in Inputs.php & MultipleCheckboxInput.js
- JS remove inline code from PasswordInput() in Inputs.php & PasswordInput.js
- JS remove inline code from DeletePrompt() & Prompt() + add .button-prompt-cancel CSS class in Prompts.php & Prompt.js
- JS remove inline code from Search() in Search.fnc.php & Search.js
- JS remove inline code from Select2Input() in Inputs.php & Select2Input.js
- JS remove inline code from TinyMCEInput() in Inputs.php & TinyMCEInput.js
- JS remove inline code from Widget_course in Widget.php & Course.js
- JS remove inline AJAX call in ClassRank.inc.php, FinalGrades.inc.php, Addon.fnc.php, Modules.inc.php, Plugins.inc.php & AjaxUrl.js
- JS remove inline code from MoodleImportFormSubmitJS() in Moodles/config.inc.php, ImportUsers.fnc.php & ImportFormSubmit.js
- JS remove inline reload menu code in MarkingPeriods.php, Rollover.php, Modules.inc.php, Profiles.php & ReloadMenu.js
- JS remove inline code from `_makePaymentsCommentsInput()` in Accounting/functions.inc.php & MakePaymentsCommentsInput.js
- JS remove inline code from program in Administration.php & Administration.js
- JS remove inline code from program in FixDailyAttendance.php & FixDailyAttendance.js
- JS remove inline code from RegistrationAdminContactEnable() in RegistrationAdmin.fnc.php & RegistrationAdminContactEnable.js
- JS remove inline code from RegistrationSiblingUseContactsAddress() in Registration.fnc.php & RegistrationSiblingUseContactsAddress.js
- JS remove inline code from program in MassCreateAssignments.php & MassCreateAssignments.js
- JS remove inline code from StudentAssignmentSubmissionOutput() in StudentAssignments.fnc.php & StudentAssignmentSubmissionOutput.js
- JS remove inline code from program in ChooseCourse.php & ChooseCourse.js
- JS remove inline code from program in ChooseRequest.php & ChooseRequest.js
- JS remove inline code from program in Export.php & Export.js
- JS remove inline code from program in Courses.php & Courses.js
- JS remove inline code from program in MassDrops.php & MassDrops.js
- JS remove inline code from program in MassRequests.php & MassRequests.js
- JS remove inline code from program in MassSchedule.php & MassSchedule.js
- JS remove inline code from program in Requests.php & Requests.js
- JS remove inline code from program in Schedule.php & Schedule.js
- JS remove inline code from program in Scheduler.php & Scheduler.js
- JS remove inline code from program in AccessLog.php & AccessLog.js
- JS remove inline code from program in Configuration.php & Configuration.js
- JS remove inline code from program in MarkingPeriods.php & MarkingPeriods.js
- JS remove inline code from program in PortalPolls.php & PortalPolls.js
- JS remove inline code from program in Rollover.php & Rollover.js
- JS remove inline code from `_makePaymentsCommentsInput()` in Student_Billing/functions.inc.php & MakePaymentsCommentsInput.js
- JS remove inline code from Addresses & Contacts tab in Address.inc.php & Address.js
- JS remove inline code from General Info tab in General_Info.inc.php & CreateStudentAccount.js
- JS remove inline code from program in StudentLabels.fnc.php & StudentLabels.js
- JS remove inline code from BottomButtonBackUpdate() in Bottom.fnc.php & BottomButtonBackUpdate.js
- JS remove inline code from ChartjsChart() in Charts.fnc.php & ChartjsChart.js
- JS remove inline code from GetFieldsForm() in Fields.fnc.php & FieldsGetForm.js
- JS remove inline code from SubstitutionsInput() in Substitutions.fnc.php & SubstitutionsInput.js
- JS fix CSP error: use setInnerHTML() instead of jQuery .append() function in jquery.colorbox.js & jquery.colorbox-min.js
- JS not available, reload page (eg. when reopening closed tab) in Warehouse.php, noJsReload.js & warehouse.js
- Module is addon, set custom module icon & remove $icon param in DashboardModule.fnc.php
- CSP Redirection is done in HTML in HackingLog.fnc.php, Theme.fnc.php, Student.php & User.php
- Fix reload theme when "Force" checked in Theme.fnc.php
- CSP add .onmouseover-tipmsg CSS class, use `data-title` & `data-msg` attributes in TipMessage.fnc.php
- Redirection is done in HTML in case current request is AJAX in Warehouse.php
- Add `_gradeScaleInputHtml()` & `_search()` private methods to Widget_letter_grade object in Widget.php
- Use Select2 input + AJAX search when scale has more than 1003 grades in Widget.php
- Change User-Agent to "RosarioSIS/1.0; +https://www.rosariosis.org/" in curl.php
- Cache config values, avoid calling Config() function X times in Currency.fnc.php & User.fnc.php
- Submit selected date: use `onchange-date-submit` CSS class & `data-name` attribute in Date.php
- Add CSS classes to .list element in ListOutput.fnc.php
- CSP use the 'vertical-tab-navigation' class option for ListOutput() in Grades.php & InputFinalGrades.php
- CSP remove unsafe-inline Javascript: use `data-url` attribute in ListOutput.fnc.php
- Use MultipleCheckboxInput() function in AddAbsences.php
- CSP add .onclick-checkall CSS class & use `data-name-like` attribute in AddAbsences.php, DuplicateAttendance.php, Exceptions.php & Profiles.php
- CSP add .onclick-toggle CSS class & use `data-id` attribute in CreateParents.php, ReportCards.fnc.php & Transcripts.fnc.php
- FinalGradeSave() can save COMMENT column in FinalGrades.inc.php
- Include Students active as of Timeframe end date in DailyTransactions.php
- CSP add .onchange-maybe-edit-select CSS class in StudentsUsersInfo.fnc.php & Address.inc.php
- Prevent using username, or email in the password in General_Info.inc.php
- CSP add .onclick-ajax-link & .onchange-ajax-link CSS classes, use `data-link` attribute, program wide
- CSP add .onchange-ajax-post-form CSS class, program wide
- CSP use curl POST instead of AJAX to send data to external domain in FirstLogin.fnc.php
- HTML space gain: remove minus between letter & percent grades in Grades.php
- SQL remove unused columns in GradebookBreakdown.php
- CSP use `<template>` for InputDivOnclick() in Inputs.php
- CSS IE fix hide `<template>` in stylesheet.css
- JS Fix for Internet Explorer in warehouse.js & csp/functions.js
- Simplify isAJAX() function code in Warehouse.php
- CSS add .tinymce-document class: double default height in HonorRoll.php, Letters.php & stylesheets.css
- CSP: use `<details><summary>` CSS deprecate .switchMenu in stylesheet.css
- CSS add `<pre>` styling in colors.css & stylesheet.css
- CSS FlatSIS theme Prevent body from scrolling when submenu open + scroll submenu in stylesheet.css
- CSP use PHP instead of JS for salute header in Portal.php
- Fix database partly installed due to user abort in InstallDatabase.php
- Fix SQL error null value in column "student_id" in Schedule.php
- Fix PHP warning when (Current) User not found / was just deleted in Current.php
- Include Reporter & Incident Date widgets on the Find a Student form in ReferralLog.php
- Fix PHP notice undefined index: elements in ReferralLog.php
- HTML responsive adjust Contact form name inputs on small desktop screen in Registration.fnc.php
- Update French & Spanish translations in rosariosis.po
- Fail if no curl installed in FirstLogin.fnc.php & Addon.fnc.php
- Fix regression since 12.1 wkhtmltopdf error segmentation fault when CSS quotes property defined in wkhtmltopdf.css
- Fix PHP fatal error unsupported operand types: string - int in database.inc.php
- Fix HACKING ATTEMPT in SetUserStudentID() when "Search All Schools" checked in Search.fnc.php, Statements.php, PrintStudentInfo.php & Students/Search.inc.php

Changes in 12.4.3
-----------------
- SQL fix #359 duplicate entries when school is renamed after Rollover in AddDrop.php, thanks to @HubertQuiquet
- Fix regression since 12.4.1 search discipline numeric field in Widget.php
- Fix PHP fatal error division by zero: Percent Total is 0%, N/A final grade in FinalGrades.inc.php
- Fix check requested enrollment date for conflict (instead of today) in Schedule.php
- Fix exact search for MySQL: double quotes are escaped in Search.fnc.php

Changes in 12.4.2
-----------------
- Fix PHP fatal error failed opening required add-on file in Student.php & User.php
- Fix remove '&bottomfunc=print' from redirect_to URL param in index.php
- Fix include students active as of requested MP's end date in Grades.fnc.php
- CSS fix image height inside responsive table to colorBox in zresponsive.css

Changes in 12.4.1
-----------------
- Fix PHP warning: fatal error when end date < start date in AddDropBreakdownTime.php & CategoryBreakdownTime.php
- Fix search discipline numeric field for "0" value in Widget.php
- SQL case-insensitive username uniqueness check in Student.php, User.php & ImportUsers.fnc.php
- Deprecate `$extra['second_col']` & `$extra['extra_search']`, use `$extra['search']` instead in ReferralLog.php, ClassSearchWidget.fnc.php, Search.inc.php & Export.php
- Fix MySQL error fsti.TRANSACTION_ID isn't in GROUP BY in MenuReports.php
- Fix & simplify form & tabs URL, use GET method & `PreparePHP_SELF()` in MenuReports.php
- Fix SQL syntax error in Food_Service/Users/ActivityReport.php
- Remove useless Find a Student/User search in ActivityReport.php
- Allow list export in ActivityReport.php
- SQL INSERT STUDENT_ID INTO food_service_transactions in Food_Service/Students/Transactions.php
- Fix display value text when associative $options array in Inputs.php
- CSS add .valign-middle class to ≥ & ≤ signs for date fields in Widget.php & Search.fnc.php
- Security #358 Add full timestamp to file name in StudentAssignments.fnc.php

Changes in 12.4
---------------
- HTML use header4 instead of nested fieldset in Registration.fnc.php
- New "Add / Drop Breakdown over Time" program in AddDropBreakdownTime.php
- Hide "Include Parents of Inactive Students" checkbox if profile is not parent in Users/Search.inc.php
- Add AllowEditTemporary() & AllowEditTeacher() functions in AllowEdit.fnc.php
- Use AllowEditTemporary() & AllowEditTeacher() functions, program wide
- Security: Security: allow everyone to use Portal, allow admin only to use popups in AllowEdit.fnc.php
- Remove unused `$extra['staff_fields']['view']` & `$extra['student_fields']['view']` vars in GetStaffList.fnc.php, GetStuList.fnc.php & Search.inc.php
- Use vertically align list data option 'valign-middle' in StudentSummary.php
- Fix PHP warning Undefined array key 1 in CategoryBreakdown.php, CategoryBreakdownTime.php, StudentFieldBreakdown.php & StudentBreakdown.php
- Select Incomplete code when N/A grade in EnterEligibility.php
- Security fix: default to none when selected profile does not exist in User.php
- Fix PHP warning undefined variable $date_select when no calendars in Eligibility/TeacherCompletion.php
- Update French & Spanish translations in rosariosis.po
- Security: add `.htaccess` file to block direct access to potentially sensitive files in .htaccess
- CSS WPadmin theme fix password strength bars in stylesheet.css
- Move the "Use Grade Scale Comments" checkbox down to the next header in InputFinalGrades.php
- Hide totals if user has no access to the Daily Transactions program in Accounting/includes/Dashboard.fnc.php & Student_Billing/includes/Dashboard.fnc.php

Changes in 12.3.2
-----------------
- Multilingual course title: use ParseMLField() in TeacherCompletion.php, Grades/Configuration.php, FinalGrades.php, MassCreateAssignments.php, ReportCardComments.php, StudentAssignments.fnc.php, StudentGrades.php, Portal.php, Scheduling/functions.inc.php & Calendar.php

Changes in 12.3.1
-----------------
- CSS fix themes for RTL (Arabic): submenu on mouseover, arrows, password input, calendar days in rtl.css
- CSS themes remove deprecated .toggle class & small adjustments in stylesheet.css & zresponsive.css
- Default School Year: 2025 (2025-2026) in config.inc.sample.php, rosariosis.sql & rosariosis_mysql.sql

Changes in 12.3
---------------
- Multilingual course title: use ParseMLField(), program wide
- Multilingual course title: SQL change title column type from varchar(100) to text in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Multilingual course title: use MLTextInput() in Courses.php
- MLTextInput() fix use of 'required' in $extra in Fields.fnc.php, Inputs.php & Courses.php
- Fix PHP8.4 Constant CURLOPT_BINARYTRANSFER is deprecated in curl.php
- Fix PHP8.4 deprecated passing E_USER_ERROR to trigger_error() in curl.php & Markdownify/Converter.php
- Fix PHP8.4 Implicitly marking parameter as nullable is deprecated in Parsedown.php
- Security: Cache killer: use file last modified time hash instead of RosarioSIS version in Warehouse.php
- Include PHP ctype extension emulation by Symfony in Ctype.php, PHPCompatibility.php & ctype.php
- Themes: Remove unused font files, 427KB gain in FlatSIS/fonts/Lato/ & WPadmin/fonts/opensans/
- WPadmin theme: convert ttf to woff2 font files, 105KB gain in WPadmin/fonts/opensans/
- CSS WPadmin: Fix Dashboard tip message row too tall in stylesheet.css
- Move SQL translation file to locale/[locale_code]/ folder
- Fix Rollover order: move Grading Scales before Courses in Rollover.php
- Allow object methods (callable) in $functions param in DBGet.fnc.php
- Use DBInsert() & DBUpdate() functions, program wide
- Fix potential SQL error SELECT DISTINCT, ORDER BY expressions must appear in select list in ReportCards.fnc.php
- CSS use .legend-gray.size-1 for details label in StudentAssignments.fnc.php
- Fix regression since 12.2.3 Save null percent: N/A final grade in FinalGrades.inc.php
- Save null percent: N/A for all children Marking Periods case in FinalGrades.inc.php

Changes in 12.2.3
-----------------
- Fix return empty array when no "Course-specific Comments" in ReportCards.fnc.php
- Fix PHP fatal error Unknown format specifier "," in Greek translation in locale/el_GR.utf8/rosariosis.po
- Fix SQL error when percent grade > 999.9 in FinalGrades.inc.php
- Revert CSS FlatSIS theme adjust tooltip position in stylesheet.css

Changes in 12.2.2
-----------------
- Fix PHP warning undefined offset: 7 when day is Sunday in EnterEligibility.php
- CSS WPadmin theme fix .underline-dots wrapping in stylesheet.css
- Fix regression since 12.2 PostgreSQL error function sum(text) does not exist in StudentGrades.php
- Fix anonymous statistics bar graph for individual assignments: average & Excused `*` in StudentGrades.php

Changes in 12.2.1
-----------------
- PostgreSQL on cPanel fix ERROR: must be owner of language plpgsql in rosariosis.sql & Update.fnc.php
- PostgreSQL create plpgsql extension if not exists in rosariosis.sql

Changes in 12.2
---------------
- Fix PostgreSQL error invalid input syntax for type integer: "2.0" in Periods.php, Assignments.php & MassCreateAssignments.php
- Internationalization: Zip Code before City for non English speaking countries in MailingLabel.fnc.php
- Use multiple Select2 instead of checkboxes in PortalPollsNotes.fnc.php
- Simplify code to save PUBLISHED_PROFILES in PortalNotes.php & PortalPolls.php
- Use multiple Select2 instead of checkboxes in Resources.php & Resources.fnc.php
- Enrollment End Date: No N/A option if next entry already has Start date in StudentsUsersInfo.fnc.php
- SQL Check if student already enrolled on that date when updating END_DATE in SaveEnrollment.fnc.php
- Error if add-on folder has "-master" or "-main" suffix in Modules.inc.php & Plugins.inc.php
- Update French & Spanish translations in rosariosis.po
- Security: move RosarioSIS version from Login screen to Portal in index.php & Portal.php
- Add `composer.json` to Files list (required for add-ons) in modules/README.md & plugins/README.md
- Check subject, course & course period ID are valid for current school & year in Courses.php
- Fix SQL error: user just deleted current Marking Period, reload Side menu in MarkingPeriods.php
- Fix SQL error null value in column "marking_period_id" & "teacher_id" in Rollover.php
- Fix SQL error null value in column "course_period_id" in Rollover.php
- Add program_config table to no school tables list in Rollover.php
- Add .opus audio to file extensions white list in FileUpload.fnc.php
- Add WebP image format support in FileUpload.fnc.php, ImageResizeGD.php, Configuration.php & General_Info.inc.php
- Raise minimum PostgreSQL version from 8.4 to 9.2 in INSTALL.md, INSTALL_es.md, INSTALL_fr.md & README.md
- Security: remove INSTALL & README PDF files (use MarkDown files) in INSTALL.pdf, INSTALL_es.pdf, INSTALL_fr.pdf & README.pdf
- Add $assignment_type_id param to FinalGradesQtrOrProCalculate() & FinalGradesGetAssignmentsPoints() functions in FinalGrades.inc.php
- Use FinalGradesGetAssignmentsPoints() & FinalGradesQtrOrProCalculate() functions in Grades.php
- Simplify `_makeExtraAssnCols()` function logic & function: 70 lines + 2.3KB gain in Grades.php
- Save null percent: N/A final grade in `_makeLetterGrade.fnc.php`, InputFinalgrades.php & FinalGrades.inc.php
- Add Gradebook Grades before save action hook in Actions.php & Grades.php
- SQL remove deprecated columns in rosariosis.sql & rosariosis_mysql.sql
- Remove php.ini safe_mode check, deprecated since PHP 5.3 in Portal.php
- Send RosarioSIS version in Addon.fnc.php
- Add Course Period update Teacher action hook in Actions.php & Courses.fnc.php
- SQL allow Gradebook Grades < 0, not only '-1' (Excused) in EnterEligibility.php, Grades.php, ProgressReports.php, StudentGrades.php & FinalGrades.inc.php
- Use FinalGradesQtrOrProCalculate() function in GradebookBreakdown.php
- Fix FinalGradesGetAssignmentsPoints() when $mp_id is not UserMP() in FinalGrades.inc.php
- CSS themes small fixes + Prevent body from scrolling when help open in stylesheet.css
- SQL avoid query, get GRADE_LETTER from FinalGradesQtrOrProCalculate() in Grades.php, GradebookBreakdown.php & FinalGrades.inc.php
- PostgreSQL remove deprecated create_language_plpgsql() in rosariosis.sql
- SQL N/A grade (empty GPA value) does not affect GPA in rosariosis.sql, rosariosis_mysql.sql & Update.fnc.php
- CSS themes small fixes + Reduce multiple select2 font size in stylesheet.css & colors.css
- SQL fix Breakoff percentages for Main grading scale in rosariosis_fr.sql & rosariosis_es.sql
- Fix regression since 12.0 display Assignment on Calendar in CalendarDay.inc.php & colors.css

Changes in 12.1.3
-----------------
- MySQL fix You can't specify target table 'staff' for update in FROM clause in User.php

Changes in 12.1.2
-----------------
- SQL fix last and first name were mixed when importing user/student from Moodle in ImportUsers.fnc.php
- Fix PHP warning when updating course period teacher in Moodle/functions.php & Moodle/Scheduling/Courses.php
- Fix enrol teacher into course instead of just assigning teacher role in Moodle/functions.php, Moodle/Scheduling/Courses.php & Moodle/School_Setup/Rollover.php
- MySQL fix You can't specify target table 'ac' for update in FROM clause in Courses.fnc.php
- Fix Moodle error Short name is already used for another course in Moodle/functions.php, Moodle/Scheduling/Courses.php & Moodle/School_Setup/Rollover.php
- Fix SQL error value too long, add input maxlength in StudentsUsersInfo.fnc.php

Changes in 12.1.1
-----------------
- Fix SQL error when single quote inside relation in Registration.fnc.php & RegistrationAdmin.fnc.php
- Allow creating historical marking periods up to 10 years in the past in EditHistoryMarkingPeriods.php & EditReportCardGrades.php
- Fix PHP < 8.0 warning Wrong parameter count for number_format() in Transcripts.fnc.php
- Fix PHP warning undefined variable $percent in FinalGrades.inc.php

Changes in 12.1
---------------
- MySQL set CREATED_AT on INSERT in rosariosis_mysql.sql
- Add Admin Delete Permission in functions.inc.php, Expenses.php, Incomes.php, Salaries.php, StaffPayments.php, Exceptions.php & Profiles.php
- SQL Add Admin Delete Permission to profile_exceptions & staff_exceptions tables in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Add "Show only my courses" checkbox in Courses.php
- Add Breakdown by Grade Level in DailyTotals.php
- Remove "only 1 MP & 1 grading period open at any given time" check for Progress Periods in MarkingPeriods.php
- Add help "limitation does not apply to progress periods" in Help_en.php & help.po
- Update SimpleXLSXGen to version 1.4.12 in classes/SimpleXLSXGen/
- Add help about Permissions (tooltip) on Add a user screen in Users/includes/General_Info.inc.php
- Add-on SQL translation file can be under `locale/[locale_code]/` folder in Modules.inc.php & Plugins.inc.php
- Fix tooltip display at bottom in School_Setup/Configuration.php
- Add AddonUpsellPremium() function in Addon.fnc.php
- JS If inside colorBox, close it on Cancel in Prompts.php
- Fix regression since 7.1 short month name, eg.: "Sep" in Date.php
- Warning instead of error for PHP extensions in diagnostic.php
- CSS Display tooltip at bottom, add `.tooltip.bottom` class in stylesheet.css & School_Setup/Configuration.php
- CSS improve styling of blockquote, datalist, fieldset, colorBox in stylesheet.css & colors.css
- CSS Upsell Premium, add `.button-upsell-premium` class in stylesheet.css & colors.css
- CSS Prevent body from scrolling when colorBox open in stylesheet.css
- CSS Hide File input "Browse" button in stylesheet.css
- JS Lazy loading for images inside inline colorBox in warehouse.js
- JS use MDConverter() instead of GetMDConverter() in warehouse.js
- JS fix maxHeight on resize patch in jquery.colorbox.js
- Add SECURITY.md file
- Update French & Spanish translations in rosariosis.po

Changes in 12.0.2
-----------------
- Format Credit, display 0.33 instead of 0.333333333333333 in Transcripts.fnc.php
- PostgreSQL fix set_class_rank_mp() call in ClassRank.inc.php
- JS Fix regression since 11.4 allow HTML inside MarkDown in warehouse.js
- Convert back to MarkDown: use link in paragraph option in MarkDownHTML.fnc.php
- Revert open link in new tab, we don't want this in SanitizeMarkDown() in Parsedown.php
- Fix do not use URL in brackets when converting to MarkDown in Markdownify/Converter.php
- JS fix isMobileMenu() detection in warehouse.js

Changes in 12.0.1
-----------------
- Fix no REMOVE column (add button) if no periods in the list in Periods.php
- Fix SQL error when no Periods, make Period select required in Courses.php
- Security fix #352 selected theme does not exist in Preferences.php, thanks to @rudranshsinghrajpurohit
- Security fix selected page size does not exist in Preferences.php
- Remove "-main" suffix from manually uploaded add-ons in Modules.inc.php & Plugins.inc.php
- Fix PHP error when Recreate Calendar & From or To date is N/A in Calendar.php
- Add checkout latest release tag to installation instrcutions in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Fix PHP warning undefined array key "PROFILE_ID" in GetTeacher.fnc.php

Changes in 12.0
---------------
- SQL update User password for all school years in PasswordReset.php, Preferences.php & User.php
- SQL escape dynamic column names in GetStaffList.fnc.php & GetStuList.fnc.php
- Check `$_REQUEST` after modfunc in case it is empty (no form inputs have changed), program wide
- SQL Use DBInsert() function instead of DBInsertSQL() in AddActivity.php, Calendar.php, MarkingPeriods.php, MassAssignFees.php & MassAssignPayments.php
- Fix SQL syntax error at or near ")" when selected MP not in current School Year in PrintSchedules.php
- Unset `$_REQUEST` data (values, student, ...) array once modfunc done, program wide
- Remove "Grade Scale" column from the Grades list in ReportCardGrades.php
- HTML Omit value attribute when empty in Inputs.php
- ChosenSelectInput() use Select2Input() function & raise deprecation notice in Inputs.php
- JS Remove jQuery Chosen plugin in assets/js/jquery-chosen/
- GetInputID() remove non-alpha, non-digit characters (keep underscores) in Inputs.php
- Breaking change: GetInputID() dots `.` are now removed from ID in Inputs.php
- Breaking change: Update PHPMailer from v5.2.8 to v6.9.1 in classes/PHPMailer/ & SendEmail.fnc.php
- Breaking change: Remove SaveData() function in SaveData.fnc.php
- Preferences() remove deprecated 'MONTH', 'DAY' & 'YEAR' items in User.fnc.php
- Use DBUpdate() function in Accounts.php & MassDrops.php
- JS Show BottomButtonBack & update its URL & text: add BottomButtonBackUpdate() function in Bottom.fnc.php
- Remove need to make an AJAX call to Bottom.php in Bottom.php, Bottom.fnc.php, MyReport.php, ClassSearchWidget.fnc.php, Search.inc.php & Export.php
- Use colorBox instead of popup window (CSS use .colorbox class) in warehouse.js, Widget.php, Buttons.php, GetStuList.fnc.php, ListOutput.fnc.php, Courses.php, MassDrops.php, MassRequests.php, MassSchedule.php, Schedule.php, Scheduling/function.inc.php, Calendar.php, CalendarDay.inc.php, ChooseCourse.php & ChooseRequest.php
- JS Move form submit handler from ajaxPrepare() to onload in warehouse.js
- JS ajaxPostForm() deprecate submit param in warehouse.js & program wide
- Update jQuery from v2.2.4 to v3.7.1 in jquery.js & Warehouse.php
- Remove jQuery Form Plugin in jquery.form.js & Gruntfile.js
- Fix maintain Enrollment Date in Course Period link (Add a Course popup) in Courses.php
- JS fix form date & checkbox inside colorBox (Add a Course popup) in Courses.php
- JS Add target param to JSCalendarSetup() & MarkDownToHTML() functions in warehouse.js
- JS Use FormData instead of jQuery Form Plugin in warehouse.js
- JS Exclude file input with no files selected: disable before creating FormData in warehouse.js
- JS Remove empty GET params from URL (new method using regex) in warehouse.js
- CSS Add modname class to body, ie .modname-grades-reportcards-php for modname=Grades/ReportCards.php in warehouse.js
- JS Add inputAddHTML() function wrapper for addHTML() used by InputDivOnclick() in warehouse.js
- JS use inputAddHTML(): reduce HTML size by up to 26%; declare only 1 global var iHtml in Inputs.php
- JS rework ajaxPrepare() for colorBox & Make onclick div focusable when accessed by tab key in warehouse.js
- JS add getURLParam() function in warehouse.js
- JS AJAX update #body with URL GET params removed or replaced in warehouse.js & Side.php
- Add FileDelete() function in FileUpload.fnc.php
- Security: use FileDelete() instead of unlink(), program wide
- Students Search: remove unused code for `$extra['array_function']` in Search.inc.php
- Add AddonUnzip() & AddOnZipCanUnzip() functions in Addon.fnc.php
- Add AddonInstallationStatisticsPost() function in Addon.fnc.php
- Post add-on installation (first activation) statistics in Modules.inc.php & Plugins.inc.php
- CalendarDayClassesDefault() Remove "inner" mode. Move .hover CSS class to table.calendar-day in Calendar.php & Calendar.inc.php
- CSS add .calendar-event-poptable, .calendar-minutes, .calendar-newevent classes in stylesheet.css, zresponsive.css & Calendar.php
- SVG menu icon (#BottomButtonMenu) in Bottom.php, colors.css & stylesheet.css
- CSS Add .uld class, alias of .underline-dots in colors.css, stylesheet.css & Inputs.php
- CSS add .poll-votes-outer class in zresponsive.css & PortalPolls.php
- CSS themes vertical alignment fixes for button, select2 & others in colors.css, rtl.css, stylesheet.css, zresponsive.css
- Breaking change: Rename CalendarDayNewAssignmentHTML() function to CalendarDayNewEventHTML() in Calendar.php & CalendarDay.inc.php
- Rename CalendarDayNewAssignmentHTMLDefault() function to CalendarDayNewEventHTMLDefault() in CalendarDay.inc.php
- Deprecate `_getAttendanceDayRET()` & `_getAttendanceRET()` functions in ReportCards.fnc.php
- Add & use `_getDailyAbsencesMP()` & `_getOtherAttendanceMP()` functions instead in ReportCards.fnc.php
- SQL rewrite "ORDER BY COL IS NOT NULL DESC" to its equivalent "ORDER BY COL IS NULL", program wide
- Close #348 Add School Year & Principal to Substitutions in Letters.php
- Give full responsibility to & improve AllowEdit() & AllowUse() in AllowEdit.fnc.php
- Add smart cache: up to 8ms faster in AllowEdit.fnc.php
- Rely solely on AllowUse() in Menu.php, Modules.php & Bottom.php
- Breaking change: Remove use of `$_SESSION['_REQUEST_vars']` in Bottom.php, Modules.php, ErrorMessage.fnc.php & PreparePHP_SELF.fnc.php
- Only load module's Menu.php file in ProgramTitle.fnc.php
- No need to call RedirectURL() & unset `$_REQUEST` params as we die just after in ClassRank.inc.php & FinalGrades.inc.php
- Add Greek & Turkish translations (99% completed) in locale/el_GR.utf8, locale/tr_TR.utf8 & REFERENCE.md, thanks to Georgios Katakalos
- Deprecate isPopup() function & remove use in Warehouse() function in Warehouse.php
- Fix do not use ProgramTitle() (loads Menu.php in English only) in Help.php
- Update instructions, add pdo extension, remove deprecated config options in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Deprecate Portal Notes Files upload path $PortalNotesFilesPath global var in PortalPollsNotes.fnc.php
- Use $FileUploadsPath . 'PortalNotes/' instead of $PortalNotesFilesPath in PortalNotes.php
- Remove Portal Notes Attached Files Folder in assets/PortalNotesFiles/
- Deprecate Assignments Files upload path $AssignmentsFilesPath global var in StudentAssignments.fnc.php
- Use $FileUploadsPath . 'Assignments/' instead of $AssignmentsFilesPath in StudentAssignments.fnc.php
- Remove Student Assignments Files Folder in assets/AssignmentsFiles/ & .gitignore
- Deprecate Food Service Icons upload path $FS_IconsPath global var in FS_Icons.inc.php
- Use $FileUploadsPath . 'FS_icons/' instead of $FS_IconsPath in MenuItems.php & FS_Icons.inc.php
- Move Food Service Icons from assets/FS_icons/ to assets/FileUploads/FS_icons/ in Update.fnc.php
- Remove assets/AssignmentsFiles/ if only contains README in Update.fnc.php
- Remove assets/PortalNotesFiles/ if only contains README in Update.fnc.php
- Remove Cookie vars from URL, may be included in `$_REQUEST` in PreparePHP_SELF.fnc.php
- Deprecate `_ReindexResults()` function in ListOutput.fnc.php
- Search List: Remove Relevance column, do not sort results in ListOutput.fnc.php
- Search List: Only return results matching (containing) all terms (AND) in ListOutput.fnc.php
- Fix "Create Student Account" URL, do not use RedirectURL() in index.php
- Add support, authors, suggest & extra in composer.json
- rename() Remove warning for directories across filesystems or devices. Workaround: exec mv (move on Windows) in Modules.inc.php & Plugins.inc.php
- Fix JS use inputAddHTML(): declare iHtml global var first in Inputs.php
- JS Fix ID collision when date inputs inside & outside target in warehouse.js

Changes in 11.8.6
-----------------
- Fix Weight when "Group by Assignment Category" is checked in ProgressReports.php
- Automatic Class Rank calculation when automatically calculating final grades in FinalGrades.inc.php
- SQL INSERT INTO grades_completed so "These grades are complete." is displayed on the Input Final Grades program in FinalGrades.inc.php

Changes in 11.8.5
-----------------
- Fix regression since 10.0 allow User Field type & category edit in Fields.fnc.php
- Fix #350 Final Grade calculation when both "Weight Assignments" & "Weight Assignment Categories" checked in Grades.php, FinalGrades.fnc.php & ProgressReports.php
- Fix Final Grade calculation when "Weight Assignments" checked & excused in Grades.php, FinalGrades.fnc.php
- Fix #351 Automatically calculate Final Grades on Assignment "Assigned"/"Due" date update in Assignments.php

Changes in 11.8.4
-----------------
- CSS fix #349 set color for code select (was white on white background) in colors.css

Changes in 11.8.3
-----------------
- Fix SQL error escape course title & save course, not course period title in FinalGrades.inc.php
- Fix performance: avoid silencing PHP error with @, use isset() instead in DBGet.fnc.php

Changes in 11.8.2
-----------------
- CSS fix input div onclick & .underline-dots line height in stylesheet.css
- Fix .thu-fri-sat class for Calendar day on Public Pages in CalendarDay.inc.php
- Rollback Fix IE not propagating focus event to parent on click in Inputs.php

Changes in 11.8.1
-----------------
- Fix IE not propagating focus event to parent on click in Inputs.php
- Fix regression since 8.6 add missing Request Widget to Advanced Student Search in Widgets.php
- HTML input set width to 100% in PasswordReset.php & Courses.php
- Use 'search' type for Search input in Courses.php
- Rollback JS set ghost div height to 4000px in jquery-fixedmenu.js
- Fix automatically calculate Final Grades for Progress Periods in FinalGrades.inc.php
- CSS fix Grade points line height in stylesheet.css
- Fix SQL error Cannot delete: foreign key constraint fails (marking_period_id + course_periods) in Rollover.php
- Remove Update default school year warning right after Rollover in Rollover.php
- Check user exists in the next school year in Rollover.fnc.php
- Rephrase the update school year warning & button in Rollover.fnc.php
- Add reset default school year note in Rollover.php
- Update French & Spanish translations in rosariosis.po

Changes in 11.8
---------------
- SQL performance: reduce staff table row size (was 3.8KB) & remove HOMEROOM column in Rollover.php, rosariosis.sql & rosariosis_mysql.sql
- Fix rare DB error "null value in column "last_name" violates not-null constraint" in Student.php & User.php
- Fix SQL error null value in column "student_id" violates not-null constraint in Requests.php
- HTML lower max Display Columns to 6 (was 10) in StudentFields.php & UserFields.php
- Check is admin before saving Template in ReportCards.php & Transcripts.php
- CSS remove forced large font size for Transcripts PDF in Transcripts.php
- Add & get PROFILE_ID column in GetTeacher.fnc.php & User.fnc.php
- Add "Course Periods - Short Name" field in Export.php
- Add Final Grades functions & AJAX modfunc in FinalGrades.inc.php
- Use FinalGradesQtrOrProCalculate() & FinalGradesSemOrFYCalculate() functions in InputFinalGrades.php
- Use FinalGradesAllMPSaveAJAX() function in Assignments.php, Grades.php & MassCreateAssignments.php
- Add "Automatically calculate and save Final Grades using Gradebook Grades" option in Configuration.php
- Add pagination for list > 1000 results in DailyTransactions.php
- Prevent hacking & fix SQL error: MPs & Students list must be a comma separated list of integers in ReportCards.fnc.php
- Fix authenticated SQL injection (administrator) via Grading Scales, escape string in InputFinalGrades.php
- Fix always get scale title for "Use the "%s" Grade Scale Comments" in InputFinalGrades.php
- Performance: avoid silencing PHP error with @, use isset() instead in DBGet.fnc.php
- Remove "Number of Days for the Rotation" option from School Information in Schools.php
- Use DBUpdate() function in Schools.php
- Move "Number of Days for the Rotation" option to School Configuration in Configuration.php
- Add help text for "Number of Days for the Rotation" option in Help_en.php
- Translate help text for "Number of Days for the Rotation" option to Spanish & French in help.po
- JS set ghost div height to 4000px in jquery-fixedmenu.js
- JS add navigator.browser property & add .browser-[name] CSS class to html in warehouse.js & plugins.min.js
- CSS various vertical alignment & height adjustments for both themes in colors.css, stylesheet.css & zresponsive.css
- CSS fix JSCalendar z-index when inside colorBox + fix checkbox label height in calendar-blue.css & stylesheet.css

Changes in 11.7.4
-----------------
- Fix regression since 11.7 no results if more than 1000 fees but 0 payments for timeframe in DailyTransactions.php
- Fix result count < results (wrong SQL query to COUNT total results) in ListOutput.fnc.php
- Fix regression since 11.7 wrong pagination info when in combination with SQLLimitForList() in ListOutput.fnc.php

Changes in 11.7.3
-----------------
- Fix regression since 11.7.1 typo generating SQL error in CreateParents.php

Changes in 11.7.2
-----------------
- Fix Security issue #345 SQL injection in RemoveAccess.php, thanks to @fishinspace
- Fix Security issue #346 SQL injection in AttendanceSummary.php & Configuration.php, thanks to @fishinspace
- Fix Security issue #347 SQL injection in index.php & Configuration.php, thanks to @fishinspace
- Fix potential authenticated SQL injection (administrator), cast to float in Reminders.php
- Fix authenticated SQL injection (administrator) via Configuration, escape string in RegistrationSave.fnc.php
- Fix SQL error when Search All Schools in GetTeacher.fnc.php

Changes in 11.7.1
-----------------
- Fix Security issue #344 SQL injection in CreateParents.php, thanks to @fishinspace

Changes in 11.7
---------------
- Fix SQL error invalid input syntax for type integer: cast to integer, program wide
- MySQL mode: disable ONLY_FULL_GROUP_BY in Warehouse.php
- Performance: Run all SQL SET (NAMES, SQL_MODE, DATESTYLE, TIMEZONE) queries at once in Warehouse.php
- HTML add label to Class Search widget in ClassSearchWidget.fnc.php & Widget.php
- Add SQLLimitForList() function in database.in.php & ListOutput.fnc.php
- Use SQLLimitForList() function in GetStuList.fnc.php, GetStaffList.fnc.php, ReportCardGrades.php & AccessLog.php
- Add 'LIMIT' to $extra param (default to 1000) in GetStuList.fnc.php & GetStaffList.fnc.php
- Add 'GROUP' to $extra param in GetStaffList.fnc.php
- CSS FlatSIS theme: add box-shadow to colorBox in colors.css, stylesheet.css & zresponsive.css
- Allow display of `$link['add']` (or remove) on PDF or if not allowed to edit in ListOutput.fnc.php
- Force display of `$link['add']` on PDF or if not allowed to edit in DailyTransactions.php, Percent.php, InputFinalGrades.php & ProgressReports.php
- Remove useless center option in ListOutput() & program wide
- Fix SQL when "Search All Schools" checked in GetTeacher.fnc.php
- Add `_makeHonorRollGPAMinInputs()` function, group Honor Roll GPA Min inputs in same column in ReportCardGrades.php
- Add "No Grades found for Percent" error in InputFinalGrades.php
- Add RolloverDeleteCoursesSQL(), RolloverDoWarning(), RolloverUpdateDefaultSyearWarning() & RolloverUpdateDefaultSyear() functions in Rollover.fnc.php
- Use RolloverDeleteCoursesSQL(), RolloverDoWarning(), RolloverUpdateDefaultSyearWarning() & RolloverUpdateDefaultSyear() functions in Rollover.php & Portal.php
- Add & use RolloverAllSchoolsDoneWarning() function in Rollover.fnc.php & Rollover.php
- Update Slovenian translation (76% complete), thanks to AT group in locale/sl_SI.utf8/
- Update French & Spanish translations in rosariosis.po
- CSS adjust vertical alignment of buttons inside note/error message in stylesheet.css
- Update default school year to 2024 in config.inc.sample.php, rosariosis.sql & rosariosis_mysql.sql

Changes in 11.6
---------------
- JS FixedMenu Round menu height as 100vh is sometimes not exactly equal to window height in jquery-fixedmenu.js
- SubstitutionsInput() use Select2Input if more than 30 substitution codes in Substitutions.fnc.php
- CSS add .valign-middle class in stylesheet.css
- Use .valign-middle class for tables in Address.inc.php, Exceptions.php & Profiles.php
- CSS increase input horizontal padding in stylesheet.css
- CSS Vertical align button inside first td, use when row has input (list with fields) in stylesheet.css
- CSS WPadmin theme use OpenSans Semibold font instead of OpenSans Bold in font.css
- Add vertically align list data option (defaults to false) in ListOutput.fnc.php
- Use vertically align list data option 'valign-middle', program wide
- CSS add .has-input class to list having input (input or select fields) in ListOutput.fnc.php
- CSS add .list-save class to Export list link in ListOutput.fnc.php
- CSS add .list-add-row class (see `$link['add']` parameter) in ListOutput.fnc.php
- HTML add data-list-id attribute to list in ListOutput.fnc.php
- Sort list numerically or as strings case-insensitively in ListOutput.fnc.php
- Sort list using cell's HTML if no inner text in ListOutput.fnc.php
- ExplodeDate() use strtotime() in Date.php
- Remove MonthNWSwitch(), `__mnwswitch_num2char()` & `__mnwswitch_char2num()` functions in Date.php
- Send wkhtmltopdf error by email in PDF.php
- Log error if not sending email ($RosarioErrorsAddress not set) in ErrorMessage.fnc.php
- Remove `$link['add']['html']['remove'] = button( 'add' );` (button added by default), program wide
- HTML add #BottomButtonBack id in Bottom.php
- Fix check no assignment_id requested before deleting assignment type in Assignments.php
- Fix PHP notice undefined array key "TITLE" on Assignment update in plugins/Moodle/Grades/Assignments.php
- Add MailingLabelPositioned() function in MailingLabel.fnc.php
- CSS add .mailing-label-left, .mailing-label-right & .mailing-label-top-margin classes in stylesheet.css
- CSS Adjust Mailing Labels top margin for Report Cards in wkhtmltopdf.css
- Use MailingLabelPositioned() function in ReportCards.fnc.php, ProgressReports.php, PrintRequests.php, PrintSchedules.php, Statements.php, Letters.php & PrintStudentInfo.php
- Add Mailing Label Position option (left or right) in User.fnc.php & Preferences.php
- CSS fix Select2 min-width when hidden in stylesheet.css
- Update French & Spanish translations in rosariosis.po
- CSS responsive tables: Prevent overscroll/bounce in iOS 16+ MobileSafari and Chrome in zresponsive.css
- CSS select: Normalize color on iOS 15+ in colors.css
- MariaDB $DatabaseDumpPath allow 'mariadb-dump' binary in diagnostic.php & config.inc.sample.php

Changes in 11.5.3
-----------------
- Fix MySQL 5.7.5+ error due to ONLY_FULL_GROUP_BY mode: use MIN() in Address.inc.php

Changes in 11.5.2
-----------------
- Fix get Student Info when Parent associated to various students in ReportCards.fnc.php

Changes in 11.5.1
-----------------
- Fix PHP warning undefined array key "" when `$days_array` is empty in PrintSchedules.php & Schedule.inc.php
- Fix Calculate GPA for all Marking Periods when Year not checked in Transcripts.fnc.php
- Fix HTML display Transcript footer when Credits unchecked in Transcripts.fnc.php
- Format Credit Earned, display 0.33 instead of 0.333333333333333 in Transcripts.fnc.php
- Fix PHP8.3 fatal error Duplicate declaration of static variable in classes/Markdownify/Converter.php

Changes in 11.5
---------------
- Moodle plugin: Add REST API protocol in plugins/Moodle/, rosariosis.sql & rosariosis_mysql.sql
- Add MoodleAPICall() & MoodleRESTCall() functions in plugins/Moodle/client.php
- Remove check for xmlrpc extension in diagnostic.php
- Send Course description HTML to Moodle in plugins/Moodle/Scheduling/Courses.php
- Copy `$_REQUEST` to `$_SESSION['_REQUEST_vars']` last in Modules.php, ErrorMessage.fnc.php & PreparePHP_SELF.fnc.php
- No error message if error code 4 (no file was attached) in FileUpload.fnc.php
- DBInsertSQL() & DBUpdateSQL() better check empty values ('', false, null) in DBUpsert.php
- Breaking change: Use `$_ROSARIO` global var instead of `$_SESSION` in School.php
- Slovenian translation (71% complete), thanks to AT group in locale/sl_SI.utf8/ & locale/REFERENCE.md
- Use DBUpdate() & DBInsert() functions in ReportCardGrades.php, Student.php & User.php
- Fix PHP Fatal error maximum execution time of 120 seconds exceeded in InstallDatabase.php
- Update French & Spanish translations in rosariosis.po
- MakeChooseCheckbox() Add 'required' option to $value param. Prevent submitting form if no checkboxes are checked in Inputs.php
- Use MakeChooseCheckbox()'s new 'required' option, program wide
- Fix RedirectURL() when GET params also contained in POST form in PreparePHP_SELF.fnc.php
- JS Compatibility with Use FormData instead of jQuery Form Plugin in FirstLogin.fnc.php
- Add "Final Grading Percentages are not configured." warning in InputFinalGrades.php
- Add ProperTime() function in Date.php
- License change from GNU/GPLv2 to GNU/GPLv2 or later so it is compatible with GNU/GPLv3 in COPYRIGHT, composer.json & package.json
- Unescape DB strings (`$_REQUEST` only, `$_GET` is not escaped) in PreparePHP_SELF.fnc.php
- Use DBUpsert() function & simplify logic in SaveEnrollment.fnc.php
- SQL exclude current record when checking dates for existing enrollment in SaveEnrollment.fnc.php
- SQL include records without dropped dates when checking for existing enrollment in SaveEnrollment.fnc.php
- Deprecate SaveData() function in SaveData.fnc.php
- Add Hide Headers option in Letters.php
- Show date inputs even if admin profile cannot edit in AccessLog.php
- CSS FlatSIS theme fix left menu logo below top menu bar when fixed in stylesheet.css

Changes in 11.4.4
-----------------
- Fix SQL transcript_grades view for History Marking Periods of existing school years in rosariosis.sql, rosariosis_mysql.sql & Update.fnc.php
- Fix regression since 11.4 check MP exists before trying to get GPA: include History Marking Periods in Grades.fnc.php
- Fix PHP Fatal error call to undefined function pg_fetch_array() in database.inc.php & AccessLog.php

Changes in 11.4.3
-----------------
- Fix SQL update gradebook_assignment_types only for old teacher's assignments in Courses.fnc.php
- Fix SQL Get all assignment types having assignments for this course period in GradebookBreakdown.php, Assignments.php, Grades.php & Courses.fnc.php
- Fix only requested modname logic in AccessLog.php

Changes in 11.4.2
-----------------
- Course Teacher change: Update teacher's assignments in Courses.fnc.php

Changes in 11.4.1
-----------------
- Fix SQL error invalid input syntax for type integer: "" in Courses.php
- InputDivOnclick() Do not not convert single quotes to gain a few bytes in Inputs.php
- Add `functions/PDF.php|pdf_stop_html` & `functions/PDF.php|pdf_stop_pdf` action hooks in Actions.php & PDF.php
- Fix PHP warning use reset() instead of guessing if $errors[1] is set in ErrorMessage.fnc.php & diagnostic.php
- Add arguments to Action tags PHPDoc in Actions.php
- Add `Students/Letters.php|header` action hook in Actions.php & Letters.php
- Add `Grades/HonorRoll.php|header` action hook in Actions.php & HonorRoll.php
- Add `ProgramFunctions/Template.fnc.php|before_get` & `ProgramFunctions/Template.fnc.php|before_save` action hooks in Actions.php & Template.fnc.php

Changes in 11.4
---------------
- Add XRedirectUrl JS global var for soft redirection when not an AJAX request in PreparePHP_SELF.fnc.php & warehouse.js
- JS Fix double HTML character encoding, use jQuery text instead of jQuery html in warehouse.js
- PostgreSQL fix limit set_updated_at_triggers() to current schema in rosariosis.sql
- SQL add Comment to every grade of the Main scale in rosariosis.sql & rosariosis_mysql.sql
- Add Chrome OS to GetUserAgentOS() in UserAgent.fnc.php
- Add 'SELECT_ONLY' to $extra param in GetStaffList.fnc.php
- SQL performance: limit main query to teacher profile in GetTeacher.fnc.php
- Smart cache: do not get ALL users database twice if Teacher not found in GetTeacher.fnc.php
- 'ParseMLField' can be used as a DBGet() callback function to parse column in ParseML.php
- Breaking change: remove use of 'next_modname' request param in Search.fnc.php, Search.inc.php & various programs
- Remove use of `$_SESSION['_REQUEST_vars']` in MyReport.php, Schedule.php, ClassSearchWidget.fnc.php, Calendar.php, CopySchool.php, Search.inc.php & Export.php
- Simplify use of `$_SESSION['Back_PHP_SELF']` in MyReport.php, ClassSearchWidget.fnc.php, Search.inc.php & Export.php
- Do NOT rely on SubmitButton() name param, use modfunc instead in DailyMenus.php, EditReportCardGrades.php, StudentAssignments.php, Calendar.php & Schools.php
- Fix isPopup() for Calendar Events new modfunc detail_save & detail_delete in Warehouse.js
- Use 'delete_ok' URL param instead of submit button name in Prompts.php
- DeletePrompt() Go back in browser history on Cancel (unless $remove_modfunc_on_cancel = false) in Prompts.php
- Rename student checkbox from 'student' to 'st' to gain space in URL in MassDrops.php
- User is not logged in, redirect to login screen in Help.php
- Fix SQL error when USERNAME length > 100 in index.php
- Automatically clear Access Log entries older than one year in AccessLog.php
- Only list events for selected month by default in Calendar.php
- Use DBUpsert() in Comments.inc.php
- Set Moodle course end date (enddate parameter) in plugins/Moodle/Scheduling/Course.php
- PHP error reporting: use php.ini configuration if debug mode not set in Warehouse.php
- Add --single-transaction flag to mysqldump so database remains in a consistent state throughout the backup in DatabaseBackup.php
- Reassign Course Subject (unless Moodle plugin is active) in Courses.php & Courses.fnc.php
- GetGpaOrTotalRow() Deprecate $courses_number param in Grades.fnc.php, ReportCards.fnc.php & Transcripts.fnc.php
- GetGpaOrTotalRow() Fix check MP exists before trying to get GPA in Grades.fnc.php
- Add Group courses by subject (only for Report Cards) in ReportCards.fnc.php
- Add "Use Grade Scale Comments" option in InputFinalGrades.php
- Update French & Spanish translations in rosariosis.po
- Remove Social Security field from Substitutions, already added by SubstitutionsCustomFields() in Transcripts.fnc.php
- Fix SQL error Field 'gp_passing_value' doesn't have a default value in ReportCardGrades.php
- Make functions: simplify setting $id var, program wide
- Prepare jQuery Form to FormData transition: check for `$_FILES[ $input ]['name']` in FileUpload.fnc.php, Accounting/functions.inc.php, Assignments.php, PortalNotes.php, Student_Billing/functions.inc.php, Student.php & User.php
- Attach multiple files in StudentAssignments.fnc.php
- Fix file upload for new Contact/Address in Student.php & Address.inc.php
- Add "Student Info tab fields after" action hook: Add your own fields in Actions.php & Other_Info.inc.php
- Add "User Info tab fields after" action hook: Add your own fields in Actions.php & Other_Info.inc.php
- Fix NoInput() display '-' when $value is false in Inputs.php
- Add "Report a bug" link to Resources in rosariosis.sql, rosariosis_mysql.sql, rosariosis_es.sql & rosariosis_fr.sql
- Fix SQL error Data too long for column 'title' in Menus.php
- Remove trailing '-main' from add-on directory in Modules.inc.php & Plugins.inc.php
- Add "Filter Grade Level" action hook in Actions.php & GetGrade.fnc.php
- Replace `_makeReadMe`, `_makeActivated` & `_delTree` functions with `AddonMakeReadMe`, `AddonMakeActivated` & `AddonDelTree` in Addon.fnc.php, Modules.inc.php & Plugins.inc.php

Changes in 11.3.3
-----------------
- Fix PHP 8.1 fatal error: array_key_exists() arg 2 ($array) must be of type array in ListOutput.fnc.php

Changes in 11.3.2
-----------------
- Fix PHP fatal error checkdate(): arg must be of type int, string given in Date.php
- Fix SQL error when amount has exponent, ie "10e-2" in Expenses.php, Incomes.php, Salaries.php, StaffPayments.php, MassAssignFees.php, MassAssignPayments.php, StudentFees.php & StudentPayments.php
- Set "Parent" as default Profile option (least compromising option) in Users/includes/General_Info.inc.php
- Simplify code for requested 'type' & remove use of `$_SESSION['_REQUEST_vars']` in Accounts.php, ActivityReport.php, BalanceReport.php, Reminders.php, ServeMenus.php, Statements.php & Transactions.php
- Fix maintain current student ID when multiple tabs are open in Eligibility/Student.php, Accounts.php, ServeMenus.php, Transactions.php, Student_Billing/functions.inc.php, StudentFees.php & StudentPayments.php
- Fix maintain current user ID when multiple tabs are open in Accounting/functions.inc.php, Salaries.php, StaffPayments.php, Transactions.php, Accounts.php, ServeMenus.php & Exceptions.php

Changes in 11.3.1
-----------------
- HTML use number input for "Base Grading Scale" in Schools.php
- Always add raw Datetime inside HTML comment in Date.php
- JS fix for IE 11 remove function param default value in jquery-passwordstrength.js & warehouse.js
- Fix regression since 4.7 SQL set_class_rank_mp() Class Rank calculation when various school years in rosariosis.sql, rosariosis_mysql.sql & Update.fnc.php

Changes in 11.3
---------------
- Immunization or Physical (select): No N/A value for existing entries in StudentsUsersInfo.fnc.php
- Nurse Visit (date): No N/A value for existing entries in StudentsUsersInfo.fnc.php
- Medical Alert: Required TITLE value for existing entries in StudentsUsersInfo.fnc.php
- SQL add NOT NULL for student_medical type, student_medical_alerts title & student_medical_visits school_date columns in rosariosis.sql & rosariosis_mysql.sql
- Replace ExcelXML class with SimpleXLSXGen in classes/ExcelXML.php & classes/SimpleXLSXGen/
- Export list to Excel using SimpleXLSXGen (more reliable) in ListOutput.fnc.php
- Fix list sort, search, page, save when multiple lists on same page in ListOutput.fnc.php
- Remove null values from URL in PreparePHP_SELF.fnc.php
- We are inside a sentence, convert nouns to lowercase (except for German) in ListOutput.fnc.php & Prompts.php
- No "Default for Teacher" checkbox if Type is "Office Only" in AttendanceCodes.php
- Fix incoherence with AllowEdit() when category_id present or not in URL in Student.php & User.php
- Refuse to enroll student twice in the same course period in Schedule.php & MassSchedule.php
- Update French & Spanish translations in rosariosis.po & help.po
- Add Percent grade inside HTML comment so we can accurately sort by Grade column in InputFinalGrades.php
- No new days inputs if Edit not allowed in DailyMenus.php
- Allow non admin users & students to Generate Menu (no AllowEdit() required) in DailyMenus.php
- Simplify logic to save Menu ID to session in Kiosk.php & MenuReports.php
- Fail if Marking Periods are not in current School Year in FinalGrades.php & ReportCards.fnc.php
- Fix "Daily Absences" / "Other Attendance" strings in ReportCards.fnc.php
- Moodle plugin: only roll Users & Courses once in Rollover.php & plugins/Moodle/README.md
- Contact not found, remove person_id & redirect URL in Address.inc.php
- Rename `$grades_RET[$i]` var to `$grade_i` inside foreach loop in ReportCards.fnc.php
- MySQL dump: export procedures, functions and triggers in DatabaseBackup.php
- Rename `_makeCheckBoxInput()` to `_makeCheckboxInput()` & `CheckBoxInput()` to `CheckboxInput()` in AttendanceCodes.php, EditReportCardGrades.php & EnrollmentCodes.php
- CSS responsive fix Stackable tables: columns always align left in zresponsive.css
- Update Installation Directions for Ubuntu 22.04 in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Move MySQL step which accidentally was under PostgreSQL instructions in INSTALL_fr.md & INSTALL_fr.pdf
- SQL do not round GPA average in Grades/includes/Dashboard.inc.php
- CSS add .rseparator class responsive separator: hide text separator & break line in zresponsive.css & InputFinalGrades.php
- CSS add .addon-readme class Add-on module/plugin README inside colorBox in stylesheet.css
- Use DBUpsert() & check for required columns on INSERT in Medical.inc.php
- Fix use DBUnescapeString() to unescape string for both PostgreSQL & MySQL in Config.fnc.php
- Fix sorting list when first value is null in ListOutput.fnc.php
- JS fix rt2colorBox children height calculation in warehouse.js
- CSS fix tooltip text wrapping inside responsive list in stylesheet.css
- JS Do NOT scroll to top onload in warehouse.js
- To disable responsive list layout, add `&LO_disable_responsive=Y` to the URL in ListOutput.fnc.php

Changes in 11.2.4
-----------------
- Fix DisplayName() for "Last Name First Name" & "Last Name, First Name" options in GetStuList.fnc.php
- Fix Mailing Label skip if No Contact tied to address in MailingLabel.fnc.php
- MySQL 8+ fix infinite loop due to cached AUTO_INCREMENT in database.inc.php

Changes in 11.2.3
-----------------
- Security fix #341 update phpwkhtmltopdf, php-shellcommand & php-tmpfile in classes/phpwkhtmltopdf
- Fix PHP warnings, force search_modfunc to list in Bottom.php
- Move Updates for version 6, 8 and 9 in UpdateV6_8_9.fnc.php
- Fix HasFirstLoginForm() when admin did not complete steps on first login in FirstLogin.fnc.php
- SQL fix duplicate key value violates unique constraint "attendance_completed_pkey" in Courses.fnc.php
- Use return instead of exit. Allows Warehouse( 'footer' ) to run in TakeAttendance.php & Grades/Configuration.php
- Fix column name is empty, use Period Title if no Short Name in StudentSummary.php

Changes in 11.2.2
-----------------
- Hide list count in ReportCards.fnc.php, Transcripts.fnc.php & Preferences.php
- Security: SQL prevent INSERT or UPDATE on any table in MassCreateAssignments.php, SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php & UserFields.php
- Adapt missing config table SQL error for MySQL in diagnostic.php
- Fix PHP warnings when course_period_id GET param does not exist in Courses.php
- Fix SQL error foreign keys: force roll Schools in Rollover.php

Changes in 11.2.1
-----------------
- ProgramUserConfig(): SQL can use negative $staff_id for Students in Config.fnc.php
- Excel & CSV: replace line breaks (br) with "\n" instead of space in ListOutput.fnc.php
- Only show header if more than 1 school in Users/includes/Schedule.inc.php
- Remove start & end date params from GET form URL in Expenses.php, Incomes.php & StudentSummary.php
- Fix SQL syntax error at or near "ND" in DeleteTransactionItem.fnc.php
- SQL add FOREIGN KEY to transaction_id column in rosariosis.sql & rosariosis_mysql.sql
- SQL Add MENU_ITEM_ID column to food_service_transaction_items & food_service_staff_transaction_items tables in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Fix regression since 11.2 SQL error duplicate key value violates unique constraint in ServeMenus.php
- FS transaction menu item ID references food_service_menu_items(menu_item_id) in ServeMenus.php

Changes in 11.2
---------------
- Add $add_post argument, POST parameters to add to the URL (optional) in Prepare_PHP_SELF.fnc.php
- Fix Apache 414 Request-URI Too Long, move st from `$_REQUEST` to `$_SESSION` in FinalGrades.php
- Add `$_POST` elements, ytd_tardies_code, mp_tardies_code & mp_arr to URL in FinalGrades.php
- CSS WPadmin theme remove bold from MD preview tabs in stylesheet.css
- SQL Add CREATED_BY column to billing_fees & billing_payments tables in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Expanded View: Add Created by & Created at columns in MassAssignFees.php, MassAssignPayments.php, StudentFees.php, StudentPayments.php & DailyTransactions.php
- Do NOT create Assignment Type if already exists for Course & Teacher in MassCreateAssignments.php
- Add RosarioURL() function in Prepare_PHP_SELF.fnc.php
- Use RosarioURL() instead of local function in PasswordReset.php, MarkDownHTML.fnc.php & SendNotification.fnc.php
- Security remove $wkhtmltopdfAssetsPath & --enable-local-file-access, use base URL instead in INSTALL.md, INSTALL_es.md, INSTALL_fr.md & PDF.php
- HTML increase Username input size (suitable for email address) in General_Info.inc.php
- Fix Attendance State Code: rename "Half Day" to "Half" in AttendanceCodes.php, AttendanceCodes.fnc.php & AttendanceSummary.php
- CSS themes adjust tooltip height to fit inside PopTable in stylesheet.css & zresponsive.css
- Fix SQL error when school has no MPs in Rollover.php
- HTML add arrow to indicate sub-option in Configuration.php
- Rename "Registration" fieldset to "Public Registration" in Configuration.php
- Add AttendanceDailyTotalMinutesSQL() & AttendanceDailyTotalMinutesPresent() functions in UpdateAttendanceDaily.fnc.php
- Breaking Change: use AttendanceDailyTotalMinutesPresent() instead of AttendanceDailyTotalMinutes() in UpdateAttendanceDaily.fnc.php
- Add Action hook, filter Total Minutes Present in UpdateAttendanceDaily.fnc.php & Actions.php
- Dynamic Daily Attendance calculation based on total course period minutes in UpdateAttendanceDaily.fnc.php, AttendanceSummary.php & Configuration.php
- Add help text for Dynamic Daily Attendance calculation in Help_en.php
- SQL new default is 0 for ATTENDANCE_FULL_DAY_MINUTES Config option in rosariosis.sql & rosariosis_mysql.sql
- MarkDown: remove two spaces before line break in Markdownify/Converter.php
- Only allow column names of string type (not empty) in DBUpsert.php
- Fix SQL error when $columns is false in DBUpsert.php
- Update French & Spanish translations in rosariosis.po & help.po
- CSS themes apply list margin/padding outside widefat tables + fix .size-1 in stylesheet.css
- HTML Fix Menu description overflow hidden + allow MarkDown in DailyMenus.php
- Add Action hook Filter each menu item in the loop in Kiosk.php & Actions.php
- FS transaction item ID references food_service_menu_items(menu_item_id) in ServeMenus.php, rosariosis.sql & rosariosis_mysql.sql
- CSS WPadmin theme style fieldset legend like FlatSIS theme in stylesheet.css
- Fix Select Multiple from Options answer count when none selected in PortalPollsNotes.fnc.php
- Fix regression since 11.0 Reporter not saved when logged in as a Teacher in MakeReferral.php
- Fix SQL error when no MP array is empty in Grades.fnc.php
- Add Student Account fields table after action hook in Actions.php & Food_Service/Students/Accounts.php
- Add Food Service tab fields table after action hook in Actions.php & Food_Service/Student.inc.php

Changes in 11.1.2
-----------------
- Maintain list preferences as GET params in form URL (save) in Schedule.php
- Use basename() to extract file name from path in StudentsUsersInfo.fnc.php, Accounting/functions.inc.php, StudentAssignments.fnc.php & Student_Billing/functions.inc.php
- Fix PHP warning undefined array index in RegistrationAdmin.fnc.php
- CSS WPadmin theme style button text like FlatSIS theme in stylesheet.css

Changes in 11.1.1
-----------------
- Fix PostgreSQL error column "ac" of relation "attendance_completed" does not exist in Courses.fnc.php

Changes in 11.1
---------------
- Fix SQL error when no MPs in calcSeats0.fnc.php
- Choose Checkbox uncheck by default to prevent accidental creations in MassCreateAssignments.php
- HTML display Courses list next to Assignment Types list in MassCreateAssignments.php
- Set email for default admin user so he can reset his password in InstallDatabase.php
- Prevent using App name, username, or email in the password in PasswordReset.php, FirstLogin.fnc.php, Inputs.php & Preferences.php
- JS Add userInputs param to prevent using App name, username, or email in the password in jquery-passwordstrength.js
- Return EMAIL column for students too (empty if "Student email field" not set) in User.fnc.php
- Fix SQL error if delete Student email field, reset in StudentFields.php
- Add SideMarkingPeriodSelect() function in Side.php
- SQL Use GetFullYearMP() & GetChildrenMP() functions to limit Marking Periods in Grades/Configuration.php, ReportCards.fnc.php, Courses.php, MassDrops.php, MassSchedule.php & PrintSchedules.php
- Allow override GetFullYearMP(), GetAllMP(), GetParentMP(), GetChildrenMP() & GetCurrentMP() functions in GetMP.php
- SQL set min Credits to 0 & fix division by zero error in Courses.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- SQL Update to version 11.1 in Update.fnc.php
- Add timestamp (including microseconds) to filename to make it harder to predict in FileUpload.fnc.php, Accounting/functions.inc.php, StudentAssignments.fnc.php, PortalNotes.php, Student_Billing/functions.inc.php
- Copy $DefaultSyear global var to session (once) to prevent errors when edited in Warehouse.php & Config.fnc.php
- Add "Probably a module. Move it to the modules/ folder." error in Plugins.inc.php
- Make Course Periods number link in Periods.php
- Move `_updateSchedulesCPMP()` to includes/Courses.fnc.php & rename CoursePeriodUpdateMP() in Courses.php & Courses.fnc.php
- Automatically update teacher: attendance_completed + grades_completed in Courses.php & Courses.fnc.php
- Automatically update credits (attempted and earned); will also recalculate GPA in Courses.php & Courses.fnc.php
- Update French & Spanish translations in rosariosis.po
- Update Recommended PHP configuration in INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- CSS WPadmin: remove opensans EOT font files in font.css & `themes/WPadmin/fonts/opensans/*.eot`
- CSS themes: reduce list margin & padding when inside .widefat table in stylesheet.css
- CSS themes: force .tooltip styles when inside .widefat table head in stylesheet.css
- Fix SQL error null value in column "student_id" in Eligibility/Student.php
- Fix SQL error Unknown column 'a.ADDRESS' in 'where clause' in Percent.php
- CSS Fix PDF Frame background when multiple pages in HonorRoll.fnc.php
- Fix SQL error invalid input syntax for type integer: "" in Registration.fnc.php & RegistrationSave.fnc.php

Changes in 11.0.2
-----------------
- Fix SQL error escape Menu title in MenuReports.php & TakeMenuCounts.php
- MySQL fix specify COUNT column name in MenuReports.php
- Remove "-master" suffix from manually uploaded add-ons in Modules.inc.php & Plugins.inc.php
- Fix SQL error, Check if Account ID already exists in Student.php & Food_Service/Students/Accounts.php

Changes in 11.0.1
-----------------
- Fix PHP deprecated passing null to parameter #1 ($datetime) of type string in Warehouse.php
- MySQL fix infinite loop, emulate PostgreSQL's nextval() in database.inc.php
- Fix Apache 414 Request-URI Too Long, use POST method instead of GET in Administration.php
- Fix display result count when no singular/plural set in ListOutput.fnc.php
- Fix SQL drop order by School Period, allow Course Periods with no Periods in ReportCards.fnc.php

Changes in 11.0
---------------
- Add Weight Assignments option in Grades/Configuration.php, Assignments.php & MassCreateAssignments.php
- Add Weight field in Assignments.php & MassCreateAssignments.php
- Calculate Weighted Grade in Grades.php, InputFinalGrades.php & ProgressReports.php
- Add Assignment Weight in StudentGrades.php & StudentAssignments.fnc.php
- Translate "Zip" & "Zipcode" to "Zip Code" in GetStuList.fnc.php, MyReport.php, Registration.fnc.php, Schools.php & Address.inc.php
- HTML Text input min size is 2 in Inputs.php
- Skip School Period column if has no students scheduled for selected date in Administration.php
- Add microseconds to filename format to make it harder to predict in FileUpload.fnc.php, Accounting/functions.php, PortalNotes.php & Student_Billing/functions.php
- Remove UserCoursePeriodSchoolPeriod() & UserPeriod() functions in Current.php
- SQL access_log: deprecate LOGIN_TIME column, use CREATED_AT instead in index.php, AccessLog.php, Dashboard.inc.php, rosariosis.sql & rosariosis_mysql.sql
- SQL portal_notes & portal_polls: deprecate PUBLISHED_DATE column, use CREATED_AT instead in PortalNotes.php, PortalPolls.php, Portal.php, rosariosis.sql & rosariosis_mysql.sql
- Truncate column title to 36 chars if > 36 chars in MyReport.php & Grades.php
- Handle Points decimal with comma instead of point, ie "10,5" in Grades.php
- Increase default Password Strength from 1 to 2 in rosariosis.sql & rosariosis_mysql.sql
- PostgreSQL rename "rank" to "class_rank" in set_class_rank_mp() for consistency with MySQL in rosariosis.sql
- HTML put "" around the link href if no spaces in $link & no other attributes in Buttons.php & program wide
- HTML5 CSS use details + summary instead of .toggle hack in index.php, stylesheet.css & colors.css
- SQL add TITLE column in GetTeacher.fnc.php
- Cache Class average percent in Grades.fnc.php
- Add GetClassRank() function (for Course Period) in Grades.fnc.php
- Add Class Average & Class Rank (Course Period) in ReportCards.fnc.php
- Move "Progress Reports" from Teacher Programs to Grades menu (admin) in Grades/Menu.php, ProgressReports.php, rosariosis.sql & rosariosis_mysql.sql
- CSS FlatSIS enlarge left menu width by 6px for German in stylesheet.css
- SQL add ORDER BY to GetChildrenMP() in GetMP.php
- SQL select Grading Scales by Teacher, only the ones having student grades in GradeBreakdown.php
- Add Insert into or Update DB functions in DBUpsert.php
- Use DBUpsert() function in Template.fnc.php, Config.fnc.php & StudentAssignments.fnc.php
- Use DBInsertSQL() function in AddActivity.php, Scheduler.php, Calendar.php, MarkingPeriods.php, MassAssignFees.php & MassAssignPayments.php
- Use DBUpdateSQL() function in MarkingPeriods.php
- Use DBUpdate() & DBInsert() functions, program wide
- Create missing Food Service Student Account in Accounts.php
- Fix SQL display Students with no Food Service account in the list in Accounts.php
- Fix PHP warning if Food Service Student Account missing in Student.inc.php, Accounts.php, ServeMenus.php, Statements.php & Transactions.php
- Let user edit inputs on Food Service User Account creation in Accounts.php
- Fix Contact file path (delete) in Student.php
- Upload Address & Contact files in RegistrationSave.fnc.php
- Security: use URLEscape() for PHP redirection in index.php & Portal.php
- Use `$_REQUEST['search_terms']` to allow GET param in URL in Courses.php
- Move from serialize() to json_encode() in StudentAssignments.fnc.php & Comments.inc.php
- SQL calculate Class Rank for Progress Periods in ReportCards.php
- SQL Fix replacement in case the "username" contains the prefix in Removeaccess.php, thanks to @0xD0M1M0
- SQL add accounting_categories table in rosariosis.sql & rosariosis_mysql.sql
- Add Categories program to Accounting module in Categories.php, Menu.php, rosariosis.sql & rosariosis_mysql.sql
- Add `_makePaymentsCategory()` & `_makeIncomesCategory()` functions in Accounting/functions.inc.php
- Reuse `_makeIncomesTextInput()` function in `_makePaymentsTextInput()` in Accounting/functions.inc.php
- Use button only for File Attached input & download in Accounting/functions.inc.php & Student_Billing/functions.inc.php
- Add Timeframe start / end date filters in Expenses.php & Incomes.php
- Add Category column in Expenses.php & Incomes.php
- Add Title column in Expenses.php
- Fix display Go button for all users (no AllowEdit() required) in DailyTotals.php & DailyTransactions.php
- Add Category filter in DailyTransactions.php
- SQL Update v11.0: Move "Progress Reports" from Teacher Programs to Grades menu (admin) in Update.fnc.php
- SQL Update v11.0: Add accounting_categories table in Update.fnc.php
- SQL Update v11.0: Add TITLE column to accounting_payments table in Update.fnc.php
- SQL Update v11.0: Add CATEGORY_ID column to accounting_incomes & accounting_payments tables in Update.fnc.php
- SQL Update v11.0: Give admin profile access to Accounting > Categories program in Update.fnc.php
- Add & translate help texts for Weight Assignments option in Help_en.php, help.pot & help.po
- Translate Weight Assignments option to French & Spanish in rosariosis.pot & rosariosis.po
- CSS Add .rbr class, responsive `<br>` does not break line, use inside responsive table in zresponsive.css, DisciplineForm.php & Student_Billing/functions.inc.php
- Enable Test Mode by default in case of accidental run in Scheduler.php
- Fix PHP fatal error if openssl PHP extension is missing in Warehouse.php, index.php, Student.php & User.php
- MySQL fix not single MP check in MarkingPeriods.php
- Fix exception strtotime() returns false for year >= 2038 (PHP 32-bit) in Date.php


### Old versions CHANGES
- [CHANGES for versions 9 and 10](CHANGES_V9_10.md).
- [CHANGES for versions 7 and 8](CHANGES_V7_8.md).
- [CHANGES for versions 5 and 6](CHANGES_V5_6.md).
- [CHANGES for versions 3 and 4](CHANGES_V3_4.md).
- [CHANGES for versions 1 and 2](CHANGES_V1_2.md).
