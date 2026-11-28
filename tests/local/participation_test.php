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

use core_cache\cache;
use mod_mootimeter\helper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the live participation.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(participation::class)]
final class participation_test extends \advanced_testcase {
    /** @var \stdClass The course. */
    private \stdClass $course;

    /** @var \stdClass The page. */
    private \stdClass $page;

    /** @var \stdClass[] Students keyed by a, b, ab and none (their groups). */
    private array $students;

    /** @var \stdClass The editing teacher. */
    private \stdClass $teacher;

    /** @var \stdClass[] Groups keyed by a and b. */
    private array $groups;

    /** @var int Id of the mootimeter instance. */
    private int $instanceid;

    /**
     * Create a course with two groups, students in no, one or both groups and a mootimeter page.
     *
     * @param int $groupmode
     */
    private function setup_course(int $groupmode): void {
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $mootimeter = $generator->create_module('mootimeter', ['course' => $this->course->id, 'groupmode' => $groupmode]);
        $this->teacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->groups = [
            'a' => $generator->create_group(['courseid' => $this->course->id]),
            'b' => $generator->create_group(['courseid' => $this->course->id]),
        ];
        foreach (['a' => ['a'], 'b' => ['b'], 'ab' => ['a', 'b'], 'none' => []] as $key => $groups) {
            $this->students[$key] = $generator->create_and_enrol($this->course, 'student');
            foreach ($groups as $group) {
                $generator->create_group_member(['groupid' => $this->groups[$group]->id, 'userid' => $this->students[$key]->id]);
            }
        }
        $this->instanceid = (int) $mootimeter->id;
        $this->page = $generator->get_plugin_generator('mod_mootimeter')->create_page(['instance' => $mootimeter->id]);
    }

    /**
     * Get the access of a user to the activity.
     *
     * @param \stdClass $user
     * @return access
     */
    private function access_as(\stdClass $user): access {
        global $PAGE;

        $this->setUser($user);
        // Like a new request: the welcome messages of the enrolments have already set up the theme of the page.
        $PAGE = new \moodle_page();
        // The course module holds the visibility for the user it was loaded for.
        return access::for_cm(helper::get_cm_by_instance($this->instanceid));
    }

    /**
     * Moderators can see all groups, participants only their own ones in the separate groups mode.
     */
    public function test_group_options(): void {
        $this->resetAfterTest();
        $this->setup_course(SEPARATEGROUPS);
        $participation = \core\di::get(participation::class);

        $options = $participation->get_group_options($this->access_as($this->teacher));
        $this->assertTrue($options['allowall']);
        $this->assertEqualsCanonicalizing(
            [(int) $this->groups['a']->id, (int) $this->groups['b']->id],
            array_keys($options['groups'])
        );

        $access = $this->access_as($this->students['a']);
        $options = $participation->get_group_options($access);
        $this->assertFalse($options['allowall']);
        $this->assertSame([(int) $this->groups['a']->id], array_keys($options['groups']));
        $this->assertSame((int) $this->groups['a']->id, $options['active']);
        $this->assertTrue($participation->can_view_group($access, (int) $this->groups['a']->id));
        $this->assertFalse($participation->can_view_group($access, (int) $this->groups['b']->id));
        $this->assertFalse($participation->can_view_group($access, 0));

        $this->assertSame([], $participation->get_group_options($this->access_as($this->students['none']))['groups']);
    }

    /**
     * The counts are filtered by group, members of several groups count in each of them, moderators never count.
     */
    public function test_get_counts(): void {
        $this->resetAfterTest();
        $clock = $this->mock_clock_with_frozen(1000);
        $this->setup_course(VISIBLEGROUPS);
        $participation = \core\di::get(participation::class);

        foreach (['a', 'ab', 'none'] as $key) {
            $participation->record_presence($this->page, (int) $this->students[$key]->id);
        }
        $participation->record_presence($this->page, (int) $this->teacher->id);
        $this->setUser($this->students['ab']);
        (new \mootimetertool_wordcloud\wordcloud())->submit_answer($this->page, 'Answer');
        $this->setUser($this->teacher);
        (new \mootimetertool_wordcloud\wordcloud())->submit_answer($this->page, 'Teacher');

        $access = $this->access_as($this->teacher);
        $this->assertSame(['answered' => 1, 'active' => 3, 'enrolled' => 4], $participation->get_counts($access, $this->page, 0));
        $groupa = (int) $this->groups['a']->id;
        $counts = $participation->get_counts($access, $this->page, $groupa);
        $this->assertSame(['answered' => 1, 'active' => 2, 'enrolled' => 2], $counts);
        $groupb = (int) $this->groups['b']->id;
        $counts = $participation->get_counts($access, $this->page, $groupb);
        $this->assertSame(['answered' => 1, 'active' => 1, 'enrolled' => 2], $counts);

        // Pollers stay active for two time buckets, the moderators are only filtered out when counting.
        $clock->bump(participation::BUCKET_MIN);
        $this->assertCount(4, $participation->get_active_user_ids($this->page));
        $clock->bump(participation::BUCKET_MIN);
        $this->assertCount(0, $participation->get_active_user_ids($this->page));
    }

    /**
     * A presence lost by a concurrent write of the same shard is added again with the next poll.
     */
    public function test_record_presence_heals_lost_update(): void {
        $this->resetAfterTest();
        $this->mock_clock_with_frozen(1000);
        $this->setup_course(NOGROUPS);
        $participation = \core\di::get(participation::class);
        $userid = (int) $this->students['a']->id;

        $participation->record_presence($this->page, $userid);
        $this->assertSame([$userid], $participation->get_active_user_ids($this->page));

        // Simulate a concurrent request of another user of the same shard that overwrote the shard.
        $bucket = intdiv(1000, participation::BUCKET_MIN);
        $key = 'p_' . $this->page->id . '_' . $bucket . '_' . ($userid % participation::SHARDS);
        cache::make('mod_mootimeter', 'presence')->set($key, [$userid + participation::SHARDS]);
        $this->assertNotContains($userid, $participation->get_active_user_ids($this->page));

        $participation->record_presence($this->page, $userid);
        $this->assertContains($userid, $participation->get_active_user_ids($this->page));
        $this->assertContains($userid + participation::SHARDS, $participation->get_active_user_ids($this->page));
    }
}
