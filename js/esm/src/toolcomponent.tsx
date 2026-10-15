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
 * Loads and renders a React component of a mootimetertool subplugin.
 *
 * @module     mod_mootimeter/toolcomponent
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useState, ComponentType} from 'react';
import {Loading} from './lib/components';
import {displayException} from './lib/moodle';
import type {ToolViewProps} from './lib/types';

type ToolComponentProps = ToolViewProps & {
    specifier: string;
};

/**
 * Import the default export of the specifier through the import map and render it.
 *
 * @param {ToolComponentProps} props
 * @returns {React.JSX.Element|null}
 */
export default function ToolComponent({specifier, ...props}: ToolComponentProps): React.JSX.Element | null {
    const [component, setComponent] = useState<{specifier: string; Component: ComponentType<ToolViewProps>} | null>(null);

    useEffect(() => {
        if (!specifier) {
            return undefined;
        }
        let active = true;
        import(specifier)
            .then((module) => active && setComponent({specifier, Component: module.default}))
            .catch(displayException);
        return () => {
            active = false;
        };
    }, [specifier]);

    if (!specifier) {
        return null;
    }
    if (!component || component.specifier !== specifier) {
        return <Loading />;
    }
    const {Component} = component;
    return <Component {...props} />;
}
