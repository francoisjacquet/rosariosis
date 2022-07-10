# CHANGES for versions 3 and 4
## RosarioSIS Student Information System

Changes in 4.9.11
-----------------
- Fix new MP not selected after Save in MarkingPeriods.php

Changes in 4.9.10
-----------------
- Fix regression since 4.8 Transcripts for various years in Transcripts.fnc.php

Changes in 4.9.9
----------------
- Format Half Day attendance & date in Dashboard.inc.php
- Fix regression since 4.9.2 Students appear in double in Side.php

Changes in 4.9.8
----------------
- Fix SQL error set RosarioSIS ID maxlength to 9 in General_Info.inc.php & Student.php

Changes in 4.9.7
----------------
- Fix regression since 4.9 Meal Item Description & Short Name fields required in MenuItems.php
- Fix PHP error undefined function HumanFilesize() in StudentUsersInfo.fnc.php

Changes in 4.9.6
----------------
- Fix hide "Create Student/User in Moodle" checkbox for public registration & non admins in Moodle/functions.php

Changes in 4.9.5
----------------
- Fix Submission timestamp in StudentAssignments.fnc.php
- Fix SQL error Food Service account already exists in Student.php

Changes in 4.9.4
----------------
- Fix regression since v4.3 translated Help file include in Help.fnc.php
- Fix Admin User Profile restriction in General_Info.inc.php
- Fix Student Gender select in ReportCards.fnc.php
- Fix Comments in FinalGrades.php
- Fix Class Rank calculus, display & allow for Quarters in Transcripts.fnc.php & Transcripts.php

Changes in 4.9.3
----------------
- Fix regression since v3.4.2 "Fix SQL error if MP was deleted" in ScheduleReport.php

Changes in 4.9.2
----------------
- CSS fix calendar below FlatSIS theme top menu in stylesheet.css
- SQL fix error when "Food Service Balance minimum amount" empty in Portal.php
- Fix SQL error when Parent have students enrolled in deleted school in Schools.php & Side.php
- Fix Partial days regression since 4.8 cannot save Minutes in Calendar.php

Changes in 4.9.1
----------------
- Fix #219 Parents & Teachers can Edit User Info in User.php

Changes in 4.9
--------------
- Only display Categories having fields in AssignOtherInfo.php
- Accessibility: add missing input label in AssignOtherInfo.php
- program_config table: Add Allow Teachers to edit gradebook grades for past quarters option in Update.fnc.php, rosariosis.sql & Configuration.php, sponsored by Santa Cecilia school
- Accessibility: add missing input label in Widgets.fnc.php
- Do not search custom Fields of the Files type in Search.fnc.php & Preferences.php
- Move Course Period option inputs + Takes Attendance input to functions in Courses.php & Courses.fnc.php
- Move Course Period title generation to function in Courses.php & Courses.fnc.php
- Move Course Period School Periods title part generation to function in Courses.php & Courses.fnc.php
- Remove "Days" mention in Course Period School Periods title part  in Courses.fnc.php
- JS fix submenu offset after (de)activating Module or Plugin in warehouse.js
- Fix SQL error when SHORT_NAME already in use in MenuItems.php
- Meal Item Description & Short Name fields required in MenuItems.php
- Fix SQL error cast Staff ID to int in Config.fnc.php
- Fix #269 Grades dashboard: display Assignments total in Dashboard.inc.php
- Fix month_date URL param for Attendance category tab link in TakeAttendance.php
- Add Comments column to Grades in EditReportCardGrades.php, sponsored by ITS Japón school
- CSS fix calendar below FlatSIS theme top menu in calendar-blue.css
- Fix Grade Level for history grades in Transcripts.fnc.php
- Fix regression since 3.9.2 Advanced Student Search not working for Student Info program in Widgets.fnc.php

Changes in 4.8.6
----------------
- Fix regression since 4.5 cannot save Teacher Programs Permissions in Profiles.php & Exceptions.php

Changes in 4.8.5
----------------
- Fix Course Period choose checkbox in PrintClassPictures.php

Changes in 4.8.4
----------------
- Fix regression other MP grades not saved in InputFinalGrades.php

Changes in 4.8.3
----------------
- Add Install RosarioSIS database script in InstallDatabase.php
- Check before first login in InstallDatabase.php

Changes in 4.8.2
----------------
- Fix PHP error do_action() not defined when $Timezone set in Warehouse.php

Changes in 4.8.1
----------------
- Fix regression from 4.7 cannot add new Period in Periods.php

Changes in 4.8
--------------
- Accessibility: add alt attribute to images, program wide
- Accessibility: use onfocus instead of onclick in Inputs.php
- Accessibility: hidden input title using .a11y-hidden class in Inputs.php, Calendar.php, CalendarDay.inc.php
- Accessibility: add hidden input label using .a11y-hidden class, program wide
- Accessibility: add hidden column title using .a11y-hidden class, program wide
- Accessibility: Fix FormatInputTitle() to avoid <label> when not relevant, program wide
- Accessibility: add missing input label, program wide
- Add language in flag title if php-intl extension active in index.php, Inputs.php
- Remove allowed fields table check in Fields.fnc.php
- Check for Course Period Teacher conflict in Courses.php & Courses.fnc.php
- Group Assignments by Type inside dropdown in Grades.php
- JS Fix infinite loop when exporting to image in Charts.fnc.php & jquery.jqplot.js
- JS optimize jqplotToColorBox() function in jquery.jqplottocolorbox.js
- Use TinyMCE input for Studies Certificate text in Transcripts.php
- Move Studies Certificate title and Signatures HTML to Template in Transcripts.php, rosariosis.sql, rosariosis_es.sql & rosariosis_fr.sql
- Add TranscriptsIncludeForm() function in Transcripts.fnc.php & Transcripts.php
- Add Transcripts header action hook in Transcripts.php
- Add TranscriptsGenerate() & \_getTranscriptsStudents() functions in Transcripts.fnc.php & Transcripts.php
- Add Transcripts PDF HTML array action hook in Transcripts.php
- Add TranscriptPDFHeader() & TranscriptPDFFooter() functions in Transcripts.fnc.php
- Add Transcript PDF Header & Footer action hooks in Transcripts.fnc.php
- Search Parents by Student Grade Level in Search.fnc.php & GetStaffList.fnc.php, sponsored by Santa Cecilia school
- Add link to associated Student / Parent Info in AddStudents.php & AddUsers.php
- Add Remove Access program in Custom/RemoveAccess.php
- Update default school year to 2019 in rosariosis.sql & config.inc.sample.php
- Set User ID & Student ID inputs maxlength to 5000 in Search.fnc.php

Changes in 4.7.2
----------------
- Fix #266 Address & People Fields Delete URL in Fields.fnc.php

Changes in 4.7.1
----------------
- Fix PHP fatal error "Can't use function return value in write context" (PHP 5.4) in Student.php & Address.inc.php

Changes in 4.7
--------------
- Clean code: remove else when not necessary in functions/*
- SQL use NOT EXISTS(SELECT...) instead of NOT IN(SELECT...) in MassCreateAssignments.php
- Remove "Edit Pull-Down" field type, program wide
- Add \_update47beta(), Convert "Edit Pull-Down" fields to "Auto Pull-Down" in Update.fnc.php
- Remove "Coded Pull-Down" field type, program wide
- Convert "Coded Pull-Down" fields to "Export Pull-Down" in Update.fnc.php
- Change Pull-Down (Auto & Export), Select Multiple from Options, Text, Long Text columns type to text in Update.fnc.php & Fields.fnc.php & rosariosis.sql
- Custom fields input maxlength: 50000 for textarea, 1000 for text in StudentsUsersInfo.fnc.php, Search.fnc.php, Registration.php
- Set select, text, multiple, textarea columns type to text in Disciplineform.php, Referral.fnc.php
- Remove GPA / MP List program in GPAMPList.php, Menu.php & rosariosis.sql
- Add CLASS_RANK_CALCULATE_MPS to config table in Update.fnc.php & rosariosis.sql
- Add ClassRankMaybeCalculate(), ClassRankCalculateAJAX() & ClassRankCalculateAddMP() in ClassRank.inc.php
- Automatic Class Rank calculation in EditReportCardGrades.php, GPARankList.php, InputFinalGrades.php, Transcripts.php
- SQL performance: rewrite set_class_rank_mp() function in rosariosis.sql & Update.fnc.php
- SQL remove calc_cum_gpa_mp() function & include it in t_update_mp_stats() trigger in rosariosis.sql & Update.fnc.php
- Remove Calculate GPA program in CalcGPA.php, Menu.php, Help_en.php & rosariosis.sql
- Do NOT remove School Period once associated to Course Periods in Periods.php
- Do NOT remove Course Period once has Student enrolled in Courses.php
- Fix PHP error Include Student/User Info tab from custom plugin in PrintStudentInfo.php

Changes in 4.6.2
----------------
- SQL Fix error, regression after PHP 7.3 compat in InputFinalGrades.php

Changes in 4.6.1
----------------
- SQL Fix more than one row returned by a subquery error in HonorRoll.fnc.php

Changes in 4.6
--------------
- Show SQL query & format in db_show_error() in database.inc.php
- CSS display accessibility link on focus in stylesheet.css
- Accessibility: Add "Skip to main content" link in Bottom.php
- JS Reset focus after AJAX so "Skip to main content" a11y link has focus first in warehouse.js
- PHP 7.3 compat: use count() for array variables only, program wide
- SQL eligibility_activities table: Add COMMENT column in Update.fnc.php & rosariosis.sql
- Add Comment column to Activities list in Activities.php
- Format PHP code, program wide
- Use StudentUsersInfo \_make\*Input functions in Schools.php
- Add \_makeFilesInput() function & $options_RET parameter to \_makeAutoSelectInput() in StudentsUsersInfo.fnc.php
- Add Files input type in Schools.php, Student.php, User.php, Other_Fields.inc.php & Other_Info.inc.php
- Add FilesUploadUpdate() function in FileUpload.fnc.php
- JS Navigate form inputs vertically using tab key in Grades.php & InputFinalGrades.php
- Performance Run multiple DELETE SQL queries at once, progam wide
- Merge Address Fields & Contact Fields programs with Student Fields program in StudentFields.php, AddressFields.php, PeopleFields.php & Menu.php
- Add FirstLoginPoll() function in FirstLogin.fnc.php

Changes in 4.5.2
----------------
- Fix SQL error when only month with RequestedDate() in Date.php

Changes in 4.5.1
----------------
- Move Header head action hook outisde page condition in Warehouse.php
- Fix SQL error, do not allow N/A in Timeframe date inputs in Calendar.php
- JS Add ajaxPopState() function & Fix logout when back button & URL is Modules.php in warehouse.js
- Upload photo when no other fields are posted in Student.php & User.php
- Make makeProfile() function reusable & better display in GetStaffList.fnc.php
- Fix SaveTemplate dynamic modname in Template.fnc.php
- Allow associative $options array for MultipleCheckboxInput() in Inputs.php

Changes in 4.5
--------------
- Add Calendar header hook in Calendar.php
- Add Calendar Day functions in CalendarDay.inc.php & Calendar.php
- Can omit DBQuery call in DBGet.fnc.php
- Add DBSeqNextID() function in database.inc.php
- Remove db_greatest() & db_least() in database.inc.php & Grades.php
- Add DBGetOne() function in DBGet.fnc.php
- Include Student/User Info tab from custom plugin in Student.php, StudentFields.php, User.php, UserFields.php
- Format Numeric field display in ReferralLog.fnc.php
- Add Report Cards header action hook in ReportCards.php
- Add Report Cards PDF header action hook in ReportCards.fnc.php
- HTML add autocomplete="off" to select inputs in Side.php
- Add GetFullYearMP() function in GetMP.php
- Reorganize screen layout & fix list search & order in PrintClassLists.php
- Remove autocomplete for checkboxes & use stackable div responsive layout in Export.php
- Add referral to various students at once in MakeReferral.php, sponsored by Santa Cecilia school
- Fix Discipline Referrals portal alert requested dates in Widgets.fnc.php
- RequestedDate() Recursive function: use request index and default value in Date.php
- Set start, end & other dates using new RequestedDate() function parameters, program wide
- Move headers to StudentAssignmentDrawHeaders() function in StudentAssignments.fnc.php
- Move assignment details from Tip message to Colorbox popup in StudentGrades.php
- Remove \_makeTextInput() & use \_makeCommentsInput() function instead in ReportCardComments.php & ReportCardCommentCodes.php
- Use SelectInput, RadioInput, CheckboxInput & TextInput functions in Grades/Configuration.php
- Show Hide letter grades for all gradebook assignments option only if Global Config allows for Letter grades in Grades/Configuration.php
- Hide letter grades for all gradebook assignments in Grades.php
- gradebook_assignment_types table: Add CREATED_MP column in rosariosis.sql & Update.fnc.php
- Add "Hide assignment types for previous quarters" option in Grades/Configuration.php, MassCreateAssignments.php & Assignments.php, sponsored by Santa Cecilia school
- Spanish translation: "bimestre" => "trimestre" in es_ES.utf8/rosariosis.po & help.po
- Add ReferralInput() function in Referral.fnc.php, MakeReferral.php & Referrals.php
- Add Referral Input action hook in Actions.php & Referral.fnc.php
- Fix DateInput name (was not saved) & error when empty Number input  in Referral.fnc.php & Referrals.php
- Add Rollover After action hook in Rollover.php
- Deprecate School_Setup/Rollover.php|rollover_[table] action hooks in Rollover.php & Moodle/functions.php
- Change Description field for TinyMCE input in MassCreateAssignments.php
- Trim Assignment Type title before grouping them in MassCreateAssignments.php
- Remove Teacher Programs from Attendance & Grades menus in Menu.php

Changes in 4.4.2
----------------
- Fix Password input do not check Strength case in Inputs.php

Changes in 4.4.1
----------------
- Fix Referrals Multiple Checkbox Input options in Referrals.php
- Fix MultipleCheckboxInput() title was displayed twice in Inputs.php

Changes in 4.4
--------------
- Add Warehouse Header hook in Warehouse.php
- Add DBQuery after hook in database.inc.php
- Leave Delete button AFTER the Save one so info are saved on Enter keypress in Schools.php
- JS Adjust Side.php submenu bottom offset when footer menu is on top in warehouse.js
- Do not check allowed tables, sanitize table name instead in Fields.fnc.php
- Fix extra link when FULL_NAME overridden in Search.inc.php
- Cookie secure flag for https in Warehouse.php
- Override default From in SendEmail.fnc.php
- Remove Go button (useless) in StudentBreakdown.php
- Add $value param to INSERT or UPDATE for Config, ProgramConfig & ProgramUserConfig functions in Config.fnc.php
- Use Config() for UPDATE in FirstLogin.fnc.php, Update.fnc.php
- Use ProgramConfig() for UPDATE in Configuration.php, EntryTimes.php, Moodle/config.inc.php
- Use ProgramUserConfig() for UPDATE in Grades/Configuration.php, Preferences.php
- gradebook_assignments table: Add FILE column in rosariosis.sql & Update.fnc.php
- gradebook_assignments table: Change DESCRIPTION column type to text in rosariosis.sql & Update.fnc.php
- gradebook_assignments table: Convert DESCRIPTION values from MarkDown to HTML.
- Change Description field for TinyMCE input in Assignments.php
- Add UploadAssignmentTeacherFile function & Adapt function for Teachers in StudentAssignments.fnc.php
- Add File Attached upload & download in Assignments.php
- Add PasswordInput() function in Inputs.php
- Add PasswordStrength jQuery plugin in jquery-passwordstrength.js & plugins.min.js
- Use PasswordInput() function in both General_Info.inc.php
- Use PasswordInput() & remove Verifiy New Password in PasswordReset.php
- JS Add zxcvbn (password strength estimator) in zxcvbn.js
- Add PASSWORD_STRENGTH to config table in Update.fnc.php & rosariosis.sql
- Add Password Strength & strength bars to Security in Configuration.php
- CSS bigger input (checkbox, select) size in stylesheet.css
- JS Load once on page load & always check height on resize & scroll in jquery-fixedmenu.js
- JS Open submenu on touch (mobile & tablet) in warehouse.js
- Remove scrollTop / SCROLL_TOP setting in warehouse.js, Warehouse.php, User.fnc.php & Preferences.php

Changes in 4.3.4
----------------
- Fix Course Periods SQL query in Grades/TeacherCompletion.php

Changes in 4.3.3
----------------
- Fix Assignments day display in Calendar.php
- SQL Fix Portal Assignments schedule dates for parent & student in Portal.php

Changes in 4.3.2
----------------
- Leave Delete button AFTER the Save one so info are saved on Enter keypress in Schools.php

Changes in 4.3.1
----------------
- Fix SQL syntax error in AddAbsences.php

Changes in 4.3
--------------
- Add MakeChooseCheckbox() function in Inputs.php
- Add FoodServiceReminderOutput() function in Reminders.php
- SQL courses table: Add DESCRIPTION column in Update.fnc.php
- Add Description (TinyMCE input) to Course in Courses.php
- Add DESCRIPTION column to courses table + when rolling Courses in rosariosis.sql & Rollover.php
- Add missing GP_PASSING_VALUE column when rolling Report Card Grade Scales in Rollover.php
- Add HelpBindTextDomain, HelpLoad, GetHelpText, & GetHelpTextRaw functions in Help.fnc.php
- Performance: static DB $connection in database.inc.php
- Add list-wrapper CSS class in ListOutput.fnc.php
- CSS Fix wkhtmltopdf issue where table header overlaps first row in wkhtmltopdf.css
- CSS Add .wkhtmltopdf-header, .wkhtmltopdf-footer, .wkhtmltopdf-portrait & .wkhtmltopdf-landscape classes in PDF.php & wkhtmltopdf.css
- Fix SQL error for FOOD_SERVICE_ACCOUNT table when adding student reusing deleted student ID in Student.php
- Fix SQL error when Parent user exists and is already associated to student in CreateParents.php
- Add SubstitutionsInput & SubstitutionsTextMake functions in Substitutions.fnc.php
- Use Substitutions functions in CreateParents.php, NotifyParents.php, HonorRoll.php, HonorRoll.fnc.php, Transcripts.php & Letters.php
- Add referrer to ErrorSendEmail() in ErrorMessage.fnc.php
- Improve User Permissions program warning & translations in Exceptions.php
- Instead of displaying a fatal error which could confuse user, display a warning and exit in TakeAttendance.php
- CSS add .teacher-programs-wrapper class in TeacherPrograms.php, stylesheet.css, zresponsive.css & colors.css
- Fix GetMailingLabelsFormHTML AllowUse check in StudentLabels.fnc.php
- Add Help texts & translations for Create Parent Users program in Help_en.php & help.po
- Group SQL inserts & deletes in AddActivity.php, MassDrops.php, MassAssignFees.php & MassAssignPayments.php
- Add \_makeEmail & \_makeAssociated functions in CreateParents.php & NotifiyParents.php
- Fix #259 Prevent XSS: Sanitize the newly created MarkDown text in MarkDownHTML.fnc.php, thanks to @DustinBorn
- Reload menu now so it does not contain links to disallowed programs in HackingLog.fnc.php

Changes in 4.2
--------------
- CSS fix responsive image height in stylesheet.css
- HTML Use width attribute for icon images in index.php, MenuItems.php & FS_Icons.inc.php
- CSS Fix tooltip displaying over side menu in stylesheet.css
- Add Assignment Grades Submission column action hook in StudentAssignments.fnc.php
- CSS TinyMCE Fullscreen above bottom menu & "Insert/Edit image" popup too large on mobile devices in stylesheet.css
- Add MultipleCheckboxInput() function in Inputs.php & Referrals.php
- Fix \_help gettext function so it works with add-on modules and plugins in Help_en.php
- SQL config table: Change config_value column type to text in rosariosis.sql & Update.fnc.php
- SQL fix more than one row returned by a subquery error in Food_Service/includes/Dashboard.inc.php
- Fix standard Search form Grade Levels input: select in Search.fnc.php

Changes in 4.1
--------------
- Add Assignments header action hook in Assignments.php
- Add icon before module title in Profiles.php & Exceptions.php
- Grade posting date inputs are required when "Graded" is checked in MarkingPeriods.php
- Upgrade showdown to version 1.7.6 in assets/js/showdown/ & warehouse.js
- Add Assignments & Assignment Submission header action hook in StudentAssignments.php
- Fix SQL error when no MPs in calcSeats0.fnc.php
- Format Credits: no 0 decimal in Transcripts.php
- Redirect automatically to Portal after 5 seconds in HackingLog.fnc.php
- Fix #257 SQL get current year Grade Level in Reportcards.fnc.php, thanks to @solida

Changes in 4.0
--------------
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
- Set default Incident Date for Referrals program only in Widgets.fnc.php
- Add Dashboard* functions in ProgramFunctions/Dashboard.fnc.php & DashboardModule.fnc.php
- Add modules data for dashboard in modules/\*/includes/Dashboard.inc.php
- Move Updates for version 2 and 3 in UpdateV2_3.fnc.php
- Fix SQL error in calc_gpa_mp function on INSERT Final Grades for students with various enrollment records in rosariosis.sql & Update.fnc.php
- Update TinyMCE to v4.8.0 in assts/js/tinymce/, Inputs.fnc.php & stylesheet.css
- Update Chosen to v1.8.7 in assets/js/chosen/
- Update jQuery MiniColors to v2.3.1 in assets/js/jquery/minicolors/
- Update jQuery Form to v4.2.2 in jquery.form.js & plugins.min.js
- Fix SQL error when no quarters MP are setup yet in Schedule.inc.php
- Move "Print Mailing Labels" program into "Print Student Labels" in MailingLabels.php, StudentLabels.php, StudentLabels.fnc.php
- Remove profile_id param from redirect_to logout URL in index.php
- Add "« Back" link to Portal or automatic redirection if has just logged in HackingLog.fnc.php
- Add LAST_LOGIN column to Student user SQL in User.fnc.php
- Fix SQL error when no Payent mean is selected in Transactions.php
- Add functions for First Login Form in index.php & FirstLogin.fnc.php
- Set admin password on first login in FirstLogin.fnc.php
- Remove deprecated GetRawPOSTvar() function in getRawPOSTvar.fnc.php
- Allow for button files missing the "\_button" suffix in Buttons.php
- Update French & Spanish translations in rosariosis.po
- Format Credits: no 0 decimal in Courses.php
- Define custom ReportCardsIncludeForm & ReportCardsGenerate functions in ReportCards.fnc.php
- Add Report Cards array hook action in ReportCards.php

Changes in 3.9.2
----------------
- No button when printing PDF in Buttons.php
- Fix PHP Notice Undefined variable / index, program wide
- Set default Incident Date in Widgets.fnc.php
- Fix regression UpdateAttendanceDaily() call when Updating in Administration.php
- Fix Set default Incident Date for Referrals program only in Widgets.fnc.php
- Fix Calendar Events display in Calendar.php
- Hotfix PHP Parse error missing ) in conditions in Grades.php
- Fix PHP 5.4 error int argument in Translator.php

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
- Fix #244: Add DISPLAY_NAME to config table for every school in Configuration.php, rosariosis.sql & Update.fnc.php, sponsored by Asian Hope
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
- #218 Add DISPLAY_NAME to config table in rosariosis.sql & Update.fnc.php
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
- Fix Save $\_REQUEST vars in session: if not printing PDF in Modules.php

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
