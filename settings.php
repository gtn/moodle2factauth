<?php
// This file is part of Exabis 2FA
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Competence Grid is free software: you can redistribute it and/or modify
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

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/inc.php';


if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_configtext('exa2fa/a2fa_timeout_for_blocks', block_exa2fa_get_string('settings_a2fa_timeout'), block_exa2fa_get_string('settings_a2fa_timeout_description'), 10*60));

	if (class_exists('\block_exastud\api')) {
		$a2fa_requirement = [
			'' => block_exa2fa_get_string('settings_a2fa_requirement_disabled'),
			'a2fa_timeout' => block_exa2fa_get_string('settings_a2fa_requirement_a2fa_timeout'),
		];
		$settings->add(new admin_setting_configselect('exa2fa/a2fa_required_for_block_exastud', block_exa2fa_get_string('settings_a2fa_required_for_block_exastud'), '', '', $a2fa_requirement));
	}
	if (class_exists('\block_exacomp\api')) {
		$a2fa_requirement = [
			'' => block_exa2fa_get_string('settings_a2fa_requirement_disabled_user_can_activate'),
			'a2fa_timeout' => block_exa2fa_get_string('settings_a2fa_requirement_a2fa_timeout'),
		];
		$settings->add(new admin_setting_configselect('exa2fa/a2fa_required_for_block_exacomp', block_exa2fa_get_string('settings_a2fa_required_for_block_exacomp'), '', '', $a2fa_requirement));
	}
	//if (class_exists('\block_exaport\api')) {
	//	$settings->add(new admin_setting_configselect('exa2fa/a2fa_required_for_block_exaport', block_exa2fa_get_string('settings_a2fa_required_for_block_exaport'), '', '', $a2fa_requirement));
	//}
}
