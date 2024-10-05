<?php
/**
 * Lists / Listings
 *
 * @package RosarioSIS
 * @subpackage functions
 *
 * @since 4.0 Add List Before and After action hooks
 * @since 12.0 Remove Relevance column, do not sort results
 */

function ListOutput( $result, $column_names, $singular = '.', $plural = '.', $link = [], $group = [], $options = [] )
{
	global $_ROSARIO;

	static $list_id = -1;

	// @since 11.3 Fix list sort, search, page, save when multiple lists on same page
	$list_id++;

	$default_options = [
		'save' => '1',
		'search' => true,
		'count' => true,
		'sort' => empty( $group ),
		'header_color' => Preferences( 'HEADER' ),
		// @since 11.3 To disable responsive list layout, add `&LO_disable_responsive=Y` to the URL
		'responsive' => empty( $_REQUEST['LO_disable_responsive'] ),
		// @since 11.7 Allow display of $link['add'] (or remove) on PDF or if not allowed to edit
		'add' => ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) ),
		// @since 10.9 Add pagination option (defaults to false)
		// Deactivated by default as yields strange results when multiple lists on same page.
		'pagination' => false,
		// @since 11.6 Add vertically align list data option (defaults to false)
		// Use when some columns are text & others input & should be displayed on 1 line only.
		'valign-middle' => false,
	];

	$options = empty( $options ) ?
		$default_options :
		array_replace_recursive( $default_options, $options );

	$LO_id = issetVal( $_REQUEST['LO_id'], '' );

	if ( (int) $LO_id !== $list_id )
	{
		// Request options are not for the current list, set to default.
		$LO_page = $LO_sort = $LO_dir = $LO_search = $LO_save = '';
	}
	else
	{
		// Request options are for the current list, set them.
		$LO_page = issetVal( $_REQUEST['LO_page'], '' );

		$LO_sort = issetVal( $_REQUEST['LO_sort'], '' );

		$LO_dir = issetVal( $_REQUEST['LO_dir'], '' );

		$LO_search = issetVal( $_REQUEST['LO_search'], '' );

		$LO_save = issetVal( $_REQUEST['LO_save'], '' );
	}

	if ( ! $options['add'] && ! empty( $link ) )
	{
		unset( $link['add'] );
		unset( $link['remove'] );
	}

	$result_count = $display_count = count( (array) $result );

	$num_displayed = 1000;

	if ( ! empty( $_ROSARIO['SQLLimitForList']['limit'] ) )
	{
		/**
		 * Limit SQL result for List
		 * Improve performance for lists > 1000 results
		 *
		 * @see SQLLimitForList() function
		 *
		 * @since 11.7
		 */
		$num_displayed = $_ROSARIO['SQLLimitForList']['limit'];
		$sql_count = $_ROSARIO['SQLLimitForList']['sql_count'];

		// Reset for multiple lists on same page, so we can call SQLLimitForList() again.
		unset( $_ROSARIO['SQLLimitForList'] );

		if ( ( $num_displayed == $result_count
				|| $LO_page > 1 && $result_count < $num_displayed )
			&& $sql_count )
		{
			// Limit reached, get total count.
			$result_count = (int) DBGetOne( $sql_count );

			if ( $result_count < $num_displayed )
			{
				/**
				 * Fix result count < results (wrong SQL query to COUNT total results)
				 * There's a bug, result count shouldn't be < results.
				 * Can happen for results made of multiple GetStuList() / GetStaffList() calls.
				 * Please make sure you sum SQL queries to COUNT total results before ListOutput().
				 * @see example in Student_Billing/includes/DailyTransactions.php
				 *
				 * @since 11.7.4
				 */
				$result_count = $num_displayed;
			}

			// Force start to 1 as we limited results in SQL.
			$start = 1;
		}
	}

	// PREPARE LINKS ---.
	$extra = URLEscape( 'LO_page=' . $LO_page .
		'&LO_sort=' . $LO_sort .
		'&LO_dir=' . $LO_dir .
		'&LO_search=' . $LO_search .
		( $list_id ? '&LO_id=' . $list_id : '' ) );

	$PHP_tmp_SELF = PreparePHP_SELF(
		$_REQUEST,
		[
			'LO_page',
			'LO_sort',
			'LO_dir',
			'LO_search',
			'LO_save',
			'LO_id',
		]
	);

	// END PREPARE LINKS ---.

	// UN-GROUPING
	$group_count = empty( $group ) ? false : count( $group );

	if ( $group_count
		&& $result_count )
	{
		$group_result = $result;

		$result = [ 0 => '' ];

		foreach ( (array) $group_result as $item1 )
		{
			foreach ( (array) $item1 as $item2 )
			{
				if ( $group_count == 1 )
				{
					$result[] = $item2;

					continue;
				}

				foreach ( (array) $item2 as $item3 )
				{
					if ( $group_count == 2 )
					{
						$result[] = $item3;

						continue;
					}

					foreach ( (array) $item3 as $item4 )
					{
						$result[] = $item4;
					}
				}
			}
		}

		unset( $result[0] );

		$result_count = $display_count = count( $result );
	}
	// END UN-GROUPING

	if ( $result_count > $num_displayed
		&& ! $options['pagination'] )
	{
		// Limit to 1000!
		$display_count = $num_displayed;

		// Remove results above 1000.
		$result = array_slice( $result, 0, $num_displayed, true );
	}

	$display_zero = false;

	// PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---.
	if ( $result_count )
	{
		$remove = 0;

		if ( isset( $link['remove']['variables'] ) )
		{
			$remove = count( $link['remove']['variables'] );
		}

		$cols = count( $column_names );

		// HANDLE SEARCHES ---.
		if ( $options['search']
			&& $LO_search !== '' )
		{
			// @since 5.8.
			$result = _listSearch( $result, $LO_search );

			$result_count = $display_count = count( $result );

			if ( $group_count )
			{
				$options['count'] = false;

				$display_zero = true;
			}
		}

		// END SEARCHES ---.

		if ( $LO_sort
			&& $result_count > 1
			&& array_key_exists( $LO_sort, $result[1] ) )
		{
			foreach ( (array) $result as $sort )
			{
				if ( ! isset( $sort[$LO_sort] )
					|| (string) $sort[$LO_sort] === '' )
				{
					$sort_array[] = '';

					continue;
				}

				if ( mb_substr( (string) $sort[$LO_sort], 0, 4 ) != '<!--' )
				{
					// Better list sorting by isolating the values
					$inner_text = trim( strip_tags( preg_replace(
						'/<script\b[^>]*>(.*?)<\/script>/is',
						"",
						(string) $sort[$LO_sort]
					) ) );

					$sort_array[] = $inner_text !== '' ? $inner_text : trim( $sort[ $LO_sort ] );

					continue;
				}

				// Use value inside comment to sort!
				$sort_array[] = trim( mb_substr(
					$sort[$LO_sort],
					4,
					mb_strpos( $sort[$LO_sort], '-->' ) - 4
				) );
			}

			$dir = $LO_dir == -1 ? SORT_DESC : SORT_ASC;

			$flag = is_numeric( $sort_array[1] ) ? SORT_NUMERIC : SORT_STRING | SORT_FLAG_CASE;

			array_multisort( $sort_array, $dir, $flag, $result );

			array_unshift( $result, [ 'always_start_list_at_key_1' ] );

			unset( $result[0] );
		}
	}

	// HANDLE MISC ---.
	if ( empty( $LO_dir ) )
	{
		$LO_dir = 1;
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ( ( isset( $cols ) && $cols > 8 ) || ! empty( $_REQUEST['expanded_view'] ) ) )
	{
		// For wkhtmltopdf.
		$_SESSION['orientation'] = 'landscape';
	}
	// END MISC ---.

	// HANDLE PAGINATION ---.
	if ( empty( $LO_page )
		|| (string) (int) $LO_page != $LO_page
		|| $LO_page < 1 )
	{
		$LO_page = 1;
	}

	if ( $result_count )
	{
		if ( $result_count >= $num_displayed )
		{
			$start_display = ( $LO_page - 1 ) * $num_displayed + 1;
			$stop_display = $start_display + ( $num_displayed - 1 );

			$start = issetVal( $start, $start_display );

			if ( $stop_display > $result_count )
			{
				$stop_display = $result_count;
			}

			$where_message = '<span class="size-1">' .
				sprintf( _( 'Displaying %d through %d' ), $start_display, $stop_display ) . '.</span> ';

			if ( $options['pagination'] )
			{
				$total_pages = ceil( $result_count / $num_displayed );

				$pagination = [ '<span class="rseparator"></span><span class="size-1">' . _( 'Page' ) . ':' ];

				for ( $i = 1; $i <= $total_pages; $i++ )
				{
					if ( $i == $LO_page )
					{
						$pagination[] = ' <b>&nbsp;' . $i . '&nbsp;</b> ';

						continue;
					}

					$add_to_url = [ 'LO_page' => $i ];

					if ( $list_id )
					{
						$add_to_url['LO_id'] = $list_id;
					}

					$page_url = PreparePHP_SELF( $_REQUEST, [ 'LO_search' ], $add_to_url );

					$pagination[] = ' <a href="' . $page_url . '">&nbsp;' . $i . '&nbsp;</a> ';
				}

				$pagination[] = ' </span>';

				$pagination = implode( '&nbsp;', $pagination );

				// Remove results above $num_displayed (1000).
				$result = array_slice( $result, $start - 1, $num_displayed );

				array_unshift( $result, [ 'always_start_list_at_key_1' ] );

				unset( $result[0] );

				$display_count = count( $result );
			}
		}

		// Reset start & stop after removing results above 1000.
		$start = 1;
		$stop = $display_count;
	}
	// END PAGINATION ---.

	// List Before hook.
	do_action( 'functions/ListOutput.fnc.php|list_before' );

	// HANDLE SAVING THE LIST ---.

	if ( $options['save']
		&& (int) $LO_save === (int) $options['save']
		&& ! headers_sent() )
	{
		_listSave( $result, $column_names, $singular, $plural, Preferences( 'DELIMITER' ) );
	}

	// END SAVING THE LIST ---.

	$class = '';

	if ( $plural && $plural !== '.' )
	{
		$class = mb_strtolower( preg_replace(
			'/([^\-a-z0-9]+)/i',
			'-',
			$plural
		) );
	}

	echo '<div class="list-outer ' . $class . '">';

	// SEARCH BOX & MORE HEADERS ---.

	if ( ! empty( $options['header'] ) )
	{
		echo '<table class="postbox width-100p cellspacing-0 list-header"><thead><tr><th class="center">' .
			$options['header'] . '</th></tr></thead></table>
			<div class="postbox">';
	}

	$list_has_nav = false;

	if ( $options['count']
		|| $display_zero
		|| ( $options['save']
			|| $options['search']
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) ) )
	{
		$list_has_nav = true;

		echo '<table class="list-nav"><tr class="st"><td>';

		if ( $options['count']
			&& $display_count > 0 )
		{
			$result_text = ngettext(
				( $singular === '.' ? _( 'Result' ) : $singular ),
				( $plural === '.' ? _( 'Results' ) : $plural ),
				$result_count
			);

			if ( mb_substr( $_SESSION['locale'], 0, 2 ) !== 'de' )
			{
				// We are inside a sentence, convert nouns to lowercase (except for German).
				$result_text = mb_strtolower( $result_text );
			}

			echo '<span class="size-1">' . sprintf(
				ngettext( '%d %s was found.', '%d %s were found.', $result_count ),
				$result_count,
				$result_text
			) . '</span> ';

			echo empty( $where_message ) ? '' : $where_message;
		}

		echo empty( $pagination ) ? '' : $pagination;

		if (  ( $options['count']
			|| $display_zero )
			&& ( $result_count == 0
				|| $display_count == 0 ) )
		{
			$result_text = ngettext(
				( $singular === '.' ? _( 'Result' ) : $singular ),
				( $plural === '.' ? _( 'Results' ) : $plural ),
				0
			);

			if ( mb_substr( $_SESSION['locale'], 0, 2 ) !== 'de' )
			{
				// We are inside a sentence, convert nouns to lowercase (except for German).
				$result_text = mb_strtolower( $result_text );
			}

			// No results message. Default to "Results".
			echo '<b class="size-1">' . sprintf(
				_( 'No %s were found.' ),
				$result_text
			) . '</b> ';
		}

		if ( $options['save']
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
			&& $result_count > 0 )
		{
			// Save / Export list button.
			echo '<a href="' . $PHP_tmp_SELF . '&' . $extra .
			'&LO_save=' . $options['save'] .
			'&_ROSARIO_PDF=true" class="list-save" target="_blank"><img src="assets/themes/' .
			Preferences( 'THEME' ) . '/btn/download.png" class="alignImg" title="' .
			AttrEscape( _( 'Export list' ) ) . '" alt="' . AttrEscape( _( 'Export list' ) ) . '"></a>';
		}

		echo '</td>';

		$colspan = 1;

		if ( $options['search']
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
			&& ( $result_count > 0
				|| $LO_search ) )
		{
			echo '<td class="align-right">';

			$add_to_url = [];

			if ( $list_id )
			{
				$add_to_url['LO_id'] = $list_id;
			}

			// Do not remove search URL due to document.URL = 'index.php' in old IE browsers.
			$search_URL = PreparePHP_SELF( $_REQUEST, [ 'LO_search' ], $add_to_url );

			$onkeypress_js = 'LOSearch(event, this.value, ' . json_encode( $search_URL ) . ');';

			$onclick_js = 'LOSearch(event, $(\'#LO_search\').val(), ' . json_encode( $search_URL ) . ');';

			echo '<input type="text" id="LO_search" name="LO_search" value="' .
			AttrEscape( DBUnescapeString( $LO_search ) ) .
			'" placeholder="' . AttrEscape( _( 'Search' ) ) .
			'" onkeypress="' . AttrEscape( $onkeypress_js ) . '" autocomplete="off">
				<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png"
				onclick="' . AttrEscape( $onclick_js ) . '"
				class="button" alt="" title="' . AttrEscape( _( 'Search' ) ) . '">
				<label for="LO_search" class="a11y-hidden">' . _( 'Search' ) . '</label>';

			echo '</td>';

			$colspan++;
		}

		echo '</tr></table>';
	}

	// END SEARCH BOX & MORE HEADERS ---.

	if ( $result_count > 0 )
	{
		// List has input?
		$list_has_input = false;

		$item = reset( $result );

		foreach ( (array) $item as $string )
		{
			if ( $string
				&& ( strpos( $string, '<input' ) !== false
					|| strpos( $string, '<select' ) !== false ) )
			{
				// First row has input.
				$list_has_input = true;

				break;
			}
		}

		echo '<div class="list-wrapper"><table class="list widefat' .
			( $options['responsive'] && ! isset( $_REQUEST['_ROSARIO_PDF'] ) ? ' rt' : '' ) .
			( $options['valign-middle'] ? ' valign-middle' : '' ) .
			( ! $list_has_nav ? ' list-no-nav' : '' ) .
			( $list_has_input ? ' has-input' : '' ) .
			'" data-list-id="' . $list_id . '"><thead><tr>';

		$i = 1;

		if ( $remove
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			echo '<th class="list-column-delete"><span class="a11y-hidden">' . _( 'Delete' ) . '</span></th>';

			$i++;
		}

		if ( $cols )
		{
			foreach ( (array) $column_names as $key => $value )
			{
				$direction = $LO_sort == $key ? -1 * (int) $LO_dir : 1;

				$i++;

				// @since 10.9 CSS Add .list-column-[column_name] class
				// Note: you can set column max-width using CSS .list-column-comment { width: 36%; }
				$class = 'list-column-' . mb_strtolower( preg_replace(
					'/([^\-a-z0-9]+)/i',
					'-',
					$key
				) );

				if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
				{
					echo '<td class="' . $class . '" style="background-color:' . $options['header_color'] . '; color:#fff;"><b>' .
						ParseMLField( $value ) . '</b></td>';

					continue;
				}

				if ( $options['sort']
					// Fix MakeChooseCheckbox() remove parent link to sort column
					&& mb_strpos( $value, 'id="controller' ) === false )
				{
					echo '<th class="' . $class . '"><a href="' . $PHP_tmp_SELF . URLEscape( '&LO_page=' . $LO_page .
						'&LO_sort=' . $key . '&LO_dir=' . $direction .
						'&LO_search=' . issetVal( $LO_search, '' ) .
						( $list_id ? '&LO_id=' . $list_id : '' ) ) . '">' .
						ParseMLField( $value ) . '</a></th>';

					continue;
				}

				echo '<th class="' . $class . '">' . ParseMLField( $value ) . '</th>';
			}
		}

		echo '</tr></thead><tbody>';

		// mab - enable add link as first or last

		if ( isset( $link['add']['first'] )
			&& ( $stop - $start + 1 ) >= $link['add']['first'] )
		{
			if ( isset( $link['add']['link'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr class="list-add-row"><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
					button(
						'add',
						issetVal( $link['add']['title'], '' ),
						( mb_strpos( $link['add']['link'], '"' ) === 0 ?
							$link['add']['link'] :
							URLEscape( $link['add']['link'] ) )
					) . '</td></tr>';
			}
			elseif ( isset( $link['add']['span'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr class="list-add-row"><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
					button( 'add' ) . $link['add']['span'] . '</td></tr>';
			}
			elseif ( isset( $link['add']['html'] ) && $cols )
			{
				echo '<tr class="list-add-row">';

				if ( $remove && ! isset( $_REQUEST['_ROSARIO_PDF'] ) && $link['add']['html']['remove'] )
				{
					echo '<td>' . $link['add']['html']['remove'] . '</td>';
				}
				elseif ( $remove && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
				{
					echo '<td>' . button( 'add' ) . '</td>';
				}

				foreach ( (array) $column_names as $key => $value )
				{
					echo '<td>' . issetVal( $link['add']['html'][$key], '' ) . '</td>';
				}

				echo '</tr>';
			}
		}

		for ( $i = $start; $i <= $stop; $i++ )
		{
			$item = $result[$i];

			if ( isset( $_REQUEST['_ROSARIO_PDF'] ) && count( $item ) )
			{
				$key = array_keys( $item );
				$size = count( $key );

				for ( $j = 0; $j < $size; $j++ )
				{
					if ( empty( $item[$key[$j]] ) )
					{
						continue;
					}

					$value = preg_replace( '!<select.*selected\>([^<]+)<.*</select\>!i', '\\1', $item[$key[$j]] );
					$value = preg_replace( '!<select.*</select\>!i', '', $value );
					$item[$key[$j]] = preg_replace( "/<div onclick=[^']+'>/", '', $value );
				}
			}

			echo '<tr>';

			if ( $remove && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$button_title = issetVal( $link['remove']['title'] );

				$button_link = empty( $link['remove']['link'] ) ?
					PreparePHP_SELF( [], array_keys( $link['remove']['variables'] ) ) :
					URLEscape( $link['remove']['link'] );

				foreach ( (array) $link['remove']['variables'] as $var => $val )
				{
					$button_link .= URLEscape( '&' . $var . '=' . issetVal( $item[$val], '' ) );
				}

				echo '<td>' . button(
					'remove',
					$button_title,
					$button_link
				) . '</td>';
			}

			$color = issetVal( $item['row_color'] );

			if ( $cols )
			{
				foreach ( (array) $column_names as $key => $value )
				{
					echo $color === Preferences( 'HIGHLIGHT' ) ?
						'<td class="highlight">' :
						'<td>';

					if ( empty( $link[$key] ) || $item[$key] === false || isset( $_REQUEST['_ROSARIO_PDF'] ) )
					{
						echo issetVal( $item[$key], '&nbsp;' );

						echo '</td>';

						continue;
					}

					$link_url = $link[$key]['link'];

					foreach ( (array) $link[$key]['variables'] as $var => $val )
					{
						// Fix URL encode link variable value to encode "/"
						$link_url .= '&' . $var . '=' . urlencode( $item[$val] );
					}

					$link_url = URLEscape( $link_url );

					if ( ! empty( $link[$key]['js'] ) )
					{
						// @since 12.0 Use colorBox instead of popup window
						echo '<a href="' . $link_url . '" class="colorbox"';
					}
					else
					{
						echo '<a href="' . $link_url . '"';
					}

					echo empty( $link[$key]['extra'] ) ? '' : ' ' . $link[$key]['extra'];

					echo '>';

					echo issetVal( $item[$key], '***' );

					echo '</a></td>';
				}
			}

			echo '</tr>';
		}

		if ( ! isset( $link['add']['first'] )
			|| ( $stop - $start + 1 ) < $link['add']['first'] )
		{
			if ( isset( $link['add']['link'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr class="list-add-row"><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
				button(
					'add',
					issetVal( $link['add']['title'], '' ),
					( mb_strpos( $link['add']['link'], '"' ) === 0 ?
						$link['add']['link'] :
						URLEscape( $link['add']['link'] ) )
				) . '</td></tr>';
			}
			elseif ( isset( $link['add']['span'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr class="list-add-row"><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
					button( 'add' ) . $link['add']['span'] . '</td></tr>';
			}
			elseif ( isset( $link['add']['html'] ) && $cols )
			{
				echo '<tr class="list-add-row">';

				if ( $remove
					&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
					&& ! empty( $link['add']['html']['remove'] ) )
				{
					echo '<td>' . $link['add']['html']['remove'] . '</td>';
				}
				elseif ( $remove && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
				{
					echo '<td>' . button( 'add' ) . '</td>';
				}

				foreach ( (array) $column_names as $key => $value )
				{
					echo '<td>' . issetVal( $link['add']['html'][$key], '' ) . '</td>';
				}

				echo '</tr>';
			}
		}

		echo '</tbody></table></div>';

		echo empty( $options['header'] ) ? '' : '</div>';
	}

	// END PRINT THE LIST ---.

	// NO RESULTS, BUT HAS ADD FIELDS ---.
	if ( $result_count == 0 )
	{
		if ( ! empty( $link['add']['link'] )
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			echo '<div class="center">' .
			button(
				'add',
				issetVal( $link['add']['title'], '' ),
				( mb_strpos( $link['add']['link'], '"' ) === 0 ?
					$link['add']['link'] :
					URLEscape( $link['add']['link'] ) )
			) . '</div>';
		}
		elseif (  ( ! empty( $link['add']['html'] )
			|| ! empty( $link['add']['span'] ) )
			&& count( $column_names )
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( ! empty( $link['add']['html'] ) )
			{
				echo '<div class="list-wrapper"><table class="list widefat has-input';

				echo $options['responsive'] ? ' rt' : '';

				echo $options['valign-middle'] ? ' valign-middle' : '';

				echo $list_has_nav ? '' : ' list-no-nav';

				echo '" data-list-id="' . $list_id . '"><thead><tr>';

				echo '<th><span class="a11y-hidden">' . _( 'Delete' ) . '</span></th>';

				foreach ( (array) $column_names as $value )
				{
					echo '<th>' . ParseMLField( $value ) . '</th>';
				}

				echo '</tr></thead><tbody><tr class="list-add-row"><td>';

				echo ! empty( $link['add']['html']['remove'] ) ?
					$link['add']['html']['remove'] :
					button( 'add' );

				echo '</td>';

				foreach ( (array) $column_names as $key => $value )
				{
					echo '<td>' . issetVal( $link['add']['html'][$key], '' ) . '</td>';
				}

				echo '</tr></tbody></table></div>';
			}
			elseif ( ! empty( $link['add']['span'] ) )
			{
				echo '<table class="widefat"><tr class="list-add-row"><td>' .
					button( 'add' ) . $link['add']['span'] . '</td></tr></table>';
			}
		}

		echo empty( $options['header'] ) ? '' : '</div>';
	}

	// END NO RESULTS, BUT HAS ADD FIELDS ---.

	echo '</div>'; // .list-outer.

	// List After hook.
	do_action( 'functions/ListOutput.fnc.php|list_after' );
}

/**
 * Reindex Results
 * Starting from 1
 *
 * @deprecated since 12.0
 *
 * Local function
 *
 * @example $result = _ReindexResults( $result );
 *
 * @param  array $array    Array to reindex
 * @return array Reindexed Array
 */
function _ReindexResults( $array )
{
	// Raise deprecation notice.
	trigger_error(
		'_ReindexResults() function is deprecated since RosarioSIS 12.0. It will be removed in RosarioSIS 13.0.',
		E_USER_DEPRECATED
	);

	$new = [];

	$i = 1;

	foreach ( (array) $array as $value )
	{
		$new[$i] = $value;

		$i++;
	}

	return $new;
}

/*class Rosario_List implements Countable
{
	/**
	 * Get the count of elements in the container array.
	 *
	 * @link http://php.net/manual/en/countable.count.php
	 *
	 * @return int
	 */
	/*public function count()
	{
		return count( $this->container );
	}
}*/

/**
 * Search List
 *
 * Local function
 *
 * @example $result = _listSearch( $result, $LO_search );
 * @since 5.8
 * @since 12.0 Remove Relevance column, do not sort results
 * @since 12.0 Only return results matching (containing) all terms (AND)
 *
 * @param  array  $result     ListOutput result.
 * @param  string $LO_search  ListOutput search term.
 * @return array  $result     Searched result.
 */
function _listSearch( $result, $LO_search )
{
	$search_term = trim( mb_strtolower( DBUnescapeString( $LO_search ) ) );

	$terms = [];

	if ( mb_substr( $search_term, 0, 1 ) != '"'
		|| mb_substr( $search_term, -1, 1 ) != '"' )
	{
		$search_term = str_replace( '"', '', $search_term );

		$terms = preg_split('/\s+/', $search_term );
	}
	elseif ( mb_strlen( $search_term ) > 2 )
	{
		// Search "expression".
		$search_term = str_replace( '"', '', $search_term );
	}

	$terms_count = count( $terms );

	/* TRANSLATORS: List of words ignored during search operations */
	$ignored_words = explode( ', ', _( 'of, the, a, an, in' ) );

	foreach ( $ignored_words as $word )
	{
		unset( $terms[trim( $word )] );
	}

	foreach ( (array) $result as $key => $columns )
	{
		$col_concat = implode( ' ', $columns );

		// Better list searching by isolating inner text.
		$col_concat = mb_strtolower( strip_tags( preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', "", $col_concat ) ) );

		if ( mb_strpos( $col_concat, $search_term ) !== false )
		{
			// Exact match.
			continue;
		}

		$terms_match = 0;

		foreach ( $terms as $term )
		{
			if ( mb_strpos( $col_concat, $term ) !== false )
			{
				// Term found.
				$terms_match++;

				if ( $terms_match >= $terms_count )
				{
					continue 2;
				}
			}
		}

		unset( $result[$key] );
	}

	if ( ! $result )
	{
		return $result;
	}

	array_unshift( $result, [ 'always_start_list_at_key_1' ] );

	unset( $result[0] );

	return $result;
}


/**
 * Save / Export List to CSV (OpenOffice), Tab (Excel) or XML
 *
 * Local function
 *
 * @example _listSave( $result, $column_names, Preferences( 'DELIMITER' ) );
 * @since 2.9
 * @since 5.8 Export list to Excel using MicrosoftXML (more reliable).
 * @since 10.9.5 Security: prevent CSV Injection via formulas
 * @since 11.2.1 Excel & CSV: replace line breaks (br) with "\n" instead of space
 * @since 11.3 Export list to Excel using SimpleXLSXGen (more reliable)
 *
 * @param  array  $result       ListOutput $result
 * @param  array  $column_names ListOutput $column_names
 * @param  string $singular     ListOutput $singular
 * @param  string $plural       ListOutput $plural
 * @param  string $delimiter    CSV|Tab|XML
 * @return void   Outputs file and exits
 */
function _listSave( $result, $column_names, $singular, $plural, $delimiter )
{
	$format_value =
	function ( $value )
	{
		$value = trim( preg_replace(
			'/ +/', // Remove double spaces.
			' ',
			str_replace(
				[ "\r", "\n", "\t", '[br][br]' ], // Convert new lines to [br], remove tabs.
				[ '', '[br]', '', '[br]' ],
				html_entity_decode(  // Decode HTML entities.
					strip_tags(  // Remove HTML tags.
						str_ireplace(
							[ '&nbsp;', '<br />', '<br>' ], // Convert &nbsp; to space, <br> to [br].
							[ ' ', '[br]', '[br]' ],
							$value
						)
					),
					ENT_QUOTES
				) ) ) );

		// Remove first [br] if any.
		return mb_strpos( $value, '[br]' ) === 0 ? mb_substr( $value, 4 ) : $value;
	};

	switch ( $delimiter )
	{
		case 'CSV':
			$extension = 'csv';
			$delimiter = ',';

			break;

		case 'XML':
			$extension = 'xml';
			$delimiter = "";

			break;

		default: // Tab.

			$extension = 'xlsx';
			$delimiter = "\t";

			break;
	}

	// Clear output.
	ob_end_clean();

	$formatted_columns = $formatted_result = [];

	// Format Columns.
	foreach ( (array) $column_names as $column )
	{
		if ( $column !== '' )
		{
			$column = ParseMLField( $column );

			$column = $format_value( $column );

			$column = str_replace( '[br]', ' ', $column );
		}

		if ( $extension === 'csv' )
		{
			$column = '"' . str_replace( '"', '""', $column ) . '"';
		}

		$formatted_columns[] = $column;
	}

	$i = $extension === 'xlsx' ? 1 : 0;

	$formula_start_characters = [ '=', '-', '+', '@', "\t", "\r" ];

	// Format Results.
	foreach ( (array) $result as $item )
	{
		$formatted_result[$i] = [];

		foreach ( (array) $column_names as $key => $value )
		{
			$value = issetVal( $item[$key], '' );

			if ( $value !== '' )
			{
				$value = preg_replace( '!<select.*selected\>([^<]+)<.*</select\>!i', '\\1', $value );

				$value = preg_replace( '!<select.*</select\>!i', '', $value );

				$value = $format_value( $value );

				$replace_br = $extension === 'xml' ? '[br]' : "\n";

				$value = str_replace( '[br]', $replace_br, $value );
			}

			if ( $extension === 'csv'
				&& mb_strlen( $value ) > 1
				&& in_array( substr( $value, 0, 1 ), $formula_start_characters, true ) )
			{
				/**
				 * Security: prevent CSV Injection via formulas
				 * Use single quote to escape formulas
				 *
				 * @since 10.9.5
				 *
				 * @link https://symfony.com/blog/cve-2021-41270-prevent-csv-injection-via-formulas
				 */
				$value = "'" . $value;
			}

			if ( $extension === 'csv' )
			{
				$value = '"' . str_replace( '"', '""', $value ) . '"';
			}

			$formatted_result[$i][] = $value;
		}

		$i++;
	}

	// Generate output.
	if ( $extension === 'xlsx' )
	{
		/**
		 * Export list to Excel using SimpleXLSXGen (more reliable)
		 *
		 * @uses SimpleXLSXGen class.
		 *
		 * @since 11.3
		 *
		 * @link https://github.com/shuchkin/simplexlsxgen
		 */
		require_once 'classes/SimpleXLSXGen/SimpleXLSXGen.php';

		$formatted_rows = array_merge( [ $formatted_columns ], $formatted_result );

		$xlsx = Shuchkin\SimpleXLSXGen::fromArray( $formatted_rows );

		$xlsx->setTitle( ProgramTitle() );

		$xlsx->downloadAs( ProgramTitle() . '.' . $extension );

		exit();
	}
	elseif ( $extension === 'csv' )
	{
		// 1st line: Columns.
		$output = implode( $delimiter, $formatted_columns );

		$output .= "\n";

		// Then values.
		foreach ( $formatted_result as $result_line )
		{
			$output .= implode( $delimiter, $result_line );

			$output .= "\n";
		}
	}

	// XML.
	else
	{
		$sanitize_xml_tag = function ( $name )
		{
			// Remove punctuation excepted underscores, points and dashes.
			$name = preg_replace( "/(?![.\-_])\p{P}/u", '', $name );

			// Lowercase and replace spaces by underscores.
			$name = mb_strtolower( str_replace( ' ', '_', $name ) );

			if ( (string) (int) mb_substr( $name, 0, 1 ) === mb_substr( $name, 0, 1 ) )
			{
				// Name cannot start with a number.
				$name = '_' . $name;
			}

			return $name;
		};

		$elements = 'items_set';

		$element = 'item';

		if ( $plural !== '.' )
		{
			// Sanitize XML tag names.
			$elements = $sanitize_xml_tag( $plural );

			$element = $sanitize_xml_tag( $singular );
		}

		$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<' . $elements . '>' . "\n";

		foreach ( $formatted_result as $result_line )
		{
			$output .= "\t" . '<' . $element . '>' . "\n";

			foreach ( $result_line as $key => $value )
			{
				if ( $formatted_columns[$key] === '' )
				{
					$column = 'column_' . ( $key + 1 );
				}
				else
				{
					// Sanitize XML tag names.
					$column = $sanitize_xml_tag( $formatted_columns[$key] );
				}

				// http://stackoverflow.com/questions/1091945/what-characters-do-i-need-to-escape-in-xml-documents
				$value = str_replace( '[br]', '<br>', AttrEscape( $value ) );

				$output .= "\t\t" . '<' . $column . '>' . $value .
					'</' . $column . '>' . "\n";
			}

			$output .= "\t" . '</' . $element . '>' . "\n";
		}

		$output .= '</' . $elements . '>';
	}

	// Download file
	header( "Cache-Control: public" );
	header( "Content-Type: application/" . $extension );
	header( "Content-Length: " . strlen( $output ) );
	header( "Content-Disposition: inline; filename=\"" . AttrEscape( ProgramTitle() ) . "." . $extension . "\"\n" );

	echo $output;

	exit();
}
