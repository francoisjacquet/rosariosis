<?php
/**
 * English Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * Please use this file as a reference to generate the Gettext help.pot / help.po files
 * and translate Help texts to your language.
 * The Catalog should only reference the Help_en.php file
 * and detect the `_help` function / source keyword.
 *
 * @package RosarioSIS
 * @subpackage Help
 *
 * @author François Jacquet
 */

// DEFAULT.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['default'] = '<p>' . _help( 'As an administrator, you can setup the schools in this system, modify students and users, and access essential student reports.' ) . '</p>

	<p>' . _help( 'You have access to any school in the system. To choose a school to work on, select the school from the pull-down menu on the left frame. The program will automatically refresh with the new school in the workspace. You can also change the school year and current marking period in a similar fashion.' ) . '</p>

	<p>' . _help( 'As you use RosarioSIS, you will notice other items appear in your side menu. When you select a student to work on, the student\'s name will appear under the marking period pull-down menu preceded by a cross. As you move between programs, you will continue to work on this student. If you want to change the working student, click on the cross by the student\'s name. You can also quickly access the student\'s General Information screen by clicking on the student\'s name.' ) . '</p>

	<p>' . _help( 'If you select a user to work on, the user\'s name will also appear in the side menu. This will behave identically to the student\'s name.' ) . '</p>

	<p>' . _help( 'Also, when you click on any of the module icons in the side menu, you will see a list of programs available to you in that module. Clicking on any program title will launch the program in the main frame, and it will update the help frame to display help for that program.' ) . '</p>

	<p>' . _help( 'In many places in RosarioSIS, you will see lists of data that are modifiable. Oftentimes, you will have to first click the value you want to change to have access to an input field. Then, when you change the value and save, the value will return to its previous state.' ) . '</p>

	<p>' . _help( 'You can logout of RosarioSIS at any time by clicking the "Logout" button in the bottom menu.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'teacher' ):

	$help['default'] = '<p>' . _help( 'As a teacher, you can view student information and schedules for students who you teach and input attendance, grades, and eligibility for these students. You also have a gradebook program to keep track of students\' grades. The Gradebook is integrated into the Input Grades program as well as the Eligibility program. From the Gradebook, not only can you keep track of grades, but you can print progress reports for any of your students.' ) . '</p>

	<p>' . _help( 'To choose a period to work on, select the period from the pull-down menu on the left frame. The program will automatically refresh with the new period in the workspace. You can also change the school year and current marking period in a similar fashion.' ) . '</p>

	<p>' . _help( 'As you use RosarioSIS, you will notice other items appear in your side menu. When you select a student to work on, the student\'s name will appear under the marking period pull-down menu preceded by a cross. As you move between programs, you will continue to work on this student. If you want to change the working student, click on the cross by the student\'s name. You can also quickly access the student\'s General Information screen by clicking on the student\'s name.' ) . '</p>

	<p>' . _help( 'Also, when you click on any of the module icons in the side menu, you will see a list of programs available to you in that module. Clicking on any program title will launch the program in the main frame, and it will update the help frame to display help for that program.' ) . '</p>

	<p>' . _help( 'In the gradebook, you will see lists of modifiable data. Oftentimes, you will have to first click the value you want to change to have access to an input field. Then, when you change the value and save, the value will return to its previous state.' ) . '</p>

	<p>' . _help( 'You can logout of RosarioSIS at any time by clicking the "Logout" button in the bottom menu.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'parent' ):

	$help['default'] = '<p>' . _help( 'As a parent, you can view your children\'s information, schedules, assignments, grades, eligibility, and attendance.' ) . '</p>

	<p>' . _help( 'To choose a child to work on, select the child\'s name from the pull-down menu on the left frame. The program will automatically refresh with the new child in the workspace. You can also change the school year and current marking period in a similar fashion.' ) . '</p>

	<p>' . _help( 'As you use RosarioSIS, you will notice other items appear in your side menu. When you click on any of the module icons in the side menu, you will see a list of programs available in that module. Clicking on any program title will launch the program in the main frame, and it will update the help frame to display help for that program.' ) . '</p>

	<p>' . _help( 'You can logout of RosarioSIS at any time by clicking the "Logout" button in the bottom menu.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'student' ):

	$help['default'] = '<p>' . _help( 'As a student, you can view your demographic information, schedule, assignments, grades, eligibility, and attendance.' ) . '</p>

	<p>' . _help( 'You can change the school year and current marking period with the pull-down menus in the left frame.' ) . '</p>

	<p>' . _help( 'As you use RosarioSIS, you will notice other items appear in your side menu. When you click on any of the module icons in the side menu, you will see a list of programs available in that module. Clicking on any program title will launch the program in the main frame, and it will update the help frame to display help for that program.' ) . '</p>

	<p>' . _help( 'You can logout of RosarioSIS at any time by clicking the "Logout" button in the bottom menu.' ) . '</p>';

endif;

// SCHOOL SETUP ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['School_Setup/Schools.php'] = '<p>' . _help( '<i>School Information</i> allows you to change the name, address, and principal of the current school. Click on any of the school\'s information to change it. After you have made the necessary modifications to your school, click "Save" to save your changes.' ) . '</p>

	<p>' . _help( 'Note: a school cannot be deleted once it has students enrolled.' ) . '</p>';

	$help['School_Setup/CopySchool.php'] = '<p>' . _help( '<i>Copy School</i> is a good way to add another school to RosarioSIS, where the Periods, Marking Periods, Grade levels, Grading Scales and Attendance Codes are similar to the school you copy. You will be able, of course, to make changes in the configuration after you have "copied" the school.' ) . '</p>

	<p>' . _help( 'If you don\'t want to copy one or more of these items, click on the checkbox corresponding to the item.' ) . '</p>

	<p>' . _help( 'Then enter the name of the new school in the "New School\'s Title" text box.' ) . '</p>

	<p>' . _help( 'Finally, click "OK" to create the new school with the values of the existing school.' ) . '</p>';

	$help['School_Setup/MarkingPeriods.php'] = '<p>' . _help( '<i>Marking Periods</i> allows you to setup your school\'s marking periods. There are three tiers of marking periods: Something like Semesters, Quarters, and Progress Periods is suggested. Despite their names, there can be more or fewer than 2 semesters and more or fewer than 4 quarters. Similarly, there can be any number of progress periods in a given quarter.' ) . '</p>

	<p>' . _help( 'To add a marking period, click on the Add icon (+) in the column corresponding to the type of marking period you want to add. Then, complete the marking period details in the fields above the list of marking periods and click the "Save" button.' ) . '</p>

	<p>' . _help( 'The "Grade Posting Begins" and "Grade Posting Ends" dates define the first and last day of the period during which teachers can enter final grades.' ) . '</p>

	<p>' . _help( 'To change a marking period, click on the marking period you want to change, and click on whatever value you want to change in the grey area above the marking period list. Then, change the value and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete a marking period, select it by clicking on its title on the list and click the "Delete" button at the top of the screen. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'Notice that neither two marking periods nor two posting periods in the same tier can overlap. Also, No two marking periods in any tier should have the same sort order.' ) . '</p>

	<p>' . _help( 'Note: a marking period cannot be deleted once it has course periods.' ) . '</p>

	<p>' . _help( 'Note: you cannot add a new year using this program. Use the <i>School > Rollover</i> program instead.' ) . '</p>';

	$help['School_Setup/Calendar.php'] = '<p>' . _help( '<i>Calendars</i> allows you to setup your school\'s calendar for the year. The calendar displays the current month by default. The month and year displayed can be changed by changing the month and year pull-down menus at the top of the screen.' ) . '</p>

	<p>' . _help( 'On full school days, the checkbox in the upper right-hand corner of the day\'s square should be checked. For partial days, the checkbox should be unchecked and the number of minutes school will be in attendance should be entered into the text box next to the checkbox. For days on which there will be no school, the checkbox should be unchecked and the text field should be blank. To uncheck the checkbox or change the number of minutes in the school day, you must first click on the value you want to change. After making any changes to the calendar, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'To setup your calendar at the beginning of the year, you should use the "Create" or "Edit" feature. By clicking on this link in the upper right-hand corner of the screen, you can setup all days in a specified timeframe as meeting all day. You can also select which days of the week that your school is in session. After selecting the beginning and ending dates of your school\'s school year and the days of the week that your school meets, click the "OK" button. You can now go through the calendar and mark holidays and partial days.' ) . '</p>

	<p>' . _help( 'The calendar is also a display of school events. This can include everything from teacher in-service days to sporting events. These events are visible by other administrators as well as parents and teachers at your school.' ) . '</p>

	<p>' . _help( 'To add a school event, click on the add icon (+) in the lower left-hand corner of the event\'s date. In the popup window that appears, enter the event\'s information and click the "Save" button. The popup window will close, and the calendar will be automatically refreshed to display the added event.' ) . '</p>

	<p>' . _help( 'To modify an event, click on the event you want to modify, and change the event\'s information in the popup window that appears after clicking on the values you want to change. Click the "Save" button. The window will close and the calendar will automatically refresh to display the change.' ) . '</p>

	<p>' . _help( 'If the school uses a Rotation of Numbered Days, the day\'s number is displayed in the day\'s box.' ) . '</p>';

	$help['School_Setup/Periods.php'] = '<p>' . _help( '<i>Periods</i> allows you to setup your school\'s periods. Middle and high schools will likely have many periods, whereas elementary schools will probably have only one period (called All Day) or perhaps 3 (All Day, Morning, and Afternoon).' ) . '</p>

	<p>' . _help( 'To add a period, fill in the period\'s title, short name, sort order, and length in minutes in the empty fields at the bottom of the periods list and click the "Save" button.' ) . '</p>

	<p>' . _help( 'Blocks can be used to define irregular periods which happens on specific days. See the <i>Calendars</i> program for setup.' ) . '</p>

	<p>' . _help( 'To modify a period, click on any of the period\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete a period, click the delete icon (-) next to the period you want to delete. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'Note: a period cannot be deleted once it is associated to a course period.' ) . '</p>';

	$help['School_Setup/GradeLevels.php'] = '<p>' . _help( '<i>Grade Levels</i> allows you to setup your school\'s grade levels.' ) . '</p>

	<p>' . _help( 'To add a grade level, fill in the grade level\'s title, short name, sort order, and next grade in the empty fields at the bottom of the grade levels list and click the "Save" button. The "Next Grade" field indicates the grade students in the current grade will proceed to in the next school year.' ) . '</p>

	<p>' . _help( 'To modify a grade level, click on any of the grade level\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete a grade level, click the delete icon (-) next to the grade level you want to delete. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'Note: a grade level cannot be deleted once it has students enrolled.' ) . '</p>';

	$help['School_Setup/Rollover.php'] = '<p>' . _help( '<i>Rollover</i> copies the current year\'s data to the next school year. Students are enrolled in the next grade, and each school\'s information is duplicated for the next school year.' ) . '</p>

	<p>' . _help( 'The data copied include periods, marking periods, users, courses, student enrollment, report card grade codes, enrollment codes, attendance codes, and eligibility activities.' ) . '</p>

	<p>' . _help( 'Note: Students are enrolled or retained depending on their "Rolling / Retention options" set in the <i>Students > Student Info</i> program. In case the option is set to another school, the student will be enrolled either in the first Grade Level (Sort Order 1) or in the same Grade Level (same Title).' ) . '</p>';

	$help['School_Setup/Configuration.php'] = '<p>' . _help( '<i>Configuration</i> offers various groups of options to help you configure:' ) . '</p>
	<ul>
		<li>RosarioSIS:
			<ul>
				<li>' . _help( '<i>Program Title</i> & <i>Program Name</i>: rebrand RosarioSIS' ) . '</li>
				<li>' . _help( 'Set the <i>Default Theme</i>, and eventually <i>Force</i> it to override users\' preferred theme.' ) . '</li>
				<li>' . _help( '<i>Create User Account</i> & <i>Create Student Account</i>: activate online registration. "Create User / Student Account" links will be displayed on the login page.' ) . '</li>
				<li>' . _help( '<i>Automatic Student Account Activation</i>: new students are added as Inactive students. Check this box so students are enrolled on the same day.' ) . '</li>
				<li>' . _help( '<i>Default School</i>: selected school on the "Create Student Account" screen.' ) . '</li>
				<li>' . _help( '<i>Student email field</i>: choose the field which you will use to store your students emails. This can be the Username field or any other text field from the General Info tab. Setting this field will enable new features for or related to students within RosarioSIS such as "Password Reset".' ) . '</li>
				<li>' . _help( '<i>Failed Login Attempts Limit</i>: ban access during 10 minutes if failed login attempts limit is reached. The "Too many failed login attempts. Please try logging in later." error will be shown on the login screen and the corresponding Access Log will have the "Banned" status.' ) . '</li>
				<li>' . _help( '<i>Password Strength</i>: minimum password strength required. Estimations are based on the <i>zxcvbn</i> tool. Set to 0 to disable.' ) . '</li>
				<li>' . _help( '<i>Force Password Change on First Login</i>: activate to prompt users and students for a new password when they login for the first time.' ) . '</li>
				<li>' . _help( '<i>Display Name</i>: select the format which will be used to display User and Student full name.' ) . '</li>
			</ul>
		</li>
		<li>' . _help( 'The School:' ) .
	'<ul>
				<li>' . _help( '<i>School year over two calendar years</i>: whether the school year should be displayed as "2014" or "2014-2015"' ) . '</li>
				<li>' . _help( '<i>School logo (.jpg)</i>: upload the school logo (displayed in Report Cards, Transcripts, School Information & Print student Info)' ) . '</li>
				<li>' . _help( '<i>Currency Symbol</i>: the currency / monetary symbol used in Accounting & Student Billing modules' ) . '</li>
				<li>' . _help( '<i>Thousands separator</i>: use a comma or point or non-breaking space in digit grouping' ) . '</li>
				<li>' . _help( '<i>Decimal separator</i>: use a point or comma to separate the integer part from the fractional part of a number' ) . '</li>
				<li>' . _help( '<i>Course Widget</i>: whether the method for selecting a course is done through a Popup window (default) or using a select drop-down listing all course periods at once.' ) . '</li>
			</ul>
		</li>
		<li>' . _help( 'The Students module:' ) .
	'<ul>
				<li>' . _help( '<i>Display Mailing Address</i>: whether to record and display the student\'s mailing address as a different address.' ) . '</li>
				<li>' . _help( '<i>Check Bus Pickup / Dropoff by default</i>: whether to check Bus Pickup / Dropoff checkboxes by default when entering the student address' ) . '</li>
				<li>' . _help( '<i>Enable Legacy Contact Information</i>: the ability to add information to the student contacts' ) . '</li>
				<li>' . _help( '<i>Use Semester Comments instead of Quarter Comments</i>: have a new student comments field each semester instead of each quarter' ) . '</li>
				<li>' . _help( '<i>Limit Existing Contacts & Addresses to current school</i>: global setting (applies to all schools) that will limit the lists of Persons & Addresses to the ones associated with the user\'s current school when Adding an Existing Contact or Address' ) . '</li>
			</ul>
		</li>
		<li>' . _help( 'The Grades module:' ) .
	'<ul>
				<li>' . _help( '<i>Grades</i>: whether your school uses percent grades, letter grades or both. Will then hide the percent or letter grades accordingly.' ) . '</li>
				<li>' . _help( '<i>Hide grade comment except for attendance period courses</i>: whether to hide grade comment for non attendance period courses' ) . '</li>
				<li>' . _help( '<i>Allow Teachers to edit grades after grade posting period</i>: the grade posting period for each marking period is set in the School > Marking Periods program' ) . '</li>
				<li>' . _help( '<i>Enable Anonymous Grade Statistics for Parents and Students / Administrators and Teachers</i>: the Anonymous Grade Statistics are displayed in the Student Grades program' ) . '</li>
			</ul>
		</li>
		<li>' . _help( 'The Attendance module:' ) .
	'<ul>
				<li>' . _help( '<i>Minutes in a Full School Day</i>: if a student attends school for 300 minutes or more, RosarioSIS will automatically mark him Present for the day. If a student attends school for 150 minutes to 299 minutes, RosarioSIS will marked him Half Day present. If a student attends school for less than 150 minutes, RosarioSIS will mark him Absent. If your School Day is not 300 minutes long, then please adjust the Minutes in a Full School Day' ) . '<br />' .
				_help( 'Set to 0 for dynamic Daily Attendance calculation based on total course period minutes. That is, the sum of the lengths of each course period during the day. Period lengths (in minutes) are defined in the School > Periods program.' ) . '</li>
				<li>' . _help( '<i>Number of days before / after the school date teachers can edit attendance</i>: leave the fields blank to always allow teachers to edit attendance' ) . '</li>
			</ul>
		</li>
		<li>' . _help( 'The Food Service module:' ) .
	'<ul>
				<li>' . _help( '<i>Food Service Balance minimum amount for warning</i>: set the minimum amount under which a warning will be displayed to the student and its parents on the Portal and to generate Reminders' ) . '</li>
				<li>' . _help( '<i>Food Service Balance minimum amount</i>: set the minimum amount allowed' ) . '</li>
				<li>' . _help( '<i>Food Service Balance target amount</i>: set the target amount to calculate the minimum deposit' ) . '</li>
			</ul>
		</li>
	</ul>
	<p>' . _help( '<b>Modules</b> tab: manage RosarioSIS modules. Deactivate any module you will not use or install new ones.' ) . '</p>

	<p>' . _help( '<b>Plugins</b> tab: manage RosarioSIS plugins. Activate, deactivate and configure plugins. Click on the plugin title to get more information.' ) . '</p>';

	$help['School_Setup/SchoolFields.php'] = '<p>' . _help( '<i>School Fields</i> allows you to add new fields to the School Information screen.' ) . '</p>

	<p>' . _help( 'Add a new Field' ) . '</p>

	<p>' . _help( 'Click on the "+" icon below the "No School Fields were found" text. Fill in the Field Name field(s), and then choose what type of field you wish with the "Data Type" pull-down.' ) . '</p>
	<ul>
	<li>' . _help( '"Pull-Down" fields create menus from which you can select one option. To create this type of field, click on "Pull-Down" and then add your options (one per line) in the "Pull-Down/Auto Pull-down/Export Pull-Down/Select Multiple from Options" text box.' ) . '</li>
	<li>' . _help( '"Auto Pull-Down" fields create menus from which you can select one option, and add options. You add options by selecting the "-Edit-" option in the menu choices and click "Save". You can then edit the field by removing the red "-Edit-" from the field, entering the correct information. RosarioSIS gets all the options that have been added to this field to create the pull-down.' ) . '</li>
	<li>' . _help( '"Export Pull-Down" fields are created by adding options to the large text box respecting the following pattern: "option shown"|"option exported". For example: "Two|2", where "Two" is displayed on screen to the user, and "2" is the value in a downloaded spreadsheet.' ) . '</li>
	<li>' . _help( '"Select Multiple from options" fields create multiple checkboxes to choose one or more options.' ) . '</li>
	<li>' . _help( '"Text" fields create alphanumeric text fields with a maximum capacity of 255 characters.' ) . '</li>
	<li>' . _help( '"Long Text" fields create large alphanumeric text boxes with a maximum length of 5000 characters.' ) . '</li>
	<li>' . _help( '"Checkbox" fields create checkboxes. When checked it means "yes" and when un-checked "no".' ) . '</li>
	<li>' . _help( '"Number" fields create text fields that stores only numeric values.' ) . '</li>
	<li>' . _help( '"Date" field creates pull-downs fields to pick a date from.' ) . '</li>
	<li>' . _help( '"Files" field creates upload file field. Files can be later be downloaded and deleted.' ) . '</li>
	</ul>
	<p>' . _help( 'The "Required" checkbox, if checked, will make that field required so an error will be displayed if the field is empty when saving the page.' ) . '</p>

	<p>' . _help( 'The "Sort Order" determines the order in which the fields will be displayed on the School Information screen.' ) . '</p>

	<p>' . _help( 'Delete a field' ) . '</p>

	<p>' . _help( 'You can delete any School field simply by clicking on the "Delete" button in the upper right corner. Please note that you will lose all your data if you delete an already used field.' ) . '</p>';

	// Teacher & Parent & Student.
else:

	$help['School_Setup/Schools.php'] = '<p>' . _help( '<i>School Information</i> displays the name, address, and principal of the current school.' ) . '</p>';

	$help['School_Setup/Calendar.php'] = '<p>' . _help( '<i>Calendars</i> is a display of school events and your student\'s assignments. The calendar also displays whether or not school is in attendance on any given day. By default, the calendar displays the current month. The month and year displayed can be changed by changing the month and year pull-down menus at the top of the screen.' ) . '</p>

	<p>' . _help( 'The titles of school events and assignments are displayed in each date\'s box. Clicking on these titles will open a popup window that displays more information about the event or assignment. School events are preceded by a black stripe and assignments are preceded by a red stripe.' ) . '</p>

	<p>' . _help( 'For days that school is in attendance all day, the date\'s box is green. On partial days, the number of minutes that school is in session is displayed. If the school is not in attendance at all on any given day, the date\'s box is pink.' ) . '</p>

	<p>' . _help( 'If the school uses a Rotation of Numbered Days, the day\'s number is displayed in the day\'s box.' ) . '</p>';

endif;

// STUDENTS ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Students/Student.php&include=General_Info&student_id=new'] = '<p>' . _help( '<i>Add a Student</i> allows you to add a student to the system and enroll it.' ) . '</p>

	<p>' . _help( 'To add the student, enter the birth date, social security number, ethnicity, gender, birthplace, and grade. Then, select the effective date of the student\'s enrollment and the enrollment code from the pull-down menus at the bottom of the page. If you wish to specify a student ID for this student, enter the student ID into the text field labeled RosarioSIS ID. if you leave this field blank, RosarioSIS will generate an unused student ID and assign it to the new student. Finally, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'Note: a student cannot be deleted once it is enrolled in courses, has grades, attendance records, discipline referrals, or billing fees. You can drop him instead.' ) . '</p>';

	$help['Students/AddUsers.php'] = '<p>' . _help( '<i>Associate Parents with Students</i> allows you to associate parents to students.' ) . '</p>

	<p>' . _help( 'Once a parent\'s account has been set up, their children must be associated to their account with this program. If you have not already chosen a student earlier in your session, select a student by using the "Find a Student" Search screen. Next, search for a user to associate with the student. From the search result, you can select any number of users. You can select all the users in the list by checking the checkbox in the column headings above the list. After you have selected each desired user from this list, click the "Add Selected Parents" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'At any time after a student has been selected, you can see the parents already associated with that student. These parents are listed to the top of the user search screen / search results. These parents can be disassociated from this student by clicking the delete icon (-) next to the parent you wish to disassociate from the student. You will be asked to confirm this action.' ) . '</p>';

	$help['Students/AssignOtherInfo.php'] = '<p>' . _help( '<i>Group Assign Student Info</i> allows you to assign values to any of the Student data fields for a group of students in one action.' ) . '</p>

	<p>' . _help( 'First, search for students. From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. After selecting students, fill in any of the Student fields in the box above the student list. Fields that you leave blank will not affect the students you selected. After you have selected each desired student from this list and filled in each desired Other Info field, click the "Save" button at the top of the screen.' ) . '</p>';

	$help['Students/Letters.php'] = '<p>' . _help( '<i>Print Letters</i> allows you to print form letters for any number of students.' ) . '</p>

	<p>' . _help( 'First, search for students. From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. After selecting students, enter the letter text in the "Letter Text" text field above the student list.' ) . '</p>

	<p>' . _help( 'You can insert certain pieces of student information into your letter with special variables:' ) . '</p>
	<ul>
		<li><b>' . _help( 'Full Name:' ) . '</b> __FULL_NAME__</li>
		<li><b>' . _help( 'First Name:' ) . '</b> __FIRST_NAME__</li>
		<li><b>' . _help( 'Middle Name:' ) . '</b> __MIDDLE_NAME__</li>
		<li><b>' . _help( 'Last Name:' ) . '</b> __LAST_NAME__</li>
		<li><b>' . _help( 'RosarioSIS ID:' ) . '</b> __STUDENT_ID__</li>
		<li><b>' . _help( 'School:' ) . '</b> __SCHOOL_ID__</li>
		<li><b>' . _help( 'Grade Level:' ) . '</b> __GRADE_ID__</li>
	</ul>
	<p>' . _help( 'Also, you can choose to print the letters with mailing labels. The letters will have mailing labels positioned in such a way as to be visible in a windowed envelope when the sheet is folded in thirds. More than one letter may be printed per student if the student has guardians residing at more than one address.' ) . '</p>

	<p>' . _help( 'The letters will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.' ) . '</p>';

	$help['Students/General Information'] = '<p>' . _help( '<i>General Information</i> is a display of a student\'s fundamental information. This includes birth date, social security number, ethnicity, gender, birthplace, and grade. You can change any of this information by clicking on the value you want to change, changing the value, and clicking the "Save" button at the top of the page.' ) . '</p>';

	$help['Students/Addresses & Contacts'] = '<p>' . _help( '<i>Addresses & Contacts</i> is a display of a student\'s address and contact information.' ) . '</p>

	<p>' . _help( 'A student can have any number of addresses. To add an address, click the "Add a New Address" link and complete the empty fields in the Address box. Finally, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'Now, you can add a contact to this address. To do this, complete the contact\'s name, and again, press the "Save" button.' ) . '</p>

	<p>' . _help( 'You can now add more information about this contact by checking the "Custody" and "Emergency" checkboxes, after first clicking on their default value of "No" (cross). Relations marked as having "Custody" of the student receive mailings and relations marked as being "Emergency" contacts can be contacted in the case of an emergency.' ) . '</p>

	<p>' . _help( 'You can add other information about this contact, such as their cell phone number, fax number, occupation, workplace, etc. by filling in the title of the new data in the "Description" field and its corresponding value in the "Value" field.' ) . '</p>

	<p>' . _help( 'Contacts and information about contacts can be deleted by clicking on the delete icon (-) next to the information to be deleted. (Note: you will be asked to confirm all deletions.) Any information on the screen can be modified by first clicking on the information, then changing its value, and finally clicking the "Save" button at the top of the screen.' ) . '</p>';

	$help['Students/Medical'] = '<p>' . _help( '<i>Medical</i> is a display of a student\'s medical information.' ) . '</p>

	<p>' . _help( 'This includes the student\'s physician, the physician\'s phone, the student\'s preferred hospital, any medical comments, whether or not the student has a doctor\'s note, and comments concerning the doctor\'s note. To change any of these values, click on the value you want to change, change it, and click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'You can also add entries for each immunization or physical received by the student as well as any medical alerts such as allergies or illnesses.' ) . '</p>

	<p>' . _help( 'To add an immunization, physical, or medical alert, fill in the blank fields at the bottom of the appropriate list, and click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'To change an immunization, physical, or medical alert, click on the value you want to change, change it, and click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'To delete an immunization, physical, or medical alert, click on the delete icon (-) next to the item you want to delete. You will be asked to confirm your deletion.' ) . '</p>';

	$help['Students/Enrollment'] = '<p>' . _help( '<i>Enrollment</i> can be used to enroll or drop a student from any school. A student can have only one active enrollment record at any time.' ) . '</p>

	<p>' . _help( 'To drop a student, change the "Dropped" date to the effective date of the student\'s drop as well as the reason for his drop. Click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'Now you can reenroll the student. To do this, select the effective date of the student\'s enrollment and the reason for his enrollment from the blank line at the bottom of the list. Also, select the school at which the student should be enrolled and click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'The enrollment and drop dates and reasons can be modified by clicking on the values, changing them to the desired value, and clicking the "Save" button at the top of the screen.' ) . '</p>';

	$help['Students/AdvancedReport.php'] = '<p>' . _help( '<i>Advanced Report</i> is a tool that helps you create any report you want, easily.' ) . '</p>

	<p>' . _help( 'Select what you want to see on the report by checking the checkboxes next to the columns you wish to see on the report. The columns will appear in the list at the top of the screen in the order you have selected them.' ) . '</p>

	<p>' . _help( 'To get the list of students who have their birthday on a specific date, select the date using the "Birth Month" and "Birth Day" pull-down menus in the "Find a Student" box.' ) . '</p>';

	$help['Students/AddDrop.php'] = '<p>' . _help( '<i>Add / Drop Report</i> is a report of all the students who have enrolled or dropped their enrollment during the time period selected.' ) . '</p>

	<p>' . _help( 'To consult other time periods, change the dates in the top part of the page and click the "Go" button on the right of the end date.' ) . '</p>';

	$help['Students/StudentLabels.php'] = '<p>' . _help( '<i>Print Student Labels</i> allows you to generate labels for Student folders.' ) . '</p>

	<p>' . _help( '<i>Print Mailing Labels</i> allows you to generate mailing labels for a group of students, parents or families.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'Then, select the students & what to print on the label: use the checkboxes on the left hand side of the student list to select the students, and use the options below the "Include on Labels" section to select what information you wish to include. You can include the student\'s Attendance Teacher and Attendance Room on the folder label.' ) . '</p>';

	$help['Students/PrintStudentInfo.php'] = '<p>' . _help( '<i>Print Student Info</i> will generate a multi-page report out of the information present in the Student Info tabs.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'Then, select the students and the information on the report: use the checkboxes on the left hand side of the student list to select the students, and then, at the top of the screen, check the tabs of the Student Info you would like to include. You can also check "Mailing Labels" to add the mailing information to the report so you may mail it in a window envelope. When you\'re done, click "Print Info for Selected Students".' ) . '</p>';

	$help['Custom/MyReport.php'] = '<p>' . _help( '<i>My Report</i> will generate a report that can be downloaded to your desktop as an excel spreadsheet, with complete contact information.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'This report will provide a printable listing, or more appropriately, a spreadsheet of students and their contact information you might use in a mail merge directory, or other.' ) . '</p>

	<p>' . _help( 'Click on the Download icon at the top of the list to export the report to an Excel spreadsheet.' ) . '</p>';

	$help['Custom/CreateParents.php'] = '<p>' . _help( '<i>Create Parent Users</i> will let you create Parent accounts from the Contact information recorded for each Student.' ) . '</p>

	<p>' . _help( 'You must first select the Field where parent email addresses are stored. The drop down list contains Student and Address fields ("Text" type only).' ) . '</p>

	<p>' . _help( 'You can create a new Email text field using the <i>Student Fields</i> program.' ) . '</p>

	<p>' . _help( 'You then must select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'The list of students will show the parent name and email. Students will have a checkbox only if a valid email address was found in the email field.' ) . '</p>

	<p>' . _help( 'Customize the email content sent to parents using the large text boxes and substitution codes above the list.' ) . '</p>

	<p>' . _help( 'To test the Parent Users creation and check the email content, you can enter your email address in the Test Mode field.' ) . '</p>

	<p>' . _help( 'Click the "Create Parent Accounts for the Selected Students" button.' ) . '</p>';

	$help['Custom/Registration.php'] = '<p>' . _help( '<i>Registration</i> lets you configure the Registration program\'s form and preview how it will be displayed to Parents or Students.' ) . '</p>

	<p>' . _help( 'The <i>Registration</i> program is a convenient way to allow Students or Parents to self register their information once their Student account is activated.' ) . '</p>

	<p>' . _help( 'The first Parent fieldset allows you to edit the main Contact relation, check whether it should be marked as "Custody" and "Emergency", select which Contact Information and which Contact Field categories you would like to be completed.' ) . '</p>

	<p>' . _help( 'If no Contact Information is available, add it first to an existing contact from the <i>Student Info > Addresses & Contacts</i> tab.' ) . '</p>

	<p>' . _help( 'If no Contact Fields are available, you can add some from the <i>Student Fields</i> program.' ) . '</p>

	<p>' . _help( 'Next to the main Parent contact, a similar fieldset allows you to edit the secondary Contact form. You can select whether it has no address, or to use the same address as for the student, or to display a new address form.' ) . '</p>

	<p>' . _help( 'Below is the Address fieldset which allows you to select which Address Field categories you would like to be completed.' ) . '</p>

	<p>' . _help( 'If no Address Fields are available, you can add some from the <i>Student Fields</i> program.' ) . '</p>

	<p>' . _help( 'Below you can activate and configure other Contacts such as Grandparents.' ) . '</p>

	<p>' . _help( 'Finally, the Student fieldset allows you to select which Student Field categories you would like to be completed.' ) . '</p>

	<p>' . _help( 'You can add more Student Fields from the <em>Student Fields</em> program.' ) . '</p>

	<p>' . _help( 'Click the "Save" button once you are ready. To preview the form, click the "Preview" link at the top of the screen.' ) . '</p>

	<p>' . _help( 'Parents registering a student who already have siblings in the school will be shown the "Use same address and contact information as for [Student name]" checkbox.' ) . '</p>

	<p>' . _help( 'Tip: you can create a Student Field named "Parent email" and set it as the "Student email field" from the <i>School > Configuration</i> program. Students having the same Parent email will also be shown a "Use same address and contact information as for [Student name]" checkbox when registering.' ) . '</p>';

	$help['Custom/RemoveAccess.php'] = '<p>' . _help( '<i>Remove Access</i> will let you remove access for students and their associated parents.' ) . '</p>

	<p>' . _help( 'To grant access for students and their associated parents, click the "Grant Access" link.' ) . '</p>

	<p>' . _help( 'You then must select a student by using the "Students with Access" search screen.' ) . '</p>

	<p>' . _help( 'The list of students will show the student username. Only students having a username and a password set will be displayed in the list.' ) . '</p>

	<p>' . _help( 'Customize the username prefix you want to add so students cannot guess it.' ) . '</p>

	<p>' . _help( 'Check the "Remove Access for Associated Parents" checkbox if you wish to set those parents\' profile to No Access.' ) . '</p>

	<p>' . _help( 'Click the "Remove Access for Selected Students" button.' ) . '</p>';

	$help['Students/StudentFields.php'] = '<p>' . _help( '<i>Data Fields</i> allows you to setup your school\'s custom data fields. These fields are used to store information about a student in the "General Info" tab / "Addresses & Contacts" tab or a custom tab of the student screen.' ) . '</p>

	<p>' . _help( 'Data Field Categories' ) . '</p>

	<p>' . _help( 'RosarioSIS allows you to add custom categories that will take the form of new "Tabs" of Data Fields in the Students > Student Info program. To create a new category or "tab", just click on the "+" icon below the existing Categories.' ) . '</p>

	<p>' . _help( 'New Category' ) . '</p>

	<p>' . _help( 'You can now type in the name of the new Category in the "Title" field(s) provided. Add a sort order (order in which the tabs will appear in the Student Info program), and the number of columns the tab will display (optional). Click "Save" when you have finished.' ) . '</p>

	<p>' . _help( 'Add a new Field' ) . '</p>

	<p>' . _help( 'Click on the "+" icon below the "No Student Fields were found" text. Fill in the Field Name field(s), and then choose what type of field you wish with the "Data Type" pull-down.' ) . '</p>
	<ul>
	<li>' . _help( '"Pull-Down" fields create menus from which you can select one option. To create this type of field, click on "Pull-Down" and then add your options (one per line) in the "Pull-Down/Auto Pull-down/Export Pull-Down/Select Multiple from Options" text box.' ) . '</li>
	<li>' . _help( '"Auto Pull-Down" fields create menus from which you can select one option, and add options. You add options by selecting the "-Edit-" option in the menu choices and click "Save". You can then edit the field by removing the red "-Edit-" from the field, entering the correct information. RosarioSIS gets all the options that have been added to this field to create the pull-down.' ) . '</li>
	<li>' . _help( '"Export Pull-Down" fields are created by adding options to the large text box respecting the following pattern: "option shown"|"option exported". For example: "Two|2", where "Two" is displayed on screen to the user, and "2" is the value in a downloaded spreadsheet.' ) . '</li>
	<li>' . _help( '"Select Multiple from options" fields create multiple checkboxes to choose one or more options.' ) . '</li>
	<li>' . _help( '"Text" fields create alphanumeric text fields with a maximum capacity of 255 characters.' ) . '</li>
	<li>' . _help( '"Long Text" fields create large alphanumeric text boxes with a maximum length of 5000 characters.' ) . '</li>
	<li>' . _help( '"Checkbox" fields create checkboxes. When checked it means "yes" and when un-checked "no".' ) . '</li>
	<li>' . _help( '"Number" fields create text fields that stores only numeric values.' ) . '</li>
	<li>' . _help( '"Date" field creates pull-downs fields to pick a date from.' ) . '</li>
	<li>' . _help( '"Files" field creates upload file field. Files can be later be downloaded and deleted.' ) . '</li>
	</ul>
	<p>' . _help( 'The "Required" checkbox, if checked, will make that field required so an error will be displayed if the field is empty when saving the page.' ) . '</p>

	<p>' . _help( 'The "Sort Order" determines the order in which the fields will be displayed in the Student Info tab.' ) . '</p>

	<p>' . _help( 'Delete a field' ) . '</p>

	<p>' . _help( 'You can delete any Student field or Category simply by clicking on the "Delete" button in the upper right corner. Please note that you will lose all your data if you delete an already used field or category.' ) . '</p>';

	$help['Students/EnrollmentCodes.php'] = '<p>' . _help( '<i>Enrollment Codes</i> allows you to setup your school\'s enrollment codes. Enrollment codes are used in the Enrollment student screen, and specify the reason the student was enrolled or dropped from a school. These codes apply to all schools system-wide.' ) . '</p>

	<p>' . _help( 'The "Rollover default" column sets the code used by the Rollover program when enrolling students in the next school year. There must be exactly one Rollover default enrollment code (of type "Add").' ) . '</p>

	<p>' . _help( 'To add an enrollment code, fill in the enrollment code\'s title, short name, and type in the empty fields at the bottom of the enrollment codes list. Click the "Save" button.' ) . '</p>

	<p>' . _help( 'To modify an enrollment code, click on any of the enrollment code\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete an enrollment code, click the delete icon (-) next to the enrollment code you want to delete. You will be asked to confirm the deletion.' ) . '</p>';

	// Teacher & Parent & Student.
else:

	$help['Students/General Information'] = '<p>' . _help( '<i>General Information</i> is a display of a student\'s fundamental information. This includes birth date, social security number, ethnicity, gender, birthplace, and grade.' ) . '</p>';

	$help['Students/Addresses & Contacts'] = '<p>' . _help( '<i>Addresses & Contacts</i> is a display of a student\'s address and contact information.' ) . '</p>

	<p>' . _help( 'A student can have any number of addresses.' ) . '</p>';

	$help['Students/Enrollment'] = '<p>' . _help( '<i>Enrollment</i> is a display of the student\'s enrollment history.' ) . '</p>';

	$help['Custom/Registration.php'] = '<p>' . _help( '<i>Registration</i> will let you register your child\'s contacts details.' ) . '</p>

	<p>' . _help( 'Fill in the form fields with your contacts details and their associated addresses. Then, enter or update the student information.' ) . '</p>

	<p>' . _help( 'Once you have completed the forms, click the "Save" button at the bottom of the screen.' ) . '</p>';

endif;

// USERS ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Users/User.php'] = '<p>' . _help( '<i>General Information</i> is a display of a user\'s fundamental information. This includes his name, username, password, profile, school, email address, and phone number. If you are an administrator, you can change any of this information by clicking on the value you want to change, changing the value, and clicking the "Save" button at the top of the page. You can delete a user by clicking the "Delete" button at the top of the screen and confirming your action.' ) . '</p>

	<p>' . _help( 'Note: you cannot delete a user, for example a teacher, once they are assigned to a course; or a parent, once they are associated to a student.' ) . '</p>';

	$help['Users/User.php&staff_id=new'] = '<p>' . _help( '<i>Add a User</i> allows you to add a user to the system. This includes administrators, teachers, and parents. Simply fill in the new user\'s name, username, password, profile, school, email address, and phone number. Click the "Save" button.' ) . '</p>';

	$help['Users/AddStudents.php'] = '<p>' . _help( '<i>Associate Students with Parents</i> allows you to associate students to parents.' ) . '</p>

	<p>' . _help( 'Once a parent\'s account has been set up, their children must be associated to their account with this program. If you have not already chosen a user earlier in your session, select a user by using the "Find a User" Search screen. Next, search for a student to add to the user\'s account. From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. After you have selected each desired student from this list, click the "Add Selected Students" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'At any time after a user has been selected, you can see the students already associated with that user. These students are listed to the top of the student search screen / search results. These students can be disassociated from this user by clicking the delete icon (-) next to the student you wish to disassociate from the user. You will be asked to confirm this action.' ) . '</p>';

	$help['Users/Preferences.php'] = '<p>' . _help( '<i>My Preferences</i> will let you personalize RosarioSIS to meet your own needs. You can also change your password, and setup RosarioSIS to show data that is important for your work.' ) . '</p>

	<p>' . _help( 'Display Options tab' ) . '</p>

	<p>' . _help( 'It allows you to select your preferred RosarioSIS theme. You can change the theme (overall color scheme) or within a particular theme, the Highlight Color. You can also set the date format, like changing the month to "January", or "Jan" or "01". "Disable Login Alerts" will hide the alerts shown on the Portal (first page after login), like the Teachers missing attendance, the new discipline Referrals & the Food Service balance alerts.' ) . '</p>

	<p>' . _help( 'Student Listing tab' ) . '</p>

	<p>' . _help( '"Student Sorting" lets you choose to have the students in listings listed by just their "Name" or by their Grade Level and Name. "File Export Type" lets you choose between Tabulation delimited files, designed for Excel, or CSV files (comma-separated values) designed for LibreOffice, or XML files. "Date Export Format" lets you choose between different date formats when date fields are exported using the "Download Icon". "Display Student Search Screen" should remain checked, unless instructed otherwise.' ) . '</p>

	<p>' . _help( 'Password tab' ) . '</p>

	<p>' . _help( 'It will help you change your password. Simply enter your current password in the first text field, and your new password in the next two text fields. Finally, click "Save".' ) . '</p>

	<p>' . _help( 'Student Fields tab' ) . '</p>

	<p>' . _help( 'The two columns on the right side of the page lets you choose data fields to show on either the "Find a Student" Page or when you click "Expanded View" in a student listing. Click the "Search" checkbox to add an often-used field to your "Find a Student" page, instead of having to click "Advanced Search" to use that often-used field. Click the "Expanded View" checkbox adds that field to your Expanded View report. You can add or remove fields as often as you want, customizing the Search page and the Expanded View report.' ) . '</p>';

	$help['Users/Profiles.php'] = '<p>' . _help( '<i>User Profiles</i> helps you configure how the users access to the information, and if they can modify it.' ) . '</p>

	<p>' . _help( 'RosarioSIS comes with four groups as profiles: Administrator, Teacher, Parent & Student. The Administrator Profile has the most permissions, and the other profiles are restricted as appropriate. Please note that teachers are limited in access to the students scheduled in their classes, and that parents can only see the information of thier children, and that students can only see their personal information.' ) . '</p>

	<p>' . _help( 'If you click on one of the Profiles, you will see the Permissions Page. This page shows to which page(s) the profile has access to READ (Can Use) or to WRITE (Can Edit) the information on that particular page.' ) . '</p>

	<p>' . _help( 'When you uncheck "Can Edit", the users belonging to the profile will see the program in the menu and will see data on the page when clicking on it. They will NOT be able to change any of that information on that page. When you uncheck "Can Use" on a particular program, then users belonging to the profile will not see the program in the menu on the left hand side, and will not be able to access it.' ) . '</p>

	<p>' . _help( 'Administrator Profile' ) . '</p>

	<p>' . _help( 'Administrators have access to almost all pages, to read or to write information on that page. By default, they cannot see the "Comments" tab in the Student Info program, but can access and can modify all other pages.' ) . '</p>

	<p>' . _help( 'You can restrict the User Profile edition if you uncheck the <i>Users > User Info > General Info > User Profile</i> checkbox. Administrators will then loose the ability to assign user profiles (and permissions).' ) . '</p>

	<p>' . _help( 'You can restrict the User Schools edition if you uncheck the <i>Users > User Info > General Info > Schools</i> checkbox. Administrators will then loose the ability to add or remove schools to/from a user.' ) . '</p>

	<p>' . _help( 'Teacher Profile' ) . '</p>

	<p>' . _help( 'Teachers have the permission to access a more limited set of pages within RosarioSIS, and their ability to edit those pages is more restricted. By default, teachers cannot change any data about a student EXCEPT for the Comments tab.' ) . '</p>

	<p>' . _help( 'Parent Profile' ) . '</p>

	<p>' . _help( 'Parents are even more limited. Parents only have access to information that is specifically of interest to them, the student\'s demographic information, attendance and grades.' ) . '</p>

	<p>' . _help( 'Add a User Profile' ) . '</p>

	<p>' . _help( 'For security reasons, it is recommended to add an "admin" profile to "Administrator" in order to limit the permissions of administrators. It should not be necessary for ALL administrators to be able to Add Schools, Copy Schools, change Marking Periods, or change Grading Scales, etc. Once the configuration of the school is done, changes to the configuration by unknowledgable users can be a source of troubles or dysfunctions.' ) . '</p>

	<p>' . _help( 'To add a new Profile, type its name in the "Title" text box and then select its "Type" of profile. Finally, click the "Save" button in the upper part of the screen.' ) . '</p>

	<p>' . _help( 'Setting Permissions' ) . '</p>

	<p>' . _help( 'To configure the permissions your users should have, it is a good practice to login with a test user belonging to the profile and figure out what can be accessed. Less is more!' ) . '</p>';

	$help['Users/Exceptions.php'] = '<p>' . _help( '<i>User Permissions</i> allows you to deny access and/or write privileges to any program for any user.' ) . '</p>

	<p>' . _help( 'To assign privileges to a user, first select a user by searching and clicking on his name on the list. Then, use the checkboxes to define which programs the user can use and which programs he can use to modify information. If a user cannot use a particular program, the program will not be displayed on his menu. If he can use the program, but can\'t edit information with the program, the program will display the data, but won\'t let him change it. After you have completed the program checkboxes, click the "Save" button to save the user\'s permissions.' ) . '</p>';

	$help['Users/UserFields.php'] = '<p>' . _help( '<i>User Fields</i> allows you to add new fields and tabs to the User Info screen.' ) . '</p>

	<p>' . _help( 'User Field Categories' ) . '</p>

	<p>' . _help( 'RosarioSIS allows you to add custom categories that will take the form of new "Tabs" of User Fields in the Users > User Info program. To create a new category or "tab", just click on the "+" icon below the existing Categories.' ) . '</p>

	<p>' . _help( 'New Category' ) . '</p>

	<p>' . _help( 'You can now type in the name of the new Category in the "Title" field(s) provided. Add a sort order (order in which the tabs will appear in the User Info program), and the number of columns the tab will display (optional). Click "Save" when you have finished.' ) . '</p>

	<p>' . _help( 'Add a new Field' ) . '</p>

	<p>' . _help( 'Click on the "+" icon below the "No User Fields were found" text. Fill in the Field Name field(s), and then choose what type of field you wish with the "Data Type" pull-down.' ) . '</p>
	<ul>
	<li>' . _help( '"Pull-Down" fields create menus from which you can select one option. To create this type of field, click on "Pull-Down" and then add your options (one per line) in the "Pull-Down/Auto Pull-down/Export Pull-Down/Select Multiple from Options" text box.' ) . '</li>
	<li>' . _help( '"Auto Pull-Down" fields create menus from which you can select one option, and add options. You add options by selecting the "-Edit-" option in the menu choices and click "Save". You can then edit the field by removing the red "-Edit-" from the field, entering the correct information. RosarioSIS gets all the options that have been added to this field to create the pull-down.' ) . '</li>
	<li>' . _help( '"Export Pull-Down" fields are created by adding options to the large text box respecting the following pattern: "option shown"|"option exported". For example: "Two|2", where "Two" is displayed on screen to the user, and "2" is the value in a downloaded spreadsheet.' ) . '</li>
	<li>' . _help( '"Select Multiple from options" fields create multiple checkboxes to choose one or more options.' ) . '</li>
	<li>' . _help( '"Text" fields create alphanumeric text fields with a maximum capacity of 255 characters.' ) . '</li>
	<li>' . _help( '"Long Text" fields create large alphanumeric text boxes with a maximum length of 5000 characters.' ) . '</li>
	<li>' . _help( '"Checkbox" fields create checkboxes. When checked it means "yes" and when un-checked "no".' ) . '</li>
	<li>' . _help( '"Number" fields create text fields that stores only numeric values.' ) . '</li>
	<li>' . _help( '"Date" field creates pull-downs fields to pick a date from.' ) . '</li>
	<li>' . _help( '"Files" field creates upload file field. Files can be later be downloaded and deleted.' ) . '</li>
	</ul>
	<p>' . _help( 'The "Required" checkbox, if checked, will make that field required so an error will be displayed if the field is empty when saving the page.' ) . '</p>

	<p>' . _help( 'The "Sort Order" determines the order in which the fields will be displayed in the User Info tab.' ) . '</p>

	<p>' . _help( 'Delete a field' ) . '</p>

	<p>' . _help( 'You can delete any User field or Category simply by clicking on the "Delete" button in the upper right corner. Please note that you will lose all your data if you delete an already used field or category.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php'] = '<p>' . _help( '<i>Teacher Programs: Input Final Grades</i> allows you to enter quarter, semester or progress period grades for all the selected teacher\'s students in the current period. By default, this program will list the students in the selected teacher\'s first period class for the current quarter. You can alter the period by changing the period pull-down menu at the top of the screen. Also, you can alter the quarter by changing the marking period pull-down menu on the left frame. Furthermore, you can select the current semester or progress period by changing the marking period pull-down menu at the top of the screen to the desired marking period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct marking period, you can enter student grades by selecting the earned grade for each student and entering comments as desired. Once all the grades and comments have been entered, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'If the selected teacher is using the Gradebook, you can have RosarioSIS calculate each student\'s quarter grades by clicking on the "Get Gradebook Grades" link at the top of the list. Clicking this link will automatically save each student\'s grades and refresh the list.' ) . '</p>

	<p>' . _help( 'If the marking period you are in is a Progress Period, when clicking on the "Get Gradebook Grades" link, the gades taken in account will be limited to the one for which the Assignment Due Date is comprised within the Progress Period, or the ones with no Due Dates.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Grades/Grades.php'] = '<p>' . _help( '<i>Teacher Programs: Gradebook Grades</i> allows you to consult and modify any grade of the students gradebooks. You can select the teachers classes using the course period pull-down in the upper left corner of the page. The Gradebook Grades of the class will be diplayed. As an administrator, you can pick any individual student, or totals for assignment categories, or all students for any or all assignments. The "All" pull-down menu lets you select an assignment category, or alternatively you can use the tabs on the top the grades listing. The "Totals" pull-down menu lets you select a particular assignment or the "total" of all assignments.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php'] = '<p>' . _help( '<i>Teacher Programs: Anomalous Grades</i> is a report that helps a teacher to keep track of missing, inappropriate and excused grades. The grades appearing on this report are NOT problematic, but a teacher MAY wish to review them. Missing, excused & negative grades, or grades that are extra credit or that exceed 100% are shown. The "Problem" column indicates the reason why the grade is anomalous.' ) . '</p>

	<p>' . _help( 'You can select the teachers classes using the course period pull-down in the upper left corner of the page. You can also select which type of "anomalous" grades you wish the report to display.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Grades/ProgressReports.php'] = '<p>' . _help( '<i>Teacher Programs: Progress Reports</i> lets you generate Progress Reports of the students for the current Marking Period, on behalf of a teacher.' ) . '</p>

	<p>' . _help( 'When teachers have created assignments and entered gradebook grades, a progress report can be generated.' ) . '</p>

	<p>' . _help( 'Once you have selected a Teacher and are in the correct period, search the students and click Submit to get a list of students scheduled with the selected Teacher. You can alter the current period by changing the period pull-down menu at the top of the screen to the desired period.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list.' ) . '</p>

	<p>' . _help( 'Check desired options to appear on the report. Checking "Assigned Date" will display the Assignment\'s Assigned Date . Checking "Due Date" will display the Assignment\'s Due Date. Checking on "Exclude Ungraded Assignments Not Due" will remove assignments which are ungraded and not due. Checking "Group by Assignment Category" will group assignments by Category and will display total points and weight for each Category.' ) . '</p>

	<p>' . _help( 'The progress reports will be automatically downloaded to your computer in the printable PDF format when you click the "Create Progress Reports for Selected Students" button.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php'] = '<p>' . _help( '<i>Teacher Programs: Take Attendance</i> allows you to enter period attendance for all the selected teacher\'s students. By default, this program will list the students in the selected teacher\'s first period class. You can alter the current period by changing the period pull-down menu at the top of the screen to the desired period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct period, you can enter attendance by selecting the attendance code corresponding to each student. Once you have entered attendance for all the students, click the "Save" button at the top of the screen.' ) . '</p>';

	$help['Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php'] = '<p>' . _help( '<i>Teacher Programs: Enter Eligibility</i> allows you to enter eligibility grades for all the selected teacher\'s students. By default, this program will list the students in the selected teacher\'s first period class. You can alter the current period by changing the period pull-down menu in the left frame to the desired period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct period, you can enter eligibility grades by selecting the eligibility code corresponding to each student. Once you have entered eligibility for all the students, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'If the selected teacher is using the Gradebook, you can have RosarioSIS calculate each student\'s eligibility grades by clicking on the "Use Gradebook Grades" link at the top of the list. Clicking this link will automatically save each student\'s eligibility grades and refresh the list.' ) . '</p>

	<p>' . _help( 'You must enter eligibility each week during the timeframe specified by your school\'s administration.' ) . '</p>';

endif;

// SCHEDULING ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Scheduling/Schedule.php'] = '<p>' . _help( '<i>Student Schedule</i> allows you to modify a student\'s course schedule.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen. You can search for students who have requested a specific course or request by clicking on the "Choose" link next to the search options "Course" and "Request" respectively and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'To add a course to the student\'s schedule, click on the "Add a Course" link next to the add icon (+) and select a course from the popup window that appears. The screen will automatically refresh to show the course addition.' ) . '</p>

	<p>' . _help( 'To drop an existing course, select the "Dropped" date next to the course you want to drop from the student\'s schedule. If you select a "Dropped" date prior to the "Enrolled" date, the course will be removed and you will be asked to confirm the removal of the associated absences and grades records.' ) . '</p>

	<p>' . _help( 'To change the course period of a course for the student, click on the "Period - Teacher" of the course you want to change and select the new course period. You can also change the term in the same fashion.' ) . '</p>

	<p>' . _help( 'All deletions, and modifications to a student\'s schedule are not made permanent until you click the "Save" button at the top of the screen.' ) . '</p>';

	$help['Scheduling/Requests.php'] = '<p>' . _help( '<i>Student Requests</i> allows you to specify which courses a student intends to take in the next school year. These requests are used by the Scheduler when filling a student\'s schedule.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen. You can search for students who have requested a specific request by clicking on the "Choose" link next to the search option "Request" and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'You can add a request by selecting the course you want to add from under the corresponding subject heading. You can add requests from each subject in the same way, or you can add another request in the same subject by clicking on the on the subject name in the last line of the list with the add icon (+). Doing this will cause another set of request pull-down menus to appear under the subject heading. Once you have added all the desired requests, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . // Note: Remove help sentence. The Scheduler is not run by the Student Requests program.
	/*_help( 'When you save the student\'s requests, the Student Requests program will run the Scheduler without saving the schedule for the current student to notify you of any conflicts. The Scheduler output will also tell you if any course requested has zero available seats. If a request could not be met, you can change the requests accordingly to ensure complete scheduling. You will also be given the option to schedule the student with the requests you entered.' ) .*/ '</p>

	<p>' . _help( 'Furthermore, when you have saved the student\'s requests, you will have the option to specify a teacher or period and to exclude a teacher or period. To do this, select the teacher or period from the "With" and "Without" pull-down menus respectively. Once you have made all the desired modifications, click the "Save" button. You can also delete a request that you entered by clicking on the delete icon (-).' ) . '</p>';

	$help['Scheduling/MassSchedule.php'] = '<p>' . _help( '<i>Group Schedule</i> allows you to schedule a group of students into one or more courses in one action.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who have requested a specific course or request by clicking on the "Choose" link next to the search options "Course" and "Request" respectively and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Select a course period to add by clicking the "Choose a Course" link at the top of the screen and choosing the course from the popup screen that appears. The window will close and the course period will now show on the page.' ) . '</p>

	<p>' . _help( 'Repeat the last step to select and add another course period.' ) . '</p>

	<p>' . _help( 'Then, select the proper "Start Date" (the date that the students will first attend this course period), and the appropriate "Marking Period".' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. To select all the students in the list, check the checkbox in the column headings above the list. After you have selected each desired student from this list, click the "Add Courses to Selected Students" button at the top of the screen.' ) . '</p>';

	$help['Scheduling/MassRequests.php'] = '<p>' . _help( '<i>Group Requests</i> allows you to add a request to a group of students in one action.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who have requested a specific request by clicking on the "Choose" link next to the search option "Request" and choosing a course from the popup window that appears. Notice that you can search for students who already have a certain request or are in a certain activity. This can be useful since you can add a laboratory course request to all students who requested chemistry. Or you can add a P.E. course request to all students in Boy\'s Basketball.' ) . '</p>

	<p>' . _help( 'Select a course to add as a request by clicking the "Choose a Course" link at the top of the screen and choosing the course from the popup screen that appears.' ) . '</p>

	<p>' . _help( 'Then, select the proper "With" or "Without" Teacher, and the correct Period.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. To select all the students in the list, check the checkbox in column headings above the list. After you have selected each desired student from this list, click the "Add Request to Selected Students" button at the top of the screen. If you have not yet chosen a course to add as a request, you must do that before you click this button.' ) . '</p>';

	$help['Scheduling/MassDrops.php'] = '<p>' . _help( '<i>Group Drops</i> allows you to drop a course for a group of students in one action.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who have requested a specific course or request by clicking on the "Choose" link next to the search options "Course" and "Request" respectively and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Select a course period to drop by clicking the "Choose a Course" link at the top of the screen and choosing the course from the popup screen that appears. The window will close and the course period will now show on the page.' ) . '</p>

	<p>' . _help( 'Then, select the proper "Drop Date" (the date that the students will drop this course period), and the appropriate "Marking Period".' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. To select all the students in the list, check the checkbox in the column headings above the list. After you have selected each desired student from this list, click the "Drop Course for Selected Students" button at the top of the screen.' ) . '</p>';

	$help['Scheduling/PrintSchedules.php'] = '<p>' . _help( '<i>Print Schedules</i> is a utility that allows you to print schedules for any number of students.' ) . '</p>

	<p>' . _help( 'You can search for students who requested or are enrolled in a specific course by clicking the "Choose" link next to the "Request" and "Course" search options respectively and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Also, you can choose to print the schedules with mailing labels. The schedules will have mailing labels positioned in such a way as to be visible in a windowed envelope when the sheet is folded in thirds. More than one schedule may be printed per student if the student has guardians residing at more than one address.' ) . '</p>

	<p>' . _help( 'The schedules will be automatically downloaded to your computer in the printable PDF format when you click the "Create Schedules for Selected Students" button.' ) . '</p>';

	$help['Scheduling/PrintClassLists.php'] = '<p>' . _help( '<i>Print Class Lists</i> will allow you to print a report of students in classes. You can narrow the classes by either the Teacher or Subject or Period or Course Period.' ) . '</p>

	<p>' . _help( 'First, select the Classes' ) . '</p>

	<p>' . _help( 'Selecting a "Teacher" will show all the classes for that one teacher. Selecting a "Subject" will show the classes for that one subject. Selecting a "Period" will show all the classes for that individual period. Selecting a "Course" via the "Choose" link will show that individual course period with one teacher.' ) . '</p>

	<p>' . _help( 'Then, on the left hand side of the page, check the columns you would like to see on the list. The fields, in the order you have selected them, will appear in the list at the top of the page.' ) . '</p>

	<p>' . _help( 'Finally, select the Classes to List on the report at the bottom of the page and click "Create Class Lists for Selected Course Periods".' ) . '</p>

	<p>' . _help( 'The Class Lists with the selected columns will be generated as a PDF document that can be printed or sent by email.' ) . '</p>';

	$help['Scheduling/PrintRequests.php'] = '<p>' . _help( '<i>Print Requests</i> is a utility that allows you to print requests sheets for any number of students.' ) . '</p>

	<p>' . _help( 'You can search for students who requested a specific course by clicking the "Choose" link next to the "Request" search option and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Also, you can choose to print the requests sheets with mailing labels. The requests sheets will have mailing labels positioned in such a way as to be visible in a windowed envelope when the sheet is folded in thirds. More than one request sheet may be printed per student if the student has guardians residing at more than one address.' ) . '</p>

	<p>' . _help( 'The request sheets will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.' ) . '</p>';

	$help['Scheduling/ScheduleReport.php'] = '<p>' . _help( '<i>Schedule Report</i> is a report that shows the students who are scheduled into each course, the students who requested the course but weren\'t successfully scheduled into it, and the number of requests, open seats, and total seats in each course.' ) . '</p>

	<p>' . _help( 'To navigate through this report, first click on any one of the subjects. You will now see each course in that subject as well as the number of requests for that course and open and total seats available for that course. If you choose a course by clicking on it, you will see a list of the course periods, and the requests, open, and total seats numbers will be broken down by each period. Here, you can also see a list of students scheduled in the course or a list of students who requested the course but weren\'t scheduled into it by clicking the "List Students" and "List Unscheduled Students" links respectively.' ) . '</p>

	<p>' . _help( 'At any point after selecting a subject, you can navigate backwards by clicking on the links that appear in the grey bar at the top of the screen.' ) . '</p>';

	$help['Scheduling/RequestsReport.php'] = '<p>' . _help( '<i>Requests Report</i> is a report that shows the number of students who requested each course and the number of total seats in that course. The courses are grouped by subject.' ) . '</p>

	<p>' . _help( 'This report is useful for creating the master schedule since it helps you determine the number of course periods necessary for each course due to demand for the course.' ) . '</p>

	<p>' . _help( '<i>Unfilled Requests</i> is a report of course requests unfilled for a group of students.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'The report shows the student information along with the request unfilled details (teacher and period requested) and the number of sections or course periods which have been setup for the course (in the Scheduling > Courses program). You can also check the available seats by checking "Show Available Seats" at the top of the screen.' ) . '</p>

	<p>' . _help( 'Clicking on the student\'s name will redirect you to the Student Requests program.' ) . '</p>';

	$help['Scheduling/IncompleteSchedules.php'] = '<p>' . _help( '<i>Incomplete Schedules</i> is a report of students who have no class scheduled in a particular period.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who have requested a specific course or request by clicking on the "Choose" link next to the search options "Course" and "Request" respectively and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Then, the students in the list are not scheduled in the periods corresponding to the columns where they have red "X" icons. If the period column has a green "tick" icon, the student have a class scheduled in that period. A red "X" icon therefore indicates a free, unscheduled period that can be scheduled.' ) . '</p>';

	$help['Scheduling/AddDrop.php'] = '<p>' . _help( '<i>Add / Drop Report</i> is a report of students who have had classes added to, or dropped from, their schedule during the timeframe selected. You can select a different timeframe by changing the dates at the top of the screen, and then clicking the "Go" button. The report shows student information along with the Course, Course Period, Enrolled and Dropped dates. You can export the report to a spreadsheet using the "Download" icon.' ) . '</p>';

	$help['Scheduling/Courses.php'] = '<p>' . _help( '<i>Courses</i> allows you to setup your school\'s courses. There are three tiers of courses: Subjects, Courses, and Course Periods.' ) . '</p>

	<p>' . _help( 'To add any of these three things, click on the Add icon (+) in the column corresponding to what you want to add. Then, fill in the information requested in the fields above the list of courses and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To change any of these three things, click on the item you want to change, and click on whatever value you want to change in the grey area above the lists. Then, change the value and click the "Save" button.' ) . '</p>

	<p>' . _help( 'Finally, to delete something, select it by clicking on its title on the list and click the "Delete" button at the top of the screen. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'Course' ) . '<ul>
		<li>' . _help( '<i>Description</i>: the description is optional. You can, for example, enter the course syllabus. By default, students and parents can browse the courses list and thus consult descriptions.' ) . '</li>
		<li>' . _help( '<i>Credit Hours</i> (abbreviated "C.H."), or the number of hours to complete the course; can be displayed on Transcripts. This field is optional.' ) . '</li>
	</ul>
	</p>

	<p>' . _help( 'Course Period (or Class)' ) . '<ul>
		<li>' . _help( '<i>Seats</i>: this field is optional. When set, you can check how many seats are available (based on the number of students enrolled in the class).' ) . '</li>
		<li>' . _help( '<i>Period - Days</i>: you can add various Periods, each with their own Days. Click on the "Add a Period" link. To remove a Period, uncheck all Days and click on the "Save" button. A Course Period must have at least one Period, or else it will not appear in the Teacher\'s left menu.' ) . '</li>
		<li>' . _help( '<i>Calendar</i>: associate a Calendar. Students in the class should be associated to that same Calendar. Note: you can create various calendars using the <i>School > Calendars</i> program.' ) . '</li>
		<li>' . _help( '<i>Grading Scale</i>: choose the "Not Graded" option if you do not wish to enter Final Grades for this class (the Gradebook will still be available). Note: you can create various grading scales using the <i>Grades > Grading Scales</i> program.' ) . '</li>
		<li>' . _help( '<i>Allow Teacher Grade Scale</i>: when checked, the teacher will be able to define custom Breakoff values for the Grading Scale.' ) . '</li>
		<li>' . _help( '<i>Credits</i> is both the <b>coefficient</b> used to weigh Final Grades in order to calculate the overall / cumulative GPA, and the Credits attempted by students. 0 credits will be earned by a student whose grade is below the minimum (see the "Minimum Passing Grade" field in the <i>Grades > Grading Scales</i> program for configuration). Credits earned can be displayed on Transcripts and Credits can be displayed on Report Cards. For example, if you assign 4 credits to a Full Year Course Period and have 4 Quarters, students will earn 1 credit for each Quarter.' ) . '</li>
		<li>' . _help( '<i>Gender Restriction</i>: the <i>Run Scheduler</i>, <i>Student Schedule</i> ("Unfilled Requests" list) and <i>Requests Report</i> programs make use of this restriction.' ) . '</li>
		<li>' . _help( '<i>Parent Course Period</i>: optionally choose a parent course period. The <i>Run Scheduler</i> program makes use of it.' ) . '</li>
	</ul>
	</p>

	<p>' . _help( 'Note: a course period cannot be deleted once it has students enrolled.' ) . '</p>';

	$help['Scheduling/Scheduler.php'] = '<p>' . _help( '<i>Run Scheduler</i> schedules every student at your school according to the requests entered for them.' ) . '</p>

	<p>' . _help( 'You first must confirm the Scheduler run. Here, you can also choose to run the scheduler in "Test Mode" which will not save the student schedules.' ) . '</p>

	<p>' . _help( 'Once the scheduler has run, which could take several minutes, it will notify you of any conflicts. The Scheduler output will also tell you if any course requested has zero available seats. If a request could not be met, you can change the requests accordingly to ensure complete scheduling. Once the schedules have been saved, you will be given the option to view the Schedule Report.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'teacher' ):

	$help['Scheduling/Schedule.php'] = '<p>' . _help( '<i>Schedule</i> is a display of the student\'s course schedule.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>';

// Parent & Student.
else:

	$help['Scheduling/Schedule.php'] = '<p>' . _help( '<i>Schedule</i> is a display of your child\'s course schedule.' ) . '</p>';

endif;

// GRADES ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Grades/ReportCards.php'] = '<p>' . _help( '<i>Report Cards</i> is a utility that allows you to print report cards for any number of students.' ) . '</p>

	<p>' . _help( 'You can search for students who are enrolled in a specific course by clicking the "Choose" link next to the "Course" search option and choosing a course from the popup window that appears. You can also limit your search based on weighted/unweighted GPA, class rank, and letter grade by filling in the upper and lower bounds of the GPA and class rank range and checking the desired letter grade checkboxes. For example, this allows you to search for all students in the top ten of their class, all students who are failing, or all students who have failed at least one course in the marking periods selected.' ) . '</p>

	<p>' . _help( 'Also, you can choose to print the report cards with mailing labels. The report cards will have mailing labels positioned in such a way as to be visible in a windowed envelope when the sheet is folded in thirds. More than one report card may be printed per student if the student has guardians residing at more than one address.' ) . '</p>

	<p>' . _help( 'Before printing the report cards, you must select which marking periods to display on the report card by checking desired marking period checkboxes.' ) . '</p>

	<p>' . _help( 'The report cards will be automatically downloaded to your computer in the printable PDF format when you click the "Create Report Cards for Selected Students" button.' ) . '</p>';

	$help['Grades/HonorRoll.php'] = '<p>' . _help( '<i>Honor Roll</i> allows you to create honor roll lists or certificates.' ) . '</p>

	<p>' . _help( 'The Honor Roll GPA values are setup via the Grades > Grading Scales program.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who qualify for "Honor" or "High Honor" by checking the respective checkboxes. You can also search for students who have requested a specific course by clicking on the "Choose" link next to the search option "Course" and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'Alternatively, you can choose a Subject from the dropdown list on the "Find a Student" search screen to create honor roll lists or certificates by subject.' ) . '</p>

	<p>' . _help( 'Then, you can generate "Certificates" or a "List" of the qualifiers by selecting the right option at the top of the screen. The Certificate text can be personalized by editing it. Finally, click the "Create Honor Roll for Selected Students" button to generate the Honor Roll certificates or the List of qualifiers in a PDF format to print or email. Alternatively, you can click the "Download" icon to generate a spreadsheet of this data.' ) . '</p>';

	$help['Grades/Transcripts.php'] = '<p>' . _help( '<i>Transcripts</i> is a utility that allows you to print transcripts for any number of students.' ) . '</p>

	<p>' . _help( 'You can search for students who are enrolled in a specific course by clicking the "Choose" link next to the "Course" search option and choosing a course from the popup window that appears. You can also limit your search based on weighted/unweighted GPA, class rank, and letter grade by filling in the upper and lower bounds of the GPA and class rank range and checking the desired letter grade checkboxes. For example, this allows you to search for all students in the top ten of their class, all students who are failing, or all students who have failed at least one course in the marking periods selected.' ) . '</p>

	<p>' . _help( 'Before printing the transcripts, you must select which marking periods to display on the transcript by checking desired marking period checkboxes.' ) . '</p>

	<p>' . _help( 'The transcripts will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.' ) . '</p>';

	$help['Grades/TeacherCompletion.php'] = '<p>' . _help( '<i>Teacher Completion</i> is a report that shows which teachers have not entered grades for any given marking period.' ) . '</p>

	<p>' . _help( 'The red checks indicate that a teacher has failed to enter the current marking period\'s grades for that period.' ) . '</p>

	<p>' . _help( 'You can select the current quarter, semester from the pull-down menu at the top of the screen. To change the current quarter, change the marking period pull-down menu on the left frame. You can also show only one period by choosing that period from the period pull-down menu at the top of the screen.' ) . '</p>';

	$help['Grades/GradeBreakdown.php'] = '<p>' . _help( '<i>Grade Breakdown</i> is a report that shows the number of each grade that a teacher gave.' ) . '</p>

	<p>' . _help( 'You can select the current quarter, semester from the pull-down menu at the top of the screen. To change the current quarter, change the marking period pull-down menu on the left frame.' ) . '</p>';

	$help['Grades/StudentGrades.php'] = '<p>' . _help( '<i>Student Grades</i> allows you to view the grades earned by a student.' ) . '</p>

	<p>' . _help( 'You can search for students who are enrolled in a specific course by clicking the "Choose" link next to the "Course" search option and choosing a course from the popup window that appears. You can also limit your search based on weighted/unweighted GPA, class rank, and letter grade by filling in the upper and lower bounds of the GPA and class rank range and checking the desired letter grade checkboxes. For example, this allows you to search for all students in the top ten of their class, all students who are failing, or all students who have failed at least one course in the marking periods selected.' ) . '</p>';

	$help['Grades/FinalGrades.php'] = '<p>' . _help( '<i>Final Grades</i> allows you to view the final grades earned by any number of students.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'Then, select what you would like to include on the Grade List: "Teacher", "Comments" and "Year-to-date Daily Absences" are pre-checked by default. If you wish to include other columns, please check them. Do not forget to check the Marking Periods to show on the Grade List.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list.' ) . '</p>

	<p>' . _help( 'Finally, click the "Create Grade Lists for Selected Students" button.' ) . '</p>

	<p>' . _help( 'Please note that if you select only ONE marking period, you will be able to delete Final Grades by clicking the (-) icon on the left hand side of the page, and then confirm your choice.' ) . '</p>';

	$help['Grades/GPARankList.php'] = '<p>' . _help( '<i>GPA / Class Rank List</i> is a report that shows the unweighted GPA, weighted GPA, and class rank of each student at your school.' ) . '</p>

	<p>' . _help( 'As with any list in RosarioSIS, you can sort by any value displayed by clicking on the coresponding column heading. For example, you can sort by grade by clicking on the "Grade" column heading. Similarly, you can sort by unweighted GPA by clicking on the "Unweighted GPA" column heading.' ) . '</p>';

	$help['Grades/Configuration.php'] = '<p>' . _help( '<i>Configuration</i> allows you to override individual teacher configuration and configure the gradebook for them.' ) . '</p>

	<p>' . _help( 'By default, grades are weighted by points. This means that the number of points in each assignment determines its weight, and the total of points in all assignments in a category determines how much the category counts.' ) . '</p>

	<p>' . _help( 'You can choose to weight grades by assignment categories. This will add a "Percent of Final Grade" field to categories.' ) . '</p>

	<p>' . _help( 'You can also choose to weight assignments. This lets you edit the weight of each assignment. In case you do not weight categories, the total of weights in all assignments in a category determines how much the category counts.' ) . '</p>

	<p>' . _help( 'Finally, you can also configure the final grading percentages of each semester. These values are used when averaging the quarter grades to calculate the semester grade.' ) . '</p>';

	$help['Grades/ReportCardGrades.php'] = '<p>' . _help( '<i>Grading Scales</i> allows you to setup your school\'s report card grades. Report card grades are used in the Input Final Grades program by teachers and in most of the Grades reports. Report card grades include letter grades as well as grade comments that a teacher can choose from when entering grades.' ) . '</p>

	<p>' . _help( 'To add a report card grade, fill in the grade\'s title, GPA value, and sort order in the empty fields at the bottom of the grades list and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To add a comment, enter the new comment\'s title in the empty field at the bottom of the comments list.' ) . '</p>

	<p>' . _help( 'To modify either type of grade, click on any of the grade\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete either type of grade, click the delete icon (-) next to the grade you want to delete. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'To add or edit a grade scale, first click the plus icon (+) tab. For each grade scale you should adjust their scale value, minimum passing grade (minimum grade to earn credits), along with various honor roll minimum GPAs.' ) . '</p>';

	$help['Grades/ReportCardComments.php'] = '<p>' . _help( '<i>Report Card Comments</i> allows you to setup your school\'s report card comments, for each course or for all courses.' ) . '</p>

	<p>' . _help( 'The "All Courses" tab is where you create Comments that apply to All Courses, for example to grade conduct, or a quality of the students that all courses share in common. The (+) tab is where you add other comments, specifically course-specific comment tabs and comments.' ) . '</p>

	<p>' . _help( 'The "General" tab contains the comments that are added when entering students\' grades in the "Input Final Grades" program. Teachers can use the pull-down menu under the "General" tab to add one or more pre-designed comments to the report card. Please note that RosarioSIS has placeholder symbols that can be used in these comments: "^n" will be replaced by the student\'s first name, while "^s" will be replaced a gender-appropriate pronoun. For example, the comment "^n Comes to ^s Class Unprepared" will be translated to "John Comes to his Class Unprepared" in John Smith\'s report card.' ) . '</p>

	<p>' . _help( 'The "All Courses" tab allows you to create Comments that apply to All Courses. Enter the Comment name and associate it to a "Code Scale" (created in the "Comment Codes" program) using the pull-down menu. The result will be a new column for the comment in the "Input Final Grades" program, under the "All Courses" tab. The column will display a pull-down menu with the comment codes of the scale associated.' ) . '</p>

	<p>' . _help( 'To create course-specific comments, first select a course by using the pull-downs at the top of the page. Then click on the tab with the (+) icon to create a Comment Category. Click "Save" and then a new tab with the category name will appear. There you will be able to add individual comments, one-by-one, and to associate them to a "Code Scale" (created in the "Comment Codes" program) using the pull-down menu. The result will be a new tab in the "Input Final Grades" program. The tab will be named after the comment category and will display one column for each of the comments under that category. The columns will display a pull-down menu with the comment codes of the scales associated.' ) . '</p>';

	$help['Grades/ReportCardCommentCodes.php'] = '<p>' . _help( '<i>Comment Codes</i> allows you to create comment scales that will generate pull-down menus of grading codes in the Input Final Grades program. Then, those codes will be displayed with their associated comment in the Report Card.' ) . '</p>

	<p>' . _help( 'To create a new Comment Scale, click on the tab with the (+) icon. Give a name to your comment scale, add an optional comment and then click "Save". A new tab will appear with the name of your new Comment Scale. Click on the tab of the comment scale to select it and then you will be able to add, one by one, the comment scale codes by filling in their respective "Title" (enter here the code), "Short Name" and "Comment" (entry / code legend that will appear on the report card).' ) . '</p>';

	$help['Grades/EditHistoryMarkingPeriods.php'] = '<p>' . _help( '<i>History Marking Periods</i> allows you to create marking periods for past years grades.' ) . '</p>

	<p>' . _help( 'Use this program first if you want to enter past years grades into RosarioSIS that were given before installing RosarioSIS, or if you want to enter grades for a student transferred to your school. Once the history marking period is added, you will be able to select it via the Historical Grades program.' ) . '</p>

	<p>' . _help( 'Please note that the "Grade Post Date" field determines the order of the history marking periods when entering grades or generating the Transcript and should therefore be entered properly. Also, each history marking period needs to be created only once.' ) . '</p>';

	$help['Grades/EditReportCardGrades.php'] = '<p>' . _help( '<i>Edit Student Grades</i> allows you to enter the past years grades of a student or the grades of a transferred student.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'Now, for the selected student, add the marking period (typically an history marking period you have created via the History Marking Periods program) selecting it in the "New Marking Period" pull-down menu. Then, enter the grade level for the selected student and click "Save".' ) . '</p>

	<p>' . _help( 'You can add the student grades via the "Grades" tab. Enter the "Course Name" and the grades associated, then click "Save". Please note that you can use a custom grade scale for the GPA calculation.' ) . '</p>

	<p>' . _help( 'RosarioSIS needs credits to calculate the GPA. Please check the "Credits" tab and adjust the credits for each course as needed.' ) . '</p>';

	$help['Grades/MassCreateAssignments.php'] = '<p>' . _help( '<i>Mass Create Assignments</i> allows you to create assignments for multiple courses at once. There are two tiers involved with assignments: assignment types and assignments.' ) . '</p>

	<p>' . _help( 'You will probably have assignment types called "Homework", "Tests", and perhaps "Quizzes". Assignment types are set for every period of a given course.' ) . '</p>

	<p>' . _help( 'To add an assignment type, click on the Add icon (+) in the assignment type column. Then, fill in the information in the fields above the list of assignment types. Select the Courses in the list at the bottom of the screen and click the "Create Assignment Type for Selected Courses" button.' ) . '</p>

	<p>' . _help( 'If you set the "Percent of Final Grade", teachers will see it only if they have checked the "Weight Assignment Categories" checkbox in their Gradebook Configuration.' ) . '</p>

	<p>' . _help( 'To add an assignment, click on the desired assignment type in the assignment type column. Then, fill in the information in the fields above the list of assignment types. Select the Course Periods in the list at the bottom of the screen and click the "Create Assignment for Selected Course Periods" button.' ) . '</p>

	<p>' . _help( 'If you enter 0 "Points", this will let you give Students Extra Credit.' ) . '</p>

	<p>' . _help( 'If you set the "Weight", teachers will see it only if they have checked the "Weight Assignments" checkbox in their Gradebook Configuration.' ) . '</p>

	<p>' . _help( 'If you check "Enable Assignment Submission", Students (or Parents) can submit the assignment (upload a file and/or leave a message). Submissions are opened from the assigned date and until the due date. If no due date has been set, submissions are open until the end of the quarter. Teachers can later consult the submissions in the "Grades" program.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'teacher' ):

	$help['Grades/InputFinalGrades.php'] = '<p>' . _help( '<i>Input Final Grades</i> allows you to enter quarter, semester or progress period grades for all your students in the current period. By default, this program will list the students in your first period class for the current quarter. You can alter the quarter by changing the marking period pull-down menu on the left frame. Also, you can select the current semester or progress period by changing the marking period pull-down menu at the top of the screen to the desired marking period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct marking period, you can enter student grades by selecting the earned grade for each student and entering comments as desired. Once all the grades and comments have been entered, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'If you are using the Gradebook, you can have RosarioSIS calculate each student\'s quarter grades by clicking on the "Use Gradebook Grades" link at the top of the list. Clicking this link will automatically save each student\'s grades and refresh the list.' ) . '</p>

	<p>' . _help( 'If the marking period you are in is a Progress Period, when clicking on the "Get Gradebook Grades" link, the gades taken in account will be limited to the one for which the Assignment Due Date is comprised within the Progress Period, or the ones with no Due Dates.' ) . '</p>';

	$help['Grades/Configuration.php'] = '<p>' . _help( '<i>Configuration</i> allows you to configure the gradebook.' ) . '</p>

	<p>' . _help( 'You can configure the gradebook to round scores up, down, or normally. Normal rounding would round 19.5 to 20 but 19.4 to 19.' ) . '</p>

	<p>' . _help( 'By default, grades are weighted by points. This means that the number of points in each assignment determines its weight, and the total of points in all assignments in a category determines how much the category counts.' ) . '</p>

	<p>' . _help( 'You can choose to weight grades by assignment categories. This will add a "Percent of Final Grade" field to categories.' ) . '</p>

	<p>' . _help( 'You can also choose to weight assignments. This lets you edit the weight of each assignment. In case you do not weight categories, the total of weights in all assignments in a category determines how much the category counts.' ) . '</p>

	<p>' . _help( 'You can also configure the score breakoff points for each letter grade. For example, if you set the score breakoff points for A+, A, and A- to 99, 91, and 90 respectively, a student with 99% to 100% would have an A+, another student with a 91% to 98% would have an A, and a student with a 90% would have an A-. The score breakoff point for F should probably be 0.' ) . '</p>

	<p>' . _help( 'Finally, you can also configure the final grading percentages of each semester. These values are used when averaging the quarter grades to calculate the semester grade.' ) . '</p>';

	$help['Grades/Assignments.php'] = '<p>' . _help( '<i>Assignments</i> allows you to setup your assignments. There are two tiers involved with assignments: assignment types and assignments.' ) . '</p>

	<p>' . _help( 'You will probably have assignment types called "Homework", "Tests", and perhaps "Quizzes". Assignment types are set for every period on which you teach any given course. So, if you teach Algebra on 1st and 3rd period, you will have to add assignment types to only one of these periods.' ) . '</p>

	<p>' . _help( 'To add an assignment type or an assignment, click on the Add icon (+) in the column corresponding to what you want to add. Then, fill in the information in the fields above the list of assignments / types and click the "Save" button.' ) . '</p>

	<p>' . _help( 'If you enter 0 "Points", this will let you give Students Extra Credit.' ) . '</p>

	<p>' . _help( 'If you check "Apply to all Periods for this Course", the assignment will be added for each period for which you teach a specific course, in the same way assignment types are added.' ) . '</p>

	<p>' . _help( 'If you check "Enable Assignment Submission", Students (or Parents) can submit the assignment (upload a file and/or leave a message). Submissions are opened from the assigned date and until the due date. If no due date has been set, submissions are open until the end of the quarter. You can later consult the submissions in the "Grades" program.' ) . '</p>

	<p>' . _help( 'To change an assignment or type, click on the assignment or type you want to modify and click on the value you want to change in the grey area above the assignments / types lists. Then, change the value and click the "Save" button.' ) . '</p>

	<p>' . _help( 'Finally, to delete an item, select it by clicking on its title on the list and click the "Delete" button at the top of the screen. You will be asked to confirm the deletion.' ) . '</p>';

	$help['Grades/Grades.php'] = '<p>' . _help( '<i>Grades</i> allows you to input assignment grades for all your students in the current period. By default, this program will list the students in your first period class. You can alter the current period by changing the period pull-down menu in the left frame to the desired period.' ) . '</p>

	<p>' . _help( 'Once you have chosen the correct period, you will see the total points and cumulative grade for each student in your class. You can view the grades for an assignment by selecting the assignment from the assignment pull-down menu at the top of the screen. From here, you can input a new grade by entering the points earned into the blank field next to the student\'s name or you can modify an existing grade by clicking on the points earned and changing the value. After changing the grades, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'You can also view and change all the grades for a single student by clicking on the student\'s name in the list. Input grades in the same way that you did with the multiple student list.' ) . '</p>';

	$help['Grades/ProgressReports.php'] = '<p>' . _help( '<i>Progress Reports</i> lets you generate Progress Reports of the students for the current Marking Period.' ) . '</p>

	<p>' . _help( 'After having created assignments and entered gradebook grades, a progress report can be generated.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list.' ) . '</p>

	<p>' . _help( 'Check desired options to appear on the report. Checking "Assigned Date" will display the Assignment\'s Assigned Date . Checking "Due Date" will display the Assignment\'s Due Date. Checking on "Exclude Ungraded Assignments Not Due" will remove assignments which are ungraded and not due. Checking "Group by Assignment Category" will group assignments by Category and will display total points and weight for each Category.' ) . '</p>

	<p>' . _help( 'The progress reports will be automatically downloaded to your computer in the printable PDF format when you click the "Create Progress Reports for Selected Students" button.' ) . '</p>';

// Parent & Student.
else:

	$help['Grades/ReportCards.php'] = '<p>' . _help( '<i>Report Cards</i> is a utility that allows you to print report cards for your child.' ) . '</p>

	<p>' . _help( 'Before printing the report cards, you must select which marking periods to display on the report card by checking desired marking period checkboxes.' ) . '</p>

	<p>' . _help( 'The report cards will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.' ) . '</p>';

	$help['Grades/ProgressReports.php'] = '<p>' . _help( '<i>Progress Reports</i> lets you generate Progress Reports of the students for the current Marking Period.' ) . '</p>

	<p>' . _help( 'When teachers have created assignments and entered gradebook grades, a progress report can be generated.' ) . '</p>

	<p>' . _help( 'Check desired options to appear on the report. Checking "Assigned Date" will display the Assignment\'s Assigned Date . Checking "Due Date" will display the Assignment\'s Due Date. Checking on "Exclude Ungraded Assignments Not Due" will remove assignments which are ungraded and not due. Checking "Group by Assignment Category" will group assignments by Category and will display total points and weight for each Category.' ) . '</p>

	<p>' . _help( 'The progress reports will be automatically downloaded to your computer in the printable PDF format when you click the "Create Progress Reports for Selected Students" button.' ) . '</p>';

	$help['Grades/Transcripts.php'] = '<p>' . _help( '<i>Transcripts</i> is a utility that allows you to print transcripts for your child.' ) . '</p>

	<p>' . _help( 'Before printing the transcripts, you must select which marking periods to display on the transcript by checking desired marking period checkboxes.' ) . '</p>

	<p>' . _help( 'The transcripts will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.' ) . '</p>';

	$help['Grades/StudentAssignments.php'] = '<p>' . _help( '<i>Assignments</i> allows you to view your child\'s assignments.' ) . '</p>

	<p>' . _help( 'In the detailed view of an assignment, you will be able to submit an assignment if allowed by the teacher. To this effect, you will be given the possibility to upload a file and/or leave a message.' ) . '</p>

	<p>' . _help( 'Assignment submissions are opened until the due date. If no due date has been set, submissions are open until the end of the quarter.' ) . '</p>

	<p>' . _help( 'You can change the marking period using the dropdown list available in the left frame.' ) . '</p>';

	$help['Grades/StudentGrades.php'] = '<p>' . _help( '<i>Gradebook Grades</i> allows you to view the grades earned by your child.' ) . '</p>

	<p>' . _help( 'You can change the marking period using the dropdown list available in the left frame.' ) . '</p>';

	$help['Grades/GPARankList.php'] = '<p>' . _help( '<i>GPA / Class Rank</i> is a report that shows the unweighted GPA, weighted GPA, and class rank of your child.' ) . '</p>';

endif;

// ATTENDANCE ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Attendance/Administration.php'] = '<p>' . _help( '<i>Administration</i> allows you to view and change the student attendance records for any given day.' ) . '</p>

	<p>' . _help( 'To change the student\'s attendance status for any period, click on the current value and select the short name of the attendance code you would like to assign that student. After making all the desired modifications, click the "Update" button at the top of the screen. You can also limit the list of students based upon what attendance codes the students have been assigned on the current day. For instance, by default, all students with any attendance codes with a state value of "Absent" are listed. This is shown by the pull-down menu on the upper right-hand corner of the screen that displays "Abs." This menu can be changed to the short name of any attendance code, and only students who received that code during the current day will be displayed. This menu can even be changed to "All" which will list all students for whom attendance has been taken. You can add an attendance code by clicking the add icon (+) next to the attendance code pull-down menu. If you select a second attendance code, the program will list students who received either code during the day.' ) . '</p>

	<p>' . _help( 'You can alter the date displayed by clicking on the date on the upper left-hand side of the screen and changing it to the desired date.' ) . '</p>

	<p>' . _help( 'After making changes to the attendance codes displayed or the current date, click the "Update" button to refresh the screen with the new parameters.' ) . '</p>

	<p>' . _help( 'You can also view the attendance code assigned to the student by the teacher as well as view and enter a comment for each period by clicking on the student\'s name.' ) . '</p>

	<p>' . _help( 'Clicking on "Current Student" on the top of the screen will display the day\'s attendance records for the current student displayed in the left frame.' ) . '</p>';

	$help['Attendance/AddAbsences.php'] = '<p>' . _help( '<i>Add Absences</i> allows you to add an absence to a group of students in one action.' ) . '</p>

	<p>' . _help( 'First, search for students. Notice that you can search for students who are enrolled in a specific course or are in a certain activity. This can be useful since you can add an absence record for each period to all of Mrs. Smith\'s first period students or the football team who will be on an all day field trip.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. You can also specify the periods to mark the selected students, the absence code, the absence reason, and the date in the yellow box above the student list. After you have selected each desired student from this list, all the desired periods, the absence code, absence reason, and absence date, click the "Save" button at the top of the screen.' ) . '</p>';

	$help['Attendance/Percent.php'] = '<p>' . _help( '<i>Average Daily Attendance</i> is a report that shows the number of students, days possible, the number of student days present, the number of student days absent, the Average Daily Attendance, the average number of students in attendance per day, and the average number of students absent per day for any date range at your school. These numbers are broken down by grade.' ) . '</p>

	<p>' . _help( 'You can alter the date range displayed by changing the date pull-down menus at the top of the screen and clicking the "Go" button. You can also limit the numbers by searching by gender or any of the customizable data fields by clicking on the "Advanced" link.' ) . '</p>

	<p>' . _help( '<i>Average Attendance by Day</i> is a report that shows the number of students, days possible, the number of student days present, the number of student days absent, and the Average Daily Attendance per day for any date range at your school. These numbers are broken down by grade.' ) . '</p>';

	$help['Attendance/DailySummary.php'] = '<p>' . _help( '<i>Attendance Chart</i> is a report that shows the daily attendance status of any number of students for every date during any timeframe.' ) . '</p>

	<p>' . _help( 'After searching for students, you can alter the date range by changing the date pull-down menus at the top of the screen and clicking the "Go" button. The list shows each student\'s daily attendance value for each day with color codes. A red box signifies that the student was absent all day, a yellow box signifies that a student was absent half-day, and a green box signifies that a student was present all day long.' ) . '</p>

	<p>' . _help( 'You can see the attendance records for each period for any student by clicking on a student\'s name from the list. Here, the absence code is displayed in the color-coded box.' ) . '</p>

	<p>' . _help( '<i>Absence Summary</i> is a report that shows the days for which a student has an absence. Select the <i>Absence Summary</i> from the pull-down at the top of the screen.' ) . '</p>';

	$help['Attendance/TeacherCompletion.php'] = '<p>' . _help( '<i>Teacher Completion</i> is a report that shows which teachers have not entered attendance for any given day.' ) . '</p>

	<p>' . _help( 'The red checks indicate that a teacher has failed to enter the current day\'s attendance for that period.' ) . '</p>

	<p>' . _help( 'You can select the current date from the pull-down menu at the top of the screen. You can also show only one period by choosing that period from the period pull-down menu at the top of the screen. After choosing a date or period, the list will be automtically refreshed with the new parameters.' ) . '</p>';

	$help['Attendance/FixDailyAttendance.php'] = '<p>' . _help( '<i>Recalculate Daily Attendance</i> is a utility to recalculate the daily attendance for a specific time-frame.' ) . '</p>

	<p>' . _help( 'Select the time-frame and click "OK". All attendance will be then calculated for whole day/half day. Please select a shorter time-frame if the system freezes. Using this utility can avoid problems related to missing course periods attendance.' ) . '</p>';

	$help['Attendance/DuplicateAttendance.php'] = '<p>' . _help( '<i>Delete Duplicate Attendance</i> is a utility to spot and delete any attendance that was taken for a student AFTER their enrollment drop date.' ) . '</p>

	<p>' . _help( 'In case a student is retro-actively dropped from a course, but attendance has already been taken by teachers or administrators for dates after the drop date.' ) . '</p>';

	$help['Attendance/AttendanceCodes.php'] = '<p>' . _help( '<i>Attendance Codes</i> allows you to setup your school\'s attendance codes. Attendance codes are used in the teacher\'s "Take Attendance" program (as well as most of the Attendance reports) and specify whether or not the student was present during the period, and if he wasn\'t, the reason.' ) . '</p>

	<p>' . _help( 'To add an attendance code, fill in the attendance code\'s title, short name, type, and state code. Select whether or not the code should be a teacher\'s default from the empty fields at the bottom of the attendance codes list and click the "Save" button. Generally, the attendance code called "Present" will be marked as the teacher\'s default. If the attendance code is marked as being type "Teacher," a teacher will be able to select that attendance code from their "Take Attendance" program. Administrators will be able to assign all codes to a student.' ) . '</p>

	<p>' . _help( 'To modify an attendance code, click on any of the attendance code\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete an attendance code, click the delete icon (-) next to the attendance code you want to delete. You will be asked to confirm the deletion.' ) . '</p>

	<p>' . _help( 'Attendance Categories' ) . '</p>

	<p>' . _help( 'RosarioSIS allows you to add custom categories that will take the form of new "Tabs" in the <i>Scheduling > Take Attendance</i> program. To create a new category or "tab", just click on the "+" icon next to the existing Categories.' ) . '</p>

	<p>' . _help( 'New Category' ) . '</p>

	<p>' . _help( 'You can now type in the name of the new Category in the "Title" field(s) provided. Add a sort order (order in which the tabs will appear in the <i>Take Attendance</i> program). Click "Save" when you have finished.' ) . '</p>

	<p>' . _help( 'To activate the Category for a specific course period, check it when editing the Course Period from the <i>Scheduling > Courses</i> program.' ) . '</p>

	<p>' . _help( 'Lunch Category' ) . '</p>

	<p>' . _help( 'The Lunch Category comes in handy to take Lunch counts in the morning so the Food Service staff knows how many meals to prepare.' ) . '</p>';

	$help['Custom/AttendanceSummary.php'] = '<p>' . _help( '<i>Attendance Summary</i> is a report that shows a day by day record of each student\'s attendance over the school year in one table.' ) . '</p>

	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen. You can search for students who have requested a specific course by clicking on the "Choose" link next to the search option "Course" and choosing a course from the popup window that appears.' ) . '</p>

	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. After you have selected each desired student from this list, click "Create Attendance Report for Select Students" to generate the report in a PDF format.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'teacher' ):

	$help['Attendance/TakeAttendance.php'] = '<p>' . _help( '<i>Take Attendance</i> allows you to enter period attendance for all your students in the current period. By default, this program will list the students in your first period class. You can alter the current period by changing the period pull-down menu in the left frame to the desired period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct period, you can enter attendance by selecting the attendance code corresponding to each student. Once you have entered attendance for all your students, click the "Save" button at the top of the screen.' ) . '</p>';

	$help['Attendance/DailySummary.php'] = '<p>' . _help( '<i>Daily Summary</i> is a report that shows the daily attendance status of any number of students for every date during any timeframe.' ) . '</p>

	<p>' . _help( 'After searching for students, you can alter the date range by changing the date pull-down menus at the top of the screen and clicking the "Go" button. The list shows each student\'s daily attendance value for each day with color codes. A red box signifies that the student was absent all day, a yellow box signifies that a student was absent half-day, and a green box signifies that a student was present all day long.' ) . '</p>

	<p>' . _help( 'You can see the attendance records for each period for any student by clicking on a student\'s name from the list. Here, the absence code is displayed in the color-coded box.' ) . '</p>

	<p>' . _help( '<i>Absence Summary</i> is a report that shows the days for which a student has an absence. Select the <i>Absence Summary</i> from the pull-down at the top of the screen.' ) . '</p>';

// Parent & Student.
else:

	$help['Attendance/DailySummary.php'] = '<p>' . _help( '<i>Daily Summary</i> is a report that shows the daily attendance status of your child during any timeframe.' ) . '</p>

	<p>' . _help( 'You can alter the date range by changing the date pull-down menus at the top of the screen and clicking the "Go" button. The list shows your child\'s daily attendance value for each period of each day with color codes. A red box signifies that the student was absent that period, and a green box indicates that the student was either present or tardy that period. The absence code is displayed in the box.' ) . '</p>

	<p>' . _help( '<i>Absence Summary</i> is a report that shows the days for which a student has an absence. Select the <i>Absence Summary</i> from the pull-down at the top of the screen.' ) . '</p>';

endif;

// ELIGIBILITY ---.
if ( User( 'PROFILE' ) === 'admin' ):

	$help['Eligibility/Student.php'] = '<p>' . _help( '<i>Student Screen</i> is a display of the student\'s activities and the current timeframe\'s eligibility grades. The program also allows you to add and delete activities to the student.' ) . '</p>

	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen. You can search for students who are enrolled in a specific course by clicking on the "Choose" link next to the "Course" search option and choosing a course from the popup window that appears. You can also search for students in a certain activity and for students who are currently ineligible.' ) . '</p>

	<p>' . _help( 'To add an activity to the student, select the desired activity from the activity pull-down next to the add icon (+) and click the "Add" button.' ) . '</p>

	<p>' . _help( 'To drop an activity, click on the delete icon (-) next to the activity you want to drop.' ) . '</p>

	<p>' . _help( 'You can specify the desired eligibility timeframe by choosing the desired timeframe from the pull-down menu at the top of the screen. These timeframes are setup in the "Entry Times" program.' ) . '</p>';

	$help['Eligibility/AddActivity.php'] = '<p>' . _help( '<i>Add Activity</i> allows you to add an activity to a group of students in one action.' ) . '</p>

	<p>' . _help( 'First, search for students. Notice that you can search for students who are in a certain activity or course. From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list. Next, select an activity to be added from the pull-down menu at the top of the screen. After you have selected each desired student from this list and the desired activity, click the "Add Activity to Selected Students" button at the top of the screen.' ) . '</p>';

	$help['Eligibility/Activities.php'] = '<p>' . _help( '<i>Activities</i> allows you to setup your school\'s activities.' ) . '</p>

	<p>' . _help( 'To add an activity, fill in the activity\'s title, beginning date, and ending date in the empty fields at the bottom of the activities list and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To modify an activity, click on any of the activity\'s information, change the value, and click the "Save" button.' ) . '</p>

	<p>' . _help( 'To delete an activity, click the delete icon (-) next to the activity you want to delete. You will be asked to confirm the deletion.' ) . '</p>';

	$help['Eligibility/EntryTimes.php'] = '<p>' . _help( '<i>Entry Times</i> allows you to setup the weekly timeframe in which teachers can enter eligibility. Teachers must enter eligibility every week within this range. Besides the teacher\'s "Enter Eligibility" program, this timeframe is used in most eligibility reports' ) . '</p>

	<p>' . _help( 'To change the timeframe, simply change the upper and lower bounds of the timeframe and click the "Save" button.' ) . '</p>';

	$help['Eligibility/StudentList.php'] = '<p>' . _help( '<i>Student List</i> is a report that shows every course and eligibility grade assigned to any number of students.' ) . '</p>

	<p>' . _help( 'After searching for students, you can specify the eligibility timeframe you want to view. These timeframes are setup in the "Entry Times" program.' ) . '</p>';

	$help['Eligibility/TeacherCompletion.php'] = '<p>' . _help( '<i>Teacher Completion</i> is a report that shows which teachers have not entered eligibility for any given date range. The date range is set in the "Entry Times" program.' ) . '</p>

	<p>' . _help( 'The red checks indicate that a teacher has failed to enter the current date range\'s eligibility for that period.' ) . '</p>

	<p>' . _help( 'You can select the current date range from the pull-down menu at the top of the screen. You can also show only one period by choosing that period from the period pull-down menu at the top of the screen. After choosing a date range or period, click the "Go" button to refresh the list with the new parameters.' ) . '</p>';

elseif ( User( 'PROFILE' ) === 'teacher' ):

	$help['Eligibility/EnterEligibility.php'] = '<p>' . _help( '<i>Enter Eligibility</i> allows you to enter eligibility grades for all your students in the current period. By default, this program will list the students in your first period class. You can alter the current period by changing the period pull-down menu in the left frame to the desired period.' ) . '</p>

	<p>' . _help( 'Once you are in the correct period, you can enter eligibility grades by selecting the eligibility code corresponding to each student. Once you have entered eligibility for all your students, click the "Save" button at the top of the screen.' ) . '</p>

	<p>' . _help( 'If you are using the Gradebook, you can have RosarioSIS calculate each student\'s eligibility grades by clicking on the "Use Gradebook Grades" link at the top of the list. Clicking this link will automatically save each student\'s eligibility grades and refresh the list.' ) . '</p>

	<p>' . _help( 'You must enter eligibility each week during the timeframe specified by your school\'s administration.' ) . '</p>';

// Parent & Student.
else:

	$help['Eligibility/Student.php'] = '<p>' . _help( '<i>Student Screen</i> is a display of your child\'s activities and the current timeframe\'s eligibility grades.' ) . '</p>

	<p>' . _help( 'You can specify the eligibility timeframe you want to view by choosing the desired timeframe from the pull-down menu at the top of the screen. Eligibility is entered once per week.' ) . '</p>';

	$help['Eligibility/StudentList.php'] = '<p>' . _help( '<i>Student List</i> is a report that shows every course and eligibility grade assigned to your child.' ) . '</p>

	<p>' . _help( 'You can specify the eligibility timeframe you want to view by choosing the timeframe from the pull-down menu at the top of the screen and clicking the "Go" button. Eligibility is entered once per week.' ) . '</p>';

endif;
