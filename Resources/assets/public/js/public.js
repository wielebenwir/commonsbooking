!function($) {
    var allPanels = $(".accordion > div.content").hide();
    $(".accordion > dt > a").click(function() {
        return allPanels.slideUp(), $(this).parent().next().slideDown(), !1;
    });
}(jQuery), function($) {
    "use strict";
    $(function() {
        if ($("#cb2-actionbar").length) {
            var time_start, time_end, day_start, day_end, selection_candidates = [], bb_el = $("#cb2-actionbar"), placer_el = ($(".cb2-calendar"), 
            $("#cb2-actionbar-placer"));
            $.fn.updateClass = function(classname) {
                $(this).closest("li").addClass(classname), $(this).attr("checked", !0);
            }, $("#bb-submit-button").click(function(e) {
                $("#cb2-booking-form").submit(), window.console && console.log("submitted"), e.preventDefault();
            }), $(window).on("load resize scroll", function(e) {
                var placer_bottom = placer_el.offset().top, placer_left = placer_el.offset().left, placer_width = placer_el.width(), viewportBottom = $(window).scrollTop() + $(window).height(), breakpoint = placer_bottom + bb_el.height() + 50;
                bb_el.css({
                    width: placer_width,
                    opacity: 1
                }), breakpoint <= viewportBottom ? bb_el.css({
                    position: "relative",
                    left: 0
                }) : bb_el.css({
                    position: "fixed",
                    bottom: 5,
                    left: placer_left
                });
            }), resetAll(), $(".cb2-selectable > .cb2-details").click(function(e) {
                var selection_container = $(this).closest(".cb2-selection-container"), checkbox = $(this).children(".cb2-periodinst-selector"), target = $(e.target), clicked_input = target.is(checkbox), clicked_id = target.attr("id"), bcontinue = !0, c_els_between = [];
                if (clicked_input) {
                    -1 !== $.inArray(clicked_id, selection_candidates) ? selection_candidates.splice($.inArray(clicked_id, selection_candidates), 1) : selection_candidates.push(clicked_id);
                    var c_els = $("#" + selection_candidates.join(",#"));
                    if (1 < c_els.length) {
                        var noninclude_errors, c_el_first_object, c_el_last_object, containers = selection_container.find(".cb2-selectable, .cb2-not-includable"), c_el_first = c_els.first(), c_el_last = c_els.last(), max_period_usage = cb2_settings.bookingoptions_max_period_usage, min_period_usage = cb2_settings.bookingoptions_min_period_usage;
                        c_els = $.merge(c_el_first, c_el_last), c_els_between = containers.slice(containers.index(c_el_first.closest("li")) + 1, containers.index(c_el_last.closest("li"))), 
                        c_el_first.attr("properties") && c_el_last.attr("properties") && (c_el_first_object = JSON.parse(c_el_first.attr("properties")), 
                        c_el_last_object = JSON.parse(c_el_last.attr("properties")), window.console && console.log(c_el_first_object, c_el_last_object), 
                        c_el_first_object && c_el_last_object && (c_el_first_object.period_entity.ID != c_el_last_object.period_entity.ID ? (showNotice(cb2_settings.bookingbartemplates_notice_across_timeframes ? cb2_settings.bookingbartemplates_notice_across_timeframes : "across timeframes:" + c_el_first_object.period_entity.ID + " =&gt; " + c_el_last_object.period_entity.ID), 
                        bcontinue = !1) : (max_period_usage = c_el_first_object.period_entity.max_period_usage, 
                        min_period_usage = c_el_first_object.period_entity.min_period_usage))), bcontinue && c_els_between.each(function(index, el) {
                            if ($(el).hasClass("cb2-not-includable")) {
                                bcontinue = !1, noninclude_errors = 1;
                                for (var i = 0; i < c_els.length; i++) {
                                    var obj = c_els[i];
                                    -1 !== clicked_id.indexOf(obj.id) && c_els.splice(i, 1);
                                }
                                e.preventDefault();
                            }
                        }), noninclude_errors && showNotice(cb2_settings.bookingbartemplates_notice_non_includable ? cb2_settings.bookingbartemplates_notice_non_includable : "Cannot book across these slots");
                    }
                    if (bcontinue && c_els.length + c_els_between.length > max_period_usage) {
                        window.console && console.info("exceeded max period usage of [" + max_period_usage + "]");
                        var notice = cb2_settings.bookingbartemplates_notice_max_slots.replace(/{{max-slots}}/g, max_period_usage);
                        showNotice(cb2_settings.bookingbartemplates_notice_max_slots ? notice : "Maximum bookable slots: " + max_period_usage, max_period_usage), 
                        bcontinue = !1;
                        for (var i = 0; i < c_els.length; i++) {
                            var obj = c_els[i];
                            -1 !== clicked_id.indexOf(obj.id) && c_els.splice(i, 1);
                        }
                        e.preventDefault();
                    }
                    if (bcontinue && c_els.length + c_els_between.length < min_period_usage) {
                        window.console && console.info("exceeded min period usage of [" + min_period_usage + "]"), 
                        showNotice(cb2_settings.bookingbartemplates_notice_min_slots ? cb2_settings.bookingbartemplates_notice_min_slots : "Minimum bookable slots: " + min_period_usage, min_period_usage), 
                        bcontinue = !1;
                        for (i = 0; i < c_els.length; i++) {
                            obj = c_els[i];
                            -1 !== clicked_id.indexOf(obj.id) && c_els.splice(i, 1);
                        }
                        e.preventDefault();
                    }
                    if (!0 === bcontinue) {
                        resetAll(), c_els.attr("checked", !0);
                        var els_to_update = c_els.closest("li");
                        els_to_update.addClass("cb2-selected"), 1 === els_to_update.length ? els_to_update.addClass("selection-single") : (els_to_update.first().addClass("selection-first"), 
                        els_to_update.last().addClass("selection-last")), c_els_between.length && c_els_between.addClass("cb2-range-selected selection-middle"), 
                        time_start = c_els.first().parents(".cb2-details").find(".cb2-period-start").text(), 
                        time_end = c_els.last().parents(".cb2-details").find(".cb2-period-end").text(), 
                        day_start = c_els.first().parents("li.cb2_day").children(".cb2-day-title").text(), 
                        day_end = c_els.last().parents("li.cb2_day").children(".cb2-day-title").text();
                    } else e.preventDefault();
                    time_start ? ($("#bb-intro").css("opacity", 0), $("#bb-selection").animate({
                        opacity: 1
                    }, 400), $("#bb-pickup-date").text(day_start), $("#bb-pickup-time").text(time_start), 
                    $("#bb-return-date").text(day_end), $("#bb-return-time").text(time_end)) : ($("#bb-intro").animate({
                        opacity: 1
                    }, 400), $("#bb-selection").css("opacity", 0)), selection_candidates = [], c_els.each(function() {
                        selection_candidates.push(this.id);
                    });
                }
                e.stopPropagation();
            });
        }
        function showNotice(notice) {
            var args = Array.prototype.slice.call(arguments);
            args.shift(), $("<li>" + notice.format.apply(notice, args) + "</li>").appendTo("#bb-error").addClass("animated shake").delay(4e3).queue(function() {
                $(this).remove();
            });
        }
        function resetAll() {
            $(".cb2-periodinst-selector").removeAttr("checked"), $(".cb2_prdinst-tf ").removeClass("cb2-selected cb2-range-selected selection-first selection-middle selection-last ");
        }
    });
}(jQuery), function($) {
    "use strict";
    $(function() {
        $(document).ready(function() {
            $(".cb2-selector").click(function() {
                var jsObject = {}, jParameters = $(this).find(".cb2-selector-value");
                $(this).hasClass("cb2-selector-value") && (jParameters = jParameters.andSelf()), 
                jParameters.length && (jParameters.each(function() {
                    jsObject[$(this).attr("name")] = $(this).val();
                }), $(this).closest(".cb2-selection-container").find(".cb2-selected").removeClass("cb2-selected"), 
                $(this).addClass("cb2-selected"), window.console && console.log(jsObject), $(this).trigger("cb2-selected", jsObject));
            }), $(document).on("cb2-selected", function(event, paramNewValues) {
                $(".cb2-listen-on").each(function() {
                    var sListenToField, self = this, jsObject = {}, paramNewValuesFiltered = {}, sListenToFields = $(this).find(":input[name=listen-to-fields]").val(), aListenToFields = sListenToFields ? sListenToFields.split(",") : {};
                    if (sListenToFields && "all" != sListenToFields && aListenToFields.length) {
                        for (var i = 0; i < aListenToFields.length; i++) sListenToField = aListenToFields[i], 
                        paramNewValues[sListenToField] && (paramNewValuesFiltered[sListenToField] = paramNewValues[sListenToField]);
                        paramNewValues = paramNewValuesFiltered, window.console && console.log(paramNewValues);
                    }
                    for (var key in $(this).children(":input").each(function() {
                        jsObject[$(this).attr("name")] = $(this).val();
                    }), jsObject = jQuery.extend(jsObject, paramNewValues)) jsObject[key] || delete jsObject[key];
                    window.console && console.log(this, jsObject, paramNewValues), jsObject.action || (jsObject.action = "cb2_ajax_shortcode");
                    var ajax_url = jsObject["ajax-url"];
                    ajax_url ? ($(this).addClass("cb2-refreshing"), $.post(ajax_url, jsObject, function(response) {
                        var jNewContentChildren = $(response).children();
                        $(self).replaceWith(jNewContentChildren), geo_hcard_map_init();
                    })) : window.console && console.error("jsObject has no ajax URL for CB2 container [" + shortcodeId + "]");
                });
            }), 0 < $("li.cb2-template-popup-item").length && $("li.cb2-template-popup-item").each(function() {
                console.log("bound"), console.log($(this)), $(this).on("click", function() {
                    console.log($(this));
                    $(this).find("a").attr("href");
                });
            }), window.cb2 = {}, cb2.calendarStyles = function() {
                $(".cb2-calendar-grouped").length < 1 || (450 <= $(".cb2-calendar-grouped").outerWidth() ? $(".cb2-calendar-grouped").addClass("cb2-calendar-grouped-large") : $(".cb2-calendar-grouped").removeClass("cb2-calendar-grouped-large"));
            }, cb2.calendarTooltips = function() {
                $(".cb2-calendar-grouped").length < 1 || $('.cb2-slot[data-state="allow-booking"] ').parents("li.cb2-date").each(function(i, elem) {
                    var template = document.createElement("div");
                    template.id = $(elem).attr("id");
                    var html = "<div><ul>";
                    $(elem).find('[data-state="allow-booking"]').each(function(j, slot) {
                        html += "<li>", $(slot).attr("data-item-thumbnail") && (html += '<img src="' + $(slot).attr("data-item-thumbnail") + '">'), 
                        html += '<a href="' + $(slot).attr("data-item-thumbnail") + '">', html += $(slot).attr("data-item-title"), 
                        html += "</a></li>";
                    }), html += "</ul></div>", template.innerHTML = html, tippy("#" + template.id, {
                        appendTo: document.querySelector(".cb2-calendar-grouped"),
                        arrow: !0,
                        html: template,
                        interactive: !0,
                        theme: "cb2-calendar",
                        trigger: "click"
                    });
                });
            }, cb2.init = function() {
                cb2.calendarStyles(), cb2.calendarTooltips();
            }, cb2.resize = function() {
                cb2.calendarStyles();
            }, cb2.init(), $(window).on("resize", cb2.resize);
        });
    });
}(jQuery), String.prototype.format || (String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) {
        return void 0 !== args[number] ? args[number] : match;
    });
}), function($) {
    "use strict";
    $(function() {
        $(".request-confirmation").on("click", function(e) {
            return confirm($(this).data("confirmationstring"));
        });
    });
}(jQuery);