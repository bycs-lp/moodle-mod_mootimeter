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
     * Creates a page with the tool hooks, like a moderator does.
     *
     * @param array $record with the key instance and the optional keys tool, visible and question
     * @return stdClass the page record
     * @throws \core\exception\coding_exception if the instance is missing
     */
    public function create_page($record = []): stdClass {
        global $DB;

        $record = (array) $record;
        if (empty($record['instance'])) {
            throw new \core\exception\coding_exception('The mootimeter instance of the page is missing.');
        }

        $helper = new \mod_mootimeter\helper();
        $cm = $helper::get_cm_by_instance((int) $record['instance']);
        $page = $helper->create_page($cm, $record['tool'] ?? self::MTMT_DEFAULT_TOOLNAME_WORDCLOUD);

        $visible = (bool) ($record['visible'] ?? true);
        if ((int) $page->visible !== (int) $visible) {
            $helper->set_page_visibility($page, $visible);
        }
        if (isset($record['question'])) {
            $helper->set_tool_config($page, 'question', $record['question']);
        }

        return $DB->get_record('mootimeter_pages', ['id' => $page->id], '*', MUST_EXIST);
    }

    /**
     * Create an answer option of a quiz or poll page.
     *
     * @param stdClass $page
     * @param string $text
     * @param bool $iscorrect only used by quiz pages
     * @return stdClass the option record
     */
    public function create_quiz_poll_answer_option(
        stdClass $page,
        string $text = 'Default Option',
        bool $iscorrect = false,
    ): stdClass {
        return \mod_mootimeter\helper::get_toolhelper($page->tool)->create_option($page, $text, $iscorrect);
    }
}
