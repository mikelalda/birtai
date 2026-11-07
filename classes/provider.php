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

use aiprovider_birtai\local\provider_common;
use core_ai\aiactions;

/**
 * Provider class for birtai AI integration, Moodle 4.5.
 *
 * The provider API of Moodle 5.0 is different: the main branch of this plugin has it.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider extends \core_ai\provider {
    use provider_common;

    /**
     * Get the list of AI actions supported by this provider.
     *
     * @return array
     */
    public function get_action_list(): array {
        return self::supported_actions();
    }

    /**
     * Requests are limited by the rate limits per service of this plugin, not by the core ones.
     *
     * @param aiactions\base $action
     * @return array|bool
     */
    public function is_request_allowed(aiactions\base $action): array|bool {
        return true;
    }

    /**
     * The system instruction of the text actions, as settings of the action.
     *
     * @param string $action The action class name.
     * @param \admin_root $ADMIN The admin root object.
     * @param string $section The section name.
     * @param bool $hassiteconfig Whether the current user has moodle/site:config capability.
     * @return array
     */
    public function get_action_settings(
        string $action,
        \admin_root $ADMIN,
        string $section,
        bool $hassiteconfig
    ): array {
        $actionname = substr($action, (strrpos($action, '\\') + 1));
        if ($actionname !== 'generate_text' && $actionname !== 'summarise_text') {
            return [];
        }
        return [
            new \admin_setting_configtextarea(
                "aiprovider_birtai/action_{$actionname}_instruction",
                new \lang_string("action:{$actionname}:instruction", 'aiprovider_birtai'),
                new \lang_string("action:{$actionname}:instruction_desc", 'aiprovider_birtai'),
                $action::get_system_instruction(),
                PARAM_TEXT
            ),
        ];
    }
}
