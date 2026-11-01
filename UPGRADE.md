# UPGRADE
## RosarioSIS Student Information System

Upgrade to 13.0
---------------

### Breaking change

If you are running RosarioSIS version < 12.5, please first upgrade to version 12.9.4 and solve any CSP violation by upgrading incompatible add-ons.

[Content Security Policy](plugins/Content_Security_Policy/README.md) (phase 3): report and enforce

[config.inc.php](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/INSTALL.md#installing-the-package)
- Add `$RosarioURL` config variable. Automatically set during the upgrade. Please set it manually in case the `config.inc.php` file cannot be written.

### Add-ons

Please upgrade the following [modules](https://www.rosariosis.org/modules/):
- Audit 11.1+ (Dec. 2024)
- Billing Elements 12.3+ (Jan. 2025)
- Certificate 14.1+ (Dec. 2024)
- Class Diary Premium 10.6+ (Dec. 2024)
- Dashboards 1.3+ (Dec. 2024)
- Email Alerts 10.5+ (Dec. 2024)
- Embedded Resources 1.2+ (Dec. 2024)
- Entry and Exit 4.4+ (Dec. 2024)
- Entry and Exit Premium 1.1+ (Dec. 2024)
- Food Service Premium 2.5+ (Jan. 2026)
- Grades Import 12.5+ (Dec. 2024)
- Hostel 1.8+ (Jan. 2025)
- Hostel Premium 2.1+ (Dec. 2024)
- Human Resources 10.4+ (Dec. 2024)
- Jitsi Meet 11.4+ (Aug. 2024)
- Lesson Plan 2.0+ (Jan. 2026)
- Lesson Plan Premium 1.4+ (Dec. 2024)
- Library 11.3+ (Dec. 2024)
- Library Premium 12.3+ (Dec. 2024)
- Meeting 1.8+ (Dec. 2024)
- Meeting Premium 1.4+ (Dec. 2024)
- Messaging Premium 11.4+ (Dec. 2024)
- PDF Archive 1.4+ (Jan. 2025)
- Quiz 10.8+ (Jan. 2025)
- Quiz Premium 10.4+ (Jan. 2025)
- Slovenian Attendance Excel Sheet OK
- SMS 11.2+ (Jan. 2025)
- SMS Premium 11.4+ (Aug. 2025)
- Staff Absences 11.0+ (Jan. 2025)
- Staff Parents Import 11.5+ (Jan. 2025)
- Student Billing Premium 16.0+ (Jul. 2026)
- Student ID Card 2.6+ (Feb. 2025)
- Student Pickup 1.4+ (Aug. 2025)
- Students Import 10.8+ (Feb. 2025)
- Students Import Premium 12.7+ (Feb. 2025)
- Timetable Import 11.6+ (Feb. 2025)
- TTHotel Smart Locks 1.5+ (Feb. 2025)

Please upgrade the following [plugins](https://www.rosariosis.org/plugins/):
- Automatic Attendance 11.3+ (Mar. 2025)
- Custom Menu 1.1+ (Mar. 2025)
- Discipline Score 11.1+ (Mar. 2025)
- Google Social Login 11.3+ (Sep. 2025)
- Instant List Search Sorting 1.5+ (Mar. 2025)
- LDAP 10.4+ (Mar. 2025)
- Microsoft Social Login 1.3+ (Sep. 2025)
- Paypal Registration 12.0+ (Jul. 2026)
- Public Pages 10.5+ (Mar. 2025)
- Public Pages Premium 10.7+ (Mar. 2025)
- Setup Assistant 10.5+ (Apr. 2025)
- Stripe Registration 1.8+ (Mar. 2025)
- Templates 1.7+ (Apr. 2025)
- TinyMCE Formula 10.1+ (Apr. 2025)
- TinyMCE Record Audio Video 10.4+ (Apr. 2025)

Third-party add-ons are not listed here, please contact the developer or your system administrator for help.

### Deprecated

Deprecated functions will be removed in version 14.0.

- Deprecate `Dashboard()` function in Dashboard.fnc.php

- Deprecate `DashboardAdd()` function in Dashboard.fnc.php

- Deprecate `DashboardOutput()` function in Dashboard.fnc.php
 - Use `(new RosarioSIS\Functions\Dashboard)->output()` instead

- Deprecate `DashboardModule()` function in DashboardModule.fnc.php
  - Use `(new RosarioSIS\Functions\DashboardModule)->add( $module, $data )` instead

- Deprecate `DashboardModuleTitle()` function in DashboardModule.fnc.php
  - Use `(new RosarioSIS\Functions\DashboardModule)->module( $module )` instead

- Deprecate `DashboardModuleData()` function in DashboardModule.fnc.php
  - Use `(new RosarioSIS\Functions\DashboardModule)->data( $data, $columns )` instead

- Deprecate `HackingLog()` function in HackingLog.fnc.php
  - Use `(new RosarioSIS\Functions\Hacking)->log()` instead

- Deprecate `Linkify()` function in Linkify.fnc.php
  - Use your own function instead

- Deprecate `SendEmail()` function in SendEmail.fnc.php
  - Use `(new RosarioSIS\Functions\Email)->send()` instead

- Deprecate 'ProgramFunctions/SendEmail.fnc.php|before_send' action hook in Email.php & Actions.php
  - Use 'RosarioSIS/Functions/Email.php|before_send' instead

- Deprecate 'ProgramFunctions/SendEmail.fnc.php|send_error' action hook in Email.php & Actions.php
  - Use 'RosarioSIS/Functions/Email.php|send_error' instead

- JS deprecate `LO_search()` function in warehouse.js, csp/functions.js & ListOutput.fnc.php
  - Use `instantList.search()` instead

### Removed

Calling removed functions or variables will result in a fatal error.

- Remove deprecated `_updateSchedulesCPMP()` function in Courses.php

- Remove deprecated `jqPlotChart()` function in Charts.fnc.php

- Remove deprecated `_printPageHead()` function in PasswordReset.php

- Remove deprecated `_rosarioLoginURL()` function in SendNotification.fnc.php

- Remove deprecated `_myURLEncode()` function in PreparePHP_SELF.fnc.php

- Remove deprecated `ColorInputMiniColors()` function in Inputs.php
  - Remove related JS files in assets/js/jquery-minicolors/

- Remove deprecated `$_ROSARIO['ReferralInput']` global var in Referral.fnc.php
