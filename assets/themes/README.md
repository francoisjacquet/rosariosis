Place here the Themes you want to add to RosarioSIS.

Select the default theme in _School Setup > School Configuration_

Or select your preferred theme in _Users > My Preferences > Display Options_

Every theme must have the following files:

- _stylesheet.css_
- _stylesheet_wkhtmltopdf.css_
- _logo.png_
- _spinning.gif_
- _btn/*.png_ (all the button images)

Note:
The _stylesheet_wkhtmltopdf.css_ file is the CSS file used by wkhtmltopdf.
It is meant to be the copy of the _stylesheet.css_ file WITHOUT media queries.

Note 2:
Replace the modules icons in CSS (example for School Setup & Student icons):

```css

/* [place this snippet before media queries] */
/* Hide default Module icons */
.menu-top img{
	width:32px;
	padding:32px 0 0 0;
	height:0;
	overflow:hidden;
	background-size:100%
}

/* Replace Module icons */
.menu-module.school-setup img{
	background-image:url("btn/back.png")
}
.menu-module.students img{
	background-image:url("btn/bus_button.png")
}

```