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
        $iface = \aiprovider_birtai\local\ratelimit\ratelimit_settings::class;
        if (class_exists($classname) && is_subclass_of($classname, $iface)) {
            $provider = new $classname();
            $provider->add_settings($settings, $sid);
        }
    }

    $ADMIN->add('reports', new admin_externalpage(
        'aiprovider_birtai_reports',
        get_string('link_generalreport_birtai', 'aiprovider_birtai'),
        new moodle_url('/ai/provider/birtai/admin/report_sections.php'),
        'moodle/site:config'
    ));

    // Web service configuration page for automatic setup and token management.
    $ADMIN->add('server', new admin_externalpage(
        'aiprovider_birtai_webservice',
        get_string('link_webservice_config', 'aiprovider_birtai'),
        new moodle_url('/ai/provider/birtai/admin/webservice_config.php'),
        'moodle/site:config'
    ));

    $ADMIN->add('users', new admin_externalpage(
        'aiprovider_birtai_userlimits',
        get_string('link_usertokenlimits', 'aiprovider_birtai'),
        new moodle_url('/ai/provider/birtai/admin/user_token_limits.php'),
        'aiprovider/birtai:managetokenlimits'
    ));
}
