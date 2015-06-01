ModuleDesignerRel = new function()
{

	this.init = function(module, moduleData)
	{
		this.module = module;
		this.moduleData = moduleData;
		this.newRelationships = {};
	};

	this.add = function(module)
	{
		if (!module.length)
			return;
		var relId = this.module.toLowerCase() + '_' + module.toLowerCase() + '_' + (new Date()).getTime();
		var table = $('rels_table');
		var row = table.insertRow(table.rows.length);
		row.className = 'evenListRowS1';
		row.id = relId;
	
		var cell = row.insertCell(0);
		cell.className = 'tabDetailViewDL2';
		cell.innerHTML = this.moduleData[module].icon + '&nbsp;';
		cell.appendChild(document.createTextNode(this.moduleData[module].label));

		var cell = row.insertCell(1);
		cell.className = 'tabDetailViewDL2';
		cell.appendChild(document.createTextNode(this.moduleData[module].bean));

		this.newRelationships[relId] = module;

		var cell = row.insertCell(2);
		cell.className = 'tabDetailViewDL2';
		cell.appendChild(document.createTextNode(relId));

		var cell = row.insertCell(3);
		cell.className = 'tabDetailViewDL2';
		var div = document.createElement('div');
		div.className = 'input-icon icon-delete active-icon';
		div.onclick = function()
		{
			ModuleDesignerRel.remove(relId);
		};
		cell.appendChild(div);
		

	};

	this.remove = function(relId)
	{
		$('rels_table').deleteRow($(relId).rowIndex-1);
		delete this.newRelationships[relId];
	};

	this.save = function(form)
	{
		var rels = JSON.stringify(this.newRelationships);
		return SUGAR.ui.sendForm(form, {newrels: rels}, null);
	};

}();

