FeedsDashlet = function() {
	var feedDlg;
	return {
		Data: {},
		init: function(id, data) {
			this.Data[id] = data;
		},
		
		initDialog: function(dashlet_id) {
			if(feedDlg)
				feedDlg.destroy();
			var feed = this.Data[dashlet_id];
			if(! feed)
				return;
			
			feedDlg = new SUGAR.ui.Dialog("feeddlg", { width: "640px", title: feed.favicon + ' ' + feed.title });
			feedDlg.keyDown = function(evt) {
				if(evt.keyCode == 37) { FeedsDashlet.previousFeedItem(dashlet_id); YEvent.stopEvent(evt); }
				else if(evt.keyCode == 39) { FeedsDashlet.nextFeedItem(dashlet_id); YEvent.stopEvent(evt); }
			};
			if(feed.copyright || feed.image) {
				var foot = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tr><td align="left">' + feed.copyright + '</td>';
				if(feed.image) foot += '<td width="1">' + feed.image.link_html + '</td>';
				feedDlg.setFooter(foot + '</tr></table>');
			}
		},
		
		showDialog: function() {
			if(! feedDlg.visible) {
				feedDlg.render();
				feedDlg.show();
			}
		},
		
		hideDialog: function() {
			if(feedDlg) {
				feedDlg.hide();
				feedDlg.destroy();
				feedDlg = null;
			}
		},
		
		renderItem: function(dashlet_id) {
			var feed = this.Data[dashlet_id];
			var item_idx = feed.active_item;
			var item = feed.items[item_idx];
			var max = feed.items.length;

			var body = '<table class="tabForm" border="0" cellpadding="0" cellspacing="0" width="100%">';
			body += '<tr><th class="dataLabel" colspan="2">';
			body += '<h4 class="dataLabel" style="margin: 0">' + item.title + '</h4>';
			var auth = '';
			if(item.author && item.author.name) {
				auth = item.author.name;
				if(item.author.link)
					auth = '<a href="' + encodeURI(item.author.link) + '" target="_blank">' + auth + '</a>';
				auth = ' ' + FeedsLang.LBL_BY + ' ' + auth;
			}
			body += '<div class="">' + item.date + auth + '</div>';
			body += '</th></tr><tr><td class="dataLabel" colspan="2">';
			body += '<div style="height: 350px; width: 600px; font-size: 12px; overflow: auto">' + item.content + '</div>';
			body += '</td></tr><tr>';
			body += '<td class="dataLabel" style="text-align: left">';
			if(feed.link)
				body += '<a href="' + feed.link + '" class="listViewTdToolsS1" target="_blank">' + feed.site_link_text + '</a>';
			body += '<td class="dataLabel listViewButtons" style="text-align: right">';
			if(item.link)
				body += '<a href="' + item.link + '" class="listViewTdToolsS1" target="_blank">' + feed.story_link_text + '</a>&nbsp;&nbsp;';
			body += item_idx ? feed.buttons.prev_on : feed.buttons.prev_off;
			body += ' ';
			body += (item_idx < max - 1) ? feed.buttons.next_on : feed.buttons.next_off;
			body += ' ' + feed.buttons.close;
			body += '</td></tr></table>';
			return body;
		},
		
		nextFeedItem: function(dashlet_id) {
			var feed = this.Data[dashlet_id];
			if(! feed || ! feedDlg || ! feed.items) return;
			if(feed.active_item < feed.items.length - 1) {
				feed.active_item ++;
				feedDlg.setContent(this.renderItem(dashlet_id));
			}
		},

		previousFeedItem: function(dashlet_id) {
			var feed = this.Data[dashlet_id];
			if(! feed || ! feedDlg) return;
			if(feed.active_item > 0) {
				feed.active_item --;
				feedDlg.setContent(this.renderItem(dashlet_id));
			}
		},

		displayFeedItem: function(dashlet_id, item_id) {
			if(! this.Data[dashlet_id] || ! this.Data[dashlet_id].items[item_id])
				return;
			var feed = this.Data[dashlet_id];
			var item = feed.items[item_id];
			feed.active_item = parseInt(item_id);
			this.initDialog(dashlet_id);
			feedDlg.setContent(this.renderItem(dashlet_id));
			this.showDialog();
		},
		
		searchFeeds: function(dashlet_id, value) {
			SUGAR.sugarHome.callMethod(dashlet_id, 'searchFeeds', 'value='+encodeURIComponent(value), false, function(v) { FeedsDashlet.showResults(dashlet_id, v); });
		},
		
		showResults: function(dashlet_id, v) {
			if(! v)
				return;
			var results = JSON.parse(v);
			var html = '';
			if(results) {
				for(var i = 0; i < results.length; i++) {
					var title = results[i].title;
					var url = results[i].url;
					html += '<a href="#" onclick="FeedsDashlet.selectFeed(\''+dashlet_id+'\', \''+url+'\'); return false;">'+title+'</a><br/>';
				}
			}
			var div = document.getElementById('feed_results_'+dashlet_id);
			div.innerHTML = html;
			div.style.display = '';
		},
		hideResults: function(dashlet_id) {
			var div = document.getElementById('feed_results_'+dashlet_id);
			div.style.display = 'none';
		},
		
		selectFeed: function(dashlet_id, feed_url) {
			this.hideResults(dashlet_id);
			var search = document.getElementById('search_'+dashlet_id);
			if(search) search.value = '';
			var url = document.getElementById('feed_url_'+dashlet_id);
			if(url) url.value = feed_url;
			var title = document.getElementById('feed_title_'+dashlet_id);
			if(title) title.value = '';
		},

		undoPrevMark: null,
		markRow: function(therow, action, id, dashlet_id) {
			if (! therow.marked) {
				if(this.undoPrevMark && this.undoPrevMark[0] != therow) {
					this.undoPrevMark[1] = 'clear';
					sListView.row_action.apply(window, this.undoPrevMark);
				}
				this.displayFeedItem(dashlet_id, id);
				this.undoPrevMark = arguments;
			} else {
				this.hideFeedItem(dashlet_id);
				this.undoPrevMark = null;
			}
			return sListView.row_action(therow, action, id, ! therow.marked);
		}
	};
}();
