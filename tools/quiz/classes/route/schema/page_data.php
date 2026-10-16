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

namespace mootimetertool_quiz\route\schema;

use core\param;
use core\router\schema\objects\schema_object;
use core\router\schema\referenced_object;
use mod_mootimeter\route\schema\field;
use mod_mootimeter\route\schema\list_of;

/**
 * The answer options, the own selection and the settings of a quiz or poll page.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_data extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'pageId' => new field(param::INT, 'Id of the page.'),
            'options' => new list_of(new option(), 'Answer options.'),
            'selectedOptionIds' => new list_of(new field(param::INT), 'Options the current user selected.'),
            'maxAnswers' => new field(param::INT, 'Maximum number of options a participant may select, 0 for unlimited.'),
            'correctionShown' => new field(param::BOOL, 'Whether the correct options are shown and answering is closed.'),
            'supportsCorrectOptions' => new field(param::BOOL, 'Whether options can be marked as correct.'),
            'visualization' => new field(param::ALPHA, 'Chart type of the results.', enum: ['pillar', 'bar', 'line', 'pie']),
        ]);
    }
}
