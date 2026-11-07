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

#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\webservice_config::class)]
/**
 * Tests of the automatic setup of the birtai web service.
 *
 * @package    aiprovider_birtai
 * @category   test
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_birtai\webservice_config
 */
final class webservice_config_test extends \advanced_testcase {
    public function test_with_the_local_api_the_setup_needs_no_registration(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('use_local_api', 1, 'aiprovider_birtai');
        unset_config('registration_verified', 'aiprovider_birtai');

        $this->assertFalse(webservice_config::is_configured());
        $status = webservice_config::setup();

        $this->assertTrue($status['isconfigured']);
        $this->assertTrue(webservice_config::is_configured());
        $this->assertContains(get_string('ws_step_registration_local', 'aiprovider_birtai'), $status['messages']);
        // Nothing was sent anywhere.
        $this->assertFalse(get_config('aiprovider_birtai', 'registration_lastsent'));
    }
}
