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
 * Code to be executed after the plugin's database schema has been installed is defined here.
 *
 * @package     aiprovider_birtai
 * @category    upgrade
 * @copyright   Josue <josue@birtai.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_aiprovider_birtai_install() {
    // Moodle 5.0 and later do not load the settings.php of AI provider plugins, so they
    // never write its defaults: write them here.
    if (\aiprovider_birtai\local\compat::has_provider_instances()) {
        \aiprovider_birtai\local\admin_settings::apply_defaults();
    }
    return true;
}
