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

use core\router\schema\response\payload_response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Payload of a problem details response, sent with the media type of RFC 9457.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class problem_details extends payload_response {
    #[\Override]
    public function get_response(ResponseFactoryInterface $responsefactory): ?ResponseInterface {
        return parent::get_response($responsefactory)?->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
    }
}
