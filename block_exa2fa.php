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

require_once __DIR__.'/lib/lib.php';

class block_exa2fa extends block_base {
	function init() {
		$this->title = \block_exa2fa\get_string('pluginname');
	}

	function applicable_formats() {
		return array('all' => true);
	}

	function has_config() {
		return true;
	}

	function get_content() {
		global $USER;
		
		if (!$a2faSettings = \block_exa2fa_user_setting::get($USER)) {
			return;
		}
		
		$this->content = new stdClass;
		$this->content->text  = $a2faSettings->getSettingOutput();
		
		return $this->content;
	}
}
