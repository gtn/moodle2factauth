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

use block_exa2fa\globals as g;

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

		$a2faSettings = \block_exa2fa_user_setting::get($user);

		if (!$data = $a2faSettings->is_a2fa_active()) {
			// no secret configured -> a2fa check not needed
			return true;
		}
		
		$token = optional_param('token', "", PARAM_TEXT);

		$error = '';
		if ($a2faSettings->verifyCodeAndAllowOnlyOnce($data->secret, $token, $error)) {
			// login ok
			return true;
		}
		
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

	private static function a2fa_for_user_enabled($userid) {
		global $DB;

		$user = $DB->get_record('user', array('id'=>$userid));

		return !!\block_exa2fa_user_setting::get($user)->is_a2fa_active();
	}

	static function check_a2fa_token($userid, $token, &$error) {
		global $DB;

		$user = $DB->get_record('user', array('id'=>$userid));

		$a2faSettings = \block_exa2fa_user_setting::get($user);

		if (!$data = $a2faSettings->is_a2fa_active()) {
			// no secret configured -> a2fa check not needed
			return true;
		}

		$error = '';
		if ($a2faSettings->verifyCodeAndAllowOnlyOnce($data->secret, $token, $error)) {
			// login ok
			return true;
		}

		return false;
	}

	static function check_user_a2fa_requirement($plugin_name) {
		$a2fa_requirement = get_config('exa2fa', 'a2fa_required_for_'.$plugin_name);
		$a2fa_timeout = get_config('exa2fa', 'a2fa_timeout_for_blocks');

		if (!$a2fa_requirement) {
			return;
		}

		$returnurl = \block_exa2fa\url::request_uri()->out_as_local_url(false);

		if (!static::a2fa_for_user_enabled(g::$USER->id)) {
			global $PAGE;

			$url = '/blocks/exa2fa/login_a2fa_timeout.php';
			$PAGE->set_url($url);

			$output = block_exa2fa_get_renderer();
			echo $output->header([], ['is_login_a2fa_timeout_page' => true]);

			echo 'Sie müssen A2fa aktivieren um diesen Bereich betreten zu können.
			<br/><br/>
			<a href="../exa2fa/configure.php?action=activate&returnurl='.\block_exa2fa\url::request_uri()->out_as_local_url().'">A2fa jetzt aktivieren</a>
			';

			echo $output->footer();
			exit;
			// redirect(new moodle_url('/blocks/exa2fa/login_a2fa_required.php', array('courseid' => @$_REQUEST['courseid'], 'returnurl' => $returnurl)));
		}

		if ($a2fa_requirement == 'a2fa_timeout') {
			global $SESSION;

			if (@$SESSION->last_a2fa_time >= time() - $a2fa_timeout + 1) {
				// login ok
				$SESSION->last_a2fa_time = time();
			} else {
				redirect(new \moodle_url('/blocks/exa2fa/login_a2fa_timeout.php', array('courseid' => @$_REQUEST['courseid'], 'returnurl' => $returnurl)));
				exit;
			}
		}
	}

	static function render_timeout_info($plugin_name) {
		$a2fa_requirement = get_config('exa2fa', 'a2fa_required_for_'.$plugin_name);
		$a2fa_timeout = get_config('exa2fa', 'a2fa_timeout_for_blocks');

		$content = '';

		if ($a2fa_requirement == 'a2fa_timeout') {
			$time = time();

			$returnurl = \block_exa2fa\url::request_uri()->out_as_local_url(false);
			$login_url = new \moodle_url('/blocks/exa2fa/login_a2fa_timeout.php', array('courseid' => @$_REQUEST['courseid'], 'returnurl' => $returnurl));

			ob_start();
			?>
				<div style="text-align: right;">Verbleibende Zeit: <span id="exa2fa-ticker-content"></span></div>
				<script>
					var block_exa2fa_timer = function(duration, onTick) {
						function CountDownTimer(duration, granularity) {
						  this.duration = duration;
						  this.granularity = granularity || 1000;
						  this.tickFtns = [];
						  this.running = false;
						}

						CountDownTimer.prototype.start = function() {
						  if (this.running) {
							return;
						  }
						  this.running = true;
						  var start = Date.now(),
							  that = this,
							  diff, obj;

						  (function timer() {
							diff = that.duration - (((Date.now() - start) / 1000) | 0);

							if (diff > 0) {
							  setTimeout(timer, that.granularity);
							} else {
							  diff = 0;
							  that.running = false;
							}

							obj = CountDownTimer.parse(diff);
							that.tickFtns.forEach(function(ftn) {
							  ftn.call(this, obj.minutes, obj.seconds);
							}, that);
						  }());
						};

						CountDownTimer.prototype.onTick = function(ftn) {
						  if (typeof ftn === 'function') {
							this.tickFtns.push(ftn);
						  }
						  return this;
						};

						CountDownTimer.prototype.expired = function() {
						  return !this.running;
						};

						CountDownTimer.parse = function(seconds) {
						  return {
							'minutes': (seconds / 60) | 0,
							'seconds': (seconds % 60) | 0
						  };
						};

						var timer = new CountDownTimer(duration);
						timer.onTick(onTick);
						timer.start();
					};

					block_exa2fa_timer(<?php echo ($a2fa_timeout-1); ?>, function(minutes, seconds){
						$("#exa2fa-ticker-content").html(minutes+":"+(seconds < 10 ? "0" + seconds : seconds));
						if (!minutes && !seconds) {
							// console.log("expired");
							document.location.href = <?php echo json_encode($login_url->out(false)); ?>;
						}
					});
				</script>
			<?php

			$content .= ob_get_clean();
		}

		return $content;
	}
}
