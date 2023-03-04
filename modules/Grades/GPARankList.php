<?php

// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/Grades/includes/ClassRank.inc.php';

DrawHeader( ProgramTitle() );

// Get all the MP's associated with the current MP
$all_mp_ids = explode( "','", trim( GetAllMP( 'PRO', UserMP() ), "'" ) );

if ( ! empty( $_REQUEST['mp'] )
	&& ! in_array( $_REQUEST['mp'], $all_mp_ids ) )
{
	// Requested MP not found, reset.
	RedirectURL( 'mp' );
}

if ( empty( $_REQUEST['mp'] ) )
{
	$_REQUEST['mp'] = UserMP();
}

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	$PHP_tmp_SELF = PreparePHP_SELF();
	echo '<form action="' . $PHP_tmp_SELF . '" method="POST">';

	$mp_select = '<select name="mp" id="mp-select" onchange="ajaxPostForm(this.form,true);">';

	foreach ( (array) $all_mp_ids as $mp_id )
	{
		if ( GetMP( $mp_id, 'DOES_GRADES' ) == 'Y' || $mp_id == UserMP() )
		{
			$mp_select .= '<option value="' . AttrEscape( $mp_id ) . '"' .
				( $mp_id == $_REQUEST['mp'] ? ' selected' : '' ) . '>' . GetMP( $mp_id ) . '</option>';
		}
	}

	$mp_select .= '</select>
		<label for="mp-select" class="a11y-hidden">' . _( 'Marking Period' ) . '</label>';

	DrawHeader( $mp_select );

	echo '</form>';
}

Widgets( 'course' );
Widgets( 'gpa' );
Widgets( 'class_rank' );
Widgets( 'letter_grade' );

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['FROM'] = issetVal( $extra['FROM'], '' );
$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

//$extra['SELECT'] .= ',sgc.GPA,sgc.WEIGHTED_GPA,sgc.CLASS_RANK';
$extra['SELECT'] .= ',sms.cum_weighted_factor, sms.cum_unweighted_factor, sms.cum_rank';

if ( mb_strpos( $extra['FROM'], 'student_mp_stats sms' ) === false )
{
	$extra['FROM'] .= ',student_mp_stats sms';
	$extra['WHERE'] .= " AND sms.STUDENT_ID=ssm.STUDENT_ID AND sms.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'";
}

$extra['columns_after'] = [ 'CUM_UNWEIGHTED_FACTOR' => _( 'Unweighted GPA' ), 'CUM_WEIGHTED_FACTOR' => _( 'Weighted GPA' ), 'CUM_RANK' => _( 'Class Rank' ) ];
$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = [
	'CUM_UNWEIGHTED_FACTOR' => '_roundGPA',
	'CUM_WEIGHTED_FACTOR' => '_roundGPA',
];
$extra['ORDER_BY'] = 'GRADE_ID, CUM_RANK';

// Parent: associated students.
$extra['ASSOCIATED'] = User( 'STAFF_ID' );

if ( User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'student' )
{
	$_REQUEST['search_modfunc'] = 'list';
}

Search( 'student_id', $extra );

foreach ( (array) $all_mp_ids as $mp_id )
{
	if ( GetMP( $mp_id, 'DOES_GRADES' ) == 'Y' || $mp_id == UserMP() )
	{
		// @since 4.7 Automatic Class Rank calculation.
		ClassRankMaybeCalculate( $mp_id );
	}
}

/**
 * @param $gpa
 * @param $column
 */
function _roundGPA( $gpa, $column )
{
	return round( $gpa * SchoolInfo( 'REPORTING_GP_SCALE' ), 2 );
}
