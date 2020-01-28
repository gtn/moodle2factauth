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

/**
 * @param \core_user\output\myprofile\tree $tree
 * @param unknown $user
 * @param unknown $iscurrentuser
 * @param unknown $course
 */
function block_exa2fa_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
	if (!$a2faSettings = \block_exa2fa_user_setting::get($user)) {
		return;
	}
	
	$content = '';
	if ($iscurrentuser) {
		// current user can turn on/off
		$content = $a2faSettings->getSettingOutput();
	} elseif ($course && block_exa2fa_teacher_can_deactivate_student($course->id, $user->id)) {
		$content = $a2faSettings->getTeacherOutput($course->id);
	}
	
	if (!$content) {
		return;
	}
	
	$node = new core_user\output\myprofile\node('contact', 'a2fa_settings', block_exa2fa_trans(['de:A2fa Einstellungen', 'en:A2fa Settings']), null, null, $content);
	$tree->add_node($node);
}
