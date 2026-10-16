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

namespace mootimetertool_quiz\form;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the import of answer options.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversMethod(import_options::class, 'parse_options')]
final class import_options_test extends \basic_testcase {
    /**
     * The first non-empty cell of each line is an answer option.
     *
     * @param string $content
     * @param string[] $expected
     */
    #[DataProvider('parse_options_provider')]
    public function test_parse_options(string $content, array $expected): void {
        $this->assertSame($expected, import_options::parse_options($content));
    }

    /**
     * Data provider for test_parse_options.
     *
     * @return array
     */
    public static function parse_options_provider(): array {
        return [
            'semicolon with quotes' => ["\"Berlin\";capital\nMunich\n\n\"Hamburg, port\"\n", ['Berlin', 'Munich', 'Hamburg, port']],
            'comma and leading empty cells' => [",Paris,France\r\nRome\r\n", ['Paris', 'Rome']],
            'tab' => ["Oslo\tNorway\nBern\tSwitzerland", ['Oslo', 'Bern']],
            'byte order mark' => ["\xEF\xBB\xBFLisbon", ['Lisbon']],
            'windows encoding' => ["M\xFCnchen", ['München']],
            'empty' => ["\n\n", []],
        ];
    }
}
