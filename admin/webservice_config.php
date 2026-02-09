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
 * Webservice configuration page for birtai AI provider.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Wilber Narvaez
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');

require_login();
require_capability('aiprovider/birtai:configurews', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/ai/provider/birtai/admin/webservice_config.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('webserviceconfig_title', 'aiprovider_birtai'));
$PAGE->set_heading(get_string('webserviceconfig_title', 'aiprovider_birtai'));

// Initial status for page render.
$status = \aiprovider_birtai\webservice_config::get_status();

echo $OUTPUT->header();

$PAGE->requires->js_call_amd('aiprovider_birtai/webservice_config', 'init');

echo $OUTPUT->render_from_template('aiprovider_birtai/webservice_config', $status);

echo $OUTPUT->footer();
