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
 * Results chart of the quiz and poll tool, drawn with the Chart.js of Moodle core.
 *
 * @module     mootimetertool_quiz/results
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef} from 'react';
import {requireAsync} from '@moodle/lms/core/amd';
import {Loading} from '@moodle/lms/mod_mootimeter/lib/components';
import {useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {componentOf, type QuizResults} from './types';

// Chart.js is loaded as untyped AMD module.
const Chart: any = await requireAsync('core/chartjs');
Chart.defaults.font.size = 25;

type Results = {
    visualization: QuizResults['visualization'];
    labels: string[];
    values: number[];
    colors: string[];
};

/**
 * Build the Chart.js configuration of a visualization.
 *
 * @param {Results} results
 * @param {string} label
 * @returns {object}
 */
const getChartConfig = (results: Results, label: string): any => {
    const config = buildChartConfig(results, label);
    config.options.maintainAspectRatio = false;
    return config;
};

/**
 * Build the Chart.js configuration of a visualization without the common options.
 *
 * @param {Results} results
 * @param {string} label
 * @returns {object}
 */
const buildChartConfig = (results: Results, label: string): any => {
    const integerticks = {ticks: {stepSize: 1}};
    const dataset: any = {label, data: results.values, backgroundColor: results.colors};
    switch (results.visualization) {
        case 'pie':
            return {
                type: 'pie',
                data: {labels: results.labels, datasets: [dataset]},
                options: {responsive: true, plugins: {legend: {position: 'right'}}},
            };
        case 'line':
            return {
                type: 'line',
                data: {
                    labels: results.labels,
                    datasets: [{...dataset, pointStyle: 'circle', pointRadius: 10, pointHoverRadius: 15}],
                },
                options: {indexAxis: 'x', scales: {x: {min: 0, ...integerticks}}},
            };
        case 'bar':
            return {
                type: 'bar',
                data: {labels: results.labels, datasets: [{...dataset, borderRadius: 20}]},
                options: {indexAxis: 'y', scales: {x: integerticks}},
            };
        default:
            return {
                type: 'bar',
                data: {labels: results.labels, datasets: [{...dataset, borderRadius: 20}]},
                options: {indexAxis: 'x', scales: {x: integerticks, y: integerticks}},
            };
    }
};

/**
 * Chart of the counted answers, updated live.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function ResultsView({page, settingsVersion, answersVersion}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_quiz', ['no_answer_due_to_showteacherpermission', 'show_results']);
    const response = useResource<QuizResults>(componentOf(page), `/pages/${page.id}/results`, [settingsVersion, answersVersion]);
    const canvas = useRef<HTMLCanvasElement>(null);
    const chart = useRef<any>(null);

    const label = new DOMParser().parseFromString(page.questionHtml, 'text/html').body.textContent ?? '';
    const results: Results | null = response?.available ? {
        visualization: response.visualization,
        labels: response.options.map((option) => option.label),
        values: response.options.map((option) => option.count),
        colors: response.colors,
    } : null;
    const resultskey = JSON.stringify(results);

    useEffect(() => {
        if (!results || !canvas.current) {
            return;
        }
        const config = getChartConfig(results, label);
        const current = chart.current;
        if (current && current.canvas === canvas.current && current.config.type === config.type) {
            current.data = config.data;
            current.options = config.options;
            current.update('none');
            return;
        }
        current?.destroy();
        chart.current = new Chart(canvas.current, config);
    }, [resultskey, label]);

    useEffect(() => () => chart.current?.destroy(), []);

    if (!response) {
        return <Loading />;
    }

    if (!results) {
        return (
            <div className="mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align">
                {s.no_answer_due_to_showteacherpermission}
            </div>
        );
    }

    return (
        <div className="mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align"
            id="mtmt_quiz_results_wrapper">
            <div className="position-relative w-100 h-100">
                <canvas ref={canvas} role="img" aria-label={`${s.show_results}: ${label}`}>
                    <ul>
                        {results.labels.map((text, index) => <li key={index}>{text}: {results.values[index]}</li>)}
                    </ul>
                </canvas>
            </div>
        </div>
    );
}
