# Moodle Integrator Plugin

This plugin is part of [RosarioSIS](https://www.rosariosis.org)

Author François Jacquet

## Description

This plugin integrates RosarioSIS with [Moodle LMS](https://moodle.org/). It lets you import Moodle users.

### WARNING & LIMITATION

The plugin is designed and intended for ONE school only in RosarioSIS.

Users and courses can only be rolled ONCE in RosarioSIS.

## Content

Plugin Configuration

- Configure Moodle API and Test
- Import Users

Create Student Account

- Create student in Moodle if "Automatic Student Account Activation" configuration option set (Moodle creates a password and sends an email to user).

Students

- Create, update & delete students in Moodle

Users

- Create, update & delete teachers, parents, admins in Moodle

Tip: leave the password field empty when creating a User / Student in Moodle so Moodle creates a password and sends an email to user.

Schedule

- Subjects, courses & course periods are automatically created, updated & deleted in Moodle
- Teacher users are automatically assigned the "Teacher" role for their courses in Moodle.
- Automatically (mass) schedule or drop students from a course period in Moodle

School

- Calendar events are automatically added to & removed from the Moodle calendar
- Portal notes are automatically created, updated & deleted in Moodle
- Rollover: Moodle users (and students) and courses are associated to the next school year entities

Note: it is not possible to create a User or a Course, or enroll a Student in Moodle from previous school years (after Rollover).

Grades

- Assignments are automatically added to & removed from the Moodle calendar (provided a Due Date is set)

## Install

Please follow [this tutorial](https://gitlab.com/francoisjacquet/rosariosis/wikis/Moodle-integrator-setup)

Requires Moodle **3.1** or higher & PHP `curl` & `xmlrpc` extensions.
