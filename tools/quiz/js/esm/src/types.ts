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
 * Types of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/types
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type QuizOption = {
    id: number;
    text: string;
    texthtml: string;
    iscorrect: boolean;
};

/** See mootimetertool_quiz\quiz::export_page_data(). */
export type QuizData = {
    options: QuizOption[];
    selected: number[];
    maxanswers: number;
    answercorrection: boolean;
    supportscorrect: boolean;
    visualization: number;
};

export const VISUALIZATION_PILLAR = 1;
export const VISUALIZATION_BAR = 2;
export const VISUALIZATION_LINE = 3;
export const VISUALIZATION_PIE = 4;
