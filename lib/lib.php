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

		if ($this->is_a2fa_configured()) {
			$data = $this->get_a2fauser();

			$output  = '<div style="text-align: center;">'.block_exa2fa_trans(['de:2FA ist aktiv', 'en:2FA is active']).'<br />';
			//$output .= \html_writer::empty_tag('input', ['type'=>'button',
			//				'value'=>block_exa2fa_trans(['de:Neuen Code generieren', 'en:Generate a new Code']),
			//				'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'generate']))]);
			//$output .= '&nbsp;&nbsp;';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:2FA deaktivieren', 'en:Disable 2FA']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'deactivate']))]);
			$output .= '</div>';
		} else {
			$output  = '<div style="text-align: center;">'.block_exa2fa_trans(['de:Hier kannst du 2FA aktivieren um Moodle noch sicherer zu machen.', 'en:Activate 2FA to make your Moodle login more secure.']).'<br /><br />';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:2FA aktivieren', 'en:Enable 2FA']),
							'onclick'=>'document.location.href='.json_encode($url->out(false, ['action' => 'activate']))]);
			$output .= '</div>';
		}

		return $output;

	}

	function getTeacherOutput($courseid) {
		if (!$this->can_a2fa()) {
			return null;
		}

		if ($this->is_a2fa_configured()) {
			$url = new \moodle_url('/blocks/exa2fa/configure.php', ['action'=>null, 'returnurl' => (new \moodle_url(g::$PAGE->url))->out_as_local_url(false), 'userid' => $this->user->id, 'courseid'=>$courseid]);

			$output  = '<div style="text-align: center;">';
			$output .= \html_writer::empty_tag('input', ['type'=>'button',
							'value'=>block_exa2fa_trans(['de:2FA deaktivieren', 'en:Disable 2FA']),
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

	function is_a2fa_configured() {
		return (preg_match('!^a2fa_!', $this->user->auth) && $this->exa2fauser && $this->exa2fauser->a2faactive && $this->exa2fauser->secret);
	}

	function is_a2fa_active_for($type) {
		return $this->is_a2fa_configured() && in_array($type, explode(',', $this->exa2fauser->active_for));
	}

	function get_a2fauser() {
		return $this->exa2fauser;
	}

	function verifyCodeAndAllowOnlyOnce($secret, $token, &$error) {
		if (empty($token)) {
			$error = block_exa2fa_trans(['de:Bitte gültigen Code eingeben', 'en:Please provide the correct 2FA Code']);
			return false;
		}

		$ga = new \PHPGangsta_GoogleAuthenticator();
		if (!$ga->verifyCode($secret, $token, 1)) {
			$error = block_exa2fa_trans(['de:Bitte gültigen Code eingeben', 'en:Please provide the correct 2FA Code']);

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
			print_error('2FA not allowed');
		}

		if (!$this->verifyCodeAndAllowOnlyOnce($secret, $token, $error)) {
			return false;
		}

		$active_for = block_exa2fa\param::optional_array('active_for', PARAM_TEXT);
		$active_for = join(',', $active_for);

		$data = [
			'a2faactive' => 1,
			'secret' => $secret,
			'active_for' => $active_for,
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

/**
 * copied from login/lib.php
 * @param $user
 * @param $resetrecord
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function block_exa2fa_send_password_change_confirmation_email($user, $resetrecord) {
    global $CFG;

    $site = get_site();
    $supportuser = core_user::get_support_user();
    $pwresetmins = isset($CFG->pwresettime) ? floor($CFG->pwresettime / MINSECS) : 30;

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname  = $user->lastname;
    $data->username  = $user->username;
    $data->sitename  = format_string($site->fullname);
    $data->link      = $CFG->httpswwwroot .'/blocks/exa2fa/reset_a2fa.php?token='. $resetrecord->token;
    $data->admin     = generate_email_signoff();
    $data->resetminutes = $pwresetmins;

    $message = block_exa2fa_trans([
    	'de:Guten Tag {$a->firstname},

jemand (wahrscheinlich Sie) hat bei \'{$a->sitename}\' das Zurücksetzen des 2FA Codes für das Nutzerkonto \'{$a->username}\' angefordert.

Um diese Anforderung zu bestätigen und den 2FA Code zu deaktivieren, gehen Sie bitte auf folgende Webseite:

{$a->link}

Hinweis:
Dieser Link wird {$a->resetminutes} Minuten nach der Anforderung ungültig. Meistens erscheint die Webadresse als blauer Link, auf den Sie einfach klicken können. Falls dies nicht funktioniert, kopieren Sie die Webadresse vollständig in die Adresszeile Ihres Browsers. Falls Sie das Zurücksetzen nicht selber ausgelöst haben, hat vermutlich jemand anders Ihren Anmeldenamen oder Ihrer E-Mail-Adresse eingegeben. Dies ist kein Grund zur Beunruhigung. Ignorieren Sie die Nachricht dann bitte.

Bei Problemen wenden Sie sich bitte an die Administrator/innen der Website.

Viel Erfolg!

{$a->admin}',
		'en:Dear {$a->firstname},

you have requested to reset your 2FA Code of \'{$a->username}\' on \'{$a->sitename}\'.

Please click the link below to disable your 2FA Code:

{$a->link}

Note:
The Link is only active for {$a->resetminutes} minutes.

If you experience any Problems, please contact your Moodle Support.

Sincerely,
{$a->admin}'
	], $data);
    $subject = block_exa2fa_trans([
    	'de:{$a}: 2FA Code zurücksetzen',
		'en:{$a}: Reset 2FA Code'
	], format_string($site->fullname));

    // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
    return email_to_user($user, $supportuser, $subject, $message);
}
