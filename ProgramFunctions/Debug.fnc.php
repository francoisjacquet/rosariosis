<?php
/**
 * Debug RosarioSIS
 */

/**
 * Load PHP Debug bar.
 * Does not integrate well with RosarioSIS...
 *
 * @see https://gitlab.com/francoisjacquet/rosariosis-meta#php-debug-bar
 *
 * @link https://github.com/maximebf/php-debugbar
 *
 * @since 5.0
 */
function PhpDebugBar()
{
	global $debugbarRenderer;

	if ( ! file_exists( 'meta/debug/php-debugbar/vendor/autoload.php' ) )
	{
		return false;
	}

	// Load PHP debug bar.
	require_once 'meta/debug/php-debugbar/vendor/autoload.php';

	$debugbar = new DebugBar\StandardDebugBar();
	$debugbarRenderer = $debugbar->getJavascriptRenderer(
		'meta/debug/php-debugbar/src/DebugBar/Resources'
	);

	// Fix $ not defined JS error.
	$debugbarRenderer->setIncludeVendors('css');

	function debugBarRenderHead()
	{
		global $debugbarRenderer;

		echo $debugbarRenderer->renderHead();
	}

	add_action( 'Warehouse.php|header_head', 'debugBarRenderHead' );

	function debugbarRender()
	{
		global $debugbarRenderer;

		echo $debugbarRenderer->render();
	}

	add_action( 'Warehouse.php|footer', 'debugbarRender' );

	return true;
}


/**
 * Load Kint.
 *
 * @example d( $_REQUEST ); // Var dump.
 * @example d( 1 ); // Debug backtrace shorthand.
 *
 * @see https://gitlab.com/francoisjacquet/rosariosis-meta#kint
 *
 * @link https://github.com/kint-php/kint/
 *
 * @since 5.0
 */
function Kint()
{
	if ( ! file_exists( 'meta/debug/kint.phar' ) )
	{
		function d()
		{
			// Prevent PHP Fatal error if Kint debug d() function not loaded.
			return var_dump( func_get_args() );
		}

		return false;
	}

	require_once 'meta/debug/kint.phar';

	return true;
}
