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

namespace mod_mootimeter\exception;

use core\exception\moodle_exception;
use core\exception\response_aware_exception;

/**
 * Base of the exceptions the REST API of mod_mootimeter and its tools turns into RFC 9457 problem responses.
 *
 * The error code is the stable, machine-readable identifier of the problem and the identifier of the language string
 * describing it. It must consist of concatenated lowercase words.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class api_exception extends moodle_exception implements response_aware_exception {
    /**
     * Constructor.
     *
     * @param string $errorcode Error code and language string identifier.
     * @param string $component Component of the language string.
     * @param mixed $a Parameter of the language string.
     * @param array $details Field level details, list of ['field' => string, 'message' => string].
     */
    public function __construct(
        string $errorcode,
        string $component = 'mod_mootimeter',
        mixed $a = null,
        /** @var array Field level details of the problem. */
        public readonly array $details = [],
    ) {
        parent::__construct($errorcode, $component, '', $a);
    }
}
