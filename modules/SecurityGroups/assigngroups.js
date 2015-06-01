function confirm_massassign(del,start_string, end_string) {
	if (del == 1) {
		return confirm( start_string + sugarListView.get_num_selected()  + end_string);
	}
	else {
		return confirm( start_string + sugarListView.get_num_selected()  + end_string);
	}
}

function send_massassign(mode, no_record_txt, start_string, end_string, del) {

	//if(!sugarListView.confirm_action(del, start_string, end_string))
	if (!sListView.send_mass_update(mode, no_record_txt, 2))
		return false;

	if(document.MassAssign_SecurityGroups.massassign_group.selectedIndex == 0) {
		alert("Please select a group and try again.");
		return false;	
	}
	 
	if (document.MassUpdate.select_entire_list &&
		document.MassUpdate.select_entire_list.value == 1)
		mode = 'entire';
	else if (document.MassUpdate.massall.checked == true)
		mode = 'page';
	else
		mode = 'selected';

	var ar = new Array();
	if(del == 1) {
		var deleteInput = document.createElement('input');
		deleteInput.name = 'Delete';
		deleteInput.type = 'hidden';
		deleteInput.value = true;
		document.MassAssign_SecurityGroups.appendChild(deleteInput);
	}
	switch(mode) {
		case 'page':
			document.MassAssign_SecurityGroups.uid.value = '';
			for(wp = 0; wp < document.MassUpdate.elements.length; wp++) {
				if(typeof document.MassUpdate.elements[wp].name != 'undefined'
					&& document.MassUpdate.elements[wp].name == 'mass[]' && document.MassUpdate.elements[wp].checked) {
							ar.push(document.MassUpdate.elements[wp].value);
				}
			}
			document.MassAssign_SecurityGroups.uid.value = ar.join(',');
			if(document.MassAssign_SecurityGroups.uid.value == '') {
				alert(no_record_txt);
				return false;
			}
			break;
		case 'selected':
			/*for(wp = 0; wp < document.MassUpdate.elements.length; wp++) {
				if(typeof document.MassUpdate.elements[wp].name != 'undefined'
					&& document.MassUpdate.elements[wp].name == 'mass[]'
						&& document.MassUpdate.elements[wp].checked) {
							ar.push(document.MassUpdate.elements[wp].value);
				}
			}
			if(document.MassAssign_SecurityGroups.uid.value != '') document.MassAssign_SecurityGroups.uid.value += ',';
			document.MassAssign_SecurityGroups.uid.value += ar.join(',');*/
			document.MassAssign_SecurityGroups.uid.value = document.MassUpdate.uid.value;
			if(document.MassAssign_SecurityGroups.uid.value == '') {
				alert(no_record_txt);
				return false;
			}
			break;
		case 'entire':
			var entireInput = document.createElement('input');
			entireInput.name = 'entire';
			entireInput.type = 'hidden';
			entireInput.value = 'index';
			document.MassAssign_SecurityGroups.appendChild(entireInput);
			//confirm(no_record_txt);
			break;
	}

	document.MassAssign_SecurityGroups.submit();
	return false;
}
