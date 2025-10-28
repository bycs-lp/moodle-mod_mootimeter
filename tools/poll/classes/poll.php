<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Pluginlib
 *
 * @package     mootimetertool_poll
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_poll;

/**
 * Pluginlib
 *
 * @package     mootimetertool_poll
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll extends \mootimetertool_quiz\quiz {
    /**
     * @var string Answer table
     */
    const ANSWER_TABLE = "mootimetertool_poll_answers";

    /**
     * @var string Name of table column of the answer table where the user id is stored
     */
    const ANSWER_USERID_COLUMN = 'usermodified';

    /**
     * @var string Answer option table name.
     */
    const ANSWER_OPTION_TABLE = "mootimetertool_poll_options";

    /**
     * Get the tools answer column.
     * @return string
     */
    public function get_answer_column() {
        return self::ANSWER_COLUMN;
    }

    /**
     * Get the tools answer table.
     * @return string
     */
    public function get_answer_table() {
        return self::ANSWER_TABLE;
    }

    /**
     * Get the userid column name in the answer table of the tool.
     *
     * @return ?string the column name where the user id is stored in the answer table, null if no user id is stored
     */
    public function get_answer_userid_column(): ?string {
        return self::ANSWER_USERID_COLUMN;
    }

    /**
     * Get the tools answer table.
     * @return string
     */
    public function get_answer_option_table() {
        return self::ANSWER_OPTION_TABLE;
    }

    /**
     * Handels inplace_edit.
     *
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswerselect') {
            return \mootimetertool_quiz\local\inplace_edit_answer::update($itemid, $newvalue);
        }
    }

    /**
     * Get content menu bar params.
     *
     * @param object $page
     * @param array $params Defaultparams
     * @return mixed
     */
    public function get_content_menu_tool_params(object $page, array $params) {
        return $params;
    }
}
