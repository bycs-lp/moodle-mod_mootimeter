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

namespace mootimetertool_wordcloud\route\api;

use mod_mootimeter\tests\route_testcase;
use mootimetertool_wordcloud\wordcloud;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the answers of wordcloud pages.
 *
 * @package    mootimetertool_wordcloud
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(answers::class)]
#[CoversClass(wordcloud::class)]
final class answers_test extends route_testcase {
    /** @var \stdClass The wordcloud page. */
    private \stdClass $page;

    /** @var string Path of the answers. */
    private string $path;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(answers::class);
        $this->page = $this->create_page('wordcloud');
        $this->path = '/pages/' . $this->page->id . '/answers';
    }

    /**
     * Participants add answers, HTML is removed.
     */
    public function test_create_answer(): void {
        $this->setUser($this->student);

        $response = $this->api('POST', $this->path, ['text' => ' <b>Moodle</b> ']);

        $this->assert_valid_response($response, 201);
        $answer = $this->payload($response);
        $this->assertSame('Moodle', $answer['text']);
        $this->assertSame(fullname($this->student), $answer['fullName']);
        $this->assertStringEndsWith($this->path . '/' . $answer['id'], $response->getHeaderLine('Location'));
        $this->assertSame($answer, $this->payload($this->api('GET', $this->path . '/' . $answer['id'])));
    }

    /**
     * Empty, too long, duplicate and too many answers are rejected.
     */
    public function test_create_answer_errors(): void {
        $this->setAdminUser();
        (new wordcloud())->update_settings($this->page, ['maxAnswers' => 2]);
        $this->setUser($this->student);

        $this->assert_problem($this->api('POST', $this->path, ['text' => '<p> </p>']), 422, 'emptyanswer');
        $longtext = str_repeat('a', wordcloud::MAX_ANSWER_LENGTH + 1);
        $this->assert_problem($this->api('POST', $this->path, ['text' => $longtext]), 422, 'answertoolong');

        $this->assert_valid_response($this->api('POST', $this->path, ['text' => 'Moodle']), 201);
        $this->assert_problem($this->api('POST', $this->path, ['text' => 'MOODLE']), 409, 'duplicateanswer');
        $this->assert_valid_response($this->api('POST', $this->path, ['text' => 'Open']), 201);
        $this->assert_problem($this->api('POST', $this->path, ['text' => 'Flexible']), 409, 'toomanyanswers');
    }

    /**
     * Duplicate answers are allowed if configured.
     */
    public function test_create_answer_duplicates_allowed(): void {
        $this->setAdminUser();
        (new wordcloud())->update_settings($this->page, ['allowDuplicateAnswers' => true]);
        $this->setUser($this->student);

        $this->assert_valid_response($this->api('POST', $this->path, ['text' => 'Moodle']), 201);
        $this->assert_valid_response($this->api('POST', $this->path, ['text' => 'Moodle']), 201);
    }

    /**
     * Moderators list, filter and change answers.
     */
    public function test_moderate_answers(): void {
        $this->setUser($this->student);
        $own = (new wordcloud())->submit_answer($this->page, 'Moodle');
        (new wordcloud())->submit_answer($this->page, 'Open');

        $this->assert_problem($this->api('GET', $this->path), 403, 'nopermissions');
        $this->assert_problem($this->api('PATCH', $this->path . '/' . $own->id, ['text' => 'Changed']), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('GET', $this->path . '?sort=text'));
        $this->assertSame(['Moodle', 'Open'], array_column($payload['items'], 'text'));

        $payload = $this->payload($this->api('GET', $this->path . '?text=moodle'));
        $this->assertSame([(int) $own->id], array_column($payload['items'], 'id'));

        $payload = $this->payload($this->api('PATCH', $this->path . '/' . $own->id, ['text' => 'Changed']));
        $this->assertSame('Changed', $payload['text']);
        $this->assert_problem($this->api('PATCH', $this->path . '/' . $own->id, ['text' => '']), 422, 'emptyanswer');
    }

    /**
     * Participants can only access their own answers.
     */
    public function test_answer_access(): void {
        $this->setUser($this->teacher);
        $foreign = (new wordcloud())->submit_answer($this->page, 'Foreign');
        $this->setUser($this->student);
        $own = (new wordcloud())->submit_answer($this->page, 'Own');

        $this->assert_problem($this->api('GET', $this->path . '/' . $foreign->id), 403, 'nopermissions');
        $this->assert_problem($this->api('DELETE', $this->path . '/' . $foreign->id), 403, 'nopermissions');
        $this->assert_problem($this->api('GET', $this->path . '/' . ($own->id + 1000)), 404, 'answernotfound');

        $this->assert_valid_response($this->api('DELETE', $this->path . '/' . $own->id), 204);
        $this->assert_problem($this->api('GET', $this->path . '/' . $own->id), 404, 'answernotfound');

        $this->setUser($this->teacher);
        $this->assert_valid_response($this->api('DELETE', $this->path . '/' . $foreign->id), 204);
    }

    /**
     * Hidden pages can not be answered by participants.
     */
    public function test_hidden_page(): void {
        $hidden = $this->create_page('wordcloud', false);
        $this->setUser($this->student);

        $this->assert_problem($this->api('POST', '/pages/' . $hidden->id . '/answers', ['text' => 'Moodle']), 403, 'pagelocked');
    }
}
