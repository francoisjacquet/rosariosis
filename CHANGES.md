# CHANGES
## RosarioSIS Student Information System

Changes in 6.2.2
----------------
- Fix Hacking Log when Parent switching Student in Side.php
- Do not allow Parents/Students to add New/Existing Address/Contact in Address.inc.php
- Do not allow Parents/Students to edit Enrollment Records in StudentsUsersInfo.fnc.php
- Rollback Give non admin users access to "No Address" in Address.inc.php
- Smaller buttons for address & contact, use help button for associated students tooltip in Address.inc.php
- Create User/Student Account: unset current User/Student in index.php

Changes in 6.2.1
----------------
- Fix UserSyear() not defined if Create Student Account link accessed directly in index.php

Changes in 6.2
--------------
- CSS Add not allowed cursor for disabled input in stylesheet.css
- HTML make "Poll completed" note fit inside list in PortalPollsNotes.fnc.php
- SQL Handle case when multiple users have same email: order by Failed Login in PasswordReset.php
- HTML Seats, Credit Hours & Credits: use "number" input type in Courses.php & Courses.fnc.php
- HTML Attendance & Food Service tabs: use "number" input type in Configuration.php
- HTML Points: use "number" input type in Assignments.php
- HTML use Tab instead of fieldset in Configuration.php
- Fix Theme live update in Configuration.php & Theme.fnc.php
- Add Configuration School Tabs action Hook in Configuration.php
- Use issetVal() instead of empty(), program wide
- HTML Add title to Block input in CalendarDay.inc.php
- Fix Configuration program name & translate in Help_en.php, help.pot & help.po
- Use makePhone() in Schools.php
- CSS adjust tooltip bottom placement in stylesheet.css
- SQL escape $table in SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php & UserFields.php
- CSS Balance: larger number input in stylesheet.css
- HTML Balance: use "number" input type in Widgets.fnc.php & StaffWidgets.fnc.php
- Remove Fees having a Payment (same Amount & Comments (Title), after or on Assigned Date) in StudentBilling/functions.inc.php

Changes in 6.1
--------------
- Add CoursePeriodDeleteSQL() & CourseDeleteSQL() functions in Courses.fnc.php
- Add SendNotificationNewStudentAccount() & SendNotificationNewUserAccount() functions in SendNotifications.fnc.php
- Translate "Student Account" to French & Spanish in rosariosis.po
- HTML use table + padding for Advanced Search Grade Levels display in Search.fnc.php
- `_makeMultipleInput()` Return HTML instead of echo in StudentsUsersInfo.fnc.php
- Homogenize save for Select Multiple from Options field type format in Student.php, User.php & Schools.php
- MultipleCheckboxInput() Allow numeric key (ID) in associative $options array in Inputs.php

Changes in 6.0
--------------
- HTML Sort Order, Length & Display Column: use "number" input type, program wide
- HTML Amount & Price: use "number" input type in StaffWidgets.fnc.php, Widgets.fnc.php, Accounting/functions.inc.php, MenuItems.php, Transactions.php, Student_Billing/functions.inc.php, MassAssignFees.php & MassAssignPayments.php
- CSS Amount & Price: larger number input in stylesheet.css
- Add TipMessage to Food Service Icon in FS_Icons.inc.php
- HTML standardize Name + ID & Balance headers in Accounts.php, ServeMenus.php, Statements.php & Transactions.php
- JS update jQuery to v2.2.4 in jquery.js
- JS MarkDown use marked instead of showdown (15KB smaller) in assets/js/marked/, plugins.min.js, warehouse.js, warehouse_wkhtmltopdf.js & PDF.php
- JS replace jqPlot with Chart.js in assets/js/Chart.js/, stylesheet.css & Charts.fnc.php
- Charts use Chart.js instead of jqPlot in CategoryBreakdown.php, CategoryBreakdownTime.php, StudentFieldBreakdown.php, GradeBreakdown.php, GradebookBreakdown.php & StudentBreakdown.php
- Do NOT remove Grade Level once has Student enrolled in GradeLevels.php
- Update French & Spanish translations in locale/es_ES.utf8/ & locale/fr_FR.utf8
- Add Warehouse Header Javascripts WarehouseHeaderJS() function in Warehouse.php
- HTML Do not close body & html (footer) if not AJAX in Warehouse.php
- Support multiple values in SelectInput() in Inputs.php
- Remove FirstLoginLoadJSCSS() & use form `target="_top"` instead in FirstLogin.fnc.php
- Handle single quotes in $value with DBEscapeString() in Config.fnc.php
- Logout if no Staff or Student session ID only if not on Modules.php in Warehouse.php, PasswordReset.php & diagnostic.php
- Moodle remove ROSARIO_STUDENTS_EMAIL_FIELD_ID config, use STUDENTS_EMAIL_FIELD in StudentsUsersInfo.fnc.php, Other_Info.inc.php, Moodle/config.inc.php, Moodle/getconfig.inc.php & rosariosis.sql
- Create Student Account: add school_id param to URL in index.php & Student.php
- Create Student Account: Reload page (AJAX) on School change, so we update UserSchool() in General_info.inc.php
- Add MoodleConfig() function in Moodle/getconfig.inc.php
- Use MoodleConfig() & remove load hack in Moodle/functions.php
- Add MoodleXRosarioGet() function in Moodle/functions.php
- Remove IsMoodleStudent(), IsMoodleUser(), IsMoodleCourse() & IsMoodleCoursePeriod() functions in Moodle/functions.php
- Use MoodleXRosarioGet() in plugins/Moodle/* & Courses.php
- HTML add arrow to indicate sub-profile in Profiles.php
- Trim select Options in PortalPolls.php, SchoolFields.php, AddressFields.php, PeopleFields.php, StudentFields.php & UserFields.php
- Update PHPMailer to version 5.2.28 in classes/PHPMailer/
- Add $request_index_3 param to MakeFieldType() in Fields.fnc.php

Changes in 5.9.6
----------------
- Fix SQL error missing FROM-clause entry for table ADDRESS in GetStuList.fnc.php

Changes in 5.9.5
----------------
- JS Fix error in Multiple Input id in StudentsUsersInfo.fnc.php
- Save Select Multiple from Options field type format in Address.inc.php
- Give non admin users access to "No Address" contacts in Address.inc.php

Changes in 5.9.4
----------------
- Automatic Student Account Activation: fix Next grade at current school in Student.php
- Allow multiple categories, do not use require_once in Address.inc.php

Changes in 5.9.3
----------------
- Fix PHP Fatal error Unsupported operand types when Student Billing module deactivated in Incomes.php
- Fix History grades in Transripts: get correct Grade Level in Transcripts.fnc.php

Changes in 5.9.2
----------------
- Charts exclude Fields of "Files" type in StudentBreakdown.php & StudentFieldBreakdown.php
- Charts allow Column type in GradeBreakdown.php
- Fix regression since 5.8 Teachers cannot save Gradebook Configuration in Configuration.php

Changes in 5.9.1
----------------
- Fix Number Field SQL column limit: type numeric(20,2) in StudentsUsersInfo.fnc.php
- Fix SQL error table ADDRESS specified more than once in GetStuList.fnc.php
- SQL Fix School Base Grading Scale for Historical Grades in TRANSCRIPT_GRADES view in Update.fnc.php & rosariosis.sql

Changes in 5.9
--------------
- Import Moodle Users in Moodle/config.inc.php & Moodle/includes/ImportUsers.fnc.php
- Update French & Spanish translations in rosariosis.po
- Move Email & Phone Staff Fields to custom fields in Fields.fnc.php, CustomFields.fnc.php, GetStaffList.fnc.php, Search.fnc.php, Rollover.php, Preferences.php, User.php, General_Info.inc.php & Other_Info.inc.php
- SQL Rename PHONE column to CUSTOM_200000001 in STAFF table in rosariosis.sql & Update.fnc.php
- SQL Add Email & Phone to Staff Fields in rosariosis.sql, rosariosis_es.sql, rosariosis_fr.sql & Update.fnc.php
- CSS Grade Points label line-height in stylesheet.css
- Moodle creates user password if left empty in General_Info.inc.php, Student.php, User.php & Moodle/functions.php
- Do not update Moodle user password in General_Info.inc.php, Student.php, User.php & Moodle/functions.php
- Hide Last Login on Create Account and Add screens in General_Info.inc.php
- Add CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION to CONFIG table in Update.fnc.php & rosariosis.sql
- Automatic Student Account Activation in Configuration.php, Student.php & General_Info.inc.php
- Automatic Moodle Student Account Creation in Moodle/functions.php & Moodle/getconfig.inc.php
- Hide Permissions for "No Access" profile in General_Info.inc.php
- Move Create Student/User Account and New Administrator notifications to functions in SendNotification.fnc.php
- Send Account Activation email notification to Student in SendNotification.fnc.php & Student.php
- Send Account Activation email notification to User in SendNotification.fnc.php & User.php
- Moodle check required username, email in update_student & update_user actions in Moodle/functions.php
- Moodle circumvent bug: no response or error but User created in Moodle/functions.php, User.php & Student.php
- HTML add .br-after span to Course Periods + use DBGetOne() in Side.php
- jQuery already loaded on first-login page, remove it in FirstLogin.fnc.php
- CSS WPadmin pointer cursor for radio & checkbox input in stylesheet.css
- Move REMOVE_ACCESS_USERNAME_PREFIX_ADD from PROGRAM_CONFIG (per school) to CONFIG (all schools, 0) in RemoveAccess.php, rosariosis.sql & Update.fnc.php
- Prevent PHP Fatal error if Kint debug d() function not loaded in Warehouse.php & Debug.fnc.php
- Fix ML default value beginning with `|locale:` in ParseML.php

Changes in 5.8.1
----------------
- Fix Next Year widget: Handle "Retain" case: value is '0' in Widgets.fnc.php
- Fix Search: Use POST for Public Pages plugin compatibility in Courses.php
- Fix MarkDown preview + bold in warehouse.js & Inputs.php
- Fix saving REMOVE_ACCESS_USERNAME_PREFIX_ADD on Grant in RemoveAccess.php

Changes in 5.8
--------------
- Add `_listSearch()` function in ListOutput.fnc.php
- ProgramUserConfig() Set $staff_id to -1 to override user config in Config.fnc.php
- Preferences overridden with USER_ID='-1' in User.fnc.php
- Admin can override teachers gradebook configuration in Configuration.php
- Add GRADEBOOK_CONFIG_ADMIN_OVERRIDE config option in rosariosis.sql
- Add Configuration program to Grades admin menu in Menu.php
- Add Help text for Configuration program for administrators in Help_en.php
- Raise recommended php.ini max_input_vars to 4000, for CreateParents.php compat in INSTALL.md
- Send email notification to Parents is optional in CreateParents.php, sponsored by English National Program
- Delete Student from Moodle in Moodle/functions.php & Moodle/Students/Student.php
- Add Excel_XML class in classes/ExcelXML.php
- Export list to Excel using MicrosoftXML (more reliable) in ListOutput.fnc.php
- Add School_Setup/CopySchool.php|header & School_Setup/CopySchool.php|copy_school action hooks in CopySchool.php
- Add School_Setup/Schools.php|update_school & School_Setup/Schools.php|delete_school in Schools.php
- Fix RedirectURL, unset values in CreateParents.php, MakeReferral.php & Schools.php
- Disable ListOutput save & search for Creation Results in CreateParents.php
- Only display Teachers in Moodle when creating a Course Period in Moodle in Courses.php
- Add Staff Payroll Balance user widget to list in Preferences.php
- Add Permissions & Discipline student widgets to list in Preferences.php
- Update French & Spanish translations in locale/es_ES.utf8/ & locale/fr_FR.utf8
- Use __DIR__ constant & load Moodle plugin using `_LoadAddons()` in Warehouse.php
- Moodle Course category update: no update if TITLE is null in Courses.php
- Moodle Calendar Event creation: fix SQL error if no ID returned by Moodle in Calendar.php
- Moodle Course Category creation: fix SQL error when no course / subject ID returned by Moodle in Courses.php
- Moodle Course Category creation: Ability to set a Parent Category to Subjects. Used by Iomad plugin in Courses.php
- Add Configuration School Table action Hook in Configuration.php
- Hide Modules & Plugins tabs, System config options if Administrator of 1 school only in Configuration.php
- Hide not set Course Period options from non editing users in Courses.php
- Hide Sort Order, Comments, Grade Posting Dates (if MP not Graded) from non editing users in MarkingPeriods.php
- Moodle drop student use `enrol_manual_unenrol_users` WS function in Moodle/functions.php, MassDrops.php, Schedule.php
- Display note messages if any in Student.php & User.php
- Fix SQL error for numeric Student Fields of Number type in AssignOtherInfo.php
- Fix PHP Notice Trying to access array offset on value of type null, program wide
- HTML Set Period input maxlength to 5 & Title input to 100 in Periods.php
- HTML No N/A option for new Type input, maxlength 100 for Title input in EnrollmentCodes.php
- Check for Scale Value when saving new Scale in ReportCardGrades.php
- HTML No N/A option for new Type & State Code inputs in AttendanceCodes.php
- Check for Short Name when saving new Code in AttendanceCodes.php
- HTML shorten Sort Order input & make Price + Staff Price inputs required in MenuItems.php
- Reorder Copy elements & use DBEscapeIdentifier for dynamic columns & tables in CopySchool.php
- SQL Change short_name column type to character varying(3). Now allows French elementary grade levels in GradeLevels.php, Update.fnc.php, rosariosis.sql & rosariosis_fr.sql

Changes in 5.7.7
----------------
- Sort functions by priority in Actions.php
- HTML maxlength 100 for Scale Title input in ReportcardGrades.php

Changes in 5.7.6
----------------
- Only search List if search option activated in ListOutput.fnc.php
- Fix Moodle course title on update in Courses.php

Changes in 5.7.5
----------------
- Fix current scheduled students SQL, index by STUDENT_ID in MassDrops.php

Changes in 5.7.4
----------------
- Increase Currency input max length to 8 in Configuration.php
- GetReportCardsExtra() Define your custom function in your addon module or plugin in ReportCards.fnc.php

Changes in 5.7.3
----------------
- Fix wkhtmltopdf exec crash: Old code for Windows OS in Wkhtmltopdf.php
- Grade Scale Value & Minimum Passing Grade inputs are required in ReportCardGrades.php
- Copy Scale Value & Minimum Passing Grade values in CopySchool.php

Changes in 5.7.2
----------------
- Fix regression since 4.3 SQL error on check if student not already associated in CreateParents.php
- Fix Username field index when text fields in Search.fnc.php

Changes in 5.7.1
----------------
- Set start date: use default RequestedDate() default parameter in CategoryBreakdown.php, CategoryBreakdownTime.php & StudentFieldBreakdown.php
- Fix Print Class Pictures for Parents & Students in PrintClassPictures.php

Changes in 5.7
--------------
- SQL ADDRESS table: city & mail_city column type to text in rosariosis.sql, Address.inc.php & Update.fnc.php
- SQL ADDRESS table: state & mail_state column type to character varying(50) in rosariosis.sql, Address.inc.php & Update.fnc.php
- Send Create Student / User Account email to Notify in Student.php, User.php, config.inc.sample.php
- Do not find other students associated with "No Address" in Address.inc.php
- Fix error when start date < first school year start date in CategoryBreakdownTime.php

Changes in 5.6.5
----------------
- Add php.ini recommended configuration settings in INSTALL.md, INSTALL_fr.md & INSTALL_es.md
- Fix last row (GPA or Total) position in Transcripts.fnc.php

Changes in 5.6.4
----------------
- Fix wkhtmltopdf crash on large PDF in Wkhtmltopdf.php

Changes in 5.6.3
----------------
- Accessibility HTML format Input in MassAssignFees.php & MassAssignPayments.php
- Fix Due Date is not required in MassAssignFees.php

Changes in 5.6.2
----------------
- Fix SQL error on delete when current Student/Staff ID was lost (in other browser tab) in Student.php & User.php
- Fix SQL error foreign keys: check if can DELETE from schools and staff in Rollover.php
- Fix SQL error foreign keys: Roll again Report Card Comment Codes when rolling Courses in Rollover.php
- Fix SQL error NULL as TITLE when various. Explicitely list rollover MP titles in Rollover.php
- Roll Users again: update users which could not be deleted in Rollover.php

Changes in 5.6.1
----------------
- Fix Delete enrollment record if start date is empty in SaveEnrollment.fnc.php

Changes in 5.6
--------------
- Fix saving new student/user when current Student/Staff ID set (in other browser tab) in Student.php & User.php
- Fix SQL error on save when current Student/Staff ID was lost (in other browser tab) in Student.php & User.php
- Always do custom fields substitutions when Student in Substitutions.fnc.php
- CSS add Library & SMS modules icons in stylesheet.css, wkhtmltopdf.css & icons.css
- Translate non core module Title in Help.php
- HTML smaller font size for Contact details title in ViewContact.php
- HTML add Emergency & Custody button to Contact Info tip message in GetStuList.fnc.php
- Support option groups (`<optgroup>`) by adding 'group' to $extra in Inputs.php
- Add Class Search Widget functions in ClassSearchWidget.fnc.php
- Remove mySearch(), use ClassSearchWidget() function in PrintClassLists.php & PrintClassPictures.php
- Add "No Students were found." message for Parents in Side.php
- User Profile restrictions for non admins in General_Info.inc.php

Changes in 5.5.4
----------------
- CSS Rollback Allow for browser address bar to disappear on scroll in zresponsive.css

Changes in 5.5.3
----------------
- Better base64 images detection in MarkDownHTML.fnc.php

Changes in 5.5.2
----------------
- Fix save first language in ML fields if not en_US.utf8 in Inputs.php

Changes in 5.5.1
----------------
- Password Input: Fill input value if $value != '********' in Inputs.php
- Fix SQL error when requested calendar ID is not integer in Calendar.php
- CSS Fix #275 WPadmin .teacher-programs-wrapper border-width in stylesheet.css, thanks to Christian Foucher
- Fix SQL error when Amount contains letters in Transactions.php
- Fix Add a User save Select Multiple from Options in User.php

Changes in 5.5
--------------
- Display Title of: Subject, Course, Course Period in PrintSchedules.php, sponsored by Tintamail
- Fix #255 Create Assignments for CP of same course but different teachers in MassCreateAssignments.php, thanks to Bacila Andrei
- Fix SQL error REPORT_CARD_GRADES table: Cut titles > 5 chars in Update.fnc.php
- SQL REPORT_CARD_GRADES table: Change title column type to character varying(5) in rosariosis.sql, Update.fnc.php & ReportCardGrades.php
- Fix required check for TextInput(), PasswordInput() & SelectInput() in Inputs.php
- Make Final Grading Percentages inputs required in Configuration.php
- Input type number for Breakoff & Final Grade Percentages in Configuration.php
- Format Grade Breakoff input title in Configuration.php
- Use Grade Scale value if Teacher Grade Scale Breakoff value is not set in \_makePercentGrade.fnc.php
- Close #46 Add Custom Fields to Substitutions in Substitutions.fnc.php, sponsored by École Étoile du Matin
- Use Custom Fields in Substitutions in HonorRoll.php, HonorRoll.fnc.php, Reportcards.fnc.php, Transcripts.fnc.php & Letters.php

Changes in 5.4.7
----------------
- Fix Students List for various Course Periods in PrintClassPictures.php

Changes in 5.4.6
----------------
- Fix Free, Studies Certificate text & Substitutions for admin users in ReportCards.fnc.php & Transcripts.fnc.php

Changes in 5.4.5
----------------
- Fix SQL error when no corresponding Grade found in Scale in InputFinalGrades.php

Changes in 5.4.4
----------------
- Fix regression since 5.0 Use Course Title as key for grades array to keep 1 line per course in Transcripts.fnc.php

Changes in 5.4.3
----------------
- SQL fix set_updated_at trigger PostgreSQL 8.4 compatible in rosariosis.sql, Update.fnc.php

Changes in 5.4.2
----------------
- Fix "Strengthen allow edit logic for teachers" when TeacherPrograms (admin) in InputFinalGrades.php
- SQL Set 50 minutes length for School Periods in rosariosis.sql
- HTML Short Name input is required in GradeLevels.php
- Fix SQL error in calc_gpa_mp function on INSERT Final Grades: column short_name does not exist, PostgreSQL 8.4 in rosariosis.sql & Update.fnc.php

Changes in 5.4.1
----------------
- Add CREATED_AT & UPDATED_AT columns to every table, 93 tables in Update.fnc.php
- Add set_updated_at() function & set_updated_at trigger in Update.fnc.php

Changes in 5.4
--------------
- Enrollment Start: No N/A option if already has Drop date in StudentsUsersInfo.fnc.php
- Delete enrollment record if start date is empty in SaveEnrollment.fnc.php
- Update current school to enrollment school in SaveEnrollment.fnc.php
- Remove / deprecate $type param from ColorInput() in Inputs.php & program wide
- CSS add input[type=color] & .color-input-value in stylesheet.css
- Add Match password action hook in Password.php & Actions.php
- Add Student Billing alerts in Portal.php & Student_Billing/includes/PortalAlerts.fnc.php
- Do not Display Teacher Programs frame if is program modfunc PDF in TeacherPrograms.php
- Explicitly check for `$_REQUEST['search_modfunc'] === 'list'` in Search.fnc.php
- Add Progress Reports program for admin (Teacher Programs), student & parent in ProgressReports.php, Menu.php & rosariosis.sql
- Add Help texts for Progress Reports program in Help_en.php, help.po
- Format code pulldown using MakeAttendanceCode() in Administration.php
- French & Spanish translation review: correct plural forms & typos in rosariosis.po
- Raise minimum PHP version from 5.3.2 to 5.4.45 in INSTALL.md, diagnostic.php & Portal.php
- Test first if can add foreign key based on reported SQL errors: column "student_id" referenced in foreign key constraint does not exist in Update.fnc.php
- Add Bulgarian translation in bg_BG.utf8/LC_MESSAGES/rosariosis.po, thanks to Martin Krastev
- Deprecate Filter `$_ROSARIO['ReferralInput']` global. Use &$input instead in referral.fnc.php

Changes in 5.3.4
----------------
- Fix #142 Remove invalid option '-i' from pg_dump command since PostgreSQL 9.5 in DatabaseBackup.php

Changes in 5.3.3
----------------
- Fix "Allow Teachers to edit grades after grade posting period" config option in InputFinalGrades.php

Changes in 5.3.2
----------------
- Fix regression since 4.4 save unchecked config option: use CheckboxInput() in Preferences.php

Changes in 5.3.1
----------------
- Fix Last row inputs for non admin users in Transcripts.fnc.php & ReportCards.fnc.php

Changes in 5.3
--------------
- Fix SQL error more than one row returned by a subquery in Search.fnc.php & Preferences.php
- Add TinyMCE before init action hook in Inputs.php
- Reduce ReferralInput() function complexity in Referral.fnc.php
- Split EmailReferral(): add EmailReferralGetReferralSafe() & EmailReferralFormatFields() functions in EmailReferral.fnc.php
- Split ReferralLogsGenerate(): add ReferralLogsGetExtra() & ReferralLogsGetReferralHTML() functions in ReferralLog.fnc.php
- Split UpdateAttendanceDaily(): add AttendanceDailyTotalMinutes() function in UpdateAttendanceDaily.fnc.php
- Deprecate StudentLabelsPDF() & MailingLabelsPDF(): add StudentLabelsHTML() & MailingLabelsHTML() functions in StudentLabels.fnc.php & StudentLabels.php
- Split GetStudentLabelsExtra(): add GetStudentLabelsExtraAdmin() & GetStudentLabelsExtraNonAdmin() functions in StudentLabels.fnc.php
- Split StudentLabelsHTML(): add StudentLabelHTML() function in StudentLabels.fnc.php
- Split MailingLabelsHTML(): add MailingLabelFormatAddressesToStudent() & MailingLabelFormatAddressesToFamily() functions in StudentLabels.fnc.php
- Fix bug when selected Attendance code is "All": set value to 0 in Administration.php
- Use Daily Attendance data for report table in AttendanceSummary.php
- Place Attendance Summary program before Utilities separator in Custom/Menu.php
- Translate "Remove Access" custom program to "Bloquer l'accès" in French in rosariosis.po & help.po
- Add FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN to CONFIG table in rosariosis.sql & Update.fnc.php
- Add Force password change on first login option in Configuration.php
- Force password change on first login in index.php & FirstLogin.fnc.php
- Add help & translations for Password Strength & Force Password Change on First Login in Help_en.php, help.po & rosariosis.po
- Add IsFirstLogin() & HasFirstLoginForm() functions in FirstLogin.fnc.php
- Add Action hooks doc in plugins/README.md & modules/README.md
- Rename School Configuration program to Configuration in School_Setup/Menu.php
- Rename School separator to Setup in School_Setup/Menu.php
- HTML Simplify Handbook markup & title in Help.php
- Add Installation directions for Docker in INSTALL.md
- Delete obsolete data first to prevent SQL errors when adding foreign keys. Based on reported error in Update.fnc.php
- Add note to add new year for Marking Periods program in Help_en.php & help.po

Changes in 5.2
--------------
- Add $max_file_size param & max file size validation in Inputs.php & warehouse.js
- Add Organization radio inputs in FirstLogin.fnc.php
- Add db_sql_filter() function in database.inc.php
- Add db_trans_rollback() function in database.inc.php
- Add DBTransDryRun() function in database.inc.php
- Deprecate $connection param for db_trans_start(), db_trans_commit() functions in database.inc.php
- Remove $connection param for db_trans_query() in database.inc.php
- Add $show_error optional param to db_query() & db_trans_query() in database.inc.php
- Use new db_trans_* functions in Scheduler.php
- Add .list-outer & dynamic CSS class using $plural in ListOutput.fnc.php
- Fix Detailed View link condition in Statements.php
- Add StudentDeleteSQL() function in Student.fnc.php
- Add UserDeleteSQL() function in User.fnc.php
- Add MarkingPeriodDeleteSQL() function in MarkingPeriods.fnc.php
- Add SchoolDeleteSQL() function in Schools.fnc.php
- Show Delete button if can delete Student, User, Marking Period, School in Student.php, User.php, MarkingPeriods.php & Schools.php
- CSS FlatSIS fix submenu hover width in stylesheet.css
- Use text type instead of character varying in rosariosis.sql
- Add NOT NULL constraint to TITLE columns in rosariosis.sql & Update.fnc.php
- Add \_update52beta() function in Update.fnc.php
- Make New School's Title input required in CopySchool.php
- Fix SQL error rename sequence to course_period_school_periods_course_period_school_periods_i_seq in Update.fnc.php

Changes in 5.1.1
----------------
- Fix SQL error when requested ID is not integer in Courses.php
- Optimize allowed modname loop in Modules.php
- Fix SQL Medical Immunization or Physical Widget WHERE in Widgets.fnc.php

Changes in 5.1
--------------
- Medical Immunization or Physical Widget in Search.fnc.php & Widgets.fnc.php
- Add dash between Salary / Fee title and Comment in DailyTransactions.php
- CSS add list-wrapper class to empty list (having add row) in ListOutput.fnc.php
- Add \_makePaymentsCommentsInput() with Salaries / Fees dropdown to reconcile Payment in Accounting/functions.inc.php & Student_Billing/functions.inc.php
- Rename default Resource links in rosariosis.sql, rosariosis_es.sql & rosariosis_fr.sql
- Remove deprecated \_makeTipMessage() function in TakeAttendance.php, Grades.php & InputFinalGrades.php
- Add db_query() function in database.inc.php
- Install add-ons in InstallDatabase.php
- Rename "Edit Student Grades" program to "Historical Grades" in Menu.php, Help_en.php & help.po
- CSS Allow for browser address bar to disappear on scroll in zresponsive.css
- JS Prevent scrolling body while Menu is open in warehouse.js
- CSS FlatSIS grey title + fix Colorbox padding issue on mobile in stylesheet.css

Changes in 5.0.5
----------------
- Fix SQL errors when MP ID is not integer in MarkingPeriods.php
- Fix SQL error when no Days to insert in Calendar.php
- Fix SQL error regression since 5.0-beta when inserting people relation in Registration.php
- Format phone input fields in Registration.php

Changes in 5.0.4
----------------
- Update French & Spanish translations in rosariosis.po
- FlatSIS change download & help buttons in themes/FlatSIS/btn/
- Add basket, briefcase, calculator, chart, clipboard, clock, down, folder, heart, key, label, music, pencil, picture, screen, settings, star & up buttons in themes/FlatSIS/btn/ & themes/WPadmin/btn/
- Compress buttons using compresspng.com in themes/FlatSIS/btn/


Changes in 5.0.3
----------------
- Fix SQL error set TITLE input maxlength to 100 in AttendanceCodes.fnc.php
- SQL use integer type for TABLE_NAME column in rosariosis.sql
- Fix PHP notices in EditHistoryMarkingPeriods.php, StudentGrades.php & Profiles.php

Changes in 5.0.2
----------------
- Fix regression since 5.0 teacher cannot edit grades in InputFinalGrades.php

Changes in 5.0.1
----------------
- Fix SQL error foreign key on Course Period delete in Courses.php
- SQL COURSE_PERIODS table: Change title column type to text in Update.fnc.php & rosariosis.sql
- Fix SQL error DROP course_details VIEW first to then recreate it in Update.fnc.php
- Fix SQL error foreign keys: Process tables in reverse order in Rollover.php
- Add delete mode to Rollover() to handle delete first in reverse, then inserts in Rollover.php
- Move rollover_checks & rollover_after action hooks outside loop in Rollover.php & Moodle/function.php

Changes in 5.0
--------------
- Use integer column type instead of numeric in rosariosis.sql
- SQL Remove DISCIPLINE_CATEGORIES table in rosariosis.sql & Rollover.php
- SQL Move PRIMARY KEY & UNIQUE to CREATE TABLE in rosariosis.sql
- SQL Remove UNIQUE & INDEX constraints for PRIMARY KEY in rosariosis.sql
- SQL Add CREATED_AT & UPDATED_AT columns to every table in rosariosis.sql
- SQL Add set_updated_at() function & set_updated_at trigger in rosariosis.sql
- SQL use serial for IDs in rosariosis.sql
- SQL remove & rename existing sequences in rosariosis.sql & Update.fnc.php
- SQL Rename sequences for add-on modules in Update.fnc.php
- SQL Add DBSeqConvertSerialName() function for compatibility database.inc.php
- SQL Use new serial ID sequence names, program wide
- SQL Add foreign keys: student_id, staff_id, school_id+syear, marking_period_id, course_period_id, course_id in rosariosis.sql & Update.fnc.php
- SQL create tables & insert data in right order in rosariosis.sql
- SQL Use NEXTVAL() on data insert in rosariosis.sql & rosariosis_fr.sql
- SQL fix on delete constraints in Schools.php,& Student.php
- Float grade points: remove trailing 0 in Schools.php & EditReportCardGrades.php
- Delete school only if has NO students enrolled in Schools.php
- MP has Course Periods? Do NOT delete in MarkingPeriods.php
- Accessibility CSS do not remove outline in stylesheet.css
- Add Bulgarian translation, thanks to Vanyo Georgiev in locale/bg_BG/
- HTML format Prompt tables list in CopySchool.php & Rollover.php
- Default Text input size is 12 in Inputs.php
- Remove Add a New / Existing Contact without an Address links in Address.inc.php
- Use openstreetmap.org to map addresses in Address.inc.php
- HTML stack question inputs & required question title in PortalPolls.php
- Fix export to XLS, delimit strings in ListOutput.fnc.php
- Load PHP Debug bar in ProgramFunctions/Debug.fnc.php
- Use Auto select input for custom contact info description in Address.inc.php
- Change error to "No Grades were found" in FinalGrades.php, ReportCards.php, Transcripts.php
- Reorder Course Period option inputs in Courses.fnc.php
- Fix PHP notices, program wide
- SQL fix Change index suffix from '\_IND' to '\_IDX' to avoid collision in Fields.fnc.php
- Add GetReportCardsComments() function in ReportCards.fnc.php
- Add issetVal() function in Warehouse.php
- Cannot delete teacher if has course periods in User.php
- Add Kint() & PhpDebugBar() functions in Debug.fnc.php
- Convert MarkDown doc to PDF files in README.pdf, INSTALL.pdf, INSTALL_es.pdf & INSTALL_fr.pdf
- Add FlatSIS theme in assets/themes/FlatSIS/
- Add Automatic Class Rank calculation in ReportCards.php
- Add GPA or Total row in Grade.fnc.php, ReportCards.fnc.php & Transcripts.fnc.php
- Add GetReportCardCommentScales(), GetReportCardGeneralComments(), GetReportCardCourseSpecificComments() & \_getReportCardCommentPersonalizations() functions in ReportCards.fnc.php
- New Comment Code Scales format in ReportCards.fnc.php
- Add Free Text to Report Cards form in ReportCards.fnc.php & ReportCards.php
- Save Template even if no default template found in Template.fnc.php
- Fix $staff_id for admin user in \_makeLetterGrade.fnc.php
- Add Min. and Max. Grades option in ReportCards.fnc.php, FinalGrades.php
- JS remove deprecated IE8 HTML5 tags fix & touchScroll() in warehouse.js
- CSS Better readability: format Help text in 3 columns in Bottom.php & stylesheet.css & warehouse.js
- "Back to Student/User/Course Search" button removed in Bottom.php
- Do not update Bottom.php on Student/User Search in Search.inc.php
- Add Do Rollover warning when School Year has ended in Portal.php
- Add Attendance Codes Tip Message to header & use Color codes in Administration.php & StudentSummary.php
- Merge Period & Teacher Comment columns to gain space in StudentSummary.php
- Format Meals using PopTable() in Kiosk.php


### Old versions CHANGES
- [CHANGES for versions 3 and 4](CHANGES_V3_4.md).
- [CHANGES for versions 1 and 2](CHANGES_V1_2.md).
