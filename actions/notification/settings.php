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
 * Notification pulse action - Admin settings.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'pulseaction_notification',
        get_string('pluginname', 'pulseaction_notification')
    );

    $name = 'pulseaction_notification/recipients_custom';
    $title = get_string('recipientscustom', 'pulseaction_notification');
    $description = get_string('recipientscustom_desc', 'pulseaction_notification');
    $setting = new admin_setting_configtextarea($name, $title, $description, '');
    $setting->set_updatedcallback(fn() => \pulseaction_notification\local\custom_mail::instance()->process_save_globalconfig());

    $settings->add($setting);
}
