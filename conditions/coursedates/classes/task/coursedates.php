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
 * Course dates check scheduled task.
 *
 * @package   pulsecondition_coursedates
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_coursedates\task;

use pulseaction_notification\notification;

/**
 * Scheduled task to check course dates and trigger automation instances.
 */
class coursedates extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname', 'pulsecondition_coursedates');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting course dates automation check...');

        // Get all active automation instances that use course dates condition.
        $sql = "SELECT ai.*, ai.id as instanceid
                FROM {pulse_autoinstances} ai
                JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
                LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'coursedates'
                WHERE ai.status = 1
                AND (co.status > 0 OR (co.status IS NULL AND ai.templateid IN (
                    SELECT c.templateid FROM {pulse_condition} c WHERE c.triggercondition = 'coursedates' AND c.status > 0
                )))";

        $instances = $DB->get_records_sql($sql);

        if (empty($instances)) {
            mtrace('No active course dates automation instances found.');
            return;
        }

        mtrace('Found ' . count($instances) . ' active course dates automation instances.');

        foreach ($instances as $instance) {
            $this->process_instance($instance);
        }

        mtrace('Course dates automation check completed.');
    }

    /**
     * Process a single automation instance.
     *
     * @param object $instance The automation instance
     */
    private function process_instance($instance) {
        global $DB;

        mtrace("Processing instance {$instance->id} for course {$instance->courseid}");

        $course = get_course($instance->courseid);

        $conditionform = new \pulsecondition_coursedates\conditionform();
        $instancedata = \mod_pulse\automation\instances::create($instance->id)->get_instance_data();

        $datetype = $instancedata->condition['coursedates']['type'] ?? 'start';
        $targetdate = $conditionform->get_course_date($instancedata);

        if (!$targetdate) {
            mtrace("No {$datetype} date set for course {$instance->courseid}");
            return;
        }

        // Check if the target date has been reached.
        $currenttime = time();
        if ($currenttime < $targetdate) {
            mtrace("Course {$datetype} date not yet reached for course {$instance->courseid}");
            return;
        }


        $context = \context_course::instance($instance->courseid);
        $enrolledusers = get_enrolled_users($context, '', 0, 'u.id, u.username');

        if (empty($enrolledusers)) {
            mtrace("No enrolled users found for course {$instance->courseid}");
            return;
        }

        $triggeredcount = 0;

        foreach ($enrolledusers as $user) {
            // Check if this user meets the course dates condition.
            $condition = $conditionform->is_user_completed($instancedata, $user->id);
            if ($condition) {
                // Check if we haven't already triggered for this user.
                if (!$this->has_already_triggered($instance->id, $user->id)) {
                    $conditionform->trigger_instance($instance->id, $user->id, $targetdate);
                    $triggeredcount++;
                    mtrace("Triggered automation for user {$user->username} (ID: {$user->id})");
                }
            }
        }

        mtrace("Triggered automation for {$triggeredcount} users in instance {$instance->id}");
    }

    /**
     * Check if automation has already been triggered for this user.
     *
     * @param int $instanceid The instance ID
     * @param int $userid The user ID
     * @return bool True if already triggered, false otherwise
     */
    private function has_already_triggered($instanceid, $userid) {
        global $DB;
        $id = $instanceid;
        $sql = "SELECT *
                FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid
                AND userid = :userid
                AND status IN (:statussent, :statusqueued)";
        $condition = ['instanceid' => $id, 'userid' => $userid, 'statussent' => notification::STATUS_SENT, 'statusqueued' => notification::STATUS_QUEUED];
        if ($records = $DB->get_records_sql($sql, $condition)) {
            $record = reset($records);
            return $record->notifiedtime != null ? true : false;
        }
        return false;
    }
}
