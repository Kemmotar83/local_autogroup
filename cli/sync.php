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
 * @package    local_autogroup
 * @author     Giorgio Riva <giorgio.riva@unimib.it>
 * @copyright  2023 Università degli Studi di Milano-Bicocca (@link https://www.unimib.it}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_autogroup\usecase\verify_course_group_membership;

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'courseid' => null,
        'verbose' => false,
        'help' => false,
    ),
    array(
        'c' => 'courseid',
        'v' => 'verbose',
        'h' => 'help',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help = "Force sync of autogroup's sets in a course

Options:
-c, --courseid      Course id
-v, --verbose       Print verbose progess information
-h, --help          Print out this help

Example:
\$php local/autogroup/cli/sync.php --courseid=2 --verbose
";

    echo $help;
    exit(0);
}

if (empty($options['courseid'])) {
    throw new InvalidArgumentException("Course id parameter is mandatory");
}

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

global $DB;
$verifycoursegroupmembership = new verify_course_group_membership((int)$options['courseid'], $DB);

$trace->output('Start sync for course: ' . $options['courseid']);

$result = $verifycoursegroupmembership->invoke();

$trace->output('End sync for course: ' . $options['courseid'] . ' - Result: ' . $result);

$trace->finished();
