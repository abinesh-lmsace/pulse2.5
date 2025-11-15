<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Conditions - Pulse condition class for the "Course Due Date".
 *
 * @package   pulsecondition_courseduedate
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulsecondition_courseduedate;

use mod_pulse\automation\condition_base;

/**
 * Pulse automation conditions form and basic details.
 */
class conditionform extends \mod_pulse\automation\condition_base {
    /**
     * Verify if the user has reached the course due date condition.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if condition is met, false otherwise.
     */
    public function is_user_completed($instancedata, int $userid, ?\completion_info $completion = null) {
        global $DB;

        // Check if timetable tool is installed.
        $helper = \mod_pulse\automation\helper::create();
        if (!$helper->timetable_installed()) {
            return false;
        }

        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        // Get the course due date from timetable.
        $timecourse = $DB->get_record('tool_timetable_course', ['course' => $course->id]);
        if (!$timecourse) {
            return false;
        }

        $timemanagement = new \tool_timetable\time_management($timecourse->course);
        $usercourseenrollinfo = $timemanagement->get_course_user_enrollment($userid);

        if (empty($usercourseenrollinfo)) {
            return false;
        }

        $startdate = $usercourseenrollinfo[0]['timestart'] ?? 0;
        $enddate = $usercourseenrollinfo[0]['timeend'] ?? 0;
        $courseduedate = $timemanagement->calculate_course_duedate($startdate, $enddate, $userid);

        if (!$courseduedate) {
            return false;
        }

        $currenttime = time();

        if ($currenttime >= $courseduedate) {
            return true;
        }

        return false;
    }

    /**
     * Get the course due date for a specific user and course.
     *
     * @param object $instancedata The instance data
     * @param int $userid The user ID
     * @return int|false The course due date timestamp or false if not available
     */
    public function get_course_due_date($instancedata, $userid) {
        global $DB;

        // Check if timetable tool is installed.
        $helper = \mod_pulse\automation\helper::create();
        if (!$helper->timetable_installed()) {
            return false;
        }

        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        // Get the course due date from timetable.
        $timecourse = $DB->get_record('tool_timetable_course', ['course' => $course->id]);
        if (!$timecourse) {
            return false;
        }

        $timemanagement = new \tool_timetable\time_management($timecourse->course);
        $usercourseenrollinfo = $timemanagement->get_course_user_enrollment($userid);

        if (empty($usercourseenrollinfo)) {
            return false;
        }

        $startdate = $usercourseenrollinfo[0]['timestart'] ?? 0;
        $enddate = $usercourseenrollinfo[0]['timeend'] ?? 0;

        return $timemanagement->calculate_course_duedate($startdate, $enddate, $userid);
    }

    /**
     * Include data to action.
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['courseduedate'] = get_string('courseduedate', 'pulsecondition_courseduedate');
    }

    /**
     * Loads the form elements for activity condition in template.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_template_form(&$mform, $forminstance) {

        $completionstr = get_string('courseduedate', 'pulsecondition_courseduedate');

        $mform->addElement('select', 'condition[courseduedate][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[courseduedate][status]', 'courseduedate', 'pulsecondition_courseduedate');
    }

    /**
     * Loads the form elements for enrolment condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {
        $this->load_template_form($mform, $forminstance);
    }
}
