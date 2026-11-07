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
 * What differs between the AI subsystem of Moodle 4.5 and that of Moodle 5.0 and later.
 *
 * Moodle 5.0 turned AI providers into instances, saved in the ai_providers table: the
 * provider API gained static action methods and per-instance settings, and the
 * settings.php of provider plugins is no longer loaded.
 *
 * @package    aiprovider_birtai
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class compat {
    /** @var bool|null Cached answer of has_provider_instances(). */
    private static ?bool $instances = null;

    /**
     * Whether AI providers are instances (Moodle 5.0 and later).
     *
     * @return bool
     */
    public static function has_provider_instances(): bool {
        if (self::$instances === null) {
            self::$instances = (new \ReflectionMethod(\core_ai\provider::class, 'get_action_list'))->isStatic();
        }
        return self::$instances;
    }
}
