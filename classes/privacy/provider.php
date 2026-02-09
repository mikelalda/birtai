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

namespace aiprovider_birtai\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\writer;
use stdClass;

/**
 * Privacy Subsystem for aiprovider_birtai implementing metadata provider.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Wilber Narvaez <https://birtai.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, plugin_provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('aiprovider_birtai', [
            'prompt' => 'privacy:metadata:aiprovider_birtai:prompt',
            'numberimages' => 'privacy:metadata:aiprovider_birtai:numberimages',
            'userid' => 'privacy:metadata:aiprovider_birtai:userid',
        ], 'privacy:metadata:aiprovider_birtai:externalpurpose');

        $fields = [
            'userid', 'serviceid', 'windowstart', 'tokensused', 'lastsync', 'timecreated', 'timemodified',
        ];
        $fielddata = [];
        foreach ($fields as $field) {
            $fielddata[$field] = get_string('privacy:metadata:aiprovider_birtai_rlimit:' . $field, 'aiprovider_birtai');
        }
        $collection->add_database_table(
            'aiprovider_birtai_rlimit',
            $fielddata,
            get_string('privacy:metadata:aiprovider_birtai_rlimit', 'aiprovider_birtai')
        );

        $userlimitfields = [
            'userid' => get_string('privacy:metadata:aiprovider_birtai_userlimit:userid', 'aiprovider_birtai'),
            'tokenlimit' => get_string('privacy:metadata:aiprovider_birtai_userlimit:tokenlimit', 'aiprovider_birtai'),
            'tokensused' => get_string('privacy:metadata:aiprovider_birtai_userlimit:tokensused', 'aiprovider_birtai'),
            'countfrom' => get_string('privacy:metadata:aiprovider_birtai_userlimit:countfrom', 'aiprovider_birtai'),
            'lastsync' => get_string('privacy:metadata:aiprovider_birtai_userlimit:lastsync', 'aiprovider_birtai'),
            'timecreated' => get_string('privacy:metadata:aiprovider_birtai_userlimit:timecreated', 'aiprovider_birtai'),
            'timemodified' => get_string('privacy:metadata:aiprovider_birtai_userlimit:timemodified', 'aiprovider_birtai'),
        ];
        $collection->add_database_table(
            'aiprovider_birtai_userlimit',
            $userlimitfields,
            get_string('privacy:metadata:aiprovider_birtai_userlimit', 'aiprovider_birtai')
        );
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        if (self::user_has_data($userid)) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        if (self::user_has_data($context->instanceid)) {
            $userlist->add_user($context->instanceid);
        }
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $user = $contextlist->get_user();
        $context = \context_user::instance($user->id);
        $tables = static::get_table_user_map($user);

        foreach ($tables as $table => $filterparams) {
            $records = $DB->get_recordset($table, $filterparams);
            foreach ($records as $record) {
                writer::with_context($context)->export_data([
                    get_string('privacy:metadata:aiprovider_birtai', 'aiprovider_birtai'),
                    get_string('privacy:metadata:' . $table, 'aiprovider_birtai'),
                ], $record);
            }
            $records->close();
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel == CONTEXT_USER) {
            self::delete_user_data($context->instanceid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            self::delete_user_data($context->instanceid);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                self::delete_user_data($context->instanceid);
            }
        }
    }

    /**
     * Delete all user data from this plugin tables for a given user.
     *
     * @param int $userid The user ID
     * @return void
     */
    private static function delete_user_data(int $userid) {
        global $DB;

        $userdata = new stdClass();
        $userdata->id = $userid;

        $tables = self::get_table_user_map($userdata);
        foreach ($tables as $table => $filterparams) {
            $DB->delete_records($table, $filterparams);
        }
    }

    /**
     * Whether the user has data stored in this plugin tables.
     *
     * @param int $userid The user ID
     * @return bool
     */
    private static function user_has_data(int $userid): bool {
        global $DB;
        $userdata = new stdClass();
        $userdata->id = $userid;

        $tables = self::get_table_user_map($userdata);
        foreach ($tables as $table => $filterparams) {
            if ($DB->record_exists($table, $filterparams)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Map of tables containing user data and their filter params for a given user.
     *
     * @param stdClass $user The user
     * @return array<string,array<string,int>>
     */
    protected static function get_table_user_map(stdClass $user): array {
        return [
            'aiprovider_birtai_rlimit' => ['userid' => $user->id],
            'aiprovider_birtai_userlimit' => ['userid' => $user->id],
        ];
    }
}
