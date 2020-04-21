function cb2_process() {
    var $ = jQuery, jRoot = $(this);
    $("body.cb2-WP_DEBUG-on").length;
    jRoot.find("label p, label span").click(function(e) {
        var idTarget, jLabel = $(this).parent("label");
        jLabel.length && (idTarget = jLabel.attr("for"), jLabel.parent().find("#" + idTarget + ":visible").click());
    }), jRoot.find("form").on("submit", function() {
        var jSubmit = $(this).find(".cb2-submit");
        jSubmit.val(jSubmit.val() + " ..."), $(window).off("beforeunload.edit-post"), setTimeout(function() {
            jSubmit.addClass("disabled");
        });
    }), jRoot.find("form").submit(function() {
        $(this).find(".cb2-form-disable").attr("disabled", "1"), $(this).find("li.cb2-selected").each(function() {
            $(this).closest("ul").find("li:not(.cb2-selected) input").attr("disabled", "1");
        });
    }), jRoot.find("a.thickbox").each(function() {
        var href = $(this).attr("href");
        -1 == href.indexOf("cb2_load_normal_page") && -1 == href.indexOf("cb2_load_template") && (href += (-1 == href.indexOf("?") ? "?" : "&") + "cb2_load_normal_page=1", 
        $(this).attr("href", href));
    }), jRoot.find(".cmb2-id-period-status-type-ID input").click(function() {
        var jPopup = $(this).closest("body, .cb2-popup"), jPeriodStatusType = $(this).parent().find("label"), css_class = jPopup.attr("class"), type = jPeriodStatusType.text().toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), id = $(this).val();
        css_class = (css_class = (css_class = (css_class = " " + css_class + " ").replace(/ +cb2-status-[^ ]+/g, " ")) + "cb2-status-" + type + " cb2-status-" + id).replace(/ +/, " ").trim(), 
        jPopup.attr("class", css_class), jPopup.css("background-color", "");
    }), jRoot.find(".cb2-form").on("submit", function() {
        var self = this;
        setTimeout(function() {
            var jSubmit = $(self).find(":input[type=submit]");
            jSubmit.val(jSubmit.val() + " ..."), jSubmit.after(" <progress></progress> "), $(self).find(":input").addClass("disabled");
        }, 0);
    }), jRoot.find(".cb2-javascript-form input[type=button]").click(function() {
        var sRedirect = document.location, sQuery = unescape(document.location.search.replace(/^\?/, "")), aQuery = sQuery.split("&"), jInputs = $(this).closest(".cb2-javascript-form").find(":input");
        jInputs.each(function() {
            var sJSName = $(this).attr("js-name");
            if (sJSName ? $(this).attr("name", sJSName) : sJSName = $(this).attr("name"), sJSName) {
                sJSName = sJSName.replace(/\[\d+\]/, "[]");
                for (var i = aQuery.length; 0 < i; ) aQuery[--i].replace(/=.*/, "").replace(/\[[0-9]+\]/, "[]") == sJSName && aQuery.splice(i, 1);
            }
        }), sQuery = aQuery.join("&"), sQuery += "&", sQuery += jInputs.serialize(), sQuery += "&redirect=" + escape(sRedirect), 
        document.location = document.location.pathname + "?" + sQuery;
    }), jRoot.find(".cb2-template-type-available > .cb2-details").click(function(e) {
        var container = $(this).parent(), checkbox = $(this).children(".cb2-periodinst-selector"), cssClass = $(this).attr("class").trim(), clicked_input = $(e.target).is(checkbox), is_checked = checkbox.attr("checked");
        clicked_input && (is_checked = !is_checked), is_checked ? (clicked_input || checkbox.removeAttr("checked"), 
        container.attr("class", cssClass.replace(/cb2-selected/, ""))) : (clicked_input || checkbox.attr("checked", "1"), 
        container.attr("class", cssClass + " cb2-selected")), clicked_input || e.preventDefault();
    });
    var original_recurrence_type, previous_datetime_part_period_start_date = $("#datetime_part_period_start_date").val();
    $("#datetime_part_period_end_date").val();
    function cb2_next_if_selects_complete() {
        var allComplete = !0, jSelects = $(this).find("select:visible");
        jSelects.each(function() {
            var value = $(this).val();
            return value && "__Null__" != value || (allComplete = !1), !allComplete && window.console && console.info($(this).attr("name") + " <select> not complete"), 
            allComplete;
        }), allComplete && $(".cb2-popup-form-next").click(), allComplete && window.console && (console.info("all complete" + (jSelects.length ? "" : " (none found)")), 
        jSelects.length && console.log(jSelects));
    }
    jRoot.find("#datetime_part_period_start_date").change(function() {
        var start_date = new Date($("#datetime_part_period_start_date").val()), end_date = new Date($("#datetime_part_period_end_date").val()), prev_start_date = new Date(previous_datetime_part_period_start_date), diff_year = start_date.getFullYear() - prev_start_date.getFullYear(), diff_month = start_date.getMonth() - prev_start_date.getMonth(), diff_day = start_date.getDate() - prev_start_date.getDate(), jSubmit = $(this).closest("body,.cb2-popup").find(".cb2-popup-form-save");
        jSubmit.removeAttr("disabled"), isNaN(start_date.getTime()) ? ($(this).addClass("cb2-error"), 
        jSubmit.attr("disabled", "1")) : ($("#datetime_part_period_start_date, #datetime_part_period_end_date").hasClass("cb2-error") || (end_date.setFullYear(end_date.getFullYear() + diff_year), 
        end_date.setMonth(end_date.getMonth() + diff_month), end_date.setDate(end_date.getDate() + diff_day), 
        $("#datetime_part_period_end_date").val(cb2_iso_date(end_date)), window.console && console.info(diff_year + "-" + diff_month + "-" + diff_day)), 
        $("#datetime_part_period_start_date, #datetime_part_period_end_date").removeClass("cb2-error"), 
        end_date < start_date && ($("#datetime_part_period_end_date").addClass("cb2-error"), 
        jSubmit.attr("disabled", "1")), previous_datetime_part_period_start_date = $(this).val(), 
        $("#datetime_part_period_end_date").val()), !0;
    }), jRoot.find("#datetime_part_period_end_date").change(function() {
        var start_date = new Date($("#datetime_part_period_start_date").val()), end_date = new Date($("#datetime_part_period_end_date").val()), jSubmit = $(this).closest("body,.cb2-popup").find(".cb2-popup-form-save");
        jSubmit.removeAttr("disabled"), isNaN(end_date.getTime()) ? ($(this).addClass("cb2-error"), 
        jSubmit.attr("disabled", "1")) : ($("#datetime_part_period_start_date, #datetime_part_period_end_date").removeClass("cb2-error"), 
        end_date < start_date && ($("#datetime_part_period_end_date").addClass("cb2-error"), 
        jSubmit.attr("disabled", "1")), $(this).val()), !0;
    }), jRoot.find("#cb2-SOT").click(function() {
        original_recurrence_type = $(".cmb2-id-recurrence-type :input[checked]"), $("#recurrence_type1").click(), 
        $(".cmb2-id-recurrence-type").slideUp(), $("#datetime_part_period_start_date").val($("#datetime_period_inst_start_date").val()), 
        $("#datetime_part_period_end_date").val($("#datetime_period_inst_end_date").val());
    }), jRoot.find("#cb2-SFH, #cb2-SAI").click(function() {
        $(".cmb2-id-recurrence-type").slideDown(), original_recurrence_type && original_recurrence_type.click();
    }), jRoot.find("#cb2-save-types input").click(function() {
        var updatestring = $('label[for="' + this.id + '"]').html();
        $("#cb2-follow-cb2-save-types").html(updatestring);
    }), jRoot.find(".cb2-nexts").each(function() {
        var nexts = $(this).find("li"), ids = "", panels = (nexts.find("a").each(function() {
            ids += (ids ? "," : "") + $(this).attr("href");
        }), nexts.closest(".cb2-popup,#post-body").find(ids));
        if (panels.css("overflow-y", "hidden"), panels.hide(), $(this).closest(".cb2-popup,body").addClass("cb2-with-nexts"), 
        $(this).closest(".cb2-popup,#post-body").removeClass("columns-2"), nexts.click(function(e) {
            var next = $(this), href = next.find("a").attr("href"), newPanel = next.closest(".cb2-popup,body").find(href), oldPanel = panels.filter(":visible");
            next.hasClass("cb2-selected") || (nexts.removeClass("cb2-selected"), nexts.addClass("cb2-unselected"), 
            next.addClass("cb2-selected"), next.removeClass("cb2-unselected"), newPanel.css("width", "100%"), 
            oldPanel.css("width", "100%"), oldPanel.css("height", oldPanel.height()), newPanel.css("height", newPanel.height()), 
            newPanel.css("width", "0%"), newPanel.show(), oldPanel.animate({
                width: "0%"
            }, 500, function() {
                oldPanel.hide(), oldPanel.css("height", "auto");
            }), newPanel.animate({
                width: "100%"
            }, 500, function() {
                newPanel.css("height", "auto"), newPanel.focus();
            }), next.hasClass("cb2-last") ? ($(".cb2-popup-form-next").hide(), $(".cb2-popup-form-save").show()) : ($(".cb2-popup-form-next").show(), 
            $(".cb2-popup-form-save").hide())), e.preventDefault();
        }), nexts.length) {
            var next = nexts.filter(".cb2-selected");
            next.length || (next = nexts.eq(0));
            var href = next.find("a").attr("href"), panel = next.closest(".cb2-popup,body").find(href);
            next.addClass("cb2-selected"), next.removeClass("cb2-unselected"), panel.show();
        }
    }), $(".cb2-popup a.thickbox").click(function(e) {
        var sPopupTypes = $("#TB_window").attr("class").match(/cb2-popup-[^ ]+/g).join(" ");
        $("#TB_window").attr("class", "cb2-popup " + sPopupTypes);
    }), jRoot.find(".cb2-popup-form-next").click(function() {
        $(this).closest(".cb2-popup,body").find(".cb2-nexts li.cb2-selected").next().click();
    }), $(".cb2-popup-add #cb2-tab-type li").click(function() {
        $(".cb2-popup-form-next").click();
    }), jRoot.find("#cb2-tab-objects").change(cb2_next_if_selects_complete), jRoot.find("#cb2-tab-objects:visible").each(cb2_next_if_selects_complete), 
    $(document).find(".toplevel_page_cb2-menu > .wp-submenu > li").each(function() {
        var href, matches;
        (href = $(this).children("a").attr("href")) && ((matches = href.match(/[?&]page=([^&]+)/)) ? matches.length && $(this).addClass(matches[1]) : href.match(/[a-zA-Z_0-9]+/) && $(this).addClass(href));
    }), jRoot.find(".cmb2-radio-list > li").each(function() {
        var jLI = $(this), jInput = $(this).children("input"), jLabel = $(this).children("label"), name = jInput.attr("name");
        "period_status_type_ID" == name && (name = "status");
        var css_name = name.toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), id = jInput.val(), type = jLabel.text().replace(/ .*$/, "").toLowerCase().replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), stub = "cb2-" + css_name + "-";
        jLI.addClass(stub + type), jLI.addClass(stub + id);
    }), jRoot.find("input[type=radio]").click(function() {
        $(this).closest("ul").children("li").removeClass("cb2-selected"), $(this).closest("li").addClass("cb2-selected");
    }), jRoot.find("input[type=radio]:checked").each(function() {
        $(this).closest("ul").children("li").removeClass("cb2-selected"), $(this).closest("li").addClass("cb2-selected");
    }), jRoot.find(".cb2-add-class-advanced").click(function() {
        $(this).closest("body,.cb2-popup,.cb2-panel").addClass("cb2-advanced");
    }), jRoot.find(".cb2-set-href-querystring").change(function() {
        var name = $(this).attr("name"), value = $(this).val().trim(), text = $(this).find("option:selected").html().trim(), jHref = $(this).closest("a"), title = jHref.attr("title"), parts = jHref.attr("href").split("?"), url = parts[0].trim(), qs = 1 < parts.length ? parts[1].trim() : "", qsParts = qs.split("&"), css_class = "cb2-follow cb2-follow-" + name.replace(/_/g, "-").replace(/[_-]ID$/g, "").replace(/[^a-z]+/g, "-").replace(/^-+|-+$/g, ""), found = !1;
        for (var i in qsParts) {
            if (qsParts[i].split("=")[0] == name) {
                qsParts[i] = name + "=" + value, found = !0;
                break;
            }
        }
        found || (qsParts[qsParts.length] = name + "=" + value), title = title.replace(/: .*/, ""), 
        qs = qsParts.join("&"), jHref.attr("href", url + "?" + qs), jHref.attr("title", title + ': <span class="' + css_class + '">' + text + "</span>");
    }), jRoot.find(".cb2-tabs").each(function() {
        var tabs = $(this).find("li"), ids = "", panels = (tabs.find("a").each(function() {
            ids += (ids ? "," : "") + $(this).attr("href");
        }), tabs.closest(".cb2-popup,body").find(ids));
        if (panels.hide(), $(this).closest(".cb2-popup,body").addClass("cb2-with-tabs"), 
        $(this).closest(".cb2-popup,#post-body").removeClass("columns-2"), $(this).addClass("cb2-processed"), 
        tabs.click(function(e) {
            var tab = $(this), href = tab.find("a").attr("href"), panel = tab.closest(".cb2-popup,body").find(href);
            tabs.removeClass("cb2-selected"), tabs.addClass("cb2-unselected"), panels.hide(), 
            tabs.each(function() {
                var other_href = $(this).find("a").attr("href");
                other_href = other_href.replace(/^#/, ""), $(this).closest(".cb2-popup,body").removeClass("cb2-tabs-" + other_href + "-selected");
            }), href = href.replace(/^#/, ""), tab.closest(".cb2-popup,body").addClass("cb2-tabs-" + href + "-selected"), 
            tab.addClass("cb2-selected"), tab.removeClass("cb2-unselected"), panel.focus(), 
            panel.show(), e.preventDefault();
        }), tabs.length) {
            var tab = tabs.filter(".cb2-selected");
            tab.length || (tab = tabs.eq(0));
            var href = tab.find("a").attr("href"), panel = tab.closest(".cb2-popup,body").find(href);
            tab.addClass("cb2-selected"), tab.removeClass("cb2-unselected"), href = href.replace(/^#/, ""), 
            tab.closest(".cb2-popup,body").addClass("cb2-tabs-" + href + "-selected"), panel.show();
        }
    }), jRoot.find(".cb2-popup-form-trash").click(function() {
        var self = this, form = $(self).closest(".cb2-ajax-edit-form"), data = form.find(":input").serialize(), action = form.attr("action-trash");
        $(self).attr("disabled", "1"), $(self).parents(".cb2-popup, body").addClass("cb2-saving"), 
        $.post({
            url: action,
            data: data,
            success: function() {
                $(self).removeAttr("disabled"), $(self).parents(".cb2-popup, body").removeClass("cb2-saving"), 
                $(document.body).hasClass("cb2-CB2_DEBUG-on") || (document.location = document.location), 
                $(self).parents(".cb2-popup, body").addClass("cb2-refreshing");
            },
            error: function(data) {
                var responseXML, message;
                $(self).parents(".cb2-popup, body").removeClass("cb2-saving"), $(self).parents(".cb2-popup, body").addClass("cb2-ajax-failed"), 
                $(self).removeAttr("disabled"), console.log(data), (responseXML = $(data.responseText)) && ((message = responseXML.filter("result").attr("message")) || (message = "Unknown response"), 
                alert(message));
            }
        });
    }), jRoot.find(".cb2-popup-form-save").click(function() {
        var self = this, form = $(self).closest(".cb2-ajax-edit-form"), data = form.find(":input").serialize(), action = form.attr("action");
        $(self).attr("disabled", "1"), $(self).parents(".cb2-popup, body").addClass("cb2-saving"), 
        $.post({
            url: action,
            data: data,
            success: function() {
                $(self).removeAttr("disabled"), $(self).parents(".cb2-popup, body").removeClass("cb2-saving"), 
                $(document.body).hasClass("cb2-CB2_DEBUG-on") || (document.location = document.location), 
                $(self).parents(".cb2-popup, body").addClass("cb2-refreshing");
            },
            error: function(data) {
                var responseXML, message;
                $(self).parents(".cb2-popup, body").removeClass("cb2-saving"), $(self).parents(".cb2-popup, body").addClass("cb2-ajax-failed"), 
                $(self).removeAttr("disabled"), console.log(data), (responseXML = $(data.responseText)) && ((message = responseXML.filter("result").attr("message")) || (message = "Unknown response"), 
                alert(message));
            }
        });
    }), jRoot.find(".cb2-calendar-krumo-show").click(function() {
        $(this).parent().find(".cb2-calendar-krumo").show();
    }), jRoot.find("#TB_window #cb2-fullscreen").click(function() {
        $("#TB_ajaxContent").css("max-width", "none").css("width", "auto").css("height", "auto"), 
        $("#TB_window").css("overflow-y", "scroll").addClass("cb2-fullscreen").animate({
            width: "100%",
            height: "100%",
            top: "0%",
            left: "0%",
            marginTop: "0px",
            marginLeft: "0px"
        });
    });
    var jCMB2_select_properties = $(".cb2-with-properties[name=location_ID]:visible, input[type=hidden].cb2-with-properties[name=location_ID]");
    jCMB2_select_properties.length ? (console.info("attaching cb2_object_selected to:"), 
    console.log(jCMB2_select_properties)) : console.info("no cb2-with-properties found"), 
    jCMB2_select_properties.on("cb2_object_selected", function(e, object, element) {
        var has_opening_hours = object && object.last_opening_hours, jNoOpeningHours = $(".cb2-period-group-id-OPH, .cb2-period-entity-create-OPH, .cb2-period-group-id-HRY, .cb2-period-entity-create-HRY, .cb2-ignore-location-restrictions"), jNoOHFirstOption = $(".cb2-period-group-id-CUS, .cb2-period-entity-create-CUS"), jOHFirstOption = $(".cb2-period-group-id-OPH, .cb2-period-entity-create-OPH"), jClassHolder = $(this).closest("body,.cb2-popup");
        jClassHolder.removeClass("cb2-has-opening-hours").removeClass("cb2-no-opening-hours"), 
        jClassHolder.addClass(has_opening_hours ? "cb2-has-opening-hours" : "cb2-no-opening-hours"), 
        has_opening_hours ? ($(".cb2-no-opening-hours-show").slideUp(), $(".cb2-no-opening-hours-hide").slideDown(), 
        jNoOpeningHours.css("opacity", "1"), jOHFirstOption.children("input").click()) : ($(".cb2-no-opening-hours-show").slideDown(), 
        $(".cb2-no-opening-hours-hide").slideUp(), jNoOpeningHours.css("opacity", "0.5"), 
        jNoOHFirstOption.children("input").click()), window.console && console.info("last_opening_hours object:" + object);
    }), $(document).on("cmb_init_pickers", function(e, pickers) {
        if (pickers) for (picker in pickers) pickers[picker].attr("autocomplete", "off");
    }), jRoot.find("form").submit(function() {
        $(".cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker");
    }), jRoot.find(".cmb-type-text-datetime-timestamp").change(function() {
        var datepicker = $(this).find(".cmb2-datepicker"), timepicker = $(this).find(".cmb2-timepicker");
        datepicker.length && !datepicker.val() && timepicker.val("");
    }), $("#cb2-set-full-day").click(function() {
        var field_list = $(this).parents(".cmb-field-list");
        return field_list.find("#datetime_part_period_start_time").val("00:00"), field_list.find("#datetime_part_period_end_time").val("23:59"), 
        !1;
    }), jRoot.find(":input").change(cb2_update_followers), jRoot.find("input[type=radio]").click(cb2_update_followers), 
    cb2_update_followers();
    $(".cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker");
    var recurrence_boxes = $(".cmb2-id-recurrence-sequence, .cmb2-id-datetime-from, .cmb2-id-datetime-to, .cmb2-id-period-explanation-selection"), sequence = $(".cmb2-id-recurrence-sequence"), sequence_checks = sequence.find(".cmb2-checkbox-list"), daily_html = sequence.find(".cmb2-checkbox-list").html(), recurrence_inputs = recurrence_boxes.find("input"), start_date_input = $("#datetime_part_period_start_date"), end_date_input = $("#datetime_part_period_end_date");
    recurrence_boxes.hide(), jRoot.find(".cmb2-id-recurrence-type input").click(function() {
        var repeat_setting = $(this).val(), start_date = new Date(start_date_input.val());
        end_date_input.val() && new Date(end_date_input.val());
        switch (recurrence_inputs.removeAttr("disabled"), recurrence_boxes.removeClass("cb2-disabled"), 
        $(this).closest("form, #cb2-ajax-edit-form").removeClass("cb2-repeat-D cb2-repeat-W cb2-repeat-M cb2-repeat-Y"), 
        "__Null__" == repeat_setting ? recurrence_boxes.slideUp() : (recurrence_boxes.slideDown(), 
        $(this).closest("form, #cb2-ajax-edit-form").addClass("cb2-repeat-" + repeat_setting)), 
        repeat_setting) {
          case "__Null__":
            recurrence_boxes.addClass("cb2-disabled"), recurrence_inputs.attr("disabled", "1");
            break;

          case "D":
            $(".cmb2-id-period-explanation-selection .cb2-description p").html(cb2_dictionary.period_explanation_selection), 
            sequence_checks.html(daily_html), sequence_checks.slideDown();
            break;

          case "W":
            var start_date_day = cb2_dayofweek_string(start_date);
            $(".cmb2-id-period-explanation-selection .cb2-description p").html(cb2_dictionary.period_repeats_weekly_on + start_date_day), 
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
            var day_advice = 31 == start_date_day_of_month ? cb2_dictionary.period_repeats_monthly : cb2_dictionary.period_repeats_monthly_on + start_date_day_of_month + suffix + cb2_dictionary.period_repeats_monthly_on_day;
            $(".cmb2-id-period-explanation-selection .cb2-description p").html(day_advice), 
            sequence_checks.slideUp(void 0, function() {
                sequence_checks.html("");
            });
            break;

          case "Y":
            sequence_checks.slideUp(void 0, function() {
                sequence_checks.html("");
            });
            var start_date_in_year = start_date.getDate() + " of " + cb2_month_string(start_date);
            $(".cmb2-id-period-explanation-selection .cb2-description p").html(cb2_dictionary.period_repeats_yearly_on + start_date_in_year + ".");
        }
    }), jRoot.find(".cmb2-id-recurrence-type input[checked]").click();
}

function cb2_init_popup() {
    var $ = window.jQuery, adopt_classes_from_content = $("#TB_window .TB_window_classes");
    adopt_classes_from_content.length && ($("#TB_window").addClass(adopt_classes_from_content.text()), 
    adopt_classes_from_content.remove());
    var adopt_header = $("#TB_window .TB_title_actions");
    adopt_header.length && $("#TB_title").after(adopt_header);
    var adopt_title = $("#TB_window .TB_title_html").text();
    adopt_title.length && $("#TB_window #TB_ajaxWindowTitle").prepend(adopt_title), 
    cb2_process.apply(this), window.CMB2 && (delete window.CMB2.$metabox, window.CMB2.init());
}

function cb2_update_followers() {
    cb2_update_follower.call(this, "location_ID"), cb2_update_follower.call(this, "period_status_type_ID"), 
    cb2_update_follower.call(this, "datetime_part_period_start[date]");
}

function cb2_update_follower(form_ID_name) {
    var $ = jQuery, jBody = $(this).closest(".cb2-popup");
    jBody.length || (jBody = $(document.body));
    var form_ID_name_escaped = form_ID_name.replace(/\]/, "\\]").replace(/\[/, "\\["), css_ID_name = form_ID_name.replace(/_/g, "-").replace(/[^a-zA-Z]+/g, "-").replace(/^-+|-+$/g, ""), css_text_name = css_ID_name.replace(/[_-]ID$/g, ""), jSelectOption = jBody.find(".cb2-leader select#" + form_ID_name).find("option:selected"), jRadio = jBody.find(".cb2-leader input:checked[type=radio][name=" + form_ID_name_escaped + "]"), jInput = jBody.find(".cb2-leader input[type!=radio][name=" + form_ID_name_escaped + "]"), sOptionID = (jSelectOption.length ? jSelectOption.attr("value") : "") + (jRadio.length ? jRadio.val() : "") + (jInput.length ? jInput.val() : ""), sOptionText = (jSelectOption.length ? jSelectOption.text() : "") + (jRadio.length ? jRadio.parent().children("label").text() : "") + (jInput.length ? jInput.attr("display-name") || jInput.val() : "");
    (sOptionText = sOptionText.replace(/\(.*\)/, "")) && !sOptionText.match(/-- .* --/) || (sOptionText = "not selected"), 
    window.console && console.info("[." + css_text_name + "] => [" + sOptionText + "]"), 
    jBody.find(".cb2-follow-" + css_ID_name).html(sOptionID), jBody.find(".cb2-follow-" + css_text_name).html(sOptionText), 
    jBody.find("a.cb2-follow").each(function() {
        var href = $(this).attr("href"), regex = new RegExp(form_ID_name + "=[^&]*", "g");
        $(this).attr("href", href.replace(regex, form_ID_name + "=" + sOptionID));
    });
}

function cb2_dayofweek_string(d) {
    return d instanceof Date ? [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ][d.getDay()] : void 0;
}

function cb2_month_string(d) {
    return d instanceof Date ? [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ][d.getMonth()] : void 0;
}

function cb2_iso_date(date) {
    return date.getFullYear() + "-" + (date.getMonth() + 1).toString().padStart(2, "0") + "-" + date.getDate().toString().padStart(2, "0");
}

!function($) {
    "use strict";
    $(document).ready(cb2_process), $(document).on("cb2-popup-appeared", function() {
        var jPopup = $("#TB_window");
        jPopup.length && (window.console && console.info("received event cb2-popup-appeared"), 
        cb2_init_popup.apply(jPopup.get(0)));
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