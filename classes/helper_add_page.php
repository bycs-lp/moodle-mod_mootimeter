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
 * The mod_mootimeter helper class to add new page.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use dml_exception;

/**
 * The mod_mootimeter helper class to add new page.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_add_page extends \mod_mootimeter\helper {
    /**
     * Get the view_content snippet for new_page.
     *
     * @param object $cm
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_view_content_new_page_params(object $cm): array {

        $mt = new \mod_mootimeter\plugininfo\mootimetertool();
        $enabledtools = $mt->get_enabled_plugins();

        $params = [];

        $params['template'] = 'mod_mootimeter/add_new_page';

        foreach ($enabledtools as $key => $tool) {
            $params['tools'][] = [
                'id' => $tool,
                'tool' => $tool,
                'name' => get_string('pluginname', 'mootimetertool_' . $tool),
                'description' => get_string('tool_description_short', 'mootimetertool_' . $tool),
                'pix' => "tools/" . $tool . "/pix/" . $tool . ".svg",
                'additional_class' => 'mtmt-tool-selector-list',
                'dataset' => 'data-name="' . $tool . '" data-instance="' . $cm->instance . '"',
            ];
        }

        return $params;
    }

    /**
     * Get the view_content snippet for no page selected.
     *
     * @param string $contentstring
     * @return array
     */
    public static function get_view_empty_content_params($contentstring = 'default heading'): array {

        $params = [];

        $params['template'] = 'mod_mootimeter/view_page_empty_content';
        $params['contentstring'] = $contentstring;

        return $params;
    }

    /**
     * Get the content block for the case that there are no pages specified.
     *
     * @param string $contentstring
     * @return string
     */
    public static function get_view_empty_content($contentstring = 'default heading'): string {
        global $OUTPUT;
        $params = self::get_view_empty_content_params($contentstring);
        return $OUTPUT->render_from_template($params['template'], $params);
    }

    /**
     * Get the view_content snippet for new_page.
     *
     * @param object $cm
     * @return string
     */
    public static function get_view_content_new_page(object $cm): string {
        global $OUTPUT;

        $params = self::get_view_content_new_page_params($cm);
        $content = $OUTPUT->render_from_template('mod_mootimeter/add_new_page', $params);

        return $content;
    }
}
