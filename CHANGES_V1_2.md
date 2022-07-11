# CHANGES for versions 1 and 2
## RosarioSIS Student Information System

Changes in 2.9.15
-----------------
- Security: update PHPMailer to version 2.9.21
- Fix #176 SQL error More than 1 row returned by a subquery in Rollover.php
- Add Attendance Chart + Student Summary to Teachers help in Help\_en.php & Help_es.php
- Fix footer help text disappearing when bottom menu updated in warehouse.js
- Add French help in Help_fr.php
- Merge PR #178 Add Khmer translation thanks to @lkozloff
- Include theme's scripts.js file (optional) in Warehouse.php
- Add percent sign (%) to grade & semester fields in Grades/Configuration.php
- Fix PHP error division by zero in InputFinalGrades.php
- Fix Show Go button to Parents & Students in StudentList.php
- Add Anomalous Grades help texts for teachers in Help_*.php

Changes in 2.9.14
-----------------
- Add "No courses found" error in Side.php
- Fix gettext bug when string is '.' in ParseML.php
- Add SELECT\_OPTIONS column to school_fields table in Update.fnc.php & rosariosis.sql
- Add School Field types in Schools.php & SchoolFields.php (sponsored by Aptiris)
- Add help for School Fields in Help\_en.php & Help_es.php
- Get autos / edits pull-down edited options: fix $field var name in AssignOtherInfo.php
- _makeMultipleInput(): Fix div ID in StudentsUsersInfo.fnc.php
- Fix Save Select multiple from options field in User.php
- Admin User Profile restriction in Profiles.php, Exceptions.php, User.php & General_Info.inc.php (sponsored by Aptiris)
- Update database for Admin User Profile restriction in rosariosis.sql & Update.fnc.php
- Merge PR #175 Update AttendanceSummary.php, fix #174, thanks to @lkozloff

Changes in 2.9.13
-----------------
- Always use SchoolInfo() instead of querying schools DB table, programwide
- Unset current student after setting new current school in Schools.php & CopySchool.php
- Check if Update() version < ROSARIO_VERSION in Update.fnc.php
- Admin Schools restriction in Profiles.php, Exceptions.php, User.php & General_Info.inc.php (sponsored by Aptiris)
- Update database for Admin Schools restriction in rosariosis.sql & Update.fnc.php
- Restrict Search All Schools to user schools (sponsored by Aptiris)

Changes in 2.9.12
-----------------
**Warning**: consequently to commit 2eaee53c6e9f5d9e7bfa24be859e9c711de88b39
please also upgrade the [Students Import](https://gitlab.com/francoisjacquet/Students_Import) & [Reports](https://gitlab.com/francoisjacquet/Reports) add-on modules.

- Order Schools list by title in Users/includes/General_Info.inc.php & Side.php (sponsored by Aptiris)
- Add Open Sans CSS to stylesheet_wkhtmltopdf.css
- Add Force Default Theme option in Configuration.php & User.fnc.php
- Fix Contact info fields display, with(out) auto-pull-downs or AllowEdit in Address.inc.php
- Add Custom/Registration.php program (sponsored by @dpredster)
- Display General Info's tab custom fields (Other Info) in Registration.php
- When -Edit- option selected, change the Address auto pull-downs to text fields in Address.inc.php (sponsored by Aptiris)
- Fix #173 resend login form: redirect to Modules.php in index.php
- Fix Recreate Calendar defaults + copy Calendar weekdays in Calendar.php
- New: check for Title, programwide
- Not new: Title is required, programwide

Changes in 2.9.11
-----------------
- Put Course Periods back + fixes in ScheduleReport.php
- Fix PHP fatal error: check Include file exists in StudentFields.php & UserFields.php
- Fix SQL error unterminated quoted string at or near "'1 in EditReportCardGrades.php
- Added common file types to upload white list in PortalNotes.php & StudentAssignments.fnc.php
- Help texts updates in Help_en.php & Help_es.php

Changes in 2.9.10
-----------------
- Schedule multiple courses in MassSchedule.php (sponsored by Aptiris)
- Wrap phone inside tel dial link in GetStuList.fnc.php
- User Fields: search Email Address & Phone in CustomFields.fnc.php, GetStaffList.fnc.php, Search.fnc.php & Preferences.php
- Add makeEmail() function in GetStuList.fnc.php
- Help texts updates in Help_en.php & Help_es.php
- Fix SQL error table "a" specified more than once when searching Address in MyReport.php
- Add makeFieldTypeFunction() function in GetStuList.fnc.php

Changes in 2.9.9
----------------
- Fix PHP error when no options set for multiple field in Referral.php & MakeReferral.php
- Fix SQL error when all-day checked & minutes in Calendar.php
- Fix SQL error when more than one Rollover default enrollment code in Rollover.php
- Add warning & help for Rollover default enrollment code in EnrollmentCodes.php
- Fix Delete Prompt displayed when working User/Student cleared program wide
- Add Warning if not in current Quarter in Assignments.php
- Check IDs are valid for current school & syear in MarkingPeriods.php & Assignments.php
- Help texts updates in Help_en.php & Help_es.php

Changes in 2.9.8
----------------
- Fix Menu default program when not allowed in Menu.php
- Add "Students/Student.php|account_created" action hook in Student.php
- Add "index.php|login_check" action hook in index.php
- Fix Create Account (was not redirecting to index) (regression since 2.9.2) in Warehouse.php
- Adapt Warehouse( 'footer' ) & use it on non Modules pages in Warehouse.php

Changes in 2.9.7
----------------
- Update Parsedown class to version 1.6.0 in classes/Parsedown.php
- Update PHPMailer class to version 5.2.16 in classes/PHPMailer/
- Actions API simplified: register your custom action tag on the fly in Actions.php
- Fix no JS loaded regression (since 2.9.2) in Create Account pages in Warehouse.php
- Hotfix SQL error when new subject ID

Changes in 2.9.6
----------------
- Fix #157 Delete Period when days unchecked in Courses.php
- Fix Check subject ID is valid for current school & syear in Courses.php
- Fix SQL error invalid input syntax for type date in Discipline/*Breakdown.php
- Fix #159 Update Help text to drop/remove a course in Help_en.php & Help_es.php
- Fix #161 Letter Grade Widget search terms when combined in Widgets.fnc.php
- Fix PHP7 error 'continue' not in the 'loop' or 'switch' context

Changes in 2.9.5
----------------
- Fix #152 Cookie on localhost/ (root path) and IE in Warehouse.php
- Add jQuery Chosen 1.5.1 plugin in assets/js/jquery-chosen
- Add ChosenSelectInput() function in Inputs.php
- Add "Limit Existing Contacts & Addresses to current school" global setting in Configuration.php (sponsored by Aptiris)
- Use Chosen for multiple select inputs in MakeReferral.php & Transcripts.php

Changes in 2.9.4
----------------
- Limit ListOutput() results to 1000 in ListOutput.fnc.php
- Add Dates Formats: DD-MM-YYYY, DD-MM-YY (European) & MM/DD/YYYY (US) to ExplodeDate() in Date.php
- Gender & Ethnicity Student Fields not Required by default in rosariosis.sql
- Add user friendly AJAX error messages in warehouse.js & Warehouse.php & stylesheet.css
- JS code optimizations in warehouse.js
- JS: show loading spinner when loading Help in warehouse.js
- Spam fix: send plain text email along with HTML & set Reply To instead of From in SendEmail.fnc.php

Changes in 2.9.3
----------------
- Force email fields check using HTML5 input's email type, pattern & placeholder in General_Info.inc.php & StudentsUsersInfo.fnc.php
- Replace jQuery ScrollToFixed plugin with jQuery FixedMenu in assets/js/jquery-fixedmenu/
- Fix XMLRPC error with Moodle 3.1 in plugins/Moodle/client.php
- Update Default School Year to 2016 in rosariosis.sql & config.inc.sample.php
- Moodle plugin configuration, Student email field: select input + Username
- Moodle plugin fix: do not save idnumber for courses categories in Courses.php
- Fix switch to previous Syear with current UserStaff (not rolled) in Side.php

Changes in 2.9.2
----------------
- If Admin Profile updated, reload menu in Profiles.php
- Add page CSS class to body & always use Warehouse( 'header' )
- Remove inline CSS & use CSS classes program wide
- Select all School Years in Transcripts.php
- Set Payment Date in StudentPayments.php & MassAssignPayments.php
- Set Date in Incomes.php, Expenses.php & StaffPayments.php
- Add Minimum Passing Grade option to Grade Scales in ReportCardGrades.php & InputFinalGrades.php
- Place RosarioSIS version, disclaimer & copyright inside "About" toggle in index.php

Changes in 2.9.1
----------------
- Add Open Sans webfont in assets/themes/WPadmin/fonts/opensans/ & stylesheet.css
- Use ReportCards.fnc.php functions in FinalGrades.php
- Add TipMessage with Student YTD & Period attendance in FinalGrades.php
- Fix logo image overlapped in Report Cards
- Remove isset & empty checks for $_REQUEST['modfunc'], program wide

Changes in 2.9
--------------
- Add Debug mode as optional config.inc.php option in Warehouse.php + INSTALL
- Bugfix Postgres datestyle = 'iso, ymd', programwide
- Add Parsedown 1.5.3 class (MarkDown parser) in classes/Parsedown.php
- Add MarkDownToHTML() function to parse MarkDown text in ProgramFunctions/MarkDownHTML.fnc.php
- Add showdown.js 1.3.0 (MarkDown to HTML) in assets/js/showdown/
- Add MarkDownInputPreview() functions to preview textarea fields in functions/Inputs.php & warehouse.js
- Add MarkDownToHTML() functions to parse MarkDown text in warehouse.js & warehouse_wkhtmltopdf.js
- Add MarkDown button image in assets/themes/WPadmin/btn/md_button.png
- Add Security class in classes/Security.php
- Add SanitizeMarkdown() function in ProgramFunctions/MarkDownHTML.fnc.php
- Add Markdownify 2.1.11 class (convert HTML back to MarkDwon) in classes/Markdownify/
- Add RequestedDate() & RequestedDates() functions in Date.php
- Add .logo CSS class & bigger logo.png definition
- Remove closing PHP tags ?> at end of files program wide
- Load body after browser history in warehouse.js
- Use Heredoc & comment Help texts in Help_en.php & Help_es.php
- Add .loading CSS class to show multiple spinners at once
- Add course_period_school_periods_id to course_period_school_periods table primary key in rosariosis.sql
- Add Update() function in ProgramFunctions/Update.fnc.php
- Rework Student Comments: serialize, MarkDown in Students/includes/Comments.inc.php
- CHANGES, INSTALL, WHATS_NEW, themes, plugins & modules README files: use MarkDown
- Move from includeOnceColorBox() to ColorBox to plugins.min.js
- Update ColorBox to version 1.6.3 in assets/js/colorbox/
- Remove functions/IncludeOnce.php file
- Add all jqPlot plugins & excanvas (IE version<9 compat) in assets/js/jqplot
- Regroup Charts (jqPlot) functions in ProgramFunctions/Charts.fnc.php
- Add Module Title to Menu.php files
- ajaLink() JS function now directly accepts URLs in warehouse.js & program wide
- Display Address & People fields when adding new in Address.inc.php
- Add .fixed-col CSS class for fixed width tables
- Reduce PNG images / icons size using CompressPNG.com program wide
- Show Assignment Type color in Grades.php
- Add ProgramConfig() function in Config.fnc.php
- Add space after control structures keywords: if, for, foreach, while, switch
- Add spaces before and after arrow: => (foreach & associative arrays)
- Always use require_once in place of include & require, program wide
- HTML tags to lowercase, program wide
- Move JSCalendar setup to warehouse.js
- Move (Staff)Widgets(), append(Staff)SQL() & CustomFields() functions calls inside GetStaffList() & GetStuList()
- Add popups JS functions to close all popups when Opener AJAX in warehouse.js
- Move Popup window detection to isPopup() for reuse in Warehouse.php
- Add .tooltip CSS class for Tooltips display in stylesheet.css
- Add XML File Export Type option in ListOutput.fnc.php & Preferences.php
- Add GetInputID(), FormatInputTitle() & InputDivOnclick() functions in Inputs.php
- Add jQuery MiniColors plugin & ColorInput() function in assets/js/jquery-minicolors/ & Inputs.php
- Add MakeTipMessage() & MakeStudentPhotoTipMessage() functions in ProgramFunctions/TipMessage.fnc.php
- Add Photo on mouse over Student Name in InputFinalGrades.php & Grades.php
- Highlight color: add CSS to HTML head in Warehouse.php
- Create school_fields_seq Sequence in rosariosis.sql & Update.fnc.php
- Add Fields (and Field Categories) functions in ProgramFunctions/Fields.fnc.php
- Use DeletePrompt() & Prompt() instead of DeletePromptX() & PromptX() program wide
- Add Daily Totals program to Accounting & Student Billing modules
- Add ProgramUserConfig() function in Config.fnc.php
- Improved accessibility (a11y) in Side.php, index.php & Buttons.php
- SendEmail() program function now uses PHPMailer and accepts attachments
- Remove FROM_DUAL constant (not used by PostgreSQL) program wide
- Add jQuery ScrollToFixed plugin in assets/js/jquery-scrolltofixed/
- Add STUDENTS_EMAIL_FIELD to config table in rosariosis.sql & Configuration.php
- Add Password Reset feature in PasswordReset.php
- Update TinyMCE to version 4.3.6 in assets/js/tinymce/
- Add TinyMCEInput() & SanitizeHTML() functions in Inputs.php & MarkDownHTML.fnc.php
- Declare $error, $note, $warning globals in ErrorMessage.fnc.php & program wide
- Add Student Assignments program + related SQL & Help + assets/AssignmentsFiles/ folder (sponsored by Whitesystem)
- Add Assignment Submissions & Help in Grades/Assignments.php & Grades/Grades.php (sponsored by Whitesystem)
- Use ISO date format (YYYY-MM-DD), always in Date.php & program wide
- Add ProperDateTime() function in Date.php
- Add HumanFilesize() function in ProgramFunctions/FileUpload.fnc.php
- Add misc/Portal.php|portal_alerts hook in Actions.php & Portal.php
- Add Bottom.php|bottom_buttons hook in Actions.php & Bottom.php
- Add optional functions.php file for non core modules in Warehouse.php
- Update French & Spanish translations
- Format custom checkbox fields: add makeCheckbox() function in GetStuList.fnc.php
- Format custom textarea fields: add makeTextarea() function in GetStuList.fnc.php
- Add DeCodeds() & StaffDeCodeds() functions in GetStuList.fnc.php & GetStaffList.fnc.php
- Move imported CSS files to css/ folder & minify stylesheet.css in WPadmin/
- Remove Moodle password update via My Preferences in Moodle plugin
- Add debug backtrace to db_show_error() email in database.inc.php

Changes in 2.8.28
-----------------
- Do NOT use GetStuList() otherwise limited to UserStudentID() in PrintClassPictures.php
- curl PHP extension check in diagnostic.php

Changes in 2.8.27
-----------------
- Correctly get Discipline Fields based on user school & year in EmailReferral.fnc.php
- Update Portuguese translation in locale/pt_PT.utf8/ thanks to @adrianomarinho
- Fix Food Service Students Accounts Account ID check + Translate Food Service Discount options
- Fix Food Service User balance widget in StaffWidgets.fnc.php
- Enable empty value when updating Food Service menu item in MenuItems.php
- Order Report Cards by Course title in ReportCards.fnc.php
- Fix SQL error when more than 1 Attendance Period Teacher in Export.php
- Fix typo in INSTALL instructions + update instructions for Ubuntu 14.04
- Fix Custom User Permissions: remove default program in Exceptions.php
- Fix Portal Polls display for Teachers & Parents in Portal.php
- Fix more than 1 row returned SQL error in GPA widget: use REPORTING_GP_SCALE in Widgets.fnc.php
- Fix menu_id's $id var for Menus tabs in DailyMenus.php
- Show Letter & Percent grades (= 0 case) in StudentGrades.php
- Fix PDF when Teacher / Room combined with skip row / line in StudentLabels.php

Changes in 2.8.26
-----------------
- Fix SQL errors when Creating User Food Service account / barcode
- Add Danish & Malaysian locale & flags files
- Allow negative amounts in Student Billing & Accounting modules
- Fix: Limit Accounting Expenses, Incomes, Salaries & Staff Payments to User School
- Give Parents & Students access to the Discipline Referrals #77
- Improve French translation

Changes in 2.8.25
-----------------
- Fix JS error related to jqPlot loading: wrap JS code inside 500ms timeout in *Breakdown.php
- Prepare for State Reports module: Save Report bottom button & modname exception
- Remove State_Reports.zip orginal module archive
- Remove State_Reports from core modules & rename to "Reports"
- Update official site URLs: HTTPS on rosariosis.org
- Fix Delete School Field in SchoolFields.php
- Improve French & update French & Spanish translations

Changes in 2.8.24
-----------------
- Fix SQL error when saving Parent Course Period in Courses.php
- Improve French translations
- Fix 1 option sub-menu height when mouse over Module in stylesheet.css
- Correct English default Help text in Help_en.php
- More explicit Assignment Type deletion Prompt message in Assignments.php
- Fix _delTree() function name typo in Modules.inc.php & Plugins.inc.php
- Create ReferralLog functions for reuse in Discipline/includes/ReferralLog.fnc.php
- Update PDFStart() & PDFStop() functions to enable Save PDF mode in PDF.php
- Add PHPMailer 5.2.14 class (email creation & (SMTP) transport) in classes/PHPMailer/
- Zap programs which are not allowed in Help.php
- Create ReportCards functions for reuse in Grades/includes/ReportCards.fnc.php
- Fix SQL bug MENU_ITEM not null in MenuItems.php
- PDF Save: unique filename in PDF.php

Changes in 2.8.23
-----------------
- Go button display when no Edit Allowed in Calendar.php
- Format Events List descriptions in Calendar.php
- Unset REQUEST students var in session after update in Student.php
- Various corrections for French translation
- Use AJAX after browser history instead of reloading page in warehouse.js
- Only show Block on Calendar Day to non admins if set

Changes in 2.8.22
-----------------
- Fix PHP Warning division by 0 in GradebookBreakdown.php
- Fix Default Points not required in Assignments.php

Changes in 2.8.21
-----------------
- Add Grade Scale default value in EditReportCardGrades.php
- Fix PHP error Invalid argument supplied for foreach() in Courses.php
- Fix spinner image reference in Scheduler.php
- Fixes: Dates not required & Points can be 0 in Assignments.php
- Correct Students Contact query: only if Custody / Emergency checked in GetStuList.fnc.php
- Limit School Years input to 5 past years in EditHistoryMarkingPeriods.php

Changes in 2.8.20
-----------------
- Add Canadian English locale in locale/en_CA.utf8
- Add return_megabytes() & FileUploadMaxSize() functions in FileUpload.fnc.php
- Remove 10Mb limit for Portal Notes file uploads in PortalNotes.php
- Send emails from programname instead of rosariosis in SendEmail.fnc.php
- Bugfix photo name when assign student ID with leading 000 in Student.php
- Add .odt, .ods & .odp (LibreOffice docs) to Portal Notes attached file in PortalNotes.php
- Fix StudentGrades.php student Help text in Help_en.php
- Fix Auto & Edit pull-downs options Advanced Search in Search.fnc.php
- Remove empty option from select fields Advanced Search in Search.fnc.php
- Add values found in current and previous year to Edit pull-downs in AssingOtherInfo.php
- Correct Class Rank field type to checkbox in EditReportCardGrades.php
- Fix Do not include students enrolled in previous school years in GetStuList.fnc.php
- Limit Requests Course DIV height in stylesheet.css

Changes in 2.8.19
-----------------
- Email Discipline Referral feature, sponsored by Hisham Abu Dawoud
- Add 'Last Login' column label in Export.php
- Bugfix do not pass prompt when Cancel in Prompts.php
- Fix bug when email set without any contact + bug when current student in CreateParents.php
- Fix propose to create user in Moodle in Moodle/functions.php
- Add .no-touch CSS class in warehouse.js & stylesheet.css
- Fix #115 SQL bug more than one row returned by a subquery in TakeAttendance.php

Changes in 2.8.18
-----------------
- Bugfix include Edit Pull-Downs when generating options in StudentUsersInfo.fnc.php
- Align inputs (same height) + add .checkbox-label CSS class in stylesheet.css & Inputs.php
- Add .no-input-value CSS class in stylesheet.css & Inputs.php
- Add .textarea CSS class in stylesheet.css & Inputs.php
- Fix advanced search forms (student & user) URL > 2000 chars in warehouse.js
- Better AJAX Courses Requests DIV display in Requests.php
- Fix #114 PHP error Cannot redeclare SendEmail()

Changes in 2.8.17
-----------------
- Fix $function_to_remove var name typo in Actions.php
- Update "Display Options Format" string translation (ES & FR)
- Adjust ProperDate() short month display according to Preferences in Date.php
- Handle IS NOT NULL cases before executing SQL in database.inc.php
- Bugfix SQL error value too long for type varchar(10) in MarkingPeriods.php
- Bugfix force required school period if none in Courses.php

Changes in 2.8.16
-----------------
- Fix SQL error invalid input syntax for type numeric in _makeLetterGrade.fnc.php

Changes in 2.8.15
-----------------
- 2015-2016 School Year update in rosariosis.sql & config.inc.sample.php
- Add Course Period Absences Widget in Widgets.fnc.php sponsored by Hisham Abu Dawoud
- Add Balance field to Advanced Report in Export.php sponsored by LM Idiomes
- Add focus to username field in index.php
- Update french & spanish translations

Changes in 2.8.14
-----------------
- Fix submenuOffset when Side.php reloaded in warehouse.js

Changes in 2.8.13
-----------------
- Fix #106 Comments with quotes ' in Comments.inc.php
- Fix #106 Other_Info category include in Student.php & User.php

Changes in 2.8.12
-----------------
- Fix #102 error language "plpgsql" does not exist in rosariosis.sql
- Fix Minutes when (re)create Calendar in Calendar.php

Changes in 2.8.11
-----------------
- Fix right arrow on menu hover when module text too long in stylesheet.css

Changes in 2.8.10
-----------------
- Add School Configuration english & spanish help texts in Help_en.php & Help_es.php
- Reference the Quick Setup Guide in README.md & INSTALL
- Update stylesheet_wkhtmltopdf.css

Changes in 2.8.9
----------------
- Attendance Codes Type & State Code required in AttendanceCodes.php
- Add Add No Subjects/Courses were found error in ReportCardComments.php
- Update french & spanish translations
- Bugfix ListOutput checkbox column: no sorting icon in stylesheet.css

Changes in 2.8.8
----------------
- Admin menu: div + table to nested ul in Side.php
- Add sub-menu display on mouse over in stylesheet.css & colors.css
- adjust Side.php submenu bottom offset + simplify openMenu() in warehouse.js
- listOutput sorting icon in stylesheet.css
- Use Character arrow instead of CSS arrow in stylesheet.css
- Simplify switchMenu() in warehouse.js
- Add .switchMenu class & remove CSS arrow in Search.fnc.php, StaffWidgets.fnc.php & Widgets.fnc.php

Changes in 2.8.7
----------------
- Fix #101 cookie domain if RosarioSIS installed at server www root in Warehouse.php

Changes in 2.8.6
----------------
- Beautify + JSHint in warehouse.js
- Comment & format, then CSSLint + Autoprefixer in stylesheet.css & colors.css
- Remove abbreviations + change orientation to landscape in AttendanceSummary.php
- Update french & spanish translations
- Concatenate and minify plugins.min.js (jquery.form.js + main16.js + calendar.js + calendar-setup.js)
- Fix SQL error relation "custom" does not exist in rosariosis.sql

Changes in 2.8.5
----------------
- Bugfix SQL error A field with precision 10, scale 2 must round to an absolute value less than 10^8 in *Fields.php, Other_Info.php & *Referrals.php
- Remove table CUSTOM in rosariosis.sql
- Bugfix SQL error syntax error at or near ")" in FinalGrades.php
- Bugfix Invalid argument supplied for foreach() in Schedule.php
- Bugfix Allowed memory size of 134217728 bytes exhausted in AttendanceSummary.php

Changes in 2.8.4
----------------
- Add multiple checkbox fields in ReferralLog.php
- errors if No courses assigned to teacher or No Students were found in GradebookBreakdown.php
- add All Courses & Course-specific comments scales/codes tipmessage in FinalGrades.php & InputFinalGrades.php
- add help for non-core modules in Help.php & Bottom.php
- Remove exit/die/eval in functions, thanks to PHP Mess Detector
- Bugfix check accept cookies in index.php

Changes in 2.8.3
----------------
- bugfix bug when Back to Student Search in StudentSummary.php
- Bugfix conflict staff_id & student_id vars Moodle plugin in AddUsers.php & AddStudents.php
- Check diagnostic.php link after first login in index.php
- Update french, spanish & german translations

Changes in 2.8.2
----------------
- Bugfix unset modfunc even if no values posted
- adapt height if US Letter paper in HonorRoll.php & HonorRollSubject.php
- Bugfix wkhtmltopdf ContentOperationNotPermittedError in HonorRollSubject.php
- send email from rosariosis@[domain] in NotifyParents.php
- Bugfix add Comments as an exception in Student.php
- Check AllowEdit() before saving in MassAssignFees.php, MassAssignPayments.php, AddUsers.php & AddStudents.php
- Bugfix conflict staff_id & student_id vars in AddUsers.php & AddStudents.php

Changes in 2.8.1
----------------
- Appify / add app to iOS / Android home screen in index.php & apple-touch-icon.png
- Add Select Parents email field facility in CreateParents.php
- Allow students to change their password by default in rosariosis.sql
- Bugfix cannot associate parents & students in AddUsers.php & AddStudents.php

Changes in 2.8
--------------
- Bugfix no payment displayed if no fee in Student_Billing/DailyTransactions.php
- Remove apostrophe + quotes escape in TEXTAREA in CreateParents.php, NotifyParents.php, Transcripts.php
- Create User/Student Account
- Add Registration in Configuration.php
- Move buttons from assets/ to theme folder
- Add HTML5 video ogv + webm formats in PortalNotes.php
- Add PorgramFunctions/README
- check PHP version + fix Referrals notifications in Portal.php
- Add Attendance Start Date this School Year + Grade Level in AssignOtherInfo.php
- Ask user if he wants absences and grades to be deleted when delete schedule in MassDrops.php & Schedule.php
- Better formatting for Labels in MailingLabels.php & StudentLabels.php
- Responsive table content in UnfilledRequests.php
- Add Help texts + spanish translation in Help_en.php & Help_es.php
- Remove inline CSS, program wide
- Add .student .staff .self .align-right .col1-align-right .bar .relevance .arrow.down .arrow.right CSS classes
- Add .center CSS class for tables
- Add <legend> CSS styling
- Remove "Display data using hidden fields" / HIDDEN Preference option
- Update french & spanish translations
- Session security in Warehouse.php & index.php
- Responsive MultiLanguages inputs in stylesheet.css
- Force School Configuration copy in CopySchool.php
- Set new current school after copy & delete school in CopySchool.php & Schools.php
- Update program according to new School/SchoolYear/CoursePeriod in Side.php
- Force REQUEST vars to POST in Side.php
- remove Schools for Parents in User.php & General_Info.inc.php
- Bugfix save school if unchecked in User.php
- Display school info on page reload after update in Schools.php
- other fields required in Student.php & User.php
- date & select student/user fields, add required attribute when required in Inputs.php & Date.php
- propose to create user in Moodle: if the users have not been rolled yet in Moodle/functions.php
- Move assets/ & locale paths from config.inc.php to Warehouse.php
- Activate links in Events descriptions in Portal.php & Calendar.php
- Check if event is in Moodle before update in Moodle/functions.php
- Display MLTextInput value when PDF or no edit allowed in Inputs.php
- Portal Assignments in Portal.php
- create Linkify() function in ProgramFunctions/Linkify.fnc.php
- Reorganize Report Cards comments display in ReportCards.php
- bugfix ListOutput sorting & remove yscroll LO_option in ListOutput.fnc.php
- Add rollover_warnings action in Actions.php & Moodle/functions.php
- Detect IP behind Proxy in HackingLog.fnc.php & User.php
- Linkify Portal Polls options in PortalPollsNotes.fnc.php
- Use Colorbox only for Portal Notes EMBED links in PortalPollsNotes.fnc.php
- check if student already enrolled on that date when updating START_DATE in Enrollment.inc.php & AssignOtherInfo.php
- reset current school if updating self schools in User.php
- move tipmessage CSS (#Migoicons) & jscalendar stylesheet import from Warehouse.php to stylesheet.css
- Add Admin checks for SetUserStaffID() & SetUserStudentID() functions in Current.php & Side.php
- Save enrollment in Student.php & SaveEnrollment.fnc.php
- prevent student ID hacking in Transcripts.php
- prevent course period ID hacking in PrintClassLists.php & PrintClassPictures.php
- prevent referral ID hacking in Referrals.php
- bugfix SQL bug more than one row returned by a subquery in Rollover.php
- Moodle plugin: Remove "roll users and courses only ONCE" limitation in Moodle/functions.php & Moodle/School_Setup/Rollover.php
- Arrows in CSS instead of GIF + rework switchMenu() in warehouse.js & stylesheet.css
- Food Service icons: correct path & limit to image files in MenuItems.php
- CSS hack: Replace the modules icons in CSS trick in themes/README
- show 80 previous years instead of 20 in Date.php
- Bugfix SQL bug for user autos field when add values found in current and previous year in Search.fnc.php
- Solve conflict student/user_id=new in Side.php
- Find a User/Student forms: method=GET + correct URL in Search.inc.php & warehouse.js
- Remove double space before ChooseCheckbox + CSS programwide
- Prevent $_REQUEST['category_id'] hacking in Student.php & User.php
- Add Timezone config variable in Warehouse.php + list of optional config variables in INSTALL
- history grades in Transripts in Transcripts.php & rosariosis.sql
- check accept cookies in index.php
- regenerate session ID on login in index.php
- Bugfix user wrongly excluded from poll in PortalPollNotes.fnc.php
- Remove functions/GetPeriod.fnc.php file
- Remove unused code programwide, thanks to PHP Mess Detector
- Remove $extra['force_search'] search option, programwide
- Add Italian, Japanese, Portuguese, Russian, Turkish, Chinese, Bengali, Korean, Persian locales

Upgrade from 2.7.x & 2.8-betax
------------------------------
- Remove functions/GetPeriod.fnc.php file
- Extract the 2.8 files
- Execute the SQL commands, see https://gist.github.com/francoisjacquet/b451c8006d5e1978fb0d
- Note: you can activate Registration via School > Configuration


Changes in 2.7.4
----------------
- Bugfix cannot create calendar after adding new school in Calendar.php

Changes in 2.7.3
----------------
- Bugfix SQL error invalid input syntax for type numeric in InputFinalGrades.php & Courses.php
- Bugfix SQL error unterminated quoted identifier in InputFinalGrades.php
- Bugfix SQL error column "period_id" does not exist in UnfilledRequests.php

Changes in 2.7.2
----------------
- Bugfix SQL error more than one row returned by a subquery used as an expression in Export.php
- Rollback select all the course periods (for all the selected mps) of the same course in InputFinalGrades.php

Changes in 2.7.1
----------------
- Add widget & hide + remove cellpadding-0/1/2/3/4/6 CSS classes, programwide
- Add check wkhtmltopdf binary exists in diagnostic.php
- Remove inline CSS in index.php & Address.inc.php
- Bugfix SQL error more than one row returned by a subquery used as an expression in GetStuList.fnc.php (Fix #92)
- Bugfix display Moodle rollover error only if rolled once in Moodle/functions.php

Changes in 2.7
--------------
- Remove $RosarioAdmins list in config.inc.php
- Move $RosarioModules to database config table
- Bigger config values capacity to accept serialized variables in rosariosis.sql
- Remove $RosarioModules + MOODLE_INTEGRATOR in config.inc.php
- Add SendEmail function
- Move icons from assets/ folder to modules/ folder
- Remove assets/icons/ folder
- Move Moodle folder from modules/ to plugins/
- Move Moodle functions from functions/ to plugins/Moodle/functions.php
- INSERT MODULES + PLUGINS serialized array in config table
- Add Modules + Plugins tabs to School Configuration
- Allow all modules to be deactivated except School_Setup
- Install/delete Modules/Plugins
- Add README to modules/ & plugins/ folders
- Add _LoadAddonLocale function in Warehouse.php
- Bugfix wrong menu opened if default program overridden in warehouse.js
- Export to Excel strip_tags for date columns in DailySummary.php
- Rename $page to $LO_page in ListOutput.fnc.php
- Add BottomSpinner while ajax
- Responsive calendar adjustments in Calendar.php
- add Default Theme to School Configuration
- Send email from rosariosis@[site_domain] in SendEmail.fnc.php
- Add Actions functions (hooks) in Actions.php
- convert Moodle integrator to plugin
- Add Moodle plugin hooks
- Get rid of contextid in core_role_assign_roles & core_role_unassign_roles Moodle WS functions
- Get rid of local_getcontexts_get_contexts Moodle WS function
- Plugins configuration interface + the one for Moodle plugin
- Move save Medical info out of Student.php
- Grade Level input: no N/A, required in General_Info.inc.php
- Add SetUserStudentID & SetUserStudentID functions in Current.php
- better list searching (case insensitive) by isolating the values in ListOutput.fnc.php
- Create calcSeats0.fnc.php file to regroup function usage
- check if Available Seats < selected students in MassSchedule.php
- add Available Seats column to every choose course popup
- Add errors to $error array (program wide)
- Hide help on page change + fix help in warehouse.js
- Remove inline style for school & period select in Side.php
- Fix Safari popstate bug in warehouse.js
- XLS export arabic chars problem in ListOutput.fnc.php
- All Course periods in Student Summary
- Move Grade Level to Enrollment.inc.php
- Reorganize Student/User General Info tables
- $DefaultSyear & $RosarioLocales checks in diagnostic.php
- Update french & spanish translations
- Fix SQL bug invalid numeric data in Courses.php
- Add cache killer to warehouse.js + stylesheet.css in Warehouse.php
- Add Currency to School Configuration
- Bugfix Field name with apostrophe in Export.php
- Hide Student Billing widget to teachers in Widgets.fnc.php
- Bugfix SQL bug syntax error at or near ")" in StudentSummary.php
- verify END_DATE > START_DATE in MarkingPeriods.php
- Replace smooth scrolling after page load with direct scroll in warehouse.js
- Use json_encode to escape JS vars, programwide
- Bugfix JS syntax error for autos/edits/exports pull down fields in StudentFieldBreakdown.php & StudentBreakdown.php
- regroup functions for Unfilled Requests + add proper Unfilled Requests list to Schedule in Schedule.php & unfilledRequests.inc.php

Upgrade from 2.6.x
------------------
- Flush the functions/ & ProgramFunctions/ folders
- Delete the modules/Moodle/ & modules/Reports/ folders
- Extract the 2.7 files
- Execute the SQL commands, see https://gist.github.com/francoisjacquet/eee136a8431b704646dc
- You can safely remove the following variables from the config.inc.php file (see new config.inc.sample.php file for comparison):
	- $RosarioAdmins
	- $CurrencySymbol
	- $RosarioModules
	- MOODLE_INTEGRATOR
- (Re)activate the Moodle integrator via: School > Configuration > Plugins
- Set the Currency Symbol ($) via: School > Configuration
- Note: all the modules coming with RosarioSIS will be activated
- Activate/deactivate modules via: School > Configuration > Modules
- Note2: if you have custom modules, reactivate them:
	- Please rename first the install.sql file in your module directory (if any). (This will prevent automatic installation when reactivated.)


Changes in 2.6.6
----------------
- Fix PHP notices undefined index in index.php
- Display error if no quarters in Side.php & GetMP.php
- Force login vars to POST in index.php
- Bugfix SQL error column "subject_id" specified more than once in Courses.php
- Bugfix illegal offset when adding user/student in new school in Other_info.inc.php
- Bugfix Student enrollment saved for new students when error in Student.php & Enrollment.inc.php
- Move fix SQL bug FIRST_NAME, LAST_NAME is null up in User.php & Student.php
- No 'N/A' for Grade Level select input in General_Info.inc.php
- Fix Attendance Summary calendar gen for Full Year over 2 years


Changes in 2.6.5
----------------
- Bugifx Invalid argument supplied for foreach() in Food_service/Users/Accounts.php
- Always include Transactions.php
- Move HackingLog function to ProgramFunctions/
- Remove .htaccess
- Remove Reports/Students.php
- Update french & spanish translations
- Bugfix No balance in Food_Service/Users/ServeMenus.php
- Bugfix if no more transaction items, delete transaction in DeleteTransationItem.fnc.php
- Bugfix User Statements access in Statements.php
- Bugfix Activity Report:
	- Enable Student / User links and selection
	- Adapt code for Staff
	- Fix $where (Type & User filters)
- Only display confirm screen when modfunc delete in ActivityReport.php & Statements.php
- Reactivate Cancel Transaction in Transactions.php
- Bigger font size for PDF (medium)
- Add tabindex to username / password inputs in index.php
- Rework Course Period TITLE generationin Courses.php
- Fix SQL bug invalid display columns in UserFields.php & StudentFields.php

Upgrade from 2.6.4-
-------------------
- Flush the functions/ folder


Changes in 2.6.4
----------------
- Bugfix set UserSchool for parent in Side.php
- Bugfix output started Export to Excel


Changes in 2.6.3
----------------
- Program wide: Bugfixes PHP Notices Undefined index / variable
- Program wide: $_REQUEST[modname] => $_REQUEST['modname']
- Program wide: Concatenate variables in SQL statements
- Program wide: Format long SQL statements for readability
- Add translation for Sales in MenuReports.php
- Program wide: replace double quotes by simple ones (echo)
- IE8 compatibility fixes: IE8 HTML5 tags fix, .focus() in warehouse.js
- Remove inline CSS + indent HTML + rework Side HTML in Bottom.php & Side.php
- Program wide: json_encode + htmlspecialchars ENT_QUOTES for Javascript strings
- PHPBench.com:
	- Program wide: Counting Loops with pre calc count()
	- Program wide: Modify loop: use for instead of foreach
	- Program wide: Variable Type Checking: check isset before is_array
- Open menu + sel menu link transferred to warehouse.js
- include homogenize in Bottom.php & Menu.php
- Place Javascript in Warehouse footer + remove *_once
- inline HTML in Help.php & index.php
- Program wide: SELECTED="SELECTED" => SELECTED
- modname + Program loaded fixes in Modules.php, Side.php, Warehouse.php, warehouse.js, Searc.fnc.php & Student.php
- Responsive teacher Schedule in Schedule.inc.php
- Bugfix check Visible to profiles w/ Custom in PortalPollsNotes.fnc.php
- Remove Warehouse('footer_plain')
- Move popup & not_ajax HTML code to Warehouse('header')
- Replace check if `$_REQUEST['_ROSARIO_PDF']=='true'`
- Remove Side_PHP_SELF in Side.php & Warehouse.php
- remove ProgramLink function
- move Attendance.php from functions/ to ProgramFunctions/
- Remove CourseTitle & CourseTitleArea functions
- Liberate $field_name in CustomFields.fnc.php
- move BackPrompt function with *Prompt, rename file to Prompts.php
- Remove ".fnc" if file contains more than one function in functions/
- Move Submit/ResetButton functions to functions/Buttons.php
- regroup Date functions in functions/Date.php
- Remove ShortDate function
- Remove Percent.fnc.php
- Remove Localize('colon', Localize('time', & Localize.fnc.php
- Move GetAllMP functions to functions/GetMP.php
- Remove GetSchool function
- unset Password and Username request vars after login in index.php
- Program wide: Replace Current SESSION vars with Current functions
- Bypass strip_tags on the `$_REQUEST` vars in Modules.php, ProgramFunctions/getRawPOSTvar.fnc.php, Letters.php, HonorRoll.php & HonorRollSubject.php
- UpdateSchoolArray when calling SchoolInfo() in functions/School.php
- Bugfix Copy calendar when date_min & date_max in Calendar.php
- Add FileUpload function in ProgramFunctions/FileUpload.fnc.php
- Display spinner if photo uploaded on form submit
- Bugfix SQL bug invalid input syntax for type numeric in Schools.php
- Delete school only if more than one school in Schools.php
- Fix SQL bug no course ID + add error in ReportCardComments.php
- Strict standards: use time instead of mktime in EnterEligibility.php
- Reactivate lists (ul + ol) in stylesheet.css
- Bugfix plot values in inversed order in GradebookBreakdown.php
- Add more complete list of Right to Left languages
- update french & spanish translations

Upgrade from 2.6.2-
-------------------
- Flush the functions/ & ProgramFunctions/ folders


Changes in 2.6.2
----------------
- add German translation, thanks to Heike Gutsche (needs update)
- IE9 & Safari fixes in warehouse.js


Changes in 2.6
--------------
- add Arabic translation, thanks to Husam Shabeeb (needs update)
- add Accounting module, sponsored by Bishnu Sharma
- add pushState and popState to warehouse.js, enables:
	- Navigate the browser history
	- Open RosarioSIS links in new tab
	- Page source viewable
	- Page title updated
- User/Student photo upload rework
- add Food Service options in School Configuration
- Rebrand RosarioSIS
- bugfix Cannot use string as an offset in StudentsUsersInfo.fnc.php
- Centralize AJAX menu_link in Modules.php
- Remove modules/*/Search.php files
- Remove CSS filters in DHTML tip message
- Shorten open menu onclick in Side.php
- Fix SQL bug PRICE_STAFF & PRICE not null in MenuItems.php
- Fix SQL bug PRICE_FREE & PRICE_REDUCED numeric in MenuItems.php
- If no more transactions items, delete transaction in DeleteTransactionItem.fnc.php
- Students - Users links in bold in Food Service
- Complete config.inc.php config vars help in INSTALL
- Only first translation string is required in Inputs.php
- Better list sorting by isolating the values
- Update french & spanish translations

Upgrade from 2.5.x
------------------
- Execute the SQL commands to add the accounting module & the food service options, see https://gist.github.com/francoisjacquet/8cd6c3625a68628674a8
- Add the Accounting module to the config.inc.php file, see https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/config.inc.sample.php#L40

Note for developers
-------------------
- The default program to be loaded when a module is opened is not defined in the Search.php file anymore. Please update your custom modules' Menu.php file by adding the "default" entry, following this example https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/modules/Attendance/Menu.php#L3

Changes in 2.5.8
----------------
- Update wkhtmltopdf install instructions
- Add link to Windows install Wiki
- Move User Fields inside box in Advanced Search
- Add no Courses assigned to teacher error in Assignments.php
- Student Billing Widgets only if AllowUse
- Grades Widget hidden
- Disable User categories edit for non admins
- User in Moodle password/username/email fields required
- Verify file call is by AJAX in PhotoUpload.php & PortalPollsNotes.fnc.php
- Add file invalid or not moveable upload error
- Concatenate calendar.js + calendar-setup.js = calendar+setup.js
- Add noreferrer attribute to index page links

Changes in 2.5.7
----------------
- RosarioSIS 2014 update
- Include Lunch Payment in Balance
- Add School submenu in School Setup module
- Bugfix SQL bug more than one row returned by subquery when more than one school and numbered days
- Bugfix array_key_exists() expects param 2 to be array when deleting the only existing calendar
- Bugfix SQL bug empty school dates in Calendar.php
- Bugfix Missing arg 2 for _removeSpaces() in ProgressReport.php
- Bugfix Invalid argument supplied for foreach() in Scheduler.php, Configuration.php & Preferences.php
- School Fields fixes:
	- display empty fields in Add a School
	- display label in red if required
	- add required attribute when apply
- /assets/PortalNotesFiles/ folder created
- Add PortalNotesFiles upload error if not moved
- Place embed link detection first in PortalPollsNotes.fnc.php
- Bugfix SQL bug datestyle in Calendar.php
- Note after admins creation only in User.php
- Bugfix array_key_exists() expects $options to be array in SelectInput()
- Link variables out of string in EditReportCardGrades.php
- Add #selectedModuleLink bugfix in warehouse.js
- select & input to same height in stylesheet.css

Changes in 2.5.6
----------------
- Bugfix remove Refund payment with refunded payment in StudentPayments.php
- Add #selectedModuleLink in warehouse.js
- Bugfix Currency() direct call via $extra['functions']
- Reset $email_column variable in CreateParents.php
- Add .br-after class to Side.php input fields
- Bugfix SQL bug column "None" does not exist in UnfilledRequests.php, Scheduler.php, ReportCards.php
- Add note after admin creationin User.php
- Update spanish & french translations

Changes in 2.5.5
----------------
- Github friendly README.md (Contribution by Scott Cytacki)
- Responsive ListOutput table cells content div
- Bugfix SYEAR timeframe in CategoryBreakdownTime.php

Changes in 2.5.4
----------------
- Do not count NULL values as 0 for numeric fileds chart in *Breakdown.php
- Fix chart for numeric fields in CategoryBreakdownTime.php
- Bugfix no results for numeric fileds chart in *Breakdown.php
- Bugfix wrong advanced student search results in Widgets.fnc.php
- Photo link onclick return false
- Bugfix XLS export of CheckboxInput & TextAreaInput
- Case insensitive string replace in XLS export
- Bugfix Portal Notes & Portal Polls XLS export
- Change Portal Notes / Polls detection in POrtalPollsNotes.fnc.php
- Replace HTML tags with space & trim XLS export value in ListOutput.fnc.php
- Bugfix DisciplineForm XLS export
- Bugfix Portal Notes & Portal Polls PDF export in Portal.php
- Bugfix Javascript jquery sourceMappingURL in jquery.js
- Bugfix Portal Polls results display after submit
- Show 20 previous years instead of 5 in PrepareDate.fnc.php
- Bugfix StudentFiledBreakdown numeric field chart
- Add y coordinate to line chart tooltip in *Breakdown.php
- Override "Student" if extra singular/plural set in Students/Search.inc.php
- Add XLS export to Grading Scale
- Bugfix XLS export Incomplete Schedules / Take Attendance / Attendance Chart
- No responsive table for School Fields ListOutput
- Remove Contact Information for XLS & PDF export in GetStuList.fnc.php
- Do not go back to top onclick in Profiles.php
- Omit script type attribute (remove type="text/javascript", default in HTML5)
- Responsive Requests ListOutput table
- Update spanish & french translations

Changes in 2.5.3
----------------
- Verify $value only for INSERT, not on UPDATE
- Check numeric fields if not empty

Changes in 2.5.2
----------------
- Bugfix SQL more than 1 enrollment / drop code in Export.php
- Bugfix data showed in the wrong month in CategoryBreakdownTime.php
- Bugfix save select input field value in MakeReferral.php
- Bugfix save '0' as input value
- Update jqplot (1.0.8)
- Add showDataLabels to Pie charts
- Input type number size
- Update colorbox (1.5.9)
- Bugfix specify colorbox iframe height
- Add "http://" as a hint for the Embed link input
- Bugfix SQL error column "month" does not exist in CategoryBreakdownTime.php
- Open new window to download XLS export (#36)
- Protect $_REQUEST['category_id'] against SQL injection in *Breakdown.php

Changes in 2.5.1
----------------
- Fix #menuback display on the right when RTL dir in stylesheet.css
- Bugfix JS error Calendar._TT undefined in Warehouse.php
- Update & fix jscalendar i18n files
- Bugfix TinyMCE loading if i18n file not found
- Add TinyMCE i18n files

Changes in 2.5
----------------
- Add default points to assignments in Assignments.php, Grades.php
- Update SQL: add default_points field
- Gradebook grades fixes
- Bugifx input data verification in Assignments.php
- Bugfix check numeric fields in MakeReferral.php, Referrals.php, Schools.php, User.php, Student.php, Address.inc.php
- Bugfix broken statistics in StudentGrades.php
- Add date field support in Schools.php
- Bugfix SQL error staff_field_seq
- Fix people fields display in Address.inc.php
- Update spanish & french translations
- Bugfix erase claendar onchange in Calendar.php

Upgrade from 2.x
----------------
- Execute the SQL commands to add default points to the gradebook assignments, see https://gist.github.com/francoisjacquet/87c12769735311bee428

Changes in 2.4.1
----------------
- Update 2014 SQL: add Resources table in rosariosis2014.sql

Changes in 2.4
----------------
- Resources module rework:
	- RosarioSIS Wiki
	- Print handbook
	- Add your links
	- delete Redirect.php
	- create Resources.php
- Help & Print Handbook rework:
	- Move Help.php to Help_en.php
	- Unique link to call handbook print: Help.php
- Update spanish & french translations
- Security fixes: add AllowEdit() check when update or remove in an Admin program
- Bugfix Javascript bug expandHelp
- Avoid orphan h3 program titles in the PDF handbook
- wkhtmltopdf CSS: bigger font size (16px)
- Add link to bottom of handbook

Upgrade from 2.x
----------------
- Execute the SQL commands to install Resources, see https://gist.github.com/francoisjacquet/11379623

Changes in 2.3.4
----------------
- Replace _makePhone with makeContactInfo in Administration.php
- Link to Windows locale resource in locale/README
- Bugfix Javascript typo error in Scheduler.php
- Print Options preferences (Add page size (A4 or US Letter) option)
- Update french & spanish translations
- Move PDF List Header Color from display to print options

Changes in 2.3.3
----------------
- Add PrintSchedules to student/parent profile by default
- Only verify xmlrpc PHP extension if Moodle integrator
- Do not display logo if "Add a School"
- Display ListOutput header if no result & if no add link
- rosariosis.sql for 2014: rosariosis2014.sql

Changes in 2.3.2
----------------
- Escape jqPlot graph strings in *Breakdown.php
- Update french & spanish translations
- Bugfix create student in Moodle in Student.php
- Move Moodle config include to Student.php
- Verify email upon student creation in Moodle
- Allow assign manager role with Moodle integrator
- Choose whether to create user in Moodle or not
- Un/assign manager role on Moodle user update
- Moodle password check on user/student update
- Remove hardcoded country in Moodle/Students/Student.php

Changes in 2.3.1
----------------
- Not title on remove button if has label in Buttons.fnc.php
- Display School logo in School Information
- Bugfix graphs in Android: load jquery.jqplottocolorbox.js if screen width>=768

Changes in 2.3
----------------
- Bugfix display in PrintStudentInfo.php in Medical.inc.php
- Remove phpinfo() for security reasons in diagnostic.php
- Upload school logo in Configuration.php:
	- remove school_logo.sample.jpg
	- add SchoolLogo.inc.php
	- update tests for school logo
	- upload error strings + "School logo" update translations
	- add spinning.gif while uploading logo
- Add spinning.gif while uploading file attached in PortalNotes.php

Changes in 2.2.5
----------------
- Bugfix: Invalid argument supplied for foreach() in InputFinalGrades.php
- Bugfix added remove parameters in ReportCardComments.php
- Organize Report Cards Comments Codes in ReportCards.php
- X button reference fix in Courses.php
- Update french & spanish translations
- SQL bugfix: remove search + system_field in custom_fields INSERTS in rosariosis.sql
- fr_FR.utf8 & es_ES.utf8 locales works on Windows: unify locales files & directories

Changes in 2.2.4
----------------
- Remove config.dist.php rule in .htaccess
- Responsive COURSE table
- Remove dataline label limit to 20 chars in *Breakdown.php
- Do not go back to top onclick tipmessage and newSchoolPeriod
- Escape apostrophe in Inputs values
- Responsive Eligibility Student screen
- Ethnicity, Gender, Social Security & Birthdate fields and type tests
- Remove students table fields in Reports/Students.php
- Update spanish & french translations
- Insert GP_SCALE when Percent grade added 1st time

Changes in 2.2.3
----------------
- SQL bugfix Balance Widget
- Change to AJAX form in MenuReports.php
- Timeframe fix in TransactionsReport.php
- Exclude Lunch Payment from Balances
- Invert Balance calculus in Widgets.fnc.php

Changes in 2.2.2
----------------
- Diagnostic: verifiy PHP extensions and php.ini
- Move scrollTop to header in Warehouse.php
- Update check_button image reference in Courses.php
- Custom Prompt function to Cancel on Schedule conflict in Schedule.php
- Base Grading Scale required in Schools.php
- Bugfix remove "-" from javascript var name in Inputs.php

Changes in 2.2.1
----------------
- Larger Tooltips for jqplot graphs
- Overflow-x scroll on ListOutput tables
- Replace TABLE with one line grade display in Grades.php
- Added "E/C" and "Not due" translations

Changes in 2.2
----------------
- SQL bugfix index row size exceeds maximum 2172 for index in *Fields.php & DisciplineForm.php
- SQL bugfix RosarioSIS ID assigned not numeric error
- Add School Fields:
	-created School_Setup/SchoolFields.php
	-add program to School_Setup/Menu.php
	-add program to profile_exceptions table
	-add school_fields table
	-create INDEXes for school_fields table
	-rollover School Fields
	-update translations
- Remove search + system_field in *_FIELDS tables

Upgrade from 2.x
----------------
- Execute the SQL commands to install School Fields, see https://gist.github.com/francoisjacquet/9118591

Changes in 2.1.1
----------------
- Unbind ajaxForm in Successful Install form
- Bugfix false low food service balance alert
- Link example parent to example student in rosariosis.sql
- Bugfix escape chosen course in ChooseCourse.php

Changes in 2.1
----------------
- SQL bugfix: skip course_period_school_periods in Courses.php
- Add time and user to comments "comment thread" like
- Display notice while recalculating daily attendance
- Delete ReferralForm.php (use DisciplineForm.php instead)
- Discipline TextAreaInput fixes

Changes in 2.0.3
----------------
- Format multiple comments in report cards
- Resource module functioning
- Transcripts printing corrections
- Rollover: add Scale Value & Honor Roll by Subject GPA

Changes in 2.0.2
----------------
- SQL bugfix string begins with single quotes in database.inc.php

Changes in 2.0.1
----------------
- SQL bugfix statement ends with '' in database.inc.php

Changes in 2.0
----------------
Responsive design
- Compatible with smartphones and tablets
- AJAX design
- Retractable menu
- Responsive tables
- Bigger texts, icons and buttons
Known bugs
- PDF printing in Android browser not working
- Scroll colorbox in Android browser

Upgrade from 1.4x
-----------------
- please recreate the config.inc.php file from config.inc.sample.php, or add the line "$DefaultSyear = '2013';"

Changes in 1.4.5
----------------
- Move SYEAR from config table to config.inc.php
- Do NOT roll students where next grade is NULL
- Explode Portal Polls questions change method
- SQL bugfix string begins with single quote in database.inc.php
- Bugfix: inputs with double quotes in Inputs.php, Referrals.php, StudentsUsersInfo.php
- Bugfix: Student Attendance when days numbered in Administration.php
- SQL bugfix statement ends with '' in database.inc.php

Changes in 1.4.4
----------------
- Escape course title & period title in popups
- Correct "Parent Course Period" translations
- Display letter grade according to Configuration in Grades.php
- Bugfix: AJAX User photo upload error handling
- Remove semester exam & "trimestre" => "bimestre" in Help files
- Update translations: "quarter" => "bimestre"
- Spanish translation: "letter"/"letter grade" = "nota"
- Update spanish translation: correct misspellings
- Adjust min course period length to appear in table in PrintSchedules.php

Changes in 1.4.3
----------------
- Fix attendance color codes in StudentSummary.php
- Add Comment Codes tipmessage in FinalGrades.php
- Fix course_period_school_periods Rollover
- Display grades according to GRADES_DOES_LETTER_PERCENT in StudentGrades.php
- ROLL Gradebook Config's Final Grading Percentages
- SQL bug: DOES_BREAKOFF grades displayed twice in Gradebook Config
- SQL bug: First Name initial in Export.php
- Bugfix: date of the day outside Quarter in ProgressReport.php
- Bugfix: no student found when parent logged in

Changes in 1.4.2
----------------
- Security fixes: delete & save not accessible to non admins in
	- AddressFields.php, PeopleFields.php, StudentFields.php, UserFields.php, Transactions.php, ActivityReport.php, ReferralForm.php, DisciplineForm.php, FinalGrades.php
- SQL bugs: course_period_school_periods, schools, DISCIPLINE_CATEGORIES, program_config & course_periods Rollover
- Bugfix UserCoursePeriod not set correctly in Grades.php

Changes in 1.4.1
----------------
- SQL: fix report_card_grades' grade_scale_id
- Moodle errors fix in Rollover.php
- take in account Search options in DuplicateAttendance.php
- bugfix: grades program_config (School Config)
- SQL: add gp_scale value to Main grade scale
- SQL: add reporting_gp_scale value to Default School
- SQL bug: SYEAR=NULL in EditReportCardGrades.php

Changes in 1.4
----------------
- added discipline_categories to discipline Widget
- wkhtmltopdf update for StudentLabels.php
- activate Custom service Attendance Summary
- bugfix: escape double quotes in stm() tipmessage
- bugfix: discipline_entry_begin Date format
- add Discipline new referrals Portal alert
- set width to 1448px for landscape PDF
- bugfix: ViewContacts.php not accessible
- SQL bugs: sequences start values fix

Upgrade from 1.3x
-----------------
- execute those 3 SQL statements to fix RosarioSIS database:
SELECT pg_catalog.setval('staff_field_categories_seq', 3, true);
SELECT pg_catalog.setval('student_field_categories_seq', 5, true);
INSERT INTO profile_exceptions VALUES (1, 'Custom/AttendanceSummary.php', 'Y', 'Y');

Changes in 1.3.5
----------------
- bugfix: illegal offset type in Inputs.php
- bugfix: delete imposible in Student screens
- forgot name of contact info input in Address.inc.php
- add maxlength=100 to contact info input
- remove Students config.inc.php

Changes in 1.3.4
----------------
- bugfix: mass drop students did not work properly
- bugfix: invalid argument supplied for foreach() in InputFinalGrades.php
- security fixes: update, save, delete & create not accessible to non admins in
	- Referrals.php, ReportCardCommentCodes.php, ReportCardComments.php, ReportCardGrades.php, MenuItems.php, StudentFees.php & StudentPayments.php, MarkingPeriods.php, Statements.php, DailyMenus.php, Requests.php, Schedule.php, Student.php, User.php, Calendar.php

Changes in 1.3.3
----------------
- bugfix: JS bug mig_clay is not defined in warehouse.js
- bugfix: division by zero in MenuReports.php
- bugfix: SQL bug more than one residence address
- bugfix: no student selected in DuplicateAttendance.php
- bugfix: SelectInput with no title
- bugfix: escape Course Title in ChooseRequest.php
- bugfix: nothing displayed if user selected in NotifyParents.php
- bugfix: nothing displayed after user search in Exceptions.php
- bugfix: add Parent/Student nobody selected
- bugfix: URL filter for Portal Note's files attached
- bugfix: SQL bug cpsp reference missing in AddAbsences.php
- bugfix: update Medical fields
- bugfixes: DailySummary.php:
	- bug when Back to Student Search
	- SQL bug PERIOD_ID numeric
	- modname not set

Changes in 1.3.2
----------------
- Moodle create user: remove lang
- User & student password:
	- Moodle password check fix
	- password saving fix
- translations: update .po project name & plural form syntax
- remove Semester / Full Year exam
- bugfix: addHTML is not defined
- bugfix: SQL bug $_SESSION['student_id'] is not set in Schedule.php
- embed link detection change in PortalNotes.php
- file upload rework
- bugix: SQL bug Event TITLE too long in Calendar.php


Changes in 1.3.1
----------------
- bugfix: comma escape in SelecInput function
- added limit visibility to the students of a determined teacher in Portal Polls
- functions folder sweep, regroup functions & removed 6 files
- removed BackPrompt & replace with ErrorMessage in AddAbsences.php, CreateParents.php, NotifyParents.php, AddActivity.php, FinalGrades.php, MassDrops.php, MassRequests.php, MassSchedule.php, AddressFields.php, AssignOtherInfo.php, Address.inc.php, PeopleFields.php, StudentFields.php, MassAssignFees.php, MassAssignPayments.php, UserFields.php,
- delete FDFReportCards.php
- bugfix: SQL bug duplicate entry in profile_exceptions
- bugfix: $_REQUEST['include'] 2 times in links
- bugfix: urlencoded include & next_modname vars
- added PHP version check in dagnostic.php


Changes in 1.3
----------------
- UTF-8 multibyte strings:
	- stripos => mb_stripos
	- strlen => mb_strlen
	- strpos => mb_strpos
	- strrchr => mb_strrchr
	- strrpos => mb_strrpos
	- strstr => mb_strstr
	- strtolower => mb_strtolower
	- strtoupper => mb_strtoupper
	- substr_count => mb_substr_count
	- substr => mb_substr
- DBEscapeString on $_REQUEST vars
- unescape strings for password encryption / to display / to search
- removed old string escaping method
- HTML table fix in Medical.inc.php
- bugfix: SQL bug when incomplete or non-existent date
- CSS header icon resize
- help PDF rework
- bugfix: no student selected in MassSchedule.php
- bugfix: include in GradebookBreakdown.php
- IN operator SQL queries fix
- deleted config.inc.php, config.dist.php and created config.inc.sample.php
- added school configuration:
	- move $RosarioTitle & $DefaultSyear to database
	- delete Grades/config.inc.php
	- move $semester_comment to database
	- move Moodle/config.inc.php to database
- added Notify Parents custom service
- HTML table fix in HonorRoll*.php, Letters.php & Transcripts.php
- added templates to CreateParents.php and NotifyParents.php email text
- disabled student lists SQL echo
- removed $DatabaseANSI & $DatabaseType config value
- removed oracle and mysql cases in database.inc.php
- updated spanish and french translations


Changes in 1.2.2
----------------
- bugfix: SQL bug invalid sort order & numeric data in Assignments.php
- disable remaining vra_dump
- translation correction
- removed IgnoreFiles
- added query string to HackingLog
- bugfix: SQL bug course period in Grades.php
- reduced header icon size


Changes in 1.2
---------------
- replace ? with & in modname parameter
- security fix, see http://www.securiteam.com/securitynews/6S02U1P6BI.html
- removed modname var scan
- added HackingLog function
- SQL queries fix: put quotes around all PHP variables
- adapt Bottom.php to wkhtmltopdf
- added Failed Login to the expanded view of staff listing
- bugfix: SQL bug when incomplete END_DATE in Schedule.php
- bugfix: PrintClassLists with all contacts
- bugfix: PDF orientation
- bugfix: wkhtmltopdf screen resolution on linux, see https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
- removed staff_exceptions table data in rosariosis.sql
- bugfix: SQL bug 'NULL' instead of NULL in InputFinalGrades.php
- relate users to Default School in rosariosis.sql
- added translations


Changes in 1.1
---------------
- added link to rosariosis.org in index.php
- added ability to modifiy and delete periods to an existing Course Period
- Javascript load optimization
- bugfix: remove modules with no programs
- bugfix: delete buttons with malformed onclick parameter


Changes in 1.0
---------------
- forked Centre SIS v.3.0.1
- added theme WPadmin
- added CSS tags for the new theme
- delete old themes
- replaced modules icon set
- added &lt;label&gt; on checkbox and radio
- added favicon
- removed Common Name
- added ability to add Student/User Photo directly from the Student/User screen via jQueryForm
- upgraded PostgreSQL functions now compatible with PostgreSQL v.9
- removed LO
- added Discpline Module
- added Student Billing Module
- added gettext on every string in RosarioSIS
- replaced date() by strftime() for dates in locale
- added custom currency
- delete folder vendor/
- delete folder language/
- delete functions/DrawPNG.fnc.php
- delete functions/DrawBlock.fnc.php
- removed function DrawRoundedRect()
- moved function ReindexResults() to ListOutput.fnc.php and delete functions/ReindexResults.fnc.php
- delete functions/StripChars.fnc.php
- delete modules/Grades/ReportCards_gpa.php
- delete labels_test.php
- delete Top.php
- delete modules/Attendance/config.inc.php
- delete modules/misc/Directory.php
- moved static Javascript code in Warehouse.php to assets/js/warehouse.js
- moved static Javascript code in Side.php to assets/js/side.js
- replaced PHP/SWF Charts by jqPlot
- replaced htmldoc by wkhtmltopdf
- replaced HTML 3 code by HTML 5 code
- added .htaccess for security
- added password encryption
- added Grades/GradebookBreakdown.php for teachers
- added Custom Module SQL
- added event repeat for the calendar
- added School uses a Rotation of Numbered Days option
- added possibility to attach a file to Portal Notes
- added possibility to add more than one period to a Course Period
- added PostgreSQL Database Backup
- added Students/StudentBreakdown.php
- added Scheduling/MasterScheduleReport.php
- added TinyMCE to letters
- added School Year over two calendar years option
- added School Configuration
- changed short names to full names (School Periods & Attendance Codes) in Grades/ReportCards.php & Grades/FinalGrades.php
- fixed errors Deprecated: Functions ereg_replace(), eregi_replace(), ereg(),
- fixed errors various PHP Warnings and Notices
- bugfix: text encoding passed to HTMLDOC
- bugfix: Eligibility add the same activity more than once
- bugfix: Eligibility Add Activity no Search when student already selected
- bugfix: Food Service no balance
- bugfix: Discipline search when only saving
- bugfix: Portal Notes not displayed when pn.START_DATE IS NULL
- bugfix: ListOutput.fnc.php search when only saving
- bugfix: ListOutput.fnc.php bug ngettext when the plural form is not registered as this in the rosariosis.po file
- bugfix: School Setup no save button if no admin
- bugfix: Internet Explorer Quirks Mode <!DOCTYPE> not valid
- bugfix: EditReportCardGrades.php 3 SQL related bugs
- bugfix: MassRequests.php Choose a Course window closing
- bugfix: SQL bug invalid sort order, program wide
- bugfix: SQL bug invalid amount in StudentBilling
- bugfix: minutes not numeric in School_Setup/Calendar.php
- bugfix: teacher's school is NULL in Scheduling/MassRequests.php
- bugfix: no student found when student logged in in functions/Search.fnc.php
- bugfix: SQL bug no course period in the marking period in Users/TeacherPrograms.php
- bugfix: SQL bug START_DATE or END_DATE is null in School_Setup/MarkingPeriods.php
