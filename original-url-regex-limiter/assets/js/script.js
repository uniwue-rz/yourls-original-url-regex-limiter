/**
 * This ajax call is triggered by clicking on the "toggle by domain" action button.
 * It dispatches the request, displays a result notice and swaps the icon on success.
 * 
 * @param {string} id 
 * @param {string} fail_msg 
 */
function uniwue_url_limiter_status_toggle_by_domain(id, fail_msg) {
    let toggle_button = $('#uniwue-url-limiter-toggle-by-domain-' + id);

    $.getJSON(
        toggle_button.attr('href'),
        function (data) {
            if ('status' in data && 'msg' in data) {
                feedback(data.msg, data.status);
                if (data.status === 'success') {
                    $('.uniwue-url-limiter-table-url-icon').each(function(idx, icon){
                        let element_id = $(icon).attr('id');
                        let id = element_id.substring(element_id.lastIndexOf("-") + 1);
                        uniwue_url_limiter_status_update(id);
                    });
                }
            } else {
                feedback(fail_msg, 'fail');
            }
        }
    );
}

/**
 * Updates the icon of the status with the given id.
 * 
 * @param {string} id 
 */
function uniwue_url_limiter_status_update(id) {
    let toggle_button = $('#uniwue-url-limiter-toggle-by-domain-' + id);
    let check_icon = $('#uniwue-url-limiter-icon-check-' + id);
    let cross_icon = $('#uniwue-url-limiter-icon-cross-' + id);

    $.getJSON(
        toggle_button.attr('href').replace('toggle-by-domain', 'is-allowed'),
        function (data) {
            if ('allowed' in data) {
                check_icon.css('display', data['allowed'] ? 'inline-block' : 'none');
                cross_icon.css('display', data['allowed'] ? 'none' : 'inline-block');
            }
        }
    );
}

/**
 * Moves the main table's URL Limiter status filter, which is pre-rendered hidden
 * at the end of the index page, behind the core filters and displays it.
 */
function uniwue_url_limiter_show_status_filter() {
    let uniwue_url_limiter_status = $('#uniwue-url-limiter-table-control-limiter-status');

    if (uniwue_url_limiter_status.length) {
        uniwue_url_limiter_status.insertBefore('#filter_buttons');
        $('<br/>').insertBefore(uniwue_url_limiter_status);
        uniwue_url_limiter_status.css('display', 'inline-block');
    }
}

/**
 * Register link edit callback.
 */
function uniwue_url_limiter_register_edit_mutation() {
    $('td[id^="url-"]').each(function() {
        new MutationObserver(uniwue_url_limiter_link_update).observe(
            $(this).get(0),
            {
                childList: true
            }
        );
    });
}
/**
 * Execute url limiter status update after link is modified.
 * 
 * @param {array} mutations 
 * @param {MutationObserver} observer 
 */
function uniwue_url_limiter_link_update(mutations, observer) {
    for (const mutation of mutations) {
        let id = mutation.target.id.split("-").pop();
        uniwue_url_limiter_status_update(id);
    }
}

// This function runs after page load.
(function () {
    uniwue_url_limiter_register_edit_mutation();
    uniwue_url_limiter_show_status_filter();
})();