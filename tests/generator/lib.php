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
 * mod_mootimeter data generator
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mootimeter_generator extends testing_module_generator {
    /** @var Mootimeter default tool */
    const MTMT_DEFAULT_TOOLNAME_WORDCLOUD = 'wordcloud';

    /**
     * Creates an instance of a mootimeter.
     *
     * @param ?array $record
     * @param ?array $options
     * @return stdClass mootimeter instance
     */
    public function create_instance($record = null, ?array $options = null): stdClass {

        $newrecord = [];
        $newrecord['name'] = (empty($record['name'])) ? 'Test Mootimeter' : $record['name'];
        $newrecord['intro'] = (empty($record['intro'])) ? 'This is a test description' : $record['intro'];
        $newrecord['introformat'] = (empty($record['introformat'])) ? 1 : $record['introformat'];

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Creates a mootimetertool_{tool} page.
     *
     * @param advanced_testcase $atc
     * @param array $record
     * @return stdClass
     * @throws dml_exception
     * @throws coding_exception
     * @throws required_capability_exception
     */
    public function create_page(advanced_testcase $atc, $record = []): stdClass {

        $record = (array) $record;

        $tool = (empty($record['tool'])) ? self::MTMT_DEFAULT_TOOLNAME_WORDCLOUD : $record['tool'];

        if (empty($record['instance'])) {
            mtrace('mootimetertool_' . $tool . " instance is missing.");
            return new stdClass();
        }

        $mtmhelper = new \mod_mootimeter\helper();
        $record = $record + [
            'tool' => $tool,
            'title' => 'Test ' . $tool . " page",
            'timemodified' => time(),
            'sortorder' => $mtmhelper->get_page_next_sortorder($record['instance']),
            'visible' => $mtmhelper::PAGE_VISIBLE,
        ];

        $atc->setAdminUser();

        $pageid = $mtmhelper->store_page((object)$record);

        return $mtmhelper->get_page($pageid);
    }

    /**
     * Create a quiz/poll answer option.
     * @param object $page
     * @param object $record
     * @return void
     */
    public function create_quiz_poll_answer_option(object $page, object $record): void {
        global $USER;

        $record = new stdClass();
        $record->pageid = $page->id;
        $record->usermodified = (empty($record->userid)) ? $USER->id : $record->userid;
        $record->optiontext = (empty($record->optiontext)) ? 'Default Option' : $record->optiontext;
        if ($page->tool == "quiz") {
            $record->optioniscorrect = (empty($record->optioniscorrect)) ? 0 : $record->optioniscorrect;
        }
        $record->timecreated = time();

        if ($page->tool == "poll") {
            $mtmthelper = new \mootimetertool_poll\poll();
        }
        if ($page->tool == "quiz") {
            $mtmthelper = new \mootimetertool_quiz\quiz();
        }

        // Store two answer options as default.
        $mtmthelper->store_answer_option($record);
        return;
    }
}
