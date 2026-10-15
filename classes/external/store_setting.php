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
 * Web service to store a setting.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use mod_mootimeter\helper;
use mod_mootimeter\local\external_util;

/**
 * Web service to store setting.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_setting extends external_api {
    /** @var array Settings every tool supports, setting name => PARAM_* type. */
    const COMMON_SETTINGS = [
        'question' => PARAM_TEXT,
        'anonymousmode' => PARAM_BOOL,
        'showonteacherpermission' => PARAM_BOOL,
    ];

    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
            'name' => new external_value(PARAM_ALPHANUMEXT, 'Name of the setting'),
            'value' => new external_value(PARAM_RAW, 'New value, cleaned according to the setting type'),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $name
     * @param string $value
     * @return array
     */
    public static function execute(int $pageid, string $name, string $value): array {
        ['pageid' => $pageid, 'name' => $name, 'value' => $value] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'name' => $name, 'value' => $value]
        );
        [$page] = external_util::validate_page($pageid, true);

        $definitions = self::COMMON_SETTINGS + helper::get_toolhelper($page->tool)->get_setting_definitions();
        if (!array_key_exists($name, $definitions)) {
            throw new invalid_parameter_exception('Unknown setting: ' . $name);
        }

        $value = clean_param($value, $definitions[$name]);
        if ($definitions[$name] === PARAM_BOOL) {
            $value = $value ? '1' : '0';
        } else if ($definitions[$name] === PARAM_INT) {
            $value = (string) max(0, (int) $value);
        }

        (new helper())->set_tool_config($page, $name, (string) $value);

        return ['value' => (string) helper::get_tool_config($page, $name)];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'value' => new external_value(PARAM_RAW, 'The stored value, it may differ from the requested value'),
        ]);
    }
}
