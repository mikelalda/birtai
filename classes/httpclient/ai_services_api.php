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

namespace aiprovider_birtai\httpclient;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * HTTP client for the birtai AI services API.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_services_api extends birtai_api_base {
    /** Default base URL for the standard birtai AI service. */
    private const DEFAULT_BASE_URL = 'https://plugins-ai.birtai.com';

    /** Default base URL for the EU-hosted birtai AI service. */
    private const DEFAULT_BASE_URL_EU = 'https://eu.plugins-ai.birtai.com';

    /** Default base URL for local development API. */
    private const DEFAULT_LOCAL_URL = 'http://localhost:8001';

    /**
     * Constructor.
     *
     * @param string|null $licensekey The license key obtained from birtai SHOP.
     * @param string|null $baseurl Optional standard-region base URL to override the default endpoint.
     * @param string|null $baseurleu Optional EU-region base URL to override the default endpoint.
     */
    public function __construct(?string $licensekey = null, ?string $baseurl = null, ?string $baseurleu = null) {
        global $CFG;

        // Check if local API is configured.
        $localurl = get_config('aiprovider_birtai', 'local_api_url');
        $uselocalapi = get_config('aiprovider_birtai', 'use_local_api');

        if (!empty($uselocalapi) && !empty($localurl)) {
            $finalbaseurl = $localurl;
        } else if (!empty($uselocalapi)) {
            $finalbaseurl = self::DEFAULT_LOCAL_URL;
        } else if ($this->is_for_ue()) {
            $finalbaseurl = $baseurleu ?? self::DEFAULT_BASE_URL_EU;
        } else {
            $finalbaseurl = $baseurl ?? self::DEFAULT_BASE_URL;
        }

        parent::__construct($finalbaseurl, $licensekey);
    }

    /**
     * Build the streaming URL for a given session ID, adjusting base URL.
     *
     * @param string $sessionid
     * @return string streaming URL
     */
    public function get_streaming_url_for_session(string $sessionid): string {
        // Build streaming URL with session ID.
        $baseurl = rtrim($this->baseurl, '/');

        return $baseurl . '/chat/stream?session_id=' . urlencode($sessionid);
    }
}
