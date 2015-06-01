/*****************************************************************************
 * The contents of this file are subject to The Long Reach Corporation
 * Software License Version 1.0 ("License"); You may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 * <http://www.thelongreach.com/swlicense.html>
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations under
 * the License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) The Long Reach Corporation copyright notice,
 * (ii) the "Powered by SugarCRM" logo, and
 * (iii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is:
 *    Info At Hand Add-on Module to SugarCRM Open Source project.
 * The Initial Developer of this Original Code is The Long Reach Corporation
 * and it is Copyright (C) 2004-2007 by The Long Reach Corporation;
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
 *****************************************************************************
 * $Id$
 * File Description:
 * Contributor(s):
*****************************************************************************/

// these are defined in the calling document
var start_date;
var end_date;
var financials_data;
var financials_changed;
var row_remove_text;
var row_remove_img;
var json_fields = ['expected_cost', 'expected_revenue', 'actual_cost', 'actual_revenue'];

var readonly_fields = {
    'expected_cost': true
};

function dateUpdated(name, val) {
	if(name == 'date_starting')
		start_date = val;
	else if(name == 'date_ending')
		end_date = val;
	displayFinancials();
}

function setStartDate(date) {
	start_date = date;
	displayFinancials();
}

function setEndDate(date) {
	end_date = date;
	displayFinancials();
}

function displayFinancials() {
	var financialsTable = $('financialsTable');
	if (financialsTable) {
		financialsTable.style.visibility = 'hidden';
		for(var i = financialsTable.rows.length; i > 1; i--)
			financialsTable.deleteRow(1);
		displayFinancialsRows(financialsTable);
		financialsTable.style.visibility = 'visible';
	}	
}

function getFinancialPeriods() {

	function mkPeriod(year, month) {
		if(month < 10)
			return year + '-0' + month;
		return year + '-' + month;
	}
	
	var periodStyles = {};
	if(start_date && end_date) {
		var year = start_date.getFullYear();
		var month = start_date.getMonth() + 1;
		while(year < end_date.getFullYear()) {
			while(month <= 12) {
				periodStyles[mkPeriod(year, month)] = 'in';
				month++;
			}
			month = 1;
			year++;
		}
		while(month <= end_date.getMonth() + 1) {
			periodStyles[mkPeriod(year, month)] = 'in';
			month++;
		}
	}
    if (financials_data.length > 0) {
        for(var period in financials_data) {
            if(! isset(periodStyles[period]))
                periodStyles[period] = 'out';
        }
    }
	var periodList = [];
	for(var period in periodStyles)
		periodList[periodList.length] = [period, periodStyles[period]];
	periodList.sort();
	return periodList;
}

function displayFinancialsRows(table) {
	if(! isset(financials_data))
		return;
	var rowCount = 1;
	var periods = getFinancialPeriods();
    var current_periods = {};

    for (var i = 0; i < periods.length; i ++) {
        var period = periods[i][0];
        current_periods[period] = 1;
        var style = periods[i][1];
		var tr = table.insertRow(rowCount++);
		var cellCount = 0;
		var td = tr.insertCell(cellCount++);
		td.className = "dataLabel";
		if(style == 'out')
			td.innerHTML = '<span class="error">' + period + '</span>';
		else
			td.innerHTML = period;
        for(var j = 0; j < json_fields.length; j++) {
			field = json_fields[j];
			td = tr.insertCell(cellCount++);
			td.className = "dataField";
			textField = createElement2('input', {type: 'text', size: 15, className: 'input-text input-outer'});
			textField.period = period;
			textField.field = field;
            textField.disabled = !!readonly_fields[field];
			if(! isset(financials_data[period]) || ! isset(financials_data[period][field]))
				textField.value = '';
			else
				textField.value = formatCurrency(financials_data[period][field]);
			textField.onchange = function() {
				this.value = inputFinancialData(this.period, this.field, this.value);
			};
			td.appendChild(textField);
		}
		td = tr.insertCell(cellCount++);
		td.style.whiteSpace = 'nowrap';
		var remlink = document.createElement('a');
		remlink.href = '#';
		remlink.className = 'listViewTdToolsS1';
		remlink.period = period;
		remlink.is_out = (style == 'out');
		remlink.onclick = function() { removeDataRow(this.period, this.is_out, true); return false; }
		remlink.innerHTML = row_remove_img;
		td.appendChild(remlink);
	}

    clearData(current_periods);
}

function clearData(periods) {
    for(period in financials_data) {
        if (period in periods) {
            //do nothing
        } else {
            removeDataRow(period, false, false);
        }
    }
}

function formatCurrency(value) {
	var decimals = SUGAR.ui.getFormInput('DetailForm', 'currency_id').getDecimals();
	var r = stdFormatNumber(parseFloat(value).toFixed(decimals), decimals, decimals);
	return r;
}

function setFinancialData(period, field, value) {
	if(! isset(financials_data[period])) {
		if(value == 0)
			return;
		financials_data[period] = {};
	}
	if(! isset(financials_changed[period]))
		financials_changed[period] = {};
	financials_data[period][field] = value;
	financials_changed[period][field] = value;
}

function inputFinancialData(period, field, value) {
	newval = parseFloat(stdUnformatNumber(value));
	if(isNaN(newval))
		newval = 0;
	setFinancialData(period, field, newval);
	return formatCurrency(newval);
}

function removeDataRow(period, is_out, display) {
	if(! isset(financials_data[period]))
		return;
	for(var field in financials_data[period])
		setFinancialData(period, field, 0);
	if(is_out)
		delete financials_data[period];
    if (display)
	    displayFinancials();
}

function updateFinancialsRate(old_rate, new_rate) {
	for(period in financials_data) {
		for(var field in financials_data[period]) {
			value = financials_data[period][field];
			newval = (value * new_rate / old_rate);
			setFinancialData(period, field, newval);
		}
	}
	displayFinancials();
}

function useTimesheets(use) {
    readonly_fields['actual_cost'] = use;
    displayFinancials();
}

FinancialsEditor = new (function() {
	var editor = this;
	this.id = 'FinancialsEditor';
	
	this.setup = function() {
		financials_changed = {};
		SUGAR.ui.onInitForm(this.form, function() { editor.load(); });
	}
	
	this.load = function() {
		var inp_start = SUGAR.ui.getFormInput(this.form, 'date_starting'),
			inp_end = SUGAR.ui.getFormInput(this.form, 'date_ending');
		start_date = inp_start.getValue(true);
		end_date = inp_end.getValue(true);
		var cb = function() { dateUpdated(this.name, this.getValue(true)); }
		SUGAR.ui.attachInputEvent(inp_start, 'onchange', cb);
		SUGAR.ui.attachInputEvent(inp_end, 'onchange', cb);
		displayFinancials();
		SUGAR.ui.attachFormInputEvent(this.form, 'currency_id', 'onrateupdate', updateFinancialsRate);
	}
	
	this.beforeSubmitForm = function() {
		this.form.financials_data.value = JSON.stringify(financials_changed);
    }
	
	return this;
})();
