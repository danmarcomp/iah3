function load_vcard(form) {
	if(! form || ! form.record)
		return;
	var id = form.record.value;
	SUGAR.util.loadUrl('vCard.php?module=Contacts&record=' + encodeURIComponent(id), false);
}
