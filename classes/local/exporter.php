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

use core\url;
use core_course\cm_info;
use mod_mootimeter\helper;

/**
 * Representations of mootimeter resources in the REST API, see the schemas in mod_mootimeter\route\schema.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter {
    /** @var int Maximum length of the page title shown in the page list. */
    const PAGE_TITLE_LENGTH = 40;

    /**
     * Export the pages of an activity as visible for the current user.
     *
     * @param access $access
     * @return array[]
     */
    public static function export_pages(access $access): array {
        $pages = [];
        $number = 1;
        foreach ((new helper())->get_pages($access->cm->instance, !$access->ismoderator) as $page) {
            $question = format_string(helper::get_tool_config($page, 'question'), true, [
                'context' => $access->context,
                'escape' => false,
            ]);
            $pages[] = [
                'id' => (int) $page->id,
                'tool' => $page->tool,
                'visible' => (int) $page->visible === helper::PAGE_VISIBLE,
                'number' => $number++,
                'title' => shorten_text($question, self::PAGE_TITLE_LENGTH),
                'iconUrl' => (new url('/mod/mootimeter/tools/' . $page->tool . '/pix/' . $page->tool . '-white.svg'))->out(false),
            ];
        }
        return $pages;
    }

    /**
     * Export an enabled tool.
     *
     * @param string $tool
     * @return array
     */
    public static function export_tool(string $tool): array {
        return [
            'name' => $tool,
            'displayName' => get_string('pluginname', 'mootimetertool_' . $tool),
            'description' => get_string('tool_description_short', 'mootimetertool_' . $tool),
            'iconUrl' => (new url('/mod/mootimeter/tools/' . $tool . '/pix/' . $tool . '.svg'))->out(false),
        ];
    }

    /**
     * Export the common data of a page.
     *
     * @param access $access access to the page
     * @return array
     */
    public static function export_page(access $access): array {
        $helper = new helper();
        $page = $access->get_page();
        $toolhelper = helper::get_toolhelper($page->tool);
        $question = helper::get_tool_config($page, 'question');

        return [
            'id' => (int) $page->id,
            'cmId' => (int) $access->cm->id,
            'tool' => $page->tool,
            'toolName' => get_string('pluginname', 'mootimetertool_' . $page->tool),
            'visible' => (int) $page->visible === helper::PAGE_VISIBLE,
            'question' => $access->ismoderator ? $question : '',
            'questionHtml' => format_string($question, true, ['context' => $access->context]),
            'resultsPublished' => !empty(helper::get_tool_config($page, 'showonteacherpermission')),
            'canViewResults' => $helper->can_view_results($page, $access->ismoderator),
            'anonymousMode' => !empty(helper::get_tool_config($page, 'anonymousmode')),
            'hasAnswers' => $helper->has_answers($page),
            'components' => $toolhelper->get_react_components(),
        ];
    }

    /**
     * Export the revisions of the data, clients reload data whose revision changed.
     *
     * @param cm_info $cm
     * @param \stdClass|null $page the page the client shows
     * @return array
     */
    public static function export_updates(cm_info $cm, ?\stdClass $page): array {
        $helper = new helper();
        return [
            'pageList' => $helper->get_data_changed((int) $cm->instance, helper::CHANGE_PAGELIST),
            'settings' => $page ? $helper->get_data_changed((int) $page->id, helper::CHANGE_SETTINGS) : 0,
            'answers' => $page ? $helper->get_data_changed((int) $page->id, helper::CHANGE_ANSWERS) : 0,
        ];
    }
}
