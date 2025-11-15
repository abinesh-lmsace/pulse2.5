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
 * Scheduled task for user inactivity condition monitoring.
 *
 * @package   pulsecondition_userinactivity
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_userinactivity\task;

use pulseaction_notification\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to check for user inactivity and trigger automation conditions.
 */
class userinactivity extends \core\task\scheduled_task {

    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function get_name() {
        return get_string('taskuserinactivity', 'pulsecondition_userinactivity');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting user inactivity automation check...');

        // Get all active automation instances that use user inactivity condition.
        $sql = "SELECT ai.*, ai.id as instanceid
                FROM {pulse_autoinstances} ai
                JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
                LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'userinactivity'
                WHERE ai.status = 1
                AND (co.status > 0 OR (co.status IS NULL AND ai.templateid IN (
                    SELECT c.templateid FROM {pulse_condition} c WHERE c.triggercondition = 'userinactivity' AND c.status > 0
                )))";

        $instances = $DB->get_records_sql($sql);

        if (empty($instances)) {
            mtrace('No active user inactivity automation instances found.');
            return;
        }

        mtrace('Found ' . count($instances) . ' active user inactivity automation instances.');

        foreach ($instances as $instance) {
            $this->process_instance($instance);
        }

        mtrace('User inactivity automation check completed.');
    }

    /**
     * Process a single automation instance for user inactivity.
     *
     * @param stdClass $instance The automation instance.
     */
    protected function process_instance($instance) {
        global $DB;

        mtrace("Processing instance {$instance->id} for course {$instance->courseid}");

        try {
            $course = get_course($instance->courseid);
            $context = \context_course::instance($course->id);

            // Get enrolled users in the course.
            $enrolledusers = get_enrolled_users($context, '', 0, 'u.id, u.username');

            if (empty($enrolledusers)) {
                mtrace("No enrolled users found for course {$instance->courseid}");
                return;
            }

            // Get instance data with condition settings.
            $instancedata = \mod_pulse\automation\instances::create($instance->id)->get_instance_data();
            
            // Check if userinactivity condition is configured.
            if (!isset($instancedata->condition['userinactivity'])) {
                mtrace("User inactivity condition not configured for instance {$instance->id}");
                return;
            }

            $conditionform = new \pulsecondition_userinactivity\conditionform();
            $conditionsettings = $instancedata->condition['userinactivity'];
            
            $triggeredcount = 0;

            foreach ($enrolledusers as $user) {
                if ($conditionform->is_user_completed($instancedata, $user->id)) {
                    // Check if we haven't already triggered for this user.
                    if (!$this->has_already_triggered($instance->id, $user->id)) {
                        // Trigger the automation actions.
                        $conditionform->trigger_instance($instance->id, $user->id);
                        //$this->mark_as_triggered($instance->id, $user->id);
                        $triggeredcount++;
                        mtrace("Triggered automation for user {$user->username} (ID: {$user->id})");
                    }
                }
            }

            mtrace("Triggered automation for {$triggeredcount} users in instance {$instance->id}");

        } catch (\Exception $e) {
            mtrace('Error processing user inactivity instance ' . $instance->id . ': ' . $e->getMessage());
        }
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

    /**
     * Mark that automation has been triggered for this user.
     *
     * @param int $instanceid The instance ID.
     * @param int $userid The user ID.
     */
    protected function mark_as_triggered($instanceid, $userid) {
        global $DB;

        $record = new \stdClass();
        $record->instanceid = $instanceid;
        $record->userid = $userid;
        $record->timecreated = time();

        $DB->insert_record('pulse_userinactivity_log', $record);
    }
}