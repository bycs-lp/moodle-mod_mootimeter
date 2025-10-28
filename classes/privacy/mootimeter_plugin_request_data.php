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
 * This file contains the mod_mootimeter mootimeter_plugin_request_data class
 *
 * For assign plugin privacy data to fulfill requests.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\privacy;

/**
 * An object for fulfilling an mootimeter plugin data request.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mootimeter_plugin_request_data {
    /** @var object The page that we are dealing with. */
    protected $page;

    /** @var object Module context */
    protected $context;

    /** @var array The path or location that we are exporting data to. */
    protected $subcontext;

    /** @var object If set then only export data related directly to this user. */
    protected $user;

    /** @var array The user IDs of the users that will be affected. */
    protected $userids;

    /**
     * Object creator for mootimeter plugin request data.
     *
     * @param \context $context Context object.
     * @param object $page The Mootimeter object.
     * @param ?object $user The user object.
     * @param array  $subcontext
     */
    public function __construct(\context $context, object $page, ?object $user = null, array $subcontext = []) {
        $this->context = $context;
        $this->page = $page;
        if ($user == null) {
            $this->user = new \stdClass();
        } else {
            $this->user = $user;
        }
        $this->subcontext = $subcontext;
    }

    /**
     * Method for adding an array of user IDs. This will do a query to populate the submissions and grades
     * for these users.
     *
     * @param array $userids User IDs to do something with.
     */
    public function set_userids(array $userids) {
        $this->userids = $userids;
    }

    /**
     * Getter for this attribute.
     *
     * @return \context Context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Getter for this attribute.
     *
     * @return object The user id. If set then only information directly related to this user ID will be returned.
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Getter for this attribute.
     *
     * @return object The page object.
     */
    public function get_page() {
        return $this->page;
    }

    /**
     * Get all of the user IDs
     *
     * @return array User IDs
     */
    public function get_userids() {
        return $this->userids;
    }

    /**
     * Get the subcontext
     *
     * @return array Subcontext
     */
    public function get_subcontext() {
        return $this->subcontext;
    }
}
