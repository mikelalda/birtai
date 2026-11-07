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

namespace aiprovider_birtai\local;

use admin_setting_configcheckbox;
use admin_setting_configpasswordunmask;
use admin_setting_configtext;
use admin_setting_heading;
use lang_string;
use moodle_url;

/**
 * The plugin settings and administration pages of birtai, on every Moodle version.
 *
 * Moodle 4.5 shows them in the administration tree through settings.php. Moodle 5.0
 * no longer loads the settings.php of AI provider plugins, so there the settings have
 * a page of their own (admin/settings.php) and the other pages set themselves up.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class admin_settings {
    /**
     * Add the plugin settings to a settings page.
     *
     * @param \admin_settingpage $settings
     */
    public static function add_settings(\admin_settingpage $settings): void {
        // Settings general.
        $settings->add(new admin_setting_heading(
            'aiprovider_birtai/general',
            new lang_string('settings', 'core'),
            ''
        ));

        // License key.
        $settings->add(new admin_setting_configpasswordunmask(
            'aiprovider_birtai/licensekey',
            new lang_string('licensekey', 'aiprovider_birtai'),
            new lang_string('licensekey_desc', 'aiprovider_birtai'),
            ''
        ));

        // Local API settings.
        $settings->add(new admin_setting_heading(
            'aiprovider_birtai/localapi_heading',
            new lang_string('localapi_heading', 'aiprovider_birtai'),
            new lang_string('localapi_heading_desc', 'aiprovider_birtai')
        ));

        // Use local API checkbox.
        $settings->add(new admin_setting_configcheckbox(
            'aiprovider_birtai/use_local_api',
            new lang_string('use_local_api', 'aiprovider_birtai'),
            new lang_string('use_local_api_desc', 'aiprovider_birtai'),
            1
        ));

        // Local API URL.
        $settings->add(new admin_setting_configtext(
            'aiprovider_birtai/local_api_url',
            new lang_string('local_api_url', 'aiprovider_birtai'),
            new lang_string('local_api_url_desc', 'aiprovider_birtai'),
            'http://localhost:8001',
            PARAM_URL
        ));
        $settings->hide_if('aiprovider_birtai/local_api_url', 'aiprovider_birtai/use_local_api', 'eq', 0);

        // Per-plugin rate limit settings.
        $settings->add(new admin_setting_heading(
            'aiprovider_birtai/ratelimits_heading',
            new lang_string('ratelimits_heading', 'aiprovider_birtai'),
            new lang_string('ratelimits_heading_desc', 'aiprovider_birtai')
        ));

        $services = \aiprovider_birtai\provider::get_services();
        // Order services by name.
        \core_collator::asort_array_of_arrays_by_key($services, 'name');
        foreach ($services as $service) {
            $sid = $service['id'];
            $sname = $service['name'];

            $settings->add(new admin_setting_heading(
                "aiprovider_birtai/ratelimit_{$sid}_heading",
                format_string($sname),
                ''
            ));

            // Enable per-user ratelimit for this plugin.
            $settings->add(new admin_setting_configcheckbox(
                "aiprovider_birtai/ratelimit_{$sid}_enable",
                new lang_string('ratelimit_enable', 'aiprovider_birtai'),
                new lang_string('ratelimit_enable_desc', 'aiprovider_birtai'),
                0
            ));

            // Credit limit in the configured window.
            $settings->add(new admin_setting_configtext(
                "aiprovider_birtai/ratelimit_{$sid}_limit",
                new lang_string('ratelimit_limit', 'aiprovider_birtai'),
                new lang_string('ratelimit_limit_desc', 'aiprovider_birtai'),
                10,
                PARAM_INT
            ));
            $settings->hide_if("aiprovider_birtai/ratelimit_{$sid}_limit", "aiprovider_birtai/ratelimit_{$sid}_enable", 'eq', 0);

            // Window: duration + unit.
            $settings->add(new \aiprovider_birtai\admin_setting_duration_unit(
                "aiprovider_birtai/ratelimit_{$sid}_window",
                new lang_string('ratelimit_window', 'aiprovider_birtai'),
                new lang_string('ratelimit_window_desc', 'aiprovider_birtai'),
                json_encode(['value' => 1, 'unit' => 'hours'])
            ));
            $settings->hide_if("aiprovider_birtai/ratelimit_{$sid}_window", "aiprovider_birtai/ratelimit_{$sid}_enable", 'eq', 0);

            $classname = "\\aiprovider_birtai\\local\\ratelimit\\{$sid}";
            $iface = ratelimit\ratelimit_settings::class;
            if (class_exists($classname) && is_subclass_of($classname, $iface)) {
                $provider = new $classname();
                $provider->add_settings($settings, $sid);
            }
        }
    }

    /**
     * Save the default of every plugin setting that has no value yet.
     *
     * Moodle 4.5 does it itself through the administration tree when it installs or
     * upgrades the plugin. From Moodle 5.0 the settings are not in that tree, so the
     * plugin has to.
     *
     * @return int Number of settings written.
     */
    public static function apply_defaults(): int {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $page = new \admin_settingpage('aiprovider_birtai', get_string('pluginname', 'aiprovider_birtai'));
        self::add_settings($page);
        $count = 0;
        foreach ($page->settings as $setting) {
            $default = $setting->get_defaultsetting();
            if ($default !== null && $setting->get_setting() === null) {
                $setting->write_setting($default);
                $count++;
            }
        }
        return $count;
    }

    /**
     * The administration pages of the plugin, with where Moodle 4.5 lists them.
     *
     * @return array[] Each one {name, parent, title, url, capability}.
     */
    public static function get_pages(): array {
        return [
            [
                'name' => 'aiprovider_birtai_reports',
                'parent' => 'reports',
                'title' => get_string('link_generalreport_birtai', 'aiprovider_birtai'),
                'url' => new moodle_url('/ai/provider/birtai/admin/report_sections.php'),
                'capability' => 'moodle/site:config',
            ],
            [
                'name' => 'aiprovider_birtai_webservice',
                'parent' => 'server',
                'title' => get_string('link_webservice_config', 'aiprovider_birtai'),
                'url' => new moodle_url('/ai/provider/birtai/admin/webservice_config.php'),
                'capability' => 'moodle/site:config',
            ],
            [
                'name' => 'aiprovider_birtai_userlimits',
                'parent' => 'users',
                'title' => get_string('link_usertokenlimits', 'aiprovider_birtai'),
                'url' => new moodle_url('/ai/provider/birtai/admin/user_token_limits.php'),
                'capability' => 'aiprovider/birtai:managetokenlimits',
            ],
        ];
    }

    /**
     * Set up one of the plugin's administration pages.
     *
     * Moodle 4.5 has the page in the administration tree; from Moodle 5.0 the page sets up
     * the context, layout and capability check itself.
     *
     * @param string $section Name of the page in get_pages().
     * @param moodle_url $url
     */
    public static function setup_page(string $section, moodle_url $url): void {
        global $PAGE;

        if (!compat::has_provider_instances()) {
            admin_externalpage_setup($section, '', null, $url);
            return;
        }
        $page = array_column(self::get_pages(), null, 'name')[$section];
        $context = \context_system::instance();
        require_login();
        require_capability($page['capability'], $context);
        $PAGE->set_context($context);
        $PAGE->set_url($url);
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($page['title']);
        $PAGE->set_heading($page['title']);
    }

    /**
     * Save the submitted values of a settings page that is not in the administration tree.
     *
     * @param \admin_settingpage $settings
     * @param \stdClass $data The submitted form.
     * @return int Number of settings that changed; errors go to admin_get_root()->errors.
     */
    public static function write_settings(\admin_settingpage $settings, \stdClass $data): int {
        $adminroot = admin_get_root();
        $formdata = (array) $data;
        $count = 0;
        foreach ($settings->settings as $setting) {
            $fullname = $setting->get_full_name();
            if (!array_key_exists($fullname, $formdata)) {
                continue;
            }
            $original = $setting->get_setting();
            $error = $setting->write_setting($formdata[$fullname]);
            if ($error !== '') {
                $adminroot->errors[$fullname] = (object) [
                    'data' => $formdata[$fullname],
                    'id' => $setting->get_id(),
                    'error' => $error,
                ];
            } else {
                $setting->write_setting_flags($formdata);
            }
            if ($setting->post_write_settings($original)) {
                $count++;
            }
        }
        return $count;
    }
}
