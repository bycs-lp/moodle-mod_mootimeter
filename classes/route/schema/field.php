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

use core\param;
use core\router\schema\objects\scalar_type;
use core\router\schema\specification;

/**
 * Scalar JSON field with an OpenAPI description, optional enumeration and format.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field extends scalar_type {
    /**
     * Constructor.
     *
     * @param param $type Moodle parameter type used for validation.
     * @param string $description Description of the field.
     * @param bool $required Whether null is rejected.
     * @param string[] $enum Allowed values.
     * @param string $format OpenAPI format, e.g. date-time.
     */
    public function __construct(
        param $type,
        /** @var string Description of the field. */
        protected readonly string $description = '',
        bool $required = false,
        /** @var string[] Allowed values. */
        protected readonly array $enum = [],
        /** @var string OpenAPI format. */
        protected readonly string $format = '',
    ) {
        parent::__construct(type: $type, required: $required);
    }

    #[\Override]
    public function get_openapi_description(specification $api, ?string $path = null): ?\stdClass {
        $data = parent::get_openapi_description($api, $path);
        if ($this->description !== '') {
            $data->description = $this->description;
        }
        if ($this->enum) {
            $data->enum = $this->enum;
            unset($data->pattern);
        }
        if ($this->format !== '') {
            $data->format = $this->format;
            unset($data->pattern);
        }
        return $data;
    }

    #[\Override]
    public function validate_data(mixed $data) {
        $value = parent::validate_data($data);
        if ($this->enum && $value !== null && !in_array($value, $this->enum, true)) {
            throw new \core\exception\invalid_parameter_exception('Unknown value: ' . $value);
        }
        return $value;
    }
}
