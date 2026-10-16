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

namespace mootimetertool_quiz\route\api;

use mod_mootimeter\tests\route_testcase;
use mootimetertool_quiz\quiz;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the settings and results of quiz pages.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pages::class)]
#[CoversClass(quiz::class)]
final class pages_test extends route_testcase {
    /** @var \stdClass The quiz page. */
    private \stdClass $page;

    /** @var int[] The option ids of the page. */
    private array $optionids;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(pages::class);

        $this->page = $this->create_page('quiz');
        $quiz = new quiz();
        $quiz->replace_options($this->page, [['text' => 'Right', 'isCorrect' => true], ['text' => 'Wrong']]);
        $this->optionids = array_map('intval', array_keys($quiz->get_answer_options($this->page->id)));
    }

    /**
     * The correctness is only revealed to moderators until the correction is shown.
     */
    public function test_get_page(): void {
        $this->setUser($this->student);
        (new quiz())->submit_response($this->page, [$this->optionids[1]]);

        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id));
        $this->assertSame([$this->optionids[1]], $payload['selectedOptionIds']);
        $this->assertSame(1, $payload['maxAnswers']);
        $this->assertSame('bar', $payload['visualization']);
        $this->assertTrue($payload['supportsCorrectOptions']);
        $this->assertSame(['', ''], array_column($payload['options'], 'text'));
        $this->assertSame(['Right', 'Wrong'], array_column($payload['options'], 'textHtml'));
        $this->assertSame([false, false], array_column($payload['options'], 'isCorrect'));

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id));
        $this->assertSame(['Right', 'Wrong'], array_column($payload['options'], 'text'));
        $this->assertSame([true, false], array_column($payload['options'], 'isCorrect'));
        $this->assertSame([], $payload['selectedOptionIds']);
    }

    /**
     * Pages of other tools are not found.
     */
    public function test_get_page_of_other_tool(): void {
        $wordcloud = $this->create_page('wordcloud');
        $this->setUser($this->teacher);

        $this->assert_problem($this->api('GET', '/pages/' . $wordcloud->id), 404, 'pagenotfound');
    }

    /**
     * Moderators can change the settings.
     */
    public function test_update_page(): void {
        $this->setUser($this->teacher);

        $response = $this->api('PATCH', '/pages/' . $this->page->id, [
            'maxAnswers' => 0,
            'visualization' => 'pie',
            'correctionShown' => true,
        ]);

        $this->assert_valid_response($response);
        $payload = $this->payload($response);
        $this->assertSame(0, $payload['maxAnswers']);
        $this->assertSame('pie', $payload['visualization']);
        $this->assertTrue($payload['correctionShown']);

        // Participants see the correct options once the correction is shown.
        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id));
        $this->assertSame([true, false], array_column($payload['options'], 'isCorrect'));
    }

    /**
     * Invalid settings and participants are rejected.
     */
    public function test_update_page_errors(): void {
        $this->setUser($this->teacher);
        $this->assert_invalid_parameter_response($this->api('PATCH', '/pages/' . $this->page->id, ['visualization' => 'radar']));

        $this->setUser($this->student);
        $this->assert_problem($this->api('PATCH', '/pages/' . $this->page->id, ['maxAnswers' => 0]), 403, 'nopermissions');
    }

    /**
     * Participants only get the counts once the results are published.
     */
    public function test_get_results(): void {
        foreach ([$this->student, $this->teacher] as $user) {
            $this->setUser($user);
            (new quiz())->submit_response($this->page, [$this->optionids[0]]);
        }

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id . '/results'));
        $this->assertFalse($payload['available']);
        $this->assertSame([], $payload['options']);

        $this->setAdminUser();
        (new quiz())->set_tool_config($this->page, 'showonteacherpermission', '1');

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id . '/results'));
        $this->assertTrue($payload['available']);
        $this->assertSame([2, 0], array_column($payload['options'], 'count'));
        $this->assertSame(['Right', 'Wrong'], array_column($payload['options'], 'label'));
        $this->assertNotEmpty($payload['colors']);
    }

    /**
     * All routes declare scopes.
     */
    public function test_routes_are_scoped(): void {
        $this->assert_routes_are_scoped([pages::class, options::class, responses::class]);
    }
}
