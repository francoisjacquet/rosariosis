<?php

/**
 * Get Grade Level Info
 *
 * @param  integer $grade  Grade Level ID
 * @param  string  $column TITLE|SHORT_NAME|SORT_ORDER|NEXT_GRADE_ID Column name (optional). Defaults to TITLE
 *
 * @return string  Grade Level Column content
 */
function GetGrade( $grade, $column = 'TITLE' )
{
	static $grades = null;

	// Column defaults to TITLE
	if ( $column !== 'TITLE'
		&& $column !== 'SHORT_NAME'
		&& $column !== 'SORT_ORDER'
		&& $column !== 'NEXT_GRADE_ID' )
	{
		$column = 'TITLE';
	}

	if ( is_null( $grades ) )
	{
		$grades = DBGet( DBQuery( "SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID
			FROM SCHOOL_GRADELEVELS" ), array(), array( 'ID' ) );
	}

	if ( $column === 'TITLE' )
		$extra = '<!-- ' . $grades[ $grade ][1]['SORT_ORDER'] . ' -->';

	return $extra . $grades[ $grade ][1][ $column ];
}
