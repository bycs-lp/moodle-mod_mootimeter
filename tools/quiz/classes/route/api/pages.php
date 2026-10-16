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
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_mootimeter\helper;
use mod_mootimeter\local\access;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\field;
use mootimetertool_quiz\quiz;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The quiz specific data and settings of a page and its results.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class pages {
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
     * Get the answer options, the own selection and the settings of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['GET'],
        title: 'Get the options, the own selection and the settings of a page',
        description: 'The correctness of the options is only revealed to moderators or once the correction is shown.',
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\page_data())],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_page(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, static::TOOL);
        return new payload_response(
            payload: static::get_tool()->export_page_data($access),
            request: $request,
            response: $response,
        );
    }

    /**
     * Change the settings of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['PATCH'],
        title: 'Change the settings of a page',
        description: 'Only the given settings are changed. Showing the correction closes the answering.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'maxAnswers' => new field(param::INT, 'Maximum number of options a participant may select, 0 for unlimited.'),
                    'visualization' => new field(
                        param::ALPHA,
                        'Chart type of the results.',
                        enum: ['pillar', 'bar', 'line', 'pie']
                    ),
                    'correctionShown' => new field(param::BOOL, 'Whether the correct options are shown, quiz pages only.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\page_data())],
            ),
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
    public function update_page(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, static::TOOL)->require_moderator();

        static::get_tool()->update_settings($mootimeterpage, api_util::get_body($request));

        return new payload_response(
            payload: static::get_tool()->export_page_data($access),
            request: $request,
            response: $response,
        );
    }

    /**
     * Get the counted answers per option.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/results',
        method: ['GET'],
        title: 'Get the results of a page',
        description: 'Participants only get the counts once the moderator published the results.',
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mootimetertool_quiz\route\schema\results())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_results(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, static::TOOL);
        return new payload_response(
            payload: static::get_tool()->export_results($access),
            request: $request,
            response: $response,
        );
    }
}
