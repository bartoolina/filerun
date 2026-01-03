FR = {
	UI: {},
	init: function() {
		this.viewport = new Ext.Viewport({
			layout: 'card', activeItem: 0,
			items: [
				{
					contentEl: 'selectFormat',
					autoScroll: true
				},
				{
					autoScroll: true,
					html: '<div id="status"></div>',
				}
			],
			listeners: {
				'afterrender': function() {
					Ext.each(Ext.query('div.format'), function(el) {
						Ext.get(el).on('click', function() {
							FR.requestConvertion(this.dom.dataset.format);
						});
					});
				}, scope: this
			}
		});
	},
	requestConvertion: function(format) {
		this.viewport.getLayout().setActiveItem(1);
		this.log(FR.T('Transfering file for conversion...'));
		Ext.Ajax.request({
			url: URLRoot+'/?module=custom_actions&action=cloudconvert&method=requestConversion',
			params: {
				path: path,
				format: format
			},
			callback: function(opts, succ, req) {
				try {
					var rs = Ext.util.JSON.decode(req.responseText);
				} catch (er){return false;}
				if (rs.msg) {
					window.parent.FR.UI.feedback(rs.msg, 'success');
					FR.taskId = rs.taskId;
					this.log(rs.msg);
					window.setTimeout(function(){FR.getStatus();}, 2000);
				}
			},
			scope: this
		});
	},
	getStatus: function() {
		var progress = 0;
		Ext.Ajax.request({
			url: URLRoot+'/?module=custom_actions&action=cloudconvert&method=getStatus',
			params: {
				path: path,
				csrf: csrf,
				taskId: FR.taskId
			},
			callback: function(opts, succ, req) {
				try {
					var rs = Ext.util.JSON.decode(req.responseText);
				} catch (er){return false;}
				if (rs.msg) {
					this.log(rs.msg);
					if (rs.step == 'downloaded') {
						window.parent.FR.UI.feedback(rs.msg, 'success');

						window.parent.FR.utils.reloadGrid(rs.newFileName);
						window.parent.FR.UI.popups[windowId].close();
					} else {
						if (rs.step != 'error') {
							window.setTimeout(function(){FR.getStatus();}, 3000);
						}
					}
				}
			},
			scope: this
		});
	},
	log: function(txt) {
		Ext.DomHelper.append('status', {tag: 'div', html: txt});
	}
}
Ext.onReady(FR.init, FR);