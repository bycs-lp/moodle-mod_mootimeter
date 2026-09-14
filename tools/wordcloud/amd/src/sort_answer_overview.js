// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * MBS-10742: Client side sorting for the wordcloud moderator answer overview.
 *
 * @module      mootimetertool_wordcloud/sort_answer_overview
 * @copyright   2026, ISB Bayern
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const ANSWER_COLUMN_INDEX = 2;
const NBR_COLUMN_INDEX = 0;

const nextDir = (current) => {
    if (current === 'none') {
        return 'asc';
    }
    if (current === 'asc') {
        return 'desc';
    }
    return 'none';
};

const updateIcon = (icon, dir) => {
    icon.classList.remove('fa-sort', 'fa-sort-up', 'fa-sort-down');
    if (dir === 'asc') {
        icon.classList.add('fa-sort-up');
    } else if (dir === 'desc') {
        icon.classList.add('fa-sort-down');
    } else {
        icon.classList.add('fa-sort');
    }
};

const compareAnswer = (a, b) => {
    const answerA = a.cells[ANSWER_COLUMN_INDEX].innerText.trim();
    const answerB = b.cells[ANSWER_COLUMN_INDEX].innerText.trim();
    return answerA.localeCompare(answerB, undefined, {sensitivity: 'base', numeric: true});
};

export const init = (tableId) => {
    const table = document.getElementById(tableId);
    if (!table) {
        return;
    }
    const tbody = table.querySelector('tbody');
    const header = table.querySelector('.mtmt-sortable-header[data-sort-key="answer"]');
    if (!tbody || !header) {
        return;
    }
    const icon = header.querySelector('.mtmt-sort-icon');
    const originalOrder = Array.from(tbody.rows);

    header.addEventListener('click', () => {
        const dir = nextDir(header.dataset.sortDir);
        header.dataset.sortDir = dir;
        updateIcon(icon, dir);

        let rows;
        if (dir === 'none') {
            rows = [...originalOrder];
        } else {
            rows = [...tbody.rows].sort(compareAnswer);
            if (dir === 'desc') {
                rows.reverse();
            }
        }

        rows.forEach((row, i) => {
            tbody.appendChild(row);
            row.cells[NBR_COLUMN_INDEX].textContent = i + 1;
        });
    });
};
