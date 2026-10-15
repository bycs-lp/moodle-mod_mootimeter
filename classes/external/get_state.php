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
 * Web service returning the change timestamps for the frontend polling.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mod_mootimeter\local\exporter;
use mod_mootimeter\local\external_util;

/**
 * Web service returning the change timestamps for the frontend polling.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_state extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'pageid' => new external_value(PARAM_INT, 'Currently selected page id, 0 for none', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Return the change timestamps of the page list and of the selected page.
     *
     * A page that is not accessible (anymore) is reported with timestamps 0, the page list tells the client what to show.
     *
     * @param int $cmid
     * @param int $pageid
     * @return array
     */
    public static function execute(int $cmid, int $pageid = 0): array {
        global $DB;

        ['cmid' => $cmid, 'pageid' => $pageid] = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'pageid' => $pageid]
        );
        [$cm, , $ismoderator] = external_util::validate_cm($cmid);

        $page = null;
        if ($pageid) {
            $page = $DB->get_record('mootimeter_pages', ['id' => $pageid, 'instance' => $cm->instance]) ?: null;
            if ($page && !$ismoderator && (int) $page->visible !== helper::PAGE_VISIBLE) {
                $page = null;
            }
        }

        return exporter::export_state($cm, $page);
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return exporter::state_structure();
    }
}
