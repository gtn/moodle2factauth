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

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/common.php';
require_once __DIR__.'/GoogleAuthenticator.php';

use block_exa2fa\globals as g;

class block_exa2fa_user_setting {

	var $user;
	var $exa2fauser;

	/**
	 * @return \block_exa2fa_user_setting
	 */
	static function get($userid) {
		// always load current user from database
		// because $USER caches the user settings
		$user = g::$DB->get_record('user', ['id' => is_object($userid) ? $userid->id : $userid]);

		if (!$user) {
			return null;
		} else {
			$class = __CLASS__;
			return new $class($user);
		}
	}

	function __construct($user) {
		$this->user = $user;

		$this->exa2fauser = g::$DB->get_record('block_exa2fauser', ['userid' => $this->user->id]);
	}

	function getSettingOutput() {
		if (!$this->can_a2fa()) {
			return null;
		}

		$url = new \moodle_url('/blocks/exa2fa/configure.php', ['action'=>null, 'returnurl' => (new \moodle_url(g::$PAGE->url))->out_as_local_url(false)]);

		if ($data = $this->is_a2fa_active()) {
			$output  = '<div style="text-align: center;">'.block_exa2fa_trans(['de:A2fa ist aktiv', 'en:A2fa is active']).'<br />';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:Neuen Code generieren', 'en:Generate a new Code']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'generate']))]);
			$output .= '&nbsp;&nbsp;';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:A2fa deaktivieren', 'en:Disable A2fa']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'deactivate']))]);
			$output .= '</div>';
		} else {
			$output  = '<div style="text-align: center;">'.block_exa2fa_trans(['de:Hier kannst du A2fa aktivieren um Moodle noch sicherer zu machen.', 'en:Activate A2fa to make your Moodle login more secure.']).'<br /><br />';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:A2fa aktivieren', 'en:Enable A2fa']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'activate']))]);
			$output .= '</div>';
		}

		return $output;

	}

	function getTeacherOutput($courseid) {
		if (!$this->can_a2fa()) {
			return null;
		}

		if ($this->is_a2fa_active()) {
			$url = new \moodle_url('/blocks/exa2fa/configure.php', ['action'=>null, 'returnurl' => (new \moodle_url(g::$PAGE->url))->out_as_local_url(false), 'userid' => $this->user->id, 'courseid'=>$courseid]);

			$output  = '<div style="text-align: center;">';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:A2fa deaktivieren', 'en:Disable A2fa']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'deactivate']))]);
			$output .= '</div>';
			return $output;
		}
	}

	function can_a2fa() {
		return $this->user->id && !empty($this->user->auth) && /* guest user has no auth set */
		(in_array($this->user->auth, block_exa2fa_get_enabled_plugins_with_a2fa_available()) /* is standard login */
				|| preg_match('!^a2fa_!', $this->user->auth)); /* is a2fa login */
	}

	function is_a2fa_active() {
		if (preg_match('!^a2fa_!', $this->user->auth) && $this->exa2fauser && $this->exa2fauser->a2faactive && $this->exa2fauser->secret) {
			return $this->exa2fauser;
		} else {
			return null;
		}
	}

	function verifyCodeAndAllowOnlyOnce($secret, $token, &$error) {
		if (empty($token)) {
			$error = block_exa2fa_trans(['de:Bitte gültigen Code eingeben', 'en:Please provide the correct A2fa Code']);
			return false;
		}

		$ga = new \PHPGangsta_GoogleAuthenticator();
		if (!$ga->verifyCode($secret, $token, 1)) {
			$error = block_exa2fa_trans(['de:Bitte gültigen Code eingeben', 'en:Please provide the correct A2fa Code']);

			return false;
		}

		$data = [];

		// check already used:
		if ($this->exa2fauser) {
			if ($this->exa2fauser->lasttokens) {
				$lasttokens = explode(',', $this->exa2fauser->lasttokens);

				// check if already used
				if (in_array($token, $lasttokens)) {
					$error = block_exa2fa_trans(['de:Der eingegebene Code wurde schon einmal verwendet', 'en:Sorry, this code was already used']);
					return false;
				}
			} else {
				$lasttokens = [];
			}

			// add new code
			$lasttokens[] = $token;

			// only save last 5 codes (current code + 2 before + 2 after)
			$lasttokens = array_slice($lasttokens, -5);

			$data['lasttokens'] = join(',', $lasttokens);
		} else {
			// insert empty row
			$data['a2faactive'] = 0;
			$data['lasttokens'] = $token;
		}

		// save last used tokens
		g::$DB->insert_or_update_record('block_exa2fauser', $data, ['userid' => $this->user->id]);

		return true;
	}

	function activate($secret, $token, &$error) {
		if (!$this->can_a2fa()) {
			print_error('a2fa not allowed');
		}

		if (!$this->verifyCodeAndAllowOnlyOnce($secret, $token, $error)) {
			return false;
		}

		$data = [
			'a2faactive' => 1,
			'secret' => $secret
		];

		g::$DB->insert_or_update_record('block_exa2fauser', $data, ['userid' => $this->user->id]);

		g::$DB->update_record('user', [
			'id' => $this->user->id,
			'auth' => 'a2fa_'.preg_replace('!^a2fa_!', '', $this->user->auth)
		]);

		return true;
	}

	function deactivate() {
		g::$DB->update_record('block_exa2fauser', [
			'a2faactive' => 0
		], ['userid' => $this->user->id]);

		g::$DB->update_record('user', [
			'id' => $this->user->id,
			'auth' => preg_replace('!^a2fa_!', '', $this->user->auth)
		]);
	}
}

function block_exa2fa_generate_secret() {
	$ga = new \PHPGangsta_GoogleAuthenticator();
	do{
		$secret = $ga->createSecret();
		$secretCheck = g::$DB->get_field_select('block_exa2fauser', 'secret', g::$DB->sql_compare_text('secret')." = ?", [$secret]);
	} while($secretCheck);

	return $secret;
}

function block_exa2fa_get_enabled_plugins_with_a2fa_available() {
	$enabledPlugins = \core_plugin_manager::instance()->get_enabled_plugins('auth');
	$a2faPlugins = [];
	
	foreach (['manual', 'ldap', 'email'] as $plugin) {
		if (in_array($plugin, $enabledPlugins) && in_array('a2fa_'.$plugin, $enabledPlugins)) {
			// normal and a2fa plugin available
			$a2faPlugins[$plugin] = $plugin;
		}
	}
	
	return $a2faPlugins;
}

function block_exa2fa_teacher_can_deactivate_student($courseid, $studentid) {
	global $USER;
	$teacher = $USER;
	
	$context = \context_course::instance($courseid);
	
	return has_capability('enrol/manual:enrol', $context, $teacher) // is teacher
		&& is_enrolled($context, $studentid); // student is enrolled
}

function block_exa2fa_get_renderer() {
	global $PAGE;

	return $PAGE->get_renderer('block_exa2fa');
}