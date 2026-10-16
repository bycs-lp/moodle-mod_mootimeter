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

use core\param;
use core\router\route;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\path_parameter;
use core\router\schema\parameters\query_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_mootimeter\exception\forbidden_exception;
use mod_mootimeter\helper;
use mod_mootimeter\local\access;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\collection;
use mod_mootimeter\route\schema\field;
use mod_mootimeter\route\schema\list_of;
use mootimetertool_quiz\quiz;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The responses of the participants of a page: the options each participant selected.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}/responses',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class responses {
    /** @var string The tool of the pages. */
    const TOOL = 'quiz';

    /**
     * Get the tool helper.
     *
     * @return quiz
     */
    protected static function get_tool(): quiz {
        return helper::get_toolhelper(static::TOOL);
    }

    /**
     * Get a response the current user may access: moderators all, participants their own.
     *
     * @param access $access
     * @param ServerRequestInterface $request
     * @return array
     * @throws forbidden_exception
     */
    protected static function get_accessible_response(access $access, ServerRequestInterface $request): array {
        global $USER;

        $response = static::get_tool()->get_response($access->get_page(), (int) $request->getAttribute('responseId'));
        if (!$access->ismoderator && $response['userid'] !== (int) $USER->id) {
            throw new forbidden_exception('nopermissions', 'error', get_capability_string('mod/mootimeter:moderator'));
        }
        return $response;
    }

    /**
     * List the responses of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['GET'],
        title: 'List the responses of a page',
        description: 'Only moderators can list the responses. In the anonymous mode the names are replaced by a placeholder.',
        queryparams: [
            new \mod_mootimeter\route\parameters\query_page(),
            new \mod_mootimeter\route\parameters\query_pagesize(),
            new \mod_mootimeter\route\parameters\query_sort(['createdat', 'fullname', 'id']),
            new query_parameter(name: 'optionid', type: param::INT, description: 'Only return responses selecting this option.'),
        ],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new collection(new \mootimetertool_quiz\route\schema\response()))],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_responses(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();
        $tool = static::get_tool();

        $responses = $tool->get_responses($mootimeterpage);
        $optionid = (int) ($request->getQueryParams()['optionid'] ?? 0);
        if ($optionid) {
            $responses = array_filter($responses, fn(array $item): bool => in_array($optionid, $item['optionids'], true));
        }
        $items = array_map(fn(array $item): array => $tool->export_response($mootimeterpage, $item), $responses);

        return new payload_response(
            payload: api_util::collection($request, $items, ['createdat' => 'createdAt', 'fullname' => 'fullName', 'id' => 'id']),
            request: $request,
            response: $response,
        );
    }

    /**
     * Submit the response of the current user.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['POST'],
        title: 'Submit the response of the current user',
        description: 'A participant has one response per page: a repeated submission replaces the selected options of '
            . 'the previous response and keeps its id. Answering is closed once the correction is shown.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'optionIds' => new list_of(new field(param::INT), 'Ids of the selected options.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(
                statuscode: 201,
                description: 'The response has been stored.',
                content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\response())],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
            new \mod_mootimeter\route\response\conflict_response(),
            new \mod_mootimeter\route\response\validation_response(),
        ],
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\respond(),
    )]
    public function create_response(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        access::for_tool_page($mootimeterpage, static::TOOL);
        $tool = static::get_tool();

        $stored = $tool->submit_response($mootimeterpage, api_util::get_body($request)['optionIds'] ?? []);

        return api_util::created(
            $request,
            $response,
            $tool->export_response($mootimeterpage, $stored),
            [static::class, 'get_response'],
            ['pageId' => $mootimeterpage->id, 'responseId' => $stored['id']],
        );
    }

    /**
     * Get a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{responseId}',
        method: ['GET'],
        title: 'Get a response',
        description: 'Participants can only get their own response.',
        pathtypes: [new path_parameter(name: 'responseId', type: param::INT, description: 'Id of the response.')],
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\response())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_response(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, static::TOOL);
        $item = static::get_accessible_response($access, $request);

        return new payload_response(
            payload: static::get_tool()->export_response($mootimeterpage, $item),
            request: $request,
            response: $response,
        );
    }

    /**
     * Replace the selected options of a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{responseId}',
        method: ['PUT'],
        title: 'Replace the selected options of a response',
        description: 'Only moderators can change responses, also after the correction is shown.',
        pathtypes: [new path_parameter(name: 'responseId', type: param::INT, description: 'Id of the response.')],
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'optionIds' => new list_of(new field(param::INT), 'Ids of the selected options.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\response())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
            new \mod_mootimeter\route\response\validation_response(),
        ],
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\write(),
    )]
    public function replace_response(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();
        $tool = static::get_tool();

        $item = $tool->replace_response(
            $mootimeterpage,
            static::get_accessible_response($access, $request),
            api_util::get_body($request)['optionIds'] ?? []
        );

        return new payload_response(
            payload: $tool->export_response($mootimeterpage, $item),
            request: $request,
            response: $response,
        );
    }

    /**
     * Delete a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        path: '/{responseId}',
        method: ['DELETE'],
        title: 'Delete a response',
        description: 'Moderators can delete every response, participants their own.',
        pathtypes: [new path_parameter(name: 'responseId', type: param::INT, description: 'Id of the response.')],
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        responses: [
            new \core\router\response\empty_response(),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\write(),
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\respond(),
    )]
    public function delete_response(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, static::TOOL);

        static::get_tool()->delete_response($mootimeterpage, static::get_accessible_response($access, $request));

        return api_util::no_content($response);
    }
}
