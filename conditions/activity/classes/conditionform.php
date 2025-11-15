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
 * Conditions - Pulse condition class for the "Activity Completion".
 *
 * @package   pulsecondition_activity
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_activity;

use mod_pulse\automation\condition_base;

/**
 * Automation condition form for acitivty completion.
 */
class conditionform extends \mod_pulse\automation\condition_base {
    /**
     * Activity completion method: Count
     */
    public const ACTVITY_COMPLETION_METHOD_COUNT = 1;

    /**
     * Activity completion method: selectactivity
     */
    public const ACTVITY_COMPLETION_METHOD_SELECTACTIVITY = 2;

    /**
     * Completion status: Completed (all completion conditions met).
     */
    public const COMPLETION_STATUS_COMPLETED = 1;

    /**
     * Completion status: Partially completed (at least one but not all completion conditions met)
     */
    public const COMPLETION_STATUS_PARTIAL = 2;

    /**
     * Completion status: Failed (completed but without passing grade)
     */
    public const COMPLETION_STATUS_FAILED = 3;

    /**
     * Completion status: Passed (completed with passing grade)
     */
    public const COMPLETION_STATUS_PASSED = 4;

    /**
     * Activities operator: ANY (any of the selected activities)
     */
    public const ACTIVITY_OPERATOR_ANY = 1;

    /**
     * Activities operator: ALL (all of the selected activities)
     */
    public const ACTIVITY_OPERATOR_ALL = 2;

    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['activity'] = get_string('activitycompletion', 'pulsecondition_activity');
    }

    /**
     * Loads the form elements for activity condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('activitycompletion', 'pulsecondition_activity');
        $mform->addElement('select', 'condition[activity][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[activity][status]', 'activitycompletion', 'pulsecondition_activity');
        $courseid = $forminstance->get_customdata('courseid') ?? '';

        // Include the activity settings for the instance.
        $completion = new \completion_info(get_course($courseid));
        $activities = $completion->get_activities();
        array_walk($activities, function (&$value) {
            $value = format_string($value->name);
        });

        $modules = $mform->addElement(
            'autocomplete',
            'condition[activity][modules]',
            get_string('selectactivity', 'pulsecondition_activity'),
            $activities
        );
        $modules->setMultiple(true);
        $mform->hideIf('condition[activity][modules]', 'condition[activity][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[activity][modules]', 'selectactivity', 'pulsecondition_activity');

        // Activity completion type selection.
        $completiontypeoptions = [
            self::ACTVITY_COMPLETION_METHOD_COUNT => get_string('acompletionmethod_count', 'pulsecondition_activity'),
            self::ACTVITY_COMPLETION_METHOD_SELECTACTIVITY => get_string('acompletionmethod_selectactivity',
                'pulsecondition_activity'),
        ];
        $completiontypestr = get_string('acompletionmethod', 'pulsecondition_activity');
        $mform->addElement('select', 'condition[activity][acompletionmethod]', $completiontypestr,
            $completiontypeoptions);
        $mform->addHelpButton('condition[activity][acompletionmethod]', 'acompletionmethod',
            'pulsecondition_activity');
        $mform->hideIf('condition[activity][acompletionmethod]', 'condition[activity][status]', 'eq', self::DISABLED);

        // Activity count field.
        $mform->addElement('text', 'condition[activity][activitycount]',
            get_string('activitycount', 'pulsecondition_activity'));
        $mform->setType('condition[activity][activitycount]', PARAM_INT);
        $mform->addHelpButton('condition[activity][activitycount]', 'activitycount', 'pulsecondition_activity');
        $mform->hideIf('condition[activity][activitycount]', 'condition[activity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[activity][activitycount]', 'condition[activity][acompletionmethod]', 'eq',
            self::ACTVITY_COMPLETION_METHOD_SELECTACTIVITY);

        // Activities operator (ANY or ALL).
        $operatoroptions = [
            self::ACTIVITY_OPERATOR_ANY => get_string('activityoperator_any', 'pulsecondition_activity'),
            self::ACTIVITY_OPERATOR_ALL => get_string('activityoperator_all', 'pulsecondition_activity'),
        ];
        $mform->addElement('select', 'condition[activity][activityoperator]',
            get_string('activityoperator', 'pulsecondition_activity'), $operatoroptions);
        $mform->addHelpButton('condition[activity][activityoperator]', 'activityoperator', 'pulsecondition_activity');
        $mform->hideIf('condition[activity][activityoperator]', 'condition[activity][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[activity][activityoperator]', 'condition[activity][acompletionmethod]', 'eq',
            self::ACTVITY_COMPLETION_METHOD_COUNT);

        // Completion status selection.
        $completionstatusoptions = [
            self::COMPLETION_STATUS_COMPLETED => get_string('completionstatus_completed', 'pulsecondition_activity'),
            self::COMPLETION_STATUS_PARTIAL => get_string('completionstatus_partial', 'pulsecondition_activity'),
            self::COMPLETION_STATUS_FAILED => get_string('completionstatus_failed', 'pulsecondition_activity'),
            self::COMPLETION_STATUS_PASSED => get_string('completionstatus_passed', 'pulsecondition_activity'),
        ];
        $mform->addElement('select', 'condition[activity][completionstatus]',
            get_string('completionstatus', 'pulsecondition_activity'), $completionstatusoptions);
        $mform->addHelpButton('condition[activity][completionstatus]', 'completionstatus', 'pulsecondition_activity');
        $mform->hideIf('condition[activity][completionstatus]', 'condition[activity][status]', 'eq', self::DISABLED);

        // Enable the override by default to prevent adding overdide checkbox.
        $mform->addElement('hidden', 'override[condition_activity_modules]', 1);
        $mform->setType('override[condition_activity_modules]', PARAM_RAW);
    }

    /**
     * Loads the form elements for activity condition in template.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_template_form(&$mform, $forminstance) {
        global $PAGE;

        $completionstr = get_string('activitycompletion', 'pulsecondition_activity');
        $mform->addElement('select', 'condition[activity][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[activity][status]', 'activitycompletion', 'pulsecondition_activity');

        $mform->addElement(
            'static',
            'condition[activity][acompletionmethod]',
            get_string('acompletionmethod', 'pulsecondition_activity'),
            get_string('acompletionmethod_help', 'pulsecondition_activity')
        );

        $mform->addElement(
            'static',
            'condition[activity][activitycount]',
            get_string('activitycount', 'pulsecondition_activity'),
            get_string('activitycount_help', 'pulsecondition_activity')
        );

        $mform->addElement(
            'static',
            'condition[activity][activityoperator]',
            get_string('activityoperator', 'pulsecondition_activity'),
            get_string('activityoperator_help', 'pulsecondition_activity')
        );

        $mform->addElement(
            'static',
            'condition[activity][modules]',
            get_string('selectactivity', 'pulsecondition_activity'),
            get_string('selectactivity_help', 'pulsecondition_activity')
        );

        $mform->addElement(
            'static',
            'condition[activity][completionstatus]',
            get_string('completionstatus', 'pulsecondition_activity'),
            get_string('completionstatus_help', 'pulsecondition_activity')
        );
    }

    /**
     * Checks if the user has completed the specified activities.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if completed, false otherwise.
     */
    public function is_user_completed($instancedata, $userid, ?\completion_info $completion = null) {

        // Get the activity condition configuration.
        $additional = $instancedata->condition['activity'] ?? [];
        $completiontype = $additional['acompletionmethod'] ?? self::ACTVITY_COMPLETION_METHOD_SELECTACTIVITY;
        $completionstatus = $additional['completionstatus'] ?? self::COMPLETION_STATUS_COMPLETED;
        $modules = $additional['modules'] ?? [];

        if ($completion === null) {
            $completion = new \completion_info(get_course($instancedata->courseid));
        }

        $fastmodinfo = get_fast_modinfo($instancedata->courseid);

        // Activity Count method.
        if ($completiontype == self::ACTVITY_COMPLETION_METHOD_COUNT) {
            $activitycount = $additional['activitycount'] ?? 1;
            return $this->check_activity_count_completion($instancedata->courseid, $userid, $activitycount,
                $completionstatus, $completion, $fastmodinfo, $modules);
        }

        // Selected Activities method.
        if ($completiontype == self::ACTVITY_COMPLETION_METHOD_SELECTACTIVITY) {
            $activityoperator = $additional['activityoperator'] ?? self::ACTIVITY_OPERATOR_ALL;

            if (empty($modules)) {
                return false;
            }

            return $this->check_selected_activities_completion($modules, $userid, $activityoperator,
                $completionstatus, $completion, $fastmodinfo);
        }

        return false;
    }

    /**
     * Check if the user has completed the required number of activities.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @param int $requiredcount The number of activities required.
     * @param int $completionstatus The completion status required.
     * @param \completion_info $completion The completion information.
     * @param \course_modinfo $fastmodinfo Fast modinfo object.
     * @param array $modules Array of course module IDs to check. If empty, check all activities.
     * @return bool True if the count is met, false otherwise.
     */
    protected function check_activity_count_completion($courseid, $userid, $requiredcount, $completionstatus,
        $completion, $fastmodinfo, $modules = []) {

        if (empty($modules)) {
            return false;
        }

        $completedcount = 0;

        // Only check the selected modules.
        foreach ($modules as $cmid) {
            $cminfo = get_coursemodule_from_id('', $cmid);
            if (!$cminfo) {
                continue;
            }
            $cminfo = $fastmodinfo->get_cm($cminfo->id);

            if ($this->check_activity_completion_status($cminfo, $userid, $completionstatus, $completion)) {
                $completedcount++;
            }
        }

        // Check if the completed count meets or exceeds the required count.
        return $completedcount >= $requiredcount;
    }

    /**
     * Check if the selected activities meet the completion criteria.
     *
     * @param array $modules Array of course module IDs.
     * @param int $userid The user ID.
     * @param int $activityoperator The operator (ANY or ALL).
     * @param int $completionstatus The completion status required.
     * @param \completion_info $completion The completion information.
     * @param \course_modinfo $fastmodinfo Fast modinfo object.
     * @return bool True if criteria met, false otherwise.
     */
    protected function check_selected_activities_completion($modules, $userid, $activityoperator, $completionstatus,
        $completion, $fastmodinfo) {

        $completedcount = 0;

        foreach ($modules as $cmid) {
            $cminfo = get_coursemodule_from_id('', $cmid);
            if (!$cminfo) {
                continue;
            }
            $cminfo = $fastmodinfo->get_cm($cminfo->id);

            if ($this->check_activity_completion_status($cminfo, $userid, $completionstatus, $completion)) {
                $completedcount++;

                // If operator is ANY, return true on first match.
                if ($activityoperator == self::ACTIVITY_OPERATOR_ANY) {
                    return true;
                }
            }
        }

        // For ALL operator, check if all modules are completed.
        if ($activityoperator == self::ACTIVITY_OPERATOR_ALL) {
            return $completedcount == count($modules);
        }

        return false;
    }

    /**
     * Check if an activity meets the specified completion status.
     *
     * @param \cm_info $cminfo The course module info.
     * @param int $userid The user ID.
     * @param int $completionstatus The completion status to check.
     * @param \completion_info $completion The completion information.
     * @return bool True if the status matches, false otherwise.
     */
    protected function check_activity_completion_status($cminfo, $userid, $completionstatus, $completion) {

        $completiondata = $completion->get_data($cminfo, true, $userid);

        // Get completion details if available (Moodle 4.0+).
        $cmcompletion = null;
        if (class_exists('\core_completion\cm_completion_details')) {
            $cmcompletion = \core_completion\cm_completion_details::get_instance($cminfo, $userid);
        }

        switch ($completionstatus) {
            case self::COMPLETION_STATUS_COMPLETED:
                // All completion conditions must be met.
                return $this->is_activity_fully_completed($cminfo, $userid, $completiondata, $cmcompletion);

            case self::COMPLETION_STATUS_PARTIAL:
                // At least one but not all completion conditions met.
                return $this->is_activity_partially_completed($cminfo, $userid, $completiondata, $cmcompletion);

            case self::COMPLETION_STATUS_FAILED:
                // Activity completed but failed.
                return $completiondata->completionstate == COMPLETION_COMPLETE_FAIL;

            case self::COMPLETION_STATUS_PASSED:
                // Activity completed with passing grade.
                return $completiondata->completionstate == COMPLETION_COMPLETE_PASS;

            default:
                return false;
        }
    }

    /**
     * Check if an activity is fully completed (all conditions met).
     *
     * @param \cm_info $cminfo The course module info.
     * @param int $userid The user ID.
     * @param \stdClass $completiondata The completion data.
     * @param \core_completion\cm_completion_details|null $cmcompletion The completion details.
     * @return bool True if fully completed, false otherwise.
     */
    protected function is_activity_fully_completed($cminfo, $userid, $completiondata, $cmcompletion) {

        if (in_array($completiondata->completionstate, [
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS,
            COMPLETION_COMPLETE_FAIL,
        ])) {
            return true;
        }

        if ($cmcompletion && method_exists($cmcompletion, 'get_details')) {
            $details = $cmcompletion->get_details();
            if (!empty($details)) {
                // Check if all conditions are completed.
                $allcomplete = true;
                foreach ($details as $detail) {
                    if ($detail->status != COMPLETION_COMPLETE) {
                        $allcomplete = false;
                        break;
                    }
                }
                if ($allcomplete) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if an activity is partially completed.
     *
     * @param \cm_info $cminfo The course module info.
     * @param int $userid The user ID.
     * @param \stdClass $completiondata The completion data.
     * @param \core_completion\cm_completion_details|null $cmcompletion The completion details.
     * @return bool True if partially completed, false otherwise.
     */
    protected function is_activity_partially_completed($cminfo, $userid, $completiondata, $cmcompletion) {

        // Partial completion only applies to activities with automatic completion and multiple conditions.
        if (!$cmcompletion || !method_exists($cmcompletion, 'get_details')) {
            return false;
        }

        $details = $cmcompletion->get_details();
        if (empty($details)) {
            return false;
        }

        if (in_array($completiondata->completionstate, [
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS,
            COMPLETION_COMPLETE_FAIL,
        ])) {
            return false;
        }

        // Count completed and total conditions.
        $completedconditions = 0;
        $totalconditions = count($details);

        foreach ($details as $detail) {
            if ($detail->status == COMPLETION_COMPLETE) {
                $completedconditions++;
            }
        }

        return $completedconditions > 0 && $completedconditions < $totalconditions;
    }

    /**
     * Module completed.
     *
     * @param stdclass $eventdata
     * @return bool
     */
    public static function module_completed($eventdata) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/completionlib.php');
        // Event data.
        $data = $eventdata->get_data();
        // Get the info for the context.
        [$context, $course, $cm] = get_context_info_array($data['contextid']);

        if ($eventdata instanceof \core\event\user_graded && $eventdata->get_grade()->grade_item->itemtype == 'mod') {
            // Get the module id.
            $gradeitem = $eventdata->get_grade()->grade_item;
            $cm = get_coursemodule_from_instance($gradeitem->itemmodule, $gradeitem->iteminstance);
            if (empty($cm)) {
                return false;
            }
            $cmid = $cm->id;
        } else {
            $cmid = $data['contextinstanceid'];
        }

        $userid = $data['relateduserid'];

        // Self condition instance.
        $condition = new self();
        $completion = new \completion_info($course);

        $notifications = self::get_acitivty_notifications($cmid);

        foreach ($notifications as $notification) {
            $additional = isset($notification->additional) ? json_decode($notification->additional, true) : '';
            $modules = $additional['modules'] ?? [];

            if (!empty($modules)) {
                $instance = \mod_pulse\automation\instances::create($notification->instanceid);
                $instancedata = $instance->get_instance_data();
                if ($condition->is_user_completed($instancedata, $userid, $completion)) {
                    $condition->trigger_instance($notification->instanceid, $userid);
                }
            }
        }

        return true;
    }

    /**
     * Fetch the list of menus which is used the triggered ID in the access rules for the given method.
     *
     * Find the menus which contains the given ID in the access rule (Role or cohorts).
     *
     * @param int $id ID of the triggered method, Role or cohort id.
     * @return array
     */
    public static function get_acitivty_notifications($id) {
        global $DB;

        $like = $DB->sql_like('co.additional', ':value'); // Like query to fetch the instances assigned this module.

        $sql = "SELECT *, ai.id as id, ai.id as instanceid FROM {pulse_autoinstances} ai
            JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
            LEFT JOIN {pulse_condition} c ON c.templateid = ai.templateid AND c.triggercondition = 'activity'
            LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'activity'
            WHERE $like AND (co.status > 0 OR (co.status IS NULL AND c.status > 0))";

        $params = ['activity' => '%"activity"%', 'value' => '%"' . $id . '"%'];

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }
}
