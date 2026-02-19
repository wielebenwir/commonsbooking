class BookingList {
    constructor(element) {
        this.currentPage = 1;
        this.totalPages = 0;
        this.loadMoreButton = document.getElementById('load-more-button');
        this.pagination = document.getElementById('booking-list--pagination');
        this.element = element;
        this.users = Array.from(document.querySelectorAll('.filter-users option'));
        this.items = Array.from(document.querySelectorAll('.filter-items option'));
        this.locations = Array.from(document.querySelectorAll('.filter-locations option'));
        this.states = Array.from(document.querySelectorAll('.filter-statuss option'));

        this.startDate = document.querySelector('.filter-startdate input');
        jQuery('#startDate-datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            altFormat: "@",
            altField: "#startDate"
        });
        jQuery('#startDate-datepicker').datepicker("setDate", new Date());

        this.endDate = document.querySelector('.filter-enddate input');
        jQuery('#endDate-datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            altFormat: "@",
            altField: "#endDate"
        });

        this.filters = {
            users: [],
            items: [],
            locations: [],
            startDate: [],
            endDate: [],
            states: []
        }

        this.shuffle = new Shuffle(element);

        this._resetListParams();
        this._addSorting();
        this._reloadData();
        this._bindEventListeners();
    }

    /**
     * Resets list params for ajax request.
     * @private
     */
    _resetListParams() {
        this.listParams = new FormData();
        this.listParams.append("_ajax_nonce", cb_ajax_bookings.nonce);
        this.listParams.append("action", "cb_bookings_data");
        this.listParams.append("page", 1);
    };

    _bindEventListeners() {
        this._onFilterReset = this._handleFilterReset.bind(this);
        const $filterReset = jQuery('#reset-filters');
        if($filterReset) $filterReset.on('click', this._onFilterReset);

        this._onFilter = this.filter.bind(this);
        const $filter = jQuery('#filter');
        if($filter) $filter.on('click', this._onFilter);

        this._onUserChange = this._handleUserChange.bind(this);
        const userSelect = document.querySelectorAll('.filter-users select');
        if(userSelect) userSelect.item(0).addEventListener('change', this._onUserChange);

        this._onItemChange = this._handleItemChange.bind(this);
        const itemSelect = document.querySelectorAll('.filter-items select');
        if(itemSelect) itemSelect.item(0).addEventListener('change', this._onItemChange);

        this._onLocationChange = this._handleLocationChange.bind(this);
        const locationSelect = document.querySelectorAll('.filter-locations select');
        if(locationSelect) locationSelect.item(0).addEventListener('change', this._onLocationChange);

        this._onStatusChange = this._handleStatusChange.bind(this);
        const statusSelect = document.querySelectorAll('.filter-statuss select');
        if(statusSelect) statusSelect.item(0).addEventListener('change', this._onStatusChange);

        this._onStartDateChange = this._handleStartDateChange.bind(this);
        const $startDatePicker = jQuery('#startDate-datepicker');
        if($startDatePicker) {
            $startDatePicker.datepicker("option", "onSelect", this._onStartDateChange);
            $startDatePicker.change(this._onStartDateChange);
        }

        this._onEndDateChange = this._handleEndDateChange.bind(this);
        const $endDatePicker = jQuery('#endDate-datepicker');
        if($endDatePicker) {
            $endDatePicker.datepicker("option", "onSelect", this._onEndDateChange);
            $endDatePicker.change(this._onEndDateChange);
        }
        this._onMenuButton = this._handleMenuButton.bind(this);
        const $menuButton = jQuery('#cb-bookingdropbtn');
        if($menuButton) $menuButton.on('click', this._onMenuButton);
    };

    _handleStartDateChange() {
        this.filters.startDate = [];

        if(jQuery('#startDate-datepicker').datepicker( "getDate" )) {
            const timezoneOffsetGermany = 3600;
            let startDate = parseInt(document.querySelector('#startDate').value.slice(0,-3)) + timezoneOffsetGermany;
            this.filters.startDate = [startDate + ''];
        }
    };

    _handleEndDateChange() {
        this.filters.endDate = [];

        if(jQuery('#endDate-datepicker').datepicker( "getDate" )) {
            const timezoneOffsetGermany = 3600;
            let endDate = parseInt(document.querySelector('#endDate').value.slice(0,-3)) + timezoneOffsetGermany;
            this.filters.endDate = [endDate + ''];
        }
    };

    _handleUserChange() {
        this.filters.users = this._getCurrentUserFilters();
        if (this.filters.users[0] == 'all') {
            this.filters.users = [];
        }
    };

    _getCurrentUserFilters() {
        return this.users.filter(function (input) {
            return input.selected;
        }).map(function (input) {
            return input.value;
        });
    };

    _handleItemChange() {
        this.filters.items = this._getCurrentItemFilters();
        if (this.filters.items[0] == 'all') {
            this.filters.items = [];
        }
    };

    _getCurrentItemFilters() {
        return this.items.filter(
            function (input) {
                return input.selected;
            }).map(function (input) {
            return input.value;
        });
    };

    _handleLocationChange() {
        this.filters.locations = this._getCurrentLocationFilters();
        if (this.filters.locations[0] == 'all') {
            this.filters.locations = [];
        }
    };

    _getCurrentLocationFilters() {
        return this.locations.filter(function (input) {
            return input.selected;
        }).map(function (input) {
            return input.value;
        });
    };

    _handleStatusChange() {
        this.filters.states = this._getCurrentStatusFilters();
        if (this.filters.states[0] == 'all') {
            this.filters.states = [];
        }
    };

    _getCurrentStatusFilters() {
        return this.states.filter(function (input) {
            return input.selected;
        }).map(function (input) {
            return input.value;
        });
    };

    /**
     * Resets all Filters
     * @private
     */
    _handleFilterReset() {
        if(typeof this.filters !== "undefined") {
            for (const [filter] of Object.entries(this.filters)) {

                let select  = document.getElementById('filter-' + filter.substring(0,filter.length - 1));
                if(select && typeof select != "undefined") {
                    // Remove all option, but all
                    var length = select .options.length;
                    for (var i = length-1; i >= 0; i--) {

                        const optionValue = select.options[i].value;
                        select.options[i].style.display = 'inline';
                        select.options[i].selected = false;
                        if(optionValue == 'all') {
                            select.options[i].selected = true;
                        }
                    }
                } else {
                    console.log('filter-' + filter.substring(0,filter.length - 1));
                }

                this.startDate.value = "";
                this.endDate.value = "";

                this.filters[filter] = [];
            }
            this.filter();
        }
    }

    /**
     * Hides options which aren't available based on selected filters and adds new options when new items / locations / users are part of the list
     * @param response
     * @private
     */
    _handleFilterUpdate(response) {
        if(typeof response.filters !== "undefined") {
            for (const [filter, values] of Object.entries(response.filters)) {

                let select  = document.getElementById('filter-' + filter);

                // Remove all options, except for "all"
                var length = select .options.length;
                for (var i = length-1; i >= 0; i--) {

                    const optionValue = select.options[i].value;

                    if(optionValue !== 'all' && !values.includes(optionValue) ) {
                        select.options[i].style.display = 'none';
                    } else {
                        select.options[i].style.display = 'inline';
                    }

                }
                // iterate over object_entries to add missing options
                for (let value of values) {
                    let found = false;
                    for (var i = length-1; i >= 0; i--) {
                        const optionValue = select.options[i].value;
                        if(optionValue === value) {
                            found = true;
                        }
                    }
                    if(!found) {
                        let option = document.createElement("option");
                        option.text = value;
                        option.value = value;
                        select.add(option);
                    }
                }

                // re-load variables for filters
                this.users = Array.from(document.querySelectorAll('.filter-users option'));
                this.items = Array.from(document.querySelectorAll('.filter-items option'));
                this.locations = Array.from(document.querySelectorAll('.filter-locations option'));
                this.states = Array.from(document.querySelectorAll('.filter-statuss option'));
            }
        }
    }

    /**
     * Reloads list data.
     * @private
     */
    _reloadData() {
        this._renderPagination = this._handleRenderPagination.bind(this);
        this._filterUpdate = this._handleFilterUpdate.bind(this);
        var self = this;

        fetch(cb_ajax_bookings.ajax_url, {
            method: 'POST',
            body: this.listParams
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                // Store the total number of pages so we know when to disable the "load more" button.
                self.totalPages = response.total_pages;

                // Check if there are any more pages to load.
                self._renderPagination(self.totalPages, response.page);

                self._filterUpdate(response);

                if (self.totalPages < 2 && typeof self.pagination !== 'undefined') {
                    self.pagination.style.display = 'none';
                }

                // Create and insert the markup.
                var markup = self._getItemMarkup(response.data);
                self._appendMarkupToGrid(markup);

                // Initialize Shuffle now that there are items.
                self.shuffle = new Shuffle(self.element, {
                    itemSelector: '.js-item',
                    sizer: '.my-sizer-element',
                });
            });
    };

    /**
     * Renders pagination list.
     * @param pages
     * @param currentPage
     * @private
     */
    _handleRenderPagination(pages, currentPage) {
        this.pagination.innerHTML = '';

        if (this.totalPages > 1) {
            let markup = '<ul>';

            for (let i = 1; i <= pages; i++) {
                let active = '';
                if (i == currentPage) {
                    active = ' class="active" ';
                }

                if(
                    i == 1 ||
                    i == pages ||
                    (
                        i < (parseInt(currentPage) + 3 ) &&
                        i > (parseInt(currentPage) - 3 )
                    )

                ) {
                    markup += '<li data-page="' + i + '"' + active + '>' + i + '</li>';
                }

                if(
                    i == (parseInt(currentPage) + 3 ) ||
                    i == (parseInt(currentPage) - 3 )

                ) {
                    markup += '<li >...</li>';
                }
            }
            markup += '</ul';
            this.pagination.insertAdjacentHTML('beforeend', markup);
            this.pagination.style.display = 'block';
            this._bindPaginationHandler();
        } else {
            this.pagination.style.display = 'none';
        }
    }

    _bindPaginationHandler() {
        this._onPageChange = this._handlePageChange.bind(this);

        var self = this;
        var pages = document.querySelectorAll('#booking-list--pagination ul li');

        pages.forEach(function (page) {
            if(page.dataset.page) {
                page.addEventListener('click', self._onPageChange);
            }
        });
    }

    _handlePageChange(evt) {
        var page = evt.currentTarget.dataset.page;
        this.listParams.set('page', page);
        this._reloadData();
    }

    _handleMenuButton(){
        jQuery('.cb-dropdown-content').toggle();
    }

    /**
     * Filter shuffle based on the current state of filters.
     */
    filter() {
        jQuery('#filter').addClass('loading');
        if (this.hasActiveFilters()) {

            if (this.filters.startDate.length) {
                this.listParams.set('startDate', this.filters.startDate);
            } else {
                this.listParams.delete('startDate');
            }

            if (this.filters.endDate.length) {
                this.listParams.set('endDate', this.filters.endDate);
            } else {
                this.listParams.delete('endDate');
            }

            if (this.filters.items.length) {
                this.listParams.set('item', this.filters.items[0]);
            } else {
                this.listParams.delete('item');
            }

            if (this.filters.users.length) {
                this.listParams.set('user', this.filters.users[0]);
            } else {
                this.listParams.delete('user');
            }

            if (this.filters.locations.length) {
                this.listParams.set('location', this.filters.locations[0]);
            } else {
                this.listParams.delete('location');
            }

            if (this.filters.states.length) {
                this.listParams.set('status', this.filters.states[0]);
            } else {
                this.listParams.delete('status');
            }

            this.shuffle.filter(this.itemPassesFilters.bind(this));
            this._reloadData();
        } else {
            this._resetListParams();
            this.shuffle.filter(Shuffle.ALL_ITEMS);
            this._reloadData();
        }
        jQuery('#filter').removeClass('loading');
    };

    /**
     * If any of the arrays in the `filters` property have a length of more than zero,
     * that means there is an active filter.
     * @return {boolean}
     */
    hasActiveFilters() {
        return Object.keys(this.filters).some(function (key) {
            return this.filters[key].length > 0;
        }, this);
    };

    /**
     * Determine whether an element passes the current filters.
     * @param {Element} element Element to test.
     * @return {boolean} Whether it satisfies all current filters.
     */
    itemPassesFilters(element) {
        var users = this.filters.users;
        var items = this.filters.items;
        var locations = this.filters.locations;
        var states = this.filters.states;
        var user = element.getAttribute('data-user');
        var item = element.getAttribute('data-item');
        var location = element.getAttribute('data-location');
        var status = element.getAttribute('data-status');

        if (users.length > 0 && !users.includes(user)) {
            return false;
        }

        if (items.length > 0 && !items.includes(item)) {
            return false;
        }

        if (locations.length > 0 && !locations.includes(location)) {
            return false;
        }

        if (states.length > 0 && !states.includes(status)) {
            return false;
        }

        return true;
    };

    _initItemElement(item) {
        var itemElement = document.createElement('div');
        itemElement.classList.add('js-item');
        itemElement.classList.add('cb-wrapper');
        itemElement.dataset.user = item.user;
        itemElement.dataset.item = item.item;
        itemElement.dataset.location = item.location;

        return itemElement;
    }

    _initHeadlineElement(item) {
        let headline = document.createElement('p');
        headline.classList.add('js-item--headline')

        let date = document.createElement('span');
        date.classList.add('cb-date')
        date.innerText = item.startDateFormatted + ' - ' + item.endDateFormatted;

        let title = document.createElement('span');
        title.classList.add('cb-title')
        title.innerText = item.item + ' @ ' + item.location;

        headline.append(date);
        headline.append(title);

        if(item.bookingCode) {
            let bookingCode = document.createElement('span');
            bookingCode.classList.add('cb-booking-code')
            bookingCode.innerText = item.bookingCode.label + ': ' + item.bookingCode.value;
            headline.append(bookingCode);
        }

        return headline;
    }

    _initContentElement(item) {
        var contentElement = document.createElement('div');
        contentElement.classList.add('js-item--infos');

        let html = '';
        for(const [key, contentItem] of Object.entries(item.content)) {
            html += '<span>' + contentItem.label + ': ' + contentItem.value + '</span>';
        }
        html += '';
        contentElement.innerHTML = html;
        return contentElement;

    }

    _initActionsElement(item) {
        var actionsElement = document.createElement('div');
        actionsElement.classList.add('js-item--action');
        actionsElement.classList.add('cb-action');
        actionsElement.insertAdjacentHTML('beforeend', item.actions);

        return actionsElement;
    }

    /**
     * Convert an object to HTML markup for an item.
     * @param {object} dataForSingleItem Data object.
     * @return {string}
     */
    _getMarkupFromData(dataForSingleItem) {
        var i = dataForSingleItem;
        var item = this._initItemElement(i);

        var contentWrapperElement = document.createElement('div');
        contentWrapperElement.classList.add('content-wrapper');

        contentWrapperElement.append(this._initHeadlineElement(i));
        contentWrapperElement.append(this._initContentElement(i));
        contentWrapperElement.append(this._initActionsElement(i));
        item.append(contentWrapperElement);

        return item.outerHTML;
    }

    /**
     * Convert an array of item objects to HTML markup.
     * @param {object[]} items Items array.
     * @return {string}
     */
    _getItemMarkup(items) {
        let self = this;
        if(items) {
            return items.reduce(function (str, item) {
                return str + self._getMarkupFromData(item);
            }, '');
        }
        return '';
    }

    /**
     * Append HTML markup to the main Shuffle element.
     * @param {string} markup A string of HTML.
     */
    _appendMarkupToGrid(markup) {
        this.element.innerHTML = '';
        this.element.insertAdjacentHTML('beforeend', markup);
    }

    _addSorting() {
        const sortSelect = document.getElementById('sorting');
        if (!sortSelect) {
            return;
        }
        sortSelect.addEventListener('change', this._handleSortChange.bind(this));

        const orderSelect = document.getElementById('order');
        if (!orderSelect) {
            return;
        }
        orderSelect.addEventListener('change', this._handleSortChange.bind(this));
    }

    _handleSortChange() {
        const sortSelect = document.getElementById('sorting');
        const sortSelectedOption = sortSelect.options[sortSelect.selectedIndex].value;

        const orderSelect = document.getElementById('order');
        const orderSelectedOption = orderSelect.options[orderSelect.selectedIndex].value;

        this.listParams.set('sort', sortSelectedOption);
        this.listParams.set('order', orderSelectedOption);
        this._reloadData();
    }

}

document.addEventListener('DOMContentLoaded', () => {
    var bookingList = document.getElementById('booking-list--results');
    if(bookingList) {
        window.demo = new BookingList(bookingList);
    }

    // Write comment text to button forms
    var commentField = jQuery('#cb-booking-comment');
    commentField.keyup(function () {
        jQuery('input[type=hidden][name=comment]').val(this.value);
    });
});
