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

use core\router\route;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_mootimeter\local\access;
use mod_mootimeter\local\exporter;
use mod_mootimeter\plugininfo\mootimetertool;
use mod_mootimeter\route\api_util;
use mod_mootimeter\route\schema\collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The tools that can be chosen for new pages.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tools {
    /**
     * List the enabled tools.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return payload_response
     */
    #[route(
        path: '/tools',
        method: ['GET'],
        title: 'List the enabled tools',
        queryparams: [
            new \mod_mootimeter\route\parameters\query_page(),
            new \mod_mootimeter\route\parameters\query_pagesize(),
            new \mod_mootimeter\route\parameters\query_sort(['name', 'displayname']),
        ],
        responses: [
            new response(
                statuscode: 200,
                content: [new json_media_type(schema: new collection(new \mod_mootimeter\route\schema\tool()))],
            ),
            new \mod_mootimeter\route\response\unauthenticated_response(),
        ],
    )]
    #[scopeset(new \mod_mootimeter\route\scope\mootimeter\read())]
    public function get_tools(ServerRequestInterface $request, ResponseInterface $response): payload_response {
        access::require_user();
        $tools = array_map([exporter::class, 'export_tool'], mootimetertool::get_enabled_plugins());

        return new payload_response(
            payload: api_util::collection($request, $tools, ['name' => 'name', 'displayname' => 'displayName']),
            request: $request,
            response: $response,
        );
    }
}
