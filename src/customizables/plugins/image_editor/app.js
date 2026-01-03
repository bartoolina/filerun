var FR = {
	UI: {}, changesSaved: true,
	init: function() {
		Ext.QuickTips.init();
		Ext.getBody().mask('Loading image..');
		this.UI.tbar = new Ext.Toolbar({
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
					handler: function(){this.save(true);}, scope: this
				},
				{
					text: "Close",
					style: 'margin-right:10px',
					hidden: !FR.vars.windowId,
					handler: function(){FR.closeWindow();}
				},
				{
					xtype: 'tbtext', id: 'status', text: ''
				},
				'->',
				{
					iconCls: 'fa-lg fa-check icon-green',
					hidden: true,
					style: 'margin-left:10px',
					id: 'apply',
					text: 'Apply',
					handler: function () {
						this.applyChanges();
					}, scope: this
				},
				{
					iconCls: 'fa-lg fa-times',
					hidden: true,
					style: 'margin-left:10px',
					id: 'cancel',
					text: 'Cancel',
					handler: function () {
						this.cancelChanges();
					}, scope: this
				},
				{
					iconCls: 'fa-lg fa-crop-alt',
					enableToggle: true,
					id: 'cropToggle',
					tooltip: 'Crop',
					style: 'margin-left:10px',
					toggleHandler: function (btn, pressed) {
						if (pressed) {
							this.editor.crop();
						} else {
							this.editor.clear();
						}
						Ext.getCmp('apply').setVisible(pressed);
						Ext.getCmp('cancel').setVisible(pressed);
						this.UI.tbar.doLayout(true);
					}, scope: this
				},
				{
					xtype: 'tbtext', id: 'resolution', text: ''
				},
				{
					iconCls: 'fa-lg fa-expand-alt',
					tooltip: 'Scale down',
					menuAlign: 't-b',
					menu: [
						new Ext.slider.SingleSlider({
							id: 'scaleSlider',
							vertical: true,
							height: 200,
							decimalPrecision: 2,
							value: 1,
							minValue: 0.1,
							maxValue: 1,
							clickToChange: false,
							listeners: {
								change: function(s, newValue) {
									this.editor.scale(newValue, newValue);
									Ext.get('resolution').update(
										Math.round(this.currentImageData.naturalWidth*newValue)+
										' <i class="fa fa-times"></i> '+
										Math.round(this.currentImageData.naturalHeight*newValue)
									)
								},
								changecomplete: function() {
									Ext.getCmp('apply').setVisible(true);
									Ext.getCmp('cancel').setVisible(true);
								},
								scope: this
							}
						})
					],
					listeners: {
						menushow: function() {
							this.currentImageData = this.editor.getImageData();
							this.editor.zoomTo(1);
						}, scope: this
					}
				},
				'-',
				{
					iconCls: 'fa-lg fa-redo',
					tooltip: 'Rotate',
					menu: [
						{
							iconCls: 'fa-undo',
							text: '90&deg;',
							handler: function () {
								this.editor.rotate(-90);
								return false;
							}, scope: this
						},
						{
							iconCls: 'fa-redo',
							text: '90&deg;',
							handler: function () {
								this.editor.rotate(90);
								return false;
							}, scope: this
						},
						{
							text: '1&deg;',
							handler: function() {return false;},
							menu: {
								items: [new Ext.slider.SingleSlider({
									vertical: true,
									height: 200,
									value: 0,
									minValue: -90,
									maxValue: 90,
									clickToChange: false,
									listeners: {
										change: function (s, newValue) {
											this.editor.rotateTo(newValue);
										}, scope: this
									}
								})],
								listeners: {
									show: function(menu) {
										var slider = menu.items.items[0];
										var editorData = this.editor.getData();
										var currentRoateAngle = editorData.rotate;
										slider.setMinValue(currentRoateAngle-90);
										slider.setMaxValue(currentRoateAngle+90);
										slider.setValue(currentRoateAngle);
									}, scope: this
								}
							}
						},
						'-',
						{
							iconCls: 'fa-times',
							text: 'Reset',
							handler: function() {this.editor.rotateTo(0);}, scope: this
						}
					]
				},
				{
					iconCls: 'fa-lg fa-adjust',
					tooltip: 'Adjust',
					menu: [
						{
							text: 'Brightness',
							iconCls: 'fa-lg fa-sun',
							handler: function() {return this.imageControl('Brightness', 'brightness');}, scope: this
						},
						{
							text: 'Contrast',
							iconCls: 'fa-lg fa-adjust',
							handler: function() {return this.imageControl('Contrast', 'contrast');}, scope: this
						},
						{
							text: 'Saturation',
							iconCls: 'fa-lg fa-tint',
							handler: function() {return this.imageControl('Saturation', 'saturation');}, scope: this
						},
						{
							text: 'Sharpen',
							iconCls: 'fa-lg fa-eye',
							handler: function() {return this.imageControl('Sharpen', 'sharpen');}, scope: this
						}
					]
				},
				{
					iconCls: 'fa-lg fa-arrows-h',
					tooltip: 'Flip horizontal',
					handler: function () {
						this.editor.scaleX(-this.editor.getData().scaleX);
					}, scope: this
				},
				{
					iconCls: 'fa-lg fa-arrows-v',
					tooltip: 'Flip vertical',
					handler: function () {
						this.editor.scaleY(-this.editor.getData().scaleY);
					}, scope: this
				},
				'-',
				{
					iconCls: 'fa-lg fa-compress',
					tooltip: 'Fit to screen',
					handler: function () {
						this.editor.reset();
						var canvasData = this.editor.getCanvasData();
						Ext.get('zoomStatus').update(Math.round(canvasData.width/canvasData.naturalWidth*100)+'%');
					}, scope: this
				},
				{
					xtype: 'tbtext', id: 'zoomStatus', text: '', style: 'width:50px'
				},
				{
					iconCls: 'fa-lg fa-expand',
					tooltip: 'Full size',
					handler: function () {
						this.editor.zoomTo(1);
					}, scope: this
				},
				'-',
				{
					iconCls: 'fa-lg fa-refresh',
					tooltip: 'Reload image',
					handler: function () {
						this.editor.replace(FR.vars.imageURL);
					}, scope: this
				}
			]
		});
		new Ext.Viewport({
			layout: 'fit',
			items: {
				layout: 'fit',
				html: '<div id="editor"></div>',
				tbar: this.UI.tbar
			},
			listeners: {
				'resize': function() {
					if (this.editor) {
						this.editor.resize.defer(200, this.editor);
						this.editor.reset.defer(300, this.editor);
					}
				},
				'afterrender': function() {
					this.status = Ext.getCmp('status');
					var image = Ext.DomHelper.append('editor', {tag: 'img', src: FR.vars.imageURL, style: 'image-orientation: none;visibility:hidden'});

					this.editor = new Cropper(image, {
						viewMode: 1,
						autoCrop: false,
						dragMode: 'move',
						highlight: false,
						toggleDragModeOnDblclick: false,
						ready: function() {
							Ext.getBody().unmask();
							var imageData = FR.editor.getImageData();
							Ext.get('resolution').update(
								imageData.naturalWidth+
								' <i class="fa fa-times"></i> '+
								imageData.naturalHeight
							);
							FR.editor.zoom(-0.1);
							FR.UI.tbar.doLayout(true);
						},
						crop: function(e) {
							if (!e.detail.width) {return true;}
							Ext.get('resolution').update(
								Math.round(e.detail.width)+
								' <i class="fa fa-times"></i> '+
								Math.round(e.detail.height)
							);
						},
						zoom: function(e) {
							Ext.get('zoomStatus').update(Math.round(e.detail.ratio*100)+'%');
						}
					});
				}, scope: this
			}
		});
	},
	applyChanges: function() {
		this.editor.replace(this.editor.getCroppedCanvas().toDataURL());
		Ext.getCmp('scaleSlider').setValue(1);
		Ext.getCmp('cropToggle').toggle(0, true);
		Ext.getCmp('apply').hide();
		Ext.getCmp('cancel').hide();
		this.UI.tbar.doLayout(true);
	},
	cancelChanges: function() {
		Ext.getCmp('cropToggle').toggle(0);
		Ext.getCmp('scaleSlider').setValue(1);
		Ext.getCmp('apply').hide();
		Ext.getCmp('cancel').hide();
		this.UI.tbar.doLayout(true);
	},
	closeWindow: function() {
		var win = FR.vars.windowId ? window.parent.FR.UI.popups[FR.vars.windowId] : window;
		if (!FR.changesSaved) {
			new Ext.ux.prompt({text: FR.T('Discard the changes made?'),
				confirmHandler: function() {
					win.close();
				}});
			return false;
		}
		win.close();
	},
	save: function(close) {
		this.closeAfterSave = close;
		var canvas = this.editor.getCroppedCanvas();
		canvas.toBlob(function(blob) {
			blob.fileName = FR.vars.saveFileName;
			var upload = new Flow({
				target: FR.vars.URLRoot+'/?module=fileman&section=do&page=up',
				chunkSize: FR.vars.UploadChunkSize, maxChunkRetries: 3, resumeLargerThan: 104857600,
				validateChunkResponse: function(status, message) {
					if (status != '200') {return 'retry';}
					try {var rs = Ext.decode(message);} catch (er){return 'retry';}
					if (rs) {if (rs.success) {return 'success';} else {return 'error';}}
				}, validateChunkResponseScope: this, startOnSubmit: true
			});
			upload.on('fileSuccess', function(f, sr) {
				try {var rs = Ext.decode(sr);} catch (er){}
				FR.changesSaved = true;
				if (rs && rs.msg) {
					if (FR.vars.windowId) {
						FR.status.update('');
						window.parent.FR.UI.feedback(rs.msg, 'success');
					} else {
						FR.status.update(rs.msg);
					}
					FR.UI.tbar.doLayout(true);
				}
				if (FR.closeAfterSave) {
					FR.closeWindow();
				}
			});
			upload.on('fileError', function(f, sr) {
				FR.status.update('');
				try {var rs = Ext.decode(sr);} catch (er){}
				if (rs && rs.msg) {
					new Ext.ux.prompt({text: rs.msg});
				}
			});
			upload.on('progress', function(flow) {
				var progress = flow.getProgress();
				var percent = Math.floor(progress*100);
				FR.status.update(FR.T('Uploading image..')+' '+percent+'%');
			});
			upload.on('uploadStart', function() {
				FR.status.update(FR.T('Uploading image..'));
			});
			upload.addFile(blob, false, {path: FR.vars.folderPath});

		}, FR.vars.saveMimeType);
	},
	imageControl: function(title, method) {
		FR.bWin = new Ext.Window({
			title: title,
			width: 450, height: 350, modal: true,
			layout: 'fit', closable: false,
			items: [{
				bodyStyle: 'position: relative;'
			}],
			camanMethod: method,
			tbar: {
				buttonAlign: 'center',
				style: 'padding-top: 15px;padding-bottom:15px;',
				items: [
				new Ext.slider.SingleSlider({
					width: 350,
					value: 0,
					minValue: -100,
					maxValue: 100,
					clickToChange: false,
					listeners: {
						changecomplete: function (s, newValue) {
							FR.bWin.cam.revert(false);
							FR.bWin.cam[FR.bWin.camanMethod](newValue);
							FR.bWin.cam.render();
						}, scope: this
					}
				})
			]},
			bbar: {
				buttonAlign: 'center',
				style: 'padding-top: 15px',
				items: [{
					iconCls: 'fa-lg fa-check',
					cls: 'fr-btn-primary',
					text: 'Apply',
					handler: function() {
						FR.editor.replace(FR.bWin.canvas.dom.toDataURL('image/jpeg'));
						FR.bWin.close();
					}
				},
				{
					iconCls: 'fa-lg fa-times',
					text: 'Cancel', style: 'margin-left:10px',
					handler: function() {FR.bWin.close();}
				}]
			},
			listeners: {
				'show': function() {
					this.canvas = this.items.first().body.insertFirst(FR.editor.getCroppedCanvas());
					this.canvas.dom.id = 'mybcanvas';
					this.canvas.dom.setAttribute('data-caman-hidpi-disabled', '1');
					this.canvas.setStyle({
						display: 'block',
						position: 'absolute',
						top: '50%',
						left: '50%',
						transform: 'translate(-50%, -50%)'
					});
					var image = FR.editor.image;

					var boxWidth = this.items.first().body.getWidth();
					var boxHeight= this.items.first().body.getHeight();

					if (image.width > image.height) {
						if (image.width > boxWidth) {
							this.canvas.setWidth(boxWidth);
						} else {
							this.canvas.setHeight(boxHeight);
						}
					} else {
						if (image.height > boxHeight) {
							this.canvas.setHeight(boxHeight);
						} else {
							this.canvas.setWidth(boxWidth);
						}
					}
					FR.bWin.cam = Caman(this.canvas.dom);
				}
			}
		});
		FR.bWin.show();
	}
};
