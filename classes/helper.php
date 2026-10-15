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
 * The mod_mootimeter helper class.
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
use moodle_exception;
use required_capability_exception;

/**
 * The mod_mootimeter helper class.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** @var int Webservice returning error code - OK */
    const ERRORCODE_OK = 200;
    /** @var int Webservice returning error code - Empty Answer */
    const ERRORCODE_EMPTY_ANSWER = 1000;
    /** @var int Webservice returning error code - Too Many Answers */
    const ERRORCODE_TO_MANY_ANSWERS = 1001;
    /** @var int Webservice returning error code - Duplicate Answers */
    const ERRORCODE_DUPLICATE_ANSWER = 1002;
    /** @var int Webservice returning error code - Answering is closed */
    const ERRORCODE_ANSWERING_CLOSED = 1003;

    /** @var int Page is visible */
    const PAGE_VISIBLE = 1;
    /** @var int Page is unvisible */
    const PAGE_UNVISIBLE = 0;

    /**
     * Get all tools of instance.#
     *
     * @param int|object $instanceorid
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_all_tools_of_instance(int|object $instanceorid) {

        if (is_object($instanceorid)) {
            $instanceid = $instanceorid->id;
        } else {
            $instanceid = $instanceorid;
        }

        $pages = $this->get_pages($instanceid);

        $toolpages = [];
        foreach ($pages as $page) {
            $toolpages[$page->tool][] = $page;
        }
        return $toolpages;
    }

    /**
     * Get a tools answer column.
     * @param object|int $pageorid
     * @return string
     */
    public function get_tool_answer_column(object|int $pageorid): string {

        $page = $pageorid;
        if (!is_object($page)) {
            $page = $this->get_page($page);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'get_answer_column' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_answer_column();
    }

    /**
     * Get a tools answer table.
     * @param object|int $pageorid
     * @return string
     */
    public function get_tool_answer_table(object|int $pageorid): string {

        $page = $pageorid;
        if (!is_object($page)) {
            $page = $this->get_page($page);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'get_answer_table' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_answer_table();
    }

    /**
     * Insert or update a page record.
     *
     * @param object $record
     * @return int pageid
     */
    public function store_page(object $record): int|string {
        global $DB;

        $instance = $record->instance;
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        if (!empty($record->id)) {
            $origrecord = $DB->get_record('mootimeter_pages', ['id' => $record->id]);
            $origrecord->title = $record->title;
            $origrecord->tool = $record->tool;
            $origrecord->timemodified = time();
            $origrecord->visible = $record->visible;
            $origrecord->sortorder = $record->sortorder;
            $DB->update_record('mootimeter_pages', $origrecord);
            $this->notify_data_changed($cm, 'pagelist');
            return $origrecord->id;
        }
        $record->timecreated = time();
        $record->sortorder = $this->get_page_next_sortorder($instance);
        $defaultvisibility = get_config('mod_mootimeter', 'default_new_page_visibility');
        $record->visible = (empty($defaultvisibility)) ? 0 : $defaultvisibility;
        $pageid = $DB->insert_record('mootimeter_pages', $record, true);

        // Hook to do further actions depending on mtmt tool.
        $classname = "\mootimetertool_" . $record->tool . "\\" . $record->tool;
        if (!class_exists($classname)) {
            return "Class '" . $record->tool . "' is missing in tool " . $record->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'hook_after_new_page_created' is missing in tool helper class " . $record->tool;
        }

        $record->id = $pageid;
        $toolhelper->hook_after_new_page_created($record);

        $this->notify_data_changed($cm, 'pagelist');

        return $pageid;
    }

    /**
     * Store one single page detail.
     *
     * @param object|int $pageorid
     * @param string $column
     * @param string $value
     * @return void
     * @throws dml_exception
     */
    public function store_page_detail(object|int $pageorid, string $column, string $value) {

        if (is_object($pageorid)) {
            $pageid = $pageorid->id;
        } else {
            $pageid = $pageorid;
        }

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        switch ($column) {
            case 'sortorder':
                $pagelisthelper = new \mod_mootimeter\local\pagelist();
                $pagelisthelper->permutate_sortorder($pageid, $value);
                break;
            default:
                $dataobject = $this->get_page($pageid);
                $dataobject->{$column} = $value;
                $this->store_page($dataobject);
                break;
        }
    }

    /**
     * Get all pages of an instance.
     *
     * @param int $instanceid
     * @param string $sort
     * @return mixed
     */
    public function get_pages(int $instanceid, string $sort = 'id ASC') {
        global $DB;

        $params = ['instance' => $instanceid];

        if (
            empty(has_capability(
                'mod/mootimeter:moderator',
                \context_module::instance(self::get_cm_by_instance($instanceid)->id)
            ))
        ) {
            $params['visible'] = self::PAGE_VISIBLE;
        }

        return $DB->get_records('mootimeter_pages', $params, $sort);
    }

    /**
     * Get mootimeter page object.
     *
     * @param int $pageid
     * @param bool $checkvisible - if true, only visible pages are returned,
     *                             when the user has no moderator capability
     * @return mixed
     * @throws dml_exception
     */
    public function get_page(int $pageid, bool $checkvisible = true): bool|object {
        global $DB;

        if (empty($pageid)) {
            return false;
        }

        $params = ['id' => $pageid];

        // If the user has no moderator capability, only visible pages are returned.
        // We need to skip this check, if the methode is called during module deletion.
        if (
            $checkvisible &&
            empty(has_capability(
                'mod/mootimeter:moderator',
                \context_module::instance(self::get_cm_by_pageid($pageid)->id)
            ))
        ) {
            $params['visible'] = self::PAGE_VISIBLE;
        }

        return $DB->get_record('mootimeter_pages', $params);
    }

    /**
     * Get instance by mootimeter pageid
     * @param int $pageid
     * @return int
     */
    public static function get_instance_by_pageid(int $pageid): int {
        global $DB;
        return $DB->get_field('mootimeter_pages', 'instance', ['id' => $pageid], IGNORE_MISSING);
    }


    /**
     * Get the mootimeterinstance
     *
     * @param int $instanceid
     * @return object
     * @throws dml_exception
     */
    public static function get_mootimeter_instance(int $instanceid): object {
        global $DB;
        return $DB->get_record('mootimeter', ['id' => $instanceid]);
    }

    /**
     * Validate that the pageid is pertinent to this mootimeter instance.
     * @param int $pageid the pageid we want to validate
     * @param array $myinstancepages list of the page records of a mootimeter instance
     * @return bool true if the page belongs to the instance
     */
    public static function validate_page_belongs_to_instance($pageid, $myinstancepages): bool {
        return in_array($pageid, array_column($myinstancepages, "id")) === true;
    }

    /**
     * Get the course module object by mootimeter instance.
     * @param int $instance
     * @return object
     */
    public static function get_cm_by_instance(int $instance): object {
        global $DB;
        $module = $DB->get_record('modules', ['name' => 'mootimeter']);
        return $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $instance]);
    }

    /**
     * Get the course module by pageid.
     *
     * @param int $pageid
     * @return object
     * @throws dml_exception
     */
    public static function get_cm_by_pageid(int $pageid): object {
        $instance = self::get_instance_by_pageid($pageid);
        return self::get_cm_by_instance($instance);
    }

    /**
     * Get next page sortorder
     * @param int $instanceid
     * @return int
     * @throws dml_exception
     */
    public function get_page_next_sortorder(int $instanceid): int {
        global $DB;

        $records = $DB->get_records('mootimeter_pages', ['instance' => $instanceid], 'sortorder DESC', '*', 0, 1);

        $lastrecord = array_shift($records);

        $sortorder = 0;
        if (!empty($lastrecord->sortorder)) {
            $sortorder = $lastrecord->sortorder;
        }

        return $sortorder + 1;
    }

    /**
     * Get the tool helper instance of a tool.
     *
     * @param string $tool short name of the mootimetertool subplugin
     * @return toolhelper
     * @throws coding_exception if the tool is not installed
     */
    public static function get_toolhelper(string $tool): toolhelper {
        $tools = \core_component::get_plugin_list('mootimetertool');
        $classname = '\\mootimetertool_' . $tool . '\\' . $tool;
        if (!array_key_exists($tool, $tools) || !class_exists($classname)) {
            throw new coding_exception('Unknown mootimeter tool: ' . $tool);
        }
        return new $classname();
    }

    /**
     * Set a single config value.
     *
     * @param object|int $pageorid
     * @param string $name
     * @param string $value
     * @return void
     * @throws dml_exception
     */
    public function set_tool_config(object|int $pageorid, string $name, string $value): void {
        global $DB;

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        // First do some capability check.
        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $conditions = [
            'tool' => $page->tool,
            'pageid' => $page->id,
            'name' => $name,
        ];

        // This is a hook to make some context specific changes or validation of specific dependencies.
        $value = $this->hook_process_value($page, $name, $value);

        if (empty($record = $DB->get_record('mootimeter_tool_settings', $conditions))) {
            $dataobject = (object)$conditions;
            $dataobject->value = $value;
            $dataobject->timemodified = time();
            $DB->insert_record('mootimeter_tool_settings', $dataobject);
        } else {
            $record->value = $value;
            $record->timemodified = time();
            $DB->update_record('mootimeter_tool_settings', $record);
        }

        // Lastupdated timestamp hast to be reset in cache.
        $this->clear_caches($page->id);
        $this->notify_data_changed($page, 'settings');
        $this->notify_data_changed($cm, 'pagelist');
        $this->notify_data_changed($page, 'answers');
    }

    /**
     * Get the tools config.
     *
     * @param mixed $pageorid
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public static function get_tool_config(mixed $pageorid, string $name = '', string $default = '') {
        global $DB;

        $pageid = $pageorid;
        if (is_object($pageorid)) {
            $pageid = $pageorid->id;
        }

        $conditions = ['pageid' => $pageid];

        if (!empty($name)) {
            $conditions['name'] = $name;
            $field = $DB->get_field('mootimeter_tool_settings', 'value', $conditions);
            if (is_null($field) || $field === false) {
                if (!empty($default)) {
                    $helper = new \mod_mootimeter\helper();
                    $helper->set_tool_config($pageid, $name, $default);
                }
                return $default;
            }
            return $field;
        }

        return (object) $DB->get_records_menu('mootimeter_tool_settings', $conditions, '', 'name, value');
    }

    /**
     * Hook handle to validate the settings value with tool specific needs.
     *
     * TODO: Make an abstract class and an implementation in the tool, to define tool specific / additional validations.
     *
     * @param object $page
     * @param string $name
     * @param string|int $value
     * @return string|int
     */
    public function hook_process_value(object $page, string $name, string|int $value): string|int {

        switch ($name) {
            case 'anonymousmode':
                // If there is already any answer stored. Then the setting could not be changed.
                if (
                    !empty($this->get_answers(
                        $this->get_tool_answer_table($page),
                        $page->id,
                        $this->get_tool_answer_column($page)
                    ))
                ) {
                    $value = $this->get_tool_config($page, 'anonymousmode');
                }
                return $value;
                break;
        }

        return $value;
    }

    /**
     * Delete the page for the current subplugin.
     * @param int|object $pageorid
     * @return bool
     */
    public function delete_page($pageorid) {
        global $DB;

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid, false);
        } else {
            $page = $pageorid;
        }

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            throw new \coding_exception("Class '" . $page->tool . "' is missing in tool " . $page->tool);
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'delete_page_tool')) {
            throw new \coding_exception("Method 'delete_page_tool' is missing in tool helper class " . $page->tool);
        }
        // Call tool specific deletion processes.
        $toolhelper->delete_page_tool($page);

        // Call mootimeter-core deletion processes.
        $DB->delete_records('mootimeter_pages', ['id' => $page->id]);
        $DB->delete_records('mootimeter_tool_settings', ['pageid' => $page->id]);

        $this->notify_data_changed($cm, 'pagelist');
        return true;
    }

    /**
     * Toggle the show results teacher permission state.
     *
     * @param object $page
     * @return int
     */
    public function toggle_teacherpermission_state(object $page): int {
        return $this->toggle_state($page, 'showonteacherpermission');
    }

    /**
     * Toggle a state.
     *
     * @param object $page
     * @param string $statename
     * @return int
     * @throws dml_exception
     */
    public function toggle_state(object $page, string $statename): int {

        $cm = self::get_cm_by_pageid($page->id);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        switch ($statename) {
            case 'page_visibility':
                $page->visible = (empty($page->visible)) ? self::PAGE_VISIBLE : self::PAGE_UNVISIBLE;
                $this->store_page($page);
                return $page->visible;
                break;
            default:
                $togglestate = self::get_tool_config($page->id, $statename);

                $helper = new \mod_mootimeter\helper();
                if (empty($togglestate)) {
                    // The config is not set yet. Set the value to 1.
                    $helper->set_tool_config($page, $statename, 1);
                    return 1;
                }

                // The config was already set. Toggle it.
                $helper->set_tool_config($page, $statename, 0);
                return 0;
                break;
        }
    }

    /**
     * Store the answer.
     *
     * This methode handels all answer storage processes. $updateexisting = true only makes sense with $record is an object.
     * In combination with $allowmultipleanswers = true means that existing answers will be deleted. This is because we do not know
     * how many answers has to be updated. It can be more or less than the original answer. Therefore it is more easy to delete
     * all ansers and store the new ones.
     *
     * @param string $table
     * @param object|array $record
     * @param bool $updateexisting
     * @param string $answercolumn
     * @param bool $allowmultipleanswers
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws cache_exception
     */
    public function store_answer(
        string $table,
        object|array $record,
        bool $updateexisting = false,
        string $answercolumn = 'answer',
        bool $allowmultipleanswers = false
    ): array {
        global $DB, $USER;

        // Temporarily get one record to retrieve user and page information.
        $recordtemp = $record;
        if (is_array($record)) {
            $recordtemp = $record[0];
        }

        if (empty($recordtemp->pageid)) {
            throw new \moodle_exception('pageidmissing', 'error');
        }

        $answerids = [];

        if ($allowmultipleanswers) {
            if ($updateexisting) {
                $params = ['pageid' => $recordtemp->pageid, 'usermodified' => $USER->id];
                $DB->delete_records($table, $params);
            }

            foreach ($record as $dataobject) {
                // Add usermodified to dataobject.
                $dataobject->usermodified = $USER->id;

                // Set the default value for timemodified to 0. This is necessary to make usage of GREATEST SQL method possible.
                $dataobject->timemodified = 0;

                $answerids[] = $DB->insert_record($table, $dataobject);
                $pageid = $dataobject->pageid;
            }
        }

        if (!$allowmultipleanswers) {
            // If it's an array with only one record in it. We can update the existing answer.
            if (is_array($record)) {
                $dataobject = array_pop($record);
            } else {
                $dataobject = $record;
            }

            // Add usermodified to dataobject.
            $dataobject->usermodified = $USER->id;

            // Set the default value for timemodified to 0. This is necessary to make usage of GREATEST SQL method possible.
            $dataobject->timemodified = 0;

            // Store the answer to db or update it.
            if ($updateexisting) {
                $params = ['pageid' => $dataobject->pageid, 'usermodified' => $dataobject->usermodified];

                // This is necessary, because if one user changes the setting, there could be more than one answer already stored.
                // In this cases we want to delete all previous answers and start from scratch.
                if ($DB->count_records($table, $params) > 1) {
                    $DB->delete_records($table, $params);
                }

                $origrecord = $DB->get_record($table, $params);
            }

            if (!empty($origrecord)) {
                $origrecord->{$answercolumn} = $dataobject->{$answercolumn};
                $origrecord->timemodified = time();

                $DB->update_record($table, $origrecord);
                $answerids[] = $origrecord->id;
            }

            if (empty($origrecord)) {
                $answerids[] = $DB->insert_record($table, $dataobject);
            }
            $pageid = $dataobject->pageid;
        }

        // Recreate the cache.
        $this->clear_caches($pageid);
        $this->get_answers($table, $pageid, $answercolumn);
        $this->get_answers_grouped($table, ['pageid' => $pageid], $answercolumn);
        $this->notify_data_changed($this->get_page($pageid), 'answers');
        return $answerids;
    }

    /**
     * Delete all answers of a page.
     * @param string $table
     * @param int $pageid
     * @return bool
     * @throws dml_exception
     */
    public function delete_all_answers(string $table, int $pageid): bool {
        global $DB;

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $params = ['pageid' => $pageid];
        $return = $DB->delete_records($table, $params);
        $this->clear_caches($pageid);
        $this->notify_data_changed($this->get_page($pageid), 'answers');
        return $return;
    }

    /**
     * Delete a single answer of a page.
     *
     * @param string $table
     * @param int $pageid
     * @param int $answerid
     * @return bool
     * @throws dml_exception
     * @throws coding_exception
     * @throws required_capability_exception
     */
    public function delete_single_answer(string $table, int $pageid, int $answerid): bool {
        global $DB, $USER;

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        $useranswers = $this->get_user_answers(
            $this->get_tool_answer_table($pageid),
            $pageid,
            $this->get_tool_answer_column($pageid),
            $USER->id
        );
        $isownanswer = in_array($answerid, array_map(fn ($useranswers) => $useranswers->id, $useranswers));

        if (!has_capability('mod/mootimeter:moderator', $context) && !$isownanswer) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $params = ['pageid' => $pageid, 'id' => $answerid];
        $return = $DB->delete_records($table, $params);
        $this->clear_caches($pageid);
        $this->notify_data_changed($this->get_page($pageid), 'answers');
        return $return;
    }

    /**
     * Delete all answers of a user of a page.
     *
     * @param string $table the table name to delete the answers from
     * @param int $pageid the id of the page
     * @param int $userid the id of the user whose answers should be deleted
     * @return bool
     */
    public function delete_answers_of_user(string $table, int $pageid, int $userid): bool {
        global $DB, $USER;

        if ($userid <= 0) {
            throw new moodle_exception('Invalid user id: ' . $userid);
        }

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context) && intval($USER->id) !== $userid) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $helper = new \mod_mootimeter\helper();
        $page = $helper->get_page($pageid);
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        /** @var \mod_mootimeter\toolhelper $toolhelper */
        $toolhelper = new $classname();

        if (is_null($toolhelper->get_answer_userid_column())) {
            throw new coding_exception('Tool ' . $page->tool . ' has no user id stored, you cannot use this function!');
        }
        $params = ['pageid' => $pageid, $toolhelper->get_answer_userid_column() => $userid];
        $return = $DB->delete_records($table, $params);
        $this->clear_caches($pageid);
        $this->notify_data_changed($page, 'answers');
        return $return;
    }

    /**
     * Get all page answers of a specific user. Using get_answers method to use the cache.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @param int $userid
     * @return array
     * @throws dml_exception
     * @throws coding_exception in case that the current tool does not have user information in the answers table
     */
    public function get_user_answers(string $table, int $pageid, string $answercolumn = 'answer', int $userid = 0): array {
        $page = $this->get_page($pageid);
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        /** @var \mod_mootimeter\toolhelper $toolhelper */
        $toolhelper = new $classname();
        $useridcolumn = $toolhelper->get_answer_userid_column();

        if (is_null($useridcolumn)) {
            throw new coding_exception('Tool ' . $page->tool . ' has no user id stored, you cannot use this function!');
        }

        $pageanswers = $this->get_answers($table, $pageid, $answercolumn);

        $useranswers = [];

        foreach ($pageanswers as $pageanswer) {
            if ($userid > 0 && $pageanswer->{$useridcolumn} == $userid) {
                $useranswers[$pageanswer->{$answercolumn}] = $pageanswer;
            }
        }

        return $useranswers;
    }

    /**
     * Get all answers of a page.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @return array
     * @throws dml_exception
     */
    public function get_answers(string $table, int $pageid, string $answercolumn = 'answer') {
        global $DB;

        $cache = \cache::make('mod_mootimeter', 'answers');
        $cachekey = 'answers_' . $pageid;
        $records = json_decode($cache->get($cachekey));

        if (empty($records)) {
            $records = $DB->get_records($table, ['pageid' => $pageid]);
            $cache->set($cachekey, json_encode($records));
        }

        // Ensure we return an array, because json_decode by default creates a stdClass object.
        return (array) $records;
    }

    /**
     * Get all grouped and counted answers of a page.
     * @param string $table
     * @param array $params
     * @param string $answercolumn
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_answers_grouped(string $table, array $params, string $answercolumn = 'answer'): array {
        global $DB;

        $cache = \cache::make('mod_mootimeter', 'answers');
        $cachekey = 'cnt_' . $params['pageid'];
        $records = json_decode($cache->get($cachekey), true);

        if (empty($records)) {
            $sql = "SELECT $answercolumn, count(*) as cnt FROM {" . $table . "} WHERE pageid = :pageid GROUP BY $answercolumn";
            $records = $DB->get_records_sql($sql, $params);
            $cache->set($cachekey, json_encode($records));
        }

        return $records;
    }

    /**
     * Notify, that data changed. This sets a new timestamp to the cache.
     * Clients then knew, there is someting new, and they have to update this.
     *
     * @param object $obj  This is normally $page object or in case of the pagelist $cm object
     * @param string $identifier
     * @return void
     */
    public function notify_data_changed(object $obj, string $identifier = ''): void {

        $cache = \cache::make('mod_mootimeter', 'lastupdated');
        $cachekey = 'lastupdate_' . $identifier . '_' . $obj->id;
        // Milliseconds, so that clients notice several changes within the same second.
        $cache->set($cachekey, (int) round(microtime(true) * 1000));
    }

    /**
     * Get the recent data changed timestamp
     *
     * @param object $obj  This is normally $page object or in case of the pagelist $cm object
     * @param string $identifier
     * @return int
     */
    public function get_data_changed(object $obj, string $identifier = ''): int {
        $cache = \cache::make('mod_mootimeter', 'lastupdated');
        $cachekey = 'lastupdate_' . $identifier . '_' . $obj->id;
        if (empty($cachevalue = $cache->get($cachekey))) {
            $this->notify_data_changed($obj, $identifier);
            $cachevalue = $cache->get($cachekey);
        }
        return (int) $cachevalue;
    }

    /**
     * Get the teacherpermission to view state.
     *
     * @param object|bool $page
     * @return int
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_teacherpermission_to_view(object|bool $page): int {

        // On empty page, there are no permissions needed. This is normally the case, if a new page is added.
        if (empty($page)) {
            return 0;
        }

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);

        if (
            self::get_tool_config($page, 'showonteacherpermission')
            || has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))
        ) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Clear all caches.
     *
     * @param int $pageid
     * @return void
     * @throws coding_exception
     * @throws cache_exception
     */
    public function clear_caches(int $pageid): void {
        $cache = \cache::make('mod_mootimeter', 'answers');
        $cache->delete('answers_' . $pageid);
        $cache->delete('cnt_' . $pageid);
    }

    /**
     * Get User by id.
     * @param int $userid
     * @return object
     */
    public function get_user_by_id($userid) {
        global $DB;
        return $DB->get_record('user', ['id' => $userid]);
    }
}
