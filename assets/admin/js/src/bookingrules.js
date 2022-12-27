(function ($) {
    'use strict';
    $(function () {
        const groupName = 'rules_group';
        const groupID = 'cmb2-id-rules-group';
        const ruleSelectorID = 'rule-type';
        const ruleDescriptionID = 'rule-description';
        const ruleAppliesAllID = 'rule-applies-all';
        const ruleAppliesCategoriesID = 'rule-applies-categories';
        const ruleParam1ID = 'rule-param1';
        const ruleParam2ID = 'rule-param2';
        const ruleParam3ID = 'rule-param3';
        const handleRuleSelection = function() {
            let groupFields = document.querySelector('#' + groupName +'_repeat');
            //iterate over all children, remove the last child because it is the new row
            //apply descriptions to the corresponding rule
            for (let i = 0; i < groupFields.childElementCount - 1; i++){
                //find all of our fields
                let currentGroup = groupID + '-' + i + '-';
                let ruleSelector = $('.' + currentGroup + ruleSelectorID).find('.cmb2_select');
                let ruleDescription = $('.' + currentGroup + ruleDescriptionID).find('.cmb2-metabox-description');
                let ruleParam1 = $('.' + currentGroup + ruleParam1ID);
                let ruleParam1Desc = ruleParam1.find('.cmb2-metabox-description');
                let ruleParam2 = $('.' + currentGroup + ruleParam2ID);
                let ruleParam2Desc = ruleParam2.find('.cmb2-metabox-description');
                let ruleParam3 = $('.' + currentGroup + ruleParam3ID);
                let ruleParam3Desc = ruleParam3.find('.cmb2-metabox-description');

                //bind events
                ruleSelector.change(function() {handleRuleSelection();});

                //get the needed values
                const selectedRule = $("option:selected", ruleSelector).val();

                //apply to description & parameter count for the found rule
                cb_booking_rules.forEach(rule => {
                    if (rule.name == selectedRule) {
                        ruleDescription.text(rule.description);
                        //check if params exist and set description / visibility accordingly
                        if (rule.hasOwnProperty("params") && rule.params.length > 0){
                            switch (rule.params.length) {
                                case 1:
                                    ruleParam1.show();
                                    ruleParam2.hide();
                                    ruleParam3.hide();
                                    ruleParam1Desc.text(rule.params[0]);
                                    break;

                                case 2:
                                    ruleParam1.show();
                                    ruleParam2.show();
                                    ruleParam3.hide();
                                    ruleParam1Desc.text(rule.params[0]);
                                    ruleParam2Desc.text(rule.params[1]);
                                    break;

                                case 3:
                                    ruleParam1.show();
                                    ruleParam2.show();
                                    ruleParam3.show();
                                    ruleParam1Desc.text(rule.params[0]);
                                    ruleParam2Desc.text(rule.params[1]);
                                    ruleParam3Desc.text(rule.params[2]);
                            }
                        }
                        else {
                            ruleParam1.hide();
                            ruleParam2.hide();
                            ruleParam3.hide();
                        }
                    }

                });
            }
        }

        const handleAppliesToAll = function() {
            let groupFields = document.querySelector('#' + groupName +'_repeat');
            //iterate over all children, remove the last child because it is the new row
            for (let i = 0; i < groupFields.childElementCount - 1; i++){
                let currentGroup = groupID + '-' + i + '-';

                //find all of our fields
                let ruleAppliesAll = $('.' + currentGroup + ruleAppliesAllID).find('.cmb2-option');
                let ruleAppliesCategories = $('.' + currentGroup + ruleAppliesCategoriesID);

                //bind events
                ruleAppliesAll.change(function () {handleAppliesToAll();});
                console.log(ruleAppliesAll);
                if (ruleAppliesAll.prop('checked')){
                    console.log("checked");
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
