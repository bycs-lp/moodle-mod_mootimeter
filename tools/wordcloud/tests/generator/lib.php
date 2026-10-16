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
 * mootimetertool_wordcloud data generator
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mootimetertool_wordcloud_generator extends component_generator_base {
    /** @var string Toolname */
    const MTMT_TOOLNAME = 'wordcloud';

    /**
     * Create a visible wordcloud page inside a fresh mootimeter activity in the given course.
     *
     * @param stdClass $course
     * @return stdClass the page record with the additional key cmid
     */
    public function create_wordcloud_page(stdClass $course): stdClass {
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $this->datagenerator->get_plugin_generator('mod_mootimeter');
        $module = $this->datagenerator->create_module('mootimeter', ['course' => $course->id]);
        $page = $mtmgenerator->create_page(['instance' => $module->id, 'tool' => self::MTMT_TOOLNAME]);
        $page->cmid = $module->cmid;
        return $page;
    }

    /**
     * Store a wordcloud answer on a page and return its id.
     *
     * @param int $pageid
     * @param int $userid submitter
     * @param string $answer
     * @return int the answer id
     */
    public function create_answer(int $pageid, int $userid, string $answer): int {
        global $DB;
        $answerid = (int) $DB->insert_record('mootimetertool_wordcloud_answers', (object) [
            'pageid' => $pageid,
            'usermodified' => $userid,
            'answer' => $answer,
            'timecreated' => \core\di::get(\core\clock::class)->time(),
            'timemodified' => 0,
        ]);
        (new \mod_mootimeter\helper())->clear_caches($pageid);
        return $answerid;
    }
}
