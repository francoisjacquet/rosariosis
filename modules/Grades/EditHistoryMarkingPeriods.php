<?php

DrawHeader(ProgramTitle());

if ( $_REQUEST['modfunc']=='update')
{
	if ( isset( $_REQUEST['day_values'], $_REQUEST['month_values'], $_REQUEST['year_values'] ) )
	{
		$requested_dates = RequestedDates(
			$_REQUEST['year_values'],
			$_REQUEST['month_values'],
			$_REQUEST['day_values']
		);

		$_REQUEST['values'] = array_replace_recursive( (array) $_REQUEST['values'], $requested_dates );
	}

	foreach ( (array) $_REQUEST['values'] as $id => $columns)
	{
		if ( $id!='new')
		{
			$sql = "UPDATE history_marking_periods SET ";

			foreach ( (array) $columns as $column => $value)
				$sql .= $column."='".$value."',";

			if ( $_REQUEST['tab_id']!='new')
				$sql = mb_substr($sql,0,-1) . " WHERE MARKING_PERIOD_ID='".$id."'";
			else
				$sql = mb_substr($sql,0,-1) . " WHERE MARKING_PERIOD_ID='".$id."'";

			DBQuery($sql);
		}
		else
		{
			$sql = 'INSERT INTO history_marking_periods ';
			$fields = 'MARKING_PERIOD_ID, SCHOOL_ID, ';
			$values = "NEXTVAL('MARKING_PERIOD_SEQ'), ".UserSchool().", ";

			$go = false;
			foreach ( (array) $columns as $column => $value)
				if ( !empty($value) || $value=='0')
				{
					$fields .= $column.',';
					$values .= '\''.$value.'\',';
					$go = true;
				}

			$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

			if ( $go && $columns['NAME'])
				DBQuery($sql);
		}
	}
	unset($_REQUEST['modfunc']);
}

if ( $_REQUEST['modfunc']=='remove')
{
	if ( DeletePrompt( _( 'History Marking Period' ) ) )
	{
		DBQuery( "DELETE FROM history_marking_periods
			WHERE MARKING_PERIOD_ID='" . $_REQUEST['id'] . "'" );

		DBQuery( "DELETE FROM student_report_card_grades
			WHERE MARKING_PERIOD_ID='" . $_REQUEST['id'] . "'" );

		$_REQUEST['modfunc'] = false;
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&tab_id='.$_REQUEST['tab_id'].'&mp_id='.$mp_id.'" method="POST">';

	DrawHeader('',SubmitButton(_('Save')));

	echo '<br />';

	$sql = 'SELECT * FROM history_marking_periods WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY POST_END_DATE';

	$functions = array( 'MP_TYPE' => 'makeSelectInput',
			'NAME' => 'makeTextInput',
			'SHORT_NAME' => 'makeTextInput',
			'POST_END_DATE' => 'makeDateInput',
			'SYEAR' => 'makeSchoolYearSelectInput'
			);

	//FJ add translation
	$LO_columns = array('MP_TYPE' => _('Type'),
			'NAME' => _('Name'),
			'SHORT_NAME' => _('Short Name'),
			'POST_END_DATE' => _('Grade Post Date'),
			'SYEAR' => _('School Year')
			);

	$link['add']['html'] = array('MP_TYPE'=>makeSelectInput('','MP_TYPE'),
			'NAME'=>makeTextInput('','NAME'),
			'SHORT_NAME'=>makeTextInput('','SHORT_NAME'),
			'POST_END_DATE'=>makeDateInput('','POST_END_DATE'),
			'SYEAR'=>makeSchoolYearSelectInput('','SYEAR')
			);

	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';//&mp_id=$mp_id";
	$link['remove']['variables'] = array('id' => 'MARKING_PERIOD_ID');
	$link['add']['html']['remove'] = button('add');
	$LO_ret = DBGet(DBQuery($sql),$functions);

	ListOutput($LO_ret,$LO_columns,'History Marking Period','History Marking Periods',$link,array(),array('count'=>true,'download'=>false,'search'=>false));

	echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '</form>';
}

function makeTextInput($value,$name)
{    global $THIS_RET;

    if ( $THIS_RET['MARKING_PERIOD_ID'])
        $id = $THIS_RET['MARKING_PERIOD_ID'];
    else
        $id = 'new';

//    if ( $name=='COURSE_TITLE')
//        $extra = 'size=20 maxlength=25';
//    elseif ( $name=='GRADE_PERCENT')
//        $extra = 'size=6 maxlength=6';
//    elseif ( $name=='GRADE_LETTER' || $name=='GP_VALUE' || $name=='UNWEIGHTED_GP_VALUE')
//        $extra = 'size=5 maxlength=5';

//    else
	if ( $name=='NAME')
	{
		$extra = 'size=20 maxlength=25';
	}
	else
		$extra = 'size=10 maxlength=10';

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

function makeDateInput($value,$name)
{    global $THIS_RET;

	if ( $THIS_RET['MARKING_PERIOD_ID'])
	{
		$id = $THIS_RET['MARKING_PERIOD_ID'];
	}
	else
		$id = 'new';

	return DateInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		''
    );
}

function makeSelectInput($value,$name)
{    global $THIS_RET;

    if ( $THIS_RET['MARKING_PERIOD_ID'])
    {
        $id = $THIS_RET['MARKING_PERIOD_ID'];
	}
    else
		$id = 'new';

	$options = array('year' => _('Year'), 'semester' => _('Semester'), 'quarter' => _('Quarter'));

	return SelectInput(
		trim( $value ),
		'values[' . $id . '][' . $name . ']',
		'',
		$options,
		false
	);
}

function makeSchoolYearSelectInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['MARKING_PERIOD_ID'] )
		$id = $THIS_RET['MARKING_PERIOD_ID'];
	else
		$id = 'new';

	$options = array();

	$years = range( UserSyear() - 5, UserSyear() );

	foreach ( (array) $years as $year )
		//FJ school year over one/two calendar years format
		$options[ $year ] = FormatSyear( $year, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );

	return SelectInput(
		trim( $value ),
		'values[' . $id . '][' . $name . ']',
		'',
		$options,
		false
	);
}
