FR = {
	UI: {}, changesSaved: true,
	init: function() {
		this.viewport = new Ext.Viewport({
			layout: 'fit',
			items: {
				layout: 'fit',
				html: '<div id="editor"></div>',
				tbar: {
					cls: 'fr-viewport-top-bar',
					items: [
						{
							text: "Save",
							cls: 'fr-btn-primary',
							style: 'margin-right:10px',
							handler: function(){this.save(false);}, scope: this
						},
						{
							text: "Save and close",
							cls: 'fr-btn-primary',
							style: 'margin-right:10px',
							hidden: !windowId,
							handler: function(){this.save(true);}, scope: this
						},
						{
							text: "Close",
							style: 'margin-right:10px',
							hidden: !windowId,
							handler: function(){this.closeWindow();}, scope: this
						},
						{
							xtype: 'tbtext', id: 'status', text: ''
						}
					]
				}
			},
			listeners: {
				'afterrender': function() {
					Ext.fly('editor').update(document.getElementById('textContents').innerText);
					$.extend($.summernote.keyMap, {
						pc: {'CTRL+S': 'Save'},
						mac: {'CMD+S': 'Save'}
					});
					$.extend($.summernote.plugins, {
						'save':function (context) {
							this.events = {
								'summernote.change':function (we, e) {
									FR.changesSaved = false;
									Ext.getCmp('status').setText('<span class="colorDanger">'+FR.T('Unsaved changes')+'</span>');
								},
								'summernote.keydown':function (we, e) {
									if(e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
										e.preventDefault();
										FR.save();
									}
								}
							};
							$(window).bind('beforeunload',function () {
								if (!FR.changesSaved) return FR.T('Discard the changes made?');
							});
						}
					});
					$('#editor').summernote({
						lang: language,
						toolbar: [
							['style', ['style', 'bold', 'italic', 'underline', 'strikethrough', 'clear']],
							['font', ['fontname', 'fontsize', 'color']],
							['para', ['ul', 'ol', 'paragraph']],
							['insert', ['link', 'picture', 'table', 'hr']],
							['code', ['undo', 'redo', 'codeview', 'print', 'help']]
						],
						height: this.items.first().body.getHeight()-43,
						focus: true, dialogsFade: true,
						disableResizeEditor: true,
						codeviewFilter: true
					});
					$('.note-statusbar').hide();
				}
			}
		});
	},
	closeWindow: function() {
		if (!FR.changesSaved) {
			new Ext.ux.prompt({text: 'Discard the changes made?',
				confirmHandler: function() {
					window.parent.FR.UI.popups[windowId].close();
				}});
			return false;
		}
		window.parent.FR.UI.popups[windowId].close();
	},
	save: function(close) {
		this.closeAfterSave = close;
		this.viewport.getEl().mask('Saving...');
		Ext.Ajax.request({
			url: URLRoot+'/customizables/plugins/html_editor/save.php',
			params: {
				path: path,
				filename: filename,
				textContents: $('#editor').summernote('code'),
				csrf: csrf
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
					if (windowId) {
						window.parent.FR.UI.feedback(rs.msg, rs.success ? 'success' : 'error');
					} else {
						if (rs.success) {
							rs.msg = '<span style="color:green">'+rs.msg+'</span>';
						}
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
Ext.onReady(function() {FR.init();})