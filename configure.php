<?php
// This file is part of Moodle - http://moodle.org/
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Moodle is free software: you can redistribute it and/or modify
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

require_once('../../config.php');
require_once __DIR__.'/lib/lib.php';

$action = required_param('action', PARAM_ALPHANUMEXT);
$returnurl = new moodle_url(required_param('returnurl', PARAM_LOCALURL));
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login($courseid);

if (!$userid || $userid == $USER->id) {
	$userid = $USER->id;
} elseif (($action == 'deactivate') && block_exa2fa_teacher_can_deactivate_student($COURSE->id, $userid)) {
	// teacher can only deactivate
} else {
	print_error('no permissions');
}

if ($action == 'deactivate') {
	// check 2fa code before deactivating
	\block_exa2fa\api::check_user_a2fa_requirement('deactivate_a2fa');

	\block_exa2fa_user_setting::get($USER->id)->deactivate();
	redirect($returnurl);
} elseif ($action == 'activate') {

	if (\block_exa2fa_user_setting::get($USER->id)->is_a2fa_configured()) {
		// already configured -> return
		// this also prevents from somebody calling activate although 2fa is already active!
		redirect($returnurl);
		exit;
	}

	$secret = optional_param('secret', '', PARAM_ALPHANUM);
	$token = optional_param('token', '', PARAM_ALPHANUM);

	$error = '';
	if ($secret && $token) {
		if (\block_exa2fa_user_setting::get($USER->id)->activate($secret, $token, $error)) {
			// ok
			redirect($returnurl);
			exit;
		}
	}

	if (!$secret) {
		$secret = block_exa2fa_generate_secret();
	}

	// Default formatting.
	$ga = new \PHPGangsta_GoogleAuthenticator();
	// don't allow any special characters in code name
	$src = $ga->getQRCodeGoogleUrl(preg_replace('![^a-zA-Z0-9]+!', '-', $SITE->fullname.'-'.fullname($USER)), $secret);

	$img = '<img src="'.$src.'" />';

	$PAGE->set_url('/blocks/exa2fa/configure.php');
	$PAGE->set_context(context_system::instance());

	echo $OUTPUT->header();

	echo '<div style="text-align: center;">'.block_exa2fa_trans([
			'de:Dein neuer 2FA Code lautet: {$a}',
			'en:Your new 2FA Code: {$a}'], $secret).
		'<br />';

	echo '<h3 style="margin-top: 20px; font-weight: bold;">1. '.block_exa2fa_trans([
			'de:Bitte scannen Sie den QR Code mit einer Auth App (z.B. FreeOTP) ein.',
			'en:Please scan the QR with your Auth App (eg. FreeOTP)']).
		'</h3>';

	echo $img;

	?>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<input type="hidden" name="secret" value="<?php echo $secret; ?>"/>
		<?php

	echo '<h3 style="margin-top: 20px; font-weight: bold;">2. '.block_exa2fa_trans([
			'de:Einstellungen',
			'en:Settings'
		]).
		'</h3>';

	echo '<div><label><input type="checkbox" name="active_for[]" value="login" checked="checked"/> '.block_exa2fa_trans([
			'de:Login mit 2FA schützen',
			'en:Use 2FA for Login'
		]).
		'</label></div>';

	if (class_exists('\block_exacomp\api') && !get_config('exa2fa', 'a2fa_required_for_block_exacomp')) {
		echo '<div><label><input type="checkbox" name="active_for[]" value="block_exacomp"/> '.block_exa2fa_trans([
				'de:Kompetenzraster mit 2FA schützen',
				'en:Use 2FA for Competence Grid'
			]).
			'</divlabel></div>';
	}

	echo '<h3 style="margin-top: 20px; font-weight: bold;">3. '.block_exa2fa_trans([
			'de:Geben Sie zur Kontrolle den in der Auth App generierten 6-stelligen Code ein.',
			'en:To activate your 2FA Login insert the 6-digit Code from your App']).
		'</h3>';

	if ($error) {
		echo '<div class="alert alert-error">'.$error.'</div>';
	}

	?>
		<input type="text" name="token" size="15" value=""/>
		<input type="submit" value="<?php echo block_exa2fa_trans(['de:Bestätigen', 'en:Check Code']); ?>"/>
	</form>
	<?php

	echo '<br /><br />';
	echo \html_writer::empty_tag('input', ['type' => 'button',
		'value' => block_exa2fa_get_string('back'),
		'onclick' => 'document.location.href='.json_encode($returnurl->out(false))]);

	echo $OUTPUT->footer();
} else {
	print_error('unknown action');
}
