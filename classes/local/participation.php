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

use core\clock;
use core_cache\cache;
use mod_mootimeter\helper;

/**
 * Live participation of a page: participants who answered, who are active and who could take part.
 *
 * The active participants are tracked in the application cache without any database write. The user ids are spread
 * over shards per time bucket. A participant adds itself to its shard without lock when missing, an entry lost by a
 * concurrent write is added again with the next poll.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participation {
    /** @var int Number of shards the active users of a bucket are spread over. */
    const SHARDS = 16;

    /** @var int Shortest time bucket in seconds. */
    const BUCKET_MIN = 15;

    /** @var int Longest time bucket in seconds, the presence cache keeps entries for 300 seconds. */
    const BUCKET_MAX = 120;

    /**
     * Constructor.
     *
     * @param clock $clock
     */
    public function __construct(
        /** @var clock The clock. */
        private readonly clock $clock,
    ) {
    }

    /**
     * Get the groups the current user may select for the counter of an activity.
     *
     * @param access $access
     * @return array with the keys mode (group mode), allowall (whether "all participants" may be selected),
     *               groups (id => name) and active (preselected group id, 0 for all)
     */
    public function get_group_options(access $access): array {
        $mode = (int) groups_get_activity_groupmode($access->cm);
        if ($mode === NOGROUPS) {
            return ['mode' => $mode, 'allowall' => true, 'groups' => [], 'active' => 0];
        }

        $groups = [];
        foreach (groups_get_activity_allowed_groups($access->cm) ?: [] as $group) {
            $groups[(int) $group->id] = format_string($group->name, true, ['context' => $access->context]);
        }
        $allowall = $mode === VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $access->context);
        $active = (int) groups_get_activity_group($access->cm, false, null);
        if (!array_key_exists($active, $groups) && !($active === 0 && $allowall)) {
            $active = $allowall ? 0 : (int) array_key_first($groups);
        }
        return ['mode' => $mode, 'allowall' => $allowall, 'groups' => $groups, 'active' => $active];
    }

    /**
     * Whether the current user may see the counter of the given group.
     *
     * @param access $access
     * @param int $groupid 0 for all participants
     * @return bool
     */
    public function can_view_group(access $access, int $groupid): bool {
        $options = $this->get_group_options($access);
        if ($options['mode'] === NOGROUPS) {
            return $groupid === 0;
        }
        return $groupid === 0 ? $options['allowall'] : array_key_exists($groupid, $options['groups']);
    }

    /**
     * Remember that a participant shows a page.
     *
     * @param \stdClass $page
     * @param int $userid
     */
    public function record_presence(\stdClass $page, int $userid): void {
        $cache = cache::make('mod_mootimeter', 'presence');
        $key = $this->get_presence_key((int) $page->id, $this->get_bucket(), $userid % self::SHARDS);
        $userids = $cache->get($key) ?: [];
        if (!in_array($userid, $userids, true)) {
            // No lock on purpose: if a concurrent write drops this user, the next poll adds it again.
            $userids[] = $userid;
            $cache->set($key, $userids);
        }
    }

    /**
     * Get the participation of a page.
     *
     * @param access $access access to the activity
     * @param \stdClass $page
     * @param int $groupid 0 for all participants
     * @return array with the keys answered, active and enrolled
     */
    public function get_counts(access $access, \stdClass $page, int $groupid): array {
        $moderators = $this->get_moderator_ids($access);
        $members = $groupid ? $this->get_group_member_ids($groupid) : null;
        $filter = function (array $userids) use ($moderators, $members): int {
            $userids = array_diff($userids, $moderators);
            return count($members === null ? $userids : array_intersect($userids, $members));
        };

        return [
            'answered' => $filter((new helper())->get_answering_userids($page)),
            'active' => $filter($this->get_active_user_ids($page)),
            'enrolled' => $this->count_enrolled($access, $groupid, $moderators),
        ];
    }

    /**
     * Get the participants who showed the page in the last two time buckets.
     *
     * @param \stdClass $page
     * @return int[]
     */
    public function get_active_user_ids(\stdClass $page): array {
        $bucket = $this->get_bucket();
        $keys = [];
        foreach ([$bucket - 1, $bucket] as $b) {
            for ($shard = 0; $shard < self::SHARDS; $shard++) {
                $keys[] = $this->get_presence_key((int) $page->id, $b, $shard);
            }
        }
        $userids = [];
        foreach (cache::make('mod_mootimeter', 'presence')->get_many($keys) as $shard) {
            if ($shard) {
                $userids = array_merge($userids, $shard);
            }
        }
        return array_values(array_unique($userids));
    }

    /**
     * Get the current time bucket. It is at least as long as the polling interval, so pollers are never missed.
     *
     * @return int
     */
    protected function get_bucket(): int {
        $interval = (int) ceil((int) get_config('mod_mootimeter', 'refreshinterval') / 1000);
        $width = max(self::BUCKET_MIN, min(self::BUCKET_MAX, $interval));
        return intdiv($this->clock->time(), $width);
    }

    /**
     * Get the cache key of a presence shard.
     *
     * @param int $pageid
     * @param int $bucket
     * @param int $shard
     * @return string
     */
    protected function get_presence_key(int $pageid, int $bucket, int $shard): string {
        return 'p_' . $pageid . '_' . $bucket . '_' . $shard;
    }

    /**
     * Get the users who moderate the activity, cached for a minute.
     *
     * @param access $access
     * @return int[]
     */
    protected function get_moderator_ids(access $access): array {
        $cache = cache::make('mod_mootimeter', 'participation');
        $key = 'moderators_' . $access->cm->id;
        $userids = $cache->get($key);
        if ($userids === false) {
            $users = get_users_by_capability($access->context, 'mod/mootimeter:moderator', 'u.id');
            $userids = array_map('intval', array_keys($users));
            $cache->set($key, $userids);
        }
        return $userids;
    }

    /**
     * Get the members of a group, cached for a minute.
     *
     * @param int $groupid
     * @return int[]
     */
    protected function get_group_member_ids(int $groupid): array {
        $cache = cache::make('mod_mootimeter', 'participation');
        $key = 'members_' . $groupid;
        $userids = $cache->get($key);
        if ($userids === false) {
            $userids = array_map('intval', array_keys(groups_get_members($groupid, 'u.id', 'u.id')));
            $cache->set($key, $userids);
        }
        return $userids;
    }

    /**
     * Count the users with an active enrolment who can view the activity and do not moderate it, cached for a minute.
     *
     * @param access $access
     * @param int $groupid 0 for all participants
     * @param int[] $moderators
     * @return int
     */
    protected function count_enrolled(access $access, int $groupid, array $moderators): int {
        global $DB;

        $cache = cache::make('mod_mootimeter', 'participation');
        $key = 'enrolled_' . $access->cm->id . '_' . $groupid;
        $count = $cache->get($key);
        if ($count === false) {
            $join = get_enrolled_with_capabilities_join($access->context, '', 'mod/mootimeter:view', $groupid, true);
            if ($join->cannotmatchanyrows) {
                $cache->set($key, 0);
                return 0;
            }
            $where = $join->wheres;
            $params = $join->params;
            if ($moderators) {
                [$notinsql, $notinparams] = $DB->get_in_or_equal($moderators, SQL_PARAMS_NAMED, 'mod', false);
                $where .= " AND u.id $notinsql";
                $params += $notinparams;
            }
            $count = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id) FROM {user} u {$join->joins} WHERE $where", $params);
            $cache->set($key, $count);
        }
        return (int) $count;
    }
}
