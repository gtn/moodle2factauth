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

namespace block_exa2fa;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../inc.php';

class api {
	static function user_login($username, $password) {
		global $CFG, $DB;

		if (isloggedin() && !isguestuser())  {
			// the user is already logged in
			// then this function is called for password change -> no a2fa needed here
			return true;
		}

		if (!$user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
			// no user yet -> no a2fa configured -> a2fa check not needed
			return true;
		}
		
		if (!$data = \block_exa2fa\user_setting::get($user)->is_a2fa_active()) {
			// no secret configured -> a2fa check not needed
			return true;
		}
		
		$token = optional_param('token', "", PARAM_TEXT);
		$ga = new \PHPGangsta_GoogleAuthenticator();
		if (!empty($token) && $ga->verifyCode($data->secret, $token, 2)) {
			// login ok
			return true;
		}
		
		$error = block_exa2fa_trans(['de:Bitte gültigen Code eingeben', 'en:Please provide the correct A2fa Code']);
		
		// for login form, set the login error message
		global $A2FA_ERROR;
		$A2FA_ERROR = $error;
		
		// for webservice, set the login error header
		header('X-A2fa-Required: '.htmlentities($error));
		
		return false;
	}

	static function user_update_password($user, $newpassword) {
		if (isloggedin() && !isguestuser())  {
			// the user is already logged in
			// then this function is called for a normal password change
		} else {
			// the user is not logged in, which is probably a password reset request
			// reset the session when changing password to log him out
			\core\session\manager::terminate_current();
		}

		return true;
	}

	static function a2fa_for_user_enabled($userid) {
		global $DB;

		$user = $DB->get_record('user', array('id'=>$userid));

		return !!\block_exa2fa\user_setting::get($user)->is_a2fa_active();
	}

	static function check_a2fa_token($userid, $token) {
		global $DB;

		$user = $DB->get_record('user', array('id'=>$userid));

		if (!$data = \block_exa2fa\user_setting::get($user)->is_a2fa_active()) {
			// no secret configured -> a2fa check not needed
			return true;
		}

		$ga = new \PHPGangsta_GoogleAuthenticator();
		if (!empty($token) && $ga->verifyCode($data->secret, $token, 2)) {
			// login ok
			return true;
		}

		return false;
	}
}
