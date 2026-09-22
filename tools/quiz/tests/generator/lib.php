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

/**
 * mootimetertool_quiz data generator
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mootimetertool_quiz_generator extends testing_module_generator {
    /** @var Toolname */
    const MTMT_TOOLNAME = 'quiz';

    /**
     * Create a quiz page inside a fresh mootimeter activity in the given course.
     *
     * @param stdClass $course
     * @return stdClass the page record
     */
    public function create_quiz_page(stdClass $course): stdClass {
        global $DB;
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $this->datagenerator->get_plugin_generator('mod_mootimeter');
        $module = $this->datagenerator->create_module('mootimeter', ['course' => $course->id]);
        $page = $mtmgenerator->create_page(['instance' => $module->id, 'tool' => self::MTMT_TOOLNAME]);
        // Pages are created hidden by default; reveal it so participants (non-moderators)
        // can actually reach it, mirroring a page a teacher has opened for answering.
        $DB->set_field('mootimeter_pages', 'visible', \mod_mootimeter\helper::PAGE_VISIBLE, ['id' => $page->id]);
        $page->visible = \mod_mootimeter\helper::PAGE_VISIBLE;
        // Keep the cmid handy for context lookups.
        $page->cmid = $module->cmid;
        return $page;
    }

    /**
     * Get the ids of the answer options of a page.
     *
     * Creating a quiz page implicitly creates two empty answer options, so in most cases
     * there is no need to create additional ones.
     *
     * @param int $pageid
     * @return int[] the answer option ids
     */
    public function get_answer_option_ids(int $pageid): array {
        global $DB;
        return array_map(
            fn($record) => (int) $record->id,
            array_values($DB->get_records('mootimetertool_quiz_options', ['pageid' => $pageid], 'id ASC', 'id'))
        );
    }

    /**
     * Store a quiz answer on a page and return its id.
     *
     * @param int $pageid
     * @param int $userid submitter
     * @param int $optionid the selected answer option
     * @return int the answer id
     */
    public function create_answer(int $pageid, int $userid, int $optionid): int {
        global $DB;
        return (int) $DB->insert_record('mootimetertool_quiz_answers', (object) [
            'pageid' => $pageid,
            'usermodified' => $userid,
            'optionid' => $optionid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }
}
