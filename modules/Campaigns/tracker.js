//this function toggles the tracker values based on whether the opt out check box is selected
function toggle_tracker_url(isoptout) {
    tracker_url = document.getElementById('tracker_url');
    if (isoptout.checked) {
        tracker_url.disabled=true;
        tracker_url.value="removeme.php";
    } else {
        tracker_url.disabled=false;
    }
}

//create variables that will be used to monitor the number of tracker url
var trackers_added = 0;
//variable that will be passed back to server to specify list of trackers
var list_of_trackers_array = new Array();

//this function adds selected tracker to list
function add_tracker() {
    //perform validation
    if(validate_step3()) {
    //increment tracker count value
        trackers_added++;
        document.getElementById('added_tracker_count').value = trackers_added ;
        //get the appropriate values from tracker form
        var trkr_name = document.getElementById('tracker_name');
        var trkr_url = document.getElementById('tracker_url');
        var trkr_opt = document.getElementById('is_optout');
        var trkr_opt_checked = '';
        if(trkr_opt.checked){trkr_opt_checked = 'checked';	}
        //construct html to display chosen tracker
        var trkr_name_html = "<input id='tracker_name"+trackers_added +"' type='text' size='20' maxlength='255' name='wiz_step3_tracker_name"+trackers_added+"' title='"+EDIT_TRACKER_NAME+trackers_added+"' value='"+trkr_name.value+"' >";
        var trkr_url_html = "<input type='text' size='60' maxlength='255' name='wiz_step3_tracker_url"+trackers_added+"' title='"+EDIT_TRACKER_URL+trackers_added+"' id='tracker_url"+trackers_added+"' value='"+trkr_url.value+"' >";
        var trkr_opt_html = "<input name='wiz_step3_is_optout"+trackers_added+"' title='"+EDIT_OPT_OUT+trackers_added+"' id='is_optout"+trackers_added+"' class='checkbox' type='checkbox' "+trkr_opt_checked+" />";
        //display the html
        var trkr_html = "<div id='trkr_added_"+trackers_added+"'> <table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td width='15%' class='evenListRowS1'>"+trkr_opt_html+"</td><td width='40%' class='evenListRowS1'>"+trkr_name_html+"</td><td width='40%' class='evenListRowS1'>"+trkr_url_html+"</td><td class='evenListRowS1'><a href='#' onclick=\"javascript:remove_tracker('trkr_added_"+trackers_added+"','"+trackers_added+"'); \" >  <img src='"+image_path+"delete_inline.gif' alt='rem' align='absmiddle' border='0' height='12' width='12'>"+REMOVE+"</a></td></tr></table></div>";
        document.getElementById('added_trackers').innerHTML = document.getElementById('added_trackers').innerHTML + trkr_html;

        //add values to array in string, seperated by "@@" characters
        list_of_trackers_array[trackers_added] = trkr_name.value+"@@"+trkr_opt.checked+"@@"+trkr_url.value;
        //assign array to hidden input, which will be used by server to process array of trackers
        document.getElementById('wiz_list_of_trackers').value = list_of_trackers_array.toString();

        //now lets clear the form to allow input of new tracker
        trkr_name.value = '';
        trkr_url.disabled = false;
        trkr_url.value = '';
        trkr_opt.checked = false;
        if(trackers_added == 1) {
            document.getElementById('no_trackers').style.display='none';
        }
    }
}

//this function will remove the selected tracker from the ui, and from the tracker array
function remove_tracker(div,num){
    //clear UI
    var trkr_div = document.getElementById(div);
    trkr_div.style.display = 'none';
    trkr_div.parentNode.removeChild(trkr_div);
    //clear tracker array from this entry and assign to form input
    list_of_trackers_array[num] = '';
    document.getElementById('wiz_list_of_trackers').value = list_of_trackers_array.toString();
}

//this function will remove the existing tracker from the ui, and add it's value to an array for removal upon save
function remove_existing_tracker(div,id){
    //clear UI
    var trkr_div = document.getElementById(div);
    trkr_div.style.display = 'none';
    trkr_div.parentNode.removeChild(trkr_div);
    //assign this id to form input for removal
    document.getElementById('wiz_remove_tracker_list').value += ','+id;
}

/**
*This function will iterate through list of trackers and gather all the values.  It will
*populate these values, seperated by delimiters into hidden inputs for processing
*/
function gatherTrackers() {
    //start with the newly added trackers, get count of total added
    count = parseInt(trackers_added);
    final_list_of_trackers_array = new Array();
    //iterate through list of added trackers
    for(i=1;i<=count;i++){
        //make sure all values exist
        if( document.getElementById('tracker_name'+i)  &&  document.getElementById('is_optout'+i)  &&  document.getElementById('tracker_url'+i) ){
            //make sure the check box value is int (0/1)
            var opt_val = '0';
            if(document.getElementById('is_optout'+i).checked){opt_val =1;}
            //add values for this tracker entry into array of tracker entries
            final_list_of_trackers_array[i] = document.getElementById('tracker_name'+i).value+"@@"+opt_val+"@@"+document.getElementById('tracker_url'+i).value;
        }
    }
    //assign array of tracker entries to hidden input, which will be used by server to process array of trackers
    document.getElementById('wiz_list_of_trackers').value = final_list_of_trackers_array.toString();

    //Now lets process existing trackers, get count of existing trackers
    count = parseInt(document.getElementById('existing_tracker_count').value);
    final_list_of_existing_trackers_array = new Array();
    //iterate through list of existing trackers
    for(i=0;i<count;i++){
        //make sure all values exist
        if( document.getElementById('existing_tracker_name'+i)  &&  document.getElementById('existing_is_optout'+i)  &&  document.getElementById('existing_tracker_url'+i) ){
            //make sure the check box value is int (0/1)
            var opt_val = '0';
            if(document.getElementById('existing_is_optout'+i).checked){opt_val =1;}
            //add values for this tracker entry into array of tracker entries
            final_list_of_existing_trackers_array[i] = document.getElementById('existing_tracker_id'+i).value+"@@"+document.getElementById('existing_tracker_name'+i).value+"@@"+opt_val+"@@"+document.getElementById('existing_tracker_url'+i).value;
        }
    }
    //assign array of tracker entries to hidden input, which will be used by server to process array of trackers
    document.getElementById('wiz_list_of_existing_trackers').value = final_list_of_existing_trackers_array.toString();
}


/*
* this is the custom validation script that will validate the fields on step3 of wizard
* this is called directly from the add tracker button
*/

function validate_step3() {
    requiredTxt = SUGAR.language.get('app_strings', 'ERR_MISSING_REQUIRED_FIELDS');
    var stepname = 'wiz_step3_';
    var has_error = 0;
    var fields = new Array();
    fields[0] = 'tracker_name';
    fields[1] = 'tracker_url';
    //loop through and check for empty strings ('  ')
    var field_value = '';
    if( (trim(document.getElementById(fields[0]).value) !='') ||  (trim(document.getElementById(fields[1]).value) !='')){
        for (i=0; i < fields.length; i++){
            field_value = trim(document.getElementById(fields[i]).value);
            if(field_value.length<1){
              add_error_style('DetailForm', fields[i], requiredTxt +' ' +document.getElementById(fields[i]).title );
              has_error = 1;
            }
        }
    }else{
        //no values have been entered, return false without error
        return false;
    }
    //error has been thrown, return false
    if(has_error == 1){
        return false;
    }

    return true;
    //add fields to validation and call generic validation script
    //if(validate['wizform']!='undefined'){delete validate['wizform']};
    //addToValidate('wizform', 'tracker_name', 'alphanumeric', false,  document.getElementById('tracker_name').title);
    //addToValidate('wizform', 'tracker_url', 'alphanumeric', false,  document.getElementById('tracker_url').title);
    //return check_form('wizform');

}

