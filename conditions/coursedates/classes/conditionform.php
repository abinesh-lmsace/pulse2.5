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
 * Conditions - Pulse condition class for the "Course dates".
 *
 * @package   pulsecondition_coursedates
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_coursedates;

/**
 * Automation course dates condition form.
 */
class conditionform extends \mod_pulse\automation\condition_base {
    /**
     * Verify if the course start or end date has been reached.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if condition is met, false otherwise.
     */
    public function is_user_completed($instancedata, int $userid, ?\completion_info $completion = null) {
        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        $datetype = $instancedata->condition['coursedates']['type'] ?? 'start';

        // Get the target date based on type.
        if ($datetype === 'end') {
            $targetdate = $course->enddate;
            if (!$targetdate) {
                return false;
            }
        } else {
            $targetdate = $course->startdate;
            if (!$targetdate) {
                return false;
            }
        }

        $currenttime = time();

        // Check if the target date has been reached.
        if ($currenttime >= $targetdate) {
            return true;
        }

        return false;
    }

    /**
     * Get the course date (start or end) for a specific course.
     *
     * @param object $instancedata The instance data
     * @return int|false The course date timestamp or false if not available
     */
    public function get_course_date($instancedata) {
        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        // Get the date type (start or end) from condition data.
        $datetype = $instancedata->condition['coursedates']['type'] ?? 'start';

        if ($datetype === 'end') {
            return $course->enddate ?: false;
        } else {
            return $course->startdate ?: false;
        }
    }

    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['coursedates'] = get_string('coursedates', 'pulsecondition_coursedates');
    }

    /**
     * Loads the form elements for course dates condition in template.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_template_form(&$mform, $forminstance) {
        $coursedatestr = get_string('coursedates', 'pulsecondition_coursedates');
        $mform->addElement('select', 'condition[coursedates][status]', $coursedatestr, $this->get_options());
        $mform->addHelpButton('condition[coursedates][status]', 'coursedates', 'pulsecondition_coursedates');
    }

    /**
     * Loads the form elements for course dates condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {
        $coursedatestr = get_string('coursedates', 'pulsecondition_coursedates');

        $mform->addElement('select', 'condition[coursedates][status]', $coursedatestr, $this->get_options());
        $mform->addHelpButton('condition[coursedates][status]', 'coursedates', 'pulsecondition_coursedates');

        $mform->addElement('select', 'condition[coursedates][type]', get_string('coursedates_type', 'pulsecondition_coursedates'),
            [
                'start' => get_string('coursedates_start', 'pulsecondition_coursedates'),
                'end' => get_string('coursedates_end', 'pulsecondition_coursedates'),
            ]
        );

        $mform->hideIf('condition[coursedates][type]', 'condition[coursedates][status]', 'eq', self::DISABLED);
    }

    public function schedule_where() {
        $where = 'AND (c.enddate = 0 OR c.enddate <> 0)';
        return $where;
    }
}
