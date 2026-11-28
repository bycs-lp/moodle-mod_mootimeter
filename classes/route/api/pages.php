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

use core\param;
use core\router\route;
use core\router\schema\objects\schema_object;
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_mootimeter\exception\conflict_exception;
use mod_mootimeter\exception\validation_exception;
use mod_mootimeter\helper;
use mod_mootimeter\local\access;
use mod_mootimeter\local\exporter;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\field;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A mootimeter page and its answers.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class pages {
    /**
     * Get a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['GET'],
        title: 'Get a page',
        description: 'Participants can only get visible pages.',
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mod_mootimeter\route\schema\page())]),
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
        return new payload_response(
            payload: exporter::export_page(access::for_page($mootimeterpage)),
            request: $request,
            response: $response,
        );
    }

    /**
     * Change the common properties of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return payload_response
     */
    #[route(
        method: ['PATCH'],
        title: 'Change a page',
        description: 'Only the given properties are changed. The anonymous mode can not be changed once answers exist. '
            . 'Opening or closing the submission stops a running countdown, starting a countdown opens the submission.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'question' => new field(param::RAW, 'The question, HTML tags are removed.'),
                    'visible' => new field(param::BOOL, 'Whether participants can see the page.'),
                    'position' => new field(param::INT, 'Zero based position in the page list of the activity.'),
                    'resultsPublished' => new field(param::BOOL, 'Whether participants may see the results.'),
                    'anonymousMode' => new field(param::BOOL, 'Whether the answers are anonymous.'),
                    'submissionOpen' => new field(param::BOOL, 'Whether participants can submit and change answers.'),
                    'countdownSeconds' => new field(
                        param::INT,
                        'Start a countdown closing the submission after this many seconds, 0 stops the countdown.',
                    ),
                    'countdownExtendSeconds' => new field(param::INT, 'Extend the running countdown by this many seconds.'),
                    'countdownDefaultSeconds' => new field(
                        param::INT,
                        'Countdown started when the first participant shows the page, 0 for none.',
                    ),
                    'autoPublishResults' => new field(
                        param::BOOL,
                        'Whether the results are published once the submission is closed.',
                    ),
                    'participationVisible' => new field(param::BOOL, 'Whether participants see the participation counter.'),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mod_mootimeter\route\schema\page())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
            new \mod_mootimeter\route\response\conflict_response(),
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
        $access = access::for_page($mootimeterpage)->require_moderator();
        $body = api_util::get_body($request);
        $helper = new helper();
        $page = $mootimeterpage;

        if (array_key_exists('anonymousMode', $body)) {
            $anonymous = (bool) $body['anonymousMode'];
            if ($anonymous !== !empty(helper::get_tool_config($page, 'anonymousmode'))) {
                if ($helper->has_answers($page)) {
                    throw new conflict_exception('anonymousmodelocked', 'mod_mootimeter');
                }
                $helper->set_tool_config($page, 'anonymousmode', $anonymous ? '1' : '0');
            }
        }
        if (array_key_exists('question', $body)) {
            $helper->set_tool_config($page, 'question', trim(param::TEXT->clean((string) $body['question'])));
        }
        if (array_key_exists('resultsPublished', $body)) {
            $helper->set_tool_config($page, 'showonteacherpermission', $body['resultsPublished'] ? '1' : '0');
        }
        if (array_key_exists('visible', $body)) {
            $helper->set_page_visibility($page, (bool) $body['visible']);
        }
        if (array_key_exists('position', $body)) {
            $helper->move_page($page, (int) $body['position']);
        }
        if (array_key_exists('autoPublishResults', $body)) {
            $helper->set_tool_config($page, 'autopublishresults', $body['autoPublishResults'] ? '1' : '0');
        }
        if (array_key_exists('participationVisible', $body)) {
            $helper->set_tool_config($page, 'participationvisible', $body['participationVisible'] ? '1' : '0');
        }
        if (array_key_exists('submissionOpen', $body)) {
            $helper->set_submission_open($page, (bool) $body['submissionOpen']);
        }
        if (array_key_exists('countdownSeconds', $body)) {
            $seconds = (int) $body['countdownSeconds'];
            if ($seconds === 0) {
                $helper->cancel_countdown($page);
            } else {
                $helper->start_countdown($page, self::validate_countdown_seconds('countdownSeconds', $seconds));
            }
        }
        if (array_key_exists('countdownDefaultSeconds', $body)) {
            $seconds = (int) $body['countdownDefaultSeconds'];
            if ($seconds !== 0) {
                self::validate_countdown_seconds('countdownDefaultSeconds', $seconds);
            }
            $helper->set_default_countdown($page, $seconds);
        }
        if (array_key_exists('countdownExtendSeconds', $body)) {
            $seconds = (int) $body['countdownExtendSeconds'];
            $helper->extend_countdown($page, self::validate_countdown_seconds('countdownExtendSeconds', $seconds));
        }

        return new payload_response(
            payload: exporter::export_page(access::for_page($helper->get_page($page->id))),
            request: $request,
            response: $response,
        );
    }

    /**
     * Validate the duration of a countdown.
     *
     * @param string $field name of the field in the request body
     * @param int $seconds
     * @return int the seconds
     * @throws validation_exception if the duration is out of range
     */
    protected static function validate_countdown_seconds(string $field, int $seconds): int {
        if ($seconds < helper::COUNTDOWN_MIN || $seconds > helper::COUNTDOWN_MAX) {
            $range = (object) ['min' => helper::COUNTDOWN_MIN, 'max' => helper::COUNTDOWN_MAX];
            throw new validation_exception('invalidcountdown', 'mod_mootimeter', $range, [
                ['field' => $field, 'message' => get_string('invalidcountdown', 'mod_mootimeter', $range)],
            ]);
        }
        return $seconds;
    }

    /**
     * Delete a page with all its answers.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        method: ['DELETE'],
        title: 'Delete a page',
        description: 'The page is deleted with its settings and all answers.',
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
    public function delete_page(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        access::for_page($mootimeterpage)->require_moderator();

        (new helper())->delete_page($mootimeterpage);

        return api_util::no_content($response);
    }

    /**
     * Delete all answers of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \stdClass $mootimeterpage
     * @return ResponseInterface
     */
    #[route(
        path: '/answers',
        method: ['DELETE'],
        title: 'Delete all answers of a page',
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
    public function delete_answers(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \stdClass $mootimeterpage,
    ): ResponseInterface {
        api_util::require_sesskey($request);
        access::for_page($mootimeterpage)->require_moderator();

        (new helper())->delete_all_answers($mootimeterpage);

        return api_util::no_content($response);
    }
}
