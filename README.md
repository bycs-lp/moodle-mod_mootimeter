# Mootimeter #

Mootimeter is a stand-alone activity plugin for moodle that enables live polls.

Mootimeter currently has the following polling tools:

* Quiz
* Poll
* Wordcloud

Thanks to its submodule structure, Mootimeter can easily be extended with additional polling tools.
An extensive API is available for the creation of further polling tools.

## Requirements ##

Moodle 5.2 or later. The user interface is built with React on top of the Moodle ESM/React integration.

## Frontend development ##

The React sources live in `js/esm/src` of the plugin and of each tool (`tools/<tool>/js/esm/src`).
Build them from the Moodle root directory and commit the generated `js/esm/build` files:

    $ npx grunt react

A tool provides its user interface by default exporting React components from the modules
`content`, `settings`, `results` and `overview` (and optionally a `menu`), see
`\mod_mootimeter\toolhelper::get_react_components()`. The props of the components are described in
`js/esm/src/lib/types.ts` (`ToolViewProps`). Shared components, hooks and the bridge to the Moodle
AMD modules can be imported from `@moodle/lms/mod_mootimeter/lib/...`.

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


## Changes to styles.scss and files in scss/* ##

If you want to change styles, you need to compile the scss files into css after you made your changes,
so they will be picked up. For doing so, just run `npm install` once after cloning the repository.

Whenever you have applied changes to `styles.scss` or any file in `scss/` directory, you need
to run `npm run compile-sass` in the plugin root directory. This creates the `styles.css`
file which is being read and used by moodle.

### Compiling SCSS with VS Code

For SCSS compilation in this Moodle plugin, the use of [VS Code](https://code.visualstudio.com/) and the extension [Live Sass Compiler](https://marketplace.visualstudio.com/items?itemName=ritwickdey.live-sass) is recommended.

The extension can recognize SCSS files and compile them as required. Certain settings can be made for this. These are described in more detail on the plugin page. The following is a standard configuration which is compatible with Mootimeter:

```php
"liveSassCompile.settings.includeItems": [
        "/**/mootimeter/styles.s[ac]ss",
],
"liveSassCompile.settings.generateMap": false,
"liveSassCompile.settings.partialsList": [
    "/**/mootimeter/scss/**/*.s[ac]ss",
],
```

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
