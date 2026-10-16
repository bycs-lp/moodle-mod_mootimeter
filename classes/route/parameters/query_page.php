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
use core\router\schema\parameters\query_parameter;
use core\router\schema\referenced_object;

/**
 * The page of a collection, starting with 1.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_page extends query_parameter implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            name: 'page',
            type: param::INT,
            description: 'The page of the collection to return, starting with 1.',
            default: 1,
            examples: [new example(name: 'First page', value: 1)],
        );
    }
}
