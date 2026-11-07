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

namespace aiprovider_birtai;

use aiprovider_birtai\local\admin_settings;
use html_writer;
use moodle_url;

/**
 * Hook callbacks of aiprovider_birtai.
 *
 * @package    aiprovider_birtai
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {
    /**
     * Link the settings and pages of the plugin from the form of a birtai provider instance.
     *
     * The plugin settings are not in the administration tree: the instance form is where
     * an administrator looks for them.
     *
     * @param \core_ai\hook\after_ai_provider_form_hook $hook
     */
    public static function add_provider_form_links(\core_ai\hook\after_ai_provider_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_birtai') {
            return;
        }
        $links = [html_writer::link(
            new moodle_url('/ai/provider/birtai/admin/settings.php'),
            get_string('link_settings', 'aiprovider_birtai')
        )];
        foreach (admin_settings::get_pages() as $page) {
            $links[] = html_writer::link($page['url'], $page['title']);
        }
        $hook->mform->addElement(
            'static',
            'birtailinks',
            get_string('providerinstance_links', 'aiprovider_birtai'),
            html_writer::div(get_string('providerinstance_links_desc', 'aiprovider_birtai'))
                . html_writer::alist($links)
        );
    }
}
