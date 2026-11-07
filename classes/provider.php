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

/**
 * Provider class for birtai AI integration, Moodle 5.0 and later.
 *
 * Moodle 4.5 has another provider API: the MOODLE_405_STABLE branch of this plugin has it.
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
    #[\Override]
    public static function get_action_list(): array {
        return self::supported_actions();
    }

    /**
     * The settings form of an action: the system instruction of the text actions.
     *
     * @param string $action The action class name.
     * @param array $customdata The customdata for the form.
     * @return \core_ai\form\action_settings_form|bool The form, or false for an action without settings.
     */
    #[\Override]
    public static function get_action_settings(
        string $action,
        array $customdata = [],
    ): \core_ai\form\action_settings_form|bool {
        $actionname = substr($action, (strrpos($action, '\\') + 1));
        if ($actionname !== 'generate_text' && $actionname !== 'summarise_text') {
            return false;
        }
        $customdata['actionname'] = $actionname;
        $customdata['action'] = $action;
        $customdata['providername'] = 'aiprovider_birtai';
        return new form\action_text_form(customdata: $customdata);
    }

    /**
     * The default settings of an action.
     *
     * @param string $action The action class name.
     * @return array
     */
    #[\Override]
    public static function get_action_setting_defaults(string $action): array {
        $form = static::get_action_settings($action);
        return $form ? $form->get_defaults() : [];
    }
}
