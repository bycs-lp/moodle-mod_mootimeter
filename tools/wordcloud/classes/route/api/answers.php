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
use mod_mootimeter\local\access;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\collection;
use mod_mootimeter\route\schema\field;
use mootimetertool_wordcloud\wordcloud;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The answers of a wordcloud page.
 *
 * @package    mootimetertool_wordcloud
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}/answers',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class answers {
    /**
     * Get an answer the current user may access: moderators all, participants their own.
     *
     * @param access $access
     * @param ServerRequestInterface $request
     * @return \stdClass
     * @throws forbidden_exception
     */
    protected static function get_accessible_answer(access $access, ServerRequestInterface $request): \stdClass {
        global $USER;

        $answer = (new wordcloud())->get_answer($access->get_page(), (int) $request->getAttribute('answerId'));
        if (!$access->ismoderator && (int) $answer->usermodified !== (int) $USER->id) {
            throw new forbidden_exception('nopermissions', 'error', get_capability_string('mod/mootimeter:moderator'));
        }
        return $answer;
    }

    /**
     * List the answers of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['GET'],
        title: 'List the answers of a page',
        description: 'Only moderators can list the answers. In the anonymous mode the names are replaced by a placeholder.',
        queryparams: [
            new \mod_mootimeter\route\parameters\query_page(),
            new \mod_mootimeter\route\parameters\query_pagesize(),
            new \mod_mootimeter\route\parameters\query_sort(['createdat', 'fullname', 'text', 'id']),
            new query_parameter(
                name: 'text',
                type: param::RAW,
                description: 'Only return answers with this text, ignoring the case.',
            ),
        ],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new collection(new \mootimetertool_wordcloud\route\schema\answer()))],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_answers(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        access::for_tool_page($mootimeterpage, 'wordcloud')->require_moderator();
        $wordcloud = new wordcloud();

        $answers = $wordcloud->get_answers(wordcloud::ANSWER_TABLE, $mootimeterpage->id);
        $text = \core_text::strtolower(trim((string) ($request->getQueryParams()['text'] ?? '')));
        if ($text !== '') {
            $answers = array_filter($answers, fn(\stdClass $answer): bool => \core_text::strtolower($answer->answer) === $text);
        }
        $items = array_map(fn(\stdClass $answer): array => $wordcloud->export_answer($mootimeterpage, $answer), $answers);

        return new payload_response(
            payload: api_util::collection(
                $request,
                $items,
                ['createdat' => 'createdAt', 'fullname' => 'fullName', 'text' => 'text', 'id' => 'id']
            ),
            request: $request,
            response: $response,
        );
    }

    /**
     * Submit an answer of the current user.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['POST'],
        title: 'Submit an answer',
        description: 'Every request adds an answer of the current user, limited by the maximum number of answers. A '
            . 'repeated answer is rejected unless the page allows duplicate answers.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'text' => new field(param::RAW, 'The word or statement, HTML tags are removed.', true),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(
                statuscode: 201,
                description: 'The answer has been stored.',
                content: [new json_media_type(schema: new \mootimetertool_wordcloud\route\schema\answer())],
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
    public function create_answer(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        access::for_tool_page($mootimeterpage, 'wordcloud');
        $wordcloud = new wordcloud();

        $answer = $wordcloud->submit_answer($mootimeterpage, (string) (api_util::get_body($request)['text'] ?? ''));

        return api_util::created(
            $request,
            $response,
            $wordcloud->export_answer($mootimeterpage, $answer),
            [self::class, 'get_answer'],
            ['pageId' => $mootimeterpage->id, 'answerId' => $answer->id],
        );
    }

    /**
     * Get an answer.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{answerId}',
        method: ['GET'],
        title: 'Get an answer',
        description: 'Participants can only get their own answers.',
        pathtypes: [new path_parameter(name: 'answerId', type: param::INT, description: 'Id of the answer.')],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new \mootimetertool_wordcloud\route\schema\answer())],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_answer(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        $access = access::for_tool_page($mootimeterpage, 'wordcloud');
        return new payload_response(
            payload: (new wordcloud())->export_answer($mootimeterpage, static::get_accessible_answer($access, $request)),
            request: $request,
            response: $response,
        );
    }

    /**
     * Change the text of an answer.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        path: '/{answerId}',
        method: ['PATCH'],
        title: 'Change an answer',
        description: 'Only moderators can change answers.',
        pathtypes: [new path_parameter(name: 'answerId', type: param::INT, description: 'Id of the answer.')],
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'text' => new field(param::RAW, 'The word or statement, HTML tags are removed.', true),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new \mootimetertool_wordcloud\route\schema\answer())],
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
    public function update_answer(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): payload_response {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, 'wordcloud')->require_moderator();
        $wordcloud = new wordcloud();

        $answer = $wordcloud->update_answer(
            $mootimeterpage,
            static::get_accessible_answer($access, $request),
            (string) (api_util::get_body($request)['text'] ?? '')
        );

        return new payload_response(
            payload: $wordcloud->export_answer($mootimeterpage, $answer),
            request: $request,
            response: $response,
        );
    }

    /**
     * Delete an answer.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        path: '/{answerId}',
        method: ['DELETE'],
        title: 'Delete an answer',
        description: 'Moderators can delete every answer, participants their own.',
        pathtypes: [new path_parameter(name: 'answerId', type: param::INT, description: 'Id of the answer.')],
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
    public function delete_answer(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        $access = access::for_tool_page($mootimeterpage, 'wordcloud');

        (new wordcloud())->delete_answer($mootimeterpage, static::get_accessible_answer($access, $request));

        return api_util::no_content($response);
    }
}
