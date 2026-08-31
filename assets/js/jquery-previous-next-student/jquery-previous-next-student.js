/**
 * jQuery Previous Next Student
 * Add Previous/Next Student buttons to quickly navigate the list
 * without needing the go back to the list
 *
 * Compatible with Instant List Search and Sorting JS
 *
 * Drawback: buttons will disappear if you reload the page (via F5 key or browser button) or when you open a new tab
 *
 * @package Previous Next Student JS
 *
 * @since 13.0
 */

var previousNextStudent = {};

/**
 * Previous Next Student List
 *
 * @use getURLParam()
 *
 * 0. Check if we have a student list
 * 1. Save the student IDs in `previousNextStudentIDs`
 * 2. Otherwise, if we have search form, reset student IDs
 *
 * @return {boolean} True if student list
 */
previousNextStudent.list = function() {
	if ( typeof previousNextStudent.list.ids == 'undefined' ) {
		// Save IDs in static variable
		previousNextStudent.list.ids = [];
	}

	var extractIdsFromList = function($list) {
		// Get rows inside the list table body.
		// List may contain nested arrays, so we use children() and not find().
		var $rows = $list.children('tbody').children('tr');

		// Reset student IDs.
		previousNextStudent.list.ids = [];

		if (! $rows.length) {
			return;
		}

		$rows.each(function() {
			if (this.style.display === 'none') {
				// Hidden row, typically when list was searched and row doesn't match
				return;
			}

			var cell = this.children[0],
				link = cell && cell.firstChild && cell.firstChild.href;

			if (! link) {
				return;
			}

			var studentId = getURLParam(link, 'student_id'),
				studentName = cell.textContent;

			if (! studentId) {
				return;
			}

			previousNextStudent.list.ids.push({id: studentId, name: studentName});
		});
	};

	// 0. Check if we have a student list
	var $list = $('.list-outer.students .list').first();

	if ($list.length) {
		// 1. Save the student IDs in `previousNextStudentIDs`
		extractIdsFromList( $list );

		return true;
	}

	if ($('#search').length) {
		// 2. Otherwise, if we have search form, reset student IDs
		previousNextStudent.list.ids = [];
	}

	return false;
}


/**
 * Previous Next Student Buttons
 *
 * 0. Get current student ID
 * 1. Get previous next student in list
 * 2. Check if program has header2
 * 3. Get buttons HTML
 * 4. Prepend buttons HTML to header2
 *
 * @return {boolean} True if student buttons
 */
previousNextStudent.buttons = function() {
	var getPreviousNextStudent = function(current) {
		var keys = Object.keys(previousNextStudent.list.ids),
			loc = previousNextStudent.list.ids.map(function(o) { return o.id; }).indexOf(current);

		if (loc < 0) {
			return false;
		}

		return {
			previous: loc > 0 && previousNextStudent.list.ids[keys[loc-1]],
			next: loc < (keys.length - 1) && previousNextStudent.list.ids[keys[loc+1]]
		};
	};

	var getButtonsHtml = function(current, prevNextStudent) {
		var html = '';

		if (prevNextStudent.previous) {
			html += getButtonHtml(current, prevNextStudent.previous, 'back') + '&nbsp;';
		}

		if (prevNextStudent.next) {
			html += getButtonHtml(current, prevNextStudent.next, 'next');
		}

		return html + ' ';
	};

	var getButtonHtml = function(current, student, button) {
		var url = window.location.href.replace('&student_id=' + current, '&student_id=' + student.id),
			$link = $('<a/>').attr({href: url, title: student.name}).html(
				'<img class="button bigger" src="assets/themes/' + previousNextStudent.buttons.theme + '/btn/' + button + '.png" />'
			);

		return $link[0].outerHTML;
	};

	// 0. Get current student ID
	var current = getURLParam(window.location.search, 'student_id');

	if (! current) {
		return false;
	}

	// 1. Get previous next student in list
	var prevNextStudent = getPreviousNextStudent(current);

	if (! prevNextStudent) {
		return false;
	}

	// 2. Check if program has header2
	var $header2 = $('#body .header2').first();

	if (! $header2.length) {
		return false;
	}

	if (typeof previousNextStudent.buttons.theme === 'undefined') {
		// Static variable, cache theme.
		previousNextStudent.buttons.theme = $('link[rel="stylesheet"][href^="assets/themes/"]').first().attr('href').split('/')[2];
	}

	// 3. Get buttons HTML
	var buttonsHtml = getButtonsHtml(current, prevNextStudent);

	// 4. Prepend buttons HTML to header2
	$header2.prepend(buttonsHtml);

	return true;
}

previousNextStudent.ready = function() {
	/**
	 * Compatibility with Instant List Search and Sorting JS
	 *
	 * Call the previousNextStudent.list() function on student `.list` search & sort events
	 */
	$(document).on('search sort', '.list-outer.students .list', previousNextStudent.list);
};

$(previousNextStudent.ready);
