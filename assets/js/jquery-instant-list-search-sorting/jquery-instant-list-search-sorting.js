/**
 * jQuery Instant List Search and Sorting
 *
 * @package Instant List Search and Sorting JS
 * @since 13.0
 */

var instantList = {};

/**
 * jQuery Instant List Search
 *
 * 0. On search input keyup (after 400ms)
 * 1. Remove repeated thead
 * 2. Show all rows & enable inputs
 * 3. Search rows (inner text, case insensitive)
 * 4. Hide not matching rows (except for "Add" row)
 * 5. Repeat thead
 * 6. Update export list link href, th sort link href
 * 7. Update history URL, bottom back button URL
 * 8. Trigger the search event on the `.list` element
 *
 * @link https://stackoverflow.com/questions/9127498/how-to-perform-a-real-time-search-and-filter-on-a-html-table
 */
instantList.search = function() {
	/**
	 * Returns a function, that, as long as it continues to be invoked, will not be triggered.
	 * The function will be called after it stops being called for N milliseconds.
	 *
	 * @link https://davidwalsh.name/javascript-debounce-function
	 */
	var debounce = function(func, wait) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				func.apply(context, args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	};

	$('.list').each(function() {
		var $list = $(this),
			$searchInput = $list.parent('.list-wrapper').prev('.list-nav').find('#LO_search');

		if (! $searchInput.length) {
			return;
		}

		$searchInput.on('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
			}
		});

		// Get rows inside the list table body.
		// List may contain nested arrays, so we use children() and not find().
		var $rows = $list.children('tbody').children('tr');

		if (! $rows.length) {
			return;
		}

		var valTmp = $searchInput.val();

		$searchInput.on('input', debounce(function() {
			var val = this.value.trim(),
				escapedRegExp = val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), // $& means the whole matched string
				// FJ fix \b word boundary not working with "é"...
				// @link https://stackoverflow.com/questions/2449779/why-cant-i-use-accented-characters-next-to-a-word-boundary
				// So instead of searching beginning of word, search inside words, just like LO_search...
				pattern = '^(?=.*' + escapedRegExp.split(/\s+/).join('.*)(?=.*') + ').*$',
				reg = RegExp(pattern, 'i');

			if (val === valTmp) {
				// Prevent searching again when browser tab gains focus (keyup event fired)
				return;
			}

			valTmp = val;

			$list.find('tr.thead-repeat').remove();

			$rows.show().find('input,select,textarea').attr('disabled', false);

			if (val) {
				var addRowInd = $list.find('tr.list-add-row').index();

				$rows.filter(function(i) {
					if (addRowInd == i) {
						return false;
					}

					if (val.substring(0, 1) === '"' && val.substring(val.length - 1) === '"') {
						// If "expression", remove double quotes & do a simple indexOf check
						return this.innerText.toLowerCase().indexOf(
							val.substring(1, val.length - 1).toLowerCase()
						) === -1;
					}

					return !reg.test(this.innerText.replace(/\s+/g, ' '));
				}).hide().find('input,select,textarea').attr('disabled', true);
			}

			repeatListTHead($list);

			var $save = $searchInput.parents('.list-nav').find('.list-save'),
				loSearch = '&LO_search=' + encodeURIComponent(val);

			if ($save.length) {
				// Update Export list link href: &LO_search
				$save[0].href = instantList.setURLParam($save[0].href, 'LO_search', val);
			}

			$list.find('th a').each(function() {
				// Update th sort link href: &LO_search
				this.href = instantList.setURLParam(this.href, 'LO_search', val);
			});

			var url = document.URL,
				listId = $list.data('list-id');

			url = instantList.setURLParam(url, 'LO_search', val);

			if (listId) {
				url = instantList.setURLParam(url, 'LO_id', listId);
			}

			// Update history URL
			history.replaceState({}, '', url);

			if ($('#BottomButtonBack').length
				&& $('#BottomButtonBack').attr('href').indexOf(getURLParam(url, 'modname')) > 0) {
				// Update bottom back button URL
				$('#BottomButtonBack').attr('href', url);
			}

			// Trigger the search event on the `.list` element
			$list.trigger('search');
		}, 400));
	});
}

/**
 * jQuery Instant List Sorting
 *
 * 0. On th link click
 * 1. Remove repeated thead
 * 2. Sort rows (inner text or HTML comment, numeric or alphabetic, ascending or desending)
 * 3. Do not sort "Add" row so it stays at the same place
 * 4. Repeat thead
 * 5. Update th link href: &LO_dir, Export list link href: &LO_dir, &LO_sort
 * 6. Update history URL, bottom back button URL
 * 7. Trigger the sort event on the `.list` element
 *
 * @link https://stackoverflow.com/questions/3160277/jquery-table-sort
 */
instantList.sorting = function() {
	$('.list').each(function() {
		var $list = $(this);

		$list.find('th a').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			$list.find('tr.thead-repeat').remove();

			var $addRow = $list.find('tr.list-add-row'),
				addRowInd = $addRow.index();

			if (addRowInd >= 0) {
				$addRow.remove();
			}

			var th = e.target.parentNode,
				rows = $list.children('tbody').children('tr').toArray().sort(
					comparer($(th).index())
				);

			th.asc = !th.asc;

			if (!th.asc) {
				rows = rows.reverse();
			}

			$list.append(rows);

			if (!addRowInd) {
				$list.prepend($addRow);
			} else if (addRowInd > 0) {
				$list.append($addRow);
			}

			repeatListTHead($list);

			// Update history URL
			history.replaceState({}, '', e.target.href);

			if ($('#BottomButtonBack').length
				&& $('#BottomButtonBack').attr('href').indexOf(getURLParam(e.target.href, 'modname')) > 0) {
				// Update bottom back button URL
				$('#BottomButtonBack').attr('href', e.target.href);
			}

			var dir = th.asc ? 1 : -1;

			// Update th link href: &LO_dir
			e.target.href = instantList.setURLParam(e.target.href, 'LO_dir', (dir * -1));

			var $save = $list.parents('.list-outer').find('.list-save');

			if ($save.length) {
				// Update Export list link href: &LO_dir, &LO_sort
				$save[0].href = instantList.setURLParam($save[0].href, 'LO_dir', dir);

				$save[0].href = instantList.setURLParam(
					$save[0].href,
					'LO_sort',
					getURLParam(e.target.href, 'LO_sort')
				);
			}

			// Trigger the sort event on the `.list` element
			$list.trigger('sort');
		});
	});

	var comparer = function(i) {
		return function(a, b) {
			var valA = getCellVal(a, i),
				valB = getCellVal(b, i);

			return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB);
		}
	};

	var getCellVal = function(row, i) {
		var cell = row.children[i];

		if (cell.firstChild
			&& cell.firstChild.nodeName === '#comment') {
			// Extract sort HTML comment.
			return cell.firstChild.data.trim();
		}

		if (!cell.innerText) {
			return cell.innerHTML.trim();
		}

		return cell.innerText.trim();
	};
}

/**
 * Set URL param
 *
 * @param {string} url   URL.
 * @param {string} name  Param name.
 * @param {string} value Param value.
 *
 * @return {string} Updated URL.
 */
instantList.setURLParam = function(url, name, value) {
	var url = new URL(url);
	url.searchParams.set(name, value);
	return url;
}
