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
use core\router\schema\referenced_object;

/**
 * An item of the page list of an activity.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_list_item extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'id' => new field(param::INT, 'Id of the page.'),
            'tool' => new field(param::PLUGIN, 'Tool of the page.'),
            'visible' => new field(param::BOOL, 'Whether participants can see the page.'),
            'number' => new field(param::INT, 'Position of the page in the list of the current user, starting with 1.'),
            'title' => new field(param::TEXT, 'Shortened question of the page.'),
            'iconUrl' => new field(param::URL, 'Icon of the tool.'),
        ]);
    }
}
