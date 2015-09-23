<?php

//FJ add School Configuration
$program_config = DBGet( DBQuery( "SELECT *
	FROM PROGRAM_CONFIG
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	AND PROGRAM='students'" ), array(), array( 'TITLE' ) );

// set comments Marking Period
$comments_MP = UserMP();

// if Semester comment
if ( $program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE'] )
	$comments_MP = GetParentMP( 'SEM', UserMP() );


//$_ROSARIO['allow_edit'] = true;
if( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit()
	&& isset( $_POST['values'] )
	&& trim( $_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'] ) !== '' )
{
	//FJ add time and user to comments "comment thread" like
	$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'] =
		date('Y-m-d G:i:s') . '|'
		. User('STAFF_ID') . '||'
		. $_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'];
	
	$existing_RET = DBGet( DBQuery( "SELECT STUDENT_ID, COMMENT
		FROM STUDENT_MP_COMMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID='" . $comments_MP . "'"
	) );
	
	if( !$existing_RET )
		DBQuery( "INSERT INTO STUDENT_MP_COMMENTS
			(
				SYEAR,
				STUDENT_ID,
				MARKING_PERIOD_ID
			)
			values(
				'" . UserSyear() . "',
				'" . UserStudentID() . "',
				'" . $comments_MP . "'
			)"
		);
	else
		$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'] =
			DBEscapeString( $existing_RET[1]['COMMENT'] ) . '||'
			. $_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'];
		
	SaveData(
		array( 'STUDENT_MP_COMMENTS' => "STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND MARKING_PERIOD_ID='" . $comments_MP . "'" ),
		'',
		array( 'COMMENT' => _( 'Comment' ) )
	);
	//unset($_SESSION['_REQUEST_vars']['modfunc']);
	//unset($_SESSION['_REQUEST_vars']['values']);
}

if( empty( $_REQUEST['modfunc'] ) )
{
	$comments_RET = DBGet( DBQuery( "SELECT COMMENT
		FROM STUDENT_MP_COMMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID='" . $comments_MP . "'" ) );
	
	?>

	<TABLE id="student-comments">
		<TR>
			<TD>
				<b><?php echo GetMP( $comments_MP, 'TITLE' ) . ' ' . _( 'Comments' ); ?></b>
				<BR />

	<?php //FJ remove maxlength limitation as it is not technically needed ?>
				<TEXTAREA id="textarea" name="values[STUDENT_MP_COMMENTS][<?php echo UserStudentID(); ?>][COMMENT]" rows="10" cols="66" style="width:100%;"<?php echo AllowEdit() ? '' : ' readonly'; ?>></TEXTAREA>
			</TD>
		</TR>
	<?php
	//echo '<BR /><b>* '._('If more than one teacher will be adding comments for this student').':</b><BR />';
	//echo '<ul><li>'._('Type your name above the comments you enter.').'</li></ul>';
	//echo '<li>'._('Leave space for other teachers to enter their comments.').'</li></ul>';
	//FJ add time and user to comments "comment thread" like
	?>
		<TR>
			<TD>
	<?php
	if (!empty($comments_RET[1]['COMMENT']))
	{
		$comments = explode( '||', $comments_RET[1]['COMMENT'] );

		foreach( $comments as $comment )
		{
			if( is_array( list( $timestamp, $staff_id ) = explode( '|', $comment ) )
				&& is_numeric( $staff_id ) )
			{
				if ( User('STAFF_ID') == $staff_id )
					$staff_name = User( 'NAME' );

				else
				{
					$staff_name_RET = DBGet( DBQuery( "SELECT FIRST_NAME||' '||LAST_NAME AS NAME
						FROM STAFF
						WHERE SYEAR='" . UserSyear() . "'
						AND USERNAME=(
							SELECT USERNAME
							FROM STAFF
							WHERE SYEAR='" . Config( 'SYEAR' ) . "'
							AND STAFF_ID='" . $staff_id . "'
						)" ) );

					$staff_name = $staff_name_RET[1]['NAME'];
				}

				// Comment meta data: "Date hour, User name:"
				echo '<span>' .
					ProperDate( mb_substr( $timestamp, 0, 10 ) ) .
					mb_substr( $timestamp, 10 ) . ', ' .
					$staff_name .
					':</span>';
			}
			else
				// Comment text
				echo '<div>' . nl2br( $comment ) . '</div>';
		}
	}
	?>
			</TD>
		</TR>
	</TABLE>
	<?php

	include( 'modules/Students/includes/Other_Info.inc.php' );
}

?>