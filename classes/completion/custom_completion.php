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

namespace mod_mootimeter\completion;

use core_completion\activity_custom_completion;
use mod_mootimeter\local\completion_calculator;

/**
 * Custom completion rules of mootimeter activities.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /** @var array|null Progress of the user, calculated once for all rules. */
    private ?array $progress = null;

    #[\Override]
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        $required = (int) $this->cm->customdata['customcompletionrules'][$rule];
        $this->progress ??= (new completion_calculator())->calculate((int) $this->cm->instance, $this->userid);

        $complete = match ($rule) {
            'completionanswerpages' => $this->progress['answered'] >= $required,
            'completionanswerall' => $this->progress['answeredall'],
            'completioncorrectpages' => $this->progress['correct'] >= $required,
        };
        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    #[\Override]
    public static function get_defined_custom_rules(): array {
        return ['completionanswerpages', 'completionanswerall', 'completioncorrectpages'];
    }

    #[\Override]
    public function get_custom_rule_descriptions(): array {
        $rules = $this->cm->customdata['customcompletionrules'];
        return [
            'completionanswerpages' => get_string(
                'completiondetail:answerpages',
                'mod_mootimeter',
                $rules['completionanswerpages'] ?? 0
            ),
            'completionanswerall' => get_string('completiondetail:answerall', 'mod_mootimeter'),
            'completioncorrectpages' => get_string(
                'completiondetail:correctpages',
                'mod_mootimeter',
                $rules['completioncorrectpages'] ?? 0
            ),
        ];
    }

    #[\Override]
    public function get_sort_order(): array {
        return ['completionview', 'completionanswerpages', 'completionanswerall', 'completioncorrectpages'];
    }
}
