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

namespace mod_mootimeter\local;

use core\context\module as context_module;
use core\exception\moodle_exception;
use core_course\cm_info;
use mod_mootimeter\exception\forbidden_exception;
use mod_mootimeter\exception\not_found_exception;
use mod_mootimeter\exception\unauthenticated_exception;
use mod_mootimeter\helper;

/**
 * Validated access of the current user to a mootimeter activity and optionally to one of its pages.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class access {
    /**
     * Constructor.
     *
     * @param cm_info $cm
     * @param context_module $context
     * @param bool $ismoderator
     * @param \stdClass|null $page
     */
    private function __construct(
        /** @var cm_info The course module of the activity. */
        public readonly cm_info $cm,
        /** @var context_module The context of the activity. */
        public readonly context_module $context,
        /** @var bool Whether the current user moderates the activity. */
        public readonly bool $ismoderator,
        /** @var \stdClass|null The page record, if the access is about a page. */
        private readonly ?\stdClass $page = null,
    ) {
    }

    /**
     * Require an authenticated user who is not the guest.
     *
     * @throws unauthenticated_exception
     */
    public static function require_user(): void {
        if (!isloggedin() || isguestuser()) {
            throw new unauthenticated_exception('requireloginerror', 'error');
        }
    }

    /**
     * Validate that the current user may view the activity.
     *
     * @param cm_info $cm
     * @return self
     * @throws unauthenticated_exception|forbidden_exception
     */
    public static function for_cm(cm_info $cm): self {
        try {
            require_login($cm->get_course(), false, $cm, false, true);
        } catch (moodle_exception) {
            if (!isloggedin()) {
                throw new unauthenticated_exception('requireloginerror', 'error');
            }
            throw new forbidden_exception('requireloginerror', 'error');
        }

        $context = context_module::instance($cm->id);
        if (!has_capability('mod/mootimeter:view', $context)) {
            throw new forbidden_exception('nopermissions', 'error', get_capability_string('mod/mootimeter:view'));
        }
        return new self($cm, $context, has_capability('mod/mootimeter:moderator', $context));
    }

    /**
     * Validate that the current user may view the page.
     *
     * Participants can not access hidden pages.
     *
     * @param \stdClass $page
     * @return self
     * @throws unauthenticated_exception|forbidden_exception|not_found_exception
     */
    public static function for_page(\stdClass $page): self {
        try {
            [, $cm] = get_course_and_cm_from_instance($page->instance, 'mootimeter');
        } catch (moodle_exception) {
            throw new not_found_exception('pagenotfound', 'mod_mootimeter', $page->id);
        }
        $access = self::for_cm($cm);
        if (!$access->ismoderator && (int) $page->visible !== helper::PAGE_VISIBLE) {
            throw new forbidden_exception('pagelocked', 'mod_mootimeter');
        }
        return new self($access->cm, $access->context, $access->ismoderator, $page);
    }

    /**
     * Validate that the current user may view the page of the given tool.
     *
     * @param \stdClass $page
     * @param string $tool
     * @return self
     * @throws not_found_exception if the page belongs to another tool
     */
    public static function for_tool_page(\stdClass $page, string $tool): self {
        if ($page->tool !== $tool) {
            throw new not_found_exception('pagenotfound', 'mod_mootimeter', $page->id);
        }
        return self::for_page($page);
    }

    /**
     * Require that the current user moderates the activity.
     *
     * @return self
     * @throws forbidden_exception
     */
    public function require_moderator(): self {
        if (!$this->ismoderator) {
            throw new forbidden_exception('nopermissions', 'error', get_capability_string('mod/mootimeter:moderator'));
        }
        return $this;
    }

    /**
     * Get the page.
     *
     * @return \stdClass
     */
    public function get_page(): \stdClass {
        if ($this->page === null) {
            throw new \core\exception\coding_exception('The access is not about a page.');
        }
        return $this->page;
    }
}
