class BookingList {
    constructor(element) {
        this.currentPage = 1;
        this.totalPages = 0;
        this.loadMoreButton = document.getElementById("load-more-button");
        this.pagination = document.getElementById("booking-list--pagination");
        this.element = element;
        this.users = Array.from(document.querySelectorAll(".filter-users option"));
        this.items = Array.from(document.querySelectorAll(".filter-items option"));
        this.locations = Array.from(document.querySelectorAll(".filter-locations option"));
        this.states = Array.from(document.querySelectorAll(".filter-statuss option"));
        this.startDate = document.querySelector(".filter-startdate input");
        jQuery("#startDate-datepicker").datepicker({
            dateFormat: "yy-mm-dd",
            altFormat: "@",
            altField: "#startDate"
        });
        jQuery("#startDate-datepicker").datepicker("setDate", new Date());
        this.endDate = document.querySelector(".filter-enddate input");
        jQuery("#endDate-datepicker").datepicker({
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
        };
        this.shuffle = new Shuffle(element);
        this._resetListParams();
        this._addSorting();
        this._reloadData();
        this._bindEventListeners();
    }
    _resetListParams() {
        this.listParams = new FormData();
        this.listParams.append("_ajax_nonce", cb_ajax_bookings.nonce);
        this.listParams.append("action", "cb_bookings_data");
        this.listParams.append("page", 1);
    }
    _bindEventListeners() {
        this._onFilterReset = this._handleFilterReset.bind(this);
        const $filterReset = jQuery("#reset-filters");
        if ($filterReset) $filterReset.on("click", this._onFilterReset);
        this._onFilter = this.filter.bind(this);
        const $filter = jQuery("#filter");
        if ($filter) $filter.on("click", this._onFilter);
        this._onUserChange = this._handleUserChange.bind(this);
        const userSelect = document.querySelectorAll(".filter-users select");
        if (userSelect) userSelect.item(0).addEventListener("change", this._onUserChange);
        this._onItemChange = this._handleItemChange.bind(this);
        const itemSelect = document.querySelectorAll(".filter-items select");
        if (itemSelect) itemSelect.item(0).addEventListener("change", this._onItemChange);
        this._onLocationChange = this._handleLocationChange.bind(this);
        const locationSelect = document.querySelectorAll(".filter-locations select");
        if (locationSelect) locationSelect.item(0).addEventListener("change", this._onLocationChange);
        this._onStatusChange = this._handleStatusChange.bind(this);
        const statusSelect = document.querySelectorAll(".filter-statuss select");
        if (statusSelect) statusSelect.item(0).addEventListener("change", this._onStatusChange);
        this._onStartDateChange = this._handleStartDateChange.bind(this);
        const $startDatePicker = jQuery("#startDate-datepicker");
        if ($startDatePicker) {
            $startDatePicker.datepicker("option", "onSelect", this._onStartDateChange);
            $startDatePicker.change(this._onStartDateChange);
        }
        this._onEndDateChange = this._handleEndDateChange.bind(this);
        const $endDatePicker = jQuery("#endDate-datepicker");
        if ($endDatePicker) {
            $endDatePicker.datepicker("option", "onSelect", this._onEndDateChange);
            $endDatePicker.change(this._onEndDateChange);
        }
        this._onMenuButton = this._handleMenuButton.bind(this);
        const $menuButton = jQuery("#cb-bookingdropbtn");
        if ($menuButton) $menuButton.on("click", this._onMenuButton);
    }
    _handleStartDateChange() {
        this.filters.startDate = [];
        if (jQuery("#startDate-datepicker").datepicker("getDate")) {
            const timezoneOffsetGermany = 3600;
            let startDate = parseInt(document.querySelector("#startDate").value.slice(0, -3)) + timezoneOffsetGermany;
            this.filters.startDate = [ startDate + "" ];
        }
    }
    _handleEndDateChange() {
        this.filters.endDate = [];
        if (jQuery("#endDate-datepicker").datepicker("getDate")) {
            const timezoneOffsetGermany = 3600;
            let endDate = parseInt(document.querySelector("#endDate").value.slice(0, -3)) + timezoneOffsetGermany;
            this.filters.endDate = [ endDate + "" ];
        }
    }
    _handleUserChange() {
        this.filters.users = this._getCurrentUserFilters();
        if (this.filters.users[0] == "all") {
            this.filters.users = [];
        }
    }
    _getCurrentUserFilters() {
        return this.users.filter(function(input) {
            return input.selected;
        }).map(function(input) {
            return input.value;
        });
    }
    _handleItemChange() {
        this.filters.items = this._getCurrentItemFilters();
        if (this.filters.items[0] == "all") {
            this.filters.items = [];
        }
    }
    _getCurrentItemFilters() {
        return this.items.filter(function(input) {
            return input.selected;
        }).map(function(input) {
            return input.value;
        });
    }
    _handleLocationChange() {
        this.filters.locations = this._getCurrentLocationFilters();
        if (this.filters.locations[0] == "all") {
            this.filters.locations = [];
        }
    }
    _getCurrentLocationFilters() {
        return this.locations.filter(function(input) {
            return input.selected;
        }).map(function(input) {
            return input.value;
        });
    }
    _handleStatusChange() {
        this.filters.states = this._getCurrentStatusFilters();
        if (this.filters.states[0] == "all") {
            this.filters.states = [];
        }
    }
    _getCurrentStatusFilters() {
        return this.states.filter(function(input) {
            return input.selected;
        }).map(function(input) {
            return input.value;
        });
    }
    _handleFilterReset() {
        if (typeof this.filters !== "undefined") {
            for (const [ filter ] of Object.entries(this.filters)) {
                let select = document.getElementById("filter-" + filter.substring(0, filter.length - 1));
                if (select && typeof select != "undefined") {
                    var length = select.options.length;
                    for (var i = length - 1; i >= 0; i--) {
                        const optionValue = select.options[i].value;
                        select.options[i].style.display = "inline";
                        select.options[i].selected = false;
                        if (optionValue == "all") {
                            select.options[i].selected = true;
                        }
                    }
                } else {
                    console.log("filter-" + filter.substring(0, filter.length - 1));
                }
                this.startDate.value = "";
                this.endDate.value = "";
                this.filters[filter] = [];
            }
            this.filter();
        }
    }
    _handleFilterUpdate(response) {
        if (typeof response.filters !== "undefined") {
            for (const [ filter, values ] of Object.entries(response.filters)) {
                let select = document.getElementById("filter-" + filter);
                var length = select.options.length;
                for (var i = length - 1; i >= 0; i--) {
                    const optionValue = select.options[i].value;
                    if (optionValue !== "all" && !values.includes(optionValue)) {
                        select.options[i].style.display = "none";
                    } else {
                        select.options[i].style.display = "inline";
                    }
                }
                for (let value of values) {
                    let found = false;
                    for (var i = length - 1; i >= 0; i--) {
                        const optionValue = select.options[i].value;
                        if (optionValue === value) {
                            found = true;
                        }
                    }
                    if (!found) {
                        let option = document.createElement("option");
                        option.text = value;
                        option.value = value;
                        select.add(option);
                    }
                }
                this.users = Array.from(document.querySelectorAll(".filter-users option"));
                this.items = Array.from(document.querySelectorAll(".filter-items option"));
                this.locations = Array.from(document.querySelectorAll(".filter-locations option"));
                this.states = Array.from(document.querySelectorAll(".filter-statuss option"));
            }
        }
    }
    _reloadData() {
        this._renderPagination = this._handleRenderPagination.bind(this);
        this._filterUpdate = this._handleFilterUpdate.bind(this);
        var self = this;
        fetch(cb_ajax_bookings.ajax_url, {
            method: "POST",
            body: this.listParams
        }).then(function(response) {
            return response.json();
        }).then(function(response) {
            self.totalPages = response.total_pages;
            self._renderPagination(self.totalPages, response.page);
            self._filterUpdate(response);
            if (self.totalPages < 2 && typeof self.pagination !== "undefined") {
                self.pagination.style.display = "none";
            }
            var markup = self._getItemMarkup(response.data);
            self._appendMarkupToGrid(markup);
            self.shuffle = new Shuffle(self.element, {
                itemSelector: ".js-item",
                sizer: ".my-sizer-element"
            });
        });
    }
    _handleRenderPagination(pages, currentPage) {
        this.pagination.innerHTML = "";
        if (this.totalPages > 1) {
            let markup = "<ul>";
            for (let i = 1; i <= pages; i++) {
                let active = "";
                if (i == currentPage) {
                    active = ' class="active" ';
                }
                if (i == 1 || i == pages || i < parseInt(currentPage) + 3 && i > parseInt(currentPage) - 3) {
                    markup += '<li data-page="' + i + '"' + active + ">" + i + "</li>";
                }
                if (i == parseInt(currentPage) + 3 || i == parseInt(currentPage) - 3) {
                    markup += "<li >...</li>";
                }
            }
            markup += "</ul";
            this.pagination.insertAdjacentHTML("beforeend", markup);
            this.pagination.style.display = "block";
            this._bindPaginationHandler();
        } else {
            this.pagination.style.display = "none";
        }
    }
    _bindPaginationHandler() {
        this._onPageChange = this._handlePageChange.bind(this);
        var self = this;
        var pages = document.querySelectorAll("#booking-list--pagination ul li");
        pages.forEach(function(page) {
            if (page.dataset.page) {
                page.addEventListener("click", self._onPageChange);
            }
        });
    }
    _handlePageChange(evt) {
        var page = evt.currentTarget.dataset.page;
        this.listParams.set("page", page);
        this._reloadData();
    }
    _handleMenuButton() {
        jQuery(".cb-dropdown-content").toggle();
    }
    filter() {
        jQuery("#filter").addClass("loading");
        if (this.hasActiveFilters()) {
            if (this.filters.startDate.length) {
                this.listParams.set("startDate", this.filters.startDate);
            } else {
                this.listParams.delete("startDate");
            }
            if (this.filters.endDate.length) {
                this.listParams.set("endDate", this.filters.endDate);
            } else {
                this.listParams.delete("endDate");
            }
            if (this.filters.items.length) {
                this.listParams.set("item", this.filters.items[0]);
            } else {
                this.listParams.delete("item");
            }
            if (this.filters.users.length) {
                this.listParams.set("user", this.filters.users[0]);
            } else {
                this.listParams.delete("user");
            }
            if (this.filters.locations.length) {
                this.listParams.set("location", this.filters.locations[0]);
            } else {
                this.listParams.delete("location");
            }
            if (this.filters.states.length) {
                this.listParams.set("status", this.filters.states[0]);
            } else {
                this.listParams.delete("status");
            }
            this.shuffle.filter(this.itemPassesFilters.bind(this));
            this._reloadData();
        } else {
            this._resetListParams();
            this.shuffle.filter(Shuffle.ALL_ITEMS);
            this._reloadData();
        }
        jQuery("#filter").removeClass("loading");
    }
    hasActiveFilters() {
        return Object.keys(this.filters).some(function(key) {
            return this.filters[key].length > 0;
        }, this);
    }
    itemPassesFilters(element) {
        var users = this.filters.users;
        var items = this.filters.items;
        var locations = this.filters.locations;
        var states = this.filters.states;
        var user = element.getAttribute("data-user");
        var item = element.getAttribute("data-item");
        var location = element.getAttribute("data-location");
        var status = element.getAttribute("data-status");
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
    }
    _initItemElement(item) {
        var itemElement = document.createElement("div");
        itemElement.classList.add("js-item");
        itemElement.classList.add("cb-wrapper");
        itemElement.dataset.user = item.user;
        itemElement.dataset.item = item.item;
        itemElement.dataset.location = item.location;
        return itemElement;
    }
    _initHeadlineElement(item) {
        let headline = document.createElement("p");
        headline.classList.add("js-item--headline");
        let date = document.createElement("span");
        date.classList.add("cb-date");
        date.innerText = item.startDateFormatted + " - " + item.endDateFormatted;
        let title = document.createElement("span");
        title.classList.add("cb-title");
        title.innerText = item.item + " @ " + item.location;
        headline.append(date);
        headline.append(title);
        if (item.bookingCode) {
            let bookingCode = document.createElement("span");
            bookingCode.classList.add("cb-booking-code");
            bookingCode.innerText = item.bookingCode.label + ": " + item.bookingCode.value;
            headline.append(bookingCode);
        }
        return headline;
    }
    _initContentElement(item) {
        var contentElement = document.createElement("div");
        contentElement.classList.add("js-item--infos");
        let html = "";
        for (const [ key, contentItem ] of Object.entries(item.content)) {
            html += "<span>" + contentItem.label + ": " + contentItem.value + "</span>";
        }
        html += "";
        contentElement.innerHTML = html;
        return contentElement;
    }
    _initActionsElement(item) {
        var actionsElement = document.createElement("div");
        actionsElement.classList.add("js-item--action");
        actionsElement.classList.add("cb-action");
        actionsElement.insertAdjacentHTML("beforeend", item.actions);
        return actionsElement;
    }
    _getMarkupFromData(dataForSingleItem) {
        var i = dataForSingleItem;
        var item = this._initItemElement(i);
        var contentWrapperElement = document.createElement("div");
        contentWrapperElement.classList.add("content-wrapper");
        contentWrapperElement.append(this._initHeadlineElement(i));
        contentWrapperElement.append(this._initContentElement(i));
        contentWrapperElement.append(this._initActionsElement(i));
        item.append(contentWrapperElement);
        return item.outerHTML;
    }
    _getItemMarkup(items) {
        let self = this;
        if (items) {
            return items.reduce(function(str, item) {
                return str + self._getMarkupFromData(item);
            }, "");
        }
        return "";
    }
    _appendMarkupToGrid(markup) {
        this.element.innerHTML = "";
        this.element.insertAdjacentHTML("beforeend", markup);
    }
    _addSorting() {
        const sortSelect = document.getElementById("sorting");
        if (!sortSelect) {
            return;
        }
        sortSelect.addEventListener("change", this._handleSortChange.bind(this));
        const orderSelect = document.getElementById("order");
        if (!orderSelect) {
            return;
        }
        orderSelect.addEventListener("change", this._handleSortChange.bind(this));
    }
    _handleSortChange() {
        const sortSelect = document.getElementById("sorting");
        const sortSelectedOption = sortSelect.options[sortSelect.selectedIndex].value;
        const orderSelect = document.getElementById("order");
        const orderSelectedOption = orderSelect.options[orderSelect.selectedIndex].value;
        this.listParams.set("sort", sortSelectedOption);
        this.listParams.set("order", orderSelectedOption);
        this._reloadData();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    var bookingList = document.getElementById("booking-list--results");
    if (bookingList) {
        window.demo = new BookingList(bookingList);
    }
    var commentField = jQuery("#cb-booking-comment");
    commentField.keyup(function() {
        jQuery("input[type=hidden][name=comment]").val(this.value);
    });
});

!function(t, e) {
    "object" == typeof exports && "object" == typeof module ? module.exports = e() : "function" == typeof define && define.amd ? define("Litepicker", [], e) : "object" == typeof exports ? exports.Litepicker = e() : t.Litepicker = e();
}(self, function() {
    return function() {
        var t = {
            645: function(t) {
                "use strict";
                t.exports = function(t) {
                    var e = [];
                    return e.toString = function() {
                        return this.map(function(e) {
                            var i = function(t, e) {
                                var i, o, n, s = t[1] || "", a = t[3];
                                if (!a) return s;
                                if (e && "function" == typeof btoa) {
                                    var r = (i = a, o = btoa(unescape(encodeURIComponent(JSON.stringify(i)))), 
                                    n = "sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(o), 
                                    "/*# ".concat(n, " */")), l = a.sources.map(function(t) {
                                        return "/*# sourceURL=".concat(a.sourceRoot || "").concat(t, " */");
                                    });
                                    return [ s ].concat(l).concat([ r ]).join("\n");
                                }
                                return [ s ].join("\n");
                            }(e, t);
                            return e[2] ? "@media ".concat(e[2], " {").concat(i, "}") : i;
                        }).join("");
                    }, e.i = function(t, i, o) {
                        "string" == typeof t && (t = [ [ null, t, "" ] ]);
                        var n = {};
                        if (o) for (var s = 0; s < this.length; s++) {
                            var a = this[s][0];
                            null != a && (n[a] = !0);
                        }
                        for (var r = 0; r < t.length; r++) {
                            var l = [].concat(t[r]);
                            o && n[l[0]] || (i && (l[2] ? l[2] = "".concat(i, " and ").concat(l[2]) : l[2] = i), 
                            e.push(l));
                        }
                    }, e;
                };
            },
            725: function(t, e, i) {
                (e = i(645)(!1)).push([ t.id, ':root{--litepickerBgColor: #fff;--litepickerMonthHeaderTextColor: #333;--litepickerMonthButton: #9e9e9e;--litepickerMonthButtonHover: #2196f3;--litepickerMonthWidth: calc(var(--litepickerDayWidth) * 7);--litepickerMonthWeekdayColor: #9e9e9e;--litepickerDayColor: #333;--litepickerDayColorBg: #20c527;--litepickerDayColorHover: #2196f3;--litepickerDayIsTodayColor: #f44336;--litepickerDayIsInRange: #bbdefb;--litepickerDayIsLockedColor: #9e9e9e;--litepickerDayIsLockedColorBg: #a0a0a0;--litepickerDayIsHolidayColor: #000000;--litepickerDayIsHolidayColorBg: #ff9218;--litepickerDayIsBookedColor: #9e9e9e;--litepickerDayIsBookedColorBg: #f06f6f;--litepickerDayIsPartiallyBookedColor: #9e9e9e;--litepickerDayIsStartColor: #fff;--litepickerDayIsStartBg: #2196f3;--litepickerDayIsEndColor: #fff;--litepickerDayIsEndBg: #2196f3;--litepickerDayWidth: 38px;--litepickerButtonCancelColor: #fff;--litepickerButtonCancelBg: #9e9e9e;--litepickerButtonApplyColor: #fff;--litepickerButtonApplyBg: #2196f3;--litepickerButtonResetBtn: #909090;--litepickerButtonResetBtnHover: #2196f3;--litepickerHighlightedDayColor: #333;--litepickerHighlightedDayBg: #ffeb3b}.show-week-numbers{--litepickerMonthWidth: calc(var(--litepickerDayWidth) * 8)}.litepicker{font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;font-size:0.8em;display:none}.litepicker .container__main{display:-webkit-box;display:-ms-flexbox;display:flex}.litepicker .container__months{display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;background-color:var(--litepickerBgColor);border-radius:5px;-webkit-box-shadow:0 0 5px #ddd;box-shadow:0 0 5px #ddd;width:calc(var(--litepickerMonthWidth) + 10px);-webkit-box-sizing:content-box;box-sizing:content-box}.litepicker .container__months.columns-2{width:calc((var(--litepickerMonthWidth) * 2) + 20px)}.litepicker .container__months.columns-3{width:calc((var(--litepickerMonthWidth) * 3) + 30px)}.litepicker .container__months.columns-4{width:calc((var(--litepickerMonthWidth) * 4) + 40px)}.litepicker .container__months.split-view .month-item-header .button-previous-month,.litepicker .container__months.split-view .month-item-header .button-next-month{visibility:visible}.litepicker .container__months .month-item{padding:5px;width:var(--litepickerMonthWidth);-webkit-box-sizing:content-box;box-sizing:content-box}.litepicker .container__months .month-item-header{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;font-weight:500;padding:10px 5px;text-align:center;-webkit-box-align:center;-ms-flex-align:center;align-items:center;color:var(--litepickerMonthHeaderTextColor)}.litepicker .container__months .month-item-header div{-webkit-box-flex:1;-ms-flex:1;flex:1}.litepicker .container__months .month-item-header div>.month-item-name{margin-right:5px}.litepicker .container__months .month-item-header div>.month-item-year{padding:0}.litepicker .container__months .month-item-header .reset-button{color:var(--litepickerButtonResetBtn)}.litepicker .container__months .month-item-header .reset-button>svg,.litepicker .container__months .month-item-header .reset-button>img{fill:var(--litepickerButtonResetBtn);pointer-events:none}.litepicker .container__months .month-item-header .reset-button:hover{color:var(--litepickerButtonResetBtnHover)}.litepicker .container__months .month-item-header .reset-button:hover>svg{fill:var(--litepickerButtonResetBtnHover)}.litepicker .container__months .month-item-header .button-previous-month,.litepicker .container__months .month-item-header .button-next-month{visibility:hidden;text-decoration:none;color:var(--litepickerMonthButton);padding:3px 5px;border-radius:3px;-webkit-transition:color 0.3s, border 0.3s;transition:color 0.3s, border 0.3s;cursor:default}.litepicker .container__months .month-item-header .button-previous-month>svg,.litepicker .container__months .month-item-header .button-previous-month>img,.litepicker .container__months .month-item-header .button-next-month>svg,.litepicker .container__months .month-item-header .button-next-month>img{fill:var(--litepickerMonthButton);pointer-events:none}.litepicker .container__months .month-item-header .button-previous-month:hover,.litepicker .container__months .month-item-header .button-next-month:hover{color:var(--litepickerMonthButtonHover)}.litepicker .container__months .month-item-header .button-previous-month:hover>svg,.litepicker .container__months .month-item-header .button-next-month:hover>svg{fill:var(--litepickerMonthButtonHover)}.litepicker .container__months .month-item-weekdays-row{display:-webkit-box;display:-ms-flexbox;display:flex;justify-self:center;-webkit-box-pack:start;-ms-flex-pack:start;justify-content:flex-start;color:var(--litepickerMonthWeekdayColor)}.litepicker .container__months .month-item-weekdays-row>div{padding:5px 0;font-size:85%;-webkit-box-flex:1;-ms-flex:1;flex:1;width:var(--litepickerDayWidth);text-align:center}.litepicker .container__months .month-item:first-child .button-previous-month{visibility:visible}.litepicker .container__months .month-item:last-child .button-next-month{visibility:visible}.litepicker .container__months .month-item.no-previous-month .button-previous-month{visibility:hidden}.litepicker .container__months .month-item.no-next-month .button-next-month{visibility:hidden}.litepicker .container__days{display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;justify-self:center;-webkit-box-pack:start;-ms-flex-pack:start;justify-content:flex-start;text-align:center;-webkit-box-sizing:content-box;box-sizing:content-box}.litepicker .container__days>div,.litepicker .container__days>a{padding:5px 0;width:var(--litepickerDayWidth)}.litepicker .container__days .day-item{color:var(--litepickerDayColor);text-align:center;text-decoration:none;border-radius:3px;-webkit-transition:color 0.3s, border 0.3s;transition:color 0.3s, border 0.3s;cursor:default}.litepicker .container__days .day-item:hover{color:var(--litepickerDayColorHover);-webkit-box-shadow:inset 0 0 0 1px var(--litepickerDayColorHover);box-shadow:inset 0 0 0 1px var(--litepickerDayColorHover)}.litepicker .container__days .day-item.is-today{color:var(--litepickerDayIsTodayColor)}.litepicker .container__days .day-item.is-locked{color:var(--litepickerDayIsLockedColor)}.litepicker .container__days .day-item.is-locked:hover{color:var(--litepickerDayIsLockedColor);-webkit-box-shadow:none;box-shadow:none;cursor:default}.litepicker .container__days .day-item.is-holiday{background-color:orange}.litepicker .container__days .day-item.is-holiday:hover{color:var(--litepickerDayIsLockedColor);-webkit-box-shadow:none;box-shadow:none;cursor:default}.litepicker .container__days .day-item.is-partially-booked-start{background:-webkit-gradient(linear, left top, right bottom, from(var(--litepickerDayColorBg)), color-stop(50%, var(--litepickerDayColorBg)), color-stop(50%, var(--litepickerDayIsBookedColorBg)), to(var(--litepickerDayIsBookedColorBg)));background:linear-gradient(to bottom right, var(--litepickerDayColorBg) 0%, var(--litepickerDayColorBg) 50%, var(--litepickerDayIsBookedColorBg) 50%, var(--litepickerDayIsBookedColorBg) 100%)}.litepicker .container__days .day-item.is-partially-booked-end{background:-webkit-gradient(linear, right bottom, left top, from(var(--litepickerDayColorBg)), color-stop(50%, var(--litepickerDayColorBg)), color-stop(50%, var(--litepickerDayIsBookedColorBg)), to(var(--litepickerDayIsBookedColorBg)));background:linear-gradient(to top left, var(--litepickerDayColorBg) 0%, var(--litepickerDayColorBg) 50%, var(--litepickerDayIsBookedColorBg) 50%, var(--litepickerDayIsBookedColorBg) 100%)}.litepicker .container__days .day-item.is-booked{color:var(--litepickerDayIsBookedColor)}.litepicker .container__days .day-item.is-booked:hover{color:var(--litepickerDayIsBookedColor);-webkit-box-shadow:none;box-shadow:none;cursor:default}.litepicker .container__days .day-item.is-in-range{background-color:var(--litepickerDayIsInRange);border-radius:0}.litepicker .container__days .day-item.is-start-date{color:var(--litepickerDayIsStartColor);background-color:var(--litepickerDayIsStartBg);border-top-left-radius:5px;border-bottom-left-radius:5px;border-top-right-radius:0;border-bottom-right-radius:0}.litepicker .container__days .day-item.is-start-date.is-flipped{border-top-left-radius:0;border-bottom-left-radius:0;border-top-right-radius:5px;border-bottom-right-radius:5px}.litepicker .container__days .day-item.is-end-date{color:var(--litepickerDayIsEndColor);background-color:var(--litepickerDayIsEndBg);border-top-left-radius:0;border-bottom-left-radius:0;border-top-right-radius:5px;border-bottom-right-radius:5px}.litepicker .container__days .day-item.is-end-date.is-flipped{border-top-left-radius:5px;border-bottom-left-radius:5px;border-top-right-radius:0;border-bottom-right-radius:0}.litepicker .container__days .day-item.is-start-date.is-end-date{border-top-left-radius:5px;border-bottom-left-radius:5px;border-top-right-radius:5px;border-bottom-right-radius:5px}.litepicker .container__days .day-item.is-highlighted{color:var(--litepickerHighlightedDayColor);background-color:var(--litepickerHighlightedDayBg)}.litepicker .container__days .week-number{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;color:#9e9e9e;font-size:85%}.litepicker .container__footer{text-align:right;padding:10px 5px;margin:0 5px;background-color:#fafafa;-webkit-box-shadow:inset 0px 3px 3px 0px #ddd;box-shadow:inset 0px 3px 3px 0px #ddd;border-bottom-left-radius:5px;border-bottom-right-radius:5px}.litepicker .container__footer .preview-date-range{margin-right:10px;font-size:90%}.litepicker .container__footer .button-cancel{background-color:var(--litepickerButtonCancelBg);color:var(--litepickerButtonCancelColor);border:0;padding:3px 7px 4px;border-radius:3px}.litepicker .container__footer .button-cancel>svg,.litepicker .container__footer .button-cancel>img{pointer-events:none}.litepicker .container__footer .button-apply{background-color:var(--litepickerButtonApplyBg);color:var(--litepickerButtonApplyColor);border:0;padding:3px 7px 4px;border-radius:3px;margin-left:10px;margin-right:10px}.litepicker .container__footer .button-apply:disabled{opacity:0.7}.litepicker .container__footer .button-apply>svg,.litepicker .container__footer .button-apply>img{pointer-events:none}.litepicker .container__tooltip{position:absolute;margin-top:-4px;padding:4px 8px;border-radius:4px;background-color:#fff;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.25);box-shadow:0 1px 3px rgba(0,0,0,0.25);white-space:nowrap;font-size:11px;pointer-events:none;visibility:hidden}.litepicker .container__tooltip:before{position:absolute;bottom:-5px;left:calc(50% - 5px);border-top:5px solid rgba(0,0,0,0.12);border-right:5px solid transparent;border-left:5px solid transparent;content:""}.litepicker .container__tooltip:after{position:absolute;bottom:-4px;left:calc(50% - 4px);border-top:4px solid #fff;border-right:4px solid transparent;border-left:4px solid transparent;content:""}.litepicker-open{overflow:hidden}.litepicker-backdrop{display:none;background-color:#000;opacity:0.3;position:fixed;top:0;right:0;bottom:0;left:0}\n', "" ]), 
                e.locals = {
                    showWeekNumbers: "show-week-numbers",
                    litepicker: "litepicker",
                    containerMain: "container__main",
                    containerMonths: "container__months",
                    columns2: "columns-2",
                    columns3: "columns-3",
                    columns4: "columns-4",
                    splitView: "split-view",
                    monthItemHeader: "month-item-header",
                    buttonPreviousMonth: "button-previous-month",
                    buttonNextMonth: "button-next-month",
                    monthItem: "month-item",
                    monthItemName: "month-item-name",
                    monthItemYear: "month-item-year",
                    resetButton: "reset-button",
                    monthItemWeekdaysRow: "month-item-weekdays-row",
                    noPreviousMonth: "no-previous-month",
                    noNextMonth: "no-next-month",
                    containerDays: "container__days",
                    dayItem: "day-item",
                    isToday: "is-today",
                    isLocked: "is-locked",
                    isHoliday: "is-holiday",
                    isPartiallyBookedStart: "is-partially-booked-start",
                    isPartiallyBookedEnd: "is-partially-booked-end",
                    isBooked: "is-booked",
                    isInRange: "is-in-range",
                    isStartDate: "is-start-date",
                    isFlipped: "is-flipped",
                    isEndDate: "is-end-date",
                    isHighlighted: "is-highlighted",
                    weekNumber: "week-number",
                    containerFooter: "container__footer",
                    previewDateRange: "preview-date-range",
                    buttonCancel: "button-cancel",
                    buttonApply: "button-apply",
                    containerTooltip: "container__tooltip",
                    litepickerOpen: "litepicker-open",
                    litepickerBackdrop: "litepicker-backdrop"
                }, t.exports = e;
            },
            110: function(t, e, i) {
                var o = i(379), n = i(725);
                "string" == typeof (n = n.__esModule ? n.default : n) && (n = [ [ t.id, n, "" ] ]), 
                o(n, {
                    insert: function(t) {
                        var e = document.querySelector("head"), i = window._lastElementInsertedByStyleLoader;
                        window.disableLitepickerStyles || (i ? i.nextSibling ? e.insertBefore(t, i.nextSibling) : e.appendChild(t) : e.insertBefore(t, e.firstChild), 
                        window._lastElementInsertedByStyleLoader = t);
                    },
                    singleton: !1
                }), t.exports = n.locals || {};
            },
            379: function(t, e, i) {
                "use strict";
                var o, n = function() {
                    var t = {};
                    return function(e) {
                        if (void 0 === t[e]) {
                            var i = document.querySelector(e);
                            if (window.HTMLIFrameElement && i instanceof window.HTMLIFrameElement) try {
                                i = i.contentDocument.head;
                            } catch (t) {
                                i = null;
                            }
                            t[e] = i;
                        }
                        return t[e];
                    };
                }(), s = [];
                function a(t) {
                    for (var e = -1, i = 0; i < s.length; i++) if (s[i].identifier === t) {
                        e = i;
                        break;
                    }
                    return e;
                }
                function r(t, e) {
                    for (var i = {}, o = [], n = 0; n < t.length; n++) {
                        var r = t[n], l = e.base ? r[0] + e.base : r[0], d = i[l] || 0, c = "".concat(l, " ").concat(d);
                        i[l] = d + 1;
                        var h = a(c), p = {
                            css: r[1],
                            media: r[2],
                            sourceMap: r[3]
                        };
                        -1 !== h ? (s[h].references++, s[h].updater(p)) : s.push({
                            identifier: c,
                            updater: y(p, e),
                            references: 1
                        }), o.push(c);
                    }
                    return o;
                }
                function l(t) {
                    var e = document.createElement("style"), o = t.attributes || {};
                    if (void 0 === o.nonce) {
                        var s = i.nc;
                        s && (o.nonce = s);
                    }
                    if (Object.keys(o).forEach(function(t) {
                        e.setAttribute(t, o[t]);
                    }), "function" == typeof t.insert) t.insert(e); else {
                        var a = n(t.insert || "head");
                        if (!a) throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");
                        a.appendChild(e);
                    }
                    return e;
                }
                var d, c = (d = [], function(t, e) {
                    return d[t] = e, d.filter(Boolean).join("\n");
                });
                function h(t, e, i, o) {
                    var n = i ? "" : o.media ? "@media ".concat(o.media, " {").concat(o.css, "}") : o.css;
                    if (t.styleSheet) t.styleSheet.cssText = c(e, n); else {
                        var s = document.createTextNode(n), a = t.childNodes;
                        a[e] && t.removeChild(a[e]), a.length ? t.insertBefore(s, a[e]) : t.appendChild(s);
                    }
                }
                function p(t, e, i) {
                    var o = i.css, n = i.media, s = i.sourceMap;
                    if (n ? t.setAttribute("media", n) : t.removeAttribute("media"), 
                    s && "undefined" != typeof btoa && (o += "\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(s)))), " */")), 
                    t.styleSheet) t.styleSheet.cssText = o; else {
                        for (;t.firstChild; ) t.removeChild(t.firstChild);
                        t.appendChild(document.createTextNode(o));
                    }
                }
                var u = null, m = 0;
                function y(t, e) {
                    var i, o, n;
                    if (e.singleton) {
                        var s = m++;
                        i = u || (u = l(e)), o = h.bind(null, i, s, !1), n = h.bind(null, i, s, !0);
                    } else i = l(e), o = p.bind(null, i, e), n = function() {
                        !function(t) {
                            if (null === t.parentNode) return !1;
                            t.parentNode.removeChild(t);
                        }(i);
                    };
                    return o(t), function(e) {
                        if (e) {
                            if (e.css === t.css && e.media === t.media && e.sourceMap === t.sourceMap) return;
                            o(t = e);
                        } else n();
                    };
                }
                t.exports = function(t, e) {
                    (e = e || {}).singleton || "boolean" == typeof e.singleton || (e.singleton = (void 0 === o && (o = Boolean(window && document && document.all && !window.atob)), 
                    o));
                    var i = r(t = t || [], e);
                    return function(t) {
                        if (t = t || [], "[object Array]" === Object.prototype.toString.call(t)) {
                            for (var o = 0; o < i.length; o++) {
                                var n = a(i[o]);
                                s[n].references--;
                            }
                            for (var l = r(t, e), d = 0; d < i.length; d++) {
                                var c = a(i[d]);
                                0 === s[c].references && (s[c].updater(), s.splice(c, 1));
                            }
                            i = l;
                        }
                    };
                };
            },
            722: function(t, e, i) {
                "use strict";
                var o = this && this.__createBinding || (Object.create ? function(t, e, i, o) {
                    void 0 === o && (o = i), Object.defineProperty(t, o, {
                        enumerable: !0,
                        get: function() {
                            return e[i];
                        }
                    });
                } : function(t, e, i, o) {
                    void 0 === o && (o = i), t[o] = e[i];
                }), n = this && this.__setModuleDefault || (Object.create ? function(t, e) {
                    Object.defineProperty(t, "default", {
                        enumerable: !0,
                        value: e
                    });
                } : function(t, e) {
                    t.default = e;
                }), s = this && this.__importStar || function(t) {
                    if (t && t.__esModule) return t;
                    var e = {};
                    if (null != t) for (var i in t) "default" !== i && Object.hasOwnProperty.call(t, i) && o(e, t, i);
                    return n(e, t), e;
                };
                Object.defineProperty(e, "__esModule", {
                    value: !0
                }), e.Calendar = void 0;
                var a = i(939), r = s(i(110)), l = i(593), d = function() {
                    function t() {
                        this.options = {
                            element: null,
                            elementEnd: null,
                            parentEl: null,
                            firstDay: 1,
                            format: "YYYY-MM-DD",
                            lang: "en-US",
                            delimiter: " - ",
                            numberOfMonths: 1,
                            numberOfColumns: 1,
                            startDate: null,
                            endDate: null,
                            zIndex: 9999,
                            minDate: null,
                            maxDate: null,
                            minDays: null,
                            maxDays: null,
                            selectForward: !1,
                            selectBackward: !1,
                            splitView: !1,
                            inlineMode: !1,
                            singleMode: !0,
                            autoApply: !0,
                            allowRepick: !1,
                            showWeekNumbers: !1,
                            showTooltip: !0,
                            hotelMode: !1,
                            disableWeekends: !1,
                            scrollToDate: !0,
                            mobileFriendly: !0,
                            useResetBtn: !1,
                            autoRefresh: !1,
                            moveByOneMonth: !1,
                            days: [],
                            lockDaysFormat: "YYYY-MM-DD",
                            lockDays: [],
                            lockDaysInclusivity: "[]",
                            mobileCalendarMonthCount: 1,
                            disallowLockDaysInRange: !0,
                            countLockedDays: !1,
                            countLockedDaysMax: 0,
                            holidaysFormat: "YYYY-MM-DD",
                            holidays: [],
                            disallowHolidaysInRange: !1,
                            holidaysInclusivity: "[]",
                            partiallyBookedDaysFormat: "YYYY-MM-DD",
                            partiallyBookedDays: [],
                            disallowPartiallyBookedDaysInRange: !0,
                            partiallyBookedDaysInclusivity: "[]",
                            anyPartiallyBookedDaysAsCheckout: !1,
                            bookedDaysFormat: "YYYY-MM-DD",
                            bookedDays: [],
                            disallowBookedDaysInRange: !0,
                            bookedDaysInclusivity: "[]",
                            anyBookedDaysAsCheckout: !1,
                            highlightedDaysFormat: "YYYY-MM-DD",
                            highlightedDays: [],
                            dropdowns: {
                                minYear: 1990,
                                maxYear: null,
                                months: !1,
                                years: !1
                            },
                            buttonText: {
                                apply: "Apply",
                                cancel: "Cancel",
                                nextMonth: '<svg width="11" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M2.748 16L0 13.333 5.333 8 0 2.667 2.748 0l7.919 8z" fill-rule="nonzero"/></svg>',
                                previousMonth: '<svg width="11" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M7.919 0l2.748 2.667L5.333 8l5.334 5.333L7.919 16 0 8z" fill-rule="nonzero"/></svg>',
                                reset: '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">\n        <path d="M0 0h24v24H0z" fill="none"/>\n        <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>\n      </svg>'
                            },
                            tooltipText: {
                                one: "Tag",
                                other: "Tage"
                            },
                            tooltipPluralSelector: null,
                            onShow: null,
                            onHide: null,
                            onSelect: null,
                            onError: null,
                            onRender: null,
                            onRenderDay: null,
                            onAutoApply: null,
                            onChangeMonth: null,
                            onChangeYear: null,
                            onDayHover: null,
                            onDaySelect: null,
                            onShowTooltip: null,
                            resetBtnCallback: null,
                            moduleRanges: null,
                            moduleNavKeyboard: null,
                            onClickCalled: !1
                        }, this.calendars = [], this.datePicked = [];
                    }
                    return t.prototype.render = function() {
                        var t = this, e = document.createElement("div");
                        e.className = r.containerMain;
                        var i = document.createElement("div");
                        i.className = r.containerMonths, r["columns" + this.options.numberOfColumns] && (i.classList.remove(r.columns2, r.columns3, r.columns4), 
                        i.classList.add(r["columns" + this.options.numberOfColumns])), 
                        this.options.splitView && i.classList.add(r.splitView), 
                        this.options.showWeekNumbers && i.classList.add(r.showWeekNumbers);
                        for (var o = this.calendars[0].clone(), n = o.getMonth(), s = o.getMonth() + this.options.numberOfMonths, a = 0, l = n; l < s; l += 1) {
                            var d = o.clone();
                            d.setDate(1), this.options.splitView ? d = this.calendars[a].clone() : d.setMonth(l), 
                            i.appendChild(this.renderMonth(d)), a += 1;
                        }
                        if (this.picker.innerHTML = "", e.appendChild(i), this.options.useResetBtn) {
                            var c = document.createElement("a");
                            c.href = "#", c.className = r.resetButton, c.innerHTML = this.options.buttonText.reset, 
                            c.addEventListener("click", function(e) {
                                e.preventDefault(), t.clearSelection(), "function" == typeof t.options.resetBtnCallback && t.options.resetBtnCallback.call(t);
                            }), e.querySelector("." + r.monthItem + ":last-child").querySelector("." + r.monthItemHeader).appendChild(c);
                        }
                        if (this.picker.appendChild(e), this.options.autoApply && !this.options.footerHTML || this.picker.appendChild(this.renderFooter()), 
                        this.options.showTooltip && this.picker.appendChild(this.renderTooltip()), 
                        this.options.moduleRanges) {
                            if ("function" != typeof this.enableModuleRanges) throw new Error("moduleRanges is on but library does not included. See https://github.com/wakirin/litepicker-module-ranges.");
                            this.enableModuleRanges.call(this, this);
                        }
                        "function" == typeof this.options.onRender && this.options.onRender.call(this, this.picker);
                    }, t.prototype.renderMonth = function(t) {
                        var e = this, i = t.clone(), o = 32 - new Date(i.getFullYear(), i.getMonth(), 32).getDate(), n = document.createElement("div");
                        n.className = r.monthItem;
                        var s = document.createElement("div");
                        s.className = r.monthItemHeader;
                        var d = document.createElement("div");
                        if (this.options.dropdowns.months) {
                            var c = document.createElement("select");
                            c.className = r.monthItemName;
                            for (var h = 0; h < 12; h += 1) {
                                var p = document.createElement("option"), u = new a.DateTime(new Date(t.getFullYear(), h, 1, 0, 0, 0));
                                p.value = String(h), p.text = u.toLocaleString(this.options.lang, {
                                    month: "long"
                                }), p.disabled = this.options.minDate && u.isBefore(new a.DateTime(this.options.minDate), "month") || this.options.maxDate && u.isAfter(new a.DateTime(this.options.maxDate), "month"), 
                                p.selected = u.getMonth() === t.getMonth(), c.appendChild(p);
                            }
                            c.addEventListener("change", function(t) {
                                var i = t.target, o = 0;
                                if (e.options.splitView) {
                                    var n = i.closest("." + r.monthItem);
                                    o = l.findNestedMonthItem(n);
                                }
                                e.calendars[o].setMonth(Number(i.value)), e.render(), 
                                "function" == typeof e.options.onChangeMonth && e.options.onChangeMonth.call(e, e.calendars[o], o);
                            }), d.appendChild(c);
                        } else {
                            var m = document.createElement("strong");
                            m.className = r.monthItemName, m.innerHTML = t.toLocaleString(this.options.lang, {
                                month: "long"
                            }), d.appendChild(m);
                        }
                        if (this.options.dropdowns.years) {
                            var y = document.createElement("select");
                            y.className = r.monthItemYear;
                            var f = this.options.dropdowns.minYear, g = this.options.dropdowns.maxYear ? this.options.dropdowns.maxYear : new Date().getFullYear();
                            for (t.getFullYear() > g && ((p = document.createElement("option")).value = String(t.getFullYear()), 
                            p.text = String(t.getFullYear()), p.selected = !0, p.disabled = !0, 
                            y.appendChild(p)), h = g; h >= f; h -= 1) {
                                p = document.createElement("option");
                                var k = new a.DateTime(new Date(h, 0, 1, 0, 0, 0));
                                p.value = h, p.text = h, p.disabled = this.options.minDate && k.isBefore(new a.DateTime(this.options.minDate), "year") || this.options.maxDate && k.isAfter(new a.DateTime(this.options.maxDate), "year"), 
                                p.selected = t.getFullYear() === h, y.appendChild(p);
                            }
                            if (t.getFullYear() < f && ((p = document.createElement("option")).value = String(t.getFullYear()), 
                            p.text = String(t.getFullYear()), p.selected = !0, p.disabled = !0, 
                            y.appendChild(p)), "asc" === this.options.dropdowns.years) {
                                var D = Array.prototype.slice.call(y.childNodes).reverse();
                                y.innerHTML = "", D.forEach(function(t) {
                                    t.innerHTML = t.value, y.appendChild(t);
                                });
                            }
                            y.addEventListener("change", function(t) {
                                var i = t.target, o = 0;
                                if (e.options.splitView) {
                                    var n = i.closest("." + r.monthItem);
                                    o = l.findNestedMonthItem(n);
                                }
                                e.calendars[o].setFullYear(Number(i.value)), e.render(), 
                                "function" == typeof e.options.onChangeYear && e.options.onChangeYear.call(e, e.calendars[o], o);
                            }), d.appendChild(y);
                        } else {
                            var v = document.createElement("span");
                            v.className = r.monthItemYear, v.innerHTML = String(t.getFullYear()), 
                            d.appendChild(v);
                        }
                        var b = document.createElement("a");
                        b.href = "#", b.className = r.buttonPreviousMonth, b.innerHTML = this.options.buttonText.previousMonth;
                        var w = document.createElement("a");
                        w.href = "#", w.className = r.buttonNextMonth, w.innerHTML = this.options.buttonText.nextMonth, 
                        s.appendChild(b), s.appendChild(d), s.appendChild(w), this.options.minDate && i.isSameOrBefore(new a.DateTime(this.options.minDate), "month") && n.classList.add(r.noPreviousMonth), 
                        this.options.maxDate && i.isSameOrAfter(new a.DateTime(this.options.maxDate), "month") && n.classList.add(r.noNextMonth);
                        var M = document.createElement("div");
                        M.className = r.monthItemWeekdaysRow, this.options.showWeekNumbers && (M.innerHTML = "<div>W</div>");
                        for (var x = 1; x <= 7; x += 1) {
                            var T = 3 + this.options.firstDay + x, B = document.createElement("div");
                            B.innerHTML = this.weekdayName(T), B.title = this.weekdayName(T, "long"), 
                            M.appendChild(B);
                        }
                        var _ = document.createElement("div");
                        _.className = r.containerDays;
                        var L = this.calcSkipDays(i);
                        this.options.showWeekNumbers && L && _.appendChild(this.renderWeekNumber(i));
                        for (var I = 0; I < L; I += 1) {
                            var P = document.createElement("div");
                            _.appendChild(P);
                        }
                        for (I = 1; I <= o; I += 1) i.setDate(I), this.options.showWeekNumbers && i.getDay() === this.options.firstDay && _.appendChild(this.renderWeekNumber(i)), 
                        _.appendChild(this.renderDay(i));
                        return n.appendChild(s), n.appendChild(M), n.appendChild(_), 
                        n;
                    }, t.prototype.renderDay = function(t) {
                        var e = this;
                        t.setHours();
                        var i = document.createElement("a");
                        if (i.href = "#", i.className = r.dayItem, i.innerHTML = String(t.getDate()), 
                        i.dataset.time = String(t.getTime()), t.toDateString() === new Date().toDateString() && i.classList.add(r.isToday), 
                        this.datePicked.length) 2 === this.datePicked.length && (this.bookedDayAfterSelection = null), 
                        Number.isInteger(this.bookedDayAfterSelection) && this.bookedDayAfterSelection < t.getTime() && 1 === this.datePicked.length && i.classList.add(r.isLocked), 
                        this.datePicked[0].toDateString() === t.toDateString() && (i.classList.add(r.isStartDate), 
                        this.options.singleMode && i.classList.add(r.isEndDate)), 
                        2 === this.datePicked.length && this.datePicked[1].toDateString() === t.toDateString() && i.classList.add(r.isEndDate), 
                        2 === this.datePicked.length && t.isBetween(this.datePicked[0], this.datePicked[1]) && i.classList.add(r.isInRange); else if (this.options.startDate) {
                            var o = document.getElementsByClassName("is-end-date").length > 0;
                            this.options.startDate.toDateString() === t.toDateString() && o && (i.classList.add(r.isStartDate), 
                            this.options.singleMode && i.classList.add(r.isEndDate)), 
                            this.options.endDate && this.options.endDate.toDateString() === t.toDateString() && i.classList.add(r.isEndDate), 
                            this.options.startDate && this.options.endDate && t.isBetween(this.options.startDate, this.options.endDate) && i.classList.add(r.isInRange);
                        }
                        if (this.options.minDate && t.isBefore(new a.DateTime(this.options.minDate)) && i.classList.add(r.isLocked), 
                        this.options.maxDate && t.isAfter(new a.DateTime(this.options.maxDate)) && i.classList.add(r.isLocked), 
                        this.options.minDays && 1 === this.datePicked.length) {
                            var n = Number(!this.options.hotelMode), s = this.datePicked[0].clone().subtract(this.options.minDays - n, "day"), l = this.datePicked[0].clone().add(this.options.minDays - n, "day");
                            t.isBetween(s, this.datePicked[0], "(]") && i.classList.add(r.isLocked), 
                            t.isBetween(this.datePicked[0], l, "[)") && i.classList.add(r.isLocked);
                        }
                        if (this.options.maxDays && 1 === this.datePicked.length) {
                            n = Number(this.options.hotelMode), s = this.datePicked[0].clone().subtract(this.options.maxDays + n, "day");
                            var d = 0;
                            if (!this.options.disallowLockDaysInRange && (this.options.countLockedDaysMax > 0 || !this.options.countLockedDays)) {
                                for (var c = this.datePicked[0].clone(), h = this.options.maxDays, p = this.options.countLockedDaysMax, u = [], m = 0, y = [ this.options.holidays, this.options.lockDays ]; m < y.length; m++) for (var f = 0, g = y[m]; f < g.length; f++) {
                                    var k = g[f];
                                    this.datePicked[0].getTime() < k.getTime() && u.push(k);
                                }
                                for (;h > 0; ) {
                                    h -= 1, c = c.add(1, "day");
                                    for (var D = 0, v = u; D < v.length; D++) v[D].getTime() === c.getTime() && (this.dateIsBooked(c, this.options.bookedDaysInclusivity) || this.dateIsPartiallyBooked(c, this.options.partiallyBookedDaysInclusivity) || (p <= 0 || !this.options.countLockedDays ? (d += 1, 
                                    h += 1) : p > 0 && (p -= 1)));
                                }
                            }
                            l = this.datePicked[0].clone().add(this.options.maxDays + d + n, "day"), 
                            t.isSameOrBefore(s) && i.classList.add(r.isLocked), 
                            t.isSameOrAfter(l) && i.classList.add(r.isLocked);
                        }
                        if (this.options.selectForward && 1 === this.datePicked.length && t.isBefore(this.datePicked[0]) && i.classList.add(r.isLocked), 
                        this.options.selectBackward && 1 === this.datePicked.length && t.isAfter(this.datePicked[0]) && i.classList.add(r.isLocked), 
                        this.options.lockDays.length && this.options.lockDays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e.options.lockDaysInclusivity) : i.isSame(t, "day");
                        }).length && i.classList.add(r.isLocked), this.options.bookedDays.length && (M = this.options.bookedDays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e.options.bookedDaysInclusivity) : i.isSame(t, "day");
                        }).length) && (i.classList.add(r.isBooked), this.datePicked.length > 0 && !this.bookedDayAfterSelection && this.datePicked[0].getTime() < t.getTime() && (this.bookedDayAfterSelection = t.getTime())), 
                        this.options.partiallyBookedDays.length && (I = this.options.partiallyBookedDays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e.options.partiallyBookedDaysInclusivity) : i.isSame(t, "day");
                        }).length) && (!1 === (L = this.options.days[t.format(this.options.format)]).firstSlotBooked && i.classList.add(r.isPartiallyBookedStart), 
                        !1 === L.lastSlotBooked && i.classList.add(r.isPartiallyBookedEnd)), 
                        this.options.holidays.length && this.options.holidays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e.options.holidaysInclusivity) : i.isSame(t, "day");
                        }).length && i.classList.add(r.isHoliday), this.options.highlightedDays.length && this.options.highlightedDays.filter(function(e) {
                            return e instanceof Array ? t.isBetween(e[0], e[1], "[]") : e.isSame(t, "day");
                        }).length && i.classList.add(r.isHighlighted), this.datePicked.length <= 1) {
                            var b = t.clone();
                            if (b.subtract(1, "day"), t.clone().add(1, "day"), this.options.bookedDays.length) {
                                var w = this.options.bookedDaysInclusivity;
                                this.options.hotelMode && 1 === this.datePicked.length && (w = "()");
                                var M = this.dateIsBooked(t, w), x = this.dateIsBooked(b, "[]"), T = this.dateIsBooked(t, "(]"), B = 0 === this.datePicked.length && M || 1 === this.datePicked.length && x && M || 1 === this.datePicked.length && x && T, _ = this.options.anyBookedDaysAsCheckout && 1 === this.datePicked.length;
                                B && !_ && i.classList.add(r.isBooked);
                            }
                            if (this.options.partiallyBookedDays.length) {
                                w = this.options.partiallyBookedDaysInclusivity, 
                                this.options.hotelMode && 1 === this.datePicked.length && (w = "()");
                                var L, I = this.dateIsPartiallyBooked(t, w), P = (x = this.dateIsPartiallyBooked(b, "[]"), 
                                T = this.dateIsPartiallyBooked(t, "(]"), 0 === this.datePicked.length && I || 1 === this.datePicked.length && x && I || 1 === this.datePicked.length && x && T), S = this.options.anyPartiallyBookedDaysAsCheckout && 1 === this.datePicked.length;
                                P && !S && (!1 === (L = this.options.days[t.format(this.options.format)]).firstSlotBooked && i.classList.add(r.isPartiallyBookedStart), 
                                !1 === L.lastSlotBooked && i.classList.add(r.isPartiallyBookedEnd));
                            }
                        }
                        return !this.options.disableWeekends || 6 !== t.getDay() && 0 !== t.getDay() || i.classList.add(r.isLocked), 
                        "function" == typeof this.options.onRenderDay && this.options.onRenderDay.call(this, i), 
                        i;
                    }, t.prototype.renderFooter = function() {
                        var t = document.createElement("div");
                        if (t.className = r.containerFooter, this.options.footerHTML ? t.innerHTML = this.options.footerHTML : t.innerHTML = '\n      <span class="' + r.previewDateRange + '"></span>\n      <button type="button" class="' + r.buttonCancel + '">' + this.options.buttonText.cancel + '</button>\n      <button type="button" class="' + r.buttonApply + '">' + this.options.buttonText.apply + "</button>\n      ", 
                        this.options.singleMode) {
                            if (1 === this.datePicked.length) {
                                var e = this.datePicked[0].format(this.options.format, this.options.lang);
                                t.querySelector("." + r.previewDateRange).innerHTML = e;
                            }
                        } else if (1 === this.datePicked.length && t.querySelector("." + r.buttonApply).setAttribute("disabled", ""), 
                        2 === this.datePicked.length) {
                            e = this.datePicked[0].format(this.options.format, this.options.lang);
                            var i = this.datePicked[1].format(this.options.format, this.options.lang);
                            t.querySelector("." + r.previewDateRange).innerHTML = "" + e + this.options.delimiter + i;
                        }
                        return t;
                    }, t.prototype.renderWeekNumber = function(t) {
                        var e = document.createElement("div"), i = t.getWeek(this.options.firstDay);
                        return e.className = r.weekNumber, e.innerHTML = 53 === i && 0 === t.getMonth() ? "53 / 1" : i, 
                        e;
                    }, t.prototype.renderTooltip = function() {
                        var t = document.createElement("div");
                        return t.className = r.containerTooltip, t;
                    }, t.prototype.dateIsBooked = function(t, e) {
                        return this.options.bookedDays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e) : i.isSame(t, "day");
                        }).length;
                    }, t.prototype.dateIsPartiallyBooked = function(t, e) {
                        return this.options.partiallyBookedDays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e) : i.isSame(t, "day");
                        }).length;
                    }, t.prototype.dateIsHoliday = function(t, e) {
                        return this.options.holidays.filter(function(i) {
                            return i instanceof Array ? t.isBetween(i[0], i[1], e) : i.isSame(t, "day");
                        }).length;
                    }, t.prototype.weekdayName = function(t, e) {
                        return void 0 === e && (e = "short"), new Date(1970, 0, t, 12, 0, 0, 0).toLocaleString(this.options.lang, {
                            weekday: e
                        });
                    }, t.prototype.calcSkipDays = function(t) {
                        var e = t.getDay() - this.options.firstDay;
                        return e < 0 && (e += 7), e;
                    }, t;
                }();
                e.Calendar = d;
            },
            939: function(t, e) {
                "use strict";
                Object.defineProperty(e, "__esModule", {
                    value: !0
                }), e.DateTime = void 0;
                var i = function() {
                    function t(e, i, o) {
                        void 0 === e && (e = null), void 0 === i && (i = null), 
                        void 0 === o && (o = "en-US"), this.dateInstance = i ? t.parseDateTime(e, i, o) : e ? t.parseDateTime(e) : t.parseDateTime(new Date()), 
                        this.lang = o;
                    }
                    return t.parseDateTime = function(e, i, o) {
                        if (void 0 === i && (i = "YYYY-MM-DD"), void 0 === o && (o = "en-US"), 
                        !e) return new Date(NaN);
                        if (e instanceof Date) return new Date(e);
                        if (e instanceof t) return e.clone().getDateInstance();
                        if (/^-?\d{10,}$/.test(e)) return t.getDateZeroTime(new Date(Number(e)));
                        if ("string" == typeof e) {
                            for (var n = [], s = null; null != (s = t.regex.exec(i)); ) "\\" !== s[1] && n.push(s);
                            if (n.length) {
                                var a = {
                                    year: null,
                                    month: null,
                                    shortMonth: null,
                                    longMonth: null,
                                    day: null,
                                    value: ""
                                };
                                n[0].index > 0 && (a.value += ".*?");
                                for (var r = 0, l = Object.entries(n); r < l.length; r++) {
                                    var d = l[r], c = d[0], h = d[1], p = Number(c), u = t.formatPatterns(h[0], o), m = u.group, y = u.pattern;
                                    a[m] = p + 1, a.value += y, a.value += ".*?";
                                }
                                var f = new RegExp("^" + a.value + "$");
                                if (f.test(e)) {
                                    var g = f.exec(e), k = Number(g[a.year]), D = null;
                                    a.month ? D = Number(g[a.month]) - 1 : a.shortMonth ? D = t.shortMonths(o).indexOf(g[a.shortMonth]) : a.longMonth && (D = t.longMonths(o).indexOf(g[a.longMonth]));
                                    var v = Number(g[a.day]) || 1;
                                    return new Date(k, D, v, 0, 0, 0, 0);
                                }
                            }
                        }
                        return t.getDateZeroTime(new Date(e));
                    }, t.convertArray = function(e, i) {
                        return e.map(function(e) {
                            return e instanceof Array ? e.map(function(e) {
                                return new t(e, i);
                            }) : new t(e, i);
                        });
                    }, t.getDateZeroTime = function(t) {
                        return new Date(t.getFullYear(), t.getMonth(), t.getDate(), 0, 0, 0, 0);
                    }, t.shortMonths = function(e) {
                        return t.MONTH_JS.map(function(t) {
                            return new Date(2019, t).toLocaleString(e, {
                                month: "short"
                            });
                        });
                    }, t.longMonths = function(e) {
                        return t.MONTH_JS.map(function(t) {
                            return new Date(2019, t).toLocaleString(e, {
                                month: "long"
                            });
                        });
                    }, t.formatPatterns = function(e, i) {
                        switch (e) {
                          case "YY":
                          case "YYYY":
                            return {
                                group: "year",
                                pattern: "(\\d{" + e.length + "})"
                            };

                          case "M":
                            return {
                                group: "month",
                                pattern: "(\\d{1,2})"
                            };

                          case "MM":
                            return {
                                group: "month",
                                pattern: "(\\d{2})"
                            };

                          case "MMM":
                            return {
                                group: "shortMonth",
                                pattern: "(" + t.shortMonths(i).join("|") + ")"
                            };

                          case "MMMM":
                            return {
                                group: "longMonth",
                                pattern: "(" + t.longMonths(i).join("|") + ")"
                            };

                          case "D":
                            return {
                                group: "day",
                                pattern: "(\\d{1,2})"
                            };

                          case "DD":
                            return {
                                group: "day",
                                pattern: "(\\d{2})"
                            };
                        }
                    }, t.prototype.getDateInstance = function() {
                        return this.dateInstance;
                    }, t.prototype.toLocaleString = function(t, e) {
                        return this.dateInstance.toLocaleString(t, e);
                    }, t.prototype.toDateString = function() {
                        return this.dateInstance.toDateString();
                    }, t.prototype.getSeconds = function() {
                        return this.dateInstance.getSeconds();
                    }, t.prototype.getDay = function() {
                        return this.dateInstance.getDay();
                    }, t.prototype.getTime = function() {
                        return this.dateInstance.getTime();
                    }, t.prototype.getDate = function() {
                        return this.dateInstance.getDate();
                    }, t.prototype.getMonth = function() {
                        return this.dateInstance.getMonth();
                    }, t.prototype.getFullYear = function() {
                        return this.dateInstance.getFullYear();
                    }, t.prototype.setMonth = function(t) {
                        return this.dateInstance.setMonth(t);
                    }, t.prototype.setHours = function(t, e, i, o) {
                        void 0 === t && (t = 0), void 0 === e && (e = 0), void 0 === i && (i = 0), 
                        void 0 === o && (o = 0), this.dateInstance.setHours(t, e, i, o);
                    }, t.prototype.setSeconds = function(t) {
                        return this.dateInstance.setSeconds(t);
                    }, t.prototype.setDate = function(t) {
                        return this.dateInstance.setDate(t);
                    }, t.prototype.setFullYear = function(t) {
                        return this.dateInstance.setFullYear(t);
                    }, t.prototype.getWeek = function(t) {
                        var e = new Date(this.timestamp()), i = (this.getDay() + (7 - t)) % 7;
                        e.setDate(e.getDate() - i);
                        var o = e.getTime();
                        return e.setMonth(0, 1), e.getDay() !== t && e.setMonth(0, 1 + (4 - e.getDay() + 7) % 7), 
                        1 + Math.ceil((o - e.getTime()) / 6048e5);
                    }, t.prototype.clone = function() {
                        return new t(this.getDateInstance());
                    }, t.prototype.isBetween = function(t, e, i) {
                        switch (void 0 === i && (i = "()"), i) {
                          default:
                          case "()":
                            return this.timestamp() > t.getTime() && this.timestamp() < e.getTime();

                          case "[)":
                            return this.timestamp() >= t.getTime() && this.timestamp() < e.getTime();

                          case "(]":
                            return this.timestamp() > t.getTime() && this.timestamp() <= e.getTime();

                          case "[]":
                            return this.timestamp() >= t.getTime() && this.timestamp() <= e.getTime();
                        }
                    }, t.prototype.isBefore = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            return t.getTime() > this.getTime();

                          case "day":
                          case "days":
                            return new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime() > new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTime();

                          case "month":
                          case "months":
                            return new Date(t.getFullYear(), t.getMonth(), 1).getTime() > new Date(this.getFullYear(), this.getMonth(), 1).getTime();

                          case "year":
                          case "years":
                            return t.getFullYear() > this.getFullYear();
                        }
                        throw new Error("isBefore: Invalid unit!");
                    }, t.prototype.isSameOrBefore = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            return t.getTime() >= this.getTime();

                          case "day":
                          case "days":
                            return new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime() >= new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTime();

                          case "month":
                          case "months":
                            return new Date(t.getFullYear(), t.getMonth(), 1).getTime() >= new Date(this.getFullYear(), this.getMonth(), 1).getTime();
                        }
                        throw new Error("isSameOrBefore: Invalid unit!");
                    }, t.prototype.isAfter = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            return this.getTime() > t.getTime();

                          case "day":
                          case "days":
                            return new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTime() > new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime();

                          case "month":
                          case "months":
                            return new Date(this.getFullYear(), this.getMonth(), 1).getTime() > new Date(t.getFullYear(), t.getMonth(), 1).getTime();

                          case "year":
                          case "years":
                            return this.getFullYear() > t.getFullYear();
                        }
                        throw new Error("isAfter: Invalid unit!");
                    }, t.prototype.isSameOrAfter = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            return this.getTime() >= t.getTime();

                          case "day":
                          case "days":
                            return new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTime() >= new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime();

                          case "month":
                          case "months":
                            return new Date(this.getFullYear(), this.getMonth(), 1).getTime() >= new Date(t.getFullYear(), t.getMonth(), 1).getTime();
                        }
                        throw new Error("isSameOrAfter: Invalid unit!");
                    }, t.prototype.isSame = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            return this.getTime() === t.getTime();

                          case "day":
                          case "days":
                            return new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTime() === new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime();

                          case "month":
                          case "months":
                            return new Date(this.getFullYear(), this.getMonth(), 1).getTime() === new Date(t.getFullYear(), t.getMonth(), 1).getTime();
                        }
                        throw new Error("isSame: Invalid unit!");
                    }, t.prototype.add = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            this.setSeconds(this.getSeconds() + t);
                            break;

                          case "day":
                          case "days":
                            this.setDate(this.getDate() + t);
                            break;

                          case "month":
                          case "months":
                            this.setMonth(this.getMonth() + t);
                        }
                        return this;
                    }, t.prototype.subtract = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          case "second":
                          case "seconds":
                            this.setSeconds(this.getSeconds() - t);
                            break;

                          case "day":
                          case "days":
                            this.setDate(this.getDate() - t);
                            break;

                          case "month":
                          case "months":
                            this.setMonth(this.getMonth() - t);
                        }
                        return this;
                    }, t.prototype.diff = function(t, e) {
                        switch (void 0 === e && (e = "seconds"), e) {
                          default:
                          case "second":
                          case "seconds":
                            return this.getTime() - t.getTime();

                          case "day":
                          case "days":
                            return Math.round((this.timestamp() - t.getTime()) / 864e5);

                          case "month":
                          case "months":
                        }
                    }, t.prototype.format = function(e, i) {
                        void 0 === i && (i = "en-US");
                        for (var o = "", n = [], s = null; null != (s = t.regex.exec(e)); ) "\\" !== s[1] && n.push(s);
                        if (n.length) {
                            n[0].index > 0 && (o += e.substring(0, n[0].index));
                            for (var a = 0, r = Object.entries(n); a < r.length; a++) {
                                var l = r[a], d = l[0], c = l[1], h = Number(d);
                                o += this.formatTokens(c[0], i), n[h + 1] && (o += e.substring(c.index + c[0].length, n[h + 1].index)), 
                                h === n.length - 1 && (o += e.substring(c.index + c[0].length));
                            }
                        }
                        return o.replace(/\\/g, "");
                    }, t.prototype.timestamp = function() {
                        return new Date(this.getFullYear(), this.getMonth(), this.getDate(), 0, 0, 0, 0).getTime();
                    }, t.prototype.formatTokens = function(e, i) {
                        switch (e) {
                          case "YY":
                            return String(this.getFullYear()).slice(-2);

                          case "YYYY":
                            return String(this.getFullYear());

                          case "M":
                            return String(this.getMonth() + 1);

                          case "MM":
                            return ("0" + (this.getMonth() + 1)).slice(-2);

                          case "MMM":
                            return t.shortMonths(i)[this.getMonth()];

                          case "MMMM":
                            return t.longMonths(i)[this.getMonth()];

                          case "D":
                            return String(this.getDate());

                          case "DD":
                            return ("0" + this.getDate()).slice(-2);

                          default:
                            return "";
                        }
                    }, t.regex = /(\\)?(Y{2,4}|M{1,4}|D{1,2}|d{1,4})/g, t.MONTH_JS = [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ], 
                    t;
                }();
                e.DateTime = i;
            },
            506: function(t, e, i) {
                "use strict";
                var o, n = this && this.__extends || (o = function(t, e) {
                    return o = Object.setPrototypeOf || {
                        __proto__: []
                    } instanceof Array && function(t, e) {
                        t.__proto__ = e;
                    } || function(t, e) {
                        for (var i in e) e.hasOwnProperty(i) && (t[i] = e[i]);
                    }, o(t, e);
                }, function(t, e) {
                    function i() {
                        this.constructor = t;
                    }
                    o(t, e), t.prototype = null === e ? Object.create(e) : (i.prototype = e.prototype, 
                    new i());
                }), s = this && this.__assign || function() {
                    return s = Object.assign || function(t) {
                        for (var e, i = 1, o = arguments.length; i < o; i++) for (var n in e = arguments[i]) Object.prototype.hasOwnProperty.call(e, n) && (t[n] = e[n]);
                        return t;
                    }, s.apply(this, arguments);
                }, a = this && this.__createBinding || (Object.create ? function(t, e, i, o) {
                    void 0 === o && (o = i), Object.defineProperty(t, o, {
                        enumerable: !0,
                        get: function() {
                            return e[i];
                        }
                    });
                } : function(t, e, i, o) {
                    void 0 === o && (o = i), t[o] = e[i];
                }), r = this && this.__setModuleDefault || (Object.create ? function(t, e) {
                    Object.defineProperty(t, "default", {
                        enumerable: !0,
                        value: e
                    });
                } : function(t, e) {
                    t.default = e;
                }), l = this && this.__importStar || function(t) {
                    if (t && t.__esModule) return t;
                    var e = {};
                    if (null != t) for (var i in t) "default" !== i && Object.hasOwnProperty.call(t, i) && a(e, t, i);
                    return r(e, t), e;
                };
                Object.defineProperty(e, "__esModule", {
                    value: !0
                }), e.Litepicker = void 0;
                var d = i(722), c = i(939), h = l(i(110)), p = i(593), u = i(110), m = function(t) {
                    function e(e) {
                        var i = t.call(this) || this;
                        i.options = s(s({}, i.options), e.element.dataset), Object.keys(i.options).forEach(function(t) {
                            "true" !== i.options[t] && "false" !== i.options[t] || (i.options[t] = "true" === i.options[t]);
                        });
                        var o = s(s({}, i.options.dropdowns), e.dropdowns), n = s(s({}, i.options.buttonText), e.buttonText), a = s(s({}, i.options.tooltipText), e.tooltipText);
                        i.options = s(s({}, i.options), e), i.options.dropdowns = s({}, o), 
                        i.options.buttonText = s({}, n), i.options.tooltipText = s({}, a), 
                        i.options.elementEnd || (i.options.allowRepick = !1), i.options.lockDays.length && (i.options.lockDays = c.DateTime.convertArray(i.options.lockDays, i.options.lockDaysFormat)), 
                        i.options.holidays.length && (i.options.holidays = c.DateTime.convertArray(i.options.holidays, i.options.holidaysFormat)), 
                        i.options.bookedDays.length && (i.options.bookedDays = c.DateTime.convertArray(i.options.bookedDays, i.options.bookedDaysFormat)), 
                        i.options.partiallyBookedDays.length && (i.options.partiallyBookedDays = c.DateTime.convertArray(i.options.partiallyBookedDays, i.options.partiallyBookedDaysFormat)), 
                        i.options.highlightedDays.length && (i.options.highlightedDays = c.DateTime.convertArray(i.options.highlightedDays, i.options.highlightedDaysFormat)), 
                        i.options.hotelMode && !("bookedDaysInclusivity" in e) && (i.options.bookedDaysInclusivity = "[)"), 
                        i.options.hotelMode && !("partiallyBookedDaysInclusivity" in e) && (i.options.partiallyBookedDaysInclusivity = "[)"), 
                        i.options.hotelMode && !("disallowBookedDaysInRange" in e) && (i.options.disallowBookedDaysInRange = !0), 
                        i.options.hotelMode && !("selectForward" in e) && (i.options.selectForward = !0);
                        var r = i.parseInput(), l = r[0], d = r[1];
                        i.options.startDate && (i.options.singleMode || i.options.endDate) && (l = new c.DateTime(i.options.startDate, i.options.format, i.options.lang)), 
                        l && i.options.endDate && (d = new c.DateTime(i.options.endDate, i.options.format, i.options.lang)), 
                        l instanceof c.DateTime && !isNaN(l.getTime()) && (i.options.startDate = l), 
                        i.options.startDate && d instanceof c.DateTime && !isNaN(d.getTime()) && (i.options.endDate = d), 
                        !i.options.singleMode || i.options.startDate instanceof c.DateTime || (i.options.startDate = null), 
                        i.options.singleMode || i.options.startDate instanceof c.DateTime && i.options.endDate instanceof c.DateTime || (i.options.startDate = new c.DateTime(i.options.startDate, i.options.format, i.options.lang), 
                        i.options.endDate = null);
                        for (var h = 0; h < i.options.numberOfMonths; h += 1) {
                            var p = i.options.startDate instanceof c.DateTime ? i.options.startDate.clone() : new c.DateTime();
                            p.setDate(1), p.setMonth(p.getMonth() + h), i.calendars[h] = p;
                        }
                        if (i.options.showTooltip) if (i.options.tooltipPluralSelector) i.pluralSelector = i.options.tooltipPluralSelector; else try {
                            var u = new Intl.PluralRules(i.options.lang);
                            i.pluralSelector = u.select.bind(u);
                        } catch (t) {
                            i.pluralSelector = function(t) {
                                return 0 === Math.abs(t) ? "one" : "other";
                            };
                        }
                        return i.loadPolyfillsForIE11(), i.onInit(), i;
                    }
                    return n(e, t), e.prototype.scrollToDate = function(t) {
                        if (this.options.scrollToDate) {
                            var e = this.options.startDate instanceof c.DateTime ? this.options.startDate.clone() : null;
                            !this.options.startDate || t && t !== this.options.element || (e.setDate(1), 
                            this.calendars[0] = e.clone(), this.options.scrollToDate = !1, 
                            this.options.onChangeMonth.call(this, this.calendars[0], 0));
                        }
                    }, e.prototype.onInit = function() {
                        var t = this;
                        if (document.addEventListener("click", function(e) {
                            return t.onClick(e);
                        }, !0), this.picker = document.createElement("div"), this.picker.className = h.litepicker, 
                        this.picker.style.display = "none", this.picker.addEventListener("mouseenter", function(e) {
                            return t.onMouseEnter(e);
                        }, !0), this.picker.addEventListener("mouseleave", function(e) {
                            return t.onMouseLeave(e);
                        }, !1), this.options.autoRefresh ? (this.options.element instanceof HTMLElement && this.options.element.addEventListener("keyup", function(e) {
                            return t.onInput(e);
                        }, !0), this.options.elementEnd instanceof HTMLElement && this.options.elementEnd.addEventListener("keyup", function(e) {
                            return t.onInput(e);
                        }, !0)) : (this.options.element instanceof HTMLElement && this.options.element.addEventListener("change", function(e) {
                            return t.onInput(e);
                        }, !0), this.options.elementEnd instanceof HTMLElement && this.options.elementEnd.addEventListener("change", function(e) {
                            return t.onInput(e);
                        }, !0)), this.options.moduleNavKeyboard) {
                            if ("function" != typeof this.enableModuleNavKeyboard) throw new Error("moduleNavKeyboard is on but library does not included. See https://github.com/wakirin/litepicker-module-navkeyboard.");
                            this.enableModuleNavKeyboard.call(this, this);
                        }
                        this.render(), this.options.parentEl ? this.options.parentEl instanceof HTMLElement ? this.options.parentEl.appendChild(this.picker) : document.querySelector(this.options.parentEl).appendChild(this.picker) : this.options.inlineMode ? this.options.element instanceof HTMLInputElement ? this.options.element.parentNode.appendChild(this.picker) : this.options.element.appendChild(this.picker) : document.body.appendChild(this.picker), 
                        this.options.mobileFriendly && (this.backdrop = document.createElement("div"), 
                        this.backdrop.className = h.litepickerBackdrop, this.backdrop.addEventListener("click", this.hide()), 
                        this.options.element && this.options.element.parentNode && this.options.element.parentNode.appendChild(this.backdrop), 
                        window.addEventListener("orientationchange", function(e) {
                            var i = function() {
                                if (p.isMobile() && t.isShowning() && ("landscape" === p.getOrientation() ? (t.options.numberOfMonths = 2, 
                                t.options.numberOfColumns = 2) : (t.options.numberOfMonths = t.options.mobileCalendarMonthCount, 
                                t.options.numberOfColumns = 1), t.render(), !t.options.inlineMode)) {
                                    var e = t.picker.getBoundingClientRect();
                                    t.picker.style.top = "calc(50% - " + e.height / 2 + "px)", 
                                    t.picker.style.left = "calc(50% - " + e.width / 2 + "px)";
                                }
                                window.removeEventListener("resize", i);
                            };
                            window.addEventListener("resize", i);
                        })), this.options.inlineMode && (this.show(), this.options.mobileFriendly && p.isMobile() && (window.dispatchEvent(new Event("orientationchange")), 
                        window.dispatchEvent(new Event("resize")))), this.updateInput();
                    }, e.prototype.parseInput = function() {
                        var t = this.options.delimiter, e = new RegExp("" + t), i = this.options.element instanceof HTMLInputElement ? this.options.element.value.split(t) : [];
                        if (this.options.elementEnd) {
                            if (this.options.element instanceof HTMLInputElement && this.options.element.value.length && this.options.elementEnd instanceof HTMLInputElement && this.options.elementEnd.value.length) return [ new c.DateTime(this.options.element.value, this.options.format), new c.DateTime(this.options.elementEnd.value, this.options.format) ];
                        } else if (this.options.singleMode) {
                            if (this.options.element instanceof HTMLInputElement && this.options.element.value.length) return [ new c.DateTime(this.options.element.value, this.options.format) ];
                        } else if (this.options.element instanceof HTMLInputElement && e.test(this.options.element.value) && i.length && i.length % 2 == 0) {
                            var o = i.slice(0, i.length / 2).join(t), n = i.slice(i.length / 2).join(t);
                            return [ new c.DateTime(o, this.options.format), new c.DateTime(n, this.options.format) ];
                        }
                        return [];
                    }, e.prototype.updateInput = function() {
                        if (this.options.element instanceof HTMLInputElement) {
                            if (this.options.singleMode && this.options.startDate) this.options.element.value = this.options.startDate.format(this.options.format, this.options.lang); else if (!this.options.singleMode && this.options.startDate && this.options.endDate) {
                                var t = this.options.startDate.format(this.options.format, this.options.lang), e = this.options.endDate.format(this.options.format, this.options.lang);
                                this.options.elementEnd ? (this.options.element.value = t, 
                                this.options.elementEnd.value = e) : this.options.element.value = "" + t + this.options.delimiter + e;
                            }
                            this.options.startDate || this.options.endDate || (this.options.element.value = "", 
                            this.options.elementEnd && (this.options.elementEnd.value = ""));
                        }
                    }, e.prototype.isSamePicker = function(t) {
                        return t.closest("." + h.litepicker) === this.picker;
                    }, e.prototype.shouldShown = function(t) {
                        return t === this.options.element || this.options.elementEnd && t === this.options.elementEnd;
                    }, e.prototype.shouldResetDatePicked = function() {
                        return this.options.singleMode || 2 === this.datePicked.length;
                    }, e.prototype.shouldSwapDatePicked = function() {
                        return 2 === this.datePicked.length && this.datePicked[0].getTime() > this.datePicked[1].getTime();
                    }, e.prototype.shouldCheckLockDays = function() {
                        return this.options.disallowLockDaysInRange && this.options.lockDays.length && 2 === this.datePicked.length;
                    }, e.prototype.shouldCheckPartiallyBookedDays = function() {
                        return this.options.disallowPartiallyBookedDaysInRange && this.options.partiallyBookedDays.length && 2 === this.datePicked.length;
                    }, e.prototype.shouldCheckHolidays = function() {
                        return this.options.disallowHolidaysInRange && this.options.holidays.length && 2 === this.datePicked.length;
                    }, e.prototype.shouldCheckBookedDays = function() {
                        return this.options.disallowBookedDaysInRange && this.options.bookedDays.length && 2 === this.datePicked.length;
                    }, e.prototype.onClick = function(t) {
                        var e = this, i = t.target;
                        if (i && this.picker) if (this.shouldShown(i)) this.show(i); else if (i.closest("." + h.litepicker), 
                        i.classList.contains(h.dayItem)) {
                            if (t.preventDefault(), !this.isSamePicker(i)) return;
                            if (i.classList.contains(h.isLocked)) return;
                            if (i.classList.contains(h.isHoliday)) return;
                            if (i.classList.contains(h.isBooked)) return;
                            if (this.shouldResetDatePicked() && (this.datePicked.length = 0), 
                            this.datePicked[this.datePicked.length] = new c.DateTime(i.dataset.time), 
                            this.shouldSwapDatePicked()) {
                                var o = this.datePicked[1].clone();
                                this.datePicked[1] = this.datePicked[0].clone(), 
                                this.datePicked[0] = o.clone();
                            }
                            if (this.shouldCheckLockDays()) {
                                var n = this.options.lockDaysInclusivity;
                                this.options.lockDays.filter(function(t) {
                                    return t instanceof Array ? t[0].isBetween(e.datePicked[0], e.datePicked[1], n) || t[1].isBetween(e.datePicked[0], e.datePicked[1], n) : t.isBetween(e.datePicked[0], e.datePicked[1], n);
                                }).length && (this.datePicked.length = 0, "function" == typeof this.options.onError && this.options.onError.call(this, "INVALID_RANGE"));
                            }
                            if (this.shouldCheckHolidays()) {
                                var s = this.options.holidaysInclusivity;
                                this.options.holidays.filter(function(t) {
                                    return t instanceof Array ? t[0].isBetween(e.datePicked[0], e.datePicked[1], s) || t[1].isBetween(e.datePicked[0], e.datePicked[1], s) : t.isBetween(e.datePicked[0], e.datePicked[1], s);
                                }).length && (this.datePicked.length = 0, "function" == typeof this.options.onError && this.options.onError.call(this, "INVALID_RANGE"));
                            }
                            if (this.shouldCheckPartiallyBookedDays()) {
                                var a = this.options.partiallyBookedDaysInclusivity;
                                if (!(m = this.options.partiallyBookedDays.filter(function(t) {
                                    return t instanceof Array ? t[0].isBetween(e.datePicked[0], e.datePicked[1], a) || t[1].isBetween(e.datePicked[0], e.datePicked[1], a) : t.isBetween(e.datePicked[0], e.datePicked[1]);
                                }).length) && this.datePicked[0].getDate() !== this.datePicked[1].getDate()) {
                                    var r = this.datePicked[0].format(this.options.bookedDaysFormat), l = this.datePicked[1].format(this.options.bookedDaysFormat), d = this.options.days[r];
                                    m = this.options.days[l].firstSlotBooked || d.lastSlotBooked;
                                }
                                var u = this.options.anyBookedDaysAsCheckout && 1 === this.datePicked.length;
                                m && !u && (this.datePicked.length = 0, "function" == typeof this.options.onError && this.options.onError.call(this, "INVALID_RANGE"));
                            }
                            if (this.shouldCheckBookedDays()) {
                                var m, y = this.options.bookedDaysInclusivity;
                                (m = this.options.bookedDays.filter(function(t) {
                                    return t instanceof Array ? t[0].isBetween(e.datePicked[0], e.datePicked[1], y) || t[1].isBetween(e.datePicked[0], e.datePicked[1], y) : t.isBetween(e.datePicked[0], e.datePicked[1], y);
                                }).length) && (this.datePicked.length = 0, "function" == typeof this.options.onError && this.options.onError.call(this, "INVALID_RANGE"));
                            }
                            if ("function" == typeof this.options.onDaySelect && this.options.onDaySelect.call(this, c.DateTime.parseDateTime(i.dataset.time), this.datePicked.length), 
                            this.render(), this.options.autoApply) {
                                var f = !1;
                                this.options.singleMode && this.datePicked.length ? (this.setDate(this.datePicked[0]), 
                                this.hide(), f = !0) : this.options.singleMode || 2 !== this.datePicked.length || (this.setDateRange(this.datePicked[0], this.datePicked[1]), 
                                this.hide(), f = !0), "function" == typeof this.options.onAutoApply && this.options.onAutoApply.call(this, f);
                            }
                        } else {
                            if (i.classList.contains(h.buttonPreviousMonth)) {
                                if (t.preventDefault(), !this.isSamePicker(i)) return;
                                var g = 0, k = this.options.moveByOneMonth ? 1 : this.options.numberOfMonths;
                                if (this.options.splitView) {
                                    var D = i.closest("." + h.monthItem);
                                    g = p.findNestedMonthItem(D), k = 1;
                                }
                                return this.calendars[g].setMonth(this.calendars[g].getMonth() - k), 
                                this.gotoDate(this.calendars[g], g), void ("function" == typeof this.options.onChangeMonth && this.options.onChangeMonth.call(this, this.calendars[g], g));
                            }
                            if (i.classList.contains(h.buttonNextMonth)) {
                                if (t.preventDefault(), !this.isSamePicker(i)) return;
                                return g = 0, k = this.options.moveByOneMonth ? 1 : this.options.numberOfMonths, 
                                this.options.splitView && (D = i.closest("." + h.monthItem), 
                                g = p.findNestedMonthItem(D), k = 1), this.calendars[g].setMonth(this.calendars[g].getMonth() + k), 
                                this.gotoDate(this.calendars[g], g), void ("function" == typeof this.options.onChangeMonth && this.options.onChangeMonth.call(this, this.calendars[g], g));
                            }
                            if (i.classList.contains(h.buttonCancel)) {
                                if (t.preventDefault(), !this.isSamePicker(i)) return;
                                this.hide();
                            }
                            if (i.classList.contains(h.buttonApply)) {
                                if (t.preventDefault(), !this.isSamePicker(i)) return;
                                this.options.singleMode && this.datePicked.length ? this.setDate(this.datePicked[0]) : this.options.singleMode || 2 !== this.datePicked.length || this.setDateRange(this.datePicked[0], this.datePicked[1]), 
                                this.hide();
                            }
                        }
                    }, e.prototype.showTooltip = function(t, e) {
                        var i = this.picker.querySelector("." + h.containerTooltip);
                        i.style.visibility = "visible", i.innerHTML = e;
                        var o = this.picker.getBoundingClientRect(), n = i.getBoundingClientRect(), s = t.getBoundingClientRect(), a = s.top, r = s.left;
                        if (this.options.inlineMode && this.options.parentEl) {
                            var l = this.picker.parentNode.getBoundingClientRect();
                            a -= l.top, r -= l.left;
                        } else a -= o.top, r -= o.left;
                        a -= n.height, r -= n.width / 2, r += s.width / 2, i.style.top = a + "px", 
                        i.style.left = r + "px", "function" == typeof this.options.onShowTooltip && this.options.onShowTooltip.call(this, i, t);
                    }, e.prototype.hideTooltip = function() {
                        this.picker.querySelector("." + h.containerTooltip).style.visibility = "hidden";
                    }, e.prototype.shouldAllowMouseEnter = function(t) {
                        return !(this.options.singleMode || t.classList.contains(h.isLocked) || t.classList.contains(h.isHoliday) || t.classList.contains(h.isBooked));
                    }, e.prototype.shouldAllowRepick = function() {
                        return this.options.elementEnd && this.options.allowRepick && this.options.startDate && this.options.endDate;
                    }, e.prototype.isDayItem = function(t) {
                        return t.classList.contains(h.dayItem);
                    }, e.prototype.onMouseEnter = function(t) {
                        var e = this, i = t.target;
                        if (this.isDayItem(i) && ("function" == typeof this.options.onDayHover && this.options.onDayHover.call(this, c.DateTime.parseDateTime(i.dataset.time), i.classList.toString().split(/\s/), i), 
                        this.shouldAllowMouseEnter(i))) {
                            if (this.shouldAllowRepick() && (this.triggerElement === this.options.element ? this.datePicked[0] = this.options.endDate.clone() : this.triggerElement === this.options.elementEnd && (this.datePicked[0] = this.options.startDate.clone())), 
                            1 !== this.datePicked.length) return;
                            var o = this.picker.querySelector("." + h.dayItem + '[data-time="' + this.datePicked[0].getTime() + '"]'), n = this.datePicked[0].clone(), s = new c.DateTime(i.dataset.time), a = !1;
                            if (n.getTime() > s.getTime()) {
                                var r = n.clone();
                                n = s.clone(), s = r.clone(), a = !0;
                            }
                            if (Array.prototype.slice.call(this.picker.querySelectorAll("." + h.dayItem)).forEach(function(t) {
                                var i = new c.DateTime(t.dataset.time), o = e.renderDay(i);
                                if (i.isBetween(n, s)) {
                                    var a = e.options.days[i.format(e.options.bookedDaysFormat)];
                                    a.bookedDay ? o.classList.add(h.isBooked) : !a.partiallyBookedDay || a.holiday || a.locked || (a.firstSlotBooked && o.classList.add(h.isPartiallyBookedStart), 
                                    a.lastSlotBooked && o.classList.add(h.isPartiallyBookedEnd)), 
                                    e.options.disallowLockDaysInRange || (a.holiday ? o.classList.remove(h.isHoliday) : a.locked && o.classList.remove(h.isLocked), 
                                    o.classList.add(u.isInRange));
                                }
                                t.className = o.className;
                            }), i.classList.add(h.isEndDate), a ? (o && o.classList.add(h.isFlipped), 
                            i.classList.add(h.isFlipped)) : (o && o.classList.remove(h.isFlipped), 
                            i.classList.remove(h.isFlipped)), this.options.showTooltip) {
                                var l = s.diff(n, "day");
                                if (this.options.hotelMode || (l += 1), l > 0) {
                                    var d = this.pluralSelector(l), p = l + " " + (this.options.tooltipText[d] ? this.options.tooltipText[d] : "[" + d + "]");
                                    this.showTooltip(i, p);
                                } else this.hideTooltip();
                            }
                        }
                    }, e.prototype.onMouseLeave = function(t) {
                        t.target, this.options.allowRepick && (!this.options.allowRepick || this.options.startDate || this.options.endDate) && (this.datePicked.length = 0, 
                        this.render());
                    }, e.prototype.onInput = function(t) {
                        var e = this.parseInput(), i = e[0], o = e[1], n = this.options.format;
                        if (this.options.elementEnd ? i instanceof c.DateTime && o instanceof c.DateTime && i.format(n) === this.options.element.value && o.format(n) === this.options.elementEnd.value : this.options.singleMode ? i instanceof c.DateTime && i.format(n) === this.options.element.value : i instanceof c.DateTime && o instanceof c.DateTime && "" + i.format(n) + this.options.delimiter + o.format(n) === this.options.element.value) {
                            if (o && i.getTime() > o.getTime()) {
                                var s = i.clone();
                                i = o.clone(), o = s.clone();
                            }
                            this.options.startDate = new c.DateTime(i, this.options.format, this.options.lang), 
                            o && (this.options.endDate = new c.DateTime(o, this.options.format, this.options.lang)), 
                            this.updateInput(), this.render();
                            var a = i.clone(), r = 0;
                            (this.options.elementEnd ? i.format(n) === t.target.value : t.target.value.startsWith(i.format(n))) || (a = o.clone(), 
                            r = this.options.numberOfMonths - 1), "function" == typeof this.options.onSelect && this.options.onSelect.call(this, this.getStartDate(), this.getEndDate()), 
                            this.gotoDate(a, r);
                        }
                    }, e.prototype.isShowning = function() {
                        return this.picker && "none" !== this.picker.style.display;
                    }, e.prototype.loadPolyfillsForIE11 = function() {
                        Object.entries || (Object.entries = function(t) {
                            for (var e = Object.keys(t), i = e.length, o = new Array(i); i; ) o[i -= 1] = [ e[i], t[e[i]] ];
                            return o;
                        }), Element.prototype.matches || (Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector), 
                        Element.prototype.closest || (Element.prototype.closest = function(t) {
                            var e = this;
                            do {
                                if (e.matches(t)) return e;
                                e = e.parentElement || e.parentNode;
                            } while (null !== e && 1 === e.nodeType);
                            return null;
                        });
                    }, e;
                }(d.Calendar);
                e.Litepicker = m;
            },
            997: function(t, e, i) {
                "use strict";
                var o = this && this.__assign || function() {
                    return o = Object.assign || function(t) {
                        for (var e, i = 1, o = arguments.length; i < o; i++) for (var n in e = arguments[i]) Object.prototype.hasOwnProperty.call(e, n) && (t[n] = e[n]);
                        return t;
                    }, o.apply(this, arguments);
                }, n = this && this.__createBinding || (Object.create ? function(t, e, i, o) {
                    void 0 === o && (o = i), Object.defineProperty(t, o, {
                        enumerable: !0,
                        get: function() {
                            return e[i];
                        }
                    });
                } : function(t, e, i, o) {
                    void 0 === o && (o = i), t[o] = e[i];
                }), s = this && this.__setModuleDefault || (Object.create ? function(t, e) {
                    Object.defineProperty(t, "default", {
                        enumerable: !0,
                        value: e
                    });
                } : function(t, e) {
                    t.default = e;
                }), a = this && this.__importStar || function(t) {
                    if (t && t.__esModule) return t;
                    var e = {};
                    if (null != t) for (var i in t) "default" !== i && Object.hasOwnProperty.call(t, i) && n(e, t, i);
                    return s(e, t), e;
                };
                Object.defineProperty(e, "__esModule", {
                    value: !0
                });
                var r = i(939), l = i(506), d = a(i(110)), c = i(593);
                l.Litepicker.prototype.show = function(t) {
                    void 0 === t && (t = null);
                    var e = t || this.options.element;
                    if (this.triggerElement = e, this.scrollToDate(t), this.options.inlineMode) return this.picker.style.position = "static", 
                    this.picker.style.display = "inline-block", this.picker.style.top = null, 
                    this.picker.style.left = null, this.picker.style.bottom = null, 
                    void (this.picker.style.right = null);
                    if (this.options.mobileFriendly && c.isMobile()) {
                        this.picker.style.position = "fixed", this.picker.style.display = "block", 
                        "portrait" === c.getOrientation() ? (this.options.numberOfMonths = 1, 
                        this.options.numberOfColumns = 1) : (this.options.numberOfMonths = 2, 
                        this.options.numberOfColumns = 2), this.render();
                        var i = this.picker.getBoundingClientRect();
                        return this.picker.style.top = "calc(50% - " + i.height / 2 + "px)", 
                        this.picker.style.left = "calc(50% - " + i.width / 2 + "px)", 
                        this.picker.style.right = null, this.picker.style.bottom = null, 
                        this.picker.style.zIndex = this.options.zIndex, this.backdrop.style.display = "block", 
                        this.backdrop.style.zIndex = this.options.zIndex - 1, document.body.classList.add(d.litepickerOpen), 
                        "function" == typeof this.options.onShow && this.options.onShow.call(this), 
                        void (t ? t.blur() : this.options.element.blur());
                    }
                    this.render(), this.picker.style.position = "absolute", this.picker.style.display = "block", 
                    this.picker.style.zIndex = this.options.zIndex;
                    var o = e.getBoundingClientRect(), n = this.picker.getBoundingClientRect(), s = o.bottom, a = o.left, r = 0, l = 0, h = 0, p = 0;
                    if (this.options.parentEl) {
                        var u = this.picker.parentNode.getBoundingClientRect();
                        s -= u.bottom, (s += o.height) + n.height > window.innerHeight && o.top - u.top - o.height > 0 && (h = o.top - u.top - o.height), 
                        (a -= u.left) + n.width > window.innerWidth && o.right - u.right - n.width > 0 && (p = o.right - u.right - n.width);
                    } else r = window.scrollX || window.pageXOffset, l = window.scrollY || window.pageYOffset, 
                    s + n.height > window.innerHeight && o.top - n.height > 0 && (h = o.top - n.height), 
                    a + n.width > window.innerWidth && o.right - n.width > 0 && (p = o.right - n.width);
                    this.picker.style.top = (h || s) + l + "px", this.picker.style.left = (p || a) + r + "px", 
                    this.picker.style.right = null, this.picker.style.bottom = null, 
                    "function" == typeof this.options.onShow && this.options.onShow.call(this);
                }, l.Litepicker.prototype.hide = function() {
                    this.isShowning() && (this.datePicked.length = 0, this.updateInput(), 
                    this.options.inlineMode ? this.render() : (this.picker.style.display = "none", 
                    "function" == typeof this.options.onHide && this.options.onHide.call(this), 
                    this.options.mobileFriendly && (document.body.classList.remove(d.litepickerOpen), 
                    this.backdrop.style.display = "none")));
                }, l.Litepicker.prototype.getDate = function() {
                    return this.getStartDate();
                }, l.Litepicker.prototype.getStartDate = function() {
                    return this.options.startDate ? this.options.startDate.clone().getDateInstance() : null;
                }, l.Litepicker.prototype.getEndDate = function() {
                    return this.options.endDate ? this.options.endDate.clone().getDateInstance() : null;
                }, l.Litepicker.prototype.setDate = function(t) {
                    this.setStartDate(t), "function" == typeof this.options.onSelect && this.options.onSelect.call(this, this.getDate());
                }, l.Litepicker.prototype.setStartDate = function(t) {
                    t && (this.options.startDate = new r.DateTime(t, this.options.format, this.options.lang), 
                    this.updateInput());
                }, l.Litepicker.prototype.setEndDate = function(t) {
                    t && (this.options.endDate = new r.DateTime(t, this.options.format, this.options.lang), 
                    this.options.startDate.getTime() > this.options.endDate.getTime() && (this.options.endDate = this.options.startDate.clone(), 
                    this.options.startDate = new r.DateTime(t, this.options.format, this.options.lang)), 
                    this.updateInput());
                }, l.Litepicker.prototype.setDateRange = function(t, e) {
                    this.triggerElement = void 0, this.setStartDate(t), this.setEndDate(e), 
                    this.updateInput(), "function" == typeof this.options.onSelect && this.options.onSelect.call(this, this.getStartDate(), this.getEndDate());
                }, l.Litepicker.prototype.gotoDate = function(t, e) {
                    void 0 === e && (e = 0);
                    var i = new r.DateTime(t);
                    i.setDate(1), this.calendars[e] = i.clone(), this.render();
                }, l.Litepicker.prototype.setLockDays = function(t) {
                    this.options.lockDays = r.DateTime.convertArray(t, this.options.lockDaysFormat), 
                    this.render();
                }, l.Litepicker.prototype.setHolidays = function(t) {
                    this.options.holidays = r.DateTime.convertArray(t, this.options.holidaysFormat), 
                    this.render();
                }, l.Litepicker.prototype.setPartiallyBookedDays = function(t) {
                    this.options.partiallyBookedDays = r.DateTime.convertArray(t, this.options.partiallyBookedDaysFormat), 
                    this.render();
                }, l.Litepicker.prototype.setBookedDays = function(t) {
                    this.options.bookedDays = r.DateTime.convertArray(t, this.options.bookedDaysFormat), 
                    this.render();
                }, l.Litepicker.prototype.setHighlightedDays = function(t) {
                    this.options.highlightedDays = r.DateTime.convertArray(t, this.options.highlightedDaysFormat), 
                    this.render();
                }, l.Litepicker.prototype.setOptions = function(t) {
                    delete t.element, delete t.elementEnd, delete t.parentEl, t.startDate && (t.startDate = new r.DateTime(t.startDate, this.options.format, this.options.lang)), 
                    t.endDate && (t.endDate = new r.DateTime(t.endDate, this.options.format, this.options.lang));
                    var e = o(o({}, this.options.dropdowns), t.dropdowns), i = o(o({}, this.options.buttonText), t.buttonText), n = o(o({}, this.options.tooltipText), t.tooltipText);
                    this.options = o(o({}, this.options), t), this.options.dropdowns = o({}, e), 
                    this.options.buttonText = o({}, i), this.options.tooltipText = o({}, n), 
                    !this.options.singleMode || this.options.startDate instanceof r.DateTime || (this.options.startDate = null, 
                    this.options.endDate = null), this.options.singleMode || this.options.startDate instanceof r.DateTime && this.options.endDate instanceof r.DateTime || (this.options.startDate = new r.DateTime(this.options.startDate, this.options.format, this.options.lang), 
                    this.options.endDate = null);
                    for (var s = 0; s < this.options.numberOfMonths; s += 1) {
                        var a = this.options.startDate ? this.options.startDate.clone() : new r.DateTime();
                        a.setDate(1), a.setMonth(a.getMonth() + s), this.calendars[s] = a;
                    }
                    this.options.lockDays.length && (this.options.lockDays = r.DateTime.convertArray(this.options.lockDays, this.options.lockDaysFormat)), 
                    this.options.holidays.length && (this.options.holidays = r.DateTime.convertArray(this.options.holidays, this.options.holidaysFormat)), 
                    this.options.partiallyBookedDays.length && (this.options.partiallyBookedDays = r.DateTime.convertArray(this.options.partiallyBookedDays, this.options.partiallyBookedDaysFormat)), 
                    this.options.bookedDays.length && (this.options.bookedDays = r.DateTime.convertArray(this.options.bookedDays, this.options.bookedDaysFormat)), 
                    this.options.highlightedDays.length && (this.options.highlightedDays = r.DateTime.convertArray(this.options.highlightedDays, this.options.highlightedDaysFormat)), 
                    this.render(), this.options.inlineMode && this.show(), this.updateInput();
                }, l.Litepicker.prototype.clearSelection = function() {
                    this.options.startDate = null, this.options.endDate = null, 
                    this.datePicked.length = 0, this.bookedDayAfterSelection = null, 
                    this.updateInput(), this.isShowning() && this.render();
                }, l.Litepicker.prototype.destroy = function() {
                    this.picker && this.picker.parentNode && (this.picker.parentNode.removeChild(this.picker), 
                    this.picker = null), this.backdrop && this.backdrop.parentNode && this.backdrop.parentNode.removeChild(this.backdrop);
                };
            },
            593: function(t, e) {
                "use strict";
                function i() {
                    return window.matchMedia("(orientation: portrait)").matches ? "portrait" : "landscape";
                }
                Object.defineProperty(e, "__esModule", {
                    value: !0
                }), e.findNestedMonthItem = e.getOrientation = e.isMobile = void 0, 
                e.isMobile = function() {
                    var t = "portrait" === i();
                    return window.matchMedia("(max-device-" + (t ? "width" : "height") + ": 480px)").matches;
                }, e.getOrientation = i, e.findNestedMonthItem = function(t) {
                    for (var e = t.parentNode.childNodes, i = 0; i < e.length; i += 1) if (e.item(i) === t) return i;
                    return 0;
                };
            },
            362: function(t, e) {
                "use strict";
                Object.defineProperty(e, "__esModule", {
                    value: !0
                });
            }
        }, e = {};
        function i(o) {
            var n = e[o];
            if (void 0 !== n) return n.exports;
            var s = e[o] = {
                id: o,
                exports: {}
            };
            return t[o].call(s.exports, s, s.exports, i), s.exports;
        }
        i.nc = void 0;
        var o = {};
        return function() {
            "use strict";
            var t = o;
            t.Litepicker = void 0;
            var e = i(506);
            Object.defineProperty(t, "Litepicker", {
                enumerable: !0,
                get: function() {
                    return e.Litepicker;
                }
            }), i(997), i(362), window.Litepicker = e.Litepicker, e.Litepicker;
        }(), o.Litepicker;
    }();
});

document.addEventListener("DOMContentLoaded", function(event) {
    if (typeof calendarData !== "undefined") {
        let globalCalendarData = calendarData;
        let globalPickedStartDate = false;
        const fadeOutCalendar = () => {
            jQuery("#litepicker .litepicker .container__days").css("visibility", "hidden");
        };
        const fadeInCalendar = () => {
            jQuery("#litepicker .litepicker .container__days").fadeTo("fast", 1);
        };
        const initSelectHandler = () => {
            const startSelect = bookingForm.find("select[name=repetition-start]");
            startSelect.change(function() {
                updateEndSelectTimeOptions();
            });
        };
        const updateEndSelectTimeOptions = () => {
            const bookingForm = jQuery("#booking-form");
            const startSelect = bookingForm.find("select[name=repetition-start]");
            const endSelect = bookingForm.find("select[name=repetition-end]");
            const startValue = startSelect.val();
            let bookedElementBefore = false;
            let firstAvailableOptionSelected = false;
            endSelect.find("option").each(function() {
                if (jQuery(this).val() < startValue || bookedElementBefore || this.dataset.booked == "true") {
                    jQuery(this).attr("disabled", "disabled");
                    jQuery(this).prop("selected", false);
                } else {
                    jQuery(this).removeAttr("disabled");
                    if (!firstAvailableOptionSelected) {
                        jQuery(this).prop("selected", true);
                        firstAvailableOptionSelected = true;
                    }
                }
                if (jQuery(this).val() > startValue && this.dataset.booked == "true") {
                    bookedElementBefore = true;
                }
            });
        };
        const updateSelectSlots = (select, slots, type = "start", fullday = false) => {
            select.empty().attr("required", "required");
            jQuery.each(slots, function(index, slot) {
                let option = new Option(slot["timestart"] + " - " + slot["timeend"], slot["timestamp" + type], fullday, fullday);
                if (slot["disabled"]) {
                    option.disabled = true;
                }
                if (slot["timeframe"]["locked"]) {
                    option.disabled = true;
                    option.dataset.booked = true;
                }
                select.append(option);
            });
        };
        const isMobile = () => {
            const isPortrait = getOrientation() === "portrait";
            return window.matchMedia(`(max-device-${isPortrait ? "width" : "height"}: ${480}px)`).matches;
        };
        const getOrientation = () => {
            if (window.matchMedia("(orientation: portrait)").matches) {
                return "portrait";
            }
            return "landscape";
        };
        const initStartSelect = date => {
            globalPickedStartDate = date;
            const day1 = globalCalendarData["days"][moment(date).format("YYYY-MM-DD")];
            const startDate = moment(date).format("DD.MM.YYYY");
            jQuery(".time-selection.repetition-start").find(".hint-selection").hide();
            jQuery(".time-selection.repetition-end").find(".hint-selection").show();
            jQuery("#resetPicker").css("display", "inline-block");
            jQuery("#calendarNotice").css("display", "inherit");
            let endSelectData = jQuery("#booking-form select[name=repetition-end]," + "#booking-form .time-selection.repetition-end .date");
            endSelectData.hide();
            jQuery("#booking-form input[type=submit]").attr("disabled", "disabled");
            let startSelect = jQuery("#booking-form select[name=repetition-start]");
            jQuery(".time-selection.repetition-start span.date").text(startDate);
            updateSelectSlots(startSelect, day1["slots"], "start", day1["fullDay"]);
            if (day1["fullDay"]) {
                jQuery(".time-selection.repetition-start").find("select").hide();
            } else {
                jQuery(".time-selection.repetition-start").find("select").show();
            }
        };
        const updateStartSelect = () => {
            const sameDay = jQuery("div.repetition-start span.date").text() === jQuery("div.repetition-end span.date").text();
            if (!sameDay) {
                jQuery.fn.reverse = [].reverse;
                const startSelect = jQuery("#booking-form select[name=repetition-start]");
                var startHasDisabled = false;
                jQuery("option", startSelect).each(function() {
                    if (jQuery(this).attr("disabled") === "disabled") {
                        startHasDisabled = true;
                    }
                });
                if (startHasDisabled) {
                    var lastOption = false;
                    jQuery("option", startSelect).reverse().each(function() {
                        let self = jQuery(this);
                        if (lastOption && lastOption.attr("disabled") === "disabled") {
                            self.attr("disabled", "disabled");
                        } else {
                            if (self.attr("disabled") !== "disabled") {
                                self.attr("selected", "selected");
                            }
                            lastOption = self;
                        }
                    });
                }
            }
        };
        const initEndSelect = date => {
            const day2 = globalCalendarData["days"][moment(date).format("YYYY-MM-DD")];
            const endDate = moment(date).format("DD.MM.YYYY");
            jQuery(".time-selection.repetition-end").find(".hint-selection").hide();
            let endSelect = jQuery("#booking-form select[name=repetition-end]");
            jQuery(".time-selection.repetition-end span.date").text(endDate);
            updateSelectSlots(endSelect, day2["slots"], "end", day2["fullDay"]);
            let endSelectData = jQuery("#booking-form select[name=repetition-end]," + "#booking-form .time-selection.repetition-end .date");
            endSelectData.show();
            jQuery("#booking-form input[type=submit]").removeAttr("disabled");
            updateEndSelectTimeOptions();
            if (day2["fullDay"]) {
                jQuery(".time-selection.repetition-end").find("select").hide();
            } else {
                jQuery(".time-selection.repetition-end").find("select").show();
            }
        };
        const countOverbookedDays = (start, end) => {
            const startDay = globalCalendarData["days"][moment(start).format("YYYY-MM-DD")];
            const endDay = globalCalendarData["days"][moment(end).format("YYYY-MM-DD")];
            let startDate = globalCalendarData["days"][moment(start).format("YYYY-MM-DD")];
            let endDate = globalCalendarData["days"][moment(end).format("YYYY-MM-DD")];
            let overbookedDays = 0;
            for (let day in globalCalendarData["days"]) {
                if (moment(day).isBetween(moment(start).format("YYYY-MM-DD"), moment(end).format("YYYY-MM-DD"))) {
                    if (globalCalendarData["days"][day]["holiday"] || globalCalendarData["days"][day]["locked"]) {
                        overbookedDays++;
                    }
                }
            }
            jQuery('input[name="days-overbooked"]').val(overbookedDays);
        };
        const getCalendarColumns = () => {
            let columns = 2;
            if (isMobile()) {
                columns = 1;
                if (window.innerHeight < window.innerWidth) {
                    columns = 2;
                }
            }
            return columns;
        };
        const updateCalendarColumns = picker => {
            picker.setOptions({
                numberOfMonths: getCalendarColumns(),
                numberOfColumns: getCalendarColumns()
            });
        };
        let numberOfMonths = getCalendarColumns();
        let numberOfColumns = numberOfMonths;
        let picker = false;
        const initPicker = () => {
            picker = new Litepicker({
                element: document.getElementById("litepicker"),
                minDate: moment().format("YYYY-MM-DD"),
                startDate: moment().isAfter(globalCalendarData["startDate"]) ? moment().format("YYYY-MM-DD") : globalCalendarData["startDate"],
                scrollToDate: true,
                inlineMode: true,
                firstDay: 1,
                countLockedDays: globalCalendarData["countLockDaysInRange"],
                countLockedDaysMax: globalCalendarData["countLockDaysMaxDays"],
                lang: globalCalendarData["lang"],
                numberOfMonths: numberOfMonths,
                numberOfColumns: numberOfColumns,
                moveByOneMonth: true,
                singleMode: false,
                showWeekNumbers: false,
                autoApply: true,
                bookedDaysInclusivity: "[]",
                anyBookedDaysAsCheckout: false,
                disallowBookedDaysInRange: true,
                disallowPartiallyBookedDaysInRange: true,
                disallowLockDaysInRange: globalCalendarData["disallowLockDaysInRange"],
                disallowHolidaysInRange: globalCalendarData["disallowLockDaysInRange"],
                mobileFriendly: true,
                mobileCalendarMonthCount: globalCalendarData["mobileCalendarMonthCount"],
                selectForward: true,
                useResetBtn: true,
                maxDays: globalCalendarData["maxDays"],
                buttonText: {
                    apply: globalCalendarData["i18n.buttonText.apply"],
                    cancel: globalCalendarData["i18n.buttonText.cancel"]
                },
                onAutoApply: datePicked => {
                    if (datePicked) {
                        jQuery("#booking-form").show();
                        jQuery(".cb-notice.date-select").hide();
                    }
                },
                resetBtnCallback: () => {
                    jQuery("#booking-form").hide();
                    jQuery(".cb-notice.date-select").show();
                },
                onChangeMonth: function(date, idx) {
                    fadeOutCalendar();
                    const startDate = moment(date.format("YYYY-MM-DD")).format("YYYY-MM-DD");
                    const calStartDate = moment(date.format("YYYY-MM-DD")).date(0).format("YYYY-MM-DD");
                    const calEndDate = moment(date.format("YYYY-MM-DD")).add(numberOfMonths, "months").date(1).format("YYYY-MM-DD");
                    jQuery.post(cb_ajax.ajax_url, {
                        _ajax_nonce: cb_ajax.nonce,
                        action: "cb_calendar_data",
                        item: jQuery("#booking-form input[name=item-id]").val(),
                        location: jQuery("#booking-form input[name=location-id]").val(),
                        sd: calStartDate,
                        ed: calEndDate
                    }, function(data) {
                        jQuery.extend(globalCalendarData.days, data.days);
                        updatePicker(data);
                        picker.gotoDate(startDate);
                    });
                }
            });
            jQuery("#litepicker .litepicker").hide();
            jQuery(window).on("orientationchange", function(event) {
                updateCalendarColumns(picker);
            });
        };
        const updatePicker = globalCalendarData => {
            fadeOutCalendar();
            picker.setOptions({
                minDate: moment().isAfter(globalCalendarData["startDate"]) ? moment().format("YYYY-MM-DD") : globalCalendarData["startDate"],
                maxDate: globalCalendarData["endDate"],
                startDate: moment().isAfter(globalCalendarData["startDate"]) ? moment().format("YYYY-MM-DD") : globalCalendarData["startDate"],
                days: globalCalendarData["days"],
                maxDays: globalCalendarData["maxDays"],
                lockDays: globalCalendarData["lockDays"],
                countLockedDays: globalCalendarData["countLockDaysInRange"],
                bookedDays: globalCalendarData["bookedDays"],
                partiallyBookedDays: globalCalendarData["partiallyBookedDays"],
                highlightedDays: globalCalendarData["highlightedDays"],
                holidays: globalCalendarData["holidays"],
                onDaySelect: function(date, datepicked) {
                    if (datepicked >= 0) {
                        let bookingForm = jQuery("#booking-form");
                        bookingForm.show();
                        if (datepicked == 1) {
                            initStartSelect(date);
                            jQuery(".cb-notice.date-select").hide();
                        }
                        if (datepicked == 2) {
                            initEndSelect(date);
                            updateStartSelect();
                            countOverbookedDays(globalPickedStartDate, date);
                        }
                    }
                },
                onSelect: function(date1, date2) {
                    let bookingForm = jQuery("#booking-form");
                    bookingForm.show();
                    jQuery(".cb-notice.date-select").hide();
                    const day1 = globalCalendarData["days"][moment(date1).format("YYYY-MM-DD")];
                    const day2 = globalCalendarData["days"][moment(date2).format("YYYY-MM-DD")];
                    initEndSelect(date2);
                    if (!day1["fullDay"] || !day2["fullDay"]) {
                        initSelectHandler();
                    }
                }
            });
            fadeInCalendar();
        };
        const resetDatepickerSelection = () => {
            picker.clearSelection();
            globalPickedStartDate = false;
            jQuery(".hint-selection").show();
            jQuery(".time-selection .date").text("");
            jQuery(".time-selection select").hide();
            jQuery("#resetPicker").hide();
            jQuery("#calendarNotice").hide();
            jQuery("#booking-form input[type=submit]").attr("disabled", "disabled");
        };
        jQuery("#resetPicker").on("click", function(e) {
            e.preventDefault();
            resetDatepickerSelection();
        });
        let bookingForm = jQuery("#booking-form");
        if (bookingForm.length) {
            initPicker();
            updatePicker(globalCalendarData);
        }
    }
});