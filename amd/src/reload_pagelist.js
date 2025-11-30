import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import SortableList from 'core/sortable_list';
import jQuery from 'jquery';
import {ajaxRequestInput} from 'mod_mootimeter/utils';

export const init = (pagerefreshintervall) => {
    var obj = document.getElementById('mootimeterstate');

    if (!obj) {
        return;
    }

    if (pagerefreshintervall < 500) {
        pagerefreshintervall = 500;
    }

    setInterval(() => {
        getPagelist();
    }, pagerefreshintervall);

    /**
     * Store the value.
     */
    function getPagelist() {
        var pageid = 0;

        if (document.getElementById('mootimeterstate').dataset.pageid) {
            pageid = document.getElementById('mootimeterstate').dataset.pageid;
            if (pageid == "undefined" || pageid.length == 0) {
                pageid = 0;
            }
        }

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPagelist(pageid, cmid);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {int} cmid
 * @param {string} dataset
 * @returns {array}
 */
const reloadPagelist = (
    pageid,
    cmid,
    dataset
) => fetchMany([{
    methodname: 'mod_mootimeter_get_pages_list',
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
 * @param {bool} forcereload
 */
export const execReloadPagelist = async(pageid, cmid, forcereload = false) => {
    var mtmstate = document.getElementById('mootimeterstate');
    var dataset = mtmstate.dataset;

    // Early exit if page reload is locked (e.g., during page transitions).
    if (mtmstate.dataset.lockpagereload && !forcereload) {
        return;
    }

    // Early exit if there were no changes.
    if (
        (
            mtmstate.dataset.pagelistchangedat_prev
            && mtmstate.dataset.pagelistchangedat == mtmstate.dataset.pagelistchangedat_prev
        )
        && !forcereload
    ) {
        return;
    }

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);

    if (urlParams.get('r')) {
        dataset.r = urlParams.get('r');
    }

    if (urlParams.get('o')) {
        dataset.o = urlParams.get('o');
    }

    dataset.useUrlParams = 1;

    const response = await reloadPagelist(pageid, cmid, JSON.stringify(dataset));

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        const pagelist = JSON.parse(response.pagelist);

        // Replace the pages list.
        Templates.renderForPromise('mod_mootimeter/elements/snippet_page_list', pagelist)
            .then(({html, js}) => {
                Templates.replaceNodeContents(document.getElementById('mootimeter-pages-list'), html, js);

                // Finally make pageslist sortable.
                var listelements = document.getElementsByClassName('mootimeter_pages_li');
                if (listelements[0]) {
                    var uniqid = listelements[0].dataset.uniqid;
                    new SortableList('#mootimeter-pages-list', {
                        moveHandlerSelector: '.mootimeter_page_move_sortablehandle_' + uniqid,
                    });
                    jQuery('.mootimeter_pages_li_sortable_' + uniqid).on(SortableList.EVENTS.DROP, async function(_, info) {
                        var newIndex = info.targetList.children().index(info.element);
                        await storePagePosition(this.dataset.pageid, newIndex);
                    });
                }

                // Remove all tooltips of pageslist that are still present.
                document.querySelectorAll('.tooltip').forEach(e => e.remove());

                return true;
            })
            .catch((error) => displayException(error));

        // Set new pagelistchangedat_prev state.
        mtmstate.setAttribute('data-pagelistchangedat_prev', mtmstate.dataset.pagelistchangedat);

        // Remove all tooltips of pageslist that are still present.
        document.querySelectorAll('.tooltip').forEach(e => e.remove());
    }
};

/**
 * Store the new page position.
 * @param {int} pageid
 * @param {int} newIndex
 */
const storePagePosition = (pageid, newIndex) => {
    ajaxRequestInput(
        'mod_mootimeter_store_page_details',
        pageid,
        'sortorder',
        newIndex,
        ''
    );
};
