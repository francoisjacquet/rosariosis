/**
 * PasswordStrength jQuery plugin
 *
 * Indicate password strength to user based on the zxcvbn JS library results.
 * Also adds reveal / hide password routine to input.
 *
 * @example $('#password').passwordStrength( 3, 'Your password must be stronger.' );
 *
 * @link https://francoisjacquet.gitlab.io/password-strength-zxcvbn-js
 *
 * @package RosarioSIS
 * @subpackage assets/js
 * @since 4.4
 * @since 11.1 Add userInputs param to prevent using App name, username, or email in the password
 */

$.fn.passwordStrength = function(minStrength, requiredText, userInputs) {

	var $password = this;

	/**
	 * Check Password strength
	 * Using 5 colored bars corresponding to score points.
	 *
	 * @uses zxcvbn JS library.
	 *
	 * @return {bool} Minimum score <= password score.
	 */
	var checkPassword = function() {
		var password = $password.val(),
			score = 0;

		// console.log(password, minStrength, userInputs);

		var result = zxcvbn(password, ( userInputs || [] ) ),
			score = result.score;

		// console.log(result, (minStrength <= score));

		$password.nextAll('.password-strength-bars').children('span').each(function(i, el) {
			// console.log(i, el);

			var cssVisibility = (password !== '' &&
				i <= score ? 'visible' : 'hidden');

			$(el).css('visibility', cssVisibility);
		});

		return (minStrength <= score);
	};


	var inputCheck = function(e) {

		if ($password.val() !== '' && !checkPassword()) {

			requiredText = requiredText || 'Password must be stronger.';

			// Check Password failed (min score > score).
			$password.focus();
			$password[0].setCustomValidity(requiredText);
		} else {
			$password[0].setCustomValidity('');
		}
	};

	/**
	 * Toggle password
	 * Reveal / hide password input
	 */
	var togglePassword = function() {
		// console.log(this.id);

		$password[0].type = ($(this).hasClass('password-show') ? 'text' : 'password');

		$password.nextAll('.password-toggle').toggle();
	};

	var zxcvbnInit = function() {

		if (!minStrength) {
			return;
		}

		// zxcvbn.js is loaded.
		$password.keyup(checkPassword);

		// Do not use submit event here!
		$password.on('input propertychange', inputCheck);
	};

	/**
	 * 1. Call zxcvbn on password input keyup.
	 * 2. Prevent submitting form if minimum required score < user password score.
	 * 3. Toggle password text visibility on icon click.
	 */
	var init = function() {
		if (!$password.length) {
			return;
		}

		if (minStrength > 0 && typeof zxcvbn == 'undefined') {
			$.getScript('assets/js/zxcvbn/zxcvbn.js', function() {
				zxcvbnInit();
			});
		} else {
			zxcvbnInit();
		}

		$password.nextAll('.password-toggle').bind('click', togglePassword);

		return $password;
	};

	init();
}
