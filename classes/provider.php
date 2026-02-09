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

use core_ai\aiactions;

/**
 * Provider class for birtai AI integration.
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider extends \core_ai\provider {
    /** @var mixed License key for birtai API. */
    private mixed $licensekey;

    /**
     * Builder.
     */
    public function __construct() {
        $this->licensekey = get_config('aiprovider_birtai', 'licensekey');
    }

    /**
     * Get the list of AI actions supported by this provider.
     *
     * @return array
     */
    public function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
            \core_ai\aiactions\generate_image::class,
            \core_ai\aiactions\summarise_text::class,
        ];
    }

    /**
     * Check if the provider is configured properly.
     *
     * @return bool
     */
    public function is_provider_configured(): bool {
        // If using local API, consider it configured even without a license key.
        $uselocalapi = get_config('aiprovider_birtai', 'use_local_api');
        if (!empty($uselocalapi)) {
            return true;
        }
        return !empty($this->licensekey);
    }

    /**
     * Check if a request is allowed for this provider.
     *
     * @param aiactions\base $action
     * @return array|bool
     */
    public function is_request_allowed(aiactions\base $action): array|bool {
        global $USER;
        return true;
    }

    /**
     * Add authentication headers to a request.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     */
    public function add_authentication_headers(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\RequestInterface {
        return $request->withAddedHeader('Authorization', "Bearer {$this->licensekey}");
    }

    /**
     * Get any admin settings available per AI action.
     *
     * @param string $action The action class name.
     * @param \admin_root $ADMIN The admin root object.
     * @param string $section The section name.
     * @param bool $hassiteconfig Whether the current user can configure site settings.
     * @return array
     */
    public function get_action_settings(
        string $action,
        \admin_root $ADMIN,
        string $section,
        bool $hassiteconfig
    ): array {
        $actionname = substr($action, (strrpos($action, '\\') + 1));
        $settings = [];

        // Settings for generate_text and summarise_text actions.
        if ($actionname === 'generate_text' || $actionname === 'summarise_text') {
            $settings[] = new \admin_setting_configtextarea(
                "aiprovider_birtai/action_{$actionname}_instruction",
                new \lang_string("action:{$actionname}:instruction", 'aiprovider_birtai'),
                new \lang_string("action:{$actionname}:instruction_desc", 'aiprovider_birtai'),
                $action::get_system_instruction(),
                PARAM_TEXT
            );
        }

        return $settings;
    }

    /**
     * Return all available AI services for this provider.
     *
     * @return array
     */
    public static function get_services(): array {
        return [
            ['id' => 'local_coursegen', 'name' => get_string('pluginname_coursegen', 'aiprovider_birtai')],
            ['id' => 'local_birtai_ratings', 'name' => get_string('pluginname_birtai_ratings', 'aiprovider_birtai')],
            ['id' => 'local_forum_ai', 'name' => get_string('pluginname_forum_ai', 'aiprovider_birtai')],
            ['id' => 'local_assign_ai', 'name' => get_string('pluginname_assign_ai', 'aiprovider_birtai')],
            ['id' => 'aiprovider_birtai', 'name' => get_string('pluginname', 'aiprovider_birtai')],
            ['id' => 'local_dttutor', 'name' => get_string('pluginname_dttutor', 'aiprovider_birtai')],
            ['id' => 'local_socialcert', 'name' => get_string('pluginname_socialcert', 'aiprovider_birtai')],
            ['id' => 'report_lifestory', 'name' => get_string('pluginname_lifestory', 'aiprovider_birtai')],
            ['id' => 'local_coursedynamicrules', 'name' => get_string('pluginname_smartrules', 'aiprovider_birtai')],
        ];
    }

    /**
     * Return all available AI actions for this provider.
     *
     * @return array
     */
    public static function get_actions(): array {
        return [
            ['id' => '/provider/chat/completions', 'name' => get_string('generate_text', 'aiprovider_birtai')],
            ['id' => '/provider/images/generations', 'name' => get_string('generate_image', 'aiprovider_birtai')],
            ['id' => '/course/execute', 'name' => get_string('generate_creation_course', 'aiprovider_birtai')],
            ['id' => '/course/start', 'name' => get_string('generate_plan_course', 'aiprovider_birtai')],
            ['id' => '/resources/create-mod', 'name' => get_string('generate_activitie', 'aiprovider_birtai')],
            ['id' => '/assign/answer', 'name' => get_string('generate_assign_answer', 'aiprovider_birtai')],
            ['id' => '/forum/chat', 'name' => get_string('generate_forum_chat', 'aiprovider_birtai')],
            ['id' => '/rating/general', 'name' => get_string('generate_analysis_general', 'aiprovider_birtai')],
            ['id' => '/rating/course', 'name' => get_string('generate_analysis_course', 'aiprovider_birtai')],
            ['id' => '/rating/query', 'name' => get_string('generate_analysis_comments', 'aiprovider_birtai')],
            ['id' => '/context/upload', 'name' => get_string('read_context_course', 'aiprovider_birtai')],
            ['id' => '/context/upload-model-context', 'name' => get_string('read_context_course_model', 'aiprovider_birtai')],
            ['id' => '/resources/create-mod/stream', 'name' => get_string('generate_activitie', 'aiprovider_birtai')],
            ['id' => '/certificate/answer', 'name' => get_string('generate_certificate_answer', 'aiprovider_birtai')],
            ['id' => '/story/analysis', 'name' => get_string('generate_analysis_story_student', 'aiprovider_birtai')],
            ['id' => '/smartrules/create-mod', 'name' => get_string('generate_ai_reinforcement_activity', 'aiprovider_birtai')],
            ['id' => '/chat/message', 'name' => get_string('generate_chat_message', 'aiprovider_birtai')],
        ];
    }
}
