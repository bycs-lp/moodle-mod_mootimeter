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

namespace mod_mootimeter;

use core\clock;
use core\di;
use core\exception\coding_exception;
use core\plugin_manager;
use core_cache\cache;
use core_course\cm_info;

/**
 * Pages, settings and answers of mootimeter activities.
 *
 * The methods do not check any capabilities, callers are responsible for the access control.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** @var int Page is visible */
    const PAGE_VISIBLE = 1;
    /** @var int Page is unvisible */
    const PAGE_UNVISIBLE = 0;

    /** @var string Change identifier of the page list of an activity. */
    const CHANGE_PAGELIST = 'pagelist';
    /** @var string Change identifier of the settings of a page. */
    const CHANGE_SETTINGS = 'settings';
    /** @var string Change identifier of the answers of a page. */
    const CHANGE_ANSWERS = 'answers';

    /**
     * Get the current time.
     *
     * @return int
     */
    protected static function now(): int {
        return di::get(clock::class)->time();
    }

    /**
     * Get the pages of an activity.
     *
     * @param int $instanceid
     * @param bool $onlyvisible Whether only the pages visible to participants are returned.
     * @return \stdClass[] ordered by the sort order
     */
    public function get_pages(int $instanceid, bool $onlyvisible = false): array {
        global $DB;

        $params = ['instance' => $instanceid];
        if ($onlyvisible) {
            $params['visible'] = self::PAGE_VISIBLE;
        }
        return $DB->get_records('mootimeter_pages', $params, 'sortorder ASC, id ASC');
    }

    /**
     * Get a page.
     *
     * @param int $pageid
     * @return \stdClass|null
     */
    public function get_page(int $pageid): ?\stdClass {
        global $DB;
        return $DB->get_record('mootimeter_pages', ['id' => $pageid]) ?: null;
    }

    /**
     * Get the course module of an activity.
     *
     * @param int $instanceid
     * @return cm_info
     */
    public static function get_cm_by_instance(int $instanceid): cm_info {
        [, $cm] = get_course_and_cm_from_instance($instanceid, 'mootimeter');
        return $cm;
    }

    /**
     * Get the course module of the activity a page belongs to.
     *
     * @param \stdClass $page
     * @return cm_info
     */
    public static function get_cm_by_page(\stdClass $page): cm_info {
        return self::get_cm_by_instance((int) $page->instance);
    }

    /**
     * Get the tool helper of a tool.
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
     * Get the names of the installed tools.
     *
     * @return string[]
     */
    public static function get_installed_tools(): array {
        return array_keys(plugin_manager::instance()->get_installed_plugins('mootimetertool'));
    }

    /**
     * Create a page.
     *
     * @param cm_info $cm
     * @param string $tool
     * @return \stdClass the new page
     */
    public function create_page(cm_info $cm, string $tool): \stdClass {
        global $DB;

        $toolhelper = self::get_toolhelper($tool);
        $page = (object) [
            'instance' => $cm->instance,
            'tool' => $tool,
            'title' => '',
            'visible' => get_config('mod_mootimeter', 'default_new_page_visibility') ? self::PAGE_VISIBLE : self::PAGE_UNVISIBLE,
            'sortorder' => count($this->get_pages($cm->instance)),
            'timecreated' => self::now(),
            'timemodified' => self::now(),
        ];
        $page->id = $DB->insert_record('mootimeter_pages', $page);

        $toolhelper->hook_after_new_page_created($page);
        $this->notify_data_changed((int) $cm->instance, self::CHANGE_PAGELIST);

        return $page;
    }

    /**
     * Show or hide a page for participants.
     *
     * @param \stdClass $page
     * @param bool $visible
     */
    public function set_page_visibility(\stdClass $page, bool $visible): void {
        global $DB;

        $page->visible = $visible ? self::PAGE_VISIBLE : self::PAGE_UNVISIBLE;
        $page->timemodified = self::now();
        $DB->update_record('mootimeter_pages', $page);
        $this->notify_data_changed((int) $page->instance, self::CHANGE_PAGELIST);
        $this->notify_data_changed((int) $page->id, self::CHANGE_SETTINGS);
    }

    /**
     * Move a page to another position.
     *
     * @param \stdClass $page
     * @param int $position zero based position in the list of all pages of the activity
     */
    public function move_page(\stdClass $page, int $position): void {
        global $DB;

        $pages = array_values(array_filter($this->get_pages($page->instance), fn($other) => $other->id != $page->id));
        $position = max(0, min($position, count($pages)));
        array_splice($pages, $position, 0, [$page]);

        $transaction = $DB->start_delegated_transaction();
        foreach ($pages as $sortorder => $record) {
            if ((int) $record->sortorder !== $sortorder) {
                $DB->update_record('mootimeter_pages', (object) [
                    'id' => $record->id,
                    'sortorder' => $sortorder,
                    'timemodified' => self::now(),
                ]);
            }
        }
        $transaction->allow_commit();

        $page->sortorder = $position;
        $this->notify_data_changed((int) $page->instance, self::CHANGE_PAGELIST);
    }

    /**
     * Delete a page with its settings and answers.
     *
     * @param \stdClass $page
     */
    public function delete_page(\stdClass $page): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        self::get_toolhelper($page->tool)->delete_page_tool($page);
        $DB->delete_records('mootimeter_tool_settings', ['pageid' => $page->id]);
        $DB->delete_records('mootimeter_pages', ['id' => $page->id]);
        $transaction->allow_commit();

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->instance, self::CHANGE_PAGELIST);
    }

    /**
     * Store a setting of a page.
     *
     * @param \stdClass $page
     * @param string $name
     * @param string $value
     */
    public function set_tool_config(\stdClass $page, string $name, string $value): void {
        global $DB;

        $conditions = ['tool' => $page->tool, 'pageid' => $page->id, 'name' => $name];
        $record = $DB->get_record('mootimeter_tool_settings', $conditions);
        if ($record) {
            $record->value = $value;
            $record->timemodified = self::now();
            $DB->update_record('mootimeter_tool_settings', $record);
        } else {
            $DB->insert_record('mootimeter_tool_settings', (object) ($conditions + [
                'value' => $value,
                'timemodified' => self::now(),
            ]));
        }

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_SETTINGS);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
        $this->notify_data_changed((int) $page->instance, self::CHANGE_PAGELIST);
    }

    /**
     * Get a setting of a page.
     *
     * @param \stdClass|int $pageorid
     * @param string $name
     * @param string $default returned if the setting is not stored
     * @return string
     */
    public static function get_tool_config(\stdClass|int $pageorid, string $name, string $default = ''): string {
        global $DB;

        $pageid = is_object($pageorid) ? $pageorid->id : $pageorid;
        $value = $DB->get_field('mootimeter_tool_settings', 'value', ['pageid' => $pageid, 'name' => $name]);
        return $value === false || $value === null ? $default : (string) $value;
    }

    /**
     * Whether a page has answers.
     *
     * @param \stdClass $page
     * @return bool
     */
    public function has_answers(\stdClass $page): bool {
        return !empty($this->get_answers(self::get_toolhelper($page->tool)->get_answer_table(), $page->id));
    }

    /**
     * Store answers of the current user.
     *
     * With $updateexisting the previous answers of the user are replaced, otherwise the answers are added.
     *
     * @param string $table
     * @param \stdClass[] $records answer records with pageid and the answer column
     * @param bool $updateexisting
     * @return int[] ids of the stored answers
     * @throws coding_exception if a record has no page id
     */
    public function store_answer(string $table, array $records, bool $updateexisting = false): array {
        global $DB, $USER;

        if (!$records || empty(reset($records)->pageid)) {
            throw new coding_exception('Answers must belong to a page.');
        }
        $pageid = (int) reset($records)->pageid;

        $transaction = $DB->start_delegated_transaction();
        if ($updateexisting) {
            $DB->delete_records($table, ['pageid' => $pageid, 'usermodified' => $USER->id]);
        }
        $answerids = [];
        foreach ($records as $record) {
            $record->usermodified = $USER->id;
            $record->timecreated = $record->timecreated ?? self::now();
            // Not modified yet, 0 keeps the column usable with GREATEST().
            $record->timemodified = 0;
            $answerids[] = $DB->insert_record($table, $record);
        }
        $transaction->allow_commit();

        $this->clear_caches($pageid);
        $this->notify_data_changed($pageid, self::CHANGE_ANSWERS);
        return $answerids;
    }

    /**
     * Delete all answers of a page.
     *
     * @param \stdClass $page
     */
    public function delete_all_answers(\stdClass $page): void {
        $this->delete_answers($page, []);
    }

    /**
     * Delete answers of a page matching the conditions.
     *
     * @param \stdClass $page
     * @param array $conditions additional conditions for the answer table
     */
    public function delete_answers(\stdClass $page, array $conditions): void {
        global $DB;

        $DB->delete_records(self::get_toolhelper($page->tool)->get_answer_table(), ['pageid' => $page->id] + $conditions);
        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
    }

    /**
     * Get the answers of a user on a page, keyed by the answer column.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @param int $userid
     * @return \stdClass[]
     */
    public function get_user_answers(string $table, int $pageid, string $answercolumn, int $userid): array {
        $useranswers = [];
        foreach ($this->get_answers($table, $pageid) as $answer) {
            if ((int) $answer->usermodified === $userid) {
                $useranswers[$answer->{$answercolumn}] = $answer;
            }
        }
        return $useranswers;
    }

    /**
     * Get all answers of a page, cached.
     *
     * @param string $table
     * @param int $pageid
     * @return \stdClass[] keyed by id
     */
    public function get_answers(string $table, int $pageid): array {
        global $DB;

        $cache = cache::make('mod_mootimeter', 'answers');
        $cachekey = 'answers_' . $pageid;
        $records = $cache->get($cachekey);
        if ($records === false) {
            $records = $DB->get_records($table, ['pageid' => $pageid], 'id ASC');
            $cache->set($cachekey, $records);
        }
        return $records;
    }

    /**
     * Count the answers of a page grouped by the answer column, cached.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @return int[] answer => count
     */
    public function get_answers_grouped(string $table, int $pageid, string $answercolumn): array {
        global $DB;

        $cache = cache::make('mod_mootimeter', 'answers');
        $cachekey = 'cnt_' . $pageid;
        $counts = $cache->get($cachekey);
        if ($counts === false) {
            $sql = "SELECT $answercolumn AS answer, COUNT(*) AS cnt
                      FROM {" . $table . "}
                     WHERE pageid = :pageid
                  GROUP BY $answercolumn";
            $counts = array_map('intval', $DB->get_records_sql_menu($sql, ['pageid' => $pageid]));
            $cache->set($cachekey, $counts);
        }
        return $counts;
    }

    /**
     * Remember that data changed. Clients compare the revisions to reload changed data.
     *
     * @param int $id instance id for the page list, page id otherwise
     * @param string $identifier one of the CHANGE_ constants
     */
    public function notify_data_changed(int $id, string $identifier): void {
        // Milliseconds, so that clients notice several changes within the same second.
        $revision = (int) di::get(clock::class)->now()->format('Uv');
        cache::make('mod_mootimeter', 'lastupdated')->set('lastupdate_' . $identifier . '_' . $id, $revision);
    }

    /**
     * Get the revision of data.
     *
     * @param int $id instance id for the page list, page id otherwise
     * @param string $identifier one of the CHANGE_ constants
     * @return int
     */
    public function get_data_changed(int $id, string $identifier): int {
        $cache = cache::make('mod_mootimeter', 'lastupdated');
        $revision = $cache->get('lastupdate_' . $identifier . '_' . $id);
        if ($revision === false) {
            $this->notify_data_changed($id, $identifier);
            $revision = $cache->get('lastupdate_' . $identifier . '_' . $id);
        }
        return (int) $revision;
    }

    /**
     * Whether the current user may see the results of a page.
     *
     * @param \stdClass $page
     * @param bool $ismoderator
     * @return bool
     */
    public function can_view_results(\stdClass $page, bool $ismoderator): bool {
        return $ismoderator || !empty(self::get_tool_config($page, 'showonteacherpermission'));
    }

    /**
     * Clear the cached answers of a page.
     *
     * @param int $pageid
     */
    public function clear_caches(int $pageid): void {
        $cache = cache::make('mod_mootimeter', 'answers');
        $cache->delete('answers_' . $pageid);
        $cache->delete('cnt_' . $pageid);
    }
}
