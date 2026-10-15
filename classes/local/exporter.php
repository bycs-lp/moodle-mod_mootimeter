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
 * Exports mootimeter data for the React frontend.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local;

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mod_mootimeter\plugininfo\mootimetertool;
use moodle_url;

/**
 * Exports mootimeter data for the React frontend.
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
     * Export the page list of an instance as visible for the current user.
     *
     * @param \cm_info $cm
     * @param \context_module $context
     * @return array
     */
    public static function export_pages(\cm_info $cm, \context_module $context): array {
        $helper = new helper();
        $pages = [];
        $number = 1;
        foreach ($helper->get_pages($cm->instance, 'sortorder ASC') as $page) {
            $question = format_string((string) helper::get_tool_config($page, 'question'), true, [
                'context' => $context,
                'escape' => false,
            ]);
            $pages[] = [
                'id' => (int) $page->id,
                'tool' => $page->tool,
                'visible' => (int) $page->visible === helper::PAGE_VISIBLE,
                'number' => $number++,
                'title' => shorten_text($question, self::PAGE_TITLE_LENGTH),
                'iconurl' => (new moodle_url('/mod/mootimeter/tools/' . $page->tool . '/pix/' . $page->tool . '-white.svg'))
                    ->out(false),
            ];
        }
        return $pages;
    }

    /**
     * Export the enabled tools a moderator can choose from when adding a page.
     *
     * @return array
     */
    public static function export_tools(): array {
        $tools = [];
        foreach (mootimetertool::get_enabled_plugins() as $tool) {
            $tools[] = [
                'name' => $tool,
                'displayname' => get_string('pluginname', 'mootimetertool_' . $tool),
                'description' => get_string('tool_description_short', 'mootimetertool_' . $tool),
                'iconurl' => (new moodle_url('/mod/mootimeter/tools/' . $tool . '/pix/' . $tool . '.svg'))->out(false),
            ];
        }
        return $tools;
    }

    /**
     * Export the common data of a page.
     *
     * @param object $page
     * @param \cm_info $cm
     * @param \context_module $context
     * @param bool $ismoderator
     * @return array
     */
    public static function export_page(object $page, \cm_info $cm, \context_module $context, bool $ismoderator): array {
        $helper = new helper();
        $toolhelper = helper::get_toolhelper($page->tool);
        $question = (string) helper::get_tool_config($page, 'question');
        $answers = $helper->get_answers($toolhelper->get_answer_table(), $page->id, $toolhelper->get_answer_column());

        return [
            'id' => (int) $page->id,
            'cmid' => (int) $cm->id,
            'tool' => $page->tool,
            'toolname' => get_string('pluginname', 'mootimetertool_' . $page->tool),
            'visible' => (int) $page->visible === helper::PAGE_VISIBLE,
            'question' => $ismoderator ? $question : '',
            'questionhtml' => format_string($question, true, ['context' => $context]),
            'showonteacherpermission' => !empty(helper::get_tool_config($page, 'showonteacherpermission')),
            'resultsvisible' => (bool) $helper->get_teacherpermission_to_view($page),
            'anonymousmode' => !empty(helper::get_tool_config($page, 'anonymousmode')),
            'hasanswers' => !empty($answers),
            'components' => $toolhelper->get_react_components(),
        ];
    }

    /**
     * Export the change timestamps used by the frontend polling.
     *
     * @param \cm_info $cm
     * @param object|null $page
     * @return array
     */
    public static function export_state(\cm_info $cm, ?object $page): array {
        $helper = new helper();
        return [
            'pagelist' => $helper->get_data_changed($cm, 'pagelist'),
            'settings' => $page ? $helper->get_data_changed($page, 'settings') : 0,
            'answers' => $page ? $helper->get_data_changed($page, 'answers') : 0,
        ];
    }

    /**
     * Structure of {@see self::export_pages()} and {@see self::export_tools()}.
     *
     * @return external_single_structure
     */
    public static function pages_structure(): external_single_structure {
        return new external_single_structure([
            'pages' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Page id'),
                'tool' => new external_value(PARAM_PLUGIN, 'Tool of the page'),
                'visible' => new external_value(PARAM_BOOL, 'Whether participants can see the page'),
                'number' => new external_value(PARAM_INT, 'Position of the page in the list'),
                'title' => new external_value(PARAM_TEXT, 'Shortened question of the page'),
                'iconurl' => new external_value(PARAM_URL, 'Icon of the tool'),
            ])),
            'tools' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_PLUGIN, 'Tool name'),
                'displayname' => new external_value(PARAM_TEXT, 'Localised tool name'),
                'description' => new external_value(PARAM_TEXT, 'Short description'),
                'iconurl' => new external_value(PARAM_URL, 'Icon of the tool'),
            ])),
        ]);
    }

    /**
     * Structure of {@see self::export_page()}.
     *
     * @return external_single_structure
     */
    public static function page_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Page id'),
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'tool' => new external_value(PARAM_PLUGIN, 'Tool of the page'),
            'toolname' => new external_value(PARAM_TEXT, 'Localised tool name'),
            'visible' => new external_value(PARAM_BOOL, 'Whether participants can see the page'),
            'question' => new external_value(PARAM_RAW, 'Raw question for editing, empty for participants'),
            'questionhtml' => new external_value(PARAM_RAW, 'Formatted question'),
            'showonteacherpermission' => new external_value(PARAM_BOOL, 'Whether participants may see the results'),
            'resultsvisible' => new external_value(PARAM_BOOL, 'Whether the current user may see the results'),
            'anonymousmode' => new external_value(PARAM_BOOL, 'Whether answers are anonymous'),
            'hasanswers' => new external_value(PARAM_BOOL, 'Whether answers exist'),
            'components' => new external_single_structure([
                'content' => new external_value(PARAM_RAW, 'ESM specifier of the question view'),
                'settings' => new external_value(PARAM_RAW, 'ESM specifier of the settings view'),
                'results' => new external_value(PARAM_RAW, 'ESM specifier of the results view'),
                'overview' => new external_value(PARAM_RAW, 'ESM specifier of the answer overview'),
                'menu' => new external_value(PARAM_RAW, 'ESM specifier of the tool specific content menu, empty for none'),
            ]),
        ]);
    }

    /**
     * Structure of {@see self::export_state()}.
     *
     * @return external_single_structure
     */
    public static function state_structure(): external_single_structure {
        return new external_single_structure([
            'pagelist' => new external_value(PARAM_INT, 'Last change of the page list'),
            'settings' => new external_value(PARAM_INT, 'Last change of the page settings'),
            'answers' => new external_value(PARAM_INT, 'Last change of the page answers'),
        ]);
    }
}
