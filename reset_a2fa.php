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

require_once __DIR__.'/inc.php';
require_once($CFG->dirroot.'/user/lib.php');

$PAGE->set_url($_SERVER['REQUEST_URI']);
$PAGE->set_context(context_system::instance());

$token = required_param('token', PARAM_ALPHANUMEXT);

$pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;
$sql = "SELECT u.*, upr.token, upr.timerequested, upr.id as tokenid
		  FROM {user} u
		  JOIN {user_password_resets} upr ON upr.userid = u.id
		 WHERE upr.token = ?";
$user = $DB->get_record_sql($sql, array($token));

$loginurl = "{$CFG->httpswwwroot}/login/";
if (empty($user) or ($user->timerequested < (time() - $pwresettime - DAYSECS))) {
	// There is no valid reset request record - not even a recently expired one.
	// (suspicious)
	// Direct the user to the forgot password page to request a password reset.
	echo $OUTPUT->header();
	notice(get_string('noresetrecord'), $loginurl);
	die; // Never reached.
}
if ($user->timerequested < (time() - $pwresettime)) {
	// There is a reset record, but it's expired.
	// Direct the user to the forgot password page to request a password reset.
	$pwresetmins = floor($pwresettime / MINSECS);
	echo $OUTPUT->header();
	notice(get_string('resetrecordexpired', '', $pwresetmins), $loginurl);
	die; // Never reached.
}

if ($user->auth === 'nologin' or !is_enabled_auth($user->auth)) {
	// Bad luck - user is not able to login, do not let them set password.
	echo $OUTPUT->header();
	print_error('forgotteninvalidurl');
	die; // Never reached.
}

// Check this isn't guest user.
if (isguestuser($user)) {
	print_error('cannotresetguestpwd');
}

// Token is correct, and unexpired.

// Delete this token so it can't be used again.
$DB->delete_records('user_password_resets', array('id' => $user->tokenid));

// disable a2fa
if ($a2faSettings = \block_exa2fa_user_setting::get($user)) {
	$a2faSettings->deactivate();
}

unset($SESSION->wantsurl);

echo $OUTPUT->header();

$msg = block_exa2fa_trans([
	'de:2FA ist nun deaktiviert. Bitte log dich neu ein.',
	'en:2FA is now disabled. Please Login again.'
]);
notice('<div style="text-align: center; padding: 30px;">'.$msg.'</div>', $loginurl);

echo $OUTPUT->footer();

