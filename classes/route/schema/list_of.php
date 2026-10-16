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

namespace mod_mootimeter\route\schema;

use core\exception\invalid_parameter_exception;
use core\router\schema\objects\type_base;
use core\router\schema\specification;

/**
 * JSON array whose items all have the same schema.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_of extends type_base {
    /**
     * Constructor.
     *
     * @param type_base $items Schema of the items.
     * @param string $description Description of the list.
     * @param int $maxitems Maximum number of items, 0 for no limit.
     */
    public function __construct(
        /** @var type_base Schema of the items. */
        protected readonly type_base $items,
        /** @var string Description of the list. */
        protected readonly string $description = '',
        /** @var int Maximum number of items, 0 for no limit. */
        protected readonly int $maxitems = 0,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function get_openapi_description(specification $api, ?string $path = null): ?\stdClass {
        $data = (object) [
            'type' => 'array',
            'items' => $this->items->get_openapi_schema($api),
        ];
        if ($this->description !== '') {
            $data->description = $this->description;
        }
        if ($this->maxitems) {
            $data->maxItems = $this->maxitems;
        }
        return $data;
    }

    #[\Override]
    public function validate_data(mixed $data) {
        if (!is_array($data) || !array_is_list($data)) {
            throw new invalid_parameter_exception('Invalid data type, expected a list.');
        }
        if ($this->maxitems && count($data) > $this->maxitems) {
            throw new invalid_parameter_exception('Too many items, at most ' . $this->maxitems . ' are allowed.');
        }
        return array_map(fn($item) => $this->items->validate_data($item), $data);
    }
}
