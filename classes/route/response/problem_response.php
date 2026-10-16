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

namespace mod_mootimeter\route\response;

use core\param;
use core\router\response\exception_response;
use core\router\schema\objects\schema_object;
use core\router\schema\response\payload_response;
use GuzzleHttp\Psr7\Response;
use mod_mootimeter\exception\api_exception;
use mod_mootimeter\route\schema\field;
use mod_mootimeter\route\schema\list_of;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Error response in the format of RFC 9457 (problem details for HTTP APIs).
 *
 * Besides the standard members the response contains the stable error code and, for validation problems,
 * field level details.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class problem_response extends exception_response {
    #[\Override]
    public static function get_response(
        ServerRequestInterface $request,
        \Exception $exception,
        ...$extra,
    ): payload_response {
        $status = static::get_exception_status_code();
        $response = new Response($status);

        $payload = [
            'type' => 'about:blank',
            'title' => $response->getReasonPhrase(),
            'status' => $status,
            'detail' => $exception->getMessage(),
            'instance' => $request->getUri()->getPath(),
            'code' => $exception instanceof \core\exception\moodle_exception ? $exception->errorcode : '',
        ];
        if ($exception instanceof api_exception && $exception->details) {
            $payload['details'] = $exception->details;
        }

        return new problem_details(payload: $payload, request: $request, response: $response);
    }

    #[\Override]
    protected static function get_response_schema(): schema_object {
        return new schema_object(content: [
            'type' => new field(param::RAW, 'URI identifying the problem type, always about:blank.'),
            'title' => new field(param::TEXT, 'Short summary of the problem type.'),
            'status' => new field(param::INT, 'HTTP status code.'),
            'detail' => new field(param::TEXT, 'Localised explanation of this occurrence of the problem.'),
            'instance' => new field(param::RAW, 'Path of the request that caused the problem.'),
            'code' => new field(param::ALPHANUM, 'Stable machine-readable error code.'),
            'details' => new list_of(
                new schema_object(content: [
                    'field' => new field(param::ALPHANUM, 'Name of the invalid field.'),
                    'message' => new field(param::TEXT, 'Localised explanation.'),
                ]),
                description: 'Field level details of validation problems.',
            ),
        ]);
    }
}
