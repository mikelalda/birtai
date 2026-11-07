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

namespace aiprovider_birtai\form;

/**
 * Settings of the text actions of a birtai provider instance.
 *
 * @package    aiprovider_birtai
 * @copyright  2026 Mikel Aldalur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_text_form extends \core_ai\form\action_settings_form {
    /**
     * The system instruction of the action, and the hidden fields the action settings page needs.
     */
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $actionconfig = $this->_customdata['actionconfig']['settings'] ?? [];
        $actionname = $this->_customdata['actionname'];
        $action = $this->_customdata['action'];

        $mform->addElement('header', 'generalsettingsheader', get_string('general', 'core'));

        $mform->addElement(
            'textarea',
            'systeminstruction',
            get_string("action:{$actionname}:instruction", 'aiprovider_birtai'),
            'wrap="virtual" rows="5" cols="20"',
        );
        $mform->setType('systeminstruction', PARAM_TEXT);
        $mform->setDefault('systeminstruction', $actionconfig['systeminstruction'] ?? $action::get_system_instruction());

        if (!empty($this->_customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']);
            $mform->setType('returnurl', PARAM_LOCALURL);
        }
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'provider', $this->_customdata['providername']);
        $mform->setType('provider', PARAM_TEXT);
        $mform->addElement('hidden', 'providerid', $this->_customdata['providerid'] ?? 0);
        $mform->setType('providerid', PARAM_INT);

        $this->set_data($actionconfig);
    }
}
