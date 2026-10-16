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
 * Tests for the settings and results of wordcloud pages.
 *
 * @package    mootimetertool_wordcloud
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pages::class)]
#[CoversClass(wordcloud::class)]
final class pages_test extends route_testcase {
    /** @var \stdClass The wordcloud page. */
    private \stdClass $page;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->setup_activity();
        $this->add_class_routes_to_route_loader(pages::class);
        $this->page = $this->create_page('wordcloud');
    }

    /**
     * Participants get their own answers and the settings.
     */
    public function test_get_page(): void {
        $this->setUser($this->student);
        (new wordcloud())->submit_answer($this->page, 'Own');
        $this->setUser($this->teacher);
        (new wordcloud())->submit_answer($this->page, 'Foreign');

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', '/pages/' . $this->page->id));
        $this->assertSame(['Own'], array_column($payload['ownAnswers'], 'text'));
        $this->assertSame(0, $payload['maxAnswers']);
        $this->assertFalse($payload['allowDuplicateAnswers']);
    }

    /**
     * Moderators can change the settings.
     */
    public function test_update_page(): void {
        $path = '/pages/' . $this->page->id;

        $this->setUser($this->student);
        $this->assert_problem($this->api('PATCH', $path, ['maxAnswers' => 3]), 403, 'nopermissions');

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('PATCH', $path, ['maxAnswers' => 3, 'allowDuplicateAnswers' => true]));
        $this->assertSame(3, $payload['maxAnswers']);
        $this->assertTrue($payload['allowDuplicateAnswers']);

        $payload = $this->payload($this->api('PATCH', $path, ['maxAnswers' => -1]));
        $this->assertSame(0, $payload['maxAnswers']);
        $this->assertTrue($payload['allowDuplicateAnswers']);
    }

    /**
     * Participants only get the words once the results are published.
     */
    public function test_get_results(): void {
        foreach (['open', 'open', 'flexible'] as $text) {
            $this->setUser($this->getDataGenerator()->create_and_enrol($this->course, 'student'));
            (new wordcloud())->submit_answer($this->page, $text);
        }
        $path = '/pages/' . $this->page->id . '/results';

        $this->setUser($this->student);
        $payload = $this->payload($this->api('GET', $path));
        $this->assertFalse($payload['available']);
        $this->assertSame([], $payload['words']);

        $this->setUser($this->teacher);
        $payload = $this->payload($this->api('GET', $path));
        $this->assertTrue($payload['available']);
        $counts = array_column($payload['words'], 'count', 'text');
        ksort($counts);
        $this->assertSame(['flexible' => 1, 'open' => 2], $counts);
    }

    /**
     * All routes declare scopes.
     */
    public function test_routes_are_scoped(): void {
        $this->assert_routes_are_scoped([pages::class, answers::class]);
    }
}
