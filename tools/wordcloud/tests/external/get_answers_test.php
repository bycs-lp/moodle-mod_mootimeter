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

namespace mootimetertool_wordcloud\external;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the get_answers web service of the wordcloud tool.
 *
 * @package    mootimetertool_wordcloud
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_answers::class)]
final class get_answers_test extends \advanced_testcase {
    /**
     * Create a released wordcloud page with one answer and a student.
     *
     * @param int $visible page visibility
     * @return array [page, student]
     */
    private function setup_page(int $visible): array {
        global $DB;
        $this->setAdminUser();
        /** @var \mootimetertool_wordcloud_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
        $course = $this->getDataGenerator()->create_course();
        $page = $toolgenerator->create_wordcloud_page($course);
        $DB->set_field('mootimeter_pages', 'visible', $visible, ['id' => $page->id]);
        (new \mod_mootimeter\helper())->set_tool_config($page->id, 'showonteacherpermission', '1');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $toolgenerator->create_answer($page->id, $teacher->id, 'secretword');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        return [$page, $student];
    }

    #[Group('baseline')]
    /**
     * A student cannot read the answers of a hidden page.
     *
     * @return void
     */
    public function test_hidden_page_answers_are_not_returned(): void {
        $this->resetAfterTest();
        [$page, $student] = $this->setup_page(\mod_mootimeter\helper::PAGE_UNVISIBLE);
        $this->setUser($student);

        $this->expectException(\moodle_exception::class);
        get_answers::execute($page->id);
    }

    #[Group('baseline')]
    /**
     * A student still reads the answers of a visible page.
     *
     * @return void
     */
    public function test_visible_page_answers_are_returned(): void {
        $this->resetAfterTest();
        [$page, $student] = $this->setup_page(\mod_mootimeter\helper::PAGE_VISIBLE);
        $this->setUser($student);

        $result = get_answers::execute($page->id);

        $this->assertStringContainsString('secretword', json_encode($result['answerlist']));
    }
}
