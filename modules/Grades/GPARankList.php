<?php

// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/Grades/includes/ClassRank.inc.php';

DrawHeader( ProgramTitle() );

// Get all the mp's associated with the current mp
$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,0,SORT_ORDER
FROM SCHOOL_MARKING_PERIODS
WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='" . UserMP() . "'))
AND MP='FY'
UNION
SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,1,SORT_ORDER
FROM SCHOOL_MARKING_PERIODS
WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='" . UserMP() . "')
AND MP='SEM'
UNION
SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,2,SORT_ORDER
FROM SCHOOL_MARKING_PERIODS
WHERE MARKING_PERIOD_ID='" . UserMP() . "'
UNION
SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,3,SORT_ORDER
FROM SCHOOL_MARKING_PERIODS
WHERE PARENT_ID='" . UserMP() . "'
AND MP='PRO'
ORDER BY 5,SORT_ORDER" );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
//FJ changed MP list to GradeBreakdown.php style
	/*if ( ! $_REQUEST['mp'] && GetMP(UserMP(),'POST_START_DATE'))
	$_REQUEST['mp'] = UserMP();
	elseif (mb_strpos(GetAllMP('QTR',UserMP()),$_REQUEST['mp'])===false && mb_strpos(GetChildrenMP('PRO',UserMP()),"'".$_REQUEST['mp']."'")===false && GetMP(UserMP(),'POST_START_DATE'))
	$_REQUEST['mp'] = UserMP();

	if ( ! $_REQUEST['mp'] && GetMP(GetParentMP('SEM',UserMP()),'POST_START_DATE'))
	$_REQUEST['mp'] = GetParentMP('SEM',UserMP());

	$sem = GetParentMP('SEM',UserMP());

	//FJ add year to the list
	$year = GetParentMP('FY',$sem);
	$pro = GetChildrenMP('PRO',UserMP());
	$pros = explode(',',str_replace("'",'',$pro));
	$pro_grading = false;
	$pro_select = '';
	foreach ( (array) $pros as $pro)
	{
	if (GetMP($pro,'DOES_GRADES')=='Y')
	{
	if ( empty( $_REQUEST['mp'] ) )
	{
	$_REQUEST['mp'] = $pro;
	$current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'",array(),array('STUDENT_ID'));
	}
	$pro_grading = true;
	$pro_select .='<option value="'.$pro.'"'.(($pro==$_REQUEST['mp'])?' selected':'').">".GetMP($pro)."</option>";
	}
	}

	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('mp'));
	echo '<form action="'.$PHP_tmp_SELF.'" method="POST">';
	$mps_select = '<select name="mp" onChange="ajaxPostForm(this.form,true);">';

	if (GetMP(UserMP(),'DOES_GRADES')=='Y')
	$mps_select .= '<option value="'.UserMP().'">'.GetMP(UserMP()).'</option>';
	elseif ( $_REQUEST['mp']==UserMP())
	$_REQUEST['mp'] = $sem;

	if (GetMP($sem,'DOES_GRADES')=='Y')
	$mps_select .= '<option value="'.$sem.'"'.($sem==$_REQUEST['mp']?' selected':'').">".GetMP($sem)."</option>";

	//FJ add year to the list
	if (GetMP($year,'DOES_GRADES')=='Y')
	$mps_select .= '<option value="'.$year.'"'.($year==$_REQUEST['mp']?' selected':'').">".GetMP($year)."</option>";

	if ( $pro_grading)
	$mps_select .= $pro_select;

	$mps_select .= '</select>';*/

	if ( empty( $_REQUEST['mp'] ) )
	{
		$_REQUEST['mp'] = UserMP();
	}

	//bjj keeping search terms
	$PHP_tmp_SELF = PreparePHP_SELF();
	echo '<form action="' . $PHP_tmp_SELF . '" method="POST">';

	$mp_select = '<select name="mp" onchange="ajaxPostForm(this.form,true);">';

	foreach ( (array) $mps_RET as $mp )
	{
		if ( $mp['DOES_GRADES'] == 'Y' || $mp['MARKING_PERIOD_ID'] == UserMP() )
		{
			$mp_select .= '<option value="' . $mp['MARKING_PERIOD_ID'] . '"' . ( $mp['MARKING_PERIOD_ID'] == $_REQUEST['mp'] ? ' selected' : '' ) . '>' . $mp['TITLE'] . '</option>';
		}
	}

	$mp_select .= "</select>";

	DrawHeader( $mp_select );
}

Widgets( 'course' );
Widgets( 'gpa' );
Widgets( 'class_rank' );
Widgets( 'letter_grade' );

//$extra['SELECT'] .= ',sgc.GPA,sgc.WEIGHTED_GPA,sgc.CLASS_RANK';
$extra['SELECT'] .= ',sms.cum_weighted_factor, sms.cum_unweighted_factor, sms.cum_rank';

if ( mb_strpos( $extra['FROM'], 'STUDENT_MP_STATS sms' ) === false )
{
	$extra['FROM'] .= ',STUDENT_MP_STATS sms';
	$extra['WHERE'] .= " AND sms.STUDENT_ID=ssm.STUDENT_ID AND sms.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'";
}

$extra['columns_after'] = array( 'CUM_UNWEIGHTED_FACTOR' => _( 'Unweighted GPA' ), 'CUM_WEIGHTED_FACTOR' => _( 'Weighted GPA' ), 'CUM_RANK' => _( 'Class Rank' ) );
$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = array( 'CUM_UNWEIGHTED_FACTOR' => '_roundGPA', 'CUM_WEIGHTED_FACTOR' => '_roundGPA' );
$extra['ORDER_BY'] = 'GRADE_ID, CUM_RANK';

// Parent: associated students.
$extra['ASSOCIATED'] = User( 'STAFF_ID' );

if ( User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'student' )
{
	$_REQUEST['search_modfunc'] = 'list';
}

Search( 'student_id', $extra );

foreach ( (array) $mps_RET as $mp )
{
	if ( $mp['DOES_GRADES'] == 'Y' || $mp['MARKING_PERIOD_ID'] == UserMP() )
	{
		// @since 4.7 Automatic Class Rank calculation.
		ClassRankMaybeCalculate( $mp['MARKING_PERIOD_ID'] );
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
