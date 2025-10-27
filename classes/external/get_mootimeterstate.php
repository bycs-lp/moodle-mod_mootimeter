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
 * Web service to get_mootimeterstate.
 *
 * @package     mod_mootimeter
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mod_mootimeter\local\mootimeterstate;

/**
 * Web service to get_mootimeterstate.
 *
 * @package     mod_mootimeter
 * @copyright   2024, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_mootimeterstate extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'pageid to be active', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'The coursemodule id.', VALUE_REQUIRED),
            'dataset' => new external_value(PARAM_RAW, 'The dataset of the page.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param int $cmid
     * @param string $dataset
     * @return array
     */
    public static function execute(int $pageid, int $cmid, string $dataset): array {
        [
            'pageid' => $pageid,
            'cmid' => $cmid,
            'dataset' => $dataset,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'cmid' => $cmid,
            'dataset' => $dataset,
        ]);
        $cmcontext = \context_module::instance($cmid);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        $cm = get_coursemodule_from_id(null, $cmid);
        if (empty($pageid)) {
            $mootimeterstate = json_encode(mootimeterstate::get_mootimeterstate_default_params($cm));
            return ['code' => 200, 'string' => 'ok', 'state' => $mootimeterstate];
        }

        try {
            $dataset = json_decode($dataset);
            $helper = new \mod_mootimeter\helper();
            $page = $helper->get_page($pageid);
            if (empty($page)) {
                $pages = $helper->get_pages($cm->instance, "sortorder ASC");
                $page = array_pop($pages);
            }
            $mootimeterstate = json_encode(mootimeterstate::get_mootimeterstate_params($page, $dataset));
            $return = ['code' => 200, 'string' => 'ok', 'state' => $mootimeterstate];
        } catch (\Exception $e) {
            $return = ['code' => 500, 'string' => $e->getMessage(), 'state' => ''];
        }

        return $return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Return code.'),
                'string' => new external_value(PARAM_TEXT, 'Return string / description'),
                'state' => new external_value(PARAM_RAW, 'Returned mootimeterstates.'),
            ],
            'Most recent mootimeterstate.'
        );
    }
}
