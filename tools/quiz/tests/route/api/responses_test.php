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
 * Tests for the responses of quiz pages.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(responses::class)]
#[CoversClass(quiz::class)]
final class responses_test extends route_testcase {
    /** @var \stdClass The quiz page. */
    private \stdClass $page;

    /** @var int[] The option ids of the page. */
    private array $optionids;

    /** @var string Path of the responses. */
    private string $path;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(responses::class);

        $this->page = $this->create_page('quiz');
        $quiz = new quiz();
        $quiz->replace_options($this->page, [['text' => 'A'], ['text' => 'B'], ['text' => 'C']]);
        $quiz->update_settings($this->page, ['maxAnswers' => 2]);
        $this->optionids = array_map('intval', array_keys($quiz->get_answer_options($this->page->id)));
        $this->path = '/pages/' . $this->page->id . '/responses';
    }

    /**
     * A repeated submission replaces the selected options and keeps the id.
     */
    public function test_create_response(): void {
        $this->setUser($this->student);

        $response = $this->api('POST', $this->path, ['optionIds' => [$this->optionids[0], $this->optionids[1]]]);
        $this->assert_valid_response($response, 201);
        $first = $this->payload($response);
        $this->assertSame([$this->optionids[0], $this->optionids[1]], $first['optionIds']);
        $this->assertSame(fullname($this->student), $first['fullName']);
        $this->assertStringEndsWith($this->path . '/' . $first['id'], $response->getHeaderLine('Location'));

        $second = $this->payload($this->api('POST', $this->path, ['optionIds' => [$this->optionids[2]]]));
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame([$this->optionids[2]], $second['optionIds']);

        $payload = $this->payload($this->api('GET', $this->path . '/' . $first['id']));
        $this->assertSame([$this->optionids[2]], $payload['optionIds']);
    }

    /**
     * Invalid selections are rejected.
     */
    public function test_create_response_validation(): void {
        $other = $this->create_page('quiz');
        $foreignid = array_key_first((new quiz())->get_answer_options($other->id));
        $this->setUser($this->student);

        $this->assert_problem($this->api('POST', $this->path, ['optionIds' => []]), 422, 'atleastoneanswer');
        $this->assert_problem($this->api('POST', $this->path, ['optionIds' => [$foreignid]]), 422, 'unknownoption');
        $this->assert_problem($this->api('POST', $this->path, ['optionIds' => $this->optionids]), 422, 'toomanyanswers');
        $response = $this->api('POST', $this->path, ['optionIds' => [$this->optionids[0]]], false);
        $this->assert_problem($response, 403, 'invalidsesskey');

        $this->assertSame([], (new quiz())->get_responses($this->page));
    }

    /**
     * Answering is closed once the correction is shown.
     */
    public function test_create_response_closed(): void {
        $this->setAdminUser();
        (new quiz())->update_settings($this->page, ['correctionShown' => true]);

        $this->setUser($this->student);
        $this->assert_problem($this->api('POST', $this->path, ['optionIds' => [$this->optionids[0]]]), 409, 'answeringclosed');
    }

    /**
     * Moderators list the responses, anonymised in the anonymous mode.
     */
    public function test_get_responses(): void {
        $second = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        foreach ([$this->student, $second] as $index => $user) {
            $this->setUser($user);
            (new quiz())->submit_response($this->page, [$this->optionids[$index]]);
        }

        $this->assert_problem($this->api('GET', $this->path), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('GET', $this->path));
        $this->assertSame(2, $payload['pagination']['totalItems']);
        $this->assertSame([fullname($this->student), fullname($second)], array_column($payload['items'], 'fullName'));

        $payload = $this->payload($this->api('GET', $this->path . '?optionid=' . $this->optionids[1]));
        $this->assertSame([fullname($second)], array_column($payload['items'], 'fullName'));

        (new quiz())->set_tool_config($this->page, 'anonymousmode', '1');
        $payload = $this->payload($this->api('GET', $this->path));
        $this->assertNotContains(fullname($this->student), array_column($payload['items'], 'fullName'));
    }

    /**
     * Participants can only access their own response, moderators all.
     */
    public function test_response_access(): void {
        $second = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($second);
        $foreign = (new quiz())->submit_response($this->page, [$this->optionids[0]]);
        $this->setUser($this->student);
        $own = (new quiz())->submit_response($this->page, [$this->optionids[1]]);

        $this->assert_valid_response($this->api('GET', $this->path . '/' . $own['id']));
        $this->assert_problem($this->api('GET', $this->path . '/' . $foreign['id']), 403, 'nopermissions');
        $this->assert_problem($this->api('DELETE', $this->path . '/' . $foreign['id']), 403, 'nopermissions');
        $this->assert_problem($this->api('PUT', $this->path . '/' . $own['id'], ['optionIds' => [$this->optionids[2]]]), 403);
        $this->assert_problem($this->api('GET', $this->path . '/' . ($own['id'] + 1000)), 404, 'responsenotfound');

        $this->assert_valid_response($this->api('DELETE', $this->path . '/' . $own['id']), 204);
        $this->assertNull((new quiz())->get_response_of_user($this->page, (int) $this->student->id));

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('PUT', $this->path . '/' . $foreign['id'], ['optionIds' => [$this->optionids[2]]]));
        $this->assertSame([$this->optionids[2]], $payload['optionIds']);
        $this->assert_valid_response($this->api('DELETE', $this->path . '/' . $foreign['id']), 204);
        $this->assertSame([], (new quiz())->get_responses($this->page));
    }
}
