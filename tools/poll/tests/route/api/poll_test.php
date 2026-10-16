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

namespace mootimetertool_poll\route\api;

use mod_mootimeter\tests\route_testcase;
use mootimetertool_poll\poll;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the routes of poll pages.
 *
 * The routes share the implementation with quiz pages, so only the differences are tested.
 *
 * @package    mootimetertool_poll
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pages::class)]
#[CoversClass(options::class)]
#[CoversClass(responses::class)]
#[CoversClass(poll::class)]
final class poll_test extends route_testcase {
    /** @var \stdClass The poll page. */
    private \stdClass $page;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        foreach ([pages::class, options::class, responses::class] as $class) {
            $this->add_class_routes_to_route_loader($class);
        }
        $this->page = $this->create_page('poll');
    }

    /**
     * Poll pages have no correct options.
     */
    public function test_no_correct_options(): void {
        $this->setUser($this->teacher);

        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id));
        $this->assertFalse($payload['supportsCorrectOptions']);

        $path = '/pages/' . $this->page->id . '/options';
        $option = $this->payload($this->api('POST', $path, ['text' => 'Yes', 'isCorrect' => true]));
        $this->assertFalse($option['isCorrect']);

        $response = $this->api('PATCH', '/pages/' . $this->page->id, ['correctionShown' => true]);
        $this->assert_problem($response, 422, 'correctionnotsupported');
    }

    /**
     * Participants can respond.
     */
    public function test_create_response(): void {
        $optionid = (int) array_key_first((new poll())->get_answer_options($this->page->id));
        $this->setUser($this->student);

        $response = $this->api('POST', '/pages/' . $this->page->id . '/responses', ['optionIds' => [$optionid]]);

        $this->assert_valid_response($response, 201);
        $this->assertSame([$optionid], $this->payload($response)['optionIds']);
        $this->assertStringContainsString('/pages/' . $this->page->id . '/responses/', $response->getHeaderLine('Location'));
    }

    /**
     * Pages of other tools are not found.
     */
    public function test_quiz_page_not_found(): void {
        $quiz = $this->create_page('quiz');
        $this->setUser($this->teacher);

        $this->assert_problem($this->api('GET', '/pages/' . $quiz->id), 404, 'pagenotfound');
    }

    /**
     * All routes declare scopes.
     */
    public function test_routes_are_scoped(): void {
        $this->assert_routes_are_scoped([pages::class, options::class, responses::class]);
    }
}
