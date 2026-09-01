Content Security Policy plugin
==============================

![screenshot](https://gitlab.com/francoisjacquet/rosariosis/-/raw/mobile/plugins/Content_Security_Policy/screenshot.webp?inline=false)

This plugin is part of [RosarioSIS](https://www.rosariosis.org)

Author François Jacquet

## Description

Core plugin to help with the Content Security Policy in RosarioSIS. The CSP main purpose is to prevent XSS (Cross Site Scripting) attacks.
This plugin reports violations to the CSP. You can add domains to the CSP (ie. allow requests to external domains).

Note: if you are experiencing issues with an add-on, please contact its developer.

Note 2: to receive new CSP violation reports, please set your email address in the [`config.inc.php`](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/config.inc.sample.php?ref_type=heads#L97) file's `$RosarioErrorsAddress` variable.

Note 3: some CSP violations/reports are triggered by browser extensions or external websites such as Google, Facebook. They can be safely ignored.

Warning: allow external domains as a last resort because this lowers system security.

Warning 2: inline Javascript cannot be (re)allowed through means of this plugin. Please check the "Developers" section below for instructions.

The Content Security Policy is rolled out in RosarioSIS in 3 phases:
1. Prepare: make RosarioSIS and the add-ons' code compatible (version 12.5)
2. Report only: report violations to the CSP through this same plugin (version 12.6)
3. Enforce: report and block resources in violation to the CSP (version 13.0)

CSP stands for Content Security Policy, read more at https://en.wikipedia.org/wiki/Content_Security_Policy

### Compatible add-ons

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
- NFC/QR Actions 1.7+ (Dec. 2024)
- PDF Archive 1.4+ (Jan. 2025)
- Quiz 10.8+ (Jan. 2025)
- Quiz Premium 10.4+ (Jan. 2025)
- Reports 11.2+ (Feb. 2025)
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
- Parent Agreement 10.1+ (Mar. 2025)
- Paypal Registration 12.0+ (Jul. 2026)
- Public Pages 10.5+ (Mar. 2025)
- Public Pages Premium 10.7+ (Mar. 2025)
- Setup Assistant 10.5+ (Apr. 2025)
- Stripe Registration 1.8+ (Mar. 2025)
- Templates 1.7+ (Apr. 2025)
- TinyMCE Formula 10.1+ (Apr. 2025)
- TinyMCE Record Audio Video 10.4+ (Apr. 2025)

Third-party add-ons are not listed here, please contact the developer or your system administrator for help.

### Default CSP header

```
Content-Security-Policy: script-src 'self' 'unsafe-eval' 'report-sample'; style-src 'self' 'unsafe-inline'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors: 'none'; object-src none'; report-uri plugins/Content_Security_Policy/SaveReport.php;
```

Warning: [report-uri](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/report-uri) is deprecated but `report-to` is not widely supported yet.

### Developers

CSP is implemented through a simple HTTP header in the response.
The browser is in charge of only allowing resources that comply with the CSP.
Resources blocked by the browser can be seen in the developer console.

Please consult the Example module's [Wiki](https://gitlab.com/francoisjacquet/Example/-/wikis/Security#content-security-policy) for an explanation of the CSP and recommendations.

## Content

Plugin Configuration

- Reports tab
  - Warning: inside the full report, `document-uri` may be inaccurate

- Domains tab:
  - Javascript (script-src)
  - CSS (style-src)
  - AJAX (connect-src)
  - Form (form-action)
