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

namespace mod_mootimeter\local;

use mod_mootimeter\helper;

/**
 * Progress of a participant in a mootimeter activity, the base of the custom completion rules.
 *
 * Only pages visible to participants count. Anonymous pages do not count as correctly answered, so the completion does
 * not reveal how a participant answered them.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_calculator {
    /**
     * Calculate the progress of a user.
     *
     * @param int $instanceid
     * @param int $userid
     * @return array with the keys answered (number of answered pages), answeredall (whether all visible pages are
     *               answered, false without visible pages) and correct (number of correctly answered pages)
     */
    public function calculate(int $instanceid, int $userid): array {
        $pages = (new helper())->get_pages($instanceid, true);
        $anonymous = helper::get_tool_configs(array_keys($pages), 'anonymousmode');

        $bytool = [];
        foreach ($pages as $page) {
            $bytool[$page->tool][] = (int) $page->id;
        }

        $answered = 0;
        $correct = 0;
        foreach ($bytool as $tool => $pageids) {
            $toolhelper = helper::get_toolhelper($tool);
            $answered += count($toolhelper->get_answered_pageids($pageids, $userid));
            $named = array_values(array_filter($pageids, fn(int $pageid): bool => empty($anonymous[$pageid])));
            $correct += count($toolhelper->get_correct_pageids($named, $userid));
        }

        return [
            'answered' => $answered,
            'answeredall' => $pages && $answered === count($pages),
            'correct' => $correct,
        ];
    }
}
