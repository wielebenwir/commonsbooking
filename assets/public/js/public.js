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