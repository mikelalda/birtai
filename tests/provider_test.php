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
use aiprovider_birtai\local\compat;
use aiprovider_birtai\local\provider_migration;
use core_ai\aiactions\generate_image;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;

#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\provider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\abstract_processor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\local\admin_settings::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\aiprovider_birtai\local\provider_migration::class)]
/**
 * Tests of the birtai provider on the AI subsystem of Moodle 4.5 and of Moodle 5.0 and later.
 *
 * @package    aiprovider_birtai
 * @category   test
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_birtai\provider
 * @covers     \aiprovider_birtai\abstract_processor
 * @covers     \aiprovider_birtai\local\admin_settings
 * @covers     \aiprovider_birtai\local\provider_migration
 */
final class provider_test extends \advanced_testcase {
    /**
     * A provider object the way the AI subsystem of this Moodle version builds it.
     *
     * @param array|null $actionconfig Action settings of the instance (Moodle 5.0 and later).
     * @return provider
     */
    private function make_provider(?array $actionconfig = null): provider {
        if (!compat::has_provider_instances()) {
            return new provider();
        }
        return \core\di::get(\core_ai\manager::class)->create_provider_instance(
            classname: provider::class,
            name: 'BirtAI',
            enabled: true,
            actionconfig: $actionconfig,
        );
    }

    /**
     * A settings page with the plugin settings (adminlib.php is not autoloaded on Moodle 4.5).
     *
     * @return \admin_settingpage
     */
    private function settings_page(): \admin_settingpage {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $page = new \admin_settingpage('aiprovider_birtai', 'birtai');
        admin_settings::add_settings($page);
        return $page;
    }

    public function test_the_provider_loads_and_offers_its_actions(): void {
        $this->resetAfterTest();

        $provider = $this->make_provider();
        $actions = compat::has_provider_instances() ? provider::get_action_list() : $provider->get_action_list();

        $this->assertSame([generate_text::class, generate_image::class, summarise_text::class], $actions);
        $this->assertContains('local_coursegen', array_column(provider::get_services(), 'id'));
        $this->assertContains('/provider/chat/completions', array_column(provider::get_actions(), 'id'));
    }

    public function test_the_local_api_or_a_licence_key_configure_it(): void {
        $this->resetAfterTest();
        $provider = $this->make_provider();

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
        if (!compat::has_provider_instances()) {
            $this->markTestSkipped('Moodle 4.5 has the action settings in the administration tree.');
        }
        $this->resetAfterTest();

        $form = provider::get_action_settings(generate_text::class);
        $this->assertInstanceOf(form\action_text_form::class, $form);
        $this->assertInstanceOf(form\action_text_form::class, provider::get_action_settings(summarise_text::class));
        $this->assertFalse(provider::get_action_settings(generate_image::class));
        $this->assertSame(
            generate_text::get_system_instruction(),
            provider::get_action_setting_defaults(generate_text::class)['systeminstruction']
        );
        $this->assertSame([], provider::get_action_setting_defaults(generate_image::class));
    }

    public function test_the_processor_uses_the_configured_system_instruction(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $action = new generate_text(contextid: \context_system::instance()->id, userid: $user->id, prompttext: 'Hello');

        if (compat::has_provider_instances()) {
            $provider = $this->make_provider([
                generate_text::class => ['enabled' => true, 'settings' => ['systeminstruction' => 'From the instance']],
            ]);
            $expected = 'From the instance';
        } else {
            $provider = $this->make_provider();
            set_config('action_generate_text_instruction', 'From the plugin settings', 'aiprovider_birtai');
            $expected = 'From the plugin settings';
        }
        $processor = new process_generate_text($provider, $action);
        $method = new \ReflectionMethod($processor, 'get_system_instruction');

        $this->assertSame($expected, $method->invoke($processor));
    }

    public function test_the_processor_falls_back_to_the_default_instruction(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $action = new summarise_text(contextid: \context_system::instance()->id, userid: $user->id, prompttext: 'Text');
        unset_config('action_summarise_text_instruction', 'aiprovider_birtai');
        $provider = $this->make_provider([summarise_text::class => ['enabled' => true, 'settings' => []]]);

        $processor = new process_summarise_text($provider, $action);
        $method = new \ReflectionMethod($processor, 'get_system_instruction');

        $this->assertSame(summarise_text::get_system_instruction(), $method->invoke($processor));
    }

    public function test_the_settings_page_holds_the_connection_and_rate_limits(): void {
        $this->resetAfterTest();
        $page = $this->settings_page();

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

    public function test_writing_the_settings_page(): void {
        $this->resetAfterTest();
        $page = $this->settings_page();

        $count = admin_settings::write_settings($page, (object) [
            's_aiprovider_birtai_local_api_url' => 'http://moodle-ai-provider:8001',
            's_aiprovider_birtai_use_local_api' => '1',
        ]);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame('http://moodle-ai-provider:8001', get_config('aiprovider_birtai', 'local_api_url'));
    }

    public function test_the_defaults_are_written_once(): void {
        $this->resetAfterTest();
        unset_config('local_api_url', 'aiprovider_birtai');
        set_config('use_local_api', 0, 'aiprovider_birtai');

        admin_settings::apply_defaults();

        $this->assertSame('http://localhost:8001', get_config('aiprovider_birtai', 'local_api_url'));
        // A value that is already set stays as it is.
        $this->assertSame('0', get_config('aiprovider_birtai', 'use_local_api'));
    }

    public function test_the_moodle_45_settings_become_one_provider_instance(): void {
        global $DB;
        if (!compat::has_provider_instances()) {
            $this->markTestSkipped('Moodle 4.5 has no provider instances.');
        }
        $this->resetAfterTest();
        set_config('use_local_api', 1, 'aiprovider_birtai');
        set_config('action_generate_text_instruction', 'Write in Basque', 'aiprovider_birtai');
        unset_config('action_summarise_text_instruction', 'aiprovider_birtai');

        $this->assertTrue(provider_migration::create_instance_from_settings());
        $this->assertFalse(provider_migration::create_instance_from_settings());

        $record = $DB->get_record('ai_providers', ['provider' => provider_migration::PROVIDER], '*', MUST_EXIST);
        $this->assertSame(0, (int) $record->enabled);
        $actionconfig = json_decode($record->actionconfig, true);
        $this->assertSame('Write in Basque', $actionconfig[generate_text::class]['settings']['systeminstruction']);
        $this->assertSame(
            get_string('action_summarise_text_instruction', 'core_ai'),
            $actionconfig[summarise_text::class]['settings']['systeminstruction']
        );
        $this->assertTrue($actionconfig[generate_image::class]['enabled']);

        // The AI subsystem can load the instance.
        $instances = \core\di::get(\core_ai\manager::class)->get_provider_instances(['provider' => provider_migration::PROVIDER]);
        $this->assertInstanceOf(provider::class, reset($instances));
    }

    public function test_nothing_is_migrated_without_settings(): void {
        global $DB;
        if (!compat::has_provider_instances()) {
            $this->markTestSkipped('Moodle 4.5 has no provider instances.');
        }
        $this->resetAfterTest();
        foreach (['enabled', 'use_local_api', 'licensekey'] as $name) {
            unset_config($name, 'aiprovider_birtai');
        }

        $this->assertFalse(provider_migration::create_instance_from_settings());
        $this->assertFalse($DB->record_exists('ai_providers', ['provider' => provider_migration::PROVIDER]));
    }
}
