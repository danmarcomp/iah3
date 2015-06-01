StockQuotes = function() {
	return {
		Data: {},
		DisplayChart: {},
		init: function(id, display_chart, data) {
			this.Data[id] = data;
			this.DisplayChart[id] = display_chart;
			YAHOO.util.Event.onDOMReady(function() {StockQuotes.autoDisplay(id);});
		},
		autoDisplay: function(id) {
			var woeid = Get_Cookie('stockquotes_' + id + '_selected');
			if(! woeid)
				return;
			var row = $('stockquotes_row_'+woeid);
			if(row)
				this.markRow(row, 'mark', woeid, id);
		},
		
		displayStockDetails: function(dashlet_id, id) {
			if(! this.Data[dashlet_id] || ! this.Data[dashlet_id][id])
				return;
			var data = deep_clone(this.Data[dashlet_id][id]);
			Set_Cookie('stockquotes_'+dashlet_id+'_selected', id, 30,'/','','');
			
			var map = {
				l1 : 'stock_last_trade',
				m  : 'stock_days_range',
				d1 : 'stock_last_trade_time',
				w  : 'stock_last_52w_range',
				c6 : 'stock_change',
				v  : 'stock_volume',
				p  : 'stock_prev_close',
				a2 : 'stock_avg_volume',
				o  : 'stock_open',
				j1 : 'stock_market_cap',
				b  : 'stock_bid',
				r  : 'stock_pe',
				a  : 'stock_ask',
				e  : 'stock_eps',
				t8 : 'stock_1y_target',
				y  : 'stock_div_yield',
				n  : 'stock_name',
				ch : 'stock_chart'
			};

			if (data.d == 'N/A' || data.y == 'N/A') {
				data.y = 'N/A (N/A)';
			} else {
				data.y = data.d + ' (' + data.y + '%)';
			}

			data.n = data.n + ' (' + data.s + ')';

			
			data.v = formatNumber(parseFloat(data.v), user_number_separators.thousands, user_number_separators.decimal, 0, 0);
			data.a2 = formatNumber(parseFloat(data.a2), user_number_separators.thousands, user_number_separators.decimal, 0, 0);

			data.d1 += ' ' + data.t1;

			for (var i in map) {
				$(map[i]+'_'+dashlet_id).innerHTML = data[i];
			}
			var ext_link = $('stock_ext_link_'+dashlet_id);
			if(ext_link)
				ext_link.href = ext_link.href.replace(/=.*?$/, '='+data.s);
			
			var chart = $('stock_chart_'+dashlet_id);
			chart.innerHTML = '';
			if (this.DisplayChart[dashlet_id]) {
				var im = document.createElement('img');
				im.id = 'StockQuotesChart_'+dashlet_id;
				im.chartType = 'l';
				im.period = '1d';
				im.scale = 'off';
				im.symbol = data.s;
				im.style.cursor = 'pointer';
				chart.appendChild(im);
				StockQuotes.smallChart(im, dashlet_id);
				var div = $('StockQuotesLinks_'+dashlet_id);
				div.style.display = '';
			}
			
			$('stock_details_'+dashlet_id).style.display='';
			setTimeout(function() {
				YDom.removeClass('stock_details_'+dashlet_id, 'hidden');
			}, 10);
		},

		hideStockDetails : function(dashlet_id) {
			Set_Cookie('stockquotes_'+dashlet_id+'_selected', '', 30,'/','','');
			$('stock_details_'+dashlet_id).style.display='none';
			YDom.addClass('stock_details_'+dashlet_id, 'hidden');
		},

		bigChart : function(params, scope, dashlet_id) {
			if (!scope) {
				scope = this;
			} else if (typeof(scope) == 'string') {
				scope = $(scope+'_'+dashlet_id);
			}
			if (!params) {
				params = {};
			}
			for (var i in params) {
				scope[i] = params[i];
			}
			var src = 'http://ichart.finance.yahoo.com/z?s=' + scope.symbol;
			src += '&t=' + scope.period;
			src += '&q=' + scope.chartType;
			src += '&a=v'; // show volume below
			src += '&l=' + scope.scale;
			src += '&p=s'; // show splits; side-effect of smoothing output
			// src += '&c=SYMB,SYMB' to compare stocks
			scope.src = src;
			scope.onclick = function() {StockQuotes.smallChart(scope, dashlet_id)};
			StockQuotes.updateLinks(dashlet_id);
		},

		smallChart : function(scope, dashlet_id) {
			if (!scope) scope = this;
			var chart = $('stock_chart_'+dashlet_id);
			scope.src = 'http://ichart.finance.yahoo.com/t?s=' + scope.symbol;
			scope.onclick = function() { StockQuotes.bigChart(null, scope, dashlet_id); }
			chart.style.display = '';
			var div = $('StockQuotesLinks_'+dashlet_id);
			div.innerHTML = '';
			div.appendChild(document.createTextNode(StockQuotesLang.LBL_CLICK_FOR_LARGER));
		},

		updateLinks : function(dashlet_id) {
			var div = $('StockQuotesLinks_'+dashlet_id);
			if (!div) {
				div = document.createElement('div');
				div.id = 'StockQuotesLinks_'+dashlet_id;
				var chart = $('stock_chart_'+dashlet_id);
				chart.insertBefore(div, chart.firstChild);
			}
			div.style.color = '#000000';
			div.innerHTML = '';
			var im = $('StockQuotesChart_'+dashlet_id);
			var content = [
				{
					label : StockQuotesLang.LBL_RANGE,
					param : 't',
					imgParam : 'period',
					links : {
						'1d' :  StockQuotesLang.LBL_1D,
						'5d' :  StockQuotesLang.LBL_5D,
						'3m' : StockQuotesLang.LBL_3M,
						'6m' : StockQuotesLang.LBL_6M,
						'1y' : StockQuotesLang.LBL_1Y,
						'2y' : StockQuotesLang.LBL_2Y,
						'5y' : StockQuotesLang.LBL_5Y
					}
				},
				{
					label : StockQuotesLang.LBL_TYPE,
					param : 'q',
					imgParam : 'chartType',
					links : {
						'b' : StockQuotesLang.LBL_BAR,
						'l' : StockQuotesLang.LBL_LINE,
						'c' : StockQuotesLang.LBL_CANDLE
					}
				},
				{
					label : StockQuotesLang.LBL_SCALE,
					param : 'l',
					imgParam : 'scale',
					links : {
						'off' : StockQuotesLang.LBL_LINEAR,
						'on' : StockQuotesLang.LBL_LOG
					}
				}
			];

			for (var i = 0; i < content.length; i++) {
				var defs = content[i];
				var label = document.createElement('span');
				label.appendChild(document.createTextNode(defs.label));
				div.appendChild(label);
				for (var j in defs.links) {
					var a;
					if (j == im[defs.imgParam]) {
						a = document.createElement('span');
						a.style.fontWeight = 'bold';
					} else {
						a = document.createElement('a');
						a.href = 'javascript:void(0);';
						a.chartParam = defs.imgParam;
						a.chartParamValue = j;
						a.onclick = function() {
							var params = {};
							params[this.chartParam] = this.chartParamValue;
							StockQuotes.bigChart(params, 'StockQuotesChart', dashlet_id);
						};
					}
					a.appendChild(document.createTextNode(defs.links[j]));
					div.appendChild(a);
					var spacer = document.createElement('span');
					spacer.innerHTML = '&nbsp;';
					div.appendChild(spacer);
				}
				var spacer = document.createElement('span');
				spacer.innerHTML = '&nbsp;&nbsp;&nbsp;';
				div.appendChild(spacer);
			}
			div.style.display = '';
		},
		
		undoPrevMark: null,
		markRow: function(therow, action, id, dashlet_id) {
			if (! therow.marked) {
				if(this.undoPrevMark && this.undoPrevMark[0] != therow) {
					this.undoPrevMark[1] = 'clear';
					sListView.row_action.apply(window, this.undoPrevMark);
				}
				this.displayStockDetails(dashlet_id, id);
				this.undoPrevMark = arguments;
			} else {
				this.hideStockDetails(dashlet_id);
				this.undoPrevMark = null;
			}
			return sListView.row_action(therow, action, id, ! therow.marked);
		}
	};
}();
