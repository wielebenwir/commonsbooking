function cb_process() {
    var $ = jQuery, jRoot = $(this);
    $("body.cb-WP_DEBUG-on").length;
    jRoot.find("label p, label span").click(function(e) {
        var idTarget, jLabel = $(this).parent("label");
        jLabel.length && (idTarget = jLabel.attr("for"), jLabel.parent().find("#" + idTarget + ":visible").click());
    }), jRoot.find("form").on("submit", function() {
        var jSubmit = $(this).find(".cb-submit");
        jSubmit.val(jSubmit.val() + " ..."), $(window).off("beforeunload.edit-post"), setTimeout(function() {
            jSubmit.addClass("disabled");
        });
    }), jRoot.find("form").submit(function() {
        $(this).find(".cb-form-disable").attr("disabled", "1"), $(this).find("li.cb-selected").each(function() {
            $(this).closest("ul").find("li:not(.cb-selected) input").attr("disabled", "1");
        });
    }), jRoot.find("a.thickbox").each(function() {
        var href = $(this).attr("href");
        -1 == href.indexOf("cb_load_normal_page") && -1 == href.indexOf("cb_load_template") && (href += (-1 == href.indexOf("?") ? "?" : "&") + "cb_load_normal_page=1", 
        $(this).attr("href", href));
    }), jRoot.find(".cmb2-id-period-status-type-ID input").click(function() {
        var jPopup = $(this).closest("body, .cb-popup"), jPeriodStatusType = $(this).parent().find("label"), css_class = jPopup.attr("class"), type = jPeriodStatusType.text().toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), id = $(this).val();
        css_class = (css_class = (css_class = (css_class = " " + css_class + " ").replace(/ +cb-status-[^ ]+/g, " ")) + "cb-status-" + type + " cb-status-" + id).replace(/ +/, " ").trim(), 
        jPopup.attr("class", css_class), jPopup.css("background-color", "");
    }), jRoot.find(".cb-form").on("submit", function() {
        var self = this;
        setTimeout(function() {
            var jSubmit = $(self).find(":input[type=submit]");
            jSubmit.val(jSubmit.val() + " ..."), jSubmit.after(" <progress></progress> "), $(self).find(":input").addClass("disabled");
        }, 0);
    }), jRoot.find(".cb-javascript-form input[type=button]").click(function() {
        var sRedirect = document.location, sQuery = unescape(document.location.search.replace(/^\?/, "")), aQuery = sQuery.split("&"), jInputs = $(this).closest(".cb-javascript-form").find(":input");
        jInputs.each(function() {
            var sJSName = $(this).attr("js-name");
            if (sJSName ? $(this).attr("name", sJSName) : sJSName = $(this).attr("name"), sJSName) {
                sJSName = sJSName.replace(/\[\d+\]/, "[]");
                for (var i = aQuery.length; 0 < i; ) aQuery[--i].replace(/=.*/, "").replace(/\[[0-9]+\]/, "[]") == sJSName && aQuery.splice(i, 1);
            }
        }), sQuery = aQuery.join("&"), sQuery += "&", sQuery += jInputs.serialize(), sQuery += "&redirect=" + escape(sRedirect), 
        document.location = document.location.pathname + "?" + sQuery;
    }), jRoot.find(".cb-template-type-available > .cb-details").click(function(e) {
        var container = $(this).parent(), checkbox = $(this).children(".cb-periodinst-selector"), cssClass = $(this).attr("class").trim(), clicked_input = $(e.target).is(checkbox), is_checked = checkbox.attr("checked");
        clicked_input && (is_checked = !is_checked), is_checked ? (clicked_input || checkbox.removeAttr("checked"), 
        container.attr("class", cssClass.replace(/cb-selected/, ""))) : (clicked_input || checkbox.attr("checked", "1"), 
        container.attr("class", cssClass + " cb-selected")), clicked_input || e.preventDefault();
    });
    var original_recurrence_type, previous_datetime_part_period_start_date = $("#datetime_part_period_start_date").val();
    $("#datetime_part_period_end_date").val();
    function cb_next_if_selects_complete() {
        var allComplete = !0, jSelects = $(this).find("select:visible");
        jSelects.each(function() {
            var value = $(this).val();
            return value && "__Null__" != value || (allComplete = !1), !allComplete && window.console && console.info($(this).attr("name") + " <select> not complete"), 
            allComplete;
        }), allComplete && $(".cb-popup-form-next").click(), allComplete && window.console && (console.info("all complete" + (jSelects.length ? "" : " (none found)")), 
        jSelects.length && console.log(jSelects));
    }
    jRoot.find("#datetime_part_period_start_date").change(function() {
        var start_date = new Date($("#datetime_part_period_start_date").val()), end_date = new Date($("#datetime_part_period_end_date").val()), prev_start_date = new Date(previous_datetime_part_period_start_date), diff_year = start_date.getFullYear() - prev_start_date.getFullYear(), diff_month = start_date.getMonth() - prev_start_date.getMonth(), diff_day = start_date.getDate() - prev_start_date.getDate(), jSubmit = $(this).closest("body,.cb-popup").find(".cb-popup-form-save");
        jSubmit.removeAttr("disabled"), isNaN(start_date.getTime()) ? ($(this).addClass("cb-error"), 
        jSubmit.attr("disabled", "1")) : ($("#datetime_part_period_start_date, #datetime_part_period_end_date").hasClass("cb-error") || (end_date.setFullYear(end_date.getFullYear() + diff_year), 
        end_date.setMonth(end_date.getMonth() + diff_month), end_date.setDate(end_date.getDate() + diff_day), 
        $("#datetime_part_period_end_date").val(cb_iso_date(end_date)), window.console && console.info(diff_year + "-" + diff_month + "-" + diff_day)), 
        $("#datetime_part_period_start_date, #datetime_part_period_end_date").removeClass("cb-error"), 
        end_date < start_date && ($("#datetime_part_period_end_date").addClass("cb-error"), 
        jSubmit.attr("disabled", "1")), previous_datetime_part_period_start_date = $(this).val(), 
        $("#datetime_part_period_end_date").val()), !0;
    }), jRoot.find("#datetime_part_period_end_date").change(function() {
        var start_date = new Date($("#datetime_part_period_start_date").val()), end_date = new Date($("#datetime_part_period_end_date").val()), jSubmit = $(this).closest("body,.cb-popup").find(".cb-popup-form-save");
        jSubmit.removeAttr("disabled"), isNaN(end_date.getTime()) ? ($(this).addClass("cb-error"), 
        jSubmit.attr("disabled", "1")) : ($("#datetime_part_period_start_date, #datetime_part_period_end_date").removeClass("cb-error"), 
        end_date < start_date && ($("#datetime_part_period_end_date").addClass("cb-error"), 
        jSubmit.attr("disabled", "1")), $(this).val()), !0;
    }), jRoot.find("#cb-SOT").click(function() {
        original_recurrence_type = $(".cmb2-id-recurrence-type :input[checked]"), $("#recurrence_type1").click(), 
        $(".cmb2-id-recurrence-type").slideUp(), $("#datetime_part_period_start_date").val($("#datetime_period_inst_start_date").val()), 
        $("#datetime_part_period_end_date").val($("#datetime_period_inst_end_date").val());
    }), jRoot.find("#cb-SFH, #cb-SAI").click(function() {
        $(".cmb2-id-recurrence-type").slideDown(), original_recurrence_type && original_recurrence_type.click();
    }), jRoot.find("#cb-save-types input").click(function() {
        var updatestring = $('label[for="' + this.id + '"]').html();
        $("#cb-follow-cb-save-types").html(updatestring);
    }), jRoot.find(".cb-nexts").each(function() {
        var nexts = $(this).find("li"), ids = "", panels = (nexts.find("a").each(function() {
            ids += (ids ? "," : "") + $(this).attr("href");
        }), nexts.closest(".cb-popup,#post-body").find(ids));
        if (panels.css("overflow-y", "hidden"), panels.hide(), $(this).closest(".cb-popup,body").addClass("cb-with-nexts"), 
        $(this).closest(".cb-popup,#post-body").removeClass("columns-2"), nexts.click(function(e) {
            var next = $(this), href = next.find("a").attr("href"), newPanel = next.closest(".cb-popup,body").find(href), oldPanel = panels.filter(":visible");
            next.hasClass("cb-selected") || (nexts.removeClass("cb-selected"), nexts.addClass("cb-unselected"), 
            next.addClass("cb-selected"), next.removeClass("cb-unselected"), newPanel.css("width", "100%"), 
            oldPanel.css("width", "100%"), oldPanel.css("height", oldPanel.height()), newPanel.css("height", newPanel.height()), 
            newPanel.css("width", "0%"), newPanel.show(), oldPanel.animate({
                width: "0%"
            }, 500, function() {
                oldPanel.hide(), oldPanel.css("height", "auto");
            }), newPanel.animate({
                width: "100%"
            }, 500, function() {
                newPanel.css("height", "auto"), newPanel.focus();
            }), next.hasClass("cb-last") ? ($(".cb-popup-form-next").hide(), $(".cb-popup-form-save").show()) : ($(".cb-popup-form-next").show(), 
            $(".cb-popup-form-save").hide())), e.preventDefault();
        }), nexts.length) {
            var next = nexts.filter(".cb-selected");
            next.length || (next = nexts.eq(0));
            var href = next.find("a").attr("href"), panel = next.closest(".cb-popup,body").find(href);
            next.addClass("cb-selected"), next.removeClass("cb-unselected"), panel.show();
        }
    }), $(".cb-popup a.thickbox").click(function(e) {
        var sPopupTypes = $("#TB_window").attr("class").match(/cb-popup-[^ ]+/g).join(" ");
        $("#TB_window").attr("class", "cb-popup " + sPopupTypes);
    }), jRoot.find(".cb-popup-form-next").click(function() {
        $(this).closest(".cb-popup,body").find(".cb-nexts li.cb-selected").next().click();
    }), $(".cb-popup-add #cb-tab-type li").click(function() {
        $(".cb-popup-form-next").click();
    }), jRoot.find("#cb-tab-objects").change(cb_next_if_selects_complete), jRoot.find("#cb-tab-objects:visible").each(cb_next_if_selects_complete), 
    $(document).find(".toplevel_page_cb-menu > .wp-submenu > li").each(function() {
        var href, matches;
        (href = $(this).children("a").attr("href")) && ((matches = href.match(/[?&]page=([^&]+)/)) ? matches.length && $(this).addClass(matches[1]) : href.match(/[a-zA-Z_0-9]+/) && $(this).addClass(href));
    }), jRoot.find(".cmb2-radio-list > li").each(function() {
        var jLI = $(this), jInput = $(this).children("input"), jLabel = $(this).children("label"), name = jInput.attr("name");
        "period_status_type_ID" == name && (name = "status");
        var css_name = name.toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), id = jInput.val(), type = jLabel.text().replace(/ .*$/, "").toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), stub = "cb-" + css_name + "-";
        jLI.addClass(stub + type), jLI.addClass(stub + id);
    }), jRoot.find("input[type=radio]").click(function() {
        $(this).closest("ul").children("li").removeClass("cb-selected"), $(this).closest("li").addClass("cb-selected");
    }), jRoot.find("input[type=radio]:checked").each(function() {
        $(this).closest("ul").children("li").removeClass("cb-selected"), $(this).closest("li").addClass("cb-selected");
    }), jRoot.find(".cb-add-class-advanced").click(function() {
        $(this).closest("body,.cb-popup,.cb-panel").addClass("cb-advanced");
    }), jRoot.find(".cb-set-href-querystring").change(function() {
        var name = $(this).attr("name"), value = $(this).val().trim(), text = $(this).find("option:selected").html().trim(), jHref = $(this).closest("a"), title = jHref.attr("title"), parts = jHref.attr("href").split("?"), url = parts[0].trim(), qs = 1 < parts.length ? parts[1].trim() : "", qsParts = qs.split("&"), css_class = "cb-follow cb-follow-" + name.replace(/_/g, "-").replace(/[_-]ID$/g, "").replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), found = !1;
        for (var i in qsParts) {
            if (qsParts[i].split("=")[0] == name) {
                qsParts[i] = name + "=" + value, found = !0;
                break;
            }
        }
        found || (qsParts[qsParts.length] = name + "=" + value), title = title.replace(/: .*/, ""), 
        qs = qsParts.join("&"), jHref.attr("href", url + "?" + qs), jHref.attr("title", title + ': <span class="' + css_class + '">' + text + "</span>");
    }), jRoot.find(".cb-tabs").each(function() {
        var tabs = $(this).find("li"), ids = "", panels = (tabs.find("a").each(function() {
            ids += (ids ? "," : "") + $(this).attr("href");
        }), tabs.closest(".cb-popup,body").find(ids));
        if (panels.hide(), $(this).closest(".cb-popup,body").addClass("cb-with-tabs"), $(this).closest(".cb-popup,#post-body").removeClass("columns-2"), 
        $(this).addClass("cb-processed"), tabs.click(function(e) {
            var tab = $(this), href = tab.find("a").attr("href"), panel = tab.closest(".cb-popup,body").find(href);
            tabs.removeClass("cb-selected"), tabs.addClass("cb-unselected"), panels.hide(), 
            tabs.each(function() {
                var other_href = $(this).find("a").attr("href");
                other_href = other_href.replace(/^#/, ""), $(this).closest(".cb-popup,body").removeClass("cb-tabs-" + other_href + "-selected");
            }), href = href.replace(/^#/, ""), tab.closest(".cb-popup,body").addClass("cb-tabs-" + href + "-selected"), 
            tab.addClass("cb-selected"), tab.removeClass("cb-unselected"), panel.focus(), panel.show(), 
            e.preventDefault();
        }), tabs.length) {
            var tab = tabs.filter(".cb-selected");
            tab.length || (tab = tabs.eq(0));
            var href = tab.find("a").attr("href"), panel = tab.closest(".cb-popup,body").find(href);
            tab.addClass("cb-selected"), tab.removeClass("cb-unselected"), href = href.replace(/^#/, ""), 
            tab.closest(".cb-popup,body").addClass("cb-tabs-" + href + "-selected"), panel.show();
        }
    }), jRoot.find(".cb-popup-form-trash").click(function() {
        var self = this, form = $(self).closest(".cb-ajax-edit-form"), data = form.find(":input").serialize(), action = form.attr("action-trash");
        $(self).attr("disabled", "1"), $(self).parents(".cb-popup, body").addClass("cb-saving"), 
        $.post({
            url: action,
            data: data,
            success: function() {
                $(self).removeAttr("disabled"), $(self).parents(".cb-popup, body").removeClass("cb-saving"), 
                $(document.body).hasClass("cb-cb_DEBUG-on") || (document.location = document.location), 
                $(self).parents(".cb-popup, body").addClass("cb-refreshing");
            },
            error: function(data) {
                var responseXML, message;
                $(self).parents(".cb-popup, body").removeClass("cb-saving"), $(self).parents(".cb-popup, body").addClass("cb-ajax-failed"), 
                $(self).removeAttr("disabled"), console.log(data), (responseXML = $(data.responseText)) && ((message = responseXML.filter("result").attr("message")) || (message = "Unknown response"), 
                alert(message));
            }
        });
    }), jRoot.find(".cb-popup-form-save").click(function() {
        var self = this, form = $(self).closest(".cb-ajax-edit-form"), data = form.find(":input").serialize(), action = form.attr("action");
        $(self).attr("disabled", "1"), $(self).parents(".cb-popup, body").addClass("cb-saving"), 
        $.post({
            url: action,
            data: data,
            success: function() {
                $(self).removeAttr("disabled"), $(self).parents(".cb-popup, body").removeClass("cb-saving"), 
                $(document.body).hasClass("cb-cb_DEBUG-on") || (document.location = document.location), 
                $(self).parents(".cb-popup, body").addClass("cb-refreshing");
            },
            error: function(data) {
                var responseXML, message;
                $(self).parents(".cb-popup, body").removeClass("cb-saving"), $(self).parents(".cb-popup, body").addClass("cb-ajax-failed"), 
                $(self).removeAttr("disabled"), console.log(data), (responseXML = $(data.responseText)) && ((message = responseXML.filter("result").attr("message")) || (message = "Unknown response"), 
                alert(message));
            }
        });
    }), jRoot.find(".cb-calendar-krumo-show").click(function() {
        $(this).parent().find(".cb-calendar-krumo").show();
    }), jRoot.find("#TB_window #cb-fullscreen").click(function() {
        $("#TB_ajaxContent").css("max-width", "none").css("width", "auto").css("height", "auto"), 
        $("#TB_window").css("overflow-y", "scroll").addClass("cb-fullscreen").animate({
            width: "100%",
            height: "100%",
            top: "0%",
            left: "0%",
            marginTop: "0px",
            marginLeft: "0px"
        });
    });
    var jCMB2_select_properties = $(".cb-with-properties[name=location_ID]:visible, input[type=hidden].cb-with-properties[name=location_ID]");
    jCMB2_select_properties.length ? (console.info("attaching cb_object_selected to:"), 
    console.log(jCMB2_select_properties)) : console.info("no cb-with-properties found"), 
    jCMB2_select_properties.on("cb_object_selected", function(e, object, element) {
        var has_opening_hours = object && object.last_opening_hours, jNoOpeningHours = $(".cb-period-group-id-OPH, .cb-period-entity-create-OPH, .cb-period-group-id-HRY, .cb-period-entity-create-HRY, .cb-ignore-location-restrictions"), jNoOHFirstOption = $(".cb-period-group-id-CUS, .cb-period-entity-create-CUS"), jOHFirstOption = $(".cb-period-group-id-OPH, .cb-period-entity-create-OPH"), jClassHolder = $(this).closest("body,.cb-popup");
        jClassHolder.removeClass("cb-has-opening-hours").removeClass("cb-no-opening-hours"), 
        jClassHolder.addClass(has_opening_hours ? "cb-has-opening-hours" : "cb-no-opening-hours"), 
        has_opening_hours ? ($(".cb-no-opening-hours-show").slideUp(), $(".cb-no-opening-hours-hide").slideDown(), 
        jNoOpeningHours.css("opacity", "1"), jOHFirstOption.children("input").click()) : ($(".cb-no-opening-hours-show").slideDown(), 
        $(".cb-no-opening-hours-hide").slideUp(), jNoOpeningHours.css("opacity", "0.5"), 
        jNoOHFirstOption.children("input").click()), window.console && console.info("last_opening_hours object:" + object);
    }), $(document).on("cmb_init_pickers", function(e, pickers) {
        if (pickers) for (picker in pickers) pickers[picker].attr("autocomplete", "off");
    }), jRoot.find("form").submit(function() {
        $(".cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker");
    }), jRoot.find(".cmb-type-text-datetime-timestamp").change(function() {
        var datepicker = $(this).find(".cmb2-datepicker"), timepicker = $(this).find(".cmb2-timepicker");
        datepicker.length && !datepicker.val() && timepicker.val("");
    }), $("#cb-set-full-day").click(function() {
        var field_list = $(this).parents(".cmb-field-list");
        return field_list.find("#datetime_part_period_start_time").val("00:00"), field_list.find("#datetime_part_period_end_time").val("23:59"), 
        !1;
    }), jRoot.find(":input").change(cb_update_followers), jRoot.find("input[type=radio]").click(cb_update_followers), 
    cb_update_followers();
    $(".cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker");
    var recurrence_boxes = $(".cmb2-id-recurrence-sequence, .cmb2-id-datetime-from, .cmb2-id-datetime-to, .cmb2-id-period-explanation-selection"), sequence = $(".cmb2-id-recurrence-sequence"), sequence_checks = sequence.find(".cmb2-checkbox-list"), daily_html = sequence.find(".cmb2-checkbox-list").html(), recurrence_inputs = recurrence_boxes.find("input"), start_date_input = $("#datetime_part_period_start_date"), end_date_input = $("#datetime_part_period_end_date");
    recurrence_boxes.hide(), jRoot.find(".cmb2-id-recurrence-type input").click(function() {
        var repeat_setting = $(this).val(), start_date = new Date(start_date_input.val());
        end_date_input.val() && new Date(end_date_input.val());
        switch (recurrence_inputs.removeAttr("disabled"), recurrence_boxes.removeClass("cb-disabled"), 
        $(this).closest("form, #cb-ajax-edit-form").removeClass("cb-repeat-D cb-repeat-W cb-repeat-M cb-repeat-Y"), 
        "__Null__" == repeat_setting ? recurrence_boxes.slideUp() : (recurrence_boxes.slideDown(), 
        $(this).closest("form, #cb-ajax-edit-form").addClass("cb-repeat-" + repeat_setting)), 
        repeat_setting) {
          case "__Null__":
            recurrence_boxes.addClass("cb-disabled"), recurrence_inputs.attr("disabled", "1");
            break;

          case "D":
            $(".cmb2-id-period-explanation-selection .cb-description p").html(cb_dictionary.period_explanation_selection), 
            sequence_checks.html(daily_html), sequence_checks.slideDown();
            break;

          case "W":
            var start_date_day = cb_dayofweek_string(start_date);
            $(".cmb2-id-period-explanation-selection .cb-description p").html(cb_dictionary.period_repeats_weekly_on + start_date_day), 
            sequence_checks.slideUp(void 0, function() {
                sequence_checks.html("");
            });
            break;

          case "M":
            var start_date_day_of_month = start_date.getDate(), suffix = "th";
            switch (start_date_day_of_month % 10) {
              case 1:
                suffix = "st";
                break;

              case 2:
                suffix = "nd";
                break;

              case 3:
                suffix = "rd";
            }
            var day_advice = 31 == start_date_day_of_month ? cb_dictionary.period_repeats_monthly : cb_dictionary.period_repeats_monthly_on + start_date_day_of_month + suffix + cb_dictionary.period_repeats_monthly_on_day;
            $(".cmb2-id-period-explanation-selection .cb-description p").html(day_advice), sequence_checks.slideUp(void 0, function() {
                sequence_checks.html("");
            });
            break;

          case "Y":
            sequence_checks.slideUp(void 0, function() {
                sequence_checks.html("");
            });
            var start_date_in_year = start_date.getDate() + " of " + cb_month_string(start_date);
            $(".cmb2-id-period-explanation-selection .cb-description p").html(cb_dictionary.period_repeats_yearly_on + start_date_in_year + ".");
        }
    }), jRoot.find(".cmb2-id-recurrence-type input[checked]").click();
}

function cb_init_popup() {
    var $ = window.jQuery, adopt_classes_from_content = $("#TB_window .TB_window_classes");
    adopt_classes_from_content.length && ($("#TB_window").addClass(adopt_classes_from_content.text()), 
    adopt_classes_from_content.remove());
    var adopt_header = $("#TB_window .TB_title_actions");
    adopt_header.length && $("#TB_title").after(adopt_header);
    var adopt_title = $("#TB_window .TB_title_html").text();
    adopt_title.length && $("#TB_window #TB_ajaxWindowTitle").prepend(adopt_title), 
    cb_process.apply(this), window.CMB2 && (delete window.CMB2.$metabox, window.CMB2.init());
}

function cb_update_followers() {
    cb_update_follower.call(this, "location_ID"), cb_update_follower.call(this, "period_status_type_ID"), 
    cb_update_follower.call(this, "datetime_part_period_start[date]");
}

function cb_update_follower(form_ID_name) {
    var $ = jQuery, jBody = $(this).closest(".cb-popup");
    jBody.length || (jBody = $(document.body));
    var form_ID_name_escaped = form_ID_name.replace(/\]/, "\\]").replace(/\[/, "\\["), css_ID_name = form_ID_name.replace(/_/g, "-").replace(/[^a-zA-Z]+/g, "-").replace(/^-+|-+$/g, ""), css_text_name = css_ID_name.replace(/[_-]ID$/g, ""), jSelectOption = jBody.find(".cb-leader select#" + form_ID_name).find("option:selected"), jRadio = jBody.find(".cb-leader input:checked[type=radio][name=" + form_ID_name_escaped + "]"), jInput = jBody.find(".cb-leader input[type!=radio][name=" + form_ID_name_escaped + "]"), sOptionID = (jSelectOption.length ? jSelectOption.attr("value") : "") + (jRadio.length ? jRadio.val() : "") + (jInput.length ? jInput.val() : ""), sOptionText = (jSelectOption.length ? jSelectOption.text() : "") + (jRadio.length ? jRadio.parent().children("label").text() : "") + (jInput.length ? jInput.attr("display-name") || jInput.val() : "");
    (sOptionText = sOptionText.replace(/\(.*\)/, "")) && !sOptionText.match(/-- .* --/) || (sOptionText = "not selected"), 
    window.console && console.info("[." + css_text_name + "] => [" + sOptionText + "]"), 
    jBody.find(".cb-follow-" + css_ID_name).html(sOptionID), jBody.find(".cb-follow-" + css_text_name).html(sOptionText), 
    jBody.find("a.cb-follow").each(function() {
        var href = $(this).attr("href"), regex = new RegExp(form_ID_name + "=[^&]*", "g");
        $(this).attr("href", href.replace(regex, form_ID_name + "=" + sOptionID));
    });
}

function cb_dayofweek_string(d) {
    return d instanceof Date ? [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ][d.getDay()] : void 0;
}

function cb_month_string(d) {
    return d instanceof Date ? [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ][d.getMonth()] : void 0;
}

function cb_iso_date(date) {
    return date.getFullYear() + "-" + (date.getMonth() + 1).toString().padStart(2, "0") + "-" + date.getDate().toString().padStart(2, "0");
}

!function($) {
    "use strict";
    $(document).ready(cb_process), $(document).on("cb-popup-appeared", function() {
        var jPopup = $("#TB_window");
        jPopup.length && (window.console && console.info("received event cb-popup-appeared"), 
        cb_init_popup.apply(jPopup.get(0)));
    });
}(jQuery), function($) {
    "use strict";
    $(function() {
        $("#confirmed_user_ID").on("click", function() {
            $("#post").submit();
        });
    });
}(jQuery), function($) {
    "use strict";
    $(function() {
        $(".admin-request-confirmation").on("click", function() {
            return confirm($(this).data("confirmationstring"));
        });
    });
}(jQuery);