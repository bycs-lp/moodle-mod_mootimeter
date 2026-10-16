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

namespace mod_mootimeter\route\schema;

use core\param;
use core\router\schema\objects\schema_object;
use core\router\schema\objects\type_base;

/**
 * Envelope of collection responses: the items of the requested page and the pagination metadata.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collection extends schema_object {
    /**
     * Constructor.
     *
     * @param type_base $item Schema of a single item.
     */
    public function __construct(type_base $item) {
        parent::__construct(content: [
            'items' => new list_of($item, 'Items of the requested page.'),
            'pagination' => new schema_object(content: [
                'page' => new field(param::INT, 'Current page, starting with 1.'),
                'pageSize' => new field(param::INT, 'Maximum number of items per page.'),
                'totalItems' => new field(param::INT, 'Number of items of all pages.'),
            ]),
        ]);
    }
}
