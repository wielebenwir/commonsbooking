function ajaxRequest(params) {
    jQuery.post(
        cb_ajax_bookings.ajax_url,
        {
            _ajax_nonce: cb_ajax_bookings.nonce,
            action: "bookings_data",
            limit: params.data.limit,
            offset: params.data.offset,
            order: params.data.order,
            search: params.data.search,
            sort: params.data.sort,
        },
        function(data) {
            params.success(data)
        }
    );
}
