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
 * Pulse notification custom mail recipients handling.
 *
 * Create a custom mail as a new moodle user in nologin auth type to prevent the course access.
 * Creates the users on the global setting is updated. Removes users when email is removed from config.
 * Also removes all schedules for the removed email users.
 *
 * @package   pulseaction_notification
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulseaction_notification\local;

use core_user;

/**
 * Custom mail handling for notification pulse action.
 */
class custom_mail {
    /**
     * Custom mail instance.
     *
     * @return self
     */
    public static function instance(): self {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Get the custom recipients from config.
     *
     * @return array
     */
    public function get_custom_recipients(): array {
        global $CFG;

        // Get the custom recipients from config.
        $customemail = get_config('pulseaction_notification', 'recipients_custom');
        $extracustomemail = [];
        if (!empty($customemail)) {
            $lines = preg_split('/\r\n|\r|\n/', trim($customemail));
            foreach ($lines as $line) {
                $parts = array_map('trim', explode(',', $line));
                if (count($parts) == 2) {
                    [$name, $email] = $parts;
                    $extracustomemail[$email] = "{$name} ({$email})";
                }
            }
        }
        return $extracustomemail;
    }

    /**
     * Parse the custom recipients from config and return emails.
     *
     * @param string $customrecipients
     * @param bool $emailonly
     * @return array
     */
    public function get_emails_from_config(string $customrecipients, $emailonly = true): array {
        $custommails = [];
        if (!empty($customrecipients)) {
            // Split the custom recipients by new line.
            $emails = preg_split('/\r\n|[\r\n]/', $customrecipients);
            foreach ($emails as $line) {
                $parts = array_map('trim', explode(',', $line));
                if (count($parts) == 2) {
                    [$name, $email] = $parts;
                    $custommails[$email] = $name;
                }
            }
        }

        return $emailonly ? array_keys($custommails) : $custommails;
    }

    /**
     * Process the save of global config for custom recipients.
     *
     * @return void
     */
    public function process_save_globalconfig() {
        global $DB;
        // When the custom recipients config is updated, we need to clear all existing schedules.
        $previousrecord = $DB->get_record_sql(
            'SELECT * FROM {config_log} cl WHERE cl.name=:clname AND cl.plugin=:component ORDER BY id DESC',
            ['clname' => 'recipients_custom', 'component' => 'pulseaction_notification'],
            IGNORE_MULTIPLE
        );

        if (empty($previousrecord->oldvalue)) {
            return null;
        }

        $previousmails = $this->get_emails_from_config($previousrecord->oldvalue, false);
        $newmails = $this->get_emails_from_config(get_config('pulseaction_notification', 'recipients_custom'), false);

        $removedmails = array_diff_key($previousmails, $newmails);

        if (!empty($removedmails)) {
            foreach ($removedmails as $removedmail => $name) {
                if ($user = core_user::get_user_by_email($removedmail)) {
                    // Remove all schedules for the user.
                    $DB->delete_records('pulseaction_notification_sch', ['userid' => $user->id]);
                    // Delete the user if its nologin auth and was created for custom mail recipient.
                    if ($user->auth === 'nologin' && get_user_preferences('pulseaction_notification_recipient', 0, $user) == 1) {
                        // Delete only nologin users.
                        delete_user($user);
                    }
                }
            }
        }

        // New custom mails added, create a dummy user for each new mail.
        foreach ($newmails as $addedmail => $name) {
            // Create a dummy user for the added custom mail.
            self::create_nologin_user_for_email($addedmail, $name);
        }
    }

    /**
     * Create a user in nologin auth for the given email.
     *
     * @param string $email
     * @param string|null $name
     * @return core_user
     */
    public static function create_nologin_user_for_email(string $email, $name = null) {
        global $DB;

        $user = $DB->get_record('user', ['username' => $email]);
        if (!$user) {
            $user = create_user_record($email, md5($email), 'nologin');
        }

        if (!empty($name)) {
            $name = explode(' ', $name, 2);
            $DB->update_record('user', (object)[
                'id' => $user->id,
                'firstname' => $name[0] ?? '',
                'lastname' => $name[1] ?? '',
                'email' => $email,
            ]);

            // Set a preference to identify this user as custom mail recipient.
            set_user_preference('pulseaction_notification_recipient', 1, $user);
        }
        return $user;
    }

    /**
     * Get the custom recipients form value.
     *
     * @return string
     */
    public static function get_custom_recipients_formvalue(): string {
        $customrecipients = get_config('pulseaction_notification', 'recipients_custom');
        return $customrecipients ?: '';
    }
}
