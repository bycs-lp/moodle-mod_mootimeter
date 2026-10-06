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
 * The mod_mootimeter helper class for pagelist.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local;

use dml_exception;
use coding_exception;
use moodle_exception;

/**
 * The mod_mootimeter helper class for pagelist.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pagelist {
    /** @var int Permutate direction up */
    const PERMUTATE_UP = 1;
    /** @var int Permutate direction down */
    const PERMUTATE_DOWN = -1;

    /**
     * Get the pagelist parameters for rendering.
     *
     * @param int $cmid
     * @param int $pageidselected
     * @param object $dataset
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_pagelist_params(int $cmid, int $pageidselected, object $dataset): array {
        global $PAGE, $USER;

        $helper = new \mod_mootimeter\helper();

        $modulecontext = \context_module::instance($cmid);
        $PAGE->set_context($modulecontext);
        $cm = get_coursemodule_from_id('mootimeter', $cmid, 0, false, MUST_EXIST);

        $pages = $helper->get_pages($cm->instance, "sortorder ASC");

        $temppages = [];
        if (!in_array($pageidselected, array_keys($pages)) && $pageidselected > 0 && $dataset->pageid != $pageidselected) {
            $temppages = $pages;
            $temppages['loadpageid'] = array_shift($temppages)->id;
            $pageidselected = $temppages['loadpageid'];
        }

        $pagenumber = 1;

        $temppages['pageid'] = $pageidselected;
        $temppages['cmid'] = $cm->id;
        $temppages['instance'] = $cm->instance;

        if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) && !empty($USER->editing)) {
            $temppages['isediting'] = $USER->editing;
        }

        foreach ($pages as $pagerow) {
            $uniqid = uniqid('mtmt_page_'); // This is necessary to separate the different page list elements.
            $pixrawurl = '/mod/mootimeter/tools/' . $pagerow->tool . '/pix/' . $pagerow->tool . '-white.svg';

            $pagevisibleiconclass = 'fa-eye';
            $pagevisibilitytooltip = get_string('tooltip_enable_page', 'mod_mootimeter');
            if (empty($pagerow->visible)) {
                $pagevisibleiconclass = 'fa-eye-slash';
                $pagevisibilitytooltip = get_string('tooltip_disable_page', 'mod_mootimeter');
            }

            $dataseticonvisibility = [
                'data-togglename="page_visibility"',
                'data-pageid="' . $pagerow->id . '"',
                'data-iconid="page_visibility_iconid_' . $uniqid . '"',
                'data-iconenabled="fa-eye"',
                'data-icondisabled="fa-eye-slash"',
            ];

            $temppages['pageslist'][] = [
                'toolicon' => (new \moodle_url($pixrawurl))->out(true),
                'active' => ($pagerow->id == $pageidselected) ? "active" : "",
                'pageunvisible' => (empty($pagerow->visible)) ? "pageunvisible" : "",
                'pageid' => $pagerow->id,
                'pagenumber' => $pagenumber,
                'width' => "35px",
                'cmid' => $cm->id,
                'id' => $uniqid,
                'tooltip' => mb_strimwidth($helper::get_tool_config($pagerow, 'question'), 0, 40, '...'),
                'uniqid' => $uniqid,
                'page_visibility-eye' => [
                    'icon' => $pagevisibleiconclass,
                    'id' => 'toggle_page_visibility_' . $uniqid,
                    'iconid' => 'page_visibility_iconid_' . $uniqid,
                    'dataset' => implode(" ", $dataseticonvisibility),
                    'tooltip' => $pagevisibilitytooltip,
                ],
            ];
            $pagenumber++;
        }

        $temppages['dataset']['pagelistlastupdated'] = $helper->get_data_changed($cm, 'pagelist');

        $temppages['dataset']['refreshinterval'] = get_config('mod_mootimeter', 'refreshinterval');
        \mod_mootimeter\local\mootimeterstate::add_mootimeterstate('refreshinterval', $temppages['dataset']['refreshinterval']);

        return $temppages;
    }

    /**
     * Permutate the pages of an instance until the targetposition of page is reached.
     * @param int $pageid
     * @param int $targetposition
     * @return void
     */
    public function permutate_sortorder(int $pageid, int $targetposition) {
        global $DB;

        $helper = new \mod_mootimeter\helper();
        $instance = $helper::get_instance_by_pageid($pageid);

        // First reset the sortorder of all pages to take sure that there are no missing sortordersteps.
        $pages = $helper->get_pages($instance, 'sortorder ASC');
        $i = 0; // Starting with 0, because core_sortable starts with index 0.
        foreach ($pages as $page) {
            $page->sortorder = $i;
            $page->timemodified = time();
            $helper->store_page($page);
            $i++;
        }
        $page = $helper->get_page($pageid);

        if ($page->sortorder == $targetposition) {
            // Nothing to do here.
            return;
        }

        // Get the permutation direction.
        if ($page->sortorder < $targetposition) {
            $permutationdirection = self::PERMUTATE_UP;
        } else {
            $permutationdirection = self::PERMUTATE_DOWN;
        }

        // Now permutate the pages as long as $page->sortorder reaches $targetposition.
        // Or an timeout is reached.
        $counter = 0;
        $page = $helper->get_page($pageid);
        while ($page->sortorder != $targetposition) {
            // Early exit, to prevent endless loop.
            if ($counter >= 1000) {
                // Exit the loop when the counter exceeds the limit.
                break;
            }

            $params = [
                'instance' => $page->instance,
                'sortorder' => $page->sortorder + $permutationdirection,
            ];
            $page2 = $DB->get_record('mootimeter_pages', $params);

            if (empty($page2)) {
                break;
            }

            // Permutate page and page 2.
            $tempsortorder = $page->sortorder;
            $page->sortorder = $page2->sortorder;
            $page2->sortorder = $tempsortorder;

            // Save chenges of page2.
            $helper->store_page($page2);
            $counter++;
        }
        // Finally store the new positioned page.
        $helper->store_page($page);
        $helper->notify_data_changed($helper->get_cm_by_instance($instance), 'pagelist');
    }
}
