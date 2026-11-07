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

use aiprovider_birtai\local\admin_settings;
use core_ai\aiactions\generate_image;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;

#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\provider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\abstract_processor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\local\admin_settings::class)]
/**
 * Tests of the birtai provider on the AI subsystem of Moodle 4.5.
 *
 * @package    aiprovider_birtai
 * @category   test
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_birtai\provider
 * @covers     \aiprovider_birtai\abstract_processor
 * @covers     \aiprovider_birtai\local\admin_settings
 */
final class provider_test extends \advanced_testcase {
    public function test_the_provider_loads_and_offers_its_actions(): void {
        $this->resetAfterTest();

        $provider = new provider();

        $this->assertSame([generate_text::class, generate_image::class, summarise_text::class], $provider->get_action_list());
        $this->assertContains('local_coursegen', array_column(provider::get_services(), 'id'));
        $this->assertContains('/provider/chat/completions', array_column(provider::get_actions(), 'id'));
    }

    public function test_the_local_api_or_a_licence_key_configure_it(): void {
        $this->resetAfterTest();
        $provider = new provider();

        set_config('use_local_api', 1, 'aiprovider_birtai');
        set_config('licensekey', '', 'aiprovider_birtai');
        $this->assertTrue($provider->is_provider_configured());

        set_config('use_local_api', 0, 'aiprovider_birtai');
        $this->assertFalse($provider->is_provider_configured());

        set_config('licensekey', 'abc', 'aiprovider_birtai');
        $this->assertTrue($provider->is_provider_configured());
        $request = $provider->add_authentication_headers(new \GuzzleHttp\Psr7\Request('POST', 'https://birtai.example/'));
        $this->assertSame('Bearer abc', $request->getHeaderLine('Authorization'));
    }

    public function test_the_text_actions_have_a_system_instruction_setting(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $provider = new provider();

        $settings = $provider->get_action_settings(generate_text::class, admin_get_root(), 'aiprovider', true);

        $this->assertCount(1, $settings);
        $this->assertSame('action_generate_text_instruction', $settings[0]->name);
        $this->assertSame([], $provider->get_action_settings(generate_image::class, admin_get_root(), 'aiprovider', true));
    }

    public function test_the_processor_uses_the_configured_system_instruction(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $action = new generate_text(contextid: \context_system::instance()->id, userid: $user->id, prompttext: 'Hello');
        set_config('action_generate_text_instruction', 'From the plugin settings', 'aiprovider_birtai');

        $processor = new process_generate_text(new provider(), $action);
        $method = new \ReflectionMethod($processor, 'get_system_instruction');

        $this->assertSame('From the plugin settings', $method->invoke($processor));
    }

    public function test_the_processor_falls_back_to_the_default_instruction(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $action = new summarise_text(contextid: \context_system::instance()->id, userid: $user->id, prompttext: 'Text');
        unset_config('action_summarise_text_instruction', 'aiprovider_birtai');

        $processor = new process_summarise_text(new provider(), $action);
        $method = new \ReflectionMethod($processor, 'get_system_instruction');

        $this->assertSame(summarise_text::get_system_instruction(), $method->invoke($processor));
    }

    public function test_a_failure_carries_every_error_field(): void {
        $method = new \ReflectionMethod(abstract_processor::class, 'with_error_fields');

        $this->assertSame(
            ['success' => false, 'error' => 'Invalid answer', 'errorcode' => 500, 'errormessage' => 'Invalid answer'],
            $method->invoke(null, ['success' => false, 'error' => 'Invalid answer'])
        );
        $this->assertSame(
            ['success' => false, 'errorcode' => 504, 'errormessage' => 'Timed out', 'error' => 'Timed out'],
            $method->invoke(null, ['success' => false, 'errorcode' => 504, 'errormessage' => 'Timed out'])
        );
        $success = ['success' => true, 'generatedcontent' => 'Hi'];
        $this->assertSame($success, $method->invoke(null, $success));
    }

    public function test_the_settings_page_holds_the_connection_and_rate_limits(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $page = new \admin_settingpage('aiprovider_birtai', 'birtai');

        admin_settings::add_settings($page);

        $names = array_map(fn($setting) => $setting->name, (array) $page->settings);
        $this->assertContains('licensekey', $names);
        $this->assertContains('use_local_api', $names);
        $this->assertContains('local_api_url', $names);
        $this->assertContains('ratelimit_local_coursegen_enable', $names);
        $this->assertSame(
            ['aiprovider_birtai_reports', 'aiprovider_birtai_webservice', 'aiprovider_birtai_userlimits'],
            array_column(admin_settings::get_pages(), 'name')
        );
    }
}
