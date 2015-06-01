function change_state(radiobutton) {
    if (radiobutton.value == '1') {
        radiobutton.form['massemailer_tracking_entities_location'].disabled=true;
        radiobutton.form['massemailer_tracking_entities_location'].value=LOCATION_DEFAULT;
    } else {
        radiobutton.form['massemailer_tracking_entities_location'].disabled=false;
        radiobutton.form['massemailer_tracking_entities_location'].value=null;
    }
}

//this function toggles visibility of fields based on selected options
function notify_setrequired() {
    var f = document.getElementById("DetailForm");
    var smtpauth_req = f.elements.mail_smtpauth_req;
    var smtpauth_req_val = smtpauth_req.value;
    var create_mbox = f.elements.create_mbox;
    var create_mbox_val = create_mbox.value;
    document.getElementById("smtp_settings").style.display = (f.mail_sendtype.value == "SMTP") ? "inline" : "none";
    document.getElementById("smtp_settings").style.visibility = (f.mail_sendtype.value == "SMTP") ? "visible" : "hidden";
    document.getElementById("smtp_auth").style.display = (smtpauth_req_val == 1) ? "inline" : "none";
    document.getElementById("smtp_auth").style.visibility = (smtpauth_req_val == 1) ? "visible" : "hidden";
    document.getElementById("new_mbox").style.display = (create_mbox_val == 1) ? "inline" : "none";
    document.getElementById("new_mbox").style.visibility = (create_mbox_val == 1) ? "visible" : "hidden";
    document.getElementById("wiz_new_mbox").value = (create_mbox_val == 1) ? "1" : "0";
    return true;
}

//this function will copy as much information as possible from the first step in wizard
//onto the the second step in wizard
function copy_down() {
    document.getElementById("name").value = document.getElementById("notify_fromname").value;
    document.getElementById("email_user").value = document.getElementById("notify_fromaddress").value;
    document.getElementById("from_addr").value = document.getElementById("notify_fromaddress").value;
    if(document.DetailForm.mail_sendtype.value=='SMTP'){
        document.getElementById("protocol").value = "SMTP";
        document.getElementById("server_url").value = document.getElementById("mail_smtpserver").value;
        if(document.DetailForm.mail_smtpauth_req.value == 1){
            document.getElementById("email_user").value = document.getElementById("mail_smtpuser").value;
            document.getElementById("email_password").value = document.getElementById("mail_smtppass").value;
        }

    }
    return true;
}

//this calls the validation functions for each step that needs validation
function validate_wiz_form(step){
    switch (step){
        case 'step1':
        if(!validate_step1()){return false;}
          copy_down();
        break;
        case 'step2':
       if(!validate_step2()){return false;}
        break;
        default://no additional validation needed
    }
    return true;

}

//this function will add validation to step1
function validate_step1(){
    requiredTxt = SUGAR.language.get('app_strings', 'ERR_MISSING_REQUIRED_FIELDS');
    var haserrors = 0;
    var fields = new Array();

    //create list of fields that need validation, based on selected options
    fields[0] = 'notify_fromname';
    fields[1] = 'notify_fromaddress';
    fields[2] = 'massemailer_campaign_emails_per_run';
    fields[3] = 'massemailer_tracking_entities_location';
    if(document.DetailForm.mail_sendtype.value=='SMTP'){
        fields[4] = 'mail_smtpserver';
        fields[5] = 'mail_smtpport';
            if(document.DetailForm.mail_smtpauth_req.value == 1){
                fields[6] = 'mail_smtpuser';
                fields[7] = 'mail_smtppass';
            }
    }

    var field_value = '';
    //iterate through required fields and set empty string values ('  '') to null, this will cause failure later on
    for (i=0; i < fields.length; i++){
        elem = document.getElementById(fields[i]);
        field_value = trim(elem.value);
        if(field_value.length<1){
            elem.value = '';
        }
    }
    //add to generic validation and call function to calidate
    if(validate['DetailForm']!='undefined'){delete validate['DetailForm']};
    addToValidate('DetailForm', 'notify_fromaddress', 'email', true,  document.getElementById('notify_fromaddress').title);
    addToValidate('DetailForm', 'notify_fromname', 'alphanumeric', true,  document.getElementById('notify_fromname').title);
    addToValidate('DetailForm', 'massemailer_campaign_emails_per_run', 'int', true,  document.getElementById('massemailer_campaign_emails_per_run').title);
    addToValidate('DetailForm', 'massemailer_tracking_entities_location', 'alphanumeric', true,  document.getElementById('massemailer_tracking_entities_location').title);
    if(document.DetailForm.mail_sendtype.value=='SMTP'){
        addToValidate('DetailForm', 'mail_smtpserver', 'alphanumeric', true,  document.getElementById('mail_smtpserver').title);
        addToValidate('DetailForm', 'mail_smtpport', 'int', true,  document.getElementById('mail_smtpport').title);
            if(document.DetailForm.mail_smtpauth_req.value == 1){
                addToValidate('DetailForm', 'mail_smtpuser', 'alphanumeric', true,  document.getElementById('mail_smtpuser').title);
                addToValidate('DetailForm', 'mail_smtppass', 'alphanumeric', true,  document.getElementById('mail_smtppass').title);
            }
    }


    return true;
}



function validate_step2(){
    requiredTxt = SUGAR.language.get('app_strings', 'ERR_MISSING_REQUIRED_FIELDS');
    //validate only if the create mailbox form input has been selected
    if(document.getElementById("wiz_new_mbox").value == "0"){
     //this form is not checked, do not validate
     return true;
    }
    var haserrors = 0;
    var wiz_message = document.getElementById('wiz_message');

    //create list of fields that need validation, based on selected options
    var fields = new Array();
    fields[0] = 'name';
    fields[1] = 'server_url';
    fields[2] = 'email_user';
    fields[3] = 'protocol';
    fields[4] = 'email_password';
    fields[5] = 'mailbox';
    fields[6] = 'port';

    //iterate through required fields and set empty string values ('  '') to null, this will cause failure later on
    var field_value = '';
    for (i=0; i < fields.length; i++){
        field_value = trim(document.getElementById(fields[i]).value);
        if(field_value.length<1){
            add_error_style('DetailForm', fields[i], requiredTxt +' ' +document.getElementById(fields[i]).title );
            haserrors = 1;
        }
    }

    //add to generic validation and call function to calidate
    if(validate['DetailForm']!='undefined'){delete validate['DetailForm']};
    addToValidate('DetailForm', 'name', 'alphanumeric', true,  document.getElementById('name').title);
    addToValidate('DetailForm', 'server_url', 'alphanumeric', true,  document.getElementById('server_url').title);
    addToValidate('DetailForm', 'email_user', 'alphanumeric', true,  document.getElementById('email_user').title);
    addToValidate('DetailForm', 'email_password', 'alphanumeric', true,  document.getElementById('email_password').title);
    addToValidate('DetailForm', 'mailbox', 'alphanumeric', true,  document.getElementById('mailbox').title);
    addToValidate('DetailForm', 'protocol', 'alphanumeric', true,  document.getElementById('protocol').title);
    addToValidate('DetailForm', 'port', 'int', true,  document.getElementById('port').title);

    if(haserrors == 1){
        return false;
    }
    return true;


}

/*
 * The generic create summary will not work for this wizard, as we have a step that only gets
 * displayed if a check box is marked, and we also have certain inputs we do not want displayed(ie. password)
 * so this function will override the genereic version
 */
function create_summary(){
    var current_step = document.getElementById('wiz_current_step');
    var currentValue = parseInt(current_step.value);
    var temp_elem = '';

    //  alert(test.title);alert(test.name);alert(test.id);
    var fields = new Array();
    //create the list of fields to create summary table
    fields[0] = 'notify_fromname';
    fields[1] = 'notify_fromaddress';
    fields[2] = 'massemailer_campaign_emails_per_run';
    fields[3] = 'massemailer_tracking_entities_location';

     if(document.DetailForm.mail_sendtype.value=='SMTP'){
          fields[4] = 'mail_smtpserver';
          fields[5] = 'mail_smtpport';
             if(document.DetailForm.mail_smtpauth_req.value == 1){
                  fields[6] = 'mail_smtpuser';
             }
      }

      if(document.getElementById("wiz_new_mbox").value != "0"){
            fields[7] = 'name';
            fields[8] = 'server_url';
            fields[9] = 'email_user';
            fields[10] = 'from_addr';
            fields[11] = 'protocol';
            fields[12] = 'mailbox';
            fields[13] = 'port';
      }

    //iterate through list and create table
    var summhtml = "<table class='tabDetailView' width='100%' border='0' cellspacing='1' cellpadding='1'>";
    var colorclass = 'tabDetailViewDF2';
    var elem ='';
    for (var i=0; i<fields.length; i++)
      {elem = document.getElementById(fields[i]);
        if(elem!=null){
            if(elem.type.indexOf('select') >= 0 ){
                var selInd = elem.selectedIndex;
                if(selInd<0){selInd =0;}
                summhtml = summhtml+ "<tr  class='"+colorclass+"' ><td class='tabDetailViewDL'  width='15%'><b>" +elem.title+ "</b></td><td class='tabDetailViewDF'  width='30%'>" + elem.options[selInd].text+ "&nbsp;</td></tr>";
            }else if(elem.type == 'checked'){
                summhtml = summhtml+ "<tr  class='"+colorclass+"' ><td class='tabDetailViewDL'  width='15%'><b>" +elem.title+ "</b></td><td class='tabDetailViewDF'  width='30%'>" + elem.value + "&nbsp;</td></tr>";
            }else {
                summhtml = summhtml+ "<tr  class='"+colorclass+"' ><td class='tabDetailViewDL'  width='15%'><b>" +elem.title+ "</b></td><td class='tabDetailViewDF'  width='30%'>" + elem.value + "&nbsp;</td></tr>";
            }
        }
        if( colorclass== 'tabDetailViewDL2'){
            colorclass= 'tabDetailViewDF2';
        }else{
            colorclass= 'tabDetailViewDL2'
        }
      }

    summhtml = summhtml+ "</table>";
    temp_elem = document.getElementById('wiz_summ');
    temp_elem.innerHTML = summhtml;

}

function hide_show_imap_folder() {
    var protocol = document.DetailForm.protocol.value;
    document.getElementById('imap_folder_label').style.visibility = document.getElementById('imap_folder_name').style.visibility = (protocol == 'IMAP') ? 'visible' : 'hidden';
}
function set_default_port() {
    var imap = document.DetailForm.protocol.value == 'IMAP';
    var ssl = document.DetailForm.use_ssl.value == 1;
    if (imap) {
        document.getElementById('port').value = ssl ? '993' : '143';
    } else {
        document.getElementById('port').value = ssl ? '995' : '110';
    }
}

function init_form() {
    hide_show_imap_folder();
    showfirst('email');
    notify_setrequired();
}

setTimeout ("init_form()", 200);
