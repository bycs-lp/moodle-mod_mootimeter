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

namespace mod_mootimeter\route\parameters;

use core\param;
use core\router\schema\parameters\header_object;
use core\router\schema\referenced_object;

/**
 * The session key, required for state changing requests that are authenticated by the session cookie.
 *
 * Requests authenticated by an OAuth2 access token or a personal access token do not need it.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header_sesskey extends header_object implements referenced_object {
    /** @var string Name of the header. */
    const NAME = 'X-Moodle-Sesskey';

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            name: self::NAME,
            type: param::ALPHANUM,
            description: 'Session key of the current session, required when the request is authenticated by the '
                . 'session cookie and changes data.',
            required: false,
        );
    }
}
