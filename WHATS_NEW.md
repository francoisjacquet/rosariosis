# WHAT'S NEW

## RosarioSIS Student Information System

New in 4.9
----------

Users
- Profiles: Teachers & Parents can edit User Info tabs.

[Timetable Import](https://www.rosariosis.org/timetable-import-module/) module

[Discipline Score](https://www.rosariosis.org/discipline-score-plugin/) plugin


New in 4.8
----------

Default School Year: 2019

Students
- Remove Access: new Custom program, sponsored by Santa Cecilia school.

Grades
- Transcripts: use rich text (TinyMCE) input for Studies Certificate text, hardcoded "Studies Certificate" title and Signtures placeholders removed.

Accessibility improved

[Calendar Schedule View](https://www.rosariosis.org/calendar-schedule-view-plugin/) plugin, sponsored by Revamp Consulting.


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

[Public Pages](https://www.rosariosis.org/public-pages-plugin/) plugin

[Email Alerts](https://www.rosariosis.org/email-alerts-module/) module, sponsored by Santa Cecilia school.


New in 4.5
----------

Grades

- Gradebook Configuration: "Hide previous quarters assignment types" option, sponsored by Santa Cecilia school.
Note: Will only work for newly created Assignment Types.
- Remove Teacher Programs (still available from Users menu)

Attendance
- Remove Teacher Programs (still available from Users menu)

[Library](https://www.rosariosis.org/library-module/) module


New in 4.4
----------

Grades

- Assignments: file upload & rich text description, sponsored by Santa Cecilia school

[Email Students](https://www.rosariosis.org/email-students-module/) module


New in 4.3
----------

Scheduling

- Courses: Description (content or summary)

[Quiz](https://www.rosariosis.org/quiz-module/) module

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

[Email SMTP](https://www.rosariosis.org/email-smtp-plugin/) plugin

Scheduling

- Courses: teachers, parents & students can access program


New in 3.5
----------

[Student Billing Premium](https://www.rosariosis.org/student-billing-premium-module/) module

[Staff and Parents Import](https://www.rosariosis.org/staff-parents-import-module/) module, sponsored by @abogadeer

School Setup

- School Configuration: Failed Login Attempts Limit, sponsored by @abogadeer

Create Student / User Account

- Captcha, sponsored by @abogadeer

Students

- Student Info: Delete Student button (only if no Schedule, Grades or Attendance records found), sponsored by @abogadeer


New in 3.4
----------

New [PDF Header Footer](https://www.rosariosis.org/pdf-header-footer-plugin) plugin

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
