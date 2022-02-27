<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Webservice functions and service file.
 *
 * @package   local_practicalgrader
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_practicalgrader_save' => array(
        'classname'   => '\local_practicalgrader\external\practicalgrader',
        /* 'classpath': old style – if a non-autoloading namespaced classname is defined,
         this is the path to the class file and, if that is not defined, externallib.php is used */
        'methodname'  => 'save_practicalgrader_grade',
        'description' => 'Saves an practical activity feedback and grade',
        'type'        => 'write',
        // 'ajax' - true or false depending on whether the webservice function is callable via ajax //
        // 'capabilities' - an array of capabilities required by the function
        /* 'services' - optional since Moodle 3.1 – an array of built-in services (by shortname)
         where the function will be included */
    ),
);

$services = array(
    'Practical_Grader' => array(
        'functions' => array('local_practicalgrader_save'), //an array of the defined functions attached to this service
        'restrictedusers' => 0, //if enabled, the Moodle administrator must link some user to this service
        'requiredcapability' => 'local/practicalgrader:grade',  // the webservice user needs this capability to access
        'enabled' => 1, // Default enabled.
        'shortname' => 'Practical_Grades' // optional, but needed if restrictedusers is set to allow logins
        // 'downloadfiles' - true/false – allow file downloads
        // 'uploadfiles' - true/false – allow file uploads
    )
);

