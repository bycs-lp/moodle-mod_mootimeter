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

namespace mod_mootimeter\tests;

use GuzzleHttp\Psr7\Utils;
use mod_mootimeter\route\parameters\header_sesskey;
use Psr\Http\Message\ResponseInterface;

/**
 * Base class for the tests of the REST API routes of mod_mootimeter and its tools.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class route_testcase extends \core\tests\router\route_testcase {
    /** @var \stdClass The course. */
    protected \stdClass $course;

    /** @var \stdClass The mootimeter module record with cmid. */
    protected \stdClass $mootimeter;

    /** @var \stdClass An editing teacher of the course. */
    protected \stdClass $teacher;

    /** @var \stdClass A student of the course. */
    protected \stdClass $student;

    /** @var \stdClass A user who is not enrolled in the course. */
    protected \stdClass $outsider;

    /**
     * Create a course with a mootimeter activity and users.
     */
    protected function setup_activity(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->mootimeter = $generator->create_module('mootimeter', ['course' => $this->course->id]);
        $this->teacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->student = $generator->create_and_enrol($this->course, 'student');
        $this->outsider = $generator->create_user();
    }

    /**
     * Create a page in the activity.
     *
     * @param string $tool
     * @param bool $visible
     * @param string $question
     * @return \stdClass the page record
     */
    protected function create_page(string $tool, bool $visible = true, string $question = ''): \stdClass {
        /** @var \mod_mootimeter_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
        return $generator->create_page([
            'instance' => $this->mootimeter->id,
            'tool' => $tool,
            'visible' => $visible,
            'question' => $question,
        ]);
    }

    /**
     * Process an API request as the current user.
     *
     * @param string $method
     * @param string $path path below the API group, including the component
     * @param array|null $body JSON body
     * @param bool $sesskey whether the session key header is sent
     * @return ResponseInterface
     */
    protected function api(string $method, string $path, ?array $body = null, bool $sesskey = true): ResponseInterface {
        $headers = ['HTTP_ACCEPT' => 'application/json'];
        if ($sesskey) {
            $headers[header_sesskey::NAME] = sesskey();
        }
        return $this->process_api_request(
            method: $method,
            path: $path,
            headers: $headers,
            body: $body === null ? null : Utils::streamFor(json_encode($body)),
        );
    }

    /**
     * Decode the JSON payload of a response.
     *
     * @param ResponseInterface $response
     * @return array
     */
    protected function payload(ResponseInterface $response): array {
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertIsArray(
            $payload,
            'The response is no JSON object: ' . $response->getStatusCode() . ' ' . $response->getBody()
        );
        return $payload;
    }

    /**
     * Assert a problem details response.
     *
     * @param ResponseInterface $response
     * @param int $status the expected HTTP status
     * @param string|null $code the expected error code
     */
    protected function assert_problem(ResponseInterface $response, int $status, ?string $code = null): void {
        $this->assertSame($status, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaderLine('Content-Type'));
        $payload = $this->payload($response);
        $this->assertSame($status, $payload['status']);
        if ($code !== null) {
            $this->assertSame($code, $payload['code']);
        }
    }

    /**
     * Assert that all routes of the classes declare scopes.
     *
     * @param string[] $classes
     */
    protected function assert_routes_are_scoped(array $classes): void {
        foreach ($classes as $class) {
            foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getAttributes(\core\router\route::class)) {
                    $this->assert_route_is_scoped([$class, $method->getName()]);
                }
            }
        }
    }
}
