# THEMES

Place here the Themes you want to add to RosarioSIS.

## Activate

Select the default theme in _School > Configuration_

Or select your preferred theme in _Users > My Preferences > Display Options_

## Files

Every theme must have the following files:

- `stylesheet.css`
- `stylesheet_wkhtmltopdf.css`
- `logo.png`
- `spinning.gif`
- `btn/*.png` (all the button images)

Note:
The `stylesheet_wkhtmltopdf.css` file is the CSS file used by wkhtmltopdf.
It is meant to be the copy of the `stylesheet.css` file WITHOUT media queries.

Optional files:

- `modules/*.png` (all the modules icons)
- `scripts.js`

## Module icons

Custom module icons CSS: from your theme's `modules/` folder (example for Accounting & School icons):

```css
.module-icon.Accounting {
	background-image: url("modules/Accounting.png");
}

.module-icon.School_Setup {
	background-image: url("modules/School_Setup.png");
}
```

Or use the default WPadmin theme ones:

```css
.module-icon.Accounting {
	background-image: url("../WPadmin/modules/Accounting.png");
}

.module-icon.School_Setup {
	background-image: url("../WPadmin/modules/School_Setup.png");
}
```
