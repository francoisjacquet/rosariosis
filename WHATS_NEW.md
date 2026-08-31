# WHAT'S NEW

## RosarioSIS Student Information System

New in 12.9
-----------

Default School Year: 2026 (2026-2027)

Security and SQL performance improvements

**Breaking change**, please upgrade the following add-ons:
- [Reports](https://www.rosariosis.org/modules/reports/) module 12.1+ (May 2026)
- [NFC/QR Actions](https://www.rosariosis.org/modules/nfc-qr-actions/) module 2.6+ (June 2026)
- [Parent Agreement](https://gitlab.com/francoisjacquet/Parent_Agreement/) plugin 10.5+ (May 2026)

Add Italian translation, thanks to Stefania Binda

[Chrome To PDF](https://gitlab.com/francoisjacquet/Chrome_To_PDF) plugin, sponsored by École Saint-Martin, France

[Student Import Premium](https://www.rosariosis.org/modules/students-import/)
- Delete existing students before import (optional, only if all students can be deleted)

[Billing Elements](https://www.rosariosis.org/modules/billing-elements/)
- Monthly Elements, sponsored by École Sainte Marie, France


New in 12.8
-----------

School
- Database Backup: removed (secure by default)

[Backup](https://gitlab.com/francoisjacquet/Backup) module (now provides the _Database Backup_ program)

Spanish translation: do not capitalize words & use "tú" instead of "usted"

Add slide effect when opening or closing left menu and help

Please update the [CoolAdmin](https://www.rosariosis.org/themes/cooladmin/) theme to version 3+

PHP8.5 compatibility

[Unsaved Changes Warning](https://gitlab.com/francoisjacquet/Unsaved_Changes_Warning) plugin, sponsored by International Philippine School in Jeddah


New in 12.7
-----------

**Breaking changes**:
- Refactoring: autoload classes (PSR-4). Files inside the `classes/` folder were moved to match their namespace
- Moodle: remove XML-RPC compatibility classes, see [plugins/Moodle/README.md](plugins/Moodle/README.md)

Add Czech translation, thanks to David Kapitančik


New in 12.6
-----------

[Content Security Policy](https://en.wikipedia.org/wiki/Content_Security_Policy) (phase 2): report only

[Content Security Policy](plugins/Content_Security_Policy/README.md) plugin
- Report CSP violations and allow external domains

School
- Configuration: Create Student Account: Force Default School

Add Russian & Ukrainian translations, thanks to Aleksey

Add [rounded flag icons](https://www.flaticon.com/packs/countrys-flags), thanks to Flaticon

[Timetable Import](https://www.rosariosis.org/modules/timetable-import/)
- Relate teacher based on RosarioSIS ID


New in 12.5
-----------

**Breaking change**, please upgrade the following plugin:
- [Assignment Max Points](https://gitlab.com/francoisjacquet/Assignment_Max_Points/) 1.5+ (July 2025)

[Content Security Policy](https://en.wikipedia.org/wiki/Content_Security_Policy) (phase 1): prepare
- Move inline Javascript to external files
- Use curl to send information to other domains instead of AJAX or form action

[CoolAdmin](https://www.rosariosis.org/themes/cooladmin/) theme, sponsored by International Philippine School in Jeddah

[Hostel Premium](https://www.rosariosis.org/modules/hostel/)
- Automatically remove inactive students from their room (performed once a day), sponsored by AT group, Slovenia


New in 12.4
-----------

**Breaking change**, please upgrade the following add-on:
- [TinyMCE Record Audio Video](https://www.rosariosis.org/plugins/tinymce-record-audio-video/) plugin version 10.4+ (April, 2025)

Security: add [`.htaccess`](https://en.wikipedia.org/wiki/.htaccess) file to block direct access to potentially sensitive files

Students
- Add / Drop Breakdown over Time: new report under _Add / Drop Report_, sponsored by Rousseau International, Cameroon

[Meeting](https://www.rosariosis.org/modules/meeting/) & Meeting Premium modules, sponsored by English National Program, France


New in 12.3
-----------

Default School Year: 2025 (2025-2026)

Grades
- Input Final Grades: "N/A" grade (empty GPA value & Breakoff) is now handled by the "Get Gradebook Grades" link

Scheduling
- Courses: Multilingual course (and subject) title

[PDF Header Footer Premium](https://www.rosariosis.org/plugins/pdf-header-footer/) plugin
- Per program Header / Footer, sponsored by Rousseau International, Cameroon
- Substitution codes to insert school information, page number, document title or current date, sponsored by Lamine Samate, Mali

[Class Diary Premium](https://www.rosariosis.org/modules/class-diary/) module
- Teachers can edit existing entries, sponsored by Form'alacarte, France

Themes:
- Remove unused font files, 427KB gain
- WPadmin: convert ttf to woff2 font files, 105KB gain

Move SQL translation file to locale/[locale_code]/ folder


New in 12.2
-----------

Raise minimum **PostgreSQL** version from 8.4 to 9.2.

Security:
- Move RosarioSIS version number from login screen to Portal (admin only)
- Remove INSTALL & README PDF files (use MarkDown files instead)

Add [WebP](https://en.wikipedia.org/wiki/WebP) image format support

Mailing Labels: Zip Code before City for non English speaking countries

Grades
- Grading Scales: "N/A" grade (empty GPA value & Breakoff) is now a [grade that does not affect GPA](https://www.rosariosis.org/forum/d/1238-is-there-a-way-to-add-a-grade-that-does-not-affect-the-gpa)

[Microsoft Social Login](https://www.rosariosis.org/plugins/microsoft-social-login/) plugin


New in 12.1
-----------

School
- Marking Periods: Remove "only 1 Marking Period & 1 grading period open at any given time" check for Progress Periods, sponsored by AEP St-Joseph-des-Carmes, France

Users
- User Profiles: add Admin Delete Permission (Accounting Expenses/Incomes/Salaries/Staff Payments), sponsored by Lamine Samate, Mali

Scheduling
- Courses: add "Show only my courses" checkbox, sponsored by Paris'Com Sup, France

Student Billing
- Daily Transactions: add Breakdown by Grade Level to Daily Totals, sponsored by Rousseau International, Cameroon

[Entry and Exit](https://www.rosariosis.org/modules/entry-exit/) module

[NFC/QR Actions](https://www.rosariosis.org/modules/nfc-qr-actions/) module

[TTHotel Smart Locks](https://www.rosariosis.org/modules/tthotel-smart-locks/) module

The above modules are sponsored by AT group, Slovenia

[Email Parents](https://www.rosariosis.org/modules/email-parents/) module
- Send Days Absent (for a specific period), sponsored by Paris'Com Sup, France

[Certificate](https://www.rosariosis.org/modules/certificate/) module
- Add background image / watermark, sponsored by Paris'Com Sup, France

[Messaging Premium](https://www.rosariosis.org/modules/messaging/) module
- Notify me by email when I receive a message, sponsored by Paris'Com Sup, France

[Audit](https://gitlab.com/francoisjacquet/Audit) module
- Add Browsing Log, sponsored by AT group, Slovenia


New in 12.0
-----------

**Breaking change**, please upgrade the following add-ons:
- [Email SMTP](https://www.rosariosis.org/plugins/email-smtp/) plugin version 10.4+ (March, 2024)
- [Email Log](https://gitlab.com/francoisjacquet/Email_Log) module version 1.2+ (March, 2024)
- [Reports](https://www.rosariosis.org/modules/reports/) module version 10.6+ (May, 2024)
- [Human Resources](https://www.rosariosis.org/modules/human-resources/) module version 10.2+ (August, 2023)
- [Calendar Schedule View](https://www.rosariosis.org/plugins/calendar-schedule-view/) plugin version 10.2+ (July, 2024)
- [Quiz](https://www.rosariosis.org/modules/quiz/) module version 10.7+ (December, 2024)
- [Assignment Max Points](https://gitlab.com/francoisjacquet/Assignment_Max_Points/) plugin version 1.2+ (December, 2024)

Performance improvements: smaller payload, up to 10% faster & less AJAX requests sent to the server

Use [colorBox](http://www.jacklmoore.com/colorbox/) instead of popup window for Course & Request Widgets and Calendar Event

Some optional configuration variables are now **deprecated** (still work if set in the `config.inc.php` file):
- `$PortalNotesFilesPath` Path to portal notes attached files.
- `$AssignmentsFilesPath` Path to student assignments files.
- `$FS_IconsPath` Path to food service icons.

Those files are now uploaded under a subdirectory of `assets/Fileuploads/`. See the [`$FileUploadsPath`](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/INSTALL.md#optional-variables) config variable.

Add-on installations statistics (on first activation). To disable, use the [`ROSARIO_DISABLE_USAGE_STATISTICS`](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/INSTALL.md#optional-variables) config constant.

[New translations](locale/REFERENCE.md) (99% completed, includes help), thanks to Georgios Katakalos
- Greek (Greece)
- Turkish (Turkey)

[Stripe Registration](https://www.rosariosis.org/plugins/stripe-registration/) plugin, sponsored by English National Program, France

[Library Premium](https://www.rosariosis.org/modules/library/)
- Quick Loan: quickly search and lend a document to a user or student, sponsored by AT group, Slovenia

[Billing Elements](https://www.rosariosis.org/modules/billing-elements/)
- Daily Transactions, sponsored by Petit Rousseau, Cameroon


New in 11.8
-----------

School
- Configuration: Move "Number of Days for the Rotation" option from School Information to Configuration

Students
- Advanced Report: Add "Course Periods - Short Name" field

Scheduling
- Print Class Lists: Add "Course Periods - Short Name" field

Grades
- Gradebook Configuration: Add "Automatically calculate and save Final Grades using Gradebook Grades" option, sponsored by AEP Saint-Joseph-des-Carmes, France

[Staff and Parents Import](https://www.rosariosis.org/modules/staff-parents-import/) module
- Assign users to a specific school, sponsored by LGBTQInnos, USA

[Templates](https://www.rosariosis.org/plugins/templates) plugin
- Integrates with the [Messaging Premium](https://www.rosariosis.org/modules/messaging/) module, sponsored by @Hirama666


New in 11.7
-----------

Default School Year: 2024 (2024-2025)

School
- Rollover: update the default school year (if the `config.inc.php` file can be edited). [Rollover Guide PDF](https://www.rosariosis.org/documentation/) update

Limit SQL result for List: Improve performance for lists > 1000 results (e.g., schools having more than 1000 students)

[Templates](https://www.rosariosis.org/plugins/templates) plugin
- Integrates with the _Grades > Report Cards_ program (Free Text), sponsored by Petit Rousseau, Cameroon


New in 11.6
-----------

Users
- My Preferences > Print Options: Mailing Label Position (left or right)

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/): link your [Stripe](https://stripe.com/) account and accept payments. Stripe is [available in 46 countries](https://stripe.com/global).

[Custom Menu](https://gitlab.com/francoisjacquet/Custom_Menu/) plugin sponsored by AT group, Slovenia


New in 11.5
-----------

Moodle plugin: Add REST API protocol for Moodle 4.1+ and MoodleCloud compatibility

Scheduling
- Courses: on course teacher change, new teacher will inherit old teacher's assignments

Students
- Letters: Hide Headers option

[Templates](https://www.rosariosis.org/plugins/templates) plugin

[Email Log](https://www.rosariosis.org/modules/email-log) module

[PDF Archive](https://gitlab.com/francoisjacquet/PDF_Archive) module

[Append Custom Field to Grade Level](https://gitlab.com/francoisjacquet/Append_Custom_Field_to_Grade_Level) plugin

The above add-ons are sponsored by AT group, Slovenia

Slovenian translation (71% complete), thanks to AT group, Slovenia

License change from GNU/GPLv2 to GNU/GPLv2 or later so it is compatible with GNU/GPLv3


New in 11.4
-----------

School
- Access Log: Automatically clear entries older than one year

Scheduling
- Courses: Reassign Course Subject, sponsored by Paris'Com Sup, France

Grades
- Assignments (Student): Attach multiple files, sponsored by Paris'Com Sup, France
- Report Cards: Group courses by subject, sponsored by Paris'Com Sup, France
- Input Final Grades: Use Grade Scale Comments, sponsored by Petit Rousseau, Cameroon

[Food Service Premium](https://www.rosariosis.org/modules/food-service-premium/) module, sponsored by AT group, Slovenia

[SMS Premium](https://www.rosariosis.org/modules/sms/)
- Add Substitution codes to send GPA to Students, sponsored by Petit Rousseau, Cameroon


New in 11.3
-----------

[Absent for the Day on First Absence](https://gitlab.com/francoisjacquet/Absent_for_the_Day_on_First_Absence) plugin, sponsored by Paris'Com Sup, France

Scheduling
- Student Schedule + Group Schedule: Refuse to enrol the student twice in the same course period

Export list to Excel using [SimpleXLSXGen](https://github.com/shuchkin/simplexlsxgen) (more reliable)

School
- Database Backup: MySQL dump now includes procedures, functions and triggers

To disable responsive list layout, add `&LO_disable_responsive=Y` to the URL


New in 11.2
-----------

School
- Configuration, Attendance tab: Dynamic Daily Attendance calculation based on total course period minutes (when "Minutes in a Full School Day" is set to 0)

Student Billing
- Fees + Payments + Daily Transactions: Expanded View (Created by & Created at columns), sponsored by Rousseau International, Cameroon

[Lesson Plan](https://www.rosariosis.org/modules/lesson-plan/) module, sponsored by Rousseau International, Cameroon

[Class Diary Premium](https://www.rosariosis.org/modules/class-diary/#premium-module)
- Attach multiple files, sponsored by Paris'Com Sup


New in 11.1
-----------

Scheduling
- Courses:
  - Automatically update teacher: Fix the false "Missing attendance" portal alerts
  - Automatically update credits (attempted and earned); will also recalculate GPA

[Grades Import](https://www.rosariosis.org/modules/grades-import/)
- Import Final Grades, sponsored by Univers Frère Raphaël, Haiti

[Students Import Premium](https://www.rosariosis.org/modules/students-import/#premium-module)
- Create Food Service Accounts (and assign a Barcode)

[Billing Elements](https://www.rosariosis.org/modules/billing-elements/)
- Mass Assign Elements: Assign Elements by Grade Level, sponsored by Rousseau International, Cameroon


New in 11.0
-----------

Grades
- Report Cards: Add Class Average & Class Rank (Course Period)
- Progress Reports: move from Teacher Programs to Grades menu (admin)
- Configuration: Add Weight Assignments option

[Assignment Max Points](https://gitlab.com/francoisjacquet/Assignment_Max_Points) plugin

The above enhancements are sponsored by École Étoile du Matin, France

Accounting
- Incomes: fiter by date, assign categories
- Expenses: fiter by date, assign categories
- Daily Transactions: filter by category
- Add Categories

Thanks to @0xD0M1M0 for the above enhancements

Food Service
- Accounts: Create missing Food Service Student Account


New in 10.9
-----------

Add Portuguese (Brazil) translation, thanks to Emerson Barros

Add Vietnamese (Vietnam) translation, thanks to Steven M. Haag & Trân Thi Kim Thanh

Update German (Germany) translation, thanks to @0xD0M1M0

Lists: add pagination if more than 1000 results. Deactivated by default, only activated for:
- Grading Scales
- Access Log

Teacher Programs:
- Prevent the following issue: If form is displayed for Course Period A, then Teacher opens a new browser tab and switches to Course Period B. Then teacher submits the form, data is saved for Course Period B.

RosarioSIS can now be installed with [Softaculous](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/How-to-install-RosarioSIS-with-Softaculous)


New in 10.8
-----------

Resources
- Resources: add Resource Visibility options, sponsored by @Hirama666


New in 10.7
-----------

Use [Select2](https://select2.org/) instead of Chosen for enhanced select inputs

Grades
- Report Cards: add "Class average" option (Last row)
- Report Cards: add "Student Photo" option, sponsored by Paris'Com Sup

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/)
- Print Invoices/Receipts: add "Two per page" checkbox

[Library Premium](https://www.rosariosis.org/modules/library/)
- Library: limit document Category Visibility to selected User Profiles and Grade Levels, sponsored by @Hirama666


New in 10.6
-----------

Files input: automatically resize & compress uploaded images (only if width > 1988px or height > 2810px)


New in 10.5
-----------

[SMS Premium](https://www.rosariosis.org/modules/sms/): new gateway, WhatsApp Cloud API, sponsored by EspaceHitech.mg


New in 10.4
-----------

[Hostel](https://www.rosariosis.org/modules/hostel/) module

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/)
- Configuration: Add Payment Reminder to Portal, sponsored by @Hirama666

Student Billing: Attach File to existing Fee/Payment

Accounting: Attach File to existing Income/Expense or Salary/Staff Payment


New in 10.3
-----------

School
- Rollover: Add "Course Periods" checkbox

Student Billing
- Student Balances: Add "Cumulative Balance over school years" checkbox


New in 10.2
-----------

Students
- Student Info: Add "Enroll student for next school year" link to Rolling / Retention Options

Student / User Listing: order by "Display Name" (was Last Name, First Name)


New in 10.0
-----------

Add **MySQL** support

config.inc.sample.php
- `$DatabaseType` configuration variable
- `$DatabasePort` configuration variable is now optional

[Installation tutorial for Mac (macOS, OS X)](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/How-to-install-RosarioSIS-on-Mac-(macOS,-OS-X))

#### Breaking changes

SQL table names were converted to lowercase. If you have any _custom_ CSS, JS (div or input ID and names) or PHP code relying on UPPERCASE table names, please update.

#### Know more

Know more about RosarioSIS version 10 and MySQL support in this [blog post](https://www.rosariosis.org/blog/#mysql-support).


New in 9.1
----------

PHP8.1 compatibility

School
- Configuration: Decimal & thousands separator

Grades
- Input Final Grades: Class average, sponsored by Paris'Com Sup


New in 9.0
----------

PHP8.1 compatibility

`intl` PHP extension is now required

Security improvements

French translation revised, thanks to Étienne de Blois

School
- Calendars: legend

[Quiz Premium](https://www.rosariosis.org/modules/quiz/)
- Answer Breakdown: Identify questions for which students have difficulties to answer


New in 8.9
----------

Food Service
- Meal Items: icon upload

Scheduling
- Courses: Half Day option removed


New in 8.8
----------

Grades
- Report Cards:
  - Min. and Max. GPA
  - Class Rank

[Email Students](https://www.rosariosis.org/modules/email-students/)
- Automatically send Absence notifications, after X registered absences
- Automatically send Birthday notifications
- Automatically send Payment reminders (outstanding fees), X days before or after Due date
Sponsored by Mr Marinsek, Argentina


New in 8.7
----------

Fix menu & scroll issue on recent smartphones (landscape resolution > 735px)

FlatSIS theme
- Do not import WPadmin theme stylesheet anymore
- Use Grunt to minify
- Improve load time & payload


New in 8.5
----------

Users
- User Profiles: Admin Student Payments Delete restriction, sponsored by Rousseau International school

[Email Parents](https://www.rosariosis.org/modules/email-parents/)
- Automatically send (child's) Birthday notifications
- Automatically send Payment reminders (outstanding fees), X days before or after Due date
Sponsored by Rousseau International school

[SMS Premium](https://www.rosariosis.org/modules/sms/#premium-module)
- Automatically send (child's) Birthday Notifications to Parents
- Automatically send Payment Reminders (outstanding fees) to Parents, X days before or after Due date
Sponsored by Rousseau International school

[Quiz Premium](https://www.rosariosis.org/modules/quiz/#premium-module) module, sponsored by Dzung Do


New in 8.4
----------

Accounting
- Incomes: add File Attached
- Expenses: add File Attached


New in 8.3
----------

Accounting
- Staff Payments: add File Attached

Student Billing
- Payments: add File Attached, sponsored by Paris'Com Sup


New in 8.2
----------

[Class Diary Premium](https://www.rosariosis.org/modules/class-diary/#premium-module)
- Send email reminder to Teachers who did not add an entry for yesterday's classes, sponsored by Paris'Com Sup


New in 8.1
----------

Accounting
- Salaries: add File Attached

Student Billing
- Fees: add File Attached


New in 8.0
----------

Attendance
- Attendance Chart: Merge Attendance Chart (Daily Summary) & Absence Summary programs

Accounting & Student Billing
- Daily Transactions: Merge Daily Transactions & Daily Totals programs

Students
- Advanced Report: Total from Fees & Total from Payments


New in 7.9
----------

[Class Diary](https://www.rosariosis.org/modules/class-diary/) module


New in 7.7
----------

[Google Social Login](https://www.rosariosis.org/plugins/google-social-login/) plugin, sponsored by Santa Cecilia school


New in 7.6
----------

PHP8 compatibility

Moodle plugin: PHP xmlrpc extension no longer required

[Automatic Attendance](https://www.rosariosis.org/plugins/automatic-attendance/) plugin, sponsored by Paris'Com Sup

Security improved


New in 7.4
----------

School
- Configuration: Course Widget select / Pull-Down option, sponsored by English National Program


New in 7.2
----------

Discipline
- Breakdown by Student Field: Grade Level breakdown.

[Certificate](https://www.rosariosis.org/modules/certificate/) module, sponsored by Paris'Com Sup

[Email](https://www.rosariosis.org/modules/email/) module:
- Attach a file, sponsored by English National Program


New in 7.1
----------

Students
- Student Breakdown: Grade Level breakdown.

Users
- Preferences: select among 10 Date Formats.

Grades
- Report Cards: include Credits.


New in 7.0
----------

School & Attendance: improved Numbered Days Rotation

Scheduling
- Schedule Report: Merge Schedule Report & Master Schedule Report
- Requests Report: Merge Requests Report & Unfilled Requests


New in 6.9
----------

Teachers: simplify Course Periods dropdown menu. Only select School Periods to Take Attendance.

Scheduling
- Courses: Secondary Teacher, sponsored by English National Program


New in 6.8
----------

Security enhanced


New in 6.7
----------

[Staff Absences](https://www.rosariosis.org/modules/staff-absences/) module, sponsored by English National Program


New in 6.6
----------

Default School Year: 2020

Custom (Students)
- Registration: Administrators can customize the Registration form, sponsored by English National Program

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/) module:
- Accept Paypal payments.


New in 6.5
----------

[SMS Premium](https://www.rosariosis.org/modules/sms/#premium-module) module:
- Send Absence Notification to Parents.


New in 6.4
----------

School
- Configuration: Add-on zip upload (check [INSTALL.md](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/INSTALL.md#optional-variables) to disable).

[Students Import Premium](https://www.rosariosis.org/modules/students-import/#premium-module) module:
- Update Existing Students info, sponsored by English National Program.


New in 6.3
----------

[Jitsi Meet](https://www.rosariosis.org/modules/jitsi-meet/) module, sponsored by Santa Cecilia school.


New in 6.1
----------

[Students Import](https://www.rosariosis.org/modules/students-import/) module:
- Send email notification to Students.
- Premium: Create Student in Moodle.

[Staff and Parents Import](https://www.rosariosis.org/modules/staff-parents-import/) module:
- Send email notification to Users.

[TinyMCE Record Audio Video](https://www.rosariosis.org/plugins/tinymce-record-audio-video/) plugin


New in 6.0
----------

[Chart.js](https://www.chartjs.org/) charts

[Paypal Registration](https://www.rosariosis.org/plugins/paypal-registration/) plugin


New in 5.9
----------

**Breaking Changes**:
- Move Email & Phone Staff Fields to custom fields
- SQL Rename PHONE column to CUSTOM_200000001 in staff table

Please upgrade the following add-ons:
- [Staff and Parents Import](https://www.rosariosis.org/modules/staff-parents-import/) module to version 1.3
- [SMS](https://www.rosariosis.org/modules/sms/) + Premium modules to version 1.5
- [Public Pages Premium](https://www.rosariosis.org/plugins/public-pages/) plugin to version 1.5

Moodle plugin
- Import Moodle Users
- Improved stability and resilience

School
- Configuration: Automatic Student Account Activation, sponsored by LearnersPlatform

Send Account Activation email notification to Student & User, sponsored by LearnersPlatform

[iCalendar](https://www.rosariosis.org/plugins/icalendar/) plugin
- Add assignments to student / teacher calendar, sponsored by Santa Cecilia school


New in 5.8
----------

Grades
- (Gradebook) Configuration: for administrators to override teacher settings, sponsored by Tintamail.

Export list to Excel using MicrosoftXML (more reliable).

[Grades Import](https://www.rosariosis.org/modules/grades-import/) module, sponsored by Instituto Japon.

[Iomad](https://www.rosariosis.org/plugins/iomad/) plugin (multi-tenancy Moodle), sponsored by LearnersPlatform

[Billing Elements](https://www.rosariosis.org/modules/billing-elements/):
- Store: sell Elements (including Courses) to Students and their Parents, sponsored by LearnersPlatform

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/)
- Payments Import, sponsored by LearnersPlatform

[Public Pages Premium](https://www.rosariosis.org/plugins/public-pages/)
- Custom page & default page, sponsored by LearnersPlatform


New in 5.7
----------

Create Student / User Account:
- Email notification

[iCalendar](https://www.rosariosis.org/plugins/icalendar/) plugin.

[Semester Rollover](https://gitlab.com/francoisjacquet/Semester_Rollover/) module, sponsored by Instituto Japon.

[Billing Elements](https://www.rosariosis.org/modules/billing-elements/) module, sponsored by English National Program.


New in 5.6
----------

[SMS](https://www.rosariosis.org/modules/sms/) module.

[Library Premium](https://www.rosariosis.org/modules/library/) module.

[TinyMCE Formula](https://www.rosariosis.org/plugins/tinymce-formula/) plugin.

[Attendance Excel Sheet](https://gitlab.com/francoisjacquet/Attendance_Excel_Sheet) module, sponsored by Tintamail.


New in 5.5
----------

- Substitutions: Custom Fields available, sponsored by École Étoile du Matin

[Grading Scale Generation](https://gitlab.com/francoisjacquet/Grading_Scale_Generation/) plugin, sponsored by Signo Digital.


New in 5.4
----------

Student Billing:
- Portal: New fee alert to Parents.

Grades:
- Progress Reports: Add program for admin (Teacher Programs), student and parent.

Bulgarian translation (100% complete), thanks to Martin Krastev

[LDAP](https://www.rosariosis.org/plugins/ldap/) plugin

[Force Password Change](https://gitlab.com/francoisjacquet/Force_Password_Change/) plugin, sponsored by Santa Cecilia school.

[Convert Names To Titlecase](https://gitlab.com/francoisjacquet/Convert_Names_To_Titlecase/) plugin


New in 5.3
----------

School:
- School Configuration: Force password change on first login, sponsored by Santa Cecilia school.


New in 5.2
----------

Database integrity improvements: added NOT NULL constraint to TITLE columns.


New in 5.1
----------

Student Fields:
- Search Medical Immunization or Physical, sponsored by Asian Hope

Grades:
- Rename "Edit Student Grades" program to "Historical Grades".

Student Billing:
- Payments: Fees dropdown to reconcile Payment.

Accounting:
- Staff Payments: Salaries dropdown to reconcile Payment.

[Relatives](https://www.rosariosis.org/plugins/relatives/) plugin, sponsored by Asian Hope

[REST API](https://gitlab.com/francoisjacquet/REST_API/) plugin


New in 5.0
----------

Grades:
- Report Cards: Min. and Max. Grades option, Last row option (GPA or Total), add Free Text.
- Transcripts: Last row option (GPA or Total).
- Final Grades: Min. and Max. Grades option.

SQL speed: ID columns now use INTEGER type.

Database integrity improvements: added foreign keys.

Bulgarian translation (54% complete), thanks to Vanyo Georgiev

New theme: FlatSIS

[Setup Assistant](https://www.rosariosis.org/plugins/setup-assistant/) plugin


New in 4.9
----------

Users
- Profiles: Teachers & Parents can edit User Info tabs.

[Timetable Import](https://www.rosariosis.org/modules/timetable-import/) module

[Discipline Score](https://www.rosariosis.org/plugins/discipline-score/) plugin


New in 4.8
----------

Default School Year: 2019

Students
- Remove Access: new Custom program, sponsored by Santa Cecilia school.

Grades
- Transcripts: use rich text (TinyMCE) input for Studies Certificate text, hardcoded "Studies Certificate" title and Signtures placeholders removed.

Accessibility improved

[Calendar Schedule View](https://www.rosariosis.org/plugins/calendar-schedule-view/) plugin, sponsored by Revamp Consulting.


New in 4.7
----------

School, Students & Users
- Fields: "Edit Pull-Down" & "Coded Pull-Down" are converted to "Auto Pull-Down" & "Export Pull-Down" Data Types.

Grades
- GPA / MP List: program removed
- Calculate GPA: program removed, automatic GPA & Class Rank calculation


New in 4.6
----------

School, Students & Users
- Fields: new "Files" Data Type, sponsored by Santa Cecilia school.

Students
- Student Fields: Merge Address Fields & Contact Fields programs with Student Fields program

Installation Poll

[Public Pages](https://www.rosariosis.org/plugins/public-pages/) plugin

[Email Alerts](https://www.rosariosis.org/modules/email-alerts/) module, sponsored by Santa Cecilia school.


New in 4.5
----------

Grades

- Gradebook Configuration: "Hide previous quarters assignment types" option, sponsored by Santa Cecilia school.
Note: Will only work for newly created Assignment Types.
- Remove Teacher Programs (still available from Users menu)

Attendance
- Remove Teacher Programs (still available from Users menu)

[Library](https://www.rosariosis.org/modules/library/) module


New in 4.4
----------

Grades
- Assignments: file upload & rich text description, sponsored by Santa Cecilia school

[Email Students](https://www.rosariosis.org/modules/email-students/) module


New in 4.3
----------

Scheduling
- Courses: Description (content or summary)

[Quiz](https://www.rosariosis.org/modules/quiz/) module

Security enhanced


New in 4.0
----------

Dashboard

New Module icons: moved from modules to theme

`config.inc.php`

- `$RosarioErrorsAddress` optional variable to receive errors by email (PHP fatal, database, hacking attempts)

Set admin password on first login


New in 3.9
----------

Translate Help texts with Poedit

Translate database fields to Spanish or French


New in 3.8
----------

Students & Users
- Expanded View: Photo Tip Message


New in 3.7
----------

School Setup
- School Configuration: Display Name, sponsored by @abogadeer


New in 3.6
----------

[Email SMTP](https://www.rosariosis.org/plugins/email-smtp/) plugin

Scheduling
- Courses: teachers, parents & students can access program


New in 3.5
----------

[Student Billing Premium](https://www.rosariosis.org/modules/student-billing-premium/) module

[Staff and Parents Import](https://www.rosariosis.org/modules/staff-parents-import/) module, sponsored by @abogadeer

School Setup
- School Configuration: Failed Login Attempts Limit, sponsored by @abogadeer

Create Student / User Account
- Captcha, sponsored by @abogadeer

Students
- Student Info: Delete Student button (only if no Schedule, Grades or Attendance records found), sponsored by @abogadeer


New in 3.4
----------

New [PDF Header Footer](https://www.rosariosis.org/plugins/pdf-header-footer) plugin

[New translations](locale/REFERENCE.md) (37% completed)
- Afrikaans (South Africa)
- Arabic (Saudi Arabia)
- Belarusian (Belarus)
- Czech (Czech Republic)
- Greek (Greece)
- Estonian (Estonia)
- Finnish (Finland)
- Irish (Ireland)
- Galician
- Croatian (Croatia)
- Hungarian (Hungary)
- Indonesian (Indonesia)
- Icelandic (Iceland)
- Lithuanian (Lithuania)
- Latvian (Latvia)
- Macedonian (Macedonia)
- Dutch (Netherlands)
- Norwegian Nynorsk (Norway)
- Polish (Poland)
- Romanian (Romania)
- Slovak (Slovakia)
- Slovenian (Slovenia)
- Albanian (Albania)
- Serbian (Cyrillic, Bosnia)
- Swedish (Sweden)
- Thai (Thailand)
- Ukrainian (Ukraine)
- Vietnamese (Vietnam)
- Walloon (Belgium)
- Chinese (Traditional, Taiwan)


New in 3.3
----------

New File Uploads folder in `assets/FileUploads/`.

.jpg, .png, .gif formats are now accepted for Student / User photos.
The 2MB size limit was removed.

Automatically resize & compress uploaded images.
[PNGQuant](INSTALL.md#optional-variables) can be used for PNG compression.

Up to 4x speed & memory gain (DBGet core function)

Usability improvements


New in 3.2
----------

Scheduling
- Requests: Students & Parents can Edit


New in 3.1
----------

Grades
- Mass Create Assignments


New in 3.0
----------

School Setup
- Access Log
- Marking Periods
	- Access granted to parents & students

Users
- My Preferences
	- Student Fields: search Username

Performance
- 90% gain when updating Side menu
- Cache system ([ETag](https://en.wikipedia.org/wiki/HTTP_ETag))

Upload images in text editor (TinyMCE)


New in 2.9
----------
Students

- (Custom) Registration (for parents or students to register their contacts)

School Setup
- Configuration
	- Limit Existing Contacts & Addresses to current school
	- Force Default Theme

Users
- My Preferences
	- Export Listings in XML format
	- User Fields: search Email Address & Phone

Scheduling
- Group Schedule
	- Schedule multiple courses

Grades
- Student Assignments
	- Submit Assignment

- Grading Scales
	- Minimum Passing Grade

Student Billing & Accounting
- Daily Totals

Password Reset feature

[MarkDown](https://gitlab.com/francoisjacquet/rosariosis/wikis/Markdown-Cheatsheet) support for Large text fields

Automatic database upgrade

French help

Khmer / Cambodian translation


New in 2.8
-----------
School Setup
- School Configuration
	- Create User Account
	- Create Student Account

Students
- Group Assign Student Info
	- Grade Level
	- Attendance Start Date this School Year

Attendance
- Course Period Absences Widget

Portal
- Upcoming Assignments

Menu enhancement

Security improved

New in 2.7
-----------
School Setup
- School Configuration
	- manage Modules & Plugins
	- set Default Theme
	- set Currency

`config.inc.php`
- No more Rosario Admins list

Security improved


New in 2.6
-----------
Accounting
- New module including Staff Payroll

School Setup
- Food Service configuration
- Rebrand RosarioSIS

Navigate the browser history

Open RosarioSIS links in new tab

Page source viewable

Arabic translation

German translation


New in 2.5
-----------
Grades
- Assignment Default Points

Better internationalization (i18n) support


New in 2.4
-----------
Resources
- RosarioSIS Wiki
- Print handbook
- Add your links

Security improved


New in 2.3
-----------
School Setup
- Upload school logo

Users
- Print Options preferences


New in 2.2
-----------
School Setup
- Added School Fields


New in 2.1
-----------
Students
- Added time and user to comments "comment thread" like


New in 2.0
-----------
Responsive design
- Compatible with smartphones and tablets
- AJAX design
- Retractable menu
- Responsive tables


New in 1.4
-----------
Discipline
- Added Discipline Categories to Discipline Widget
- Added new referrals Portal alert

Custom
- Added Attendance Summary


New in 1.3
-----------
Security improved

School Setup
- Moved all the configuration values inside the `config.inc.php` files to School Configuration
- Added limit visibility to the students of a determined teacher in Portal Polls

Custom
- Added Notify Parents


New in 1.2
-----------
Security improved

Users
- Added Failed Login to the expanded view of staff listing


New in 1.1
-----------
Scheduling
- Added ability to modifiy and delete periods to an existing Course Period


New in 1.0
-----------
Themes
- New logo
- New theme: WPadmin inspired by Wordpress' admin theme
- New icon set

Translation
- Every string in RosarioSIS now translatable
- Full french and spanish translation
- Dates translation
- Custom Currency
- Handbooks/Help in spanish

Students
- Added ability to add/change Photo directly from the Student/User screen
- Added student breakdown adapted from Focus SIS v.2.3
- Added TinyMCE to letters

Grades
- Added gradebook breakdown adapted from Focus SIS v.2.3

School Setup
- Added PostgreSQL database backup
- Added School uses a Rotation of Numbered Days option
- Added possibility to attach a file to Portal Notes
- Added ability to repeat a calendar event
- Added School Configuration

Scheduling
- Added master schedule report
- Added possibility to add more than one period to a Course Period

Discipline
- Added module adapted from Focus SIS v.2.3

Student Billing
- Added module adapted from Focus SIS v.2.3

Password Encryption

Moodle
- Added Moodle integration

Breakdowns
- jqPlot Javascript charts replace PHP/SWF Charts

PDF
- Replaced htmldoc by wkhtmltopdf
