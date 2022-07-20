<?php
/**
 * Students Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Students module
 *
 * @since 4.0
 *
 * @return string Dashboard module HTML.
 */
function DashboardDefaultStudents()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardStudentsAdmin();
	}

	return DashboardModule( 'Students', $data );
}

if ( ! function_exists( 'DashboardStudentsAdmin' ) )
{
	/**
	 * Dashboard data
	 * Students module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardStudentsAdmin()
	{
		$students_nb = 0;

		$students_RET = DBGet( "SELECT
		sgl.SHORT_NAME AS GRADELEVEL,
		SUM(CASE WHEN se.GRADE_ID=sgl.ID THEN 1 END) AS STUDENTS_NB
		FROM student_enrollment se, school_gradelevels sgl
		WHERE se.SYEAR='" . UserSyear() . "'
		AND se.SCHOOL_ID='" . UserSchool() . "'
		AND (CURRENT_DATE>=se.START_DATE
			AND (se.END_DATE IS NULL OR CURRENT_DATE<=se.END_DATE))
		AND sgl.SCHOOL_ID='" . UserSchool() . "'
		AND se.GRADE_ID=sgl.ID
		GROUP BY sgl.SHORT_NAME,sgl.SORT_ORDER
		ORDER BY sgl.SORT_ORDER IS NULL,sgl.SORT_ORDER" );

		$students_gradelevel_data = [];

		foreach ( $students_RET as $students )
		{
			$students_gradelevel_data[$students['GRADELEVEL']] = $students['STUDENTS_NB'];

			$students_nb += (int) $students['STUDENTS_NB'];
		}

		if ( ! $students_nb )
		{
			return [];
		}

		$data = [];

		// Active students in school.
		$data[_( 'Students' )] = (int) $students_nb;

		$data += $students_gradelevel_data;

		$inactive_students = DBGetOne( "SELECT
		SUM(CASE WHEN CURRENT_DATE<START_DATE OR CURRENT_DATE>END_DATE THEN 1 END) AS STUDENTS_NB
		FROM student_enrollment
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		GROUP BY START_DATE
		ORDER BY START_DATE DESC
		LIMIT 1" );

		$data[_( 'Inactive' )] = $inactive_students;

		return $data;
	}
}
