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

/**
 * Shared access checks for the mootimeter web services.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local;

use core_external\external_api;
use mod_mootimeter\helper;
use moodle_exception;

/**
 * Shared access checks for the mod_mootimeter and mootimetertool web services.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_util {
    /**
     * Validate the access to a mootimeter course module.
     *
     * @param int $cmid course module id
     * @return array [cm_info $cm, \context_module $context, bool $ismoderator]
     */
    public static function validate_cm(int $cmid): array {
        [, $cm] = get_course_and_cm_from_cmid($cmid, 'mootimeter');
        $context = \context_module::instance($cm->id);
        external_api::validate_context($context);
        require_capability('mod/mootimeter:view', $context);
        return [$cm, $context, has_capability('mod/mootimeter:moderator', $context)];
    }

    /**
     * Validate the access to a mootimeter page.
     *
     * Hidden pages are only accessible for moderators.
     *
     * @param int $pageid
     * @param bool $requiremoderator whether the moderator capability is required
     * @return array [object $page, cm_info $cm, \context_module $context, bool $ismoderator]
     */
    public static function validate_page(int $pageid, bool $requiremoderator = false): array {
        global $DB;

        $page = $DB->get_record('mootimeter_pages', ['id' => $pageid]);
        if (!$page) {
            throw new moodle_exception('pageaccessexception', 'mod_mootimeter');
        }
        [, $cm] = get_course_and_cm_from_instance($page->instance, 'mootimeter');
        $context = \context_module::instance($cm->id);
        external_api::validate_context($context);
        require_capability('mod/mootimeter:view', $context);
        if ($requiremoderator) {
            require_capability('mod/mootimeter:moderator', $context);
        }
        $ismoderator = has_capability('mod/mootimeter:moderator', $context);

        if (!$ismoderator && (int) $page->visible !== helper::PAGE_VISIBLE) {
            throw new moodle_exception('pagelocked', 'mod_mootimeter');
        }

        return [$page, $cm, $context, $ismoderator];
    }
}
