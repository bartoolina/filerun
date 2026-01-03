FR = {
	UI: {}, changesSaved: true,
	setWrap: function() {
		var canvasSize = Ext.getBody().getViewSize();
		if (canvasSize.width > 700) {
			FR.editor.setOption("wrap", 80);
		} else {
			FR.editor.setOption("wrap", true);
		}
	},
	init: function() {
		FR.textarea = Ext.get('textContents');
		this.charsetSelector = new Ext.form.ComboBox({
			width: 120,
			emptyText: 'Charset for saving',
			mode: 'local', triggerAction: 'all', editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: ['text'],
				data: FR.settings.charsets
			}),
			valueField: 'text',
			displayField: 'text', value: (FR.settings.charset || 'UTF-8'),
			listeners: {
				'select': function() {
					new Ext.ux.prompt({
						text: FR.T('Would you like to reload the file using the selected charset? Any unsaved changes will be lost.'),
						confirmHandler: function() {FR.changeCharset(this.getValue());},
						scope: this
					});
				}
			}
		});

		this.viewport = new Ext.Viewport({
			layout: 'fit',
			items: {
				layout: 'fit',
				html: '<div id="editor" style="position: absolute;top: 0;right: 0;bottom: 0;left: 0;"></div>',
				tbar: {
					cls: 'fr-viewport-top-bar',
					items: [
						{
							text: "Save", cls: 'fr-btn-primary',
							hidden: !FR.settings.isEditable,
							style: 'margin-right:10px',
							handler: function(){this.save(false);}, scope: this
						},
						{
							text: "Save and close", cls: 'fr-btn-primary',
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
							xtype: 'tbtext', id: 'status', text: ''
						},
						'->',
						this.charsetSelector,
						{
							xtype: 'button',
							enableToggle: true,
							text: 'Word wrap',
							pressed: true,
							style: 'margin-left:10px',
							toggleHandler: function(b, pressed) {
								FR.editor.getSession().setUseWrapMode(pressed);
							}
						}
					]
				}
			},
			listeners: {
				'resize': function() {
					if (FR.editor) {
						FR.editor.resize.defer(300, FR.editor);
						FR.setWrap();
					}
				},
				'afterrender': function() {
					FR.editor = ace.edit("editor");
					var modelist = ace.require('ace/ext/modelist');
					var mode = modelist.getModeForPath(FR.settings.filename).mode;
					if (!mode) {
						mode = 'ace/mode/html'
					}
					var isPlainText = ['ace/mode/text','ace/mode/markdown'].indexOf(mode) !== -1;
					//https://github.com/ajaxorg/ace/wiki/Configuring-Ace

					var dark = ((FR.settings.theme == 'dark') || window.matchMedia('(prefers-color-scheme: dark)').matches);

					FR.editor.setOptions({
						mode: mode,
						showLineNumbers: !isPlainText,
						scrollPastEnd: 0.3,
						fontSize: Ext.isMobile ? 16 : 14,
						showPrintMargin: !isPlainText,
						theme: 'ace/theme/'+(dark ? 'twilight' : 'chrome')
					});
					var s = FR.editor.getSession();
					s.setUseWrapMode(true);
					FR.setWrap();
					s.setValue(FR.textarea.dom.value);
					FR.editor.renderer.setScrollMargin(10, 10);
					FR.editor.resize();
					FR.editor.focus();
					FR.textarea.remove();
					FR.editor.on('change', FR.onChange);
					FR.editor.commands.addCommand({
						name: 'Save',
						bindKey: {win: 'Ctrl-S', mac: 'Command-S'},
						exec: function (editor) {
							FR.save();
						}
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
	changeCharset: function(charset) {
		var frm = document.createElement('FORM');
		frm.action = FR.settings.actionURL;
		frm.method = 'POST';
		var postArgs = [
			{name: 'path', value: FR.settings.path},
			{name: 'filename', value: FR.settings.filename},
			{name: 'charset', value: charset}
		];
		Ext.each(postArgs, function(param) {
			var inpt = document.createElement('INPUT');
			inpt.type = 'hidden';
			inpt.name = param.name;
			inpt.value = param.value;
			frm.appendChild(inpt);
		});
		Ext.get('theBODY').appendChild(frm);
		frm.submit();
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
		this.viewport.getEl().mask(FR.T('Saving...'));

		Ext.Ajax.request({
			url: FR.settings.actionURL+'&method=saveChanges',
			params: {
				csrf: FR.settings.csrf,
				path: FR.settings.path,
				filename: FR.settings.filename,
				charset: this.charsetSelector.getValue(),
				textContents: FR.editor.getSession().getValue()
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

Ext.onReady(function() {FR.init();});
