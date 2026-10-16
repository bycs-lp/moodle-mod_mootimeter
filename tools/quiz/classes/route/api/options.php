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
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
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
 * The answer options of a page.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}/options',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class options {
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
     * List the answer options of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['GET'],
        title: 'List the answer options of a page',
        queryparams: [
            new \mod_mootimeter\route\parameters\query_page(),
            new \mod_mootimeter\route\parameters\query_pagesize(),
            new \mod_mootimeter\route\parameters\query_sort(['id', 'text']),
        ],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new collection(new \mootimetertool_quiz\route\schema\option()))],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_options(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, static::TOOL);
        $tool = static::get_tool();
        $options = array_map(
            fn(\stdClass $option): array => $tool->export_option($mootimeterpage, $option, $access),
            $tool->get_answer_options($mootimeterpage->id)
        );

        return new payload_response(
            payload: api_util::collection($request, $options, ['id' => 'id', 'text' => 'textHtml']),
            request: $request,
            response: $response,
        );
    }

    /**
     * Add an answer option.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['POST'],
        title: 'Add an answer option',
        description: 'Every request adds a new option at the end of the list.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'text' => new field(param::RAW, 'Text of the option, empty by default. HTML tags are removed.'),
                    'isCorrect' => new field(param::BOOL, 'Whether the option is correct, quiz pages only.'),
                ]),
            ),
        ),
        responses: [
            new response(
                statuscode: 201,
                description: 'The option has been added.',
                content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\option())],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\write(),
    )]
    public function create_option(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();
        $body = api_util::get_body($request);

        $option = static::get_tool()->create_option(
            $mootimeterpage,
            (string) ($body['text'] ?? ''),
            !empty($body['isCorrect'])
        );

        return api_util::created(
            $request,
            $response,
            static::get_tool()->export_option($mootimeterpage, $option, $access),
            [static::class, 'get_option'],
            ['pageId' => $mootimeterpage->id, 'optionId' => $option->id],
        );
    }

    /**
     * Replace all answer options of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        method: ['PUT'],
        title: 'Replace the answer options of a page',
        description: 'Items with an id keep and change the existing option, items without an id add an option. Options '
            . 'that are not listed are deleted together with the answers selecting them. At most '
            . quiz::MAX_IMPORT_OPTIONS . ' options can be added at once.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'items' => new list_of(new schema_object(content: [
                        'id' => new field(param::INT, 'Id of an existing option to keep.'),
                        'text' => new field(param::RAW, 'Text of the option, HTML tags are removed.'),
                        'isCorrect' => new field(param::BOOL, 'Whether the option is correct, quiz pages only.'),
                    ]), 'The answer options in their order.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new \core\router\response\empty_response(),
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
    public function replace_options(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();

        static::get_tool()->replace_options($mootimeterpage, api_util::get_body($request)['items'] ?? []);

        return api_util::no_content($response);
    }

    /**
     * Get an answer option.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{optionId}',
        method: ['GET'],
        title: 'Get an answer option',
        pathtypes: [new path_parameter(name: 'optionId', type: param::INT, description: 'Id of the answer option.')],
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\option())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_option(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, static::TOOL);
        $option = static::get_tool()->get_option($mootimeterpage, (int) $request->getAttribute('optionId'));

        return new payload_response(
            payload: static::get_tool()->export_option($mootimeterpage, $option, $access),
            request: $request,
            response: $response,
        );
    }

    /**
     * Change an answer option.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{optionId}',
        method: ['PATCH'],
        title: 'Change an answer option',
        pathtypes: [new path_parameter(name: 'optionId', type: param::INT, description: 'Id of the answer option.')],
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'text' => new field(param::RAW, 'Text of the option, HTML tags are removed.'),
                    'isCorrect' => new field(param::BOOL, 'Whether the option is correct, quiz pages only.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\option())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(
        new \mod_mootimeter\route\scope\mootimeter\read(),
        new \mod_mootimeter\route\scope\mootimeter\write(),
    )]
    public function update_option(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();
        $tool = static::get_tool();
        $body = api_util::get_body($request);

        $option = $tool->update_option(
            $mootimeterpage,
            $tool->get_option($mootimeterpage, (int) $request->getAttribute('optionId')),
            array_key_exists('text', $body) ? (string) $body['text'] : null,
            array_key_exists('isCorrect', $body) ? (bool) $body['isCorrect'] : null,
        );

        return new payload_response(
            payload: $tool->export_option($mootimeterpage, $option, $access),
            request: $request,
            response: $response,
        );
    }

    /**
     * Delete an answer option with the answers selecting it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        path: '/{optionId}',
        method: ['DELETE'],
        title: 'Delete an answer option',
        description: 'The answers selecting the option are deleted too.',
        pathtypes: [new path_parameter(name: 'optionId', type: param::INT, description: 'Id of the answer option.')],
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
    public function delete_option(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();
        $tool = static::get_tool();

        $tool->delete_option($mootimeterpage, $tool->get_option($mootimeterpage, (int) $request->getAttribute('optionId')));

        return api_util::no_content($response);
    }
}
