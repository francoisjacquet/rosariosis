<?php
/**
 * Lists / Listings
 *
 * @package RosarioSIS
 * @subpackage functions
 *
 * @since 4.0 Add List Before and After action hooks
 */

function ListOutput( $result, $column_names, $singular = '.', $plural = '.', $link = array(), $group = array(), $options = array() )
{
	$default_options = array(
		'save' => '1',
		'search' => true,
		'center' => true,
		'count' => true,
		'sort' => empty( $group ),
		'header_color' => Preferences( 'HEADER' ),
		'responsive' => true,
		'add' => true,
	);

	if ( ! empty( $options ) )
	{
		$options = array_replace_recursive( $default_options, $options );
	}
	else
	{
		$options = $default_options;
	}

	$LO_page = ( isset( $_REQUEST['LO_page'] ) ? $_REQUEST['LO_page'] : '' );

	// FJ bugfix ListOutput sorting when more than one list in a page.
	$LO_sort = ( isset( $_REQUEST['LO_sort'] ) ? $_REQUEST['LO_sort'] : '' );

	$LO_dir = ( isset( $_REQUEST['LO_dir'] ) ? $_REQUEST['LO_dir'] : '' );

	$LO_search = ( isset( $_REQUEST['LO_search'] ) ? $_REQUEST['LO_search'] : '' );

	$LO_save = ( isset( $_REQUEST['LO_save'] ) ? $_REQUEST['LO_save'] : '' );

	if ( ! $options['add']
		|| ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( ! empty( $link ) )
		{
			unset( $link['add'] );
			unset( $link['remove'] );
		}
	}

	$result_count = $display_count = count( $result );

	if ( $result_count > 1000 )
	{
		// Limit to 1000!
		$result_count = 1000;

		// Remove results above 1000.
		$result = array_slice( $result, 0, 1001 );
	}

	$num_displayed = 1000;

	// PREPARE LINKS ---.
	$extra = 'LO_page=' . $LO_page .
	'&amp;LO_sort=' . $LO_sort .
	'&amp;LO_dir=' . $LO_dir .
	'&amp;LO_search=' . urlencode( $LO_search );

	$PHP_tmp_SELF = PreparePHP_SELF(
		$_REQUEST,
		array(
			'LO_page',
			'LO_sort',
			'LO_dir',
			'LO_search',
			'LO_save',
			'remove_prompt',
			'remove_name',
		)
	);

	// END PREPARE LINKS ---.

	// UN-GROUPING
	if ( empty( $group ) )
	{
		$group_count = false;
	}
	else
	{
		$group_count = count( $group );
	}

	if ( $group_count
		&& $result_count )
	{
		$group_result = $result;

		unset( $result );

		$result[0] = '';

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

		$result_count = count( $result );
	}

	// END UN-GROUPING

	//$_LIST['output'] = true;

	// PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---.
	if ( $result_count != 0 )
	{
		$count = 0;

		$display_zero = false;

		if ( isset( $link['remove']['variables'] ) )
		{
			$remove = count( $link['remove']['variables'] );
		}
		else
		{
			$remove = 0;
		}

		$cols = count( $column_names );

		// HANDLE SEARCHES ---.
		if ( $result_count
			&& $LO_search !== '' )
		{
			$search_term = trim( mb_strtolower( str_replace( "''", "'", $LO_search ) ) );

			if ( mb_substr( $search_term, 0, 1 ) != '"'
				&& mb_substr( $search_term, -1, 1 ) != '"' )
			{
				$search_term = str_replace( '"', '', $search_term );

				while ( $space_pos = mb_strpos( $search_term, ' ' ) )
				{
					$terms[mb_substr( $search_term, 0, $space_pos )] = 1;

					$search_term = mb_substr( $search_term, ( $space_pos + 1 ) );
				}

				$terms[trim( $search_term )] = 1;
			}

			// Search "expression"
			else
			{
				$search_term = str_replace( '"', '', $search_term );

				$terms[$search_term] = 1;
			}

			/* TRANSLATORS: List of words ignored during search operations */
			$ignored_words = explode( ', ', _( 'of, the, a, an, in' ) );

			foreach ( $ignored_words as $word )
			{
				unset( $terms[trim( $word )] );
			}

			foreach ( (array) $result as $key => $value )
			{
				$values[$key] = 0;

				foreach ( (array) $value as $val )
				{
					// FJ better list searching by isolating the values.
					$val = mb_strtolower( strip_tags( preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', "", $val ) ) );

					if ( $search_term == $val )
					{
						// +25 if Exact match.
						$values[$key] += 25;
					}

					foreach ( (array) $terms as $term => $one )
					{
						if ( mb_strpos( $val, $term ) !== FALSE )
						{
							// +3 for each Term found.
							$values[$key] += 3;
						}
					}
				}

				if ( $values[$key] == 0 )
				{
					unset( $values[$key] );

					unset( $result[$key] );

					$result_count--;

					$display_count--;
				}
			}

			// Add Relevance column.
			if ( $result_count )
			{
				array_multisort( $values, SORT_DESC, $result );

				$result = _ReindexResults( $result );

				$values = _ReindexResults( $values );

				$last_value = 1;

				$scale = ( 100 / $values[$last_value] );

				for ( $i = $last_value; $i <= $result_count; $i++ )
				{
					$score = (int) ( $values[$i] * $scale );

					$result[$i]['RELEVANCE'] = '<div class="bar relevance" style="width:' .
						$score . 'px;">' . $score . '</div>';
				}

				$column_names['RELEVANCE'] = _( 'Relevance' );
			}

			if ( is_array( $group )
				&& count( $group ) )
			{
				$options['count'] = false;

				$display_zero = true;
			}
		}

		// END SEARCHES ---.

		if ( $LO_sort )
		{
			foreach ( (array) $result as $sort )
			{
				if ( mb_substr( $sort[$LO_sort], 0, 4 ) != '<!--' )
				{
					//FJ better list sorting by isolating the values
					//$sort_array[] = $sort[ $LO_sort ];
					$sort_array[] = strip_tags( preg_replace(
						'/<script\b[^>]*>(.*?)<\/script>/is',
						"",
						$sort[$LO_sort]
					) );
				}

				// Use value inside comment to sort!
				else
				{
					$sort_array[] = mb_substr(
						$sort[$LO_sort],
						4,
						mb_strpos( $sort[$LO_sort], '-->' ) - 5
					);
				}
			}

			if ( $LO_dir == -1 )
			{
				$dir = SORT_DESC;
			}
			else
			{
				$dir = SORT_ASC;
			}

			if ( $result_count > 1 )
			{
				if ( is_int( $sort_array[1] )
					|| is_double( $sort_array[1] ) )
				{
					array_multisort( $sort_array, $dir, SORT_NUMERIC, $result );
				}
				else
				{
					array_multisort( $sort_array, $dir, $result );
				}

				for ( $i = $result_count - 1; $i >= 0; $i-- )
				{
					$result[$i + 1] = $result[$i];
				}

				unset( $result[0] );
			}
		}
	}

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

	if ( $result_count > 0
		|| $LO_search !== '' )
	{
		// HANDLE MISC ---.

		if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( empty( $LO_page )
				|| $LO_page < 1 )
			{
				$LO_page = 1;
			}

			if ( empty( $LO_dir ) )
			{
				$LO_dir = 1;
			}

			$start = ( $LO_page - 1 ) * $num_displayed + 1;
			$stop = $start + ( $num_displayed - 1 );

			if ( $stop > $result_count )
			{
				$stop = $result_count;
			}

			if ( $result_count >= $num_displayed )
			{
				$where_message = ' ' . sprintf( _( 'Displaying %d through %d' ), $start, $stop );
			}
		}
		else
		{
			$start = 1;
			$stop = $result_count;

			if ( $cols > 8 || ! empty( $_REQUEST['expanded_view'] ) )
			{
				//FJ wkhtmltopdf
				$_SESSION['orientation'] = 'landscape';
			}
		}

		// END MISC ---.
	}

	// SEARCH BOX & MORE HEADERS ---.

	if ( ! empty( $options['header'] ) )
	{
		echo '<table class="postbox width-100p cellspacing-0 list-header"><thead><tr><th class="center">' . $options['header'] . '</th></tr></thead></table>
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

		if ( $singular !== '.'
			&& $plural !== '.'
			&& $options['count'] )
		{
			if ( $display_count > 0 )
			{
				echo '<b>' . sprintf(
					ngettext( '%d %s was found.', '%d %s were found.', $display_count ),
					$display_count,
					ngettext( $singular, $plural, $display_count )
				) . '</b>';
			}

			if ( isset( $where_message ) )
			{
				echo $where_message;
			}
		}

		if (  ( $options['count']
			|| $display_zero )
			&& ( $result_count == 0
				|| $display_count == 0 ) )
		{
			// No results message. Default to "Results".
			echo '<b>' . sprintf(
				_( 'No %s were found.' ),
				ngettext(
					( $singular === '.' ? _( 'Result' ) : $singular ),
					( $plural === '.' ? _( 'Results' ) : $plural ),
					0
				)
			) . '</b>';
		}

		if ( $options['save']
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
			&& $result_count > 0 )
		{
			// Save / Export list button.
			echo '&nbsp;<a href="' . $PHP_tmp_SELF . '&amp;' . $extra .
			'&amp;LO_save=' . $options['save'] .
			'&amp;_ROSARIO_PDF=true" target="_blank"><img src="assets/themes/' .
			Preferences( 'THEME' ) . '/btn/download.png" class="alignImg" title="' .
			_( 'Export list' ) . '" alt="' . _( 'Export list' ) . '" /></a>';
		}

		echo '</td>';

		$colspan = 1;

		if ( $options['search']
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
			&& ( $result_count > 0
				|| $LO_search ) )
		{
			echo '<td class="align-right">';

			// Do not remove search URL due to document.URL = 'index.php' in old IE browsers.
			$search_URL = PreparePHP_SELF( $_REQUEST, array( 'LO_search' ) );

			echo '<input type="text" id="LO_search" name="LO_search" value="' .
			htmlspecialchars( str_replace( "''", "'", $LO_search ), ENT_QUOTES ) .
			'" placeholder="' . _( 'Search' ) . '" onkeypress="LOSearch(event, this.value, \'' .
			$search_URL . '\');" autocomplete="off" />
				<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png"
				onclick="LOSearch(event, $(\'#LO_search\').val(), \'' . $search_URL . '\');"
				class="button" alt="" title="' . htmlspecialchars( _( 'Search' ), ENT_QUOTES ) . '" />
				<label for="LO_search" class="a11y-hidden">' . _( 'Search' ) . '</label>';

			echo '</td>';

			$colspan++;
		}

		echo '</tr></table>';
	}

	// END SEARCH BOX & MORE HEADERS ---.

	if ( $result_count > 0 )
	{
		echo '<div class="list-wrapper"><table class="list widefat' .
			( $options['responsive'] && ! isset( $_REQUEST['_ROSARIO_PDF'] ) ? ' rt' : '' ) .
			( ! $list_has_nav ? ' list-no-nav' : '' ) . '">';
		echo '<thead><tr>';

		$i = 1;

		if ( $remove
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			echo '<th><span class="a11y-hidden">' . _( 'Delete' ) . '</span></th>';

			$i++;
		}

		if ( $cols )
		{
			foreach ( (array) $column_names as $key => $value )
			{
				if ( $LO_sort == $key )
				{
					$direction = -1 * $LO_dir;
				}
				else
				{
					$direction = 1;
				}

				if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
				{
					echo '<td style="background-color:' . $options['header_color'] . '; color:#fff;"><b>';
					echo ParseMLField( $value );
					echo '</b></td>';
				}
				else
				{
					echo '<th>';

					if ( $options['sort'] )
					{
						echo '<a href="' . $PHP_tmp_SELF . '&amp;LO_page=' . $LO_page . '&amp;LO_sort=' . $key . '&amp;LO_dir=' . $direction . '&amp;LO_search=' . urlencode( isset( $LO_search ) ? $LO_search : '' ) . '">' .
						ParseMLField( $value ) .
							'</a>';
					}
					else
					{
						echo ParseMLField( $value );
					}

					echo '</th>';
				}

				$i++;
			}
		}

		echo '</tr></thead><tbody>';

		// mab - enable add link as first or last

		if ( isset( $link['add']['first'] )
			&& ( $stop - $start + 1 ) >= $link['add']['first'] )
		{
			if ( $link['add']['link'] && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' . button( 'add', $link['add']['title'], $link['add']['link'] ) . '</td></tr>';
			}
			elseif ( $link['add']['span'] && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' . button( 'add' ) . $link['add']['span'] . '</td></tr>';
			}
			elseif ( $link['add']['html'] && $cols )
			{
				echo '<tr>';

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
					echo '<td>' . $link['add']['html'][$key] . '</td>';
				}

				echo '</tr>';
				$count++;
			}
		}

		for ( $i = $start; $i <= $stop; $i++ )
		{
			$item = $result[$i];

			if ( isset( $_REQUEST['_ROSARIO_PDF'] ) && count( $item ) )
			{
				//modify loop: use for instead of foreach
				$key = array_keys( $item );
				$size = count( $key );

				for ( $j = 0; $j < $size; $j++ )
				{
					$value = preg_replace( '!<select.*selected\>([^<]+)<.*</select\>!i', '\\1', $item[$key[$j]] );
					$value = preg_replace( '!<select.*</select\>!i', '', $value );
					$item[$key[$j]] = preg_replace( "/<div onclick=[^']+'>/", '', $value );
				}

				/*foreach ( (array) $item as $key => $value)
			{
			$value = preg_replace('!<select.*selected\>([^<]+)<.*</select\>!i','\\1',$value);
			$value = preg_replace('!<select.*</select\>!i','',$value);

			$item[ $key ] = preg_replace("/<div onclick=[^']+'>/",'',$value);
			}*/
			}

			echo '<tr>';

			$count++;

			if ( $remove && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$button_title = empty( $link['remove']['title'] ) ? '' : $link['remove']['title'];

				$button_link = empty( $link['remove']['link'] ) ?
				PreparePHP_SELF( array(), array_keys( $link['remove']['variables'] ) ) :
				$link['remove']['link'];

				foreach ( (array) $link['remove']['variables'] as $var => $val )
				{
					$button_link .= '&' . $var . '=' . urlencode( $item[$val] );
				}

				echo '<td>' . button(
					'remove',
					$button_title,
					'"' . $button_link . '"'
				) . '</td>';
			}

			$color = empty( $item['row_color'] ) ? '' : $item['row_color'];

			if ( $cols )
			{
				foreach ( (array) $column_names as $key => $value )
				{
					if ( $color === Preferences( 'HIGHLIGHT' ) )
					{
						echo '<td class="highlight">';
					}
					else
					{
						echo '<td>';
					}

					if ( ! empty( $link[$key] ) && $item[$key] !== false && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
					{
						if ( ! empty( $link[$key]['js'] ) )
						{
							echo '<a href="#" onclick=\'popups.open("' . $link[$key]['link'];

							foreach ( (array) $link[$key]['variables'] as $var => $val )
							{
								echo '&' . $var . '=' . urlencode( $item[$val] );
							}

							echo '"); return false;\'';

							if ( $link[$key]['extra'] )
							{
								echo ' ' . $link[$key]['extra'];
							}

							echo '>';
						}
						else
						{
							echo '<a href="' . $link[$key]['link'];

							foreach ( (array) $link[$key]['variables'] as $var => $val )
							{
								echo '&' . $var . '=' . urlencode( $item[$val] );
							}

							echo '"';

							if ( ! empty( $link[$key]['extra'] ) )
							{
								echo ' ' . $link[$key]['extra'];
							}

							echo '>';
						}

						echo isset( $item[$key] ) ? $item[$key] : '***';

						echo '</a>';
					}
					else
					{
						echo isset( $item[$key] ) ? $item[$key] : '&nbsp;';
					}

					echo '</td>';
				}
			}

			echo '</tr>';
		}

		if ( ! isset( $link['add']['first'] )
			|| ( $stop - $start + 1 ) < $link['add']['first'] )
		{
			if ( isset( $link['add']['link'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
				button(
					'add',
					isset( $link['add']['title'] ) ? $link['add']['title'] : '',
					$link['add']['link']
				) .
					'</td></tr>';
			}
			elseif ( isset( $link['add']['span'] ) && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				echo '<tr><td colspan="' . ( $remove ? $cols + 1 : $cols ) . '">' .
				button( 'add' ) . $link['add']['span'] . '</td></tr>';
			}
			elseif ( isset( $link['add']['html'] ) && $cols )
			{
				echo '<tr>';

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
					echo '<td>' .
						( isset( $link['add']['html'][$key] ) ? $link['add']['html'][$key] : '' )
						. '</td>';
				}

				echo '</tr>';
			}
		}

		echo '</tbody></table></div>';

		if ( ! empty( $options['header'] ) )
		{
			echo '</div>';
		}
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
				isset( $link['add']['title'] ) ? $link['add']['title'] : '',
				$link['add']['link']
			) .
				'</div>';
		}
		elseif (  ( ! empty( $link['add']['html'] )
			|| ! empty( $link['add']['span'] ) )
			&& count( $column_names )
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( ! empty( $link['add']['html'] ) )
			{
				echo '<div style="overflow-x:auto;"><table class="list widefat';

				if ( $options['responsive'] )
				{
					echo ' rt';
				}

				if ( ! $list_has_nav )
				{
					echo ' list-no-nav';
				}

				if ( $options['center'] )
				{
					echo ' center';
				}

				echo '"><thead><tr>';

				echo '<th><span class="a11y-hidden">' . _( 'Delete' ) . '</span></th>';

				foreach ( (array) $column_names as $key => $value )
				{
					echo '<th>' . str_replace( ' ', '&nbsp;', $value ) . '</th>';
				}

				echo '</tr></thead>';

				echo '<tbody><tr>';

				if ( ! empty( $link['add']['html']['remove'] ) )
				{
					echo '<td>' . $link['add']['html']['remove'] . '</td>';
				}
				else
				{
					echo '<td>' . button( 'add' ) . '</td>';
				}

				foreach ( (array) $column_names as $key => $value )
				{
					echo '<td>' . $link['add']['html'][$key] . '</td>';
				}

				echo '</tr></tbody>';
				echo '</table></div>';
			}
			elseif ( ! empty( $link['add']['span'] ) )
			{
				echo '<table class="postbox';

				if ( $options['center'] )
				{
					echo ' center';
				}

				echo '"><tr><td>' . button( 'add' ) . $link['add']['span'] . '</td></tr></table>';
			}
		}

		if ( ! empty( $options['header'] ) )
		{
			echo '</div>';
		}
	}

	// END NO RESULTS, BUT HAS ADD FIELDS ---.

	// List After hook.
	do_action( 'functions/ListOutput.fnc.php|list_after' );
}

/**
 * Reindex Results
 * Starting from 1
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
	$new = array();

	$i = 1;

	foreach ( (array) $array as $value )
	{
		$new[$i] = $value;

		$i++;
	}

	return $new;
}

class Rosario_List implements Countable
{
	/**
	 * Get the count of elements in the container array.
	 *
	 * @link http://php.net/manual/en/countable.count.php
	 *
	 * @return int
	 */
	public function count()
	{
		return count( $this->container );
	}
}

/**
 * Save / Export List to CSV (OpenOffice), Tab (Excel) or XML
 *
 * Local function
 *
 * @example _listSave( $result, $column_names, Preferences( 'DELIMITER' ) );
 * @since 2.9
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
			'/ +/', // remove double spaces
			' ',
			str_replace(
				array( "\r", "\n", "\t", '[br][br]' ), // convert new lines to [br], remove tabs
				array( '', '[br]', '', '[br]' ),
				html_entity_decode(  // decode HTML entities
					strip_tags(  // remove HTML tags
						str_ireplace(
							array( '&nbsp;', '<br />' ), // convert &nbsp; to space, <br /> to [br]
							array( ' ', '[br]' ),
							$value
						)
					),
					ENT_QUOTES
				) ) ) );

		// remove first [br] if any

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

		default: // Tab

			$extension = 'xls';
			$delimiter = "\t";

			break;
	}

	// Clear output
	ob_end_clean();

	$formatted_columns = $formatted_result = array();

	// Format Columns

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

	$i = 0;

	// Format Results

	foreach ( (array) $result as $item )
	{
		$formatted_result[$i] = array();

		foreach ( (array) $column_names as $key => $value )
		{
			$value = $item[$key];

			if ( $value !== '' )
			{
				$value = preg_replace( '!<select.*selected\>([^<]+)<.*</select\>!i', '\\1', $value );

				$value = preg_replace( '!<select.*</select\>!i', '', $value );

				$value = $format_value( $value );

				$replace_br = $extension === 'xml' ? '[br]' : ' ';

				$value = str_replace( '[br]', $replace_br, $value );
			}

			if ( $extension === 'csv' )
			{
				$value = '"' . str_replace( '"', '""', $value ) . '"';
			}

			$formatted_result[$i][] = $value;
		}

		$i++;
	}

// Generate output

	if ( $extension !== 'xml' )
	{
		// 1st line: Columns
		$output = implode( $delimiter, $formatted_columns );

		$output .= "\n";

// Then values

		foreach ( (array) $formatted_result as $result_line )
		{
			$output .= implode( $delimiter, $result_line );

			$output .= "\n";
		}
	}

	// XML
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

		if ( $plural !== '.' )
		{
			// Sanitize XML tag names.
			$elements = $sanitize_xml_tag( $plural );

			$element = $sanitize_xml_tag( $singular );
		}
		else
		{
			$elements = 'items_set';

			$element = 'item';
		}

		$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<' . $elements . '>' . "\n";

		foreach ( (array) $formatted_result as $result_line )
		{
			$output .= "\t" . '<' . $element . '>' . "\n";

			foreach ( (array) $result_line as $key => $value )
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
				$value = str_replace( '[br]', '<br />', htmlspecialchars( $value, ENT_QUOTES ) );

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
	header( "Content-Disposition: inline; filename=\"" . ProgramTitle() . "." . $extension . "\"\n" );

	echo $output;

	exit();
}
