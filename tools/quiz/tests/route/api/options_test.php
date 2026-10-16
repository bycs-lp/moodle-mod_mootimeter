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
 * Tests for the answer options of quiz pages.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(options::class)]
#[CoversClass(quiz::class)]
final class options_test extends route_testcase {
    /** @var \stdClass The quiz page. */
    private \stdClass $page;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(options::class);
        $this->page = $this->create_page('quiz');
    }

    /**
     * Get the texts of the options of the page.
     *
     * @return string[]
     */
    private function get_option_texts(): array {
        return array_values(array_map(fn($option) => $option->optiontext, (new quiz())->get_answer_options($this->page->id)));
    }

    /**
     * Options can be added, read, changed and deleted by moderators.
     */
    public function test_option_lifecycle(): void {
        $path = '/pages/' . $this->page->id . '/options';
        $this->setUser($this->teacher);

        $response = $this->api('POST', $path, ['text' => '<b>Paris</b>', 'isCorrect' => true]);
        $this->assert_valid_response($response, 201);
        $option = $this->payload($response);
        $this->assertSame('Paris', $option['text']);
        $this->assertTrue($option['isCorrect']);
        $this->assertStringEndsWith($path . '/' . $option['id'], $response->getHeaderLine('Location'));

        $payload = $this->payload($this->api('GET', $path . '/' . $option['id']));
        $this->assertSame($option, $payload);

        $payload = $this->payload($this->api('PATCH', $path . '/' . $option['id'], ['text' => 'Rome']));
        $this->assertSame('Rome', $payload['text']);
        $this->assertTrue($payload['isCorrect']);

        $payload = $this->payload($this->api('GET', $path . '?sort=-text'));
        $this->assertSame(3, $payload['pagination']['totalItems']);
        $this->assertSame('Rome', $payload['items'][0]['text']);

        $this->assert_valid_response($this->api('DELETE', $path . '/' . $option['id']), 204);
        $this->assert_problem($this->api('GET', $path . '/' . $option['id']), 404, 'optionnotfound');
        $this->assertSame(['', ''], $this->get_option_texts());
    }

    /**
     * Participants can read but not change the options.
     */
    public function test_participant_access(): void {
        $path = '/pages/' . $this->page->id . '/options';
        $optionid = array_key_first((new quiz())->get_answer_options($this->page->id));

        $this->setUser($this->student);
        $this->assert_valid_response($this->api('GET', $path));
        $this->assert_problem($this->api('POST', $path, ['text' => 'New']), 403, 'nopermissions');
        $this->assert_problem($this->api('PATCH', $path . '/' . $optionid, ['text' => 'New']), 403, 'nopermissions');
        $this->assert_problem($this->api('DELETE', $path . '/' . $optionid), 403, 'nopermissions');
        $this->assert_problem($this->api('PUT', $path, ['items' => []]), 403, 'nopermissions');

        $this->assertSame(['', ''], $this->get_option_texts());
    }

    /**
     * Options of other pages are not found.
     */
    public function test_option_of_other_page(): void {
        $other = $this->create_page('quiz');
        $optionid = array_key_first((new quiz())->get_answer_options($other->id));
        $this->setUser($this->teacher);

        $path = '/pages/' . $this->page->id . '/options/' . $optionid;
        $this->assert_problem($this->api('GET', $path), 404, 'optionnotfound');
        $this->assert_problem($this->api('DELETE', $path), 404, 'optionnotfound');
    }

    /**
     * All options can be replaced at once, removed options lose their answers.
     */
    public function test_replace_options(): void {
        global $DB;

        $quiz = new quiz();
        [$keepid, $removeid] = array_keys($quiz->get_answer_options($this->page->id));
        $this->setUser($this->student);
        $quiz->submit_response($this->page, [$removeid]);

        $this->setUser($this->teacher);
        $response = $this->api('PUT', '/pages/' . $this->page->id . '/options', ['items' => [
            ['id' => $keepid, 'text' => 'Kept', 'isCorrect' => true],
            ['text' => 'Added'],
        ]]);

        $this->assert_valid_response($response, 204);
        $options = array_values($quiz->get_answer_options($this->page->id));
        $this->assertSame(['Kept', 'Added'], array_column($options, 'optiontext'));
        $this->assertEquals($keepid, $options[0]->id);
        $this->assertEquals(1, $options[0]->optioniscorrect);
        $this->assertFalse($DB->record_exists('mootimetertool_quiz_answers', ['optionid' => $removeid]));
    }

    /**
     * Foreign option ids and too many new options are rejected.
     */
    public function test_replace_options_errors(): void {
        $other = $this->create_page('quiz');
        $foreignid = array_key_first((new quiz())->get_answer_options($other->id));
        $path = '/pages/' . $this->page->id . '/options';
        $this->setUser($this->teacher);

        $this->assert_problem($this->api('PUT', $path, ['items' => [['id' => $foreignid, 'text' => 'X']]]), 422, 'unknownoption');

        $items = array_fill(0, quiz::MAX_IMPORT_OPTIONS + 1, ['text' => 'Option']);
        $this->assert_problem($this->api('PUT', $path, ['items' => $items]), 422, 'toomanyoptions');

        $this->assertSame(['', ''], $this->get_option_texts());
    }
}
