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

namespace mod_mootimeter\task;

use core\task\adhoc_task;

/**
 * Recalculate the completion of all participants of a mootimeter activity after changes affecting everybody.
 *
 * Unlike completion_info::reset_all_state() the stored states are kept, so the viewed state and the times of
 * completion of unaffected participants stay unchanged.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recalculate_completion extends adhoc_task {
    /** @var int Number of users recalculated between two progress messages. */
    const CHUNK_SIZE = 500;

    #[\Override]
    public function get_name(): string {
        return get_string('taskrecalculatecompletion', 'mod_mootimeter');
    }

    /**
     * Queue the recalculation of an activity, at most once until it ran.
     *
     * @param int $cmid
     */
    public static function queue(int $cmid): void {
        $task = new self();
        $task->set_custom_data(['cmid' => $cmid]);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    #[\Override]
    public function execute(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');

        $cmid = (int) $this->get_custom_data()->cmid;
        if (!$DB->record_exists('course_modules', ['id' => $cmid])) {
            // The activity has been deleted meanwhile.
            return;
        }
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'mootimeter');
        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm) != COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }

        foreach (array_chunk($this->get_affected_userids($cm), self::CHUNK_SIZE) as $index => $chunk) {
            foreach ($chunk as $userid) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
            mtrace('Recalculated the completion of ' . ($index * self::CHUNK_SIZE + count($chunk)) . ' users.');
        }
    }

    /**
     * Get the users whose completion may change: the ones with a stored state and the ones who answered.
     *
     * Users without both can not complete the custom rules, so the enrolled users need not be loaded.
     *
     * @param \core_course\cm_info $cm
     * @return int[]
     */
    protected function get_affected_userids(\core_course\cm_info $cm): array {
        global $DB;

        $userids = array_map('intval', $DB->get_fieldset_select(
            'course_modules_completion',
            'userid',
            'coursemoduleid = :cmid',
            ['cmid' => $cm->id]
        ));
        $toolpages = $DB->get_records_menu('mootimeter_pages', ['instance' => $cm->instance], '', 'id, tool');
        $bytool = [];
        foreach ($toolpages as $pageid => $tool) {
            $bytool[$tool][] = (int) $pageid;
        }
        foreach ($bytool as $tool => $pageids) {
            $table = \mod_mootimeter\helper::get_toolhelper($tool)->get_answer_table();
            [$insql, $params] = $DB->get_in_or_equal($pageids, SQL_PARAMS_NAMED);
            $rs = $DB->get_recordset_sql("SELECT DISTINCT usermodified FROM {" . $table . "} WHERE pageid $insql", $params);
            foreach ($rs as $record) {
                $userids[] = (int) $record->usermodified;
            }
            $rs->close();
        }
        return array_values(array_unique($userids));
    }
}
