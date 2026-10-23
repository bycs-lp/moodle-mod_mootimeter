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

use mod_mootimeter\helper;

/**
 * Tests for the store_answer web service of the wordcloud tool.
 *
 * @package    mootimetertool_wordcloud
 * @covers     \mootimetertool_wordcloud\external\store_answer
 * @copyright  2026 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class store_answer_test extends \advanced_testcase {
    /**
     * Create a wordcloud page with the given limits and a student who already answered "Cat" twice.
     *
     * @param int $maxinputsperuser
     * @param int $allowduplicateanswers
     * @return array [page, student]
     */
    private function setup_page(int $maxinputsperuser, int $allowduplicateanswers): array {
        $this->setAdminUser();
        /** @var \mootimetertool_wordcloud_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
        $course = $this->getDataGenerator()->create_course();
        $page = $toolgenerator->create_wordcloud_page($course);
        $helper = new helper();
        $helper->set_tool_config($page->id, 'maxinputsperuser', $maxinputsperuser);
        $helper->set_tool_config($page->id, 'allowduplicateanswers', $allowduplicateanswers);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $toolgenerator->create_answer($page->id, $student->id, 'Cat');
        $toolgenerator->create_answer($page->id, $student->id, 'Cat');
        return [$page, $student];
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Repeated answers neither bypass the per-user cap nor the duplicate check.
     *
     * @covers \mootimetertool_wordcloud\external\store_answer::execute
     */
    public function test_answer_limits_cannot_be_bypassed(): void {
        global $DB;
        $this->resetAfterTest();

        [$page, $student] = $this->setup_page(2, 1);
        $this->setUser($student);
        $result = store_answer::execute($page->id, 'Cat');
        $this->assertSame(helper::ERRORCODE_TO_MANY_ANSWERS, $result['code']);
        $this->assertSame(2, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));

        [$page, $student] = $this->setup_page(0, 0);
        $this->setUser($student);
        $result = store_answer::execute($page->id, 'Cat');
        $this->assertSame(helper::ERRORCODE_DUPLICATE_ANSWER, $result['code']);
        $this->assertSame(2, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * A new answer below the cap is stored.
     *
     * @covers \mootimetertool_wordcloud\external\store_answer::execute
     */
    public function test_new_answer_is_stored(): void {
        global $DB;
        $this->resetAfterTest();

        [$page, $student] = $this->setup_page(3, 0);
        $this->setUser($student);
        $result = store_answer::execute($page->id, 'Dog');
        $this->assertSame(helper::ERRORCODE_OK, $result['code']);
        $this->assertSame(3, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * The duplicate check ignores case, including multibyte case, in both directions.
     *
     * @covers \mootimetertool_wordcloud\external\store_answer::execute
     */
    public function test_duplicate_check_ignores_case(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \mootimetertool_wordcloud_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
        $course = $this->getDataGenerator()->create_course();
        $page = $toolgenerator->create_wordcloud_page($course);
        $helper = new helper();
        $helper->set_tool_config($page->id, 'maxinputsperuser', 0);
        $helper->set_tool_config($page->id, 'allowduplicateanswers', 0);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $toolgenerator->create_answer($page->id, $student->id, 'ÄPFEL');
        $toolgenerator->create_answer($page->id, $student->id, 'cat');
        $toolgenerator->create_answer($page->id, $student->id, 'Bird');
        $this->setUser($student);

        $expected = [
            'äpfel' => helper::ERRORCODE_DUPLICATE_ANSWER,
            'ÄPFEL' => helper::ERRORCODE_DUPLICATE_ANSWER,
            'CAT' => helper::ERRORCODE_DUPLICATE_ANSWER,
            'bird' => helper::ERRORCODE_DUPLICATE_ANSWER,
        ];
        foreach ($expected as $input => $code) {
            $result = store_answer::execute($page->id, (string) $input);
            $this->assertSame($code, $result['code'], 'Input: ' . $input);
        }
        $this->assertSame(3, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * The per-user cap counts only the rows of the current user.
     *
     * @covers \mootimetertool_wordcloud\external\store_answer::execute
     */
    public function test_cap_is_counted_per_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \mootimetertool_wordcloud_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
        $course = $this->getDataGenerator()->create_course();
        $page = $toolgenerator->create_wordcloud_page($course);
        $helper = new helper();
        $helper->set_tool_config($page->id, 'maxinputsperuser', 2);
        $helper->set_tool_config($page->id, 'allowduplicateanswers', 0);
        $first = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $second = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $toolgenerator->create_answer($page->id, $first->id, 'Cat');
        $toolgenerator->create_answer($page->id, $first->id, 'Dog');

        $this->setUser($first);
        $result = store_answer::execute($page->id, 'Mouse');
        $this->assertSame(helper::ERRORCODE_TO_MANY_ANSWERS, $result['code']);

        $this->setUser($second);
        $result = store_answer::execute($page->id, 'Cat');
        $this->assertSame(helper::ERRORCODE_OK, $result['code']);
        $this->assertSame(3, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }
}
