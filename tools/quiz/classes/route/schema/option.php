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

/**
 * An answer option of a quiz or poll page.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class option extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'id' => new field(param::INT, 'Id of the option.'),
            'text' => new field(param::TEXT, 'Unformatted text for editing, empty for participants.'),
            'textHtml' => new field(param::RAW, 'Formatted text.'),
            'isCorrect' => new field(
                param::BOOL,
                'Whether the option is correct, false until the correction is shown to participants.'
            ),
        ]);
    }
}
