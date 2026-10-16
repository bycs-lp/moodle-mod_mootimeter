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

namespace mod_mootimeter\route\api;

use mod_mootimeter\helper;
use mod_mootimeter\tests\route_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the pages resource.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pages::class)]
#[CoversClass(\mod_mootimeter\route\parameters\path_page::class)]
#[CoversClass(\mod_mootimeter\route\response\problem_response::class)]
#[CoversClass(\mod_mootimeter\local\access::class)]
#[CoversClass(\mod_mootimeter\local\exporter::class)]
final class pages_test extends route_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(pages::class);
    }

    /**
     * Store a wordcloud answer as a user.
     *
     * @param \stdClass $page
     * @param \stdClass $user
     */
    private function answer(\stdClass $page, \stdClass $user): void {
        $this->setUser($user);
        (new \mootimetertool_wordcloud\wordcloud())->submit_answer($page, 'Answer');
    }

    /**
     * Moderators get the editable question, participants only the formatted one.
     */
    public function test_get_page(): void {
        $page = $this->create_page('wordcloud', true, 'What <b>now</b>?');

        $this->setUser($this->teacher);
        $response = $this->api('GET', '/pages/' . $page->id);
        $this->assert_valid_response($response);
        $payload = $this->payload($response);
        $this->assertSame((int) $page->id, $payload['id']);
        $this->assertSame('wordcloud', $payload['tool']);
        $this->assertSame('What <b>now</b>?', $payload['question']);
        $this->assertFalse($payload['hasAnswers']);
        $this->assertTrue($payload['canViewResults']);
        $this->assertNotEmpty($payload['components']);

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', '/pages/' . $page->id));
        $this->assertSame('', $payload['question']);
        $this->assertSame('What now?', $payload['questionHtml']);
        $this->assertFalse($payload['canViewResults']);
    }

    /**
     * Hidden pages, unknown pages and other users are rejected.
     */
    public function test_get_page_access(): void {
        $hidden = $this->create_page('wordcloud', false);

        $this->assert_problem($this->api('GET', '/pages/' . $hidden->id), 401);

        $this->setUser($this->student);
        $this->assert_problem($this->api('GET', '/pages/' . $hidden->id), 403, 'pagelocked');
        $this->assert_problem($this->api('GET', '/pages/' . ($hidden->id + 1000)), 404, 'pagenotfound');

        $this->setUser($this->outsider);
        $this->assert_problem($this->api('GET', '/pages/' . $hidden->id), 403);

        $this->setUser($this->teacher);
        $this->assert_valid_response($this->api('GET', '/pages/' . $hidden->id));
    }

    /**
     * Moderators can change the common properties of a page.
     */
    public function test_update_page(): void {
        $page = $this->create_page('wordcloud', false);
        $other = $this->create_page('quiz');

        $this->setUser($this->teacher);
        $response = $this->api('PATCH', '/pages/' . $page->id, [
            'question' => '<script>alert(1)</script>Question',
            'visible' => true,
            'resultsPublished' => true,
            'anonymousMode' => true,
            'position' => 1,
        ]);

        $this->assert_valid_response($response);
        $payload = $this->payload($response);
        $this->assertSame('alert(1)Question', $payload['question']);
        $this->assertTrue($payload['visible']);
        $this->assertTrue($payload['resultsPublished']);
        $this->assertTrue($payload['anonymousMode']);
        $this->assertSame([(int) $other->id, (int) $page->id], array_map('intval', array_keys(
            (new helper())->get_pages($this->mootimeter->id)
        )));

        // Properties that are not sent are kept.
        $payload = $this->payload($this->api('PATCH', '/pages/' . $page->id, ['visible' => false]));
        $this->assertFalse($payload['visible']);
        $this->assertTrue($payload['resultsPublished']);
    }

    /**
     * The anonymous mode can not be changed once there are answers.
     */
    public function test_update_page_anonymous_mode_locked(): void {
        $page = $this->create_page('wordcloud');
        $this->answer($page, $this->student);

        $this->setUser($this->teacher);
        $this->assert_problem($this->api('PATCH', '/pages/' . $page->id, ['anonymousMode' => true]), 409, 'anonymousmodelocked');

        // Sending the unchanged value is fine.
        $this->assert_valid_response($this->api('PATCH', '/pages/' . $page->id, ['anonymousMode' => false]));
    }

    /**
     * Participants can not change pages and the session key is required.
     */
    public function test_update_page_errors(): void {
        $page = $this->create_page('wordcloud');

        $this->setUser($this->student);
        $this->assert_problem($this->api('PATCH', '/pages/' . $page->id, ['visible' => false]), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $this->assert_problem($this->api('PATCH', '/pages/' . $page->id, ['visible' => false], false), 403, 'invalidsesskey');

        $this->assertEquals(helper::PAGE_VISIBLE, (new helper())->get_page($page->id)->visible);
    }

    /**
     * Moderators can delete pages.
     */
    public function test_delete_page(): void {
        $page = $this->create_page('wordcloud');

        $this->setUser($this->student);
        $this->assert_problem($this->api('DELETE', '/pages/' . $page->id), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $response = $this->api('DELETE', '/pages/' . $page->id);
        $this->assert_valid_response($response, 204);
        $this->assertSame('', (string) $response->getBody());
        $this->assertNull((new helper())->get_page($page->id));

        $this->assert_problem($this->api('DELETE', '/pages/' . $page->id), 404, 'pagenotfound');
    }

    /**
     * Moderators can delete all answers of a page.
     */
    public function test_delete_answers(): void {
        $page = $this->create_page('wordcloud');
        $this->answer($page, $this->student);

        $this->setUser($this->student);
        $this->assert_problem($this->api('DELETE', '/pages/' . $page->id . '/answers'), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $this->assert_valid_response($this->api('DELETE', '/pages/' . $page->id . '/answers'), 204);
        $this->assertFalse((new helper())->has_answers($page));
    }
}
