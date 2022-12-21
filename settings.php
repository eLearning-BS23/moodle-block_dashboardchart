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
 * Settings for the dashboardchart block
 *
 * @package    block_dashboardchart
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
//    require_once($CFG->dirroot . '/blocks/dashboardchart/lib.php');

    // Presentation options heading.
    $settings->add(new admin_setting_heading('block_dashboardchart/appearance',
        get_string('appearance', 'admin'),
        ''));


    $name = 'block_dashboardchart/barcolor';
    $title = get_string('color', 'block_dashboardchart');
    $description = get_string('color_desc', 'block_dashboardchart');

    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, ''));

}
