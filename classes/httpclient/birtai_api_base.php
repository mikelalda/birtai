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
 * Class birtai_api_base
 * Base class for interacting with birtai APIs.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class birtai_api_base {
    /** Services that depend on the local Moodle webservice. */
    private const SERVICES_REQUIRING_WEBSERVICE = [
        'local_assign_ai',
        'local_forum_ai',
        'local_dttutor',
    ];

    /** Default license key for local API. */
    private const DEFAULT_LOCAL_LICENSE = 'moodle-ollama-local';

    /** @var string $baseurl The base URL for birtai API requests */
    protected $baseurl;

    /** @var string|null $licensekey The license key obtained from birtai SHOP */
    protected $licensekey;

    /** @var bool $islocalapi Whether we're using local API */
    protected $islocalapi;

    /**
     * Constructor.
     *
     * @param string $baseurl The base URL for birtai API requests.
     * @param string|null $licensekey The license key obtained from birtai SHOP.
     */
    public function __construct(string $baseurl, ?string $licensekey = null) {
        $this->baseurl = $baseurl;
        $this->islocalapi = !empty(get_config('aiprovider_birtai', 'use_local_api'));

        // For local API, use default license key if not configured.
        if ($this->islocalapi) {
            $this->licensekey = $licensekey ?? get_config('aiprovider_birtai', 'licensekey') ?: self::DEFAULT_LOCAL_LICENSE;
        } else {
            $this->licensekey = $licensekey ?? get_config('aiprovider_birtai', 'licensekey');
        }
    }

    /**
     * Check if we're using local API.
     *
     * @return bool True if using local API.
     */
    public function is_local_api(): bool {
        return $this->islocalapi;
    }

    /**
     * Returns the base URL for birtai API requests.
     * The URL is trimmed to remove any trailing slashes and a trailing slash is added.
     *
     * @return string The base URL for birtai API requests.
     */
    public function get_base_url(): string {
        return rtrim($this->baseurl, '/') . '/';
    }

    /**
     * Download a file from birtai API.
     *
     * @param string $endpoint Relative endpoint (starting with "/").
     * @param string $filename The name of the file to download.
     * @param array $filerecord File record options as accepted by create_file_from_url(); defaults to storing in draft user area.
     * @return \stored_file|null The downloaded file.
     * @throws \Exception If the file cannot be created.
     */
    public function download_file($endpoint, $filename, $filerecord = []): ?\stored_file {
        global $USER;

        $baseurl = $this->get_base_url();
        $packageurl = $baseurl . ltrim($endpoint, '/');

        $userid = $USER->id;
        $draftid = file_get_unused_draft_itemid();

        // Store SCORM package in moodledata draft area directly from URL.
        $fs = get_file_storage();
        $context = \context_user::instance($userid);
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $fileinfo = array_merge($fileinfo, $filerecord);
        $options = [];
        $options['headers'] = [
            'License-Key: ' . $this->licensekey,
        ];

        $file = $fs->create_file_from_url($fileinfo, $packageurl, $options, true);
        return $file;
    }

    /**
     * Generic handler for HTTP calls to birtai API.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, UPLOAD).
     * @param string $path   Relative endpoint (starting with "/").
     * @param mixed  $payload Data for request (array, string, or multipart).
     * @param array  $headers Extra headers if needed.
     * @return array|null
     * @throws \Exception
     */
    protected function send_request(string $method, string $path, $payload = [], array $headers = []): ?array {
        global $USER, $CFG;

        // For external API, license key is required. For local API, use default if empty.
        if (empty($this->licensekey) && !$this->islocalapi) {
            debugging('Cannot make this request: invalid license key', DEBUG_DEVELOPER);
            throw new \moodle_exception('invalidlicensekey', 'aiprovider_birtai');
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Enforce per-user, per-service rate limit using cached DB pre-check.
        // Service could be null if the path is not mapped.
        $serviceid = \aiprovider_birtai\local\ratelimiter::resolve_service_for_path($path);
        $this->enforce_webservice_requirements($serviceid);
        $userid = (int)($payload['userid'] ?? $USER->id);
        $ratelimiter = new \aiprovider_birtai\local\ratelimiter();

        // Validate if user is allowed to make this request.
        if (!empty($serviceid) && !$ratelimiter->is_user_allowed($serviceid, $userid)) {
            throw new \moodle_exception('notallowed', 'aiprovider_birtai');
        }

        // Enforce user global quota (across services) first.
        if (!$ratelimiter->precheck_user_quota($userid)) {
            $snapshot = $ratelimiter->get_user_quota_snapshot($userid);
            $details = '';
            if (is_array($snapshot) && ($snapshot['limit'] ?? 0) > 0) {
                $details = $snapshot['used'] . '/' . $snapshot['limit'];
            }
            throw new \moodle_exception('error_usertokenlimit_exceeded', 'aiprovider_birtai', '', $details);
        }

        if (!empty($serviceid) && !$ratelimiter->precheck($serviceid, $userid)) {
            $remaining = $ratelimiter->get_time_until_next_window((string)$serviceid, (int)$userid);
            $retrytimestamp = time() + max(0, (int)$remaining);
            $retryat = userdate($retrytimestamp, get_string('strftimedatetime', 'langconfig'));
            throw new \moodle_exception('error_ratelimit_exceeded', 'aiprovider_birtai', '', $retryat);
        }

        $curl = new \curl();
        $baseheaders = [
            'License-Key: ' . $this->licensekey,
        ];

        $headers = array_merge($baseheaders, $headers);

        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER' => $headers,
        ];

        $url = $this->baseurl . $path;
        $response = null;

        $defaultpayload = [
            'site_id' => md5($CFG->wwwroot),
            'userid' => $payload['userid'] ?? $USER->id,
            'timezone' => \core_date::get_user_timezone(),
            'lang' => $payload['lang'] ?? current_language(),
        ];
        switch (strtoupper($method)) {
            case 'GET':
                $response = $curl->get($url, $payload, $options);
                break;
            case 'POST':
                $payload = array_merge($payload, $defaultpayload);
                $response = $curl->post($url, json_encode($payload, JSON_UNESCAPED_UNICODE), $options);
                break;
            case 'PUT':
                $payload = array_merge($payload, $defaultpayload);
                $response = $curl->put($url, $payload, $options);
                break;
            case 'DELETE':
                $response = $curl->delete($url, $payload, $options);
                break;
            case 'UPLOAD':
                $payload = array_merge($payload, $defaultpayload);
                $response = $curl->post($url, $payload, $options);
                break;
            default:
                throw new \coding_exception('Invalid HTTP method: ' . $method);
        }

        if (!$response) {
            debugging('Empty response from birtai API', DEBUG_DEVELOPER);
            throw new \moodle_exception('emptyresponse', 'aiprovider_birtai');
        }

        if ($curl->error) {
            debugging('cURL error (' . $curl->error . ')', DEBUG_DEVELOPER);
            throw new \moodle_exception('curlerror', 'aiprovider_birtai', '', $curl->error);
        }

        $httpcode = $curl->get_info()['http_code'] ?? 0;
        if ($httpcode == 403) {
            $decodedresponse = json_decode($response, true);
            if ($decodedresponse['detail'] == 'tokens_not_sufficient') {
                debugging('Not enough tokens to make this request', DEBUG_DEVELOPER);
                throw new \moodle_exception('notenoughtokens', 'aiprovider_birtai');
            }
            if ($decodedresponse['detail'] == 'license_not_allowed') {
                debugging('License not allowed to make this request', DEBUG_DEVELOPER);
                throw new \moodle_exception('license_not_allowed', 'aiprovider_birtai');
            }

            debugging('Unknown error from birtai API', DEBUG_DEVELOPER);
            throw new \moodle_exception('forbidden', 'aiprovider_birtai');
        }

        if ($httpcode >= 400) {
            debugging("HTTP error {$httpcode} from birtai API: {$response}", DEBUG_DEVELOPER);
            // 4xx carry a message meant for the user ("file type not supported",
            // "scanned PDF..."): show it instead of a bare "HTTP 400".
            $detail = $httpcode < 500 ? self::error_detail($response) : '';
            if ($detail !== '') {
                throw new \moodle_exception('httperrordetail', 'aiprovider_birtai', '',
                    (object)['code' => $httpcode, 'detail' => $detail]);
            }
            throw new \moodle_exception('httperror', 'aiprovider_birtai', '', $httpcode);
        }

        $decodedresponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('JSON decode error: ' . json_last_error_msg() . '. Response: ' . $response, DEBUG_DEVELOPER);
            throw new \moodle_exception('jsondecodeerror', 'aiprovider_birtai', '', json_last_error_msg());
        }

        // Post-success syncs: only after a valid, non-error response.
        if (!empty($serviceid)) {
            $ratelimiter->sync_after_success($serviceid, $userid, $path);
        }
        $ratelimiter->sync_user_quota_after_success($userid, $path);

        return $decodedresponse;
    }

    /**
     * Human-readable message of an API error body ({"detail": "..."} from FastAPI).
     *
     * @param string $response Raw response body
     * @return string The message, or '' when there is none
     */
    protected static function error_detail(string $response): string {
        $decoded = json_decode($response, true);
        $detail = is_array($decoded) ? ($decoded['detail'] ?? '') : '';
        if (is_array($detail)) {
            // Validation errors: [{"loc": [...], "msg": "..."}, ...].
            $detail = implode('; ', array_filter(array_map(
                static fn($item) => is_array($item) ? (string)($item['msg'] ?? '') : (string)$item,
                $detail
            )));
        }
        return shorten_text(clean_param((string)$detail, PARAM_TEXT), 300);
    }

    /**
     * Standard JSON API call.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, UPLOAD).
     * @param string $path   Relative endpoint. Example: '/create-course'.
     * @param array $body Data for request.
     * @return array|null
     * @throws \Exception
     */
    public function request(string $method, string $path, array $body = []): ?array {
        $headers = ['Content-Type: application/json'];
        return $this->send_request($method, $path, $body, $headers);
    }

    /**
     * Upload a file using multipart/form-data.
     *
     * @param string $path Relative endpoint. Example: '/upload-file'.
     * @param string $filepath Absolute path to the file to upload.
     * @param string|null $mimetype MIME type of the file (falls back to PHP detection when null).
     * @param string|null $filename Name to use for the uploaded file (defaults to basename of $filepath).
     * @param array $extraparams Extra POST parameters to include in the upload request.
     * @return array|null Decoded response from the API.
     * @throws \Exception If the local file does not exist or the request fails.
     */
    public function upload_file(
        string $path,
        string $filepath,
        ?string $mimetype = null,
        ?string $filename = null,
        array $extraparams = []
    ): ?array {
        if (!file_exists($filepath)) {
            $filename = basename($filepath);
            throw new \coding_exception("File not found: {$filename}");
        }

        $postdata = array_merge($extraparams, [
            'file' => new \CURLFile($filepath, $mimetype, $filename),
        ]);

        return $this->send_request('UPLOAD', $path, $postdata);
    }

    /**
     * Ensure the birtai webservice is fully configured when required by the service.
     *
     * @param string|null $serviceid
     * @return void
     */
    private function enforce_webservice_requirements(?string $serviceid): void {
        if (empty($serviceid) || !in_array($serviceid, self::SERVICES_REQUIRING_WEBSERVICE, true)) {
            return;
        }

        if (!\aiprovider_birtai\webservice_config::is_configured()) {
            $setupurl = \aiprovider_birtai\webservice_config::get_url();
            $messageparams = (object)['url' => $setupurl->out(false)];
            throw new \moodle_exception('error_webservice_not_configured', 'aiprovider_birtai', '', $messageparams);
        }
    }

    /**
     * Check if the license is for European Union.
     *
     * @return bool
     */
    public function is_for_ue(): bool {
        $birtaiapi = new birtai_api();
        $response = $birtaiapi->get('tokens/saldo');
        return $response['is_for_eu'] == true;
    }
}
