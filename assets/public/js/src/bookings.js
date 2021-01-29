var Shuffle = window.Shuffle;

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

        this.startDate = document.querySelector('.filter-startdate input');
        jQuery('#startDate-datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            altFormat: "@",
            altField: "#startDate"
        });

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
            endDate: []
        }

        this.shuffle = new Shuffle(element);

        this.resetListParams();
        // this.listParams.append("posts_per_page", 100);
        // listParams.append("order", params.data.order);
        // listParams.append("search", params.data.search);
        // listParams.append("sort", params.data.sort);

        this.reloadData();
        // this.addSorting();
        // this.addSearchFilter();
        this._bindEventListeners();
    }

    resetListParams() {
        this.listParams = new FormData();
        this.listParams.append("_ajax_nonce", cb_ajax_bookings.nonce);
        this.listParams.append("action", "bookings_data");
        this.listParams.append("page", 1);
    };

    _bindEventListeners() {
        this._onUserChange = this._handleUserChange.bind(this);
        this._onItemChange = this._handleItemChange.bind(this);
        this._onLocationChange = this._handleLocationChange.bind(this);
        this._onStartDateChange = this._handleStartDateChange.bind(this);
        this._onEndDateChange = this._handleEndDateChange.bind(this);

        var userSelect = document.querySelectorAll('.filter-users select');
        userSelect.item(0).addEventListener('change', this._onUserChange);

        var itemSelect = document.querySelectorAll('.filter-items select');
        itemSelect.item(0).addEventListener('change', this._onItemChange);

        var locationSelect = document.querySelectorAll('.filter-locations select');
        locationSelect.item(0).addEventListener('change', this._onLocationChange);

        jQuery('#startDate-datepicker').datepicker("option", "onSelect", this._onStartDateChange);
        jQuery('#startDate-datepicker').change(this._onStartDateChange);


        jQuery('#endDate-datepicker').datepicker("option", "onSelect", this._onEndDateChange);
        jQuery('#endDate-datepicker').change(this._onEndDateChange);
    };

    _handleStartDateChange() {
        this.filters.startDate = [];

        if(jQuery('#startDate-datepicker').datepicker( "getDate" )) {
            const timezoneOffsetGermany = 3600;
            let startDate = parseInt(document.querySelector('#startDate').value.slice(0,-3)) + timezoneOffsetGermany;
            this.filters.startDate = [startDate + ''];
        }

        this.filter();
    };

    _handleEndDateChange() {
        this.filters.endDate = [];

        if(jQuery('#endDate-datepicker').datepicker( "getDate" )) {
            const timezoneOffsetGermany = 3600;
            let endDate = parseInt(document.querySelector('#endDate').value.slice(0,-3)) + timezoneOffsetGermany;
            this.filters.endDate = [endDate + ''];
        }

        this.filter();
    };

    _handleUserChange() {
        this.filters.users = this._getCurrentUserFilters();
        if (this.filters.users[0] == 'all') {
            this.filters.users = [];
        }
        this.filter();
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

        this.filter();
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
        this.filter();
    };

    _getCurrentLocationFilters() {
        return this.locations.filter(function (input) {
            return input.selected;
        }).map(function (input) {
            return input.value;
        });
    };

    reloadData() {
        this._renderPagination = this._handleRenderPagination.bind(this);
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
                markup += '<li data-page="' + i + '"' + active + '>' + i + '</li>';
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
            page.addEventListener('click', self._onPageChange);
        });
    }

    _handlePageChange(evt) {
        var page = evt.currentTarget.dataset.page;
        this.listParams.set('page', page);
        this.reloadData();
    }

    /**
     * Filter shuffle based on the current state of filters.
     */
    filter() {
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

            this.reloadData();
            this.shuffle.filter(this.itemPassesFilters.bind(this));
        } else {
            this.resetListParams();
            this.reloadData();
            this.shuffle.filter(Shuffle.ALL_ITEMS);
        }
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
        var user = element.getAttribute('data-user');
        var item = element.getAttribute('data-item');
        var location = element.getAttribute('data-location');

        if (users.length > 0 && !users.includes(user)) {
            return false;
        }

        if (items.length > 0 && !items.includes(item)) {
            return false;
        }

        if (locations.length > 0 && !locations.includes(location)) {
            return false;
        }

        return true;
    };

    // Add click listener to button to load the next page.
    addPagination() {
        if (this.loadMoreButton) {
            const onClick = this._fetchNextPage.bind(this);
            this.loadMoreButton.addEventListener('click', onClick);
        }
    }

    _fetchNextPage() {
        this.currentPage += 1;
        this.listParams.set('page', this.currentPage);

        var self = this;

        fetch(cb_ajax_bookings.ajax_url, {
            method: 'POST',
            body: this.listParams
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                // Create and insert the markup.
                var markup = self._getItemMarkup(response.data);
                self._appendMarkupToGrid(markup);

                // Save the total number of new items returned from the API.
                var itemsFromResponse = response.data.length;
                // Get an array of elements that were just added to the grid above.
                var allItemsInGrid = Array.from(self.element.children);
                // Use negative beginning index to extract items from the end of the array.
                var newItems = allItemsInGrid.slice(-itemsFromResponse);

                // Notify the shuffle instance that new items were added.
                self.shuffle.add(newItems);
            });
    }

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
        var headline = document.createElement('h4');
        headline.classList.add('cb-title');
        headline.classList.add('cb-item-title');

        var link = document.createElement('a');
        link.href = item.calendarLink;
        link.text = item.item + ' @ ' + item.location;
        link.target = '_blank';

        headline.append(link)
        return headline;
    }

    _initContentElement(item) {
        var contentElement = document.createElement('p');
        contentElement.append(document.createTextNode(
            item.startDateFormatted + ' -> ' + item.endDateFormatted + ' / User: ' + item.user + ' / ' + item.status
        ));

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
        item.append(contentWrapperElement);
        item.append(this._initActionsElement(i));

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


    // addSorting() {
    //     const buttonGroup = document.querySelector('.sort-options');
    //     if (!buttonGroup) {
    //         return;
    //     }
    //     buttonGroup.addEventListener('change', this._handleSortChange.bind(this));
    // }
    //
    // _handleSortChange(evt) {
    //     // Add and remove `active` class from buttons.
    //     const buttons = Array.from(evt.currentTarget.children);
    //     buttons.forEach((button) => {
    //         if (button.querySelector('input').value === evt.target.value) {
    //             button.classList.add('active');
    //         } else {
    //             button.classList.remove('active');
    //         }
    //     });
    //
    //     // Create the sort options to give to Shuffle.
    //     const { value } = evt.target;
    //     let options = {};
    //
    //     function sortByDate(element) {
    //         return element.getAttribute('data-created');
    //     }
    //
    //     function sortByTitle(element) {
    //         return element.getAttribute('data-title').toLowerCase();
    //     }
    //
    //     if (value === 'date-created') {
    //         options = {
    //             reverse: true,
    //             by: sortByDate,
    //         };
    //     } else if (value === 'title') {
    //         options = {
    //             by: sortByTitle,
    //         };
    //     }
    //     this.shuffle.sort(options);
    // }
    //
    // // Advanced filtering
    // addSearchFilter() {
    //     const searchInput = document.querySelector('.js-shuffle-search');
    //     if (!searchInput) {
    //         return;
    //     }
    //     searchInput.addEventListener('keyup', this._handleSearchKeyup.bind(this));
    // }
    //
    // /**
    //  * Filter the shuffle instance by items with a title that matches the search input.
    //  * @param {Event} evt Event object.
    //  */
    // _handleSearchKeyup(evt) {
    //     const searchText = evt.target.value.toLowerCase();
    //     this.shuffle.filter((element, shuffle) => {
    //         // If there is a current filter applied, ignore elements that don't match it.
    //         if (shuffle.group !== Shuffle.ALL_ITEMS) {
    //             // Get the item's groups.
    //             const groups = JSON.parse(element.getAttribute('data-groups'));
    //             const isElementInCurrentGroup = groups.indexOf(shuffle.group) !== -1;
    //             // Only search elements in the current group
    //             if (!isElementInCurrentGroup) {
    //                 return false;
    //             }
    //         }
    //         const titleElement = element.querySelector('.picture-item__title');
    //         const titleText = titleElement.textContent.toLowerCase().trim();
    //         return titleText.indexOf(searchText) !== -1;
    //     });
    // }
}

document.addEventListener('DOMContentLoaded', () => {
    window.demo = new BookingList(document.getElementById('booking-list--results'));
});
