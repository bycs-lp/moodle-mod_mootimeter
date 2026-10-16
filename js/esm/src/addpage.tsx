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
 * Tool selection for a new page.
 *
 * @module     mod_mootimeter/addpage
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {useStrings} from './lib/hooks';
import type {ToolInfo} from './lib/types';

type AddPageProps = {
    tools: ToolInfo[];
    onAdd: (tool: string) => void;
};

/**
 * Cards to choose the tool of a new page.
 *
 * @param {AddPageProps} props
 * @returns {React.JSX.Element}
 */
export default function AddPage({tools, onAdd}: AddPageProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['select_page_type_heading', 'select_page_type_paragraph']);

    return (
        <div className="mootimeter-add-tool-container">
            <div className="mootimeter-add-tool-header">
                <h3>{s.select_page_type_heading}</h3>
                {s.select_page_type_paragraph && <p>{s.select_page_type_paragraph}</p>}
            </div>
            <div className="mootimeter-tool-select-wrapper">
                {tools.map((tool) => (
                    <button
                        key={tool.name}
                        type="button"
                        className="mootimeter-radio-card mtmt-tool-selector-list"
                        data-name={tool.name}
                        onClick={() => onAdd(tool.name)}
                    >
                        <img src={tool.iconUrl} width="40px" className="m-1" alt="" />
                        <span className="card-details">
                            <span className="card-type">{tool.displayName}</span>
                            <span className="card-desc">{tool.description}</span>
                        </span>
                        <span className="active-border"></span>
                    </button>
                ))}
            </div>
        </div>
    );
}
