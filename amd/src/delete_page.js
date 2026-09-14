import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {removeGetParam} from 'mod_mootimeter/utils';

export const init = async(id) => {
    const element = document.getElementById(id);
    if (!element) {
        return;
    }

    // Get pageid from the element's data attribute
    const pageid = element.dataset.pageid;

    const modal = await ModalDeleteCancel.create({
        title: getString('delete', 'core'),
        body: getString('areyousure'),
    });

    modal.getRoot().on(ModalEvents.delete, function() {
        execDeletePage(pageid);
    });

    element.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent triggering page selection
        modal.show();
    });

    /**
     * Call to store input value
     * @param {int} pageid
     * @returns {mixed}
     */
    const deletePageCall = (
        pageid,
    ) => fetchMany([{
        methodname: 'mod_mootimeter_delete_page',
        args: {
            pageid
        },
    }])[0];

    /**
     * Executes the call to store input value.
     * @param {int} pageid
     */
    const execDeletePage = async(pageid) => {
        var mtmstate = document.getElementById('mootimeterstate');
        const response = await deletePageCall(pageid);
        if (response.code != 200) {
            Log.error(response.string);
            return;
        }
        removeGetParam('pageid', window.location.href);
        mtmstate.setAttribute('data-pageid', 0);
        reloadPage(0, response.cmid);
    };
};
