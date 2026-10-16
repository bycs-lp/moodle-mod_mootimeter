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

namespace mod_mootimeter\output;

use core\output\react_component_renderable;
use core\output\renderable;
use core\output\renderer_base;

/**
 * The mootimeter React application.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class app implements react_component_renderable, renderable {
    /**
     * Constructor.
     *
     * @param int $cmid Course module id.
     * @param int $pageid Selected page, 0 for none.
     * @param string $view One of question, results, overview and addpage.
     * @param bool $fullscreen Whether the fullscreen mode is active.
     * @param bool $ismoderator Whether the current user moderates the activity.
     * @param bool $isediting Whether the editing mode is active.
     * @param int $refreshinterval Interval of the update polling in milliseconds.
     */
    public function __construct(
        /** @var int Course module id. */
        private readonly int $cmid,
        /** @var int Selected page, 0 for none. */
        private readonly int $pageid,
        /** @var string One of question, results, overview and addpage. */
        private readonly string $view,
        /** @var bool Whether the fullscreen mode is active. */
        private readonly bool $fullscreen,
        /** @var bool Whether the current user moderates the activity. */
        private readonly bool $ismoderator,
        /** @var bool Whether the editing mode is active. */
        private readonly bool $isediting,
        /** @var int Interval of the update polling in milliseconds. */
        private readonly int $refreshinterval,
    ) {
    }

    #[\Override]
    public function get_react_component_name(): string {
        return 'mod_mootimeter/app';
    }

    #[\Override]
    public function get_react_component_props(renderer_base $renderer): \stdClass {
        return (object) [
            'cmId' => $this->cmid,
            'pageId' => $this->pageid,
            'view' => $this->view,
            'fullscreen' => $this->fullscreen,
            'isModerator' => $this->ismoderator,
            'isEditing' => $this->isediting,
            'refreshInterval' => $this->refreshinterval,
        ];
    }
}
