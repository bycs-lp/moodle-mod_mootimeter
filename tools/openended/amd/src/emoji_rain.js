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
 * Spawns short-lived emoji "rain" particles over a bubble whenever a new
 * reaction arrives. Particles clean themselves up via animationend.
 *
 * @module     mootimetertool_openended/emoji_rain
 * @copyright  2026, ISB Bayern
 * @author     Benedikt Blumenfelder
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const MAX_PARTICLES_PER_EVENT = 5;
const STAGGER_MS = 60;

/**
 * Spawn `count` (capped) emoji particles inside the rain layer of `bubble`.
 *
 * @param {HTMLElement} bubble  The .mootimeter-oe-bubble element.
 * @param {string} symbol       The emoji glyph to rain.
 * @param {number} count        Desired number of particles. Capped at 5.
 */
export const spawn = (bubble, symbol, count) => {
    if (!bubble || !symbol || count <= 0) {
        return;
    }
    const layer = bubble.querySelector('.mootimeter-oe-rain-layer');
    if (!layer) {
        return;
    }
    const total = Math.min(count, MAX_PARTICLES_PER_EVENT);
    for (let i = 0; i < total; i++) {
        const particle = document.createElement('span');
        particle.className = 'mootimeter-oe-rain-particle';
        particle.textContent = symbol;
        // Spread horizontally and randomise drift / rotation so the particles
        // don't fall as a single column.
        const leftPct = 10 + Math.random() * 80;
        const drift = (Math.random() * 40 - 20).toFixed(1);
        const rot = (Math.random() * 60 - 30).toFixed(1);
        particle.style.left = leftPct + '%';
        particle.style.setProperty('--rain-drift', drift + 'px');
        particle.style.setProperty('--rain-rot', rot + 'deg');
        particle.style.animationDelay = (i * STAGGER_MS) + 'ms';
        particle.addEventListener('animationend', () => particle.remove(), {once: true});
        layer.appendChild(particle);
    }
};
