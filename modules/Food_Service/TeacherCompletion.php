<?php

$date = RequestedDate( 'date', DBDate(), 'set' );

DrawHeader( ProgramTitle() );

$day = date( 'D', strtotime( $date ) );

switch ( $day )
{
	case 'Sun':
		$day = 'U';
		break;
	case 'Thu':
		$day = 'H';
		break;
	default:
		$day = mb_substr( $day, 0, 1 );
		break;
}

$QI = DBQuery( "SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp WHERE sp.SCHOOL_ID='" . UserSchool() . "' AND sp.SYEAR='" . UserSyear() . "' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE SYEAR=sp.SYEAR AND PERIOD_ID=sp.PERIOD_ID AND DOES_FS_COUNTS='Y') ORDER BY sp.SORT_ORDER" );
$periods_RET = DBGet( $QI );

$period_select = '<select name="period"<option value="">' . _( 'All' ) . '</option>';

foreach ( (array) $periods_RET as $period )
{
	$period_select .= '<option value="' . $period[PERIOD_ID] . '"' . (  ( $_REQUEST['period'] == $period['PERIOD_ID'] ) ? ' selected' : '' ) . ">" . $period['TITLE'] . '</option>';
}

$period_select .= '</select>';

// FJ multiple school periods for a course period.
$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,s.STAFF_ID
	FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND	sp.PERIOD_ID = cpsp.PERIOD_ID
	AND cp.TEACHER_ID=s.STAFF_ID
	AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
	AND cp.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID='" . UserSchool() . "'
	AND s.PROFILE='teacher'
	AND cp.DOES_FS_COUNTS='Y' " .
	(  ( $_REQUEST['period'] ) ? " AND cpsp.PERIOD_ID='" . $_REQUEST['period'] . "'" : '' ) .
	" AND position('" . $day . "' in cpsp.DAYS)>0";

$RET = DBGet( $sql, array(), array( 'STAFF_ID', 'PERIOD_ID' ) );

$menus_RET = DBGet( 'SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\'' . UserSchool() . '\' ORDER BY SORT_ORDER', array(), array( 'MENU_ID' ) );

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( ! $_SESSION['FSA_menu_id'] )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( array( 'There are no menus yet setup.' ), 'fatal' );
		}
	}
	else
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
	}

	unset( $_SESSION['FSA_sale'] );
}
else
{
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
}

$totals = array( array() );

if ( ! empty( $RET ) )
{
	foreach ( (array) $RET as $staff_id => $periods )
	{
		$i++;
		$staff_RET[$i]['FULL_NAME'] = $periods[key( $periods )][1]['FULL_NAME'];

		foreach ( (array) $periods as $period_id => $period )
		{
			//$sql = 'SELECT (SELECT DESCRIPTION FROM FOOD_SERVICE_LUNCH_ITEMS WHERE ITEM_ID=ac.ITEM_ID) AS DESCRIPTION,(SELECT SORT_ORDER FROM FOOD_SERVICE_MENU_ITEMS WHERE ITEM_ID=ac.ITEM_ID AND MENU_ID=\''.$_REQUEST['menu_id'].'\') AS SORT_ORDER,ac.SHORT_NAME,ac.COUNT FROM FOOD_SERVICE_COMPLETED ac WHERE ac.STAFF_ID=\''.$staff_id.'\' AND ac.SCHOOL_DATE=\''.$date.'\' AND ac.PERIOD_ID=\''.$period_id.'\' ORDER BY SORT_ORDER';
			$sql = 'SELECT fsi.DESCRIPTION,fsi.SHORT_NAME,ac.COUNT FROM FOOD_SERVICE_COMPLETED ac,FOOD_SERVICE_ITEMS fsi WHERE ac.STAFF_ID=\'' . $staff_id . '\' AND ac.SCHOOL_DATE=\'' . $date . '\' AND ac.PERIOD_ID=\'' . $period_id . '\' AND ac.MENU_ID=\'' . $_REQUEST['menu_id'] . '\' AND fsi.ITEM_ID=ac.ITEM_ID ORDER BY fsi.SORT_ORDER';
			$items_RET = DBGet( $sql );

			if ( $items_RET )
			{
				$color = 'FFFFFF';

				$staff_RET[$i][$period_id] = '<table style="background-color:#' . $color . '"><tr>';

				foreach ( (array) $items_RET as $item )
				{
					$staff_RET[$i][$period_id] .= '<td style="background-color:#' . $color . '">' . ( $item['COUNT'] ? $item['COUNT'] : '0' ) . '<br />' . $item['DESCRIPTION'] . '</td>';

					if ( $color == 'FFFFFF' )
					{
						$color = 'F0F0F0';
					}
					else
					{
						$color = 'FFFFFF';
					}

					if ( $totals[$item['SHORT_NAME']] )
					{
						$totals[$item['SHORT_NAME']]['COUNT'] += $item['COUNT'];
					}
					else
					{
						$totals += array( $item['SHORT_NAME'] => array( 'DESCRIPTION' => $item['DESCRIPTION'], 'COUNT' => $item['COUNT'] ) );
					}
				}

				$staff_RET[$i][$period_id] .= '</tr></table>';
			}
			else
			{
				$staff_RET[$i][$period_id] = button( 'x' );
			}
		}
	}
}

$columns = array( 'FULL_NAME' => 'Teacher' );

if ( empty( $_REQUEST['period'] ) )
{
	foreach ( (array) $periods_RET as $period )
	{
		$columns[$period['PERIOD_ID']] = $period['TITLE'];
	}
}

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';
DrawHeader( PrepareDate( $date, '_date' ) . ' : ' . $period_select . ' : <input type=submit value=' . _( 'Go' ) . '>' );
echo '</form>';

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=add&menu_id=' . $_REQUEST['menu_id'] . '" method="POST">';

if ( count( (array) $menus_RET ) > 1 )
{
	$tabs = array();

	foreach ( (array) $menus_RET as $id => $menu )
	{
		$tabs[] = array( 'title' => $menu[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id );
	}

	echo '<br />';
	echo '<div class="center">' . WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] ) . '</div>';
}

echo '<table class="width-100p"><tr><td>';
$singular = sprintf( _( 'Teacher who takes %s counts' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
$plural = sprintf( _( 'Teachers who take %s counts' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
ListOutput( $staff_RET, $columns, $singular, $plural );
echo '</td></tr>';

$totals = array_values( $totals );
unset( $totals[0] );
echo '<tr><td>';
ListOutput( $totals, array( 'DESCRIPTION' => _( 'Item' ), 'COUNT' => _( 'Total Count' ) ), 'Item Total', 'Item Totals' );
echo '</td></tr></table>';

echo '</form>';
