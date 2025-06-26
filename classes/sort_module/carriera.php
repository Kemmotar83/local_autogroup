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

use local_autogroup\sort_module;
use local_unimibdatamanager;
use stdClass;

/**
 * Class course
 *
 * @package local_autogroup\domain
 */
class carriera extends sort_module {

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

        global $DB;
        if (!$DB->get_record('customfield_field', array('shortname' => 'cds'))) {
            throw new \Exception("Campo CDS non trovato in questo corso.");
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
    public function eligible_groups_for_user(stdClass $user): array {
        $groups = [];
        $listcds = $this->get_cds();
        foreach ($listcds as $cds) {
            $carriera = $this->get_carriera($user, $cds);
            if (!$carriera) {
                continue;
            }
            $fields = explode("-", $this->field);
            $search = [];
            $replace = [];
            foreach ($fields as $fieldname) {
                if (!empty($carriera[$fieldname])) {
                    $search[] = '{' . $fieldname . '}';
                    $replace[] = $carriera[$fieldname];
                }
            }
            $groupname = $this->get_config_options_groupname()[$this->field];
            $groups[] = str_replace($search, $replace, $groupname);
        }
        return $groups;
    }

    /**
     * Returns the options to be displayed on the autogroup_set
     * editing form. These are defined per-module.
     *
     * @return array
     */
    public function get_config_options(): array {
        $options = [
            'annoRegolamento' => 'Anno regolamento (coorte)',
            'annoCorso' => 'Anno corso',
            'codiceCds' => 'Cds',
            'codiceCds-annoCorso' => 'Cds | Anno corso',
            'codiceCds-annoRegolamento' => 'Cds | Anno regolamento (coorte)',
        ];
        return $options;
    }

    /**
     * Returns the options to be displayed on the autogroup_set
     * editing form. These are defined per-module.
     *
     * @return array
     */
    public function get_config_options_groupname(): array {
        $options = [
            'annoRegolamento' => 'Anno regolamento (coorte) - {annoRegolamento}',
            'annoCorso' => 'Anno corso - {annoCorso}',
            'codiceCds' => 'Cds - {codiceCds}',
            'codiceCds-annoCorso' => 'Cds - {codiceCds} | Anno corso - {annoCorso}',
            'codiceCds-annoRegolamento' => 'Cds - {codiceCds} | Anno regolamento (coorte) - {annoRegolamento}',
        ];
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

    /**
     * Returns the grouping text based on the field.
     *
     * @return string
     */
    public function grouping_by_text(): string {
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
     * Get specific career info
     *
     * @param stdClass $user
     * @param string $cds
     * @return mixed
     */
    private function get_carriera(stdClass $user, string $cds) {
        $unimibdataservice = new local_unimibdatamanager\unimibdata_service(new \text_progress_trace());
        return $unimibdataservice->get_carriera_by_moodle_username_and_cds($user->username, $cds);
    }

    /**
     * Get list of all cds
     *
     * @return array
     */
    private function get_cds(): array {
        global $DB;
        $cdsfieldid = $DB->get_record('customfield_field',
            ['shortname' => 'cds'], '*', MUST_EXIST);
        $cds = $DB->get_record('customfield_data',
            ['fieldid' => $cdsfieldid->id, 'instanceid' => $this->courseid], '*', MUST_EXIST);
        return explode(",", $cds->value);
    }
}
