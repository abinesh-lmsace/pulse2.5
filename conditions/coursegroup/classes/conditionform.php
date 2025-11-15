<?php
// This file is part of Moodle - http://moodle.org/.
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
 * Pulse condition class for "Course group".
 *
 * @package   pulsecondition_coursegroup
 * @copyright 2025 bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_coursegroup;

use mod_pulse\automation\condition_base;

/**
 * Automation condition form for course group.
 */
class conditionform extends condition_base {
    /**
     * Include condition in dropdown.
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['coursegroup'] = get_string('coursegroup', 'pulsecondition_coursegroup');
    }

    /**
     * Loads the form elements for course group condition in instance.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {
        global $DB;

        $courseid = $forminstance->get_customdata('courseid') ?? 0;
        if (empty($courseid)) {
            return;
        }

        $groupstr = get_string('coursegroup', 'pulsecondition_coursegroup');
        $mform->addElement(
            'select',
            'condition[coursegroup][status]',
            $groupstr,
            $this->get_options()
        );
        $mform->addHelpButton('condition[coursegroup][status]', 'coursegroup', 'pulsecondition_coursegroup');

        $options = [
            'nogroup' => get_string('nogroup', 'pulsecondition_coursegroup'),
            'anygroup' => get_string('anygroup', 'pulsecondition_coursegroup'),
            'selectedgroups' => get_string('selectedgroups', 'pulsecondition_coursegroup'),
            'selectedgroupings' => get_string('selectedgroupings', 'pulsecondition_coursegroup'),
        ];

        $mform->addElement(
            'select',
            'condition[coursegroup][type]',
            get_string('type', 'pulsecondition_coursegroup'),
            $options
        );
        $mform->addHelpButton('condition[coursegroup][type]', 'type', 'pulsecondition_coursegroup');


        // Course groups.
        $groups = $DB->get_records_menu('groups', ['courseid' => $courseid], '', 'id, name');
        $groups = !empty($groups) ? $groups : [0 => get_string('nogroups', 'pulsecondition_coursegroup')];

        $groupselect = $mform->addElement(
            'autocomplete',
            'condition[coursegroup][groups]',
            get_string('selectgroups', 'pulsecondition_coursegroup'),
            $groups
        );
        $groupselect->setMultiple(true);
        $mform->addHelpButton('condition[coursegroup][groups]', 'selectgroups', 'pulsecondition_coursegroup');

        $mform->hideIf('condition[coursegroup][groups]', 'condition[coursegroup][type]', 'neq', 'selectedgroups');
        $mform->addElement('hidden', 'override[condition_coursegroup_groups]', 1);
        $mform->setType('override[condition_coursegroup_groups]', PARAM_RAW);

        // Course groupings.
        $groupings = $DB->get_records_menu('groupings', ['courseid' => $courseid], '', 'id, name');
        $groupings = !empty($groupings) ? $groupings : [0 => get_string('nogroupings', 'pulsecondition_coursegroup')];

        $groupingselect = $mform->addElement(
            'autocomplete',
            'condition[coursegroup][groupings]',
            get_string('selectgroupings', 'pulsecondition_coursegroup'),
            $groupings
        );
        $groupingselect->setMultiple(true);
        $mform->addHelpButton('condition[coursegroup][groupings]', 'selectgroupings', 'pulsecondition_coursegroup');

        $mform->hideIf('condition[coursegroup][groupings]', 'condition[coursegroup][type]', 'neq', 'selectedgroupings');
        $mform->addElement('hidden', 'override[condition_coursegroup_groupings]', 1);
        $mform->setType('override[condition_coursegroup_groupings]', PARAM_RAW);

        $mform->hideIf('condition[coursegroup][type]', 'condition[coursegroup][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[coursegroup][groups]', 'condition[coursegroup][status]', 'eq', self::DISABLED);
        $mform->hideIf('condition[coursegroup][groupings]', 'condition[coursegroup][status]', 'eq', self::DISABLED);


        $mform->addElement('hidden', 'override[condition_coursegroup]', 1);
        $mform->setType('override[condition_coursegroup]', PARAM_RAW);
    }

    /**
     * Loads the form elements for course group condition in template.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_template_form(&$mform, $forminstance) {


        $groupstr = get_string('coursegroup', 'pulsecondition_coursegroup');
        $mform->addElement(
            'select',
            'condition[coursegroup][status]',
            $groupstr,
            $this->get_options()
        );
        $mform->addHelpButton('condition[coursegroup][status]', 'coursegroup', 'pulsecondition_coursegroup');

        $options = [
            'nogroup' => get_string('nogroup', 'pulsecondition_coursegroup'),
            'anygroup' => get_string('anygroup', 'pulsecondition_coursegroup'),
            'selectedgroups' => get_string('selectedgroups', 'pulsecondition_coursegroup'),
            'selectedgroupings' => get_string('selectedgroupings', 'pulsecondition_coursegroup'),
        ];

        $mform->addElement(
            'select',
            'condition[coursegroup][type]',
            get_string('coursegroup', 'pulsecondition_coursegroup'),
            $options
        );
        $mform->addHelpButton('condition[coursegroup][type]', 'type', 'pulsecondition_coursegroup');
        $mform->hideIf('condition[coursegroup][type]', 'condition[coursegroup][status]', 'eq', self::DISABLED);
    }

    /**
     * Checks if the user meets the "Course group" condition.
     *
     * @param object $instancedata The instance data (form condition values)
     * @param int $userid The user ID to check
     * @param \completion_info|null $completion Optional completion info
     * @return bool True if the condition passes (user should get mail)
     */
    public function is_user_completed($instancedata, $userid, ?\completion_info $completion = null) {
        global $DB;

        // check if condition disabled.
        if (!isset($instancedata->condition['coursegroup']['status']) ||
            $instancedata->condition['coursegroup']['status'] == self::DISABLED) {
            return true;
        }

        //Course group.
        $type = $instancedata->condition['coursegroup']['type'] ?? '';
        $courseid = $instancedata->courseid ?? 0;
        if (empty($courseid)) {
            return true;
        }

        // Group belongs to the course.
        $sql = "SELECT gm.groupid
                  FROM {groups_members} gm
                  JOIN {groups} g ON g.id = gm.groupid
                 WHERE g.courseid = :courseid AND gm.userid = :userid";
        $params = ['courseid' => $courseid, 'userid' => $userid];
        $usergroups = $DB->get_fieldset_sql($sql, $params);

        // Nogroup.
        switch ($type) {
            case 'nogroup':
                return empty($usergroups);
            case 'anygroup':
                return !empty($usergroups);

            //Select groups.
            case 'selectedgroups':
                $selected = $instancedata->condition['coursegroup']['groups'] ?? [];
                $coursegroups = $DB->get_records('groups', ['courseid' => $courseid]);
                if (empty($coursegroups) || empty($selected)) {
                    return false;
                }
                foreach ($usergroups as $gid) {
                    if (in_array($gid, $selected)) {
                        return true;
                    }
                }
                return false;
            //Groupings.
            case 'selectedgroupings':

                $selectedgroupings = $instancedata->condition['coursegroup']['groupings'] ?? [];
                $coursegroupings = $DB->get_records('groupings', ['courseid' => $courseid]);                
                if (empty($selectedgroupings) || empty($coursegroupings)) {
                    return false; 
                }

                list($ingrpsql, $ingrpparams) = $DB->get_in_or_equal($selectedgroupings, SQL_PARAMS_NAMED);
                $sql = "SELECT groupid FROM {groupings_groups} WHERE groupingid $ingrpsql";
                $groupsingroupings = $DB->get_fieldset_sql($sql, $ingrpparams);

                if (empty($groupsingroupings)) {
                    return false;
                }

                foreach ($usergroups as $gid) {
                    if (in_array($gid, $groupsingroupings)) {
                        return true;
                    }
                }
                return false;

            default:
                return true;
            }
    }

      /**
     * Trigger when user added to a group.
     *
     * @param \core\event\group_member_added $eventdata
     */
    public static function member_added($eventdata) {
        global $DB;

        $data = $eventdata->get_data();
        $groupid = $data['objectid'];
        $userid = $data['relateduserid'];

        $sql = "SELECT ai.id AS instanceid
                  FROM {pulse_autoinstances} ai
                  JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
                  JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id
                 WHERE (co.status > 0 OR (co.status IS NULL AND ai.templateid IN (
                           SELECT c.templateid FROM {pulse_condition} c WHERE c.triggercondition = 'coursegroup'
                       )))
                   AND co.additional LIKE :value";
        $params = ['value' => '%"' . $groupid . '"%'];

        $instances = $DB->get_records_sql($sql, $params);

        $condition = new self();
        foreach ($instances as $instance) {
            $condition->trigger_instance($instance->instanceid, $userid);
        }
        return true;
    }

    /**
     * Trigger when user removed from a group.
     *
     * @param \core\event\group_member_removed $eventdata
     */
    public static function member_removed($eventdata) {
        return self::member_added($eventdata);
    }
}
