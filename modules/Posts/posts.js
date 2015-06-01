ForumPosts = new function() {
	this.delete = function(form, thread_id, post_id) {
        form.record.value = post_id;
        form.action.value = 'DetailView';
        form.return_module.value = 'Threads';
        form.return_action.value = 'DetailView';
        form.return_record.value =  thread_id;
        form.thread_id.value =  thread_id;
        form.record_perform.value =  'delete';
    	form.return_layout.value = 'Standard';
		form.return_panel.value = 'posts';
		form.submit();
	};

	this.edit = function(form, thread_id, post_id) {
		form.reply.value = '';
		form.quote.value = '';
        form.record.value = post_id;
        form.action.value = 'EditView';
        form.return_module.value = 'Threads';
        form.return_action.value = 'DetailView';
        form.return_record.value =  thread_id;
        form.thread_id.value =  thread_id;
        form.record_perform.value =  '';
    	form.return_layout.value = 'Standard';
		form.return_panel.value = 'posts';
		form.submit();
	};

	this.reply = function(form, thread_id, post_id) {
		form.reply.value = post_id;
		form.quote.value = '';
        form.record.value = '';
        form.action.value = 'EditView';
        form.return_module.value = 'Threads';
        form.return_action.value = 'DetailView';
        form.return_record.value =  thread_id;
        form.thread_id.value =  thread_id;
        form.record_perform.value =  '';
    	form.return_layout.value = 'Standard';
		form.return_panel.value = 'posts';
		form.submit();
	};

	this.quote = function(form, thread_id, post_id) {
		form.reply.value = '';
		form.quote.value = post_id;
        form.record.value = '';
        form.action.value = 'EditView';
        form.return_module.value = 'Threads';
        form.return_action.value = 'DetailView';
        form.return_record.value =  thread_id;
        form.thread_id.value =  thread_id;
        form.record_perform.value =  '';
    	form.return_layout.value = 'Standard';
		form.return_panel.value = 'posts';
		form.submit();
	};
}();
