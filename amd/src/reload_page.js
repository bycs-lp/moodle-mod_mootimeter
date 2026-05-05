import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {execReloadPagelist as reloadPagelist} from 'mod_mootimeter/reload_pagelist';
import {getGetParams} from 'mod_mootimeter/utils';
import {setGetParam} from 'mod_mootimeter/utils';
import {getMootimeterstate} from 'mod_mootimeter/get_mootimeterstate';

export const init = (uniqueID) => {

    const obj = document.getElementById(uniqueID);
    if (!obj) {
        return;
    }
    obj.addEventListener("click", changePage);

    /**
     * Store the value.
     */
    function changePage() {
        var pageid = 0;
        var dataset = "";
        if (this.dataset) {
            pageid = this.dataset.pageid;
            dataset = this.dataset;
        }

        if (pageid === null || pageid === undefined || pageid == "undefined" || pageid.length == 0) {
            pageid = 0;
        }

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPage(pageid, cmid, dataset);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {int} cmid
 * @param {string} dataset
 * @returns {array}
 */
const reloadPage = (
    pageid,
    cmid,
    dataset
) => fetchMany([{
    methodname: 'mod_mootimeter_get_pagecontentparams',
    args: {
        pageid,
        cmid,
        dataset
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {int} cmid
 * @param {array} dataset
 */
export const execReloadPage = async(pageid, cmid, dataset) => {

    // Check if the pagereload is locked.
    var mtmstate = document.getElementById('mootimeterstate');
    if (mtmstate.dataset.lockpagereload) {
        return;
    }

    // Lock page reload during transition to prevent race conditions.
    mtmstate.setAttribute('data-lockpagereload', '1');

    if (!dataset) {
        dataset = getGetParams();
    } else {
        Object.assign(dataset, getGetParams());
    }

    dataset = JSON.stringify(dataset);

    // Get the most recent timestamps.
    if (pageid != 0) {
        await getMootimeterstate();
    }

    mtmstate = document.getElementById('mootimeterstate');

    const response = await reloadPage(pageid, cmid, dataset);
    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        const pageparmas = JSON.parse(response.pageparams);

        // Replace the pagecontent.
        if (
            !mtmstate.dataset.contentchangedat_prev
            || mtmstate.dataset.contentchangedat_prev != mtmstate.contentchangedat
            || !mtmstate.dataset.teacherpermissiontoview_prev
            || mtmstate.dataset.teacherpermissiontoview_prev != mtmstate.dataset.teacherpermissiontoviewteacherpermissiontoview
        ) {
            reloadPageContent(pageparmas.pagecontent);

            // Set active page marked in pageslist.
            reloadPagelist(pageid, cmid, true);
        }

        // Replace the pagecontent menu.
        if (
            !mtmstate.dataset.pagecontentmenuchangedat_prev
            || mtmstate.dataset.pagecontentmenuchangedat_prev != mtmstate.settingschangedat
        ) {
            if (pageparmas.contentmenu) {
                reloadContentMenu(pageparmas.contentmenu);
            }
        }

        if (
            pageparmas.colsettings
            && (
                !mtmstate.dataset.settingschangedat_prev
                || mtmstate.dataset.settingschangedat_prev != mtmstate.settingschangedat
            )
        ) {
            reloadSettingsCol(pageparmas.colsettings);
        }

        if (pageparmas.pageid) {
            // Set new pageid.
            mtmstate.setAttribute('data-pageid', pageparmas.pageid);

            // Set URL parameter - pageid.
            setGetParam('pageid', pageparmas.pageid);
        }

        // Unlock page reload after transition is complete.
        mtmstate.setAttribute('data-lockpagereload', '');
    }
};

export const reloadSettingsCol = async(pageparmas) => {

    var mtmstate = document.getElementById('mootimeterstate');

    // Replace the settings col if necessary.
    Templates.renderForPromise(pageparmas.template, pageparmas)
        .then(({html, js}) => {
            Templates.replaceNodeContents('#mootimeter-col-settings', html, js);
            mtmstate.setAttribute('data-settingschangedat_prev', mtmstate.dataset.settingschangedat);
            return true;
        })
        .catch((error) => displayException(error));

};

export const reloadContentMenu = async(pageparmas) => {

    var mtmstate = document.getElementById('mootimeterstate');

    // Replace the settings col if necessary.
    Templates.renderForPromise(pageparmas.template, pageparmas)
        .then(({html, js}) => {
            Templates.replaceNode('#mootimeter-pagecontentmenu', html, js);
            mtmstate.setAttribute('data-pagecontentmenuchangedat_prev', mtmstate.dataset.pagecontentmenuchangedat);
            return true;
        })
        .catch((error) => displayException(error));

    // Set subpage URL parameters.
    if (pageparmas.sp) {
        for (const [key, value] of Object.entries(pageparmas.sp)) {
            setGetParam(key, value);
        }
    }
};

export const reloadPageContent = async(pageparmas) => {

    var mtmstate = document.getElementById('mootimeterstate');

    Templates.renderForPromise(pageparmas.template, pageparmas)
        .then(({html, js}) => {
            Templates.replaceNodeContents('#mootimeter-pagecontent', html, js);
            mtmstate.setAttribute('data-contentchangedat_prev', mtmstate.dataset.contentchangedat);
            mtmstate.setAttribute('data-teacherpermissiontoview_prev', mtmstate.dataset.teacherpermissiontoview);
            return true;
        })
        .catch((error) => displayException(error));
};
