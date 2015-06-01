var SocialAccount = new function() {
	this.id = 'SocialAccount';
    this.add_button_id = 'add_social_acc';
    this.error_block_id = 'social_error_msg';
    this.popup = null;
    this.module = null;
    this.table = null;
    this.menu_button = null;
    this.networks = {};
    this.rows = [];
    this.updated = false;
    
    this.init = function(module, networks, rows) {
    	this.module = module;
    	this.networks = networks;
    	this.rows = rows;
		this.table = $('social_accounts');
    }
    
    this.setup = function() {
    	var self = this;
    	this.menu_button = SUGAR.ui.getMenuSource(this.form, 'add_social_network');
		SUGAR.ui.attachInputEvent(this.menu_button, 'onchange',
			function(k) { self.handleSelectNetwork(k); });
    	this.updateDisplay();
    }
    
    this.updateDisplay = function() {
    	if(this.menu_button) {
    		var opts = {keys: [], values: this.networks ? this.networks.values : {}}, seen = {}, i;
    		if(this.rows) {
    			for(i = 0; i < this.rows.length; i++)
    				seen[this.rows[i].type] = 1;
    		}
    		if(this.networks.keys) {
    			for(i = 0; i < this.networks.keys.length; i++)
    				if(! seen[this.networks.keys[i]])
						opts.keys.push(this.networks.keys[i]);
    		}
    		this.menu_button.menu.setOptions(new SUGAR.ui.SelectOptions(opts));
    	}
    	if(this.table) {
    		SUGAR.ui.clearChildNodes(this.table);
    		if(this.rows) {
				for(var i = 0; i < this.rows.length; i++)
					this.renderRow(i, this.rows[i], this.table.insertRow(this.table.rows.length));
			}
    	}
    }
    
    this.addRow = function(row) {
    	this.rows.push(row);
    	this.updated = true;
    	this.updateDisplay();
    }

	this.removeRow = function(index) {
        var msg = mod_string('LBL_SOCIAL_DELETE_CONFIRMATION', 'SocialAccounts');

        if (this.rows && this.rows[index] && confirm(msg)) {
            this.rows.splice(index, 1);
			this.updated = true;
            this.updateDisplay();
        }
	};
	
	this.handleSelectNetwork = function(network) {
		if(network) {
			this.showAddPopup(network);
		}
	};

    this.showAddPopup = function(network) {
        if (network) {
        	var params = {width: '500px', title_text: mod_string('LBL_SOCIAL_ADD_POPUP_TITLE', 'SocialAccounts'), resizable: false};
        	params.onshow = function() { SUGAR.ui.focusForm('AddSocialForm'); }
            this.popup = SUGAR.popups.openUrl('async.php?module=SocialAccounts&action=ShowDialog&type=' +network, null, params);
            return this.popup;
        }
        return false;
    };

    this.add = function(form_id) {
        var form = $(form_id);

        if (form.profile_url.value != '') {
            var error_id = this.error_block_id;
            $(error_id).innerHTML = '&nbsp;';

            function ready() {
                var row = this.getResult();

                if (row && row.result == 'ok') {
                    SocialAccount.addRow(row.data);
                    SUGAR.popups.close();
                } else {
                    $(error_id).innerHTML = mod_string('LBL_SOCIAL_PARSING_URL_ERROR', 'SocialAccounts');
                }
            }

            var query_params = {'module': 'SocialAccounts', 'url': form.profile_url.value, 'type': form.network.value, 'related_type': form.related_type.value, 'related_id': form.related_id.value};
            var req = new SUGAR.conn.JSONRequest('add_social_account', {status_msg: app_string('LBL_LOADING')}, query_params);
            req.fetch(ready);
        } else {
            $(this.error_block_id).innerHTML = mod_string('LBL_SOCIAL_PARSING_URL_ERROR', 'SocialAccounts');
        }
    };

    this.renderRow = function(index, data, acc_row) {
        acc_row.setAttribute('id', this.id + '_row_' + index);
        var cell1 = acc_row.insertCell(0);
        cell1.setAttribute('width', '32');
        cell1.setAttribute('class', 'dataLabel');
        var link = createElement2('a', {href: data.url, target: '_blank'});
        link.style.outline = 'none';
        link.innerHTML = data.icon;
        cell1.appendChild(link);
        var cell2 = acc_row.insertCell(1);
        cell2.setAttribute('width', '400');
        cell2.setAttribute('class', 'dataLabel');
        var tlink = createElement2('div', {className: 'input-icon icon-tlink'});
        cell2.appendChild(tlink);
        cell2.appendChild(nbsp());
        var link2 = createElement2('a', {href: data.url, target: '_blank', className: 'tabDetailViewDFLink'});
		link2.appendChild(document.createTextNode(data._display));
		cell2.appendChild(link2);
        var cell3 = acc_row.insertCell(2);
        cell3.setAttribute('class', 'dataLabel');
        createElement2('div', {className: 'input-icon icon-delete active-icon',
        	onclick: function() { SocialAccount.removeRow(index); }}, null, cell3);
    };
    
	this.beforeSubmitForm = function(form) {
		var ret = this.rows, input;
		input = form.social_accounts;
		if(! input) input = createElement2('input', {type: 'hidden', name: 'social_accounts'}, null, form);
		input.value = JSON.stringify(ret);
	};
}();