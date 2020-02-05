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

return [
	// shown in admin plugin list
	'pluginname' => [
		'A2FA (Another 2-Factor Auth)',
		'A2FA (Another 2-Factor Auth)',
	],
	// shown in block title and all headers
	'blocktitle' => [
		'A2FA (Another 2-Factor Auth)',
		'A2FA (Another 2-Factor Auth)',
	],

	'exa2fa:addinstance' => [
		'Exa2fa auf Kursseite anlegen',
		'Exa2fa auf Kursseite anlegen',
	],

	'exa2fa:myaddinstance' => [
		'Exa2fa auf Startseite anlegen',
		'Exa2fa auf Startseite anlegen',
	],

	'settings_a2fa_timeout' => [
		'Timeout',
		'Timeout',
	],

	'settings_a2fa_timeout_description' => [
		'Timeout in Sekunden für 2FA in den Exabis Blöcken',
		'2FA timeout in seconds for Exabis Blocks',
	],
	'settings_a2fa_requirement_def' => [
		'2FA deaktiviert (kann vom Benutzer selbst aktiviert werden)',
		'2FA deactivated (no two-factor-authentication required)',
	],
	'settings_a2fa_requirement_a2fa_timeout' => [
		'2FA für Block zwingend aktivieren',
		'2FA for this block required',
	],

	'settings_a2fa_required_for_block_exastud' => [
		'Lernentwicklungsbericht',
		'Require 2FA in Exabis Student Review',
	],
	'settings_a2fa_required_for_block_exacomp' => [
		'Kompetenzraster',
		'Require 2FA in Exabis Copetence Grid',
	],
	'settings_a2fa_required_for_block_exaport' => [
		'ePortfolio',
		'Require 2FA in Exabis ePortfolio',
	],
];
