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
 * Instance add/edit form for Whiteboard plugin
 *
 * @package   mod_whiteboard
 * @copyright 2025 (Your Name/School)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(dirname(__FILE__).'/lib.php');

/**
 * Module instance settings form
 */
class mod_whiteboard_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = &$this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('whiteboard_name', 'mod_whiteboard'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('whiteboard_maxlength', 'mod_whiteboard', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'whiteboard_name', 'mod_whiteboard');

        // Adding the standard intro and intro format elements.
        $this->standard_intro_elements();

        // Adding standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add form buttons.
        $this->add_action_buttons();
    }

    /**
     * Data preprocessing
     *
     * @param array $defaultvalues Default values for the form
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            // Bestehende Instanz: Daten aus DB laden
            global $DB;
            $whiteboard = $DB->get_record('whiteboard', array('id' => $this->current->instance));
            if ($whiteboard) {
                $defaultvalues['name'] = $whiteboard->name;
                $defaultvalues['intro'] = $whiteboard->intro;
                $defaultvalues['introformat'] = $whiteboard->introformat;
            }
        } else {
            // NEUE Instanz: Raum-ID und Verschlüsselungsschlüssel generieren
            $defaultvalues['room_id'] = whiteboard_generate_room_id();
            $defaultvalues['encryption_key'] = whiteboard_generate_encryption_key();
        }
    }
}