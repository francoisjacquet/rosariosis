<?php
/**
 * Get Marking Period functions
 *
 * @package RosarioSIS
 * @package functions
 */

/**
 * Get Marking Period Info
 *
 * Can be called through DBGet()'s functions parameter
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['GetMP']
 *
 * @param  string $mp_id    Marking Period ID.
 * @param  string $column   TITLE|POST_START_DATE|POST_END_DATE|POST_END_DATE|MP|SORT_ORDER|SHORT_NAME|START_DATE|END_DATE|DOES_GRADES|DOES_COMMENTS (optional). Defaults to 'TITLE'.
 *
 * @return string Marking Period Column value
 */
function GetMP( $mp_id, $column = 'TITLE' )
{
	global $_ROSARIO;

	// Mab - need to translate marking_period_id to title to be useful as a function call from dbget
	// also, it doesn't make sense to ask for same thing you give.
	if ( $column === 'MARKING_PERIOD_ID'
		|| ! in_array( $column, array( 'TITLE', 'POST_START_DATE', 'POST_END_DATE', 'POST_END_DATE', 'MP', 'SORT_ORDER', 'SHORT_NAME', 'START_DATE', 'END_DATE', 'DOES_GRADES', 'DOES_COMMENTS' ) ) )
	{
		$column = 'TITLE';
	}

	if ( ! isset( $_ROSARIO['GetMP'] ) )
	{
		$_ROSARIO['GetMP'] = DBGet( "SELECT MARKING_PERIOD_ID,TITLE,POST_START_DATE,
			POST_END_DATE,MP,SORT_ORDER,SHORT_NAME,START_DATE,END_DATE,DOES_GRADES,DOES_COMMENTS
			FROM SCHOOL_MARKING_PERIODS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'", array(), array( 'MARKING_PERIOD_ID' ) );
	}

	return empty( $_ROSARIO['GetMP'][ $mp_id ][1][ $column ] ) ?
		'' :
		$_ROSARIO['GetMP'][ $mp_id ][1][ $column ];
}


/**
 * Get Full Year MP ID
 *
 * @since 4.5
 *
 * @return null or FY MP ID.
 */
function GetFullYearMP()
{
	return DBGetOne( "SELECT MARKING_PERIOD_ID
		FROM SCHOOL_MARKING_PERIODS
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY SORT_ORDER" );
}


/**
 * Get All Marking Periods
 *
 * Returns FY,[SEM,...],[QTR,...],[PRO,...] IDs.
 *
 * @example GetAllMP( 'QTR', UserMP() );
 *
 * @param  string $mp                PRO|QTR|SEM|FY Marking Period.
 * @param  string $marking_period_id Marking Period ID (optional). Defaults to '0' (FY).
 *
 * @return string Marking Period IDs list (separated by commas)
 */
function GetAllMP( $mp, $marking_period_id = '0' )
{
	static $all_mp = null;

	if ( $marking_period_id == 0 )
	{
		$marking_period_id = GetFullYearMP();

		$mp = 'FY';
	}
	elseif ( ! $mp )
	{
		$mp = GetMP( $marking_period_id, 'MP' );
	}

	if ( is_null( $all_mp )
		|| ! isset( $all_mp[ $mp ] ) )
	{
		$error_no_qtr = array( _( 'No quarters found' ) );

		$fy = GetFullYearMP();

		$sem_SQL = "SELECT MARKING_PERIOD_ID
			FROM SCHOOL_MARKING_PERIODS s
			WHERE MP='SEM'
			AND NOT EXISTS (SELECT ''
				FROM SCHOOL_MARKING_PERIODS q
				WHERE q.MP='QTR'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID)
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'";

		$qtr_SQL = "SELECT MARKING_PERIOD_ID,PARENT_ID
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='QTR'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'";

		if ( $mp === 'PRO'
			|| $mp === 'QTR' )
		{
			$qtr_RET = DBGet( $qtr_SQL );
		}
		else
			$qtr_RET = DBGet( $qtr_SQL, array(), array( 'PARENT_ID' ) );

		// FJ Fatal error if no quarters.
		if ( ! $qtr_RET )
		{
			return ErrorMessage( $error_no_qtr, 'fatal' );
		}

		switch ( $mp )
		{
			case 'PRO':

				foreach ( (array) $qtr_RET as $qtr )
				{
					$qtr_id = $qtr['MARKING_PERIOD_ID'];

					$all_mp[ $mp ][ $qtr_id ] = "'" . $fy . "','" . $qtr['PARENT_ID'] . "','" . $qtr_id . "'";

					if ( GetChildrenMP( $mp, $qtr_id ) )
					{
						$all_mp[ $mp ][ $qtr_id ] .= ',' . GetChildrenMP( $mp, $qtr_id );
					}

					/*if ( mb_substr( $all_mp[ $mp ][$value['MARKING_PERIOD_ID']], -1 ) === ',' )
						$all_mp[ $mp ][$value['MARKING_PERIOD_ID']] = mb_substr( $all_mp[ $mp ][ $qtr_id ], 0, -1 );*/
				}

			break;

			case 'QTR':

				foreach ( (array) $qtr_RET as $qtr )
				{
					$qtr_id = $qtr['MARKING_PERIOD_ID'];

					$all_mp[ $mp ][ $qtr_id ] = "'" . $fy . "','" . $qtr['PARENT_ID'] . "','" . $qtr_id . "'";
				}

			break;

			case 'SEM':

				foreach ( (array) $qtr_RET as $sem => $qtrs )
				{
					$all_mp[ $mp ][ $sem ] = "'" . $fy . "','" . $sem . "'";

					foreach ( (array) $qtrs as $qtr )
					{
						$all_mp[ $mp ][ $sem ] .= ",'" . $qtr['MARKING_PERIOD_ID'] . "'";
					}
				}

				$sem_RET = DBGet( $sem_SQL );

				foreach ( (array) $sem_RET as $sem )
				{
					$sem_id = $sem['MARKING_PERIOD_ID'];

					$all_mp[ $mp ][ $sem_id ] = "'" . $fy . "','" . $sem_id . "'";
				}

			break;

			case 'FY':

				// There should be exactly one FY marking period which better be $marking_period_id.
				$all_mp[ $mp ][ $marking_period_id ] = "'" . $marking_period_id . "'";

				foreach ( (array) $qtr_RET as $sem => $qtrs )
				{
					$all_mp[ $mp ][ $marking_period_id ] .= ",'" . $sem . "'";

					foreach ( (array) $qtrs as $qtr )
					{
						$all_mp[ $mp ][ $marking_period_id ] .= ",'" . $qtr['MARKING_PERIOD_ID'] . "'";
					}
				}

				$sem_RET = DBGet( $sem_SQL );

				foreach ( (array) $sem_RET as $sem )
				{
					$all_mp[ $mp ][ $marking_period_id ] .= ",'" . $sem['MARKING_PERIOD_ID'] . "'";
				}

			break;
		}
	}

	return $all_mp[ $mp ][ $marking_period_id ];
}


/**
 * Get Parent Marking Period ID
 *
 * @example GetParentMP( 'SEM', UserMP() );
 *
 * @param string $mp                SEM|FY Marking Period.
 * @param string $marking_period_id Children Marking Period ID.
 *
 * @return string Parent Marking Period ID
 */
function GetParentMP( $mp, $marking_period_id )
{
	static $parent_mp = null;

	if ( is_null( $parent_mp )
		|| ! isset( $parent_mp[ $mp ] ) )
	{
		switch ( $mp )
		{
			case 'SEM':

				$parent_SQL = "SELECT MARKING_PERIOD_ID,PARENT_ID
					FROM SCHOOL_MARKING_PERIODS
					WHERE MP='QTR'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'";

			break;

			case 'FY':

				$parent_SQL = "SELECT MARKING_PERIOD_ID,PARENT_ID
					FROM SCHOOL_MARKING_PERIODS
					WHERE MP='SEM'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'";

			break;

			default:

				return false;
		}

		$parent_mp[ $mp ] = DBGet( $parent_SQL, array(), array( 'MARKING_PERIOD_ID' ) );
	}

	return empty( $parent_mp[ $mp ][ $marking_period_id ] ) ?
		'' :
		$parent_mp[ $mp ][ $marking_period_id ][1]['PARENT_ID'];
}


/**
 * Get Children Marking Period IDs
 *
 * @example GetChildrenMP( 'PRO', UserMP() );
 *
 * @param string $mp                PRO|QTR|SEM|FY Child Marking Period.
 * @param string $marking_period_id Parent Marking Period ID (optional). Defaults to '0' (FY).
 *
 * @return string Children Marking Period IDs list (separated by commas)
 */
function GetChildrenMP( $mp, $marking_period_id = '0' )
{
	static $children_mp = null;

	if ( $mp === 'FY' )
	{
		$marking_period_id = '0';
	}

	elseif ( $mp === 'SEM'
		&& GetMP( $marking_period_id, 'MP' ) === 'QTR' )
	{
		$marking_period_id = GetParentMP( 'SEM', $marking_period_id );
	}

	if ( is_null( $children_mp )
		|| ! isset( $children_mp[ $mp ] ) )
	{
		$qtr_SQL = "SELECT MARKING_PERIOD_ID,PARENT_ID
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='QTR'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'";

		switch ( $mp )
		{
			case 'FY':

				$qtr_RET = DBGet( $qtr_SQL, array(), array( 'PARENT_ID' ) );

				foreach ( (array) $qtr_RET as $sem => $qtrs )
				{
					$children_mp[ $mp ]['0'] .= ",'" . $sem . "'";

					foreach ( (array) $qtrs as $qtr )
					{
						$children_mp[ $mp ]['0'] .= ",'" . $qtr['MARKING_PERIOD_ID'] . "'";
					}
				}

				$children_mp[ $mp ]['0'] = mb_substr( $children_mp[ $mp ][0], 1 );

				return $children_mp[ $mp ]['0'];

			break;

			case 'SEM':

				$qtr_RET = DBGet( $qtr_SQL, array(), array( 'PARENT_ID' ) );

				foreach ( (array) $qtr_RET as $sem => $qtrs )
				{
					$children_mp[ $mp ][ $sem ] = '';

					foreach ( (array) $qtrs as $qtr )
					{
						$children_mp[ $mp ][ $sem ] .= ",'" . $qtr['MARKING_PERIOD_ID'] . "'";
					}

					$children_mp[ $mp ][ $sem ] = mb_substr( $children_mp[ $mp ][ $sem ], 1 );
				}

			break;

			case 'QTR':

				$children_mp[ $mp ][ $marking_period_id ] = "'" . $marking_period_id . "'";

			break;

			case 'PRO':

				$pro_RET = DBGet( "SELECT MARKING_PERIOD_ID,PARENT_ID
					FROM SCHOOL_MARKING_PERIODS
					WHERE MP='PRO'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'", array(), array( 'PARENT_ID' ) );

				foreach ( (array) $pro_RET as $qtr => $pros )
				{
					$children_mp[ $mp ][ $qtr ] = '';

					foreach ( (array) $pros as $pro )
					{
						$children_mp[ $mp ][ $qtr ] .= ",'" . $pro['MARKING_PERIOD_ID'] . "'";
					}

					$children_mp[ $mp ][ $qtr ] = mb_substr( $children_mp[ $mp ][ $qtr ], 1 );
				}

			break;
		}
	}

	return isset( $children_mp[ $mp ][ $marking_period_id ] ) ?
		$children_mp[ $mp ][ $marking_period_id ] :
		'';
}


/**
 * Get Current Marking Period ID
 *
 * Exit with Fatal error if No Marking Period found
 *
 * @example GetCurrentMP( 'QTR', $date, false );
 *
 * @param  string  $mp    PRO|QTR|SEM|FY Marking Period.
 * @param  string  $date  Database Date.
 * @param  boolean $error Fatal error (optional). Defaults to true.
 *
 * @return string Current Marking Period ID
 */
function GetCurrentMP( $mp, $date, $error = true )
{
	static $current_mp = null;

	if ( is_null( $current_mp )
		|| ! isset( $current_mp[ $date ][ $mp ] ) )
	{
		$current_mp[ $date ][ $mp ] = DBGet( "SELECT MARKING_PERIOD_ID
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='" . $mp . "'
			AND '" . $date . "' BETWEEN START_DATE AND END_DATE
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );
	}

	if ( isset( $current_mp[ $date ][ $mp ][1]['MARKING_PERIOD_ID'] ) )
	{
		return $current_mp[ $date ][ $mp ][1]['MARKING_PERIOD_ID'];
	}
	elseif ( $error )
	{
		return ErrorMessage( array( _( 'You are not currently in a marking period' ) ), 'fatal' );
	}
}
