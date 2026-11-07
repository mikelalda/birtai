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
 * The plugin settings of birtai: connection to BirtAI and rate limits per service.
 *
 * Moodle 4.5 shows them in the administration tree (settings.php). Moodle 5.0 and later
 * no longer load the settings.php of AI provider plugins, so this page shows and saves
 * the same settings there.
 *
 * @package    aiprovider_birtai
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use aiprovider_birtai\local\admin_settings;
use aiprovider_birtai\local\compat;

if (!compat::has_provider_instances()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'aiprovider_birtai']));
}

$url = new moodle_url('/ai/provider/birtai/admin/settings.php');
$context = context_system::instance();
require_admin();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = get_string('link_settings', 'aiprovider_birtai');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('aiproviders', 'ai'), new moodle_url('/admin/settings.php', ['section' => 'aiprovider']));
$PAGE->navbar->add($title, $url);

$settingspage = new admin_settingpage('aiprovider_birtai', $title);
admin_settings::add_settings($settingspage);

$adminroot = admin_get_root();
$errormsg = '';
// The core template only sends action=save-settings with URL parameters, which this page has none of.
if (($data = data_submitted()) && confirm_sesskey()) {
    $count = admin_settings::write_settings($settingspage, $data);
    if (empty($adminroot->errors)) {
        redirect($url, $count ? get_string('changessaved') : null, null, \core\output\notification::NOTIFY_SUCCESS);
    }
    $errormsg = get_string('errorwithsettings', 'admin');
}

echo $OUTPUT->header();
if ($errormsg !== '') {
    echo $OUTPUT->notification($errormsg);
}
echo $OUTPUT->render_from_template('core_admin/settings', [
    'actionurl' => $url->out(false),
    'params' => [],
    'sesskey' => sesskey(),
    'return' => '',
    'title' => null,
    'settings' => $settingspage->output_html(),
    'showsave' => true,
]);
if ($settingspage->has_dependencies()) {
    echo $OUTPUT->render_from_template('core_admin/settings_showhide', [
        'dependencies' => json_encode($settingspage->get_dependencies_for_javascript()),
    ]);
}
echo $OUTPUT->footer();
