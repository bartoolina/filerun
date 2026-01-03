FR = {
	UI: {}, changesSaved: true,
	editor: false,
	init: function() {
		this.viewport = new Ext.Viewport({
			layout: 'fit',
			items: {
				layout: 'fit',
				html: '<div id="editor" style="position: absolute;top: 0;right: 0;bottom: 0;left: 0;"></div>',
				tbar: {
					cls: 'fr-viewport-top-bar',
					items: [
						{
							text: "Save",
							cls: 'fr-btn-primary',
							style: 'margin-right:10px',
							hidden: !FR.settings.isEditable,
							handler: function(){this.save(false);}, scope: this
						},
						{
							text: "Save and close",
							cls: 'fr-btn-primary',
							style: 'margin-right:10px',
							hidden: (!FR.settings.isEditable || !FR.settings.isClosable),
							handler: function(){this.save(true);}, scope: this
						},
						{
							text: "Close",
							style: 'margin-right:10px',
							hidden: !FR.settings.isClosable,
							handler: function(){FR.closeWindow();}
						},
						{
							text: "Insert new row",
							style: 'margin-right:10px',
							hidden: !FR.settings.isEditable,
							handler: function(){FR.editor.insertRow();}
						},
						{
							text: "Insert new column",
							style: 'margin-right:10px',
							hidden: !FR.settings.isEditable,
							handler: function(){FR.editor.insertColumn();}
						},
						{
							xtype: 'tbtext', id: 'status', text: ''
						}
					]
				}
			},
			listeners: {
				'afterrender': function() {
					FR.editor = jspreadsheet(document.getElementById('editor'), {
						csv: FR.settings.fileURL,
						csvHeaders:true,
						lazyLoading:true,
						loadingSpin:true,
						fullscreen: true,
						allowExport: false,
						defaultColWidth: 150,
						csvDelimiter: FR.settings.delimiter,
						includeHeadersOnDownload: true,
						onchange: FR.onChange
					});
				}
			}
		});
		window.onbeforeunload = function() {
			if (!FR.changesSaved) return FR.T('Discard the changes made?');
		};

	},
	onChange: function() {
		FR.changesSaved = false;
		Ext.getCmp('status').setText('<span class="colorDanger">' + FR.T('Unsaved changes') + '</span>');
	},
	closeWindow: function() {
		if (!FR.changesSaved) {
			new Ext.ux.prompt({text: FR.T('Discard the changes made?'),
				confirmHandler: function() {
					window.parent.FR.UI.popups[FR.settings.windowId].close();
				}});
			return false;
		}
		window.parent.FR.UI.popups[FR.settings.windowId].close();
	},
	save: function(close) {
		this.closeAfterSave = close;
		this.viewport.getEl().mask('Saving...');
		Ext.Ajax.request({
			url: FR.settings.actionURL+'&method=saveChanges',
			params: {
				path: FR.settings.path,
				filename: FR.settings.filename,
				csvHeaders: JSON.stringify(FR.editor.getHeaders(true)),
				textContents: JSON.stringify(FR.editor.getJson())
			},
			success: function(req) {
				this.viewport.getEl().unmask();
				try {
					var rs = Ext.util.JSON.decode(req.responseText);
				} catch (er){return false;}

				if (rs.success) {
					FR.changesSaved = true;
					Ext.getCmp('status').setText('');
				} else {
					FR.changesSaved = false;
				}
				if (rs.msg) {
					if (FR.settings.windowId) {
						window.parent.FR.UI.feedback(rs.msg, rs.success ? 'success' : 'error');
					} else {
						Ext.getCmp('status').setText(rs.msg);
					}
				}
				if (rs.success && this.closeAfterSave) {
					this.closeWindow();
				}
			},
			scope: this
		});
	}
};