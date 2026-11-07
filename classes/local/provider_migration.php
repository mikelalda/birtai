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

/**
 * Turn the Moodle 4.5 settings of birtai into a provider instance (Moodle 5.0 and later).
 *
 * The same as Moodle 5.0 did for its own providers in upgrade_convert_ai_providers_to_instances():
 * a site that used birtai keeps it as an AI provider after upgrading.
 *
 * @package    aiprovider_birtai
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_migration {
    /** @var string Provider class name, as Moodle stores it in ai_providers. */
    public const PROVIDER = 'aiprovider_birtai\provider';

    /**
     * Create a birtai provider instance from the plugin settings, unless there is one already.
     *
     * Nothing is created on Moodle 4.5 (no ai_providers table) or when birtai was never
     * set up. The instance is enabled only if the plugin was enabled as an AI provider.
     *
     * @return bool Whether an instance was created.
     */
    public static function create_instance_from_settings(): bool {
        global $DB;

        if (!$DB->get_manager()->table_exists('ai_providers')) {
            return false;
        }
        if ($DB->record_exists('ai_providers', ['provider' => self::PROVIDER])) {
            return false;
        }
        $config = get_config('aiprovider_birtai');
        if (empty($config->enabled) && empty($config->use_local_api) && empty($config->licensekey)) {
            return false;
        }

        $name = get_string('pluginname', 'aiprovider_birtai');
        $instanceconfig = [
            'aiprovider' => self::PROVIDER,
            'name' => $name,
            'enableglobalratelimit' => 0,
            'globalratelimit' => 100,
            'enableuserratelimit' => 0,
            'userratelimit' => 10,
        ];
        $actionconfig = [];
        foreach (['generate_text', 'generate_image', 'summarise_text'] as $actionname) {
            $settings = [];
            if ($actionname !== 'generate_image') {
                $instruction = $config->{"action_{$actionname}_instruction"} ?? '';
                $settings['systeminstruction'] = $instruction !== ''
                    ? $instruction
                    : get_string("action_{$actionname}_instruction", 'core_ai');
            }
            $actionconfig["core_ai\\aiactions\\{$actionname}"] = [
                'enabled' => (bool) ($config->{$actionname} ?? true),
                'settings' => $settings,
            ];
        }

        // Upgrade code may not use the AI manager: insert the record directly, as core does.
        $DB->insert_record('ai_providers', (object) [
            'name' => $name,
            'provider' => self::PROVIDER,
            'enabled' => (int) !empty($config->enabled),
            'config' => json_encode($instanceconfig),
            'actionconfig' => json_encode($actionconfig),
        ]);
        return true;
    }
}
