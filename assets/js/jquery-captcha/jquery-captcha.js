/**
 * Captcha jQuery plugin
 *
 * @since 3.5
 *
 * @see CatpchaInput() in Inputs.php
 *
 * @package RosarioSIS
 * @subpackage assets/js
 */

/**
 * Captcha
 *
 * @param  {string} id Captcha base ID for input IDs.
 */
var captcha = function(id) {

	/**
	 * Captcha init function.
	 *
	 * If captcha generated, check it before form submit.
	 *
	 * @return {boolean} False if no captcha / ID found, else true.
	 */
	var init = function() {
		var $captcha = $('.captcha');

		if (!$captcha.length ||
			!id) {
			// No captcha input found or no ID.
			return false;
		}

		var answer = captchaGen();

		if (answer !== false) {
			// Get form in which captcha is placed and check captcha before submit.
			$captcha.closest('form').submit(function() {
				return captchaCheck(answer);
			});
		}

		return true;
	}


	/**
	 * Generate captcha
	 *
	 * Generate random numbers to sum (between 0 and 10)
	 * and display them before captcha input.
	 *
	 * @return {string}    Answer.
	 */
	var captchaGen = function() {
		// Generate idom numbers to sum.
		var n1 = Math.floor(Math.random() * 11),
			n2 = Math.floor(Math.random() * 11); // Between 0-10.

		// Display them before captcha input.
		$('#' + id + '-n1').html(n1);
		$('#' + id + '-n2').html(n2);

		return (n1 + n2).toString();
	}


	/**
	 * Check captcha
	 *
	 * Check captcha answer against user input.
	 *
	 * If wrong, do not submit form and focus on captcha input.
	 *
	 * @param  {string} answer Captcha answer (sum of random numbers).
	 *
	 * @return {boolean}       True if answer is correct, else false.
	 */
	var captchaCheck = function(answer) {

		var input = $('#' + id + '-input');

		console.log(input.val(), answer);

		if (input.val() !== answer) {
			// Wrong answer, focus captcha input.
			input.val('').focus();

			// Do not submit form.
			return false;
		}

		// Save captcha answer.
		$('#' + id + '-answer').val(answer);

		return true;
	}

	init();
}
