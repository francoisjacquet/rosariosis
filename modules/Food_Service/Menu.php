<?php
/**
 * Food Service module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 * 
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Food_Service']['admin'] = [
	'title' => _( 'Food Service' ),
	'default' => 'Food_Service/Accounts.php',
	'Food_Service/Accounts.php' => _( 'Accounts' ),
	'Food_Service/Statements.php' => _( 'Statements' ),
	'Food_Service/Transactions.php' => _( 'Transactions' ),
	'Food_Service/ServeMenus.php' => _( 'Serve Meals' ),
	1 => _( 'Reports' ),
	'Food_Service/ActivityReport.php' => _( 'Activity Report' ),
	'Food_Service/TransactionsReport.php' => _( 'Transactions Report' ),
	'Food_Service/MenuReports.php' => _( 'Meal Reports' ),
	//'Food_Service/BalanceReport.php' => _( 'Balance Report' ),
	'Food_Service/Reminders.php' => _( 'Reminders' ),
	2 => _( 'Setup' ),
	'Food_Service/DailyMenus.php' => _( 'Daily Menus' ),
	'Food_Service/MenuItems.php' => _( 'Meal Items' ),
	'Food_Service/Menus.php' => _( 'Meals' ),
	'Food_Service/Kiosk.php' => _( 'Kiosk Preview' )
	//3 => 'Utilities',
	//'Food_Service/AssignSchool.php' => 'Assign School'
];

$menu['Food_Service']['teacher'] = [
	'title' => _( 'Food Service' ),
	'default' => 'Food_Service/Accounts.php',
	'Food_Service/Accounts.php' => _( 'Accounts' ),
	'Food_Service/Statements.php' => _( 'Statements' ),
	1 => _( 'Menu' ),
	'Food_Service/DailyMenus.php' => _( 'Daily Menus' ),
	'Food_Service/MenuItems.php' => _( 'Meal Items' )
];

$menu['Food_Service']['parent'] = [
	'title' => _( 'Food Service' ),
	'default' => 'Food_Service/Accounts.php',
	'Food_Service/Accounts.php' => _( 'Accounts' ),
	'Food_Service/Statements.php' => _( 'Statements' ),
	1 => _( 'Menu' ),
	'Food_Service/DailyMenus.php' => _( 'Daily Menus' ),
	'Food_Service/MenuItems.php' => _( 'Meal Items' )
];

$exceptions['Food_Service'] = [
	'Food_Service/ServeMenus.php' => true
];
