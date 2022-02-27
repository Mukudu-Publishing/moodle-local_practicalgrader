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
 * External API class file.
 *
 * @package   local_practicalgrader
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_practicalgrader\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

/**
 * The practicalgrader class.
 *
 * @package   local_practicalgrader
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class practicalgrader extends \external_api {

    /**
     * The return values from this webservice call.
     *
     * @return \external_value
     */
    public static function save_practicalgrader_grade_returns() {
        return new \external_value(PARAM_TEXT, 'result of operation');
    }

    /**
     * The parameters expected for the webservice call.
     *
     * @return \external_function_parameters
     */
    public static function save_practicalgrader_grade_parameters() {
        return new \external_function_parameters( array(
                'activityidnumber' => new \external_value(PARAM_ALPHANUMEXT, 'activity idnumber'),
                'studentemail' => new \external_value(PARAM_EMAIL, 'student email address'),
                'activitygrade' => new \external_value(PARAM_RAW, 'activity grade'),
            )
        );
    }

    /**
     *  The actual working code for the webservice call.
     *
     * @param string $activityidnumber - the activity to be graded's idnumber value.
     * @param string $studentemail - the student's email address to identify the user being graded.
     * @param mixed $activitygrade - the grade to be stored.
     * @return string OK - errors are thrown as Exceptions.
     */
    public static function save_practicalgrader_grade($activityidnumber, $studentemail, $activitygrade) {
        global $DB, $USER;

        // Validate the inputs.
        $params = self::validate_parameters(self::save_practicalgrader_grade_parameters(),
            array(
                'activityidnumber' => $activityidnumber,
                'studentemail' => $studentemail,
                'activitygrade' => $activitygrade
            )
        );

        // Identify the activity.
        $activitymodule = $DB->get_record('course_modules', array('idnumber' => $params['activityidnumber']), 'id, idnumber');
        if ($activitymodule) {
            list ($course, $cm) = get_course_and_cm_from_cmid($activitymodule->id);
            $module = $cm->modname;

            if (plugin_supports('mod', $module, FEATURE_GRADE_HAS_GRADE)) {

                // Check the user has grading capabilities.
                $context = \context_module::instance($cm->id);
                $capability = 'mod/' . $module . ':grade';
                // Some activities do not have grade caps.
                if (get_capability_info($capability)) {
                    if (!has_capability($capability, $context)) {
                        print_error('errornocapability', 'local_practicalgrader', null,
                            (object) array('username' => $USER->username, 'capability' => $capability));
                    }
                } else {
                    // Check for a cap that tutors have.
                    if (!has_capability( 'moodle/course:markcomplete', \context_course::instance($course->id))) {
                        print_error('errornocapability', 'local_practicalgrader', null,
                            (object) array('username' => $USER->username, 'capability' => $capability));
                    }
                }

                $activity = $DB->get_record($module, array('id' => $cm->instance));
                // Add the required additional field.
                $activity->cmidnumber = $activitymodule->idnumber;

                // Identify the student user and determine if enrolled on course.
                if ($students = search_users($course->id, 0, $params['studentemail'])) {
                    if (count($students) > 1) {
                        print_error('errortoomanyusers', 'local_practicalgrader');
                    }
                    $student = reset($students);
                    $studentid = $student->id;
                } else {
                    print_error('errornocourseuser', 'local_practicalgrader', $params['studentemail']);
                }

                // That should have loaded the module.
                $function = $module . '_grade_item_update';

                $grades = array(
                    'userid' => $studentid,
                    'rawgrade' => $params['activitygrade'],
                    'usermodified' => $USER->id,
                    'datesubmitted' => '',
                    'dategraded' => time()
                );

                // Add the grade to the grade book.

                // There appears to be a problem sometimes with some output from the gradebook uses ...
                // ... mtrace to output text - new grades?
                ob_start();
                $result = $function($activity, $grades);
                ob_end_clean();

                switch ($result)  {
                    case GRADE_UPDATE_FAILED :
                        return get_string('errorgradeupdate', 'local_practicalgrader');
                        break;
                    case GRADE_UPDATE_ITEM_LOCKED :
                        return get_string('errorgradelocked', 'local_practicalgrader');
                        break;
                    case GRADE_UPDATE_MULTIPLE :
                        return get_string('errorgrademultiple', 'local_practicalgrader');
                        break;
                }

            } else {
                print_error('errornomodulegrades', 'local_practicalgrader');
            }
        } else {
            print_error('errornoactivity', 'local_practicalgrader');
        }

        return 'OK';
    }
}