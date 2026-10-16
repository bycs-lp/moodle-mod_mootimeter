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
 * Tests for the instances resource.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(instances::class)]
#[CoversClass(\mod_mootimeter\route\api_util::class)]
#[CoversClass(\mod_mootimeter\route\parameters\path_mootimeter::class)]
#[CoversClass(\mod_mootimeter\local\access::class)]
final class instances_test extends route_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(instances::class);
        $this->add_class_routes_to_route_loader(pages::class);
    }

    /**
     * Moderators get all pages, participants only the visible ones.
     */
    public function test_get_pages(): void {
        $visible = $this->create_page('quiz', true, 'Visible question');
        $hidden = $this->create_page('wordcloud', false);
        $path = '/instances/' . $this->mootimeter->cmid . '/pages';

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('GET', $path));
        $this->assertSame([(int) $visible->id, (int) $hidden->id], array_column($payload['items'], 'id'));
        $this->assertSame('Visible question', $payload['items'][0]['title']);
        $this->assertSame([1, 2], array_column($payload['items'], 'number'));
        $this->assertSame(2, $payload['pagination']['totalItems']);

        $payload = $this->payload($this->api('GET', $path . '?visible=false'));
        $this->assertSame([(int) $hidden->id], array_column($payload['items'], 'id'));

        $payload = $this->payload($this->api('GET', $path . '?sort=-number&pagesize=1'));
        $this->assertSame([(int) $hidden->id], array_column($payload['items'], 'id'));
        $this->assertSame(1, $payload['pagination']['pageSize']);

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', $path));
        $this->assertSame([(int) $visible->id], array_column($payload['items'], 'id'));
    }

    /**
     * Unknown sort fields are rejected.
     */
    public function test_get_pages_unknown_sort(): void {
        $this->setUser($this->teacher);

        $response = $this->api('GET', '/instances/' . $this->mootimeter->cmid . '/pages?sort=title');

        $this->assert_invalid_parameter_response($response);
    }

    /**
     * Users without access to the course are rejected.
     */
    public function test_get_pages_access(): void {
        $path = '/instances/' . $this->mootimeter->cmid . '/pages';

        $this->assert_problem($this->api('GET', $path), 401);

        $this->setUser($this->outsider);
        $this->assert_problem($this->api('GET', $path), 403);
    }

    /**
     * Unknown activities are not found.
     */
    public function test_get_pages_not_found(): void {
        $this->setUser($this->teacher);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);

        $this->assert_problem($this->api('GET', '/instances/' . ($this->mootimeter->cmid + 1000) . '/pages'), 404);
        $this->assert_problem($this->api('GET', '/instances/' . $page->cmid . '/pages'), 404);
    }

    /**
     * Moderators can create pages.
     */
    public function test_create_page(): void {
        $this->setUser($this->teacher);

        $response = $this->api('POST', '/instances/' . $this->mootimeter->cmid . '/pages', ['tool' => 'quiz']);

        $this->assert_valid_response($response, 201);
        $payload = $this->payload($response);
        $this->assertSame('quiz', $payload['tool']);
        $this->assertSame((int) $this->mootimeter->cmid, $payload['cmId']);
        $this->assertStringEndsWith('/pages/' . $payload['id'], $response->getHeaderLine('Location'));
        $this->assertNotNull((new helper())->get_page($payload['id']));
    }

    /**
     * Creating pages validates the tool, the session key and the permission.
     */
    public function test_create_page_errors(): void {
        $path = '/instances/' . $this->mootimeter->cmid . '/pages';

        $this->setUser($this->teacher);
        $this->assert_problem($this->api('POST', $path, ['tool' => 'unknown']), 422, 'unknowntool');
        $this->assert_problem($this->api('POST', $path, ['tool' => 'quiz'], false), 403, 'invalidsesskey');

        $this->setUser($this->student);
        $this->assert_problem($this->api('POST', $path, ['tool' => 'quiz']), 403, 'nopermissions');

        $this->assertEmpty((new helper())->get_pages($this->mootimeter->id));
    }

    /**
     * The revisions change when data changes.
     */
    public function test_get_updates(): void {
        $clock = $this->mock_clock_with_frozen(1000);
        $page = $this->create_page('wordcloud');
        $hidden = $this->create_page('wordcloud', false);
        $path = '/instances/' . $this->mootimeter->cmid . '/updates';

        $this->setUser($this->student);
        $before = $this->payload($this->api('GET', $path . '?pageid=' . $page->id));
        $this->assertGreaterThan(0, $before['pageList']);
        $this->assertGreaterThan(0, $before['answers']);

        $clock->bump();
        (new \mootimetertool_wordcloud\wordcloud())->submit_answer($page, 'Answer');
        $after = $this->payload($this->api('GET', $path . '?pageid=' . $page->id));
        $this->assertSame($before['pageList'], $after['pageList']);
        $this->assertGreaterThan($before['answers'], $after['answers']);

        // Hidden pages are ignored for participants.
        $payload = $this->payload($this->api('GET', $path . '?pageid=' . $hidden->id));
        $this->assertSame(0, $payload['settings']);
        $this->assertSame(0, $payload['answers']);
    }
}
