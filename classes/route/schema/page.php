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
 * The common data of a mootimeter page.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'id' => new field(param::INT, 'Id of the page.'),
            'cmId' => new field(param::INT, 'Course module id of the activity.'),
            'tool' => new field(param::PLUGIN, 'Tool of the page.'),
            'toolName' => new field(param::TEXT, 'Localised name of the tool.'),
            'visible' => new field(param::BOOL, 'Whether participants can see the page.'),
            'question' => new field(param::TEXT, 'Unformatted question for editing, empty for participants.'),
            'questionHtml' => new field(param::RAW, 'Formatted question.'),
            'resultsPublished' => new field(param::BOOL, 'Whether participants may see the results.'),
            'canViewResults' => new field(param::BOOL, 'Whether the current user may see the results.'),
            'anonymousMode' => new field(param::BOOL, 'Whether the answers are anonymous. Can not change once answers exist.'),
            'hasAnswers' => new field(param::BOOL, 'Whether answers exist.'),
            'components' => new schema_object(content: [
                'content' => new field(param::RAW, 'ESM specifier of the question view.'),
                'settings' => new field(param::RAW, 'ESM specifier of the settings view.'),
                'results' => new field(param::RAW, 'ESM specifier of the results view.'),
                'overview' => new field(param::RAW, 'ESM specifier of the answer overview.'),
                'menu' => new field(param::RAW, 'ESM specifier of the tool specific content menu, empty for none.'),
            ]),
        ]);
    }
}
