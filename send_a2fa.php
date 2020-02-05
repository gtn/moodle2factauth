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
require_once $CFG->dirroot.'/login/lib.php';

$PAGE->set_url('/blocks/exa2fa/send_a2fa.php');
$PAGE->set_context(context_system::instance());

$username = required_param('username', PARAM_USERNAME);
// $password = required_param('password', PARAM_RAW);

if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
	$resetrecord = core_login_generate_password_reset($user);
	block_exa2fa_send_password_change_confirmation_email($user, $resetrecord);
}

// always show ok message

echo $OUTPUT->header();

$msg = block_exa2fa_trans([
	'de:Die Anleitung zum Zurücksetzen des 2FA Codes wurde dir per E-Mail gesendet.',
	'en:We have sent you an email with the instructions on how to reset your 2FA Code.'
]);
notice('<div style="text-align: center; padding: 30px;">'.$msg.'</div>', $CFG->wwwroot.'/index.php');

echo $OUTPUT->footer();
