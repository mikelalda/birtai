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

use Psr\Http\Message\RequestInterface;

/**
 * What the provider does the same way on Moodle 4.5 and on Moodle 5.0 and later.
 *
 * The connection to BirtAI (licence key, local API) is a plugin setting on every
 * Moodle version: the plugins that call BirtAI directly read it as well.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait provider_common {
    /**
     * The AI actions this provider can do.
     *
     * @return string[] Action class names.
     */
    protected static function supported_actions(): array {
        return [
            \core_ai\aiactions\generate_text::class,
            \core_ai\aiactions\generate_image::class,
            \core_ai\aiactions\summarise_text::class,
        ];
    }

    /**
     * Whether the connection to BirtAI is set up: the local API needs no licence key.
     *
     * @return bool
     */
    public function is_provider_configured(): bool {
        return !empty(get_config('aiprovider_birtai', 'use_local_api'))
            || !empty(get_config('aiprovider_birtai', 'licensekey'));
    }

    /**
     * Add the licence key to a request for the BirtAI API.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function add_authentication_headers(RequestInterface $request): RequestInterface {
        $licensekey = (string) get_config('aiprovider_birtai', 'licensekey');
        return $request->withAddedHeader('Authorization', "Bearer {$licensekey}");
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
