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

/**
 * Admin page to manage per-user token limits for the birtai AI provider.
 *
 * @package    aiprovider_birtai
 * @copyright  2025 Wilber Narvaez <https://birtai.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use aiprovider_birtai\local\user_token_limit_manager;

require_login();

$context = context_system::instance();
require_capability('aiprovider/birtai:managetokenlimits', $context);

$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$sort = optional_param('sort', 'email', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$pageurl = new moodle_url('/ai/provider/birtai/admin/user_token_limits.php', [
    'search' => $search,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
]);
\aiprovider_birtai\local\admin_settings::setup_page('aiprovider_birtai_userlimits', $pageurl);

$heading = get_string('link_usertokenlimits', 'aiprovider_birtai');
$PAGE->set_title($heading);
$PAGE->set_heading($SITE->fullname);

if ($action === 'delete' && $id) {
    require_sesskey();
    user_token_limit_manager::delete($id);
    redirect(new moodle_url('/ai/provider/birtai/admin/user_token_limits.php'));
}

$allowedsorts = ['fullname', 'email', 'tokenlimit', 'tokensused'];
if (!in_array($sort, $allowedsorts, true)) {
    $sort = 'email';
}
$dir = (strtoupper($dir) === 'DESC') ? 'DESC' : 'ASC';

$total = user_token_limit_manager::count($search);
$offset = $page * $perpage;
$records = user_token_limit_manager::get_records($search, $sort, $dir, $offset, $perpage);

$addurl = new moodle_url('/ai/provider/birtai/admin/user_token_limit_edit.php', [
    'returnurl' => $PAGE->url->out_as_local_url(false),
]);

// Build template data.
$headers = [
    'fullname' => get_string('fullname'),
    'email' => get_string('email'),
    'tokenlimit' => get_string('usertokenlimit_limit', 'aiprovider_birtai'),
    'tokensused' => get_string('usertokenlimit_used', 'aiprovider_birtai'),
    'actions' => get_string('actions', 'moodle'),
];

$columns = [];
foreach (['fullname', 'email', 'tokenlimit', 'tokensused', 'actions'] as $col) {
    $coldata = ['key' => $col, 'label' => $headers[$col]];
    $iscurrent = ($sort === $col);
    if ($iscurrent) {
        $coldata['current'] = true;
        $coldata['dirasc'] = ($dir === 'ASC');
        $coldata['dirdesc'] = ($dir === 'DESC');
    }
    if ($col !== 'actions') {
        $nextdir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
        $coldata['sorturl'] = (new moodle_url($PAGE->url, [
            'sort' => $col,
            'dir' => $nextdir,
            'search' => $search,
            'page' => $page,
            'perpage' => $perpage,
        ]))->out(false);
    }
    $columns[] = $coldata;
}

$rows = [];
$editbase = new moodle_url('/ai/provider/birtai/admin/user_token_limit_edit.php');
foreach ($records as $record) {
    $fullname = fullname((object)[
        'firstname' => $record->firstname,
        'lastname' => $record->lastname,
    ]);
    $editurl = new moodle_url($editbase, [
        'id' => $record->id,
        'returnurl' => $PAGE->url->out_as_local_url(false),
    ]);
    $rows[] = [
        'id' => (int)$record->id,
        'fullname' => $fullname,
        'email' => $record->email,
        'tokenlimit' => (int)$record->tokenlimit,
        'tokensused' => (int)$record->tokensused,
        'canreset' => $record->tokensused > 0,
        'editurl' => $editurl->out(false),
    ];
}

$base = new moodle_url('/ai/provider/birtai/admin/user_token_limits.php', [
    'search' => $search,
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage,
]);

$templatadata = [
    'addurl' => $addurl->out(false),
    'searchaction' => (new moodle_url('/ai/provider/birtai/admin/user_token_limits.php'))->out(false),
    'searchvalue' => $search,
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage,
    'columns' => $columns,
    'rows' => $rows,
    'empty' => empty($rows),
    'nothingtodisplay' => get_string('nothingtodisplay'),
    'pagingbar' => $OUTPUT->paging_bar($total, $page, $perpage, $base),
];

// JS: Delete confirmation modal.
$PAGE->requires->js_call_amd('aiprovider_birtai/user_token_limits', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->render_from_template('aiprovider_birtai/user_token_limits', $templatadata);
echo $OUTPUT->footer();
