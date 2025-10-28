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
 * The mod_mootimeter answer_overview renderer.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * The mod_mootimeter answer_overview renderer.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer_overview implements renderable, templatable {
    /**
     * @var object course module
     */
    protected $cm = null;

    /**
     * @var object
     */
    protected $page = null;

    /**
     * Inits the answer_overview renderer
     * @param object $cm
     * @param object $page
     * @return void
     */
    public function __construct(object $cm, object $page) {
        $this->cm = $cm;
        $this->page = $page;
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {

        // This content should only be viewed with moderator capabilities.
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($this->cm->id))) {
            redirect('view.php?id=' . $this->cm->id . "&pageid=" . $this->page->id);
        }

        $classname = "\mootimetertool_" . $this->page->tool . "\\" . $this->page->tool;

        if (!class_exists($classname)) {
            return [
                'error' => "Class '" . $this->page->tool . "' is missing in tool " . $this->page->tool,
            ];
        }

        $toolhelper = new $classname();

        if (!method_exists($toolhelper, 'get_tool_answer_overview_params')) {
            return [
                'error' => "Method 'get_tool_answer_overview_params' is missing in tool helper class " . $this->page->tool,
            ];
        }

        return $toolhelper->get_tool_answer_overview_params($this->cm, $this->page);
    }
}
