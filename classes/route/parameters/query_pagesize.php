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
use mod_mootimeter\route\api_util;

/**
 * The number of items per page of a collection.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_pagesize extends query_parameter implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            name: 'pagesize',
            type: param::INT,
            description: 'The number of items per page, at most ' . api_util::MAX_PAGE_SIZE . '.',
            default: api_util::DEFAULT_PAGE_SIZE,
            examples: [new example(name: 'Default page size', value: api_util::DEFAULT_PAGE_SIZE)],
        );
    }
}
