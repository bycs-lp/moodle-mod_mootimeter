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

namespace mod_mootimeter\route\parameters;

use core\param;
use core\router\schema\example;
use core\router\schema\parameters\mapped_property_parameter;
use core\router\schema\parameters\path_parameter;
use core\router\schema\referenced_object;
use mod_mootimeter\exception\not_found_exception;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A mootimeter activity referenced in the path by its course module id.
 *
 * The course module is provided as the request attribute mootimetercm.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class path_mootimeter extends path_parameter implements mapped_property_parameter, referenced_object {
    /** @var string Name of the request attribute holding the course module. */
    const ATTRIBUTE = 'mootimetercm';

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            name: 'cmId',
            type: param::INT,
            description: 'The course module id of the mootimeter activity.',
            examples: [new example(name: 'A course module id', value: 42)],
        );
    }

    #[\Override]
    public function add_attributes_for_parameter_value(ServerRequestInterface $request, string $value): ServerRequestInterface {
        try {
            [, $cm] = get_course_and_cm_from_cmid((int) $value, 'mootimeter');
        } catch (\core\exception\moodle_exception) {
            throw new not_found_exception('instancenotfound', 'mod_mootimeter', $value);
        }
        return $request->withAttribute(self::ATTRIBUTE, $cm);
    }
}
