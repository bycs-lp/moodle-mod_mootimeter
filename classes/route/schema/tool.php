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
 * A tool that can be chosen for a new page.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'name' => new field(param::PLUGIN, 'Short name of the tool.'),
            'displayName' => new field(param::TEXT, 'Localised name of the tool.'),
            'description' => new field(param::TEXT, 'Localised short description.'),
            'iconUrl' => new field(param::URL, 'Icon of the tool.'),
        ]);
    }
}
