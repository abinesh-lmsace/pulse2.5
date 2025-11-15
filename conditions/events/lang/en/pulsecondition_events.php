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
 * Strings for "events condition", language 'en'.
 *
 * @package   pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['affecteduser'] = 'Affected user';
$string['availableevents'] = 'Available events';
$string['availableeventsdesc'] = 'Select the events that should be available for pulse automation conditions. Only the selected events will be shown in the event selector when configuring pulse automation conditions.';
$string['eventmodule'] = 'Event module';
$string['eventscompletion'] = 'Events completion';
$string['eventscompletion_help'] = '<b>Events Completion:</b> This automation will be triggered when the event extention has been granted, where this instance is used.<br><b>Disabled:</b>Events completion condition is disabled.<br><b>All:</b>Events completion condition applies to all enrolled users.<br><b>Upcoming:</b>Events completion condition only applies to future enrolments.';
$string['eventscontexts'] = 'Event contexts';
$string['eventscontextsdesc'] = 'Choose where the selected events can trigger the pulse automation condition. <br>
<b>Everywhere:</b> Events that occur in the context of the course or any course activity trigger the condition. Note: This excludes events that occur at the system level or in other courses.<br>
<b>Selected activity:</b> Only events in the selected activity trigger the condition.';
$string['eventscontextseverywhere'] = 'Everywhere';
$string['eventscontextsmoduleonly'] = 'Selected activity';
$string['notifyuser'] = 'User';
$string['notifyuser_help'] = 'Choose the user who will be monitored for the specified event and will receive the notification.<br>
<b>Affected User:</b>This refers to the user who is directly impacted or affected by the event. For example, if a user submits an assignment, the affected user would be the one who submitted the assignment.<br>
<b>Related User:</b>This refers to any other user who is related to or associated with the event in some way, but may not be directly affected by it. This could include users who are part of the same course, group, or organization, or users who have some connection to the event through their roles or permissions.';
$string['pluginname'] = 'Events completion';
$string['relateduser'] = 'Related user';
$string['selectevent'] = 'Event';
$string['selectevent_help'] = 'Select the event from the available events on the Moodle site.';
