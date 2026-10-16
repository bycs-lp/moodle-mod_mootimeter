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
 * Types of the quiz and poll tool, mirroring mootimetertool_quiz\route\schema.
 *
 * @module     mootimetertool_quiz/types
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {PageInfo} from '@moodle/lms/mod_mootimeter/lib/types';

export type Visualization = 'pillar' | 'bar' | 'line' | 'pie';

export type QuizOption = {
    id: number;
    text: string;
    textHtml: string;
    isCorrect: boolean;
};

export type QuizData = {
    pageId: number;
    options: QuizOption[];
    selectedOptionIds: number[];
    maxAnswers: number;
    correctionShown: boolean;
    supportsCorrectOptions: boolean;
    visualization: Visualization;
};

export type QuizResults = {
    available: boolean;
    visualization: Visualization;
    options: {id: number; label: string; count: number}[];
    colors: string[];
};

export type QuizResponse = {
    id: number;
    fullName: string;
    optionIds: number[];
    createdAt: string;
};

/**
 * The component owning the REST resources of a page, the poll reuses the quiz frontend.
 *
 * @param page
 * @returns The frankenstyle component.
 */
export const componentOf = (page: PageInfo): string => `mootimetertool_${page.tool}`;
