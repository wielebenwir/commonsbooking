//will open all meta boxes by default
//see #1932
//this is an issue since WP 6.9
const CB_POST_TYPES_WITH_METABOXES = [ 'cb_location', 'cb_item', 'cb_timeframe', 'cb_booking', 'cb_restriction', 'cb_map' ];
(function waitForWP() {
    if (window.wp && wp.data && wp.data.select && wp.data.subscribe) {
        init();
        return;
    }
    setTimeout(waitForWP, 50);
})();

function init() {
    const unsubscribe = wp.data.subscribe(() => {
        const editor = wp.data.select('core/editor');
        if (!editor || typeof editor.getCurrentPostType !== 'function') return;

        const postType = editor.getCurrentPostType();
        if (!postType) return;

        unsubscribe(); //prevent this code from running again

        if ( !CB_POST_TYPES_WITH_METABOXES.includes( postType ) ) {
            return;
        }

        const prefs = wp.data.select('core/preferences');

        // get function only available on newer wp
        if (prefs && typeof prefs.get === 'function') {
            const isOpen = prefs.get('core/edit-post', 'metaBoxesMainIsOpen');

            if (undefined === isOpen) {
                wp.data.dispatch('core/preferences')
                    .set('core/edit-post', 'metaBoxesMainIsOpen', true);
            }
        }
    });
}
