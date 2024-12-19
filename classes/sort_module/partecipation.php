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
 * autogroup local plugin
 *
 * A course object relates to a Moodle course and acts as a container
 * for multiple groups. Initialising a course object will automatically
 * load each autogroup group for that course into memory.
 *
 * @package    local
 * @subpackage autogroup
 * @author     Gabrio Secco (gabrio.secco@unimib.it)
 * @date       02/2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_autogroup\sort_module;

use local_autogroup\exception;
use local_autogroup\sort_module;
use stdClass;

/**
 * Class course
 *
 * @package local_autogroup\domain
 */
class partecipation extends sort_module {
    /**
     * @param stdClass $config
     * @param int $courseid
     */
    public function __construct($config, $courseid) {
        if ($this->config_is_valid($config)) {
            $this->field = $config->field;
        }
        $this->courseid = (int)$courseid;
    }

    /**
     * @param stdClass $config
     * @return bool
     */
    public function config_is_valid(stdClass $config) {
        if (!isset($config->field)) {
            return false;
        }

        // Ensure that the stored option is valid.
        if (array_key_exists($config->field, $this->get_config_options())) {
            return true;
        }

        return false;
    }

    /**
     * @param stdClass $user
     * @return array $result
     */
    public function eligible_groups_for_user(stdClass $user) {
        $partecipation = $this->get_partecipation($user);
        if (!$partecipation) {
            return array();
        }
        $field = $this->field;

        $return = array();
        if (isset($partecipation[$field])) {
            foreach ($partecipation[$field] as $value) {
                $return[] = $this->get_config_options()[$field] . ' - ' . $value;
            }
        }

        return $return;
    }

    /**
     * Returns the options to be displayed on the autgroup_set
     * editing form. These are defined per-module.
     *
     * @return array
     */
    public function get_config_options() {
        $options = array(
            'role' => get_string('roles', 'core_role'),
            'enrolment' => get_string('enrolmentinstances', 'core_enrol'),
        );
        return $options;
    }

    /**
     * @return bool|string
     */
    public function grouping_by() {
        if (empty ($this->field)) {
            return false;
        }
        return (string)$this->field;
    }

    public function grouping_by_text() {
        if (empty ($this->field)) {
            return false;
        }
        $options = $this->get_config_options();
        return isset($options[$this->field]) ? $options[$this->field] : $this->field;
    }

    /**
     * @var string
     */
    private $field = '';

    /**
     * array con i ruoli e i metodo iscrizione di uno user
     *
     * @return array
     */
    private function get_partecipation(stdClass $user) {
        global $DB;
        $contextid = \context_course::instance($this->courseid)->id;
        $partecipation = [];
        $partecipation[$this->field] = [];
        if ($this->field === 'role') {
            $roles = $DB->get_records('role_assignments', ['contextid' => $contextid, 'userid' => $user->id]);
            foreach ($roles as $role) {
                $roleclass = $DB->get_record('role', ['id' => $role->roleid]);
                if ($roleclass) {
                    $partecipation['role'][] = $roleclass->shortname;
                }
            }
        }
        if ($this->field === 'enrolment') {
            $enrols = $DB->get_records('enrol', ['courseid' => $this->courseid]);
            foreach ($enrols as $enrol) {
                $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user->id]);
                if ($userenrolment) {
                    $partecipation['enrolment'][] = $enrol->enrol . ' - ' . $enrol->name;
                }
            }
        }
        return $partecipation;
    }
}
