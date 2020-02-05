<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
$token = optional_param('token', '', PARAM_TEXT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login($courseid, true);

$url = '/blocks/exa2fa/login_a2fa_timeout.php';
$PAGE->set_url($url);
$PAGE->set_context(\context_system::instance());

$output = block_exa2fa_get_renderer();

$error = '';

if ($action == 'reset_a2fa') {
	require_once $CFG->dirroot.'/login/lib.php';

	$resetrecord = core_login_generate_password_reset($USER);
	block_exa2fa_send_password_change_confirmation_email($USER, $resetrecord);

	echo $output->header();

	$msg = block_exa2fa_trans([
		'de:Die Anleitung zum Zurücksetzen des 2FA Codes wurde dir per E-Mail gesendet.',
		'en:We have sent you an email with the instructions on how to reset your 2FA Code.'
	]);
	notice('<div style="text-align: center; padding: 30px;">'.$msg.'</div>', $CFG->wwwroot.'/index.php');

	echo $output->footer();
	exit;
}

if ($action == 'login') {
	if (\block_exa2fa\api::check_a2fa_token($USER->id, $token, $error)) {
		$_SESSION['last_a2fa_time'] = time();

		redirect(new moodle_url($returnurl));
		exit;
	}
}

echo $output->header();

?>
	<?php
	if ($error) {
		echo $OUTPUT->notification($error);
	}
	?>

	<form method="post" style="text-align: center;">
	<?php
		echo block_exa2fa_trans([
			'de:Um diesen Bereich betreten zu können ist die erneute Eingabe Ihres 2FA Codes notwendig',
			'en:Please provide your 2FA code to continue',
		]).':';
	?><br/>
	<input type="hidden" name="returnurl" value="<?php echo s($returnurl); ?>"/>
	<input type="hidden" name="action" value="login"/>
	<input type="password" name="token" size="15" value="" placeholder="2FA Code"/><br/>
	<input type="submit" value="Login"/>

	<br/><br/><br/>
	<a href="?action=reset_a2fa"><?php
		echo block_exa2fa_trans([
			'de:2FA Code zurücksetzen',
			'en:Reset 2FA Code',
		]);
	?></a>
</form>
<?php

echo $output->footer();
