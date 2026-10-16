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

namespace mod_mootimeter\route;

use core\exception\invalid_parameter_exception;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use core\router\util;
use mod_mootimeter\exception\forbidden_exception;
use mod_mootimeter\route\parameters\header_sesskey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Functions shared by the REST API routes of mod_mootimeter and its tools.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api_util {
    /** @var int Default number of items per page of a collection. */
    const DEFAULT_PAGE_SIZE = 25;

    /** @var int Maximum number of items per page of a collection. */
    const MAX_PAGE_SIZE = 100;

    /**
     * Require the session key for requests authenticated by the session cookie.
     *
     * Token authenticated requests carry the granted scopes and need no session key.
     *
     * @param ServerRequestInterface $request
     * @throws forbidden_exception
     */
    public static function require_sesskey(ServerRequestInterface $request): void {
        if ($request->getAttribute(scopeset::GRANTED_SCOPES) !== null) {
            return;
        }
        if (!util::confirm_sesskey($request, $request->getHeaderLine(header_sesskey::NAME))) {
            throw new forbidden_exception('invalidsesskey', 'error');
        }
    }

    /**
     * Get the parsed JSON body of the request.
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public static function get_body(ServerRequestInterface $request): array {
        return (array) $request->getParsedBody();
    }

    /**
     * Return a 201 response with the location and the representation of the created resource.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $payload Representation of the created resource.
     * @param array $callable Route of the created resource.
     * @param array $params Path parameters of the route.
     * @return payload_response
     */
    public static function created(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $payload,
        array $callable,
        array $params,
    ): payload_response {
        $location = util::get_path_for_callable($callable, array_map('strval', $params))->out(false);
        return new payload_response(
            payload: $payload,
            request: $request,
            response: $response->withStatus(201)->withHeader('Location', $location),
        );
    }

    /**
     * Return a 204 response.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function no_content(ResponseInterface $response): ResponseInterface {
        return $response->withStatus(204);
    }

    /**
     * Sort and paginate the items of a collection according to the query parameters page, pagesize and sort.
     *
     * @param ServerRequestInterface $request
     * @param array $items All items of the collection.
     * @param array $sortfields Sort field of the query => item key.
     * @return array with the keys items and pagination
     * @throws invalid_parameter_exception if the sort order contains unknown fields
     */
    public static function collection(ServerRequestInterface $request, array $items, array $sortfields = []): array {
        $params = $request->getQueryParams();
        $items = self::sort(array_values($items), (string) ($params['sort'] ?? ''), $sortfields);

        $page = max(1, (int) ($params['page'] ?? 1));
        $pagesize = min(self::MAX_PAGE_SIZE, max(1, (int) ($params['pagesize'] ?? self::DEFAULT_PAGE_SIZE)));

        return [
            'items' => array_slice($items, ($page - 1) * $pagesize, $pagesize),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pagesize,
                'totalItems' => count($items),
            ],
        ];
    }

    /**
     * Sort a list of items.
     *
     * @param array $items
     * @param string $sort Comma separated sort fields, descending ones prefixed with a minus.
     * @param array $sortfields Sort field of the query => item key.
     * @return array
     * @throws invalid_parameter_exception if the sort order contains unknown fields
     */
    private static function sort(array $items, string $sort, array $sortfields): array {
        $criteria = [];
        foreach (array_filter(array_map('trim', explode(',', $sort))) as $field) {
            $descending = str_starts_with($field, '-');
            $field = ltrim($field, '-');
            if (!array_key_exists($field, $sortfields)) {
                throw new invalid_parameter_exception('Unknown sort field: ' . $field);
            }
            $criteria[] = [$sortfields[$field], $descending ? -1 : 1];
        }
        if (!$criteria) {
            return $items;
        }

        usort($items, function (array $a, array $b) use ($criteria): int {
            foreach ($criteria as [$key, $direction]) {
                $result = is_string($a[$key])
                    ? strcmp(\core_text::strtolower($a[$key]), \core_text::strtolower($b[$key]))
                    : $a[$key] <=> $b[$key];
                if ($result !== 0) {
                    return $result * $direction;
                }
            }
            return 0;
        });
        return $items;
    }

    /**
     * Format a timestamp as ISO 8601 date and time in the timezone of the current user.
     *
     * @param int $timestamp
     * @return string
     */
    public static function format_datetime(int $timestamp): string {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(\core_date::get_user_timezone_object())
            ->format(DATE_ATOM);
    }
}
