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
 * Strings for "Group conditions", language 'en'.
 *
 * @package   pulsecondition_group
 * @copyright 2025 bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Language strings for Course group condition.

$string['pluginname'] = 'Course group';
$string['coursegroup'] = 'Course group';
$string['coursegroup_help'] = 'Choose how this automation behaves based on the user’s course group membership.';
$string['grouptype'] = 'Course group type';
$string['grouptype_help'] = 'Select whether to target users without a group, in any group, or in selected groups/groupings.';
$string['nogroup'] = 'No group';
$string['anygroup'] = 'Any group';
$string['selectedgroups'] = 'Selected groups';
$string['selectedgroupings'] = 'Selected groupings';
$string['selectgroups'] = 'Select groups';
$string['selectgroups_help'] = 'Select one or more course groups.';
$string['selectgroupings'] = 'Select groupings';
$string['selectgroupings_help'] = 'Select one or more groupings of groups.';
$string['nogroups'] = 'No groups';
$string['nogroupings'] = 'No groupings';
$string['type'] = 'Type';
$string['type_help'] = '<b>No group:</b> Triggers automation only for users who are not members of any group.<br><b>Any group:</b> Triggers automation for users who belong to any group in the course.<br><b>Select groups:</b> Triggers automation for users who are members of at least one selected group. (If no group is selected, it has no effect.)<br><b>Selected groupings:</b> Triggers automation for users who are members of at least one group within the selected groupings. (If no grouping is selected, it has no effec';
