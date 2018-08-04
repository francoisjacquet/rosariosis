# CHANGES
## RosarioSIS Student Information System

Changes in 4.0-beta
-------------------
- Rename School Setup module to School & Eligibility module to Activities in Menu.php
- Move CHANGES for version 1 and 2 in CHANGES_V1_2.md
- New Module icons: moved from modules to theme in assets/themes/WPadmin/modules/
- CSS Add modules icons & .module-icon class in assets/themes/WPadmin/css/icons.css & Gruntfile.js
- Remove .HeaderIcon CSS class, add .module-icon class & use modcat as $\_ROSARIO['HeaderIcon'] value in DrawHeader.fnc.php, ProgramTitle.fnc.php, Side.php, Help.php & various programs
- CSS Fixed responsive menu & footer in zresponsive.css & warehouse.js
- Remove Honor Roll by Subject program in HonorRollSubject.php, Menu.php & rosariosis.sql
- Add by Subject option to Honor Roll program in HonorRoll.php & HonorRoll.fnc.php
- Remove assets/Frames/ folder. Upload Frames directly inside program.
- CSS add .list-header class in ListOutput.fnc.php & stylesheet.css
- Add List Before and After action hooks in ListOutput.fnc.php & Actions.php
- CSS Use linear gradient instead of background image for input buttons in colors.css
- CSS Add LO search icon inside input in stylsheet.css & ListOutput.fnc.php
- Remove #menuback & #menushadow div in Side.php, Warehouse.php, warehouse.js, colors.css, rtl.css & stylesheet.css
- Select distinct Participated STUDENT_ID & STAFF_ID in MenuReports.php
- Format CSS with JSBeautifier in themes/WPadmin/css/*.css
- Format JS with JSBeautifier in assets/js/*.js
- Remove "Add a School" program in Schools.php, Menu.php, rosariosis.sql & Help_en.php
- Add $RosarioErrorsAddress optional config variable in INSTALL\*.md
- Add ErrorSendEmail() function in ErrorMessage.fnc.php, HackingLog.fnc.php & database.inc.php
- Send email on PHP fatal error in Warehouse.php

Changes in 3.9.2
----------------
- No button when printing PDF in Buttons.php
- Fix PHP Notice Undefined variable / index, program wide
- Set default Incident Date in Widgets.fnc.php
- Fix regression UpdateAttendanceDaily() call when Updating in Administration.php

Changes in 3.9.1
----------------
- Fix UpdateAttendanceDaily() call when Updating in Administration.php
- Fix "You are not currently in a marking period" error when recalculating daily attendance in UpdateAttendanceDaily.fnc.php
- Add Daily Comment column in TakeAttendance.php, sponsored by Asian Hope
- Translate database fields to Spanish or French in rosariosis_es.sql & rosariosis_fr.sql
- Moving from github.com to gitlab.com, program wide

Changes in 3.9
--------------
- Add FileInput() function in Inputs.php
- Add FileExtensionWhiteList() function in FileUpload.fnc.php
- Move flag icons from assets/flags/ to their corresponding locale/[code].utf8/ folder
- Check Moodle URL and token are valid in plugins/Moodle/config.inc.php, thanks to @abogadeer
- Fix #241 assignments of other teachers appear in StudentAssignments.fnc.php
- SaveTemplate() $staff_id param: use 0 for default template in Template.fnc.php
- Add insert_attendance, update_attendance & header action hooks in Actions.php & TakeAttendance.php
- Add $type param to AttendanceCodesTipMessage() in AttendanceCodes.fnc.php
- Fix #246 SQL error when selecting "All Periods" (admin) in StudentSummary.php
- Fix SQL error: A field with precision 9, scale 2 must round to an absolute value less than 10^7 in Transactions.php
- Fix Assignments columns for teacher list in Portal.php
- Add link to Assignment to teacher list in Portal.php & Assignments.php
- Mention current MP in program title in StudentAssignments.php, Grades.php & StudentGrades.php
- Outside link: Assignment is in the current MP? in StudentAssignments.php, Portal.php & Assignments.php
- Add Student_Billing/StudentFees.php|student_fees_header action hook in StudentFees.php
- Add Grades program link header in Assignments.php
- Fix SQL error no AMOUNT when Print after Save Payments in StudentPayments.php & StaffPayments.php
- Translate Help_en.php help texts in locale/[code].utf8/LC_MESSAGES/help.po & help.mo
- Fix #244: Add DISPLAY_NAME to CONFIG table for every school in Configuration.php, rosariosis.sql & Update.fnc.php, sponsored by Asian Hope
- Default school year is 2018 in rosariosis.sql & config.inc.sample.php

Changes in 3.8
--------------
- PHP gettext, mbstring, json & xml extensions compatibility in functions/PHPCompatibility.php
- Add Warehouse header_head and footer actions in Warehouse.php
- Side Menu form: add CSS classes in Side.php & stylesheet.css
- Select distinct entries GetReportCardsExtra() in ReportCards.fnc.php
- CSS fix (hidden) submenu width on mobile in stylesheet.css
- Fix SQL for Schedule table in PrintSchedules.php
- Fix ngettext plural forms in Translator.php
- Fix Error: RosarioSIS cannot connect to the PostgreSQL in diagnostic.php
- JS Fix TipMessage on mobile in TipMessage.fnc.php & main17.js
- CSS RTL .align-right align left in rtl.css
- CSS allow centering image using .center in stylesheet.css
- Gettext .po use relative base path in locale/*.po
- Gettext Czech (Czech Republic) locale code is "cs_CZ.utf8" in locale/cs_CZ.utf8/
- Gettext .pot file available in locale/en_US.utf8/LC_MESSAGES/rosariosis.pot
- Expanded View: Tip Message containing Student or User Photo in TipMessage.fnc.php & GetStuList.fnc.php & GetStaffList.fnc.php
- CSS Display button text on multiple lines if too long in zresponsive.css
- CSS & HTML reorganize login form in index.php & stylesheet.css
- Teacher: My Periods option in DailySummary.php, sponsored by @abogadeer
- Redirect to Modules.php URL after login in Warehouse.php & index.php
- CSS, HTML & PHP add AttendanceCodesTipMessage() & MakeAttendanceCode() & color codes classes in AttendanceCodes.fnc.php, DailySummary.php, stylesheet.css & colors.css
- CSS & HTML use attendance-code colors classes in TakeAttendance.php
- CSS add .proper-date class in Date.php & stylesheet.css
- Add AddRequestedDates() function in Date.php & use programwide
- Add CSS .button-primary class to submit buttons in Buttons.php
- SubmitButton() $value parameter is optional i Buttons.php & programwide
- CSS accessibility color contrast: darker text & .legend-gray in colors.css
- Fix SQL error when no Courses selected in MassCreateAssignments.php
- Fix #234 Grades not saved after ordering list, thanks to @abogadeer
- Remove tabindex from Points and Comment input fields in Grades.php
- JS fix fixMenuLogic in jquery-fixedmenu.js
- CSS & HTML add header title to Password Reset page in PasswordReset.php

Changes in 3.7.1
----------------
- Fix #225 Print Schedule inactive courses bug in PrintSchedules.php
- Automatically update schedules marking period in Courses.php, sponsored by Aptiris
- Update MP column on MARKING_PERIOD_ID update in Schedule.php
- Fix #226 Student Photo tooltip won't disappear in TipMessage.fnc.php

Changes in 3.7
--------------
- RTL layout issues #214 in rtl.css
- INSERT INTO case to Replace empty strings ('') with NULL values in database.inc.php
- #218 Add DISPLAY_NAME to CONFIG table in rosariosis.sql & Update.fnc.php
- Fix #221 Delete Addresses & Contacts info does not delete student in Student.php & Address.inc.php
- #218 Add DisplayNameSQL() & DisplayName() functions in GetStuList.fnc.php
- #218 Add Display Name option in Configuration.php
- #218 Use DisplayName & DisplayNameSQL functions programwide
- Add Help for Display Name & update French & Spanish translations

Changes in 3.6.1
----------------
- User email: reply-to instead of carbon-copy in CreateParents.php & NotifiyParents.php
- Give teachers, parents & students access to Courses program in Scheduling/Menu.php & rosariosis.sql
- Fix bug when timezone for PHP and PostgreSQL are different in PassWordReset.php
- Add ProgramFunctions/SendEmail.fnc.php|before_send hook in SendEmail.fnc.php
- Update PHPMailer classes to v5.2.26 in classes/PHPMailer/
- Fix SQL error escape parent's name in CreateParents.php
- Fix Password Reset for Students in PasswordReset.php

Changes in 3.6
--------------
- Add menuMP JS var to update current MP in side menu in Side.php & Warehouse.php
- Add link to Take Attendance program from the Missing Attendance listing in Portal.php
- Fix UTF8 Excel file and non English characters in ListOutput.fnc.php
- Add GetTemplate() & SaveTemplate() functions to ProgramFunctions/Template.fnc.php
- Remove Honor Roll ClipArts in HonorRollSubject.php & assets/ClipArts/
- Add custom medical text input size per column in StudentsUsersInfo.fnc.php
- Fix #216 Delete Medical info does not delete student in Student.php & Medical.inc.php

Changes in 3.5.3
----------------
- Fix AJAX error display in warehouse.js
- Fix #177 Get Gradebook Grades' Percentage rounding issue in InputFinalGrades.php, thanks to @lkozloff
- Hide School column in Missing Attendance listing if only 1 school in Portal.php
- Fix #206 No Missing Attendance warning if course period has no students in Portal.php

Changes in 3.5.2
----------------
- Fix pg_connect() error in database.inc.php
- Fix DB error with REPORTING_GP_SCALE field numeric(10,3) type in Schools.php
- Fix SQL error when course has no periods in MassCreateAssignments.php
- Add Exif imagetype function in ImageResizeGD.php

Changes in 3.5.1
----------------
- Add Course Period column to Edit Student Grades program in EditReportCardGrades.php, sponsored by Aptiris
- Update Arabic translation & flag in locale/ar_AE.utf8, thanks to Ali Al-Hassan
- Fix "Please enter valide numeric data error" in Configuration.php, thanks to @vanyog

Changes in 3.5
--------------
- #199 Add failed login ban if >= X failed attempts within 10 minutes in index.php & AccessLog.php
- #199 Add FAILED_LOGIN_LIMIT Config option in rosariosis.sql & Update.fnc.php & Configuration.php
- Add Help for FAILED_LOGIN_LIMIT Config option in Help_en.php, Help_fr.php & Help_es.php
- #199 Add Captcha jQuery plugin in assets/js/jquery-captcha/
- #199 Add CaptchaInput() & CheckCaptcha() functions in Inputs.php
- #199 Add Captcha to Create User / Student Account forms in User.php, Student.php & General_Info.inc.php
- #201 Delete Student in Student.php
- #202 Fix shared hosting: permission 755 for directories in FileUpload.fnc.php
- Fix List save / export in ListOutput.fnc.php

Changes in 3.4.3
----------------
- Fix #198 Add error if student account inactive (today < Attendance start date) in index.php
- Optimization Remove $schools_RET & $calendars_RET ID index in Widgets.fnc.php
- Fix JS addHTML so inline Javascript gets evaluated in warehouse.js

Changes in 3.4.2
----------------
- Move "Calendars" program up & "Database Backup" under Security in School_Setup/Menu.php
- Update Arabic translation in locale/ar_AE.utf8, thanks to @abogadeer
- Fix #195 Add Right to Left languages stylesheet & move side menu to right in rtl.css
- Fix #195 Handle RTL languages (menu on the right) in jquery-fixedmenu.js
- Fix SQL error if MP was deleted in ScheduleReport.php
- CSS fix responsive calendar for RTL in rtl.css

Changes in 3.4.1
----------------
- Add maxlength & length to Fees & Payments text inputs in Student_Billing/functions.inc.php
- Add maxlength & length to Salaries & Payments text inputs in Accounting/functions.inc.php
- #191 Fix PHP notices thanks to @vanyog in GetStaffList.fnc.php, GetStuList.fnc.php & ListOutput.fnc.php
- Set current SchoolYear on login in index.php, Side.php & Portal.php
- Fix SQL error when UserSchool() not set in Config.fnc.php
- Optimize: remove SCHOOL_DATE index (events) & group LO options in vars in Portal.php
- Translate "No Address" in Address.inc.php, Transcripts.php
- Fix Remove previous years MP columns from list in Transcripts.php
- Fix SQL error remove duplicate "s." prefix in Search.fnc.php
- Remove useless DBGet indexes in SetUserStudentID() in Current.php
- Fix PHP error when no Student associated to Parent in Registration.php
- Fix #197 last login date in PasswordReset.php, thanks to @Claculagator
- Add Student Payments Header action hook in StudentPayments.php
- Fix SQL error searching Other Value in Search.fnc.php

Changes in 3.4
--------------
- Fix #193 PHP error: do not call button(), not logged in in PortalPollsNotes.fnc.php
- New translations (37% completed) in locale/
- Add new translations flag icons in assets/flags/
- Update README.md & add translations REFERENCE.md in locale/
- International proof no_accents function in FileUpload.fnc.php
- Fix SQL error field type numeric(5,0) in Registration.php
- Fix PHP error typo SchoolInfo() in HonorRoll.php
- Rollback TinyMCE image upload handler in Inputs.php
- CSS style sub & sup HTML elements in stylesheet.css
- Add pdf_start action hook to PDF.php & Actions.php
- Add header & footer HTML options to PDF.php & Wkhtmltopdf.php

Changes in 3.3.4
----------------
- Fix "Create User Account" schools in User.php
- Security check for $modname in Modules.php
- Default school year is 2017 in rosariosis.sql & config.inc.sample.php

Changes in 3.3.3
----------------
- Fix PHP error new width height not set in ImageResizeGD.php
- Accept .jpg, .png. & .gif + remove 2MB limit for school logo upload in Configuration.php
- Add School Periods "Blocks" help text in Help_en.php, Help_es.php & Help_fr.php

Changes in 3.3.2
----------------
- Fix SQL error in students data in Transcripts.php
- Fatal error when no calendars setup yet in Calendar.php
- Fix #192 CheckRequiredCustomFields() in Fields.fnc.php, thanks to @vanyog
- Display required address / people fields error in Address.inc.php
- Set start date to yesterday, prevents having long list on first load in AccessLog.php
- Fix PHP notice in diagnostic.php, thanks to @vanyog
- Add rosariosis2017.sql for 2017 school year DB in rosariosis2017.sql

Changes in 3.3.1
----------------
- Fix wkhtmltopdf error on Windows: prepend file:/// in PDF.php
- Fix PHP error removed s.*, select each student field in Export.php
- Optimize _makeNextSchool & _makeTeachers functions in miscExport.fnc.php
- Fix GD bug with transparent background PNG in ImageResizeGD.php
- Fix TinyMCE using relative URLs in Inputs.php
- JS Fix tipmessage mig_lay error in main16.js
- Add $image_path parameter to SanitizeHTML() in MarDownHTML.fnc.php
- Upload TinyMCE images to AssignmentFiles/ in StudentAssignments.fnc.php
- Fix PHP error max execution time in DBGet.fnc.php

Changes in 3.3
--------------
- CSS Add padding to .list-nav & remove spaces before buttons in ListOutput.fnc.php & stylesheet.css
- Add RedirectURL(), prevents showing an obsolete & confusing delete confirmation screen on page reload in Prepare_PHP_SELF.fnc.php & program wide
- Handle X-Redirect-Url header in warehouse.js
- Use PreparePHP_Self() in forms to maintain program state in Administration.php & Eligibility/Student.php
- Fixed Cancel Delete Event / Transaction in Calendar.php, ActivityReport.php & Statements.php
- Remove "# Associated" column from Student list in AddStudents.php
- Fix disabled buttons on back or page reload in Firefox in warehouse.js
- CSS larger tooltip & fix FS menu calendar in stylesheet.css
- Add  &student_id / &staff_id params to update form URL in Student.php & User.php
- Do not display Contact Info tipmsg in Student List if no contacts in GetStuList.fnc.php
- Remove eval(), up to 4x speed & memory gain in DBGet.fnc.php
- Fix PHP error 'VALUE' index. Append % to Teacher grade scale breakoff in ReportCardGrades.php
- Format ListOutput() code, rework nav HTML & logic in ListOutput.fnc.php
- Do not submit form when LO_search in ListOutput.fnc.php & warehouse.js
- CSS Rename .list-header to .list-nav & add .list-no-nav class in stylesheet.css
- Add Grunt for automatic CSS & JS files concat & minify in package.json, Gruntfile.js, assets/js/ & assets/themes/WPadmin/
- Relevance score inside bar (transparent) so value can be exported in stylesheet.css, colors.css & ListOutput.fnc.php
- Check if Request exists before inserting in Requests.php
- Check for PHP gd extension in diagnostic.php & INSTALL.md
- Add general File Uploads folder in assets/FileUploads/
- Add $FileUploadsPath & $PNGQuantPath optional configuration variables in INSTALL.md & Warehouse.php
- Add Image resize and compress class in classes/ImageResizeGD.php
- Add ImageUpload() function in FileUpload.fnc.php
- Use new ImageUpload() function in SanitizeHTML() & remove CheckBase64Image() in MarkDownHTML.fnc.php
- Use new ImageUpload() function for student / user photo in Student.php & User.php

Changes in 3.2
--------------
- Append "%" to displayed Breakoff value in ReportCardGrades.php
- CSS add .tipmsg-label class to TipMessage label in TipMessage.fnc.php & stylesheet.css
- Format code, reorganize update errors & add maxlength to Sort Order input in Assignments.php
- Allow Parents & Students to Edit Requests if have permissions in Requests.php, Exceptions.php & Profiles.php
- Hide List sorting icon on vertical mobile + rename "LO_direction" param to "LO_dir" in stylesheet.css & ListOutput.fnc.php
- Update French & Spanish translations

Changes in 3.1.2
----------------
- Fix Assignment view: do not exit so Warehouse('footer') is called in StudentAssignments.php
- Remove Used for Attendance column, unused in Periods.php
- Add Student photo Tip message in MakeReferral.php & Referrals.php
- Sanitize XML tag names in ListOutput.fnc.php
- Fix #185 PHP error do not check if constant is empty

Changes in 3.1.1
----------------
- Fix memory error: rework SQL query in MassCreateAssignments.php
- Fix Save $_REQUEST vars in session: if not printing PDF in Modules.php

Changes in 3.1
--------------
- Add ETagCache() function in Warehouse.php
- Fix SQL error when entering (Unweighted) GPA Value > 99.99 in rosariosis.sql & Update.fnc.php
- Activate ETagCache in Bottom.php
- Rename 'modfunc' to 'bottomfunc' in Bottom.php & warehouse.js
- Unique Bottom.php URL in Users/Search.inc.php & Students/Search.inc.php
- Hide link to User Permissions on Add a User screen in Users/includes/General_Info.inc.php
- Fix Admin Schools restriction: Assign new user to current school only in User.php
- Fix modname & ProgramLoaded when has parameters in Modules.php & warehouse.js
- Add Mass Create Assignments program (sponsored by Sofia Private School) in MassCreateAssignments.php & Grades/Menu.php & Update.fnc.php
- Fix check if user logged in when history back in warehouse.js, Warehouse.php & Side.php
- Fix SQL error: check for current Student / User ID before saving programwide
- Format code & data display in DuplicateAttendance.php
- Update French & Spanish translations
- CSS for responsive images, TinyMCE max-width & min-height
- Add maxlength attribute to every text input in Widgets.fnc.php & StaffWidgets.fnc.php
- Move Find a User form General Info & Profile inside Search() in Users/Search.inc.php & Search.fnc.php

Changes in 3.0.2
----------------
- Retry once on AJAX error 0, maybe a micro Wifi interruption in warehouse.js
- Better check if #body should be updated in Side.php
- Fix Warehouse footer: always open menu to modname in Warehouse.php
- Cache &lt;script&gt; resources loaded in AJAX in warehouse.js
- Limit Assignments to the ones due during the Progress Period in InputFinalGrades
- Update help texts for Input Final Grades in Help_en.php, Help_es.php & Help_fr.php

Changes in 3.0.1
----------------
- Fix popup + AJAX: no Warehouse header / footer neede in Modules.php & Warehouse.php
- CSS optimizations: add .header & .list-nav classes in DrawHeader.fnc.php & ListOutput.fnc.php
- CSS Media queries for mobile: update for iPhone 6 plus in stylesheet.css
- Add isAJAX() function in Warehouse.php
- Simplified code in Modules.php
- Fix XML export: remove parenthesis in column names in ListOutput.fnc.php
- Fix Admin User Profile & School restrictions position in Exceptions.php
- Fix logic for User Info tabs in Profiles.php & Exceptions.php

Changes in 3.0
--------------
- Add ETag cache system in Warehouse.php & Modules.php
- Add TinyMCE UploadImage plugin in assets/js/tinymce/plugins/uploadimage & Inputs.php
- Add CheckBase64Image() in MarkDownHTML.fnc.php
- Fix JS error for search Go button in ListOutput.fnc.php
- Add link to RosarioSIS Forum to Resources in rosariosis.sql
- Current tab in bold in Configuration.php & Food Service module wide
- Fix do not show Delete prompt when reloading page in Schools.php
- Add Marking Periods to parents & students in School_Setup/Menu.php & rosariosis.sql
- Add Access Log, thanks to @dpredster in AccessLog.php, index.php, Update.fnc.php & rosariosis.sql
- Add User Agent functions in ProgramFunctions/UserAgent.fnc.php
- Add Browser column to Access Log in AccessLog.php
- When clicking on Username, go to Student or User Info in AccessLog.php
- Logic & design fixes & show Can Edit for User Info tabs in Profiles.php & Exceptions.php
- Add DBEscapeIdentifier() in database.inc.php
- Escape SQL identifiers (table, column), program wide
- Performance: 90% gain when updating Side menu in Side.php, Warehouse.php & warehouse.js
- Student Fields: Search Username in Search.fnc.php, GetStuList.fnc.php
- Add SearchField() function in Search.fnc.php
- Add link to User Permissions when user has custom permissions in Users/includes/General_Info.inc.php
- Can't delete Assignment Type if has Assignments in Assignments.php
- Add ThemeLiveUpdate() in ProgramFunctions/Theme.fnc.php
- Update French & Spanish translations


### Old verions CHANGES
- [CHANGES for version 1 and 2](CHANGES_V1_2.md).
