//this function will toggle the popup forms to be read only if "Default" is selected,
//and enable the pop up select if "Custom" is selected
function change_target_list(radiobutton,list) {
    var def_value ='';
    if(list == 'subs'){
        list_name = 'wiz_step3_subscription_name';
        def_id = subscription_id;
        def_value = subscription_name;
    }
    if(list == 'unsubs'){
        list_name = 'wiz_step3_unsubscription_name';
        def_id = unsubscription_id;
        def_value = unsubscription_name;
    }
    if(list == 'test'){
        list_name = 'wiz_step3_test_name';
        def_id = test_id;
        def_value = test_name;
    }
    //default selected, set inputs to read only
    if (radiobutton.value == '1') {
        radiobutton.form[list_name].disabled=true;
        radiobutton.form[list_name+"_button"].style.visibility='hidden';
        radiobutton.form[list_name+"_id"].value=def_id;
        //call function that populates the default value
        change_target_list_names(list,def_value);
    } else {
        //custom selected, make inputs editable
        radiobutton.form[list_name].disabled=false;
        radiobutton.form[list_name+"_button"].style.visibility='visible';
        radiobutton.form[list_name].value='';
        radiobutton.form[list_name+"_id"].value='';
    }
}

//this function will populate the "default" name on the target list.  It will either do one,
//if specified, or all three widgets, if blank idis passed in
function change_target_list_names(list, def_value) {
    //id was passed in, create the listname and inputname variables
    if(list != '') {
       switch (list){
            case 'subs':
                listname = SUBSCRIPTION_LIST;
                inputname = 'subscription_name';
            break;
            case 'unsubs':
                listname = UNSUBSCRIPTION_LIST;
                inputname = 'unsubscription_name';
            break;
            case 'test':
                listname = TEST_LIST;
                inputname = 'test_name';
            break;
            default:
                listname = '';
                inputname = '';
        }
    }
    //populate specified input with default value
    if(def_value==''){
    def_value = document.getElementById('name').value + ' ' + listname;}
    document.getElementById(inputname).value = def_value;
}