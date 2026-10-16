<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Answer options, answers and results of poll pages.
 *
 * @package     mootimetertool_poll
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_poll;

/**
 * Poll tool: the quiz without correct options.
 *
 * @package     mootimetertool_poll
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll extends \mootimetertool_quiz\quiz {
    /** @var string Answer table. */
    const ANSWER_TABLE = 'mootimetertool_poll_answers';

    /** @var string Answer option table. */
    const ANSWER_OPTION_TABLE = 'mootimetertool_poll_options';

    #[\Override]
    public function supports_correct_options(): bool {
        return false;
    }

    /**
     * The poll reuses the React components of the quiz, but has no answer correction.
     *
     * @return array
     */
    #[\Override]
    public function get_react_components(): array {
        $components = (new \mootimetertool_quiz\quiz())->get_react_components();
        $components['menu'] = '';
        return $components;
    }
}
