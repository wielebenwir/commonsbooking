(function ($) {
    'use strict';
    $(function () {
        const groupName = 'rules_group';
        const groupID = 'cmb-group-rules_group-';
        const ruleSelectorID = 'rule-type';
        const ruleDescriptionID = 'rule-description';
        const ruleAppliesAllID = 'rule-applies-all';
        const ruleAppliesCategoriesID = 'rule-applies-categories';
        const ruleParam1ID = 'rule-param1';
        const ruleParam2ID = 'rule-param2';
        const ruleSelectParamID = 'rule-select-param';
        console.log(cb_booking_rules);
        const handleRuleSelection = function() {
            let groupFields = $('#' + groupName + '_repeat');

            //bind to row adding/ removing
            groupFields.on( 'cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete', function() {handleRuleSelection();} );

            //iterate over all children, remove the last child because it is the new row
            for (let i = 0; i < groupFields.children().length - 1; i++){
                // https://github.com/CMB2/CMB2/issues/1149 this is why we have to do this weird selection style

                //find all of our fields
                let currentGroup = $('#' + groupID + i);
                let ruleSelector = currentGroup.find('#' + groupName + '_' + i + '_'  + ruleSelectorID);
                let ruleDescription = currentGroup.find('[class*="' + ruleDescriptionID + '"]').find('.cmb2-metabox-description');
                let ruleParam1 = currentGroup.find('[class*="' + ruleParam1ID + '"]');
                let ruleParam1Desc = ruleParam1.find('.cmb2-metabox-description');
                let ruleParam2 = currentGroup.find('[class*="' + ruleParam2ID + '"]');
                let ruleParam2Desc = ruleParam2.find('.cmb2-metabox-description');
                let ruleSelectParam = currentGroup.find('[class*="' + ruleSelectParamID + '"]');
                let ruleSelectParamDesc = ruleSelectParam.find('.cmb2-metabox-description');
                let ruleSelectParamOptions = ruleSelectParam.find('.cmb2_select');
                //bind events
                ruleSelector.change(function() {handleRuleSelection();});

                //get the needed values
                const selectedRule = $("option:selected", ruleSelector).val();

                //apply to description & parameter count for the found rule, are passed using wp_inline_script as cb_booking_rules
                cb_booking_rules.forEach(rule => {
                    if (rule.name == selectedRule) {
                        ruleDescription.text(rule.description);
                        //check if params exist and set description / visibility accordingly
                        if (rule.hasOwnProperty("params") && rule.params.length > 0){
                            switch (rule.params.length) {
                                case 1:
                                    ruleParam1.show();
                                    ruleParam2.hide();
                                    ruleParam1Desc.text(rule.params[0]);
                                    ruleParam2.val('');
                                    break;

                                case 2:
                                    ruleParam1.show();
                                    ruleParam2.show();
                                    ruleParam1Desc.text(rule.params[0]);
                                    ruleParam2Desc.text(rule.params[1]);
                                    break;
                            }
                        }
                        else {
                            ruleParam1.hide();
                            ruleParam1.val('');
                            ruleParam2.hide();
                            ruleParam2.val('');
                        }
                        if (rule.hasOwnProperty("selectParam") && rule.selectParam.length > 0){
                            ruleSelectParam.show();
                            ruleSelectParamDesc.text(rule.selectParam[0]);
                            let ruleOptions = rule.selectParam[1];
                            for (var key in ruleOptions) {
                                ruleSelectParamOptions.append($('<option>', {
                                    value: key,
                                    text: ruleOptions[key]
                                }));
                            }
                            //TODO: set select field to saved value
                        }
                        else {
                            ruleSelectParam.hide();
                        }
                    }

                });
            }
        }

        const handleAppliesToAll = function() {
            let groupFields = $('#' + groupName + '_repeat');

            //bind to row adding / removing
            groupFields.on( 'cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete', function() {handleAppliesToAll();} );
            //iterate over all children, remove the last child because it is the new row
            for (let i = 0; i < groupFields.children().length - 1; i++){
                let currentGroup = $('#' + groupID + i);

                //find all of our fields
                let ruleAppliesAll = currentGroup.find('[class*="' + ruleAppliesAllID + '"]').find('.cmb2-option');
                let ruleAppliesCategories = currentGroup.find('[class*="' + ruleAppliesCategoriesID + '"]');


                //bind events
                ruleAppliesAll.change(function () {handleAppliesToAll();});
                if (ruleAppliesAll.prop('checked')){
                    ruleAppliesCategories.hide();
                }
                else {
                    ruleAppliesCategories.show();
                }

            }
        }
        handleRuleSelection();
        handleAppliesToAll();
    });
})(jQuery);
