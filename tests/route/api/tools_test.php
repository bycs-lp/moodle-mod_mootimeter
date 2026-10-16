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

use mod_mootimeter\tests\route_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the tools resource.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tools::class)]
#[CoversClass(\mod_mootimeter\local\exporter::class)]
final class tools_test extends route_testcase {
    /**
     * The enabled tools are listed for authenticated users.
     */
    public function test_get_tools(): void {
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(tools::class);
        $this->setUser($this->getDataGenerator()->create_user());

        $response = $this->api('GET', '/tools');

        $this->assert_valid_response($response);
        $payload = $this->payload($response);
        $names = array_column($payload['items'], 'name');
        foreach (['quiz', 'poll', 'wordcloud'] as $tool) {
            $this->assertContains($tool, $names);
        }
        $this->assertSame(count($names), $payload['pagination']['totalItems']);
        $this->assertArrayHasKey('displayName', $payload['items'][0]);
        $this->assertArrayHasKey('iconUrl', $payload['items'][0]);
    }

    /**
     * Anonymous requests are rejected.
     */
    public function test_get_tools_unauthenticated(): void {
        $this->add_class_routes_to_route_loader(tools::class);

        $this->assert_problem($this->api('GET', '/tools'), 401);
    }

    /**
     * All routes declare scopes.
     */
    public function test_routes_are_scoped(): void {
        $this->assert_routes_are_scoped([tools::class, instances::class, pages::class]);
    }
}
