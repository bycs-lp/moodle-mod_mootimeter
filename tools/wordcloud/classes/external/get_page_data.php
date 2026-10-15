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
 * Web service returning the own answers and the settings of a wordcloud page.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\local\external_util;
use mootimetertool_wordcloud\wordcloud;

/**
 * Web service returning the own answers and the settings of a wordcloud page.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_page_data extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
        ]);
    }

    /**
     * Return the own answers and the settings.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        ['pageid' => $pageid] = self::validate_parameters(self::execute_parameters(), ['pageid' => $pageid]);
        [$page] = external_util::validate_page($pageid);

        return (new wordcloud())->export_page_data($page);
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'answers' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Answer id'),
                'text' => new external_value(PARAM_TEXT, 'Answer'),
            ])),
            'maxinputsperuser' => new external_value(PARAM_INT, 'Maximum number of answers per user, 0 for unlimited'),
            'allowduplicateanswers' => new external_value(PARAM_BOOL, 'Whether a user may submit the same answer again'),
        ]);
    }
}
