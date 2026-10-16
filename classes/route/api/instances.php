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
use core\router\schema\parameters\query_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use core_course\cm_info;
use mod_mootimeter\exception\validation_exception;
use mod_mootimeter\helper;
use mod_mootimeter\local\access;
use mod_mootimeter\local\exporter;
use mod_mootimeter\plugininfo\mootimetertool;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\collection;
use mod_mootimeter\route\schema\field;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Pages and update revisions of a mootimeter activity.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/instances/{cmId}',
    pathtypes: [new \mod_mootimeter\route\parameters\path_mootimeter()],
)]
class instances {
    /**
     * List the pages of an activity. Participants only get the visible pages.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param cm_info $mootimetercm
     * @return payload_response
     */
    #[route(
        path: '/pages',
        method: ['GET'],
        title: 'List the pages of a mootimeter activity',
        description: 'Participants only get the pages that are visible to them.',
        queryparams: [
            new \mod_mootimeter\route\parameters\query_page(),
            new \mod_mootimeter\route\parameters\query_pagesize(),
            new \mod_mootimeter\route\parameters\query_sort(['number', 'tool']),
            new query_parameter(name: 'visible', type: param::BOOL, description: 'Only return visible or hidden pages.'),
        ],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new collection(new \mod_mootimeter\route\schema\page_list_item()))],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_pages(
        ServerRequestInterface $request,
        ResponseInterface $response,
        cm_info $mootimetercm,
    ): payload_response {
        $access = access::for_cm($mootimetercm);

        $pages = exporter::export_pages($access);
        $visible = $request->getQueryParams()['visible'] ?? null;
        if ($visible !== null) {
            $pages = array_filter($pages, fn(array $page): bool => $page['visible'] === (bool) $visible);
        }

        return new payload_response(
            payload: api_util::collection($request, $pages, ['number' => 'number', 'tool' => 'tool']),
            request: $request,
            response: $response,
        );
    }

    /**
     * Create a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param cm_info $mootimetercm
     * @return payload_response
     */
    #[route(
        path: '/pages',
        method: ['POST'],
        title: 'Create a page',
        description: 'Every request creates a new page at the end of the page list. The visibility of the new page '
            . 'follows the site setting.',
        headerparams: [new \mod_mootimeter\route\parameters\header_sesskey()],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(content: [
                    'tool' => new field(param::PLUGIN, 'Short name of an enabled tool.', true),
                ]),
            ),
            required: true,
        ),
        responses: [
            new response(
                statuscode: 201,
                description: 'The page has been created.',
                content: [new json_media_type(schema: new \mod_mootimeter\route\schema\page())],
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
    public function create_page(
        ServerRequestInterface $request,
        ResponseInterface $response,
        cm_info $mootimetercm,
    ): payload_response {
        api_util::require_sesskey($request);
        access::for_cm($mootimetercm)->require_moderator();

        $tool = (string) (api_util::get_body($request)['tool'] ?? '');
        if (!in_array($tool, mootimetertool::get_enabled_plugins(), true)) {
            throw new validation_exception('unknowntool', 'mod_mootimeter', null, [
                ['field' => 'tool', 'message' => get_string('unknowntool', 'mod_mootimeter')],
            ]);
        }

        $page = (new helper())->create_page($mootimetercm, $tool);

        return api_util::created(
            $request,
            $response,
            exporter::export_page(access::for_page($page)),
            [pages::class, 'get_page'],
            ['pageId' => $page->id],
        );
    }

    /**
     * Get the revisions of the page list and of the settings and answers of a page.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param cm_info $mootimetercm
     * @return payload_response
     */
    #[route(
        path: '/updates',
        method: ['GET'],
        title: 'Get the revisions of the data of an activity',
        description: 'Clients poll this resource and reload the data whose revision changed. The revisions are opaque '
            . 'increasing numbers. Hidden pages are ignored for participants.',
        queryparams: [
            new query_parameter(name: 'pageid', type: param::INT, description: 'The page shown by the client.'),
        ],
        responses: [
            new response(statuscode: 200, content: [new json_media_type(schema: new \mod_mootimeter\route\schema\updates())]),
            new \mod_mootimeter\route\response\unauthenticated_response(),
            new \mod_mootimeter\route\response\forbidden_response(),
            new \mod_mootimeter\route\response\not_found_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_updates(
        ServerRequestInterface $request,
        ResponseInterface $response,
        cm_info $mootimetercm,
    ): payload_response {
        global $DB;

        $access = access::for_cm($mootimetercm);
        $pageid = (int) ($request->getQueryParams()['pageid'] ?? 0);
        $page = null;
        if ($pageid) {
            $page = $DB->get_record('mootimeter_pages', ['id' => $pageid, 'instance' => $mootimetercm->instance]) ?: null;
            if ($page && !$access->ismoderator && (int) $page->visible !== helper::PAGE_VISIBLE) {
                $page = null;
            }
        }

        return new payload_response(
            payload: exporter::export_updates($mootimetercm, $page),
            request: $request,
            response: $response,
        );
    }
}
