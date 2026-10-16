# Mootimeter #

Mootimeter is a stand-alone activity plugin for moodle that enables live polls.

Mootimeter currently has the following polling tools:

* Quiz
* Poll
* Wordcloud

Thanks to its submodule structure, Mootimeter can easily be extended with additional polling tools.
An extensive API is available for the creation of further polling tools.

## Requirements ##

Moodle 5.3 or later. The user interface is built with React and the components and design tokens of the
Moodle design system (`@moodlehq/design-system`).

## Upgrading ##

Sites running an earlier version of Mootimeter can upgrade directly, the database schema and all pages,
settings and answers are kept. The former web service functions (`mod_mootimeter_*` and
`mootimetertool_*` in `db/services.php`) have been replaced by the REST API described below.

## REST API ##

The user interface uses the REST API of the Moodle routing system. The routes are defined in
`classes/route/api` of the plugin and of each tool and are documented in the OpenAPI specification of the
site (`/api/rest/v2/openapi.json`, or `/r.php/api/rest/v2/openapi.json` if routing is not configured).
The resources of each component are located below `/api/rest/v2/<component>`:

* `mod_mootimeter`: `/tools`, `/instances/{cmId}/pages`, `/instances/{cmId}/updates`, `/pages/{pageId}`,
  `/pages/{pageId}/answers`
* `mootimetertool_quiz` and `mootimetertool_poll`: `/pages/{pageId}`, `/pages/{pageId}/results`,
  `/pages/{pageId}/options[/{optionId}]`, `/pages/{pageId}/responses[/{responseId}]`
* `mootimetertool_wordcloud`: `/pages/{pageId}`, `/pages/{pageId}/results`, `/pages/{pageId}/answers[/{answerId}]`

Requests authenticated by the session cookie have to send the session key in the header
`X-Moodle-Sesskey` for changes. Collections support the query parameters `page`, `pagesize` and `sort`.
Errors are returned as problem details (RFC 9457, `application/problem+json`) with a stable `code`.

## Frontend development ##

The React sources live in `js/esm/src` of the plugin and of each tool (`tools/<tool>/js/esm/src`).
Build them from the Moodle root directory and commit the generated `js/esm/build` files:

    $ npx grunt esm

A tool provides its user interface by default exporting React components from the modules
`content`, `settings`, `results` and `overview` (and optionally a `menu`), see
`\mod_mootimeter\toolhelper::get_react_components()`. The props of the components are described in
`js/esm/src/lib/types.ts` (`ToolViewProps`). Shared components, hooks and the bridge to the Moodle
AMD modules can be imported from `@moodle/lms/mod_mootimeter/lib/...`.

The styles in `styles.css` only use the design tokens (`--mds-*`) and Bootstrap custom properties, so that
they work in the light and the dark colour mode.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/mootimeter

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2023, ISB Bayern

Lead developer: Dr. Peter Mayer <peter.mayer@isb.bayern.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
