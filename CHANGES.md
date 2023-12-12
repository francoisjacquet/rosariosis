# CHANGES
## RosarioSIS Student Information System

Changes in 11.3.3
-----------------
- Fix PHP 8.1 fatal error: array_key_exists() arg 2 ($array) must be of type array in ListOutput.fnc.php
- Fix only display "Delete Account" button if AllowEdit() in Accounts.php

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

Changes in 10.9.8
-----------------
- Fix remove "-master" suffix from add-on folder in Modules.inc.php & Plugins.inc.php
- Update Default School Year to 2023 in config.inc.sample.php, rosariosis.sql & rosariosis_mysql.sql

Changes in 10.9.7
-----------------
- Fix SQL for Class Average calculation, exclude NULL grades in Grades.fnc.php
- Fix Total Credits reset for each student in ReportCards.fnc.php
- Fix NULL grade display when Min. and Max. Grades in ReportCards.fnc.php

Changes in 10.9.6
-----------------
- Do not use strtok(), can't handle nested calls for multiple files in InstallDatabase.php & database.inc.php
- Add Vietnamese (Vietnam) translation in locale/vi_VN.utf8/ & locale/REFERENCE.md
- Update German (Germany) translation in locale/de_DE.utf8/ & locale/REFERENCE.md
- Fix Portuguese (Brazil) translation for "Gradebook": "livro de notas" in locale/pt_BR.utf8/

Changes in 10.9.5
-----------------
- Fix SQL error table name "sam" specified more than once in ReportCards.fnc.php
- Security: prevent CSV Injection via formulas in ListOutput.fnc.php, thanks to Ranjeet Jaiswal

Changes in 10.9.4
-----------------
- Fix regression since 10.8.4 save multiple SelectInput() when none selected, add hidden empty input (only if $allow_na) in Inputs.php
- Save when none selected, add hidden empty input (Grade Levels) in Resources.php & Resources.fnc.php

Changes in 10.9.3
-----------------
- Security Fix browser loading cached page when page full reload (F5) + logout + Back button in warehouse.js, thanks to @b1tch3s
- Fix Edge browser detection in UserAgent.fnc.php
- Add-on SQL translation file can be named "install_es.sql" or "install_pt_BR.sql" in Modules.inc.php & Plugins.inc.php
- Fix regression since 10.8.1 SQL error when saving Contact/Address/Student Fields in Registration.fnc.php, RegistrationAdmin.fnc.php & RegistrationSave.fnc.php

Changes in 10.9.2
-----------------
- Fix Format Contact Field value based on its Type in ViewContact.php
- Fix regression add Contact Info columns to list in MyReport.php
- ETag + Cache-Control header: use no-cache directive in Warehouse.php
- Set `$_SESSION['is_secondary_teacher']` in SetUserCoursePeriod() in Current.php
- Fix Set current User Course Period before Secondary Teacher logic in TakeAttendance.php, AnomalousGrades.php, Assignments.php, Grades.php, InputFinalGrades.php & ProgressReports.php
- SQL Show Gradebook Grades of Inactive Students (Only if has grades) in StudentGrades.php

Changes in 10.9.1
-----------------
- Fix Anonymous Statistics bar overflow in StudentGrades.php
- SQL Show Gradebook Grades of Inactive Students (Course status, maybe dropped as of today) in StudentGrades.php
- Show Gradebook Grades of Inactive Students (School status) in StudentGrades.php
- Fix PHP error if date.timezone ini setting is an invalid time zone identifier in Warehouse.php

Changes in 10.9
---------------
- Fix do not add new Enrollment Record if existing has no Dropped date in Student.php, Enrollment.inc.php & SaveEnrollment.fnc.php
- Enrollment Start: No N/A option for first entry in StudentsUsersInfo.fnc.php
- Hide End Date input for Inactive Students (no Attendance Start Date) in StudentsUsersInfo.fnc.php
- SQL ORDER BY DEFAULT_CALENDAR IS NULL,DEFAULT_CALENDAR ASC (nulls first) for consistency between PostgreSQL & MySQL in Courses.fnc.php, Calendar.php & Enrollment.inc.php
- Simplify & harmonize code, use GetAllMP() in GPARankList.php, InputFinalGrades.php & TeacherCompletion.php
- Exclude already associated parents/students from Search() in AddUsers.php & AddStudents.php
- Replace "Course Title" in TipMessage with actual Course title in Attendance/, Eligibility/ & Grades/TeacherCompletion.php
- Handle multiple Course Periods on the same Period for same Teacher in Attendance/ & Eligibility/TeacherCompletion.php
- JS Remove "Go" button & submit form on select change in Eligibility/TeacherCompletion.php
- Add SetUserCoursePeriod() function in Current.php
- Set current User Course Period using SetUserCoursePeriod() in Side.php, TakeAttendance.php, EnterEligibility.php, AnomalousGrades.php, Assignments.php, Grades.php, InputFinalGrades.php, ProgressReports.php & TeacherPrograms.php
- Add `'&period=' . UserCoursePeriod()` to Teacher form URL in TakeAttendance.php, EnterEligibility.php, Assignments.php, Grades.php, InputFinalGrades.php, ProgressReports.php, StudentAssignments.fnc.php & Portal.php
- Remove period from URL when switching School / Year / MP / CP in Side.php
- HTML Rename "period" select input ID to "school_period" to avoid conflicts in Attendance/, Eligibility/, Food_Service/ & Grades/TeacherCompletion.php
- CSS Add modname class, ie .modname-grades-reportcards-php for modname=Grades/ReportCards.php in PDF.php
- CSS Add .list-column-[column_name] class in ListOutput.fnc.php
- CSS Set Report Cards Comments column (max) width to 33% in wkhtmltopdf.css
- Temporary AllowEdit so SelectInput() is displayed to everyone in DailyTransactions.php
- SQL ORDER BY Assignment Type first, then order Assignments in Grades.php
- Remove $count & $has_count_text variables in ListOutput.fnc.php
- Add pagination option (defaults to false) in ListOutput.fnc.php
- Add pagination for list > 1000 results in AccessLog.php & ReportCardGrades.php
- SQL gradebook_assignments table: Add WEIGHT column in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Fix security issue, unset any FILE_ATTACHED column first in PortalNotes.php
- Use `_makeAutoSelectInputX()` for Contact Information Description in Address.inc.php
- SQL courses ORDER BY TITLE in Requests.php
- HTML remove "Add a Request" & hide "Subject" input label in Requests.php
- Update command to install wkhtmltopdf & dependencies in INSTALL.md
- Add link to Softaculous installation directions in INSTALL.md
- Fix wkhtmltopdf not rendering URL in CSS in PDF.php
- CSS Fix breaking words inside .header2 in stylesheet.css
- SQL set N/A grade GPA to NULL in rosariosis.sql, rosariosis_es.sql, rosariosis_fr.sql & rosariosis_mysql.sql
- Add Portuguese (Brazil) translation in InstallDatabase.php, REFERENCE.md, locale/pt_BR.utf8/ & rosariosis_pt_BR.sql, thanks to Emerson Barros

Changes in 10.8.5
-----------------
- Fix "Folder not writable" error on add-on zip upload in Modules.inc.php & Plugins.inc.php

Changes in 10.8.4
-----------------
- Fix SQL error escape menu title in DailyMenus.php, ServeMenus.php
- Fix MySQL date interval in Food_Service/Students/ServeMenus.php
- SQL order Transactions by ID in Transactions.php
- Fix regression since 10.6.2 SQL error invalid reference to FROM-clause entry for table "ssm" in Widget.php
- Fix do not set empty Grade Points to 0 in Grades.php
- Fix save multiple SelectInput() when none selected, add hidden empty input in Inputs.php
- Fix check if Available Seats < selected students calculation in MassSchedule.php
- SQL order Requests by Course in Requests.php
- CSS set minimum colorBox width to 50% view width in StudentGrades.php
- Fix SQL error when Phone > 30 chars in Address.inc.php
- If City length > 22 without space, force stackable table in Address.inc.php
- CSS responsive stack Address & Contacts table below Laptop MDPI screen in zresponsive.css
- Fix PHP fatal error Unsupported operand types: string - int when not a date in Date.php
- Fix do not display Rollover default check warning if modfunc=remove in EnrollmentCodes.php
- Fix check for Description & Value when saving new Contact Information in Address.inc.php

Changes in 10.8.3
-----------------
- Add assets/FileUploads/ directory
- Fix regression since 10.8.2 PHP fatal error $timestamp is not a valid date-time string in StudentAssignments.fnc.php
- Catch strftime_compat() exception $timestamp is not a valid date-time string in Date.php

Changes in 10.8.2
-----------------
- PHP<7 Fix add microseconds to filename to make it harder to predict in StudentAssignments.fnc.php, thanks to @jeffreygaor
- Add datetime to filename to make it harder to predict in Accounting/functions.inc.php & Student_Billing/functions.inc.php
- HTML add maxlength to Field Category & Name inputs in Fields.fnc.php
- Do not check username uniqueness if empty in Student.php & User.php
- CSS FLatSIS adjust fieldset border color & MD to HTML line-height in colors.css & stylesheet.css
- Fix MySQL 8 syntax error, 'rank' is a reserved keyword in rosariosis_mysql.sql
- Add MySQL global setting to allow function creation in INSTALL.md, INSTALL_es.md & INSTALL_fr.md

Changes in 10.8.1
-----------------
- Use CheckBoxOnclick() in StudentBalances.php & Schedule.inc.php
- Fix SQL error when Quarter with Assignments is deleted in Portal.php
- SQL Check requested assignment belongs to current Marking Period in Assignments.php
- MySQL + MariaDB < 10.5 fix FOREIGN KEY constraint syntax in rosariosis_mysql.sql
- SQL use CONCAT() instead of pipes || for MySQL compatibility in CreateParents.php
- Fix SQL error cast attendance code to integer in TakeAttendance.php
- Fix hide Contact Fields according to Emergency/Custody settings in Address.inc.php
- Fix MultipleCheckboxInput() save all unchecked, add hidden empty checkbox in Inputs.php

Changes in 10.8
---------------
- Fix MySQL Periods list in MasterScheduleReport.php
- SQL explicitly list all columns instead of `SELECT *` in MenuItems.php, Menus.php, EditHistoryMarkingPeriods.php, EditReportcardGrades.php, ReportCardCommentCodes.php, ReportCardComments.php & ReportCardGrades.php
- Add & use DBSQLCommaSeparatedResult() function (SQL result as comma separated list) in database.inc.php, Dashboard.inc.php, MasterScheduleReport.php & Export.php
- HTML email input remove pattern, add maxlength in PasswordReset.php, StudentsUsersInfo.fnc.php, NotifyParents.php, CreateParents.php & General_Info.inc.php, Other_Info.inc.php
- JS Fix #319 Try a full match first to identify selected menu link in warehouse.js
- Spanish translation add ¿ character before questions in rosariosis.po
- HTML add "Check All" checkbox after Periods in AddAbsences.php
- Truncate Assignment title to 36 chars in AnomalousGrades.php
- SQL resources table: Add PUBLISHED_PROFILES & PUBLISHED_GRADE_LEVELS columns in Update.fnc.php, rosariosis.sql & rosariosis_mysql.sql
- Add Resource Visibility options in Resources.php
- Move Resources functions to separate file & rename them in Resources.php & Resources.fnc.php
- Update French & Spanish translations in rosariosis.po
- CSS Fix Select2 dropdown hidden when inside colorBox (has z-index 9999) in stylesheet.css

Changes in 10.7.1
-----------------
- Fix SQL limit School Periods to Course Period in AddAbsences.php

Changes in 10.7
---------------
- CSS add Select2 styles in stylesheet.css & colors.css
- JS add Select2 jQuery plugin in assets/js/jquery-select2/
- Add Select2Input() function in Inputs.php
- Use Select2 instead of Chosen, fixes the overflow issue in Widget.php, MakeReferral.php, Transcripts.fnc.php & Address.inc.php
- Fix PHP8.2 deprecated Creation of dynamic property in Markdownify/ConverterExtra.php
- SQL always add space before AND to $extra['WHERE'], program wide
- Escape SanitizeHTML() & SanitizeMarkDown() before saving to DB, program wide
- Always use UserStudentID() instead of `$_SESSION['STUDENT_ID']` in PortalPollsNotes.php
- SQL use EXISTS(SELECT 1) instead of `EXISTS(SELECT *)` in EditReportCardGrades.php, Rollover.php & Moodle/functions.php
- Remove trailing seconds :00 & add sorting HTML comment to datetime in Date.php
- HTML set max Event Repeat Days to 300 in Calendar.php
- Only display Locked column if AllowEdit() in Schedule.php
- Schedule table: rename Periods column to Period (singular) in PrintSchedules.php & Schedule.inc.php
- HTML add padding to Assignment options + remove useless table in Grades/Configuration.php
- Move "Remove required attribute, TinyMCE bug" fix to TextAreaInput() in Inputs.php
- Only display profiles w/Custom if actually used to gain space in PortalPollsNotes.php
- Add & remove module icons in themes/FlatSIS/modules/
- Add .dotx,.ppsx,.mdb,.sldx,.odg,.odc,.odb,.odf,.numbers,.pages,.m4a,.tsv,.json,.ics file extensions to whitelist in FileUpload.fnc.php
- ROLL Gradebook Config's Final Grading Percentages for Admin (overridden) in Rollover.php
- HTML responsive limit display columns to 3 in Courses.php & Assignments.php
- SQL remove Period Length<=(Minutes in a Full School Day / 2) for Teacher's Schedule table in Schedule.inc.php
- SQL include Secondary Teacher Course Periods in Schedule.inc.php
- HTML move Schedule list inside PopTable & rework table cell in Schedule.inc.php
- Fix do not display "Enroll student for next school year" link if new enrollment record in Enrollment.inc.php
- Add Class Average row in ReportCards.fnc.php & Grades.fnc.php
- Fix HTML5 notice Trailing slash on void elements has no effect, but keep `<br />` for `$_ROSARIO['SearchTerms']`, program wide
- Fix regression since 9.0 show Student Photo in Transcripts.fnc.php
- Check Assignment is in current MP in StudentAssignments.php & StudentAssignments.fnc.php
- Corrections for French translation in rosariosis.po
- CSS FlatSIS .list & .postbox headers adjustments for Chrome in stylesheet.css
- CSS reduce File input size when inside list in stylesheet.css
- CSS responsive increase max input size when inside list in zresponsive.css
- CSS fix Student Comments margin & padding in stylesheet.css
- Fix regression display Mailing Labels in ReportCards.fnc.php
- Add Student Photo in ReportCards.fnc.php
- Fix SQL error ORDER BY for Expanded View + Contact Information in GetStuList.fnc.php

Changes in 10.6.3
-----------------
- Fix regression Teacher can save config even if overridden by Admin in Grades/Configuration.php
- Prevent double encoding single quote (`&#039;`), was encoded by SanitizeHTML() or SanitizeMarkDown() in Inputs.php

Changes in 10.6.2
-----------------
- Do not truncate value on List export in Accounting/functions.inc.php, Grades.php, StudentGrades.php, StudentAsignments.fnc.php, Resources.php & Student_Billing/functions.inc.php
- SQL performance use POSITION()>0 instead of LIKE, program wide
- SQL remove useless ID='' check in StudentFees.php, StudentPayments.php & Student_Billing/functions.inc.php
- Fix hide File input if cannot edit in Accounting/functions.inc.php & Student_Billing/functions.inc.php
- SQL use CONCAT() instead of pipes || for MySQL compatibility in Accounting/functions.inc.php & Student_Billing/functions.inc.php
- Fix SQL use JOIN instead of WHERE EXISTS for Discipline Widgets in Widget.php
- MySQL fix PHP fatal error when updating Course's Marking Period in Courses.php
- Fix #338 Only check against Course Periods having overlapping Marking Period in Courses.fnc.php

Changes in 10.6.1
-----------------
- PostgreSQL Fix Class Rank float comparison issue: do NOT use double precision type (inexact), use numeric (exact) in rosariosis.sql, thanks to @fatahou
- MySQL Fix Class Rank float comparison issue: do NOT use double precision type (inexact), use numeric(22,16) (exact) in rosariosis_mysql.sql, thanks to @fatahou
- SQL Fix regression since 10.0, change sum/cum factors & credit_attempted/earned columns type from double precision to numeric in Update.fnc.php
- Fix Grades input not displaying for Teachers in Widget.php
- Raise minimum PHP version from 5.4.45 to 5.5.9 in INSTALL.md, README.md, composer.json, diagnostic.php & Portal.php
- Fix close PopTable wrapper `<div>` in Enrollment.inc.php, General_Info.inc.php, Medical.inc.php & Schedule.inc.php

Changes in 10.6
---------------
- Add Pashto to right-to-left languages in Warehouse.php, Inputs.php & PDF.php
- CSS FixedMenu bug when menu hidden in stylesheet.css
- Fix JPG image rotation in ImageResizeGD.php
- Resize, compress & store image using ImageUpload() in FileUpload.fnc.php
- Fix remove `<span>` HTML tag from Assignment Type in select in Grades.php
- Truncate Assignment Type to 36 chars only if has words > 36 chars in ProgressReports.php & StudentAssignments.fnc.php
- Truncate Assignment title to 36 chars in StudentGrades.php
- CSS Do NOT use global word-break. Use it only on specific elements in stylesheet.css & zresponsive.css
- CSS responsive Fix list overflow-x scroll inside div.st in stylesheet.css
- CSS set max-width for City & State select in Address.inc.php
- Security fix for dynamic include in index.php
- Fix SQL limit 1 when adding existing Contact in Address.inc.php
- CSS responsive add .postbox-wrapper class for overflow-x scroll in PopTable.fnc.php & stylesheet.css
- Fix MySQL 5.6 error Can't specify target table for update in FROM clause in AssignOtherInfo.php

Changes in 10.5.2
-----------------
- Fix PHP8.1 Fatal error when $options is null in StudentsUsersInfo.fnc.php
- Remove .00 decimal from value of numeric type in Substitutions.fnc.php
- Truncate Assignment title to 36 chars only if has words > 36 chars in ProgressReports.php, Assignments.php & MassCreateAssignments.php
- Truncate Assignment title to 36 chars in GradebookBreakdown.php & Grades.php
- CSS responsive fix List column title prevent word breaking in zresponsive.css
- Fix photo use mime types, not file extensions so mobile browsers allow camera in General_Info.inc.php & MenuItems.php
- Fix return processed image upload in case source type != target type in FileUpload.fnc.php

Changes in 10.5.1
-----------------
- Fix MySQL error Unknown system variable 'storage_engine' in InstallDatabase.php, Modules.inc.php, Plugins.inc.php & rosariosis_mysql.sql

Changes in 10.5
---------------
- JS responsive add minWidth & minHeight options to inline colorBox in jquery-colorbox.js & warehouse.js
- Trim white spaces for Contact name & Address fields in RegistrationSave.fnc.php & Address.inc.php
- Save Student Files fields, upload files in RegistrationSave.fnc.php
- CSS Do not break text inside button in stylesheet.css
- CSS responsive raise max-width for mobile & vertical tablet from 736 to 874px in zresponsive.css, rtl.css & colors.css
- HTML fix responsive table & weekdays in Calendars.php
- CSS responsive reduce select max-width from 440 to 340px in stylesheet.css & zresponsive.css
- Fix PHP8.2 utf8_decode() function deprecated in PDF.php
- MySQL change database charset to utf8mb4 and collation to utf8mb4_unicode_520_ci in InstallDatabase.php

Changes in 10.4.4
-----------------
- Fix AllowUse() & AllowEdit() for User Info when on Student Info in AllowEdit.fnc.php
- CSS fix Calendar header days word-break in zresponsive.css
- CSS style time input in stylesheet.css & colors.css
- CSS remove padding for LO_SORT arrow in zresponsive.css
- HTML fix responsive / stackable table for Course Periods in Courses.php

Changes in 10.4.3
-----------------
- MySQL always use InnoDB (default), avoid MyISAM in InstallDatabase.php, Modules.inc.php, Plugins.inc.php & rosariosis_mysql.sql

Changes in 10.4.2
-----------------
- Fix SQL error null value in column "amount" in Salaries.php
- Fix Total row calculation, reset for each student in ReportCards.fnc.php & Transcripts.fnc.php, thanks to @fatahou

Changes in 10.4.1
-----------------
- JS fix regression since 9.0 & DOMPurify, open links in new window in warehouse.js
- Add Database Type and Version, add PHP version to FirstLoginPoll() in FirstLogin.fnc.php
- Fix typos in INSTALL.md & INSTALL_fr.md

Changes in 10.4
---------------
- Modcat is addon module, set custom module icon in Profiles.php & Exceptions.php
- SQL performance: use NOT EXISTS instead of NOT IN + LIMIT 1000 in Portal.php
- Add student name to Student's Absences and Grades delete prompt in MassDrops.php
- Fix display only first letter of attendance code in AttendanceSummary.php
- Remove "Minimum assignment points for letter grade" config option in Grades/Configuration.php & StudentGrades.php
- Truncate Assignment title to 36 chars in StudentGrades.php & Grades.php
- CSS date capitalize first letter only in stylesheet.css
- Add optional $id param to FilesUploadUpdate() in FileUpload.fnc.php
- JS Only show laoding spinner if file input has selected files in warehouse.js
- Add File Attached Input for existing Fees/Payments in StudentFees.php, StudentPayments.php & Student_Billing/functions.inc.php
- Add File Attached Input for existing Salaries/Staff Payments/Incomes/Expenses in Expenses.php, Incomes.php, Salaries, StaffPayments.php & Accounting/functions.inc.php
- Add-ons can add their custom Widgets in classes/core/Widgets.php & classes/core/StaffWidgets.php
- Add Widgets init action hook in Actions.php & Widgets.fnc.php
- Add Staff Widgets init action hook in Actions.php & StaffWidgets.fnc.php
- Fix SQL check student is actually enrolled in Enrollment.fnc.php
- Fix date is 1969-12-31 on Windows when PHP intl ext not activated in strftime_compat.php

Changes in 10.3.3
-----------------
- SQL ORDER BY END_DATE IS NULL DESC,END_DATE DESC (nulls first) for consistency between PostgreSQL & MySQL in User.fnc.php
- Fix PostgreSQL error column "students_join_people.address_id" must appear in the GROUP BY clause in Address.inc.php

Changes in 10.3.2
-----------------
- Fix PostgreSQL error ORDER BY "full_name" is ambiguous in DailyTransactions.php

Changes in 10.3.1
-----------------
- Fix MySQL error result as comma separated list in Export.php
- Add recommended php.ini setting session.gc_maxlifetime = 3600 in INSTALL.md, INSTALL_es.md & INSTALL_fr.md

Changes in 10.3
---------------
- Add "Cumulative Balance over school years" checkbox in StudentBalances.php
- Fix program not found when query string is URL encoded in Modules.php
- JS fix add new Period below existing Period row in Courses.php
- Add "Course Periods" checkbox in Rollover.php
- Fix MySQL error 1069 Too many keys specified; max 64 keys allowed in Fields.fnc.php & DisciplineForm.php

Changes in 10.2.3
-----------------
- Remove dead link to centresis.org in index.php
- Fix MySQL error TEXT column used in key specification without a key length in Fields.fnc.php & DisciplineForm.php
- Add ROLLOVER_ID column to User() in User.fnc.php
- Get template from last school year (rollover ID) in Template.fnc.php

Changes in 10.2.2
-----------------
- Fix PHP fatal error undefined function StudentCanEnrollNextSchoolYear() in PrintStudentInfo.php
- Set school logo with to 120px in PrintStudentInfo.php

Changes in 10.2.1
-----------------
- SQL order by Marking Period Start Date in MarkingPeriods.php, ReportCards.fnc.php, Courses.php, Schedule.php, PrintSchedules.php, MassSchedule.php, MassDrops.php & Side.php
- Maintain current month on calendar change in Calendar.php
- Maintain Calendar when closing event popup in Calendar.php
- CSS FlatSIS smaller font size for Calendar Event title in stylesheet.css
- Fix SQL error mysqli_fetch_assoc(): Argument 1 must be of type mysqli_result, null given in database.inc.php & StudentsUsersInfo.fnc.php
- When -Edit- option selected, change the auto pull-down to text field in StudentsUsersInfo.fnc.php
- HTML remove bold for "Other students associated with this address/person" in Address.inc.php
- SQL order by FULL_NAME (Display Name config option) in PortalPollNotes.fnc.php, Widget.php, GetStaffList.fnc.php, GetStuList.fnc.php, Transcripts.fnc.php, Courses.php, MassRequests.php, ScheduleReport.php & Address.inc.php
- CSS fix Report Cards PDF columns size when long comments text in ReportCards.fnc.php & stylesheet_wkhtmltopdf.css
- CSS Add .grade-minmax-wrap,.grade-minmax-min,.grade-minmax-grade & .grade-minmax-max classes & avoid breaking grades in stylesheet.css & ReportCards.fnc.php
- Fix get Min. Max. grades for students in distinct grade levels in FinalGrades.php
- Fix SQL syntax error since 10.0 in Administration.php
- CSS Do not break words inside lists in stylesheet.css
- SQL handle case when student dropped and then later re-enrolled in course in DuplicateAttendance.php
- Use DBEscapeIdentifier() for Gradebook ASSIGNMENT_SORTING in Assignments.php, GradebookBreakdown.php & Grades.php

Changes in 10.2
---------------
- Add StudentCanEnrollNextSchoolYear() & StudentEnrollNextSchoolYear() functions in Enrollment.fnc.php
- Add "Enroll student for next school year" in Enrollment.inc.php
- Translate "Enroll student for next school year" to French & Spanish in rosariosis.po
- MySQL fix character encoding when translating database in InstallDatabase.php

Changes in 10.1
---------------
- Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table in TakeAttendance.php, Reminders.php,  InputFinalGrades.php, Requests.php & Calendar.php
- Add dual VIEW for compatibility with MySQL 5.6 to avoid syntax error when WHERE without FROM clause in rosariosis.sql & Update.fnc.php
- Fix MySQL 5.6 syntax error in ORDER BY use report_card_comments table instead of dual in InputFinalGrades.php
- Fix SQL use cast(extract(DOW) AS int) for PostrgeSQL in Calendar.php
- Add instructions for MySQL in INSTALL.md, INSTALL_es.md & INSTALL_fr.md

Changes in 10.0
---------------
- SQL convert table names to lowercase, program wide
- Fix delete file attached in StudentFees.php
- Use DBEscapeIdentifier() for reserved 'column' keyword in plugins/Moodle/
- Avoid regression due to lowercase table names: Maintain compatibility with add-ons using rollover_after action hook & `$_REQUEST['tables']` in Rollover.php
- Use db_trans_*() functions in DeleteTransaction.fnc.php & DeleteTransactionItem.fnc.php
- Close popup if no UserSchool in session, happens on login redirect in Warehouse.php
- SQL order Grade Levels in StudentBreakdown.php
- Remove semicolon before "With" & "On" values in PrintRequests.php & unfilledRequests.inc.php
- HTML Link is selected: bold in ScheduleReport.php
- Display Period title if no short name set in IncompleteSchedules.php
- Fix Widget search & add Search Terms header in IncompleteSchedules.php
- Add Schedule link & photo tooltip to Student name in Scheduling/AddDrop.php
- HTML add a11y-hidden label to select in GPARankList.php & Attendance/TeacherCompletion.php
- Fix unset requested dates in MassCreateAssignments.php & Assignments.php
- Add User / Student photo tooltip in Grades/TeacherCompletion.php, GPARankList.php & EnterEligibility.php
- SQL order by Period title in TeacherCompletion.php
- Use Period's Short Name when > 10 columns in the list in TeacherCompletion.php
- Add note on save in EntryTimes.php
- Fix PHP8.1 Deprecated passing null to parameter in EmailReferral.fnc.php, CategoryBreakdown.php & StudentGrades.php
- Add Total sum of balances in StaffBalances.php
- Fix French translation for "Waiver" & "Refund" in rosariosis.po
- Force title & action to lowercase in Prompts.php
- HTML use .dashboard-module-title CSS class for module titles in Profiles.php & Exceptions.php
- CSS set input label max-width on Search form in stylesheet.css
- JS new default popup size: 1200x450 in warehouse.js
- Use URLEscape() for add button link when appropriate in ListOutput.fnc.php
- JS set Calendar date to current fields date in warehouse.js & calendar-setup.js
- HTML add label to select in ActivityReport.php
- Use Currency() function instead of number_format() in TransactionsReport.php
- HTML remove line-break in Warning/Minimum columns in Reminders.php
- HTML CSS make Daily Menus calendar coherent with School Calendar in DailyMenus.php
- Shorten Referral email subject in EmailReferral.fnc.php
- Use plural wise ngettext() for "No %s were found." in FinalGrades.php, GradeBreakdown.php, ReportCardComments.php, ReportCards.php & Transcripts.php
- Force result text to lowercase for "No %s were found." in ListOutput.fnc.php, FinalGrades.php, GradeBreakdown.php, ReportCardComments.php, ReportCards.php & Transcripts.php
- Prevent admin from removing own access to User Profiles program in Profiles.php
- SQL change modname column type from text to varchar(150) to match with MySQL key index limitation in rosariosis.sql
- SQL change program column type from text to varchar(100) NOT NULL to match with MySQL index limitation in rosariosis.sql
- SQL change schools column type from text to varchar(150) to match with MySQL index limitation in rosariosis.sql
- Rename YEAR_MONTH column alias to YEAR_MONTH_DATE: reserved keyword in MySQL in Dashboard.inc.php
- SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL, program wide
- SQL cast(AS UNSIGNED) for MySQL or cast(AS INT) for PostgreSQL, program wide
- SQL cast custom_fields ID AS char(10) instead of TEXT for MySQL compatibility in GetStaffList.fnc.php, GetStuList.fnc.php & Search.fnc.php
- SQL rename $field COLUMN (reserved keyword) to COLUMN_NAME for MySQL compatibility in CustomFields.fnc.php, GetStaffList.fnc.php, GetStuList.fnc.php & Search.fnc.php
- SQL remove use of nextval in rosariosis_fr.sql
- Rename $pg_dumpPath configuration variable to $DatabaseDumpPath in config.inc.sample.php, diagnostic.php & DatabaseBackup.php
- Build command for executing mysqldump in DatabaseBackup.php
- SQL to extract Unix timestamp or epoch from date in Eligibility/Student.php, StudentList.php & TeacherCompletion.php
- Install module/plugin: execute the install_mysql.sql script for MySQL in Modules.inc.php, Plugins.inc.php & modules/README.md & plugins/README.md
- Fix typo "inexistant" to "nonexistent" & update translations in Modules.inc.php, Plugins.inc.php & rosariosis.po
- HTML fix duplicated #menu-top div on update in Side.php
- JS fix #body height calculation: include bottom margin in jquery-fixedmenu.js & plugins.min.js
- Add MySQLRemoveDelimiter() remove DELIMITER $$ declarations before procedures or functions in database.inc.php, Modules.inc.php & Plugins.inc.php
- SQL ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) for consistency between PostgreSQL & MySQL, program wide
- Rollback Fix PostgreSQL error invalid ORDER BY, only result column names can be used, program wide
- HTML use number input for Gradebook config options in Configuration.php
- HTML use number input for Grade points & average in ReportCardGrades.php
- SQL limit results to current school year in AddDrop.php
- SQL always use INTERVAL to add/subtract days to date for MySQL compatibility in Reminders.php, Transactions.php, ServeMenus.php, Assignments.php, StudentGrades.php, Rollover.php & Portal.php
- SQL change amount columns type from numeric to numeric(14,2) NOT NULL in rosariosis.sql & StudentFees.php
- SQL change minutes,minutes_present,points,default_points,length,count_weighted_factors,count_unweighted_factors columns type from numeric to integer in rosariosis.sql, UpdateAttendanceDaily.fnc.php, Assignments.php, MassCreateAssignments.php & Periods.php
- SQL change gp & gpa columns type from numeric to numeric(7,2) in rosariosis.sql
- SQL change sum/cum factors & credit_attempted/earned columns type from numeric to double precision in rosariosis.sql
- Add Can use modname to HACKING ATTEMPT error email in ErrorMessage.fnc.php
- Fix HACKING ATTEMPT when Grades module inactive in Portal.php & Calendar.php
- Use GetTemplate() instead of unescaping `$_REQUEST` in CreateParents.php & NotifyParents.php
- Use `$_POST` to get password instead of unescaping `$_REQUEST` in PasswordReset.php, Student.php & User.php
- Use DBGetOne() instead of unescaping `$_REQUEST` in Config.fnc.php
- Add MySQL support in database.inc.php, diagnostic.php, InstallDatabase.php & Warehouse.php
- Add $DatabaseType configuration variable in database.inc.php, diagnostic.php, InstallDatabase.php, Warehouse.php & config.inc.php
- Add $show_error parameter to db_start() in database.inc.php
- Add DBUnescapeString() function in database.inc.php, GetStuList.fnc.php, ListOutput.fnc.php, PreparePHP_SELF.fnc.php & Search.fnc.php
- PostgreSQL Date format: move query from Date.php to Warehouse.php
- Compatibility with add-ons version < 10.0, gather CONFIG (uppercase table name) values too in Configuration.php
- Fix MySQL error Table is specified twice, both as a target for 'INSERT' and as a separate source for data in CopySchool.php & Rollover.php
- Fix MySQL syntax error: no table alias in DELETE in Rollover.php
- Fix MySQL syntax error: no FROM allowed inside UPDATE, use subquery or multi-table syntax in Rollover.php
- Fix MySQL syntax error: replace CAST (NULL AS CHAR(1)) AS CHECKBOX with NULL AS CHECKBOX in AddAbsences.php, AddActivity.php, MassDrops.php, MassRequests.php, MassSchedule.php, AddUsers.php, AssignOtherInfo.php & AddStudents.php
- Add Installation tutorial for Mac in WHATS_NEW.md & INSTALL.md, INSTALL_fr.md & INSTALL_es.md
- Update tested on Ubuntu 18.04 to 20.04 in INSTALL.md, INSTALL_fr.md & INSTALL_es.md
- Fix SQL error when column already dropped in Fields.fnc.php
- SQL fix CREATE INDEX on right table in rosariosis.sql
- SQL remove unused indices for various tables in rosariosis.sql
- SQL match index with FOREIGN KEY for various tables in rosariosis.sql
- SQL ORDER BY fix issue when Transferring to another school & new start date is <= old start date in Enrollment.inc.php
- Check if student already enrolled on that date when inserting START_DATE in SaveEnrollment.fnc.php
- Add `_getAddonsSQL()` & `_configTableCheck()` functions in InstallDatabase.php
- $DatabasePort configuration variable is now optional in config.inc.sample.php, INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- SQL start staff_fields ID sequence at 200000000 for coherence with custom_fields in rosariosis.sql & Fields.fnc.php
- MySQL use LONGTEXT type for textarea field in Fields.fnc.php & DisciplineForm.php
- SQL Check requested assignment belongs to teacher in Assignments.php
- CSS fix responsive when really long string with no space in stylesheet.css
- Limit `$_POST` array size to a maximum of 16MB in Warehouse.php, thanks to @ahmad0x1
- Add optional ROSARIO_POST_MAX_SIZE_LIMIT constant in Warehouse.php, INSTALL.md, INSTALL_es.md & INSTALL_fr.md
- Add MySQL database dump in rosariosis_mysql.sql
- Log "RosarioSIS HACKING ATTEMPT" into Apache error.log in HackingLog.fnc.php
- Force URL & menu reloading, always use JS to redirect in HackingLog.fnc.php
- Place currency symbol after amount for some locales in Currency.fnc.php
- SQL use timestamp type: standard & without time zone by default in rosariosis.sql
- CSS add .accounting-totals, .accounting-staff-payroll-totals, .student-billing-totals classes in Expenses.php, Incomes.php, Salaries.php, StaffPayments.php, StudentFees.php & StudentPayments.php
- SQL rename KEY (reserved keyword) to SORT_KEY for MySQL compatibility in Search.fnc.php, StudentFieldBreakdown.php, StudentBreakdown.php
- SQL use GROUP BY instead of DISCTINCT ON for MySQL compatibility in Address.inc.php & EnterEligibility.php
- SQL cast Config( 'STUDENTS_EMAIL_FIELD' ) to int when custom field in SendNotification.fnc.php, Registration.fnc.php, Moodle/getconfig.inc.php & ImportUsers.fnc.php
- Fix MySQL 5.6 error Can't specify target table for update in FROM clause in PortalPollsNotes.fnc.php, DeleteTransaction.fnc.php, DeleteTransactionItem.fnc.php, Rollover.php, CopySchool.php & AssignOtherInfo.php
- Fix MySQL syntax error: explicitly list all columns instead of wildcard in ActivityReport.php & Statements.php
- Fix MakeChooseCheckbox() remove parent link to sort column in Inputs.php & ListOutput.fnc.php
- CSS WPadmin fix menu select width in stylesheet.css
- Enrollment Start: No N/A option for new student in StudentUsersInfo.fnc.php

Changes in 9.3.2
----------------
- Fix regression since 9.2.1 fields other type than Select Multiple from Options in CategoryBreakdownTime.php

Changes in 9.3.1
----------------
- Fix regression since 2.9 Schedule multiple courses in plugins/Moodle/Scheduling/MassSchedule.php
- Fix SQL to select Periods where exists CP in TeacherCompletion.php & Administration.php
- Fix dummy day (year month date) set to 28 for February in Dashboard.inc.php
- Fix AllowEdit for Teacher in Users/includes/General_Info.inc.php
- Security: sanitize filename with no_accents() in Student.php, User.php & Schools.php
- Fix "Exclude PDF generated using the "Print" button" option for the PDF Header Footer plugin in Bottom.php

Changes in 9.3
--------------
- Handle case where Course Period Parent ID is null in Courses.php
- SQL order by Period title in Periods.php, DailySummary.php & StudentSummary.php
- SQL get Period title if no short name set in AddAbsences.php
- Use DBLastInsertID() instead of DBSeqNextID() in Moodle/includes/ImportUsers.fnc.php
- Still use DBSeqNextID() for student ID, adapt for MySQL in Student.php & Moodle/includes/ImportUsers.fnc.php
- SQL use CONCAT() instead of pipes || for MySQL compatibility, program wide
- Fix first item in the list not displayed in Accounting/includes/DailyTransactions.php
- SQL time interval for MySQL compatibility in PasswordReset.php & index.php
- SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility in Dashboard.inc.php & Reminders.php
- SQL result as comma separated list for MySQL compatibility in Grades/includes/Dashboard.inc.php & MasterScheduleReport.php
- Use DBEscapeIdentifier() for MySQL reserved 'TIMESTAMP' keyword in ServeMenus.php & Transactions.php
- SQL add `_SQLUnixTimestamp()` to extract Unix timestamp or epoch from date in Grades.php & Schedule.php
- Add case for MySQL: get next MP ID & set AUTO_INCREMENT+1 in EditHistoryMarkingPeriods.php
- Display Name: SQL use CONCAT() instead of pipes || for MySQL compatibility in Configuration.php & GetStuList.fnc.php
- config table: update DISPLAY_NAME to use CONCAT() instead of pipes || in Update.fnc.php

Changes in 9.2.2
----------------
- Fix SQL error lastval is not yet defined when editing field in SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php, UserFields.php & Assignments.php

Changes in 9.2.1
----------------
- Remove use of db_seq_nextval(), use auto increment, program wide
- SQL set default nextval (auto increment) for RosarioSIS version < 5.0 on install & old add-ons in Update.fnc.php
- SQL no more cast MARKING_PERIOD_ID column as text/varchar in rosariosis.sql & InputFinalGrades.php
- PLpgSQL compact & consistent function declaration in rosariosis.sql
- Use DB transaction statements compatible with MySQL in database.inc.php
- Add DBLastInsertID() & deprecate DBSeqNextID() + db_seq_nextval() in database.inc.php
- SQL rename character varying & character data types to varchar & char in rosariosis.sql
- SQL replace use of STRPOS() with LIKE, compatible with MySQL in PortalPollNotes.fnc.php & Courses.php
- SQL fix French & Spanish translation for Create Parent Users & Notifiy Parents email templates in rosariosis_fr.sql & rosariosis_es.sql
- Use DBLastInsertID() instead of DBSeqNextID(), program wide
- SQL TRIM() both compatible with PostgreSQL and MySQL in AttendanceSummary.php & CopySchool.php
- SQL use extract() or SUBSTRING() or REPLACE() instead of to_char() for MySQL compatibility, program wide
- Fix No Address contact not properly saved for student / parent in RegistrationSave.fnc.php
- AddDBField() Change $sequence param to $field_id, adapted for use with DBLastInsertID() in Fields.fnc.php, SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php & UserFields.php
- Raise Frame file size limit to 5MB in HonorRoll.fnc.php
- Fix Marking Period not found in user School Year (multiple browser tabs case) in MassSchedule.php & MassDrops.php
- Fix Course not found in user School Year (multiple browser tabs case) in MassRequests.php
- HTML add label to inputs in Requests.php
- Remove help sentence. The Scheduler is not run by the Student Requests program in Help_en.php

Changes in 9.2
--------------
- Fix SQL error invalid input syntax for integer in Administration.php
- SQL student_report_card_grades table: convert MARKING_PERIOD_ID column to integer in Update.fnc.php, rosariosis.sql, EditReportCardGrades.php, FinalGrades.php & ReportCards.fnc.php

Changes in 9.1.1
----------------
- Fix PHP8.1 fatal error unsupported operand types: string / int in Assignments.php & MassCreateAssignments.php
- Fix selected Subject lost on Comment Category delete in ReportCardComments.php
- Fix Color Input was hidden in ReportCardComments.php
- Fix use Course ID in session in MassRequests.php
- Fix SQL error primary key exists on table food_service_staff_accounts in Rollover.php
- Fix SQL error foreign key exists on tables gradebook_assignments,gradebook_assignment_types,schedule_requests in Rollover.php
- Fix save State input value in Registration.fnc.php
- Fix SchoolInfo() on user School Year update in School.php

Changes in 9.1
--------------
- Fix stored XSS security issue: decode HTML entities from URL in PreparePHP_SELF.fnc.php, thanks to @domiee13
- Capitalize month when date is only month and year in Dashboard.inc.php
- Add decimal & thousands separator configuration in Help_en.php, Currency.fnc.php, Configuration.php, rosariosis.sql & rosariosis_fr.sql
- Use Currency() for Food Service Balance value in Widget.php & StaffWidget.php
- Add Class average in InputFinalGrades.php & Grades.fnc.php
- Update French & Spanish translation in rosariosis.po & help.po
- Update Default School Year to 2022 in config.inc.sample.php & rosariosis.sql

Changes in 9.0
--------------
- CSS add length to previous meals select in DailyMenus.php
- CSS FlatSIS fix calendar menu text wrapping in stylesheet.css
- Add Export list button in TransactionsReport.php
- Add Food Service icon to list in ServeMenus.php
- Add User / Student photo tooltip in ServeMenus.php, Transactions.php & TeacherCompletion.php
- HTML add horizontal ruler before each category in MakeReferral.php
- Fix SQL error when generating Schedule table with PHP8.1 in GetMP.php
- Reorder PDF list columns to match Schedule columns in PrintSchedules.php
- SQL order Schedule list by Course Title & Course Period Short Name in Schedule.php, PrintSchedules.php & Schedule.inc.php
- Fix SQL error more than one row returned by a subquery in Rollover.php
- Fix update Course Period title when Short Name contains single quote in Courses.php
- Fix PHP8.1 deprecated function parameter is null, program wide
- Fix PHP8.1 deprecated automatic conversion of false to array in StudentsUsersInfo.fnc.php
- Fix PHP8.1 deprecated automatic conversion of float to int in ImageResizeGD.php
- Add Student Photo Tip Message in AddDrop.php & StudentList.php
- Format Enrollment Start & End Date in Export.php
- Add Student name if no Contacts at address in MailingLabel.fnc.php
- Do not Export Delete column in Periods.php & GradeLevels.php
- HTML group inputs inside fieldset (tab title or program name) in Configuration.php
- Hide Comment Codes tip message if Comments unchecked for Marking Period in InputFinalGrades.php
- Add Get Student Labels Form JS (Disable unchecked fieldset) in StudentLabels.fnc.php & StudentLabels.php
- Fix PHP8.1 deprecated use PostgreSQL $db_connection global variable in database.inc.php & Grades/includes/Dashboard.inc.php
- Don't Delete Gender & Birthday Student Fields in Fields.fnc.php
- CSS set cursor for .tipmsg-label in stylesheet.css
- Add Username to Password Reset email in PasswordReset.php
- `intl` PHP extension is now required in diagnostic.php & INSTALL.md
- Fix PHP8.1 deprecated strftime() use strftime_compat() instead in Side.php, Date.php, PHPCompatibility.php, strftime_compat.php, Dashboard.inc.php & Preferences.php
- Add $course_period_id param to limit check to a single Course Period in Courses.fnc.php & Courses.php
- Add title to Contact & Address button images in Address.inc.php & GetStuList.fnc.php
- CSS select max-width 440px in stylesheet.css & zresponsive.css
- HTML add label to Points inputs to correct alignment in Grades.php
- HTML add a11y-hidden label to select in CategoryBreakdown.php, CategoryBreakdownTime.php & StudentFieldBreakdown.php
- Place Go button right after Timeframe in DailyTransactions.php, DailyTotals.php, CategoryBreakdown.php, CategoryBreakdownTime.php, StudentFieldBreakdown.php & Percent.php
- Fix French translation for "Not due" in rosariosis.po
- Move Transcript Include form checkboxes up in Transcripts.fnc.php
- Add Delete button for Submission File in StudentAssignments.fnc.php
- Fix SQL error null value in column "title" violates not-null constraint in MassCreateAssignments.php
- Reorder & rename Course Periods columns to match Schedule program in MassCreateAssignments.php
- Fix get History Grades Grade Level short name only if no Grade Level available in Transcripts.fnc.php
- Fix get Student Photo from previous year in Transcripts.fnc.php
- Fix SQL error invalid input syntax in PrintSchedules.php & TeacherCompletion.php, thanks to @scgajge12
- Filter IP, HTTP_* headers can be forged in index.php, PasswordReset.php & ErrorMessage.fnc.php
- Fix SQL error invalid input syntax for integer, program wide
- Fix PHP8.1 fatal error checkdate argument must be of type int in Calendar.php
- Fix SQL error invalid input syntax for type date in Calendar.php
- Fix SQL error duplicate key value violates unique constraint "attendance_calendar_pkey" in Calendar.php
- Fix PHP fatal error Unsupported operand types in ListOutput.php
- Add AttrEscape() function in Inputs.php
- Use AttrEscape() instead of htmlspecialchars(), program wide
- Add use of AttrEscape(), program wide
- Maintain Advanced search when editing Timeframe in Percent.php
- Fix SQL injection escape DB identifier in RegistrationSave.fnc.php, Calendar.php, MarkingPeriods.php, Courses.php, SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php, UserFields.php & Referrals.php
- JS update marked to v4.0.14 in assets/js/marked/ & warehouse_wkhtmltopdf.js
- JS add DOMPurify 2.3.6 in assets/js/DOMPurify/ & Gruntfile.js
- JS fix stored XSS issue related to MarkDown in warehouse.js & plugins.min.js, thanks to @intrapus
- JS remove logged in check on history back in warehouse.js & plugins.min.js
- Add CSRF token to protect unauthenticated requests in Warehouse.php & login.php
- Add CSRF token to logout URL in login.php, Warehouse.php, PasswordReset.php, Bottom.php, Student.php & User.php, thanks to @khanhchauminh
- Logout after 10 Hacking attempts within 1 minute in HackingLog.fnc.php
- Destroy session now: some clients do not follow redirection in HackingLog.fnc.php
- Add use of URLEscape(), program wide
- Use URLEscape() for img src attribute, program wide
- Sanitize / escape URL as THEME is often included for button img src attribute in User.fnc.php
- Better format for "Add another marking period" form in EditReportCardGrades.php
- Fix Improper Access Control security issue: add random string to photo file name in TipMessage.fnc.php, Transcripts.fnc.php, PrintClassPictures.php, Student.php, User.php & General_Info.inc.php, thanks to @dungtuanha
- Fix stored XSS security issue: decode HTML entities from URL in PreparePHP_SELF.fnc.php, thanks to @khanhchauminh
- Fix stored XSS security issue: remove inline JS from URL in PreparePHP_SELF.fnc.php, thanks to @intrapus & @domiee13
- Fix stored XSS security issue: add semicolon to HTML entity so it can be decoded in PreparePHP_SELF.fnc.php, thanks to @intrapus
- Accessibility: add hidden input label using .a11y-hidden class in ReportCardComments.php, StudentFields.php & Grades/TeacherCompletion.php
- Accessibility: add select label in Eligibility/TeacherCompletion.php, Student.php, StudentList.php, MassDrops.php & MassSchedule.php
- Two Lists on same page: export only first, no search in Eligibility/Student.php
- Remove photos on delete in Student.php & User.php, thank to @jo125ker
- Remove Student Assignment Submission files on delete in Assignments.php, thank to @khanhchauminh
- Add microseconds to filename format to make it harder to predict in Assignments.php & StudentAssignments.fnc.php, thanks to @khanhchauminh
- Restrict Sort Order input number range, program wide
- Restrict Price / Amount / Balance input number range, program wide, thanks to @nhienit2010
- Restrict input number step in Courses.fnc.php
- Restrict diagnostic access to logged in admin in diagnostic.php, thanks to @intrapus
- Fix SQL error value too long for type character varying(50) in Schools.php
- Add Secure RosarioSIS link in INSTALL.md
- Add Calendar days legend in Calendar.php
- CSS add .legend-square class in stylesheet.css & colors.css
- Create / Edit / Delete calendar: use button() in Calendar.php
- Update Calendars help text in Help_en.php & help.po
- Add translations for Calendar days legend in rosariosis.po
- Use json_encode() for AjaxLink() URL, program wide
- SQL skip "No Address" contacts to avoid lines with empty Address fields in Export.php
- French translation: remove capitalization & use articles in rosariosis.po, help.po & rosariosis_fr.sql
- JS Sanitize string for legal variable name in Export.php & Inputs.php
- Remove deprecated `_makeTeacher()` function in ReportCards.fnc.php
- Use multiple select input for grades list to gain space in Widget.php
- Fix regression since 5.0, allow Administration of "Lunch" attendance categories in Administration.php, AttendanceCodes.fnc.php & colors.css
- SQL set default FAILED_LOGIN_LIMIT to 30 in rosariosis.sql, thanks to @domiee13
- JS Hide Options textarea if Field not of select type in Fields.fnc.php
- Add Balance widget in StudentBalances.php
- Add Total sum of balances in StudentBalances.php
- Fix SQL error check requested UserSyear & UserSchool exists in DB in Side.php, Search.fnc.php & SaveEnrollment.fnc.php
- HTML use number input for Class Rank widget in Widget.php
- Check default if school has no default calendar in Calendar.php
- CSS do not capitalize date in stylesheet.css
- Remove unused index ON attendance_period (attendance_code) & ON student_report_card_grades (school_id) in rosariosis.sql & rosariosis_mysql.sql
- SQL VACUUM & ANALIZE are for PostgreSQL only in Scheduler.php


### Old versions CHANGES
- [CHANGES for versions 7 and 8](CHANGES_V7_8.md).
- [CHANGES for versions 5 and 6](CHANGES_V5_6.md).
- [CHANGES for versions 3 and 4](CHANGES_V3_4.md).
- [CHANGES for versions 1 and 2](CHANGES_V1_2.md).
