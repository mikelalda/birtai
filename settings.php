<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     aiprovider_birtai
 * @category    admin
 * @copyright   Josue <josue@birtai.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_ai\admin\admin_settingspage_provider;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingspage_provider(
        'aiprovider_birtai',
        new lang_string('pluginname', 'aiprovider_birtai'),
        'moodle/site:config',
        true
    );
    \aiprovider_birtai\local\admin_settings::add_settings($settings);

    foreach (\aiprovider_birtai\local\admin_settings::get_pages() as $page) {
        $ADMIN->add($page['parent'], new admin_externalpage($page['name'], $page['title'], $page['url'], $page['capability']));
    }
}
