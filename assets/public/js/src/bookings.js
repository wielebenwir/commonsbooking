
var Shuffle = window.Shuffle;

class Demo {
    constructor(element) {
        this.currentPage = 1;
        this.totalPages = 0;
        this.loadMoreButton = document.getElementById('load-more-button');
        this.shuffleInstance;

        this.element = element;
        // this.shuffle = new Shuffle(element/*, {
        //     itemSelector: '.picture-item',
        //     sizer: element.querySelector('.my-sizer-element'),
        // }*/);

        let listParams = new FormData();

        listParams.append("_ajax_nonce", cb_ajax_bookings.nonce);
        listParams.append("action", "bookings_data");
        listParams.append("page", this.currentPage);
        // listParams.append("limit", params.data.limit);
        // listParams.append("offset", params.data.offset);
        // listParams.append("order", params.data.order);
        // listParams.append("search", params.data.search);
        // listParams.append("sort", params.data.sort);

        var self = this;

        fetch( cb_ajax_bookings.ajax_url, {
            method: 'POST',
            body: listParams
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {

                // Store the total number of pages so we know when to disable the "load more" button.
                self.totalPages = response.total_pages;

                // Check if there are any more pages to load.
                if (self.currentPage === self.totalPages) {
                    self._replaceLoadMoreButton();
                }

                // Create and insert the markup.
                var markup = self._getItemMarkup(response.data);
                self._appendMarkupToGrid(markup);

                // Add click listener to button to load the next page.
                if(self._loadMoreButton) {
                    self._loadMoreButton.addEventListener('click', self._fetchNextPage);
                }
            });

        // Initialize Shuffle now that there are items.
        this.shuffle = new Shuffle(this.element, {
            itemSelector: '.js-item',
            sizer: '.my-sizer-element',
        });

        // Log events.
        this.addShuffleEventListeners();
        this._activeFilters = [];
        this.addFilterButtons();

        // this.addSorting();
        // this.addSearchFilter();

    }

    /**
     * Shuffle uses the CustomEvent constructor to dispatch events. You can listen
     * for them like you normally would (with jQuery for example).
     */
    addShuffleEventListeners() {
        this.shuffle.on(Shuffle.EventType.LAYOUT, (data) => {
            console.log('layout. data:', data);
        });
        this.shuffle.on(Shuffle.EventType.REMOVED, (data) => {
            console.log('removed. data:', data);
        });
    }

    addFilterButtons() {
        const options = document.querySelector('.filter-options');
        if (!options) {
            return;
        }

        const filterButtons = Array.from(options.children);
        const onClick = this._handleFilterClick.bind(this);
        filterButtons.forEach((button) => {
            button.addEventListener('click', onClick, false);
        });
    }

    _handleFilterClick(evt) {
        const btn = evt.currentTarget;
        const isActive = btn.classList.contains('active');
        const btnGroup = btn.getAttribute('data-group');

        this._removeActiveClassFromChildren(btn.parentNode);

        let filterGroup;
        if (isActive) {
            btn.classList.remove('active');
            filterGroup = Shuffle.ALL_ITEMS;
        } else {
            btn.classList.add('active');
            filterGroup = btnGroup;
        }

        this.shuffle.filter(filterGroup);
    }

    _removeActiveClassFromChildren(parent) {
        const { children } = parent;
        for (let i = children.length - 1; i >= 0; i--) {
            children[i].classList.remove('active');
        }
    }

    _fetchNextPage() {
        this.currentPage += 1;
        listParams.set('page', currentPage);

        fetch(cb_ajax_bookings.ajax_url, {
            method: 'POST',
            body: listParams
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                // Create and insert the markup.
                var markup = getItemMarkup(response.data);
                appendMarkupToGrid(markup);

                // Check if there are any more pages to load.
                if (currentPage === totalPages) {
                    replaceLoadMoreButton();
                }

                // Save the total number of new items returned from the API.
                var itemsFromResponse = response.data.length;
                // Get an array of elements that were just added to the grid above.
                var allItemsInGrid = Array.from(gridContainerElement.children);
                // Use negative beginning index to extract items from the end of the array.
                var newItems = allItemsInGrid.slice(-itemsFromResponse);

                // Notify the shuffle instance that new items were added.
                shuffleInstance.add(newItems);
            });
    }

    /**
     * Convert an object to HTML markup for an item.
     * @param {object} dataForSingleItem Data object.
     * @return {string}
     */
    _getMarkupFromData(dataForSingleItem) {
        var i = dataForSingleItem;
        var name = i.bookingDate + ' - ' + i.item + ' ' + i.location;
        var randomColor = ('000000' + Math.random().toString(16).slice(2, 8)).slice(-6);
        return [
            '<div class="js-item col-3@xs col-3@sm person-item" data-id="' + name + '" data-groups=\'["nature"]\'>',
            '<div class="person-item__inner" style="background-color:#' + randomColor + '">',
            '<span>' + name + '</span>',
            '</div>',
            '</div>',
        ].join('');
    }

    /**
     * Convert an array of item objects to HTML markup.
     * @param {object[]} items Items array.
     * @return {string}
     */
    _getItemMarkup(items) {
        let self = this;
        return items.reduce(function (str, item) {
            return str + self._getMarkupFromData(item);
        }, '');
    }

    /**
     * Append HTML markup to the main Shuffle element.
     * @param {string} markup A string of HTML.
     */
    _appendMarkupToGrid(markup) {
        this.element.insertAdjacentHTML('beforeend', markup);
    }

    /**
     * Remove the load more button so that the user cannot click it again.
     */
    _replaceLoadMoreButton() {
        if( this.loadMoreButton) {
            var text = document.createTextNode('All users loaded');
            var replacement = document.createElement('p');
            replacement.appendChild(text);
            this.loadMoreButton.parentNode.replaceChild(replacement, loadMoreButton);
        }
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
    window.demo = new Demo(document.getElementById('grid'));
});
