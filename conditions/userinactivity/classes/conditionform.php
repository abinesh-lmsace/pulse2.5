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
 * Conditions - Pulse condition class for "User inactivity".
 *
 * @package   pulsecondition_userinactivity
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_userinactivity;

use mod_pulse\automation\condition_base;

/**
 * Automation condition form for user inactivity.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Condition disabled.
     */
    const DISABLED = 0;

    /**
     * Inactivity based on course access.
     */
    const INACTIVITY_ACCESS = 'inactivity_access';

    /**
     * Inactivity based on activity completion.
     */
    const INACTIVITY_COMPLETION = 'inactivity_completion';

    /**
     * Inactivity based on activity completion conditions.
     */
    const INACTIVITY_COMPLETION_CONDITIONS = 'inactivity_completion_conditions';

    /**
     * All activities included.
     */
    const ACTIVITIES_ALL = 1;

    /**
     * Only completion relevant activities.
     */
    const ACTIVITIES_COMPLETION_RELEVANT = 2;


     /**
     * Verify if the course start or end date has been reached.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if condition is met, false otherwise.
     */
    public function is_user_completed($instancedata, int $userid, ?\completion_info $completion = null) {
        global $DB;
        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        $triggercondition = $instancedata->condition['userinactivity'];
        if (!isset($triggercondition['type']) ||
            $triggercondition['type'] == self::DISABLED) {
            return false;
        }

        $type = $triggercondition['type'];
        $includedactivities = $triggercondition['includedactivities'] ?? self::ACTIVITIES_ALL;
        $inactivityperiod = $triggercondition['inactivityperiod'] ?? 0;
        $requirepreviousactivity = !empty($triggercondition['requirepreviousactivity']);
        $activityperiod = $triggercondition['activityperiod'] ?? 0;

        if ($inactivityperiod <= 0) {
            return false;
        }

        $currenttime = time();
        $inactivitythreshold = $currenttime - $inactivityperiod;

        // Check if user requires previous activity.
        if ($requirepreviousactivity && $activityperiod > 0) {
            $activitythreshold = $currenttime - $activityperiod;
            if (!$this->has_user_activity($userid, $course, $type, $includedactivities, $activitythreshold, $currenttime)) {
                return false; // User never had activity in the required period.
            }
        }

        // Check if user is currently inactive.
        return !$this->has_user_activity($userid, $course, $type, $includedactivities, $inactivitythreshold, $currenttime);
    }

    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['userinactivity'] = get_string('userinactivity', 'pulsecondition_userinactivity');
    }

    /**
     * Loads the form elements for user inactivity condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {
       $this->load_form($mform, $forminstance);
    }

    /**
     * Loads the form elements for user inactivity condition in template.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_template_form(&$mform, $forminstance) {
        $this->load_form($mform, $forminstance);
    }


    public function load_form(&$mform, $forminstance) {
        global $CFG;
         // Register custom duration form element.
        \MoodleQuickForm::registerElementType(
            'pulseconditionduration',
            $CFG->dirroot . '/mod/pulse/conditions/userinactivity/forms/duration.php',
            'moodlequickform_pulseconditionduration'
        );


        $mform->addElement('select', 'condition[userinactivity][status]', get_string('status', 'pulse'), $this->get_options());
        $mform->addHelpButton('condition[userinactivity][status]', 'userinactivity', 'pulsecondition_userinactivity');

        // User inactivity type.
        $inactivityoptions = [
            self::DISABLED => get_string('disabled', 'pulsecondition_userinactivity'),
            self::INACTIVITY_ACCESS => get_string('basedonaccess', 'pulsecondition_userinactivity'),
            self::INACTIVITY_COMPLETION => get_string('basedoncompletion', 'pulsecondition_userinactivity'),
            self::INACTIVITY_COMPLETION_CONDITIONS => get_string('basedoncompletionconditions', 'pulsecondition_userinactivity'),
        ];

        $mform->addElement(
            'select',
            'condition[userinactivity][type]',
            get_string('userinactivity', 'pulsecondition_userinactivity'),
            $inactivityoptions
        );
        $mform->addHelpButton('condition[userinactivity][type]', 'userinactivity', 'pulsecondition_userinactivity');
        $mform->hideIf('condition[userinactivity][type]', 'condition[userinactivity][status]', 'eq', self::DISABLED);
        // Included activities.
        $activityoptions = [
            self::ACTIVITIES_ALL => get_string('allactivities', 'pulsecondition_userinactivity'),
            self::ACTIVITIES_COMPLETION_RELEVANT => get_string('completionrelevantactivities', 'pulsecondition_userinactivity'),
        ];

        $mform->addElement(
            'select',
            'condition[userinactivity][includedactivities]',
            get_string('includedactivities', 'pulsecondition_userinactivity'),
            $activityoptions
        );
        $mform->addHelpButton('condition[userinactivity][includedactivities]', 'includedactivities', 'pulsecondition_userinactivity');
        $mform->hideIf('condition[userinactivity][includedactivities]', 'condition[userinactivity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[userinactivity][includedactivities]', 'condition[userinactivity][type]', 'eq', self::DISABLED);
        $mform->hideIf('condition[userinactivity][includedactivities]', 'condition[userinactivity][type]', 'eq', self::INACTIVITY_ACCESS);

        // Inactivity period.
        $mform->addElement(
            'pulseconditionduration',
            'condition[userinactivity][inactivityperiod]',
            get_string('inactivityperiod', 'pulsecondition_userinactivity')
        );
        $mform->addHelpButton('condition[userinactivity][inactivityperiod]', 'inactivityperiod', 'pulsecondition_userinactivity');
        $mform->hideIf('condition[userinactivity][inactivityperiod]', 'condition[userinactivity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[userinactivity][inactivityperiod]', 'condition[userinactivity][type]', 'eq', self::DISABLED);

        // Require previous activity.
        $mform->addElement(
            'selectyesno',
            'condition[userinactivity][requirepreviousactivity]',
            get_string('requirepreviousactivity', 'pulsecondition_userinactivity'),
            null, null, [0, 1]
        );
        $mform->addHelpButton('condition[userinactivity][requirepreviousactivity]', 'requirepreviousactivity', 'pulsecondition_userinactivity');
        $mform->hideIf('condition[userinactivity][requirepreviousactivity]', 'condition[userinactivity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[userinactivity][requirepreviousactivity]', 'condition[userinactivity][type]', 'eq', self::DISABLED);

        // Activity period.
        $mform->addElement(
            'pulseconditionduration',
            'condition[userinactivity][activityperiod]',
            get_string('activityperiod', 'pulsecondition_userinactivity')
        );
        $mform->addHelpButton('condition[userinactivity][activityperiod]', 'activityperiod', 'pulsecondition_userinactivity');
        $mform->hideIf('condition[userinactivity][activityperiod]', 'condition[userinactivity][requirepreviousactivity]', 'eq', 0);
        $mform->hideIf('condition[userinactivity][activityperiod]', 'condition[userinactivity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[userinactivity][activityperiod]', 'condition[userinactivity][type]', 'eq', self::DISABLED);

    }


    /**
     * Checks if user has activity based on the specified criteria.
     *
     * @param int $userid The user ID.
     * @param stdClass $course The course object.
     * @param int $type The inactivity type.
     * @param int $includedactivities Which activities to include.
     * @param int $fromtime The start time to check.
     * @param int $totime The end time to check.
     * @return bool True if user has activity, false otherwise.
     */
    protected function has_user_activity($userid, $course, $type, $includedactivities, $fromtime, $totime) {
        global $DB;
        //mtrace("Checking user activity for user $userid in course {$course->id}");
        switch ($type) {
            case self::INACTIVITY_ACCESS:
                return $this->has_course_access($userid, $course->id, $fromtime, $totime);

            case self::INACTIVITY_COMPLETION:
                return $this->has_activity_completion($userid, $course, $includedactivities, $fromtime, $totime);

            case self::INACTIVITY_COMPLETION_CONDITIONS:
                return $this->has_completion_conditions_met($userid, $course, $includedactivities, $fromtime, $totime);
        }

        return false;
    }

    /**
     * Checks if user has accessed the course in the given time period.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @param int $fromtime The start time.
     * @param int $totime The end time.
     * @return bool True if user accessed the course.
     */
    protected function has_course_access($userid, $courseid, $fromtime, $totime) {
        global $DB;
        //mtrace("Checking course access for user $userid in course $courseid");
        // Check in user last access table.
        $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        if ($lastaccess && $lastaccess >= $fromtime && $lastaccess <= $totime) {
            return true;
        }

        // Also check in log store if available.
        if (get_config('logstore_standard', 'logguests')) {
            $params = [
                'userid' => $userid,
                'courseid' => $courseid,
                'fromtime' => $fromtime,
                'totime' => $totime
            ];

            $sql = "SELECT COUNT(id) FROM {logstore_standard_log}
                    WHERE userid = :userid AND courseid = :courseid 
                    AND timecreated >= :fromtime AND timecreated <= :totime";

            return $DB->count_records_sql($sql, $params) > 0;
        }

        return false;
    }

    /**
     * Checks if user has completed activities in the given time period.
     *
     * @param int $userid The user ID.
     * @param stdClass $course The course object.
     * @param int $includedactivities Which activities to include.
     * @param int $fromtime The start time.
     * @param int $totime The end time.
     * @return bool True if user completed activities.
     */
    protected function has_activity_completion($userid, $course, $includedactivities, $fromtime, $totime) {
        global $DB;
        //mtrace("Checking activity completion for user $userid in course {$course->id}");
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return true;
        }

        $activities = $this->get_relevant_activities($course, $includedactivities);
        if (empty($activities)) {
            return true;
        }

        $activityids = array_keys($activities);
        [$insql, $inparams] = $DB->get_in_or_equal($activityids, SQL_PARAMS_NAMED);

        $params = array_merge([
            'userid' => $userid,
            'fromtime' => $fromtime,
            'totime' => $totime
        ], $inparams);

        $sql = "SELECT COUNT(id) FROM {course_modules_completion}
                WHERE userid = :userid AND coursemoduleid $insql
                AND completionstate > 0
                AND timemodified >= :fromtime AND timemodified <= :totime";

        return $DB->count_records_sql($sql, $params) > 0;
    }

    /**
     * Checks if user has met completion conditions in the given time period.
     *
     * @param int $userid The user ID.
     * @param stdClass $course The course object.
     * @param int $includedactivities Which activities to include.
     * @param int $fromtime The start time.
     * @param int $totime The end time.
     * @return bool True if user met completion conditions.
     */
    protected function has_completion_conditions_met($userid, $course, $includedactivities, $fromtime, $totime) {
        global $DB;

        $activities = $this->get_relevant_activities($course, $includedactivities);
        if (empty($activities)) {
            return true;
        }
        //$userid = 3;
        foreach ($activities as $cm) {
            $completioninfo = new \completion_info($course);
            $completiondata = new \core_completion\cm_completion_details($completioninfo, $cm, $userid, true);
            if (!$completiondata->is_automatic()) {
                $criteriarecord = $DB->get_record('course_completion_criteria', [
                    'course' => $course->id,
                    'moduleinstance' => $cm->id,
                    'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY
                ]);
                if ($criteriarecord) {
                    $record = $DB->get_record('course_completion_crit_compl', [
                        'criteriaid' => $criteriarecord->id,
                        'userid' => $userid,
                        'course' => $course->id,
                    ]);
                    if ($record &&
                        $record->timecompleted >= $fromtime &&
                        $record->timecompleted <= $totime) {
                        return true;
                    }
                }
            } else {
                return false;
                /* foreach ($completiondata->get_details() as $key => $detail) {
                    $statuscomplete = in_array($detail->status, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
                    if ($statuscomplete) {
                        // Check if the action for this condition happened in the specified time period
                        //if ($this->check_condition_activity_in_logs($userid, $course->id, $cm->id, $key, $fromtime, $totime)) {
                            //return true;
                        //}
                    }
                } */
            }
        }

        return false;
    }
    /**
     * Gets relevant activities based on the inclusion criteria.
     *
     * @param stdClass $course The course object.
     * @param int $includedactivities Which activities to include.
     * @return array Array of course modules.
     */
    protected function get_relevant_activities($course, $includedactivities) {
        global $DB;
        $completion = new \completion_info($course);
        $activities = $completion->get_activities();

        if ($includedactivities == self::ACTIVITIES_COMPLETION_RELEVANT) {
            // Get activities that are part of course completion criteria.
            $sql = "SELECT DISTINCT cc.moduleinstance, cc.module
                    FROM {course_completion_criteria} cc
                    WHERE cc.course = :courseid
                    AND cc.criteriatype = :criteriatype";

            $params = [
                'courseid' => $course->id,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            ];

            $completioncriteria = $DB->get_records_sql($sql, $params);

            // Create a lookup array for faster checking
            $criteriaactivities = [];
            foreach ($completioncriteria as $criteria) {
                $criteriaactivities[$criteria->moduleinstance] = true;
            }

            // Filter activities to only include those that are part of course completion
            $activities = array_filter($activities, function($cm) use ($criteriaactivities, $completion) {
                // Check if completion is enabled for this activity
                if (!$completion->is_enabled($cm) || $cm->completion <= 0) {
                    return false;
                }

                // Check if this activity is in the course completion criteria
                return isset($criteriaactivities[$cm->id]);
            });
        }
        return $activities;
    }
}