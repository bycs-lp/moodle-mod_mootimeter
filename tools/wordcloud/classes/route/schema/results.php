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

namespace mootimetertool_wordcloud\route\schema;

use core\param;
use core\router\schema\objects\schema_object;
use core\router\schema\referenced_object;
use mod_mootimeter\route\schema\field;
use mod_mootimeter\route\schema\list_of;

/**
 * The counted answers of a wordcloud page.
 *
 * @package    mootimetertool_wordcloud
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results extends schema_object implements referenced_object {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(content: [
            'available' => new field(param::BOOL, 'Whether the current user may see the results.'),
            'words' => new list_of(new schema_object(content: [
                'text' => new field(param::TEXT, 'The word or statement.'),
                'count' => new field(param::INT, 'How often it was submitted.'),
            ]), 'The words with their counts, empty if the results are not available.'),
        ]);
    }
}
