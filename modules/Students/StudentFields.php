<?php
/**
 * Student Fields
 *
 * @since 4.6 Merge Address Fields & Contact Fields programs with Student Fields program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['id'] = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '';

$_REQUEST['category_id'] = isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : '';

$_REQUEST['category'] = isset( $_REQUEST['category'] ) ? $_REQUEST['category'] : '';

if ( $_REQUEST['category'] === 'address' )
{
	require_once 'modules/Students/includes/AddressFields.php';
}
elseif ( $_REQUEST['category'] === 'contact' )
{
	require_once 'modules/Students/includes/PeopleFields.php';
}
else
{
	require_once 'modules/Students/includes/StudentFields.php';
}


/**
 * Fields Category Menu
 *
 * Local function
 *
 * @since 4.6
 *
 * @param  string $category Category: student|address|contact.
 *
 * @return string           Select Category input.
 */
function _fieldsCategoryMenu( $category )
{
	$link = PreparePHP_SELF(
		array(),
		array( 'category', 'category_id', 'id', 'table', 'ML_tables' )
	) . '&category=';

	$menu = SelectInput(
		$category,
		'category',
		'',
		array(
			'student' => _( 'Student Fields' ),
			'address' => _( 'Address Fields' ),
			'contact' => _( 'Contact Fields' ),
		),
		false,
		'onchange="ajaxLink(\'' . $link . '\' + this.options[selectedIndex].value);" autocomplete="off"',
		false
	);

	return $menu;
}
