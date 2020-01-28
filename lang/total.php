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
		'Timeout in Sekunden für Zwei-Faktoren Authentifizierung in den Exabis Blöcken',
		'Two-factor-authentication timeout in seconds for Exabis Blocks',
	],

	'settings_a2fa_requirement' => [
		'Zwei-Faktoren-Authentifizierung im Lernentwicklungsbericht aktivieren',
		'Use two-factor-authentication',
	],
	'settings_a2fa_requirement_description' => [
		'Das 2-Faktoren-Authentifizierungs-Modul exa2fa ist nicht installiert',
		'Exa2fa Plugin is not installed',
	],
	'settings_a2fa_requirement_def' => [
		'Deaktiviert (Keine A2fa erforderlich)',
		'Deactivated (no two-factor-authentication required)',
	],
	'settings_a2fa_requirement_user_a2fa' => [
		'A2fa für Benutzer erforderlich (z.B. Lehrernetz)',
		'A2fa required for user',
	],
	'settings_a2fa_requirement_a2fa_timeout' => [
		'A2fa für Benutzer erforderlich und erneute A2fa für Block notwendig (z.B. päd. Netz)',
		'A2fa required for user - timeout',
	],

	'settings_a2fa_required_for_block_exastud' => [
		'A2fa im Lernentwicklungsbericht erforderlich',
		'Require A2fa in Exabis Student Review',
	],
	'settings_a2fa_required_for_block_exacomp' => [
		'A2fa im Kompetenzraster erforderlich',
		'Require A2fa in Exabis Copetence Grid',
	],
	'settings_a2fa_required_for_block_exaport' => [
		'A2fa im ePortfolio erforderlich',
		'Require A2fa in Exabis ePortfolio',
	],
];
