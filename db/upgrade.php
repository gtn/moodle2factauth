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

require_once __DIR__.'/../inc.php';



function xmldb_block_exa2fa_upgrade($oldversion) {
	global $DB, $CFG;
	$dbman = $DB->get_manager();
	$return_result = true;

    if ($oldversion < 2020012700) {

        // Define field lasttokens to be added to block_exa2fauser.
        $table = new xmldb_table('block_exa2fauser');
        $field = new xmldb_field('lasttokens', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secret');

        // Conditionally launch add field lasttokens.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Exa2fa savepoint reached.
        upgrade_block_savepoint(true, 2020012700, 'exa2fa');
    }

	return $return_result;
}
