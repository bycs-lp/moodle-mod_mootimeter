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

/**
 * The sort order of a collection, a comma separated list of fields, descending ones prefixed with a minus.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_sort extends query_parameter {
    /**
     * Constructor.
     *
     * @param string[] $fields The fields the collection can be sorted by.
     */
    public function __construct(array $fields) {
        parent::__construct(
            name: 'sort',
            type: param::RAW,
            description: 'Comma separated fields to sort by, prefix a field with - for a descending order. Fields: '
                . implode(', ', $fields) . '. Without a sort order the order is not guaranteed.',
            examples: [new example(name: 'Descending by the first field', value: '-' . reset($fields))],
        );
    }
}
