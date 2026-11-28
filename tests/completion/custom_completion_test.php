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

use mod_mootimeter\helper;
use mod_mootimeter\task\recalculate_completion;
use mootimetertool_quiz\quiz;
use mootimetertool_wordcloud\wordcloud;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Tests for the activity completion of mootimeter.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_completion::class)]
#[CoversClass(\mod_mootimeter\local\completion_calculator::class)]
#[CoversClass(recalculate_completion::class)]
#[CoversClass(helper::class)]
final class custom_completion_test extends \advanced_testcase {
    /** @var \stdClass The course. */
    private \stdClass $course;

    /** @var \stdClass The mootimeter module record with cmid. */
    private \stdClass $mootimeter;

    /** @var \stdClass A student. */
    private \stdClass $student;

    /**
     * Create a course with completion and a mootimeter activity with the given rules.
     *
     * @param array $rules completion rules of the activity
     */
    private function setup_activity(array $rules): void {
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['enablecompletion' => 1]);
        $this->mootimeter = $generator->create_module('mootimeter', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ] + $rules);
        $this->student = $generator->create_and_enrol($this->course, 'student');
    }

    /**
     * Create a page.
     *
     * @param string $tool
     * @param bool $visible
     * @return \stdClass
     */
    private function create_page(string $tool, bool $visible = true): \stdClass {
        $this->setAdminUser();
        return $this->getDataGenerator()->get_plugin_generator('mod_mootimeter')->create_page([
            'instance' => $this->mootimeter->id,
            'tool' => $tool,
            'visible' => $visible,
        ]);
    }

    /**
     * Answer a wordcloud page as the student.
     *
     * @param \stdClass $page
     * @return \stdClass the answer
     */
    private function answer(\stdClass $page): \stdClass {
        $this->setUser($this->student);
        return (new wordcloud())->submit_answer($page, 'Answer');
    }

    /**
     * Get the completion state of a rule for the student.
     *
     * @param string $rule
     * @return int
     */
    private function get_state(string $rule): int {
        $cm = get_fast_modinfo($this->course, $this->student->id)->get_cm($this->mootimeter->cmid);
        return (new custom_completion($cm, (int) $this->student->id))->get_state($rule);
    }

    /**
     * Get the stored overall completion state of the student.
     *
     * @return int
     */
    private function get_stored_state(): int {
        $cm = get_fast_modinfo($this->course)->get_cm($this->mootimeter->cmid);
        return (int) (new \completion_info($this->course))->get_data($cm, false, (int) $this->student->id)->completionstate;
    }

    /**
     * Only visible pages count, deleting an own answer takes the completion back immediately.
     */
    public function test_answer_pages(): void {
        $this->resetAfterTest();
        $this->setup_activity(['completionanswerpages' => 2]);
        $first = $this->create_page('wordcloud');
        $second = $this->create_page('wordcloud');
        $hidden = $this->create_page('wordcloud', false);

        $this->answer($hidden);
        $answer = $this->answer($first);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state('completionanswerpages'));

        $this->answer($second);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_state('completionanswerpages'));
        $this->assertSame(COMPLETION_COMPLETE, $this->get_stored_state());

        (new wordcloud())->delete_answer($first, $answer);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_stored_state());
    }

    /**
     * All visible pages must be answered, a new visible page makes the activity incomplete again in the background.
     */
    public function test_answer_all(): void {
        $this->resetAfterTest();
        $this->expectOutputRegex('/Recalculated the completion/');
        $this->setup_activity(['completionanswerall' => 1]);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state('completionanswerall'));

        $page = $this->create_page('wordcloud');
        $this->create_page('wordcloud', false);
        $this->answer($page);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_stored_state());

        $new = $this->create_page('wordcloud');
        $this->runAdhocTasks(recalculate_completion::class);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_stored_state());

        // Hiding the new page completes the activity again.
        (new helper())->set_page_visibility($new, false);
        $this->runAdhocTasks(recalculate_completion::class);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_stored_state());

        // Deleting all answers of a page takes the completion back.
        (new helper())->delete_all_answers($page);
        $this->runAdhocTasks(recalculate_completion::class);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_stored_state());
    }

    /**
     * A quiz page counts as correct with exactly the correct options. Polls and anonymous pages never count.
     */
    public function test_correct_pages(): void {
        $this->resetAfterTest();
        $this->expectOutputRegex('/Recalculated the completion/');
        $this->setup_activity(['completioncorrectpages' => 2]);
        $quiz = new quiz();

        $pages = [];
        $options = [];
        foreach (['exact', 'extra', 'anonymous'] as $key) {
            $pages[$key] = $this->create_page('quiz');
            $quiz->replace_options($pages[$key], [
                ['text' => 'A', 'isCorrect' => true],
                ['text' => 'B', 'isCorrect' => true],
                ['text' => 'C', 'isCorrect' => false],
            ]);
            $quiz->update_settings($pages[$key], ['maxAnswers' => 3]);
            $options[$key] = array_map('intval', array_keys($quiz->get_answer_options($pages[$key]->id)));
        }
        (new helper())->set_tool_config($pages['anonymous'], 'anonymousmode', '1');

        $this->setUser($this->student);
        $quiz->submit_response($pages['exact'], [$options['exact'][0], $options['exact'][1]]);
        $quiz->submit_response($pages['extra'], $options['extra']);
        $quiz->submit_response($pages['anonymous'], [$options['anonymous'][0], $options['anonymous'][1]]);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state('completioncorrectpages'));

        // Correcting the selection completes the rule.
        $quiz->submit_response($pages['extra'], [$options['extra'][1], $options['extra'][0]]);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_state('completioncorrectpages'));
        $this->assertSame(COMPLETION_COMPLETE, $this->get_stored_state());

        // Marking another option as correct is recalculated in the background.
        $this->setAdminUser();
        $option = $quiz->get_answer_options($pages['exact']->id)[$options['exact'][2]];
        $quiz->update_option($pages['exact'], $option, null, true);
        $this->runAdhocTasks(recalculate_completion::class);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_stored_state());
    }

    /**
     * Without custom rules the answers do not queue recalculations.
     */
    public function test_no_custom_rules(): void {
        $this->resetAfterTest();
        $this->setup_activity([]);
        $page = $this->create_page('wordcloud');
        $this->answer($page);
        (new helper())->delete_all_answers($page);

        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(recalculate_completion::class));
    }

    /**
     * The rule descriptions contain the required numbers.
     */
    public function test_get_custom_rule_descriptions(): void {
        $this->resetAfterTest();
        $this->setup_activity(['completionanswerpages' => 3, 'completioncorrectpages' => 1]);

        $cm = get_fast_modinfo($this->course)->get_cm($this->mootimeter->cmid);
        $descriptions = (new custom_completion($cm, (int) $this->student->id))->get_custom_rule_descriptions();

        $this->assertSame(get_string('completiondetail:answerpages', 'mod_mootimeter', 3), $descriptions['completionanswerpages']);
        $this->assertSame(
            get_string('completiondetail:correctpages', 'mod_mootimeter', 1),
            $descriptions['completioncorrectpages']
        );
    }
}
