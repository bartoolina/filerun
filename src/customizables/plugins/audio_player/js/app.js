FR = {
	initialized: false, pgrs: 0, duration: 0, fileItem: false, aurora4M4A: false,
	paused: true, thumbDrag: false, volume: 50,
	embedded: false,
	init: function() {
		if (this.initialized) {return false;}
		this.embedded = (Settings.context == 'embedded');
		if (this.embedded) {
			window.parent.FR.UI.AudioPlayer.app = FR;
		}
		Ext.QuickTips.init();
		this.volumeSlider = new Ext.Slider({
			style: 'margin-left:10px;margin-right:10px;',
			value: this.volume,
			minValue: 0,
			maxValue: 100,
			width: 100,
			listeners: {
				change: function (s, newValue) {
					FR.setVolume(newValue);
				}
			}
		});
		this.progress = new Ext.Slider({
			style: 'margin: 0 20px',
			value: 0,
			minValue: 0,
			maxValue: 100,
			decimalPrecision: false,
			width: 200,
			listeners: {
				dragstart: function(s) {
					s.ttip.show();
					FR.thumbDrag = true;
				},
				dragend: function(s) {
					FR.thumbDrag = false;
					s.ttip.hide();
				},
				drag: function(s) {
					s.updateSeekIndicator();
				},
				changecomplete: function(s, newValue) {
					this.song.seek(newValue/100*FR.duration);
				}, scope: this,
				afterrender: function(s) {
					s.ttip = new Ext.ToolTip({
						anchor: 'top',
						anchorOffset: -10,
						autoHide: false,
						showDelay: 0,
						hideDelay: 0,
						text: 'Test'
					});
					s.ttip.anchorTarget = Ext.get(s.thumbs[0].el);
				}
			},
			updateSeekIndicator: function(v) {
				var s = this;
				if (!v) {
					v = s.thumbs[0].getNewValue();
				}
				if (v > s.minValue && v <= s.maxValue) {
					s.ttip.update(s.getSeek(v));
					s.ttip.setPagePosition(s.ttip.getTargetXY());
				}
			},
			getSeek: function(value) {
				if (FR.duration) {
					var seconds = Math.floor(value/100*FR.duration);
					return new Date(seconds * 1000).toISOString().substr(11, 8);
				} else {
					return value+'%';
				}
			}
		});
		var tbarItems = [
			{
				tooltip: 'Select',
				iconCls: 'fa-check-square-o',
				handler: function () {
					window.parent.FR.UI.gridPanel.highlightByRecord(this.fileItem);
				}, scope: this,
				hidden: !this.embedded
			},
			{
				tooltip: 'Previous',
				style: 'margin-left:5px;font-size:1.2em',
				iconCls: 'fa-step-backward',
				id: 'fr-prev-btn',
				handler: this.previousFile, scope: this,
				disabled: !this.embedded
			},{
				tooltip: 'Play/Pause',
				style: 'margin-left:5px;font-size:1.5em',
				iconCls: 'fa-play', handler: this.playPause,
				id: 'fr-play-btn', scope: this
			},{
				tooltip: 'Next',
				style: 'margin-left:5px;font-size:1.2em',
				id: 'fr-next-btn', iconCls: 'fa-step-forward',
				handler: this.nextFile, scope: this, disabled: !this.embedded
			},
			{
				iconCls: 'fa-volume-up',
				tooltip: 'Adjust Volume',
				style: 'margin-left:5px',
				menuAlign: 'b-t',
				menu: [
					this.volumeSlider
				]
			},
			this.progress
		];
		this.toolbar = new Ext.Toolbar({items: tbarItems, style: 'padding-left:7px'});


		var layout = {
			layout: 'border',
			items: [
				{
					title: '&nbsp;',
					closable: this.embedded,
					closeAction: 'closePlayer',
					closePlayer: function() {
						FR.reset();
						with (window.parent) {
							FR.UI.AudioPlayer.close();
						}
					},
					layout: 'border',
					region: 'center',
					margins: '0 0 10px 0',
					items: [
						{
							region: 'center',
							html: '',
							bbar: this.toolbar
						}
					]
				}
			],
			listeners: {
				'afterrender': function() {
					FR.playerPanel = this.items.items[0];
				},
				'resize': function() {
					FR.adjustSize();
				}
			}
		};
		if (!this.embedded) {
			layout.width = 450;
			layout.height = 150;
			layout.closable = false;
			this.viewport = new Ext.Window(layout);
			this.viewport.show().anchorTo(Ext.get('theBODY'), 'c-c');
		} else {
			this.viewport = new Ext.Viewport(layout);
		}
		FR.adjustSize();
		this.updater = new Ext.util.DelayedTask(function(){
			FR.setProgress(FR.song.getProgress());
			FR.updateProgress();
			FR.updater.delay(500);
		});
		this.reset();
		if (!this.embedded) {
			this.loadFile(fileItem);
		} else {
			window.parent.FR.UI.AudioPlayer.onLoad(FR);
		}
		this.initialized = true;
	},
	adjustSize: function() {
		if (!this.viewport) {return false;}
		var w = this.viewport.getWidth()-225;
		this.progress.setWidth(Math.max(w, 10));
	},
	setVolume: function(v) {
		if (this.song) {
			this.song.setVolume(v);
		}
		this.volume = v;
	},
	stopPlayback: function() {
		this.song.stop();
		this.paused = true;
		Ext.getCmp('fr-play-btn').setIconClass('fa-play');
		this.progress.setValue(0);
		this.reset();
	},
	playPause: function() {
		if (this.paused) {
			this.play();
		} else {
			this.pause();
		}
	},
	pause: function() {
		if (!this.song) {return false;}
		this.updater.cancel();
		Ext.getCmp('fr-play-btn').setIconClass('fa-play');
		this.song.pause();
		this.paused = true;
	},
	play: function() {
		if (!this.song) {
			this.nextFile();
		} else {
			this.song.play();
		}
	},
	setProgress: function(p) {
		FR.pgrs = p;
	},
	setDuration: function(d) {
		FR.duration = d;
	},
	reset: function() {
		this.updater.cancel();
		if (this.song) {
			this.song.destroy();
		}
		this.pgrs = 0;
		this.duration = 0;
		FR.playerPanel.setTitle('');
		this.progress.setValue(0);
	},
	updateProgress: function() {
		var songTitle = '<span style="color:var(--theme-textLighter);font-size:12px">'+this.fileItem.filename+'</span>';
		var dur;
		if (FR.duration) {
			if (FR.progress.disabled) {
				FR.progress.enable();
			}
			if (!FR.thumbDrag) {
				FR.progress.setValue((FR.pgrs / FR.duration * 100), true, false);
			}
			dur = FR.formatTime(FR.pgrs) + ' / ' + FR.formatTime(FR.duration);
		} else {
			dur = FR.formatTime(FR.pgrs) + ' / &infin;';
			if (!FR.progress.disabled) {
				FR.progress.disable();
			}
		}
		FR.playerPanel.setTitle(dur+' '+songTitle);
	},
	formatTime: function(s){
		var min=parseInt(s/60);
		var sec=parseInt(s%60);
		return String.leftPad(min,2,'0')+':'+String.leftPad(sec,2,'0');
	},
	getDurationEstimate: function(song) {
		if (song.instanceOptions.isMovieStar) {
			return (song.duration);
		} else {
			return song.durationEstimate || (song.duration || 0);
		}
	},
	loadFile: function(fileItem) {
		if (fileItem.isFolder || fileItem.filetype != 'mp3') {return false;}
		this.fileItem = fileItem;
		if (this.song) {this.reset();}

		var url = fileItem.url || URLRoot+'/?module=custom_actions&action=audio_player&method=stream&path='+encodeURIComponent(fileItem.path);
		FR.playerPanel.setTitle(FR.T('Loading %1').replace('%1', fileItem.filename));
		this.song = new Song({
			url: url,
			ext: fileItem.ext,
			volume: this.volume,
			onLoad: function(duration) {
				FR.setDuration(duration);
				FR.updateProgress();
			},
			onPlay: function() {
				Ext.getCmp('fr-play-btn').setIconClass('fa-pause');
				FR.paused = false;
				FR.updater.delay(0);
			},
			onLoadError: function(id, error) {
				var msg = FR.T('Failed to load audio file: %1').replace('%1', error);
				if (FR.embedded) {
					with (window.parent) {
						FR.UI.feedback(msg);
					}
				} else {
					alert(msg);
				}
			},
			onEnd: function() {
				FR.paused = true;
				Ext.getCmp('fr-play-btn').setIconClass('fa-play');
				if (!Settings.disable_autoplay) {
					FR.nextFile();
				}
			},
			onBuffering: function(percent) {
				if (percent < 100) {
					FR.playerPanel.setTitle('Loading: ' + Math.round(percent) + '%');
				} else {
					FR.playerPanel.setTitle('');
				}
			},
			onMetadata: function() {}
		});
		this.play();
	},
	nextFile: function() {
		var gridStore = window.parent.FR.UI.gridPanel.store;
		var findRecord = this.fileItem;
		var rowIdx = gridStore.findBy(function(record) {
			if (findRecord == record.data) {return true;}
		});
		var nextRowIdx, r;
		if (rowIdx == -1) {
			nextRowIdx = 0;
		} else {
			nextRowIdx = rowIdx+1;
			if (!gridStore.getAt(nextRowIdx)) {
				nextRowIdx = 0;
			}
		}
		r = gridStore.getAt(nextRowIdx);
		if (!r) {return false;}
		this.loadFile(r.data);
	},
	previousFile: function() {
		var gridStore = window.parent.FR.UI.gridPanel.store;
		var findRecord = this.fileItem;
		var rowIdx = gridStore.findBy(function(record) {
			if (findRecord == record.data) {return true;}
		});
		var prevRowIdx, r;
		if (rowIdx == -1) {
			prevRowIdx = 0;
		} else {
			prevRowIdx = rowIdx-1;
			if (!gridStore.getAt(prevRowIdx)) {
				prevRowIdx = gridStore.data.keys.length-1;
			}
		}
		r = gridStore.getAt(prevRowIdx);
		if (!r) {return false;}
		this.loadFile(r.data);
	}
};

var Song = function(opts) {
	var aurora = (opts.ext == 'm4a' && FR.aurora4M4A);
	this.opts = opts;
	this.load(aurora);
};
Song.prototype.load = function(aurora) {
	this.progress = 0;
	this.duration = 0;
	var ths = this;
	if (aurora) {
		this.aurora = true;
		this.player = AV.Player.fromURL(this.opts.url);
		this.player.volume = this.opts.volume;
		this.player.on('ready', function(){
			ths.duration = Math.ceil(this.duration/1000);
			ths.opts.onLoad(ths.duration);
			ths.opts.onPlay();
		});
		this.player.on('progress', function(p) {ths.progress = Math.ceil(p/1000);});
		this.player.on('error', this.opts.onLoadError.bind(this));
		this.player.on('end', this.opts.onEnd.bind(this));
		this.player.on('metadata', this.opts.onMetadata.bind(this));
		this.player.on('buffer', this.opts.onBuffering.bind(this));

	} else {
		this.aurora = false;
		this.player = new Howl({
			src: [this.opts.url],
			format: [this.opts.ext],
			volume: (this.opts.volume / 100),
			preload: true,
			html5: true,
			onload: function() {
				ths.opts.onLoad(this.duration());
			},
			onplay: function(){ths.opts.onPlay();},
			onloaderror: function() {
				//retry using aurora
				FR.aurora4M4A = true;
				this.load(true).play();
			}.bind(this),
			onend: this.opts.onEnd.bind(this)
		});
	}
	return this;
};
Song.prototype.play = function() {
	this.player.play();
};
Song.prototype.pause = function() {
	this.player.pause();
};
Song.prototype.getDuration = function() {
	return this.duration;
};
Song.prototype.getProgress = function() {
	if (this.aurora) {
		return this.progress;
	}
	return this.player.seek();
};
Song.prototype.destroy = function() {
	this.player.stop();
	if (!this.aurora) {
		this.player.unload();
	}
};
Song.prototype.setVolume = function(v) {
	if (this.aurora) {
		this.player.volume = v;
	} else {
		this.player.volume(v/100);
	}
};
Song.prototype.seek = function(p) {
	this.player.seek(p);
};

Ext.onReady(function(){
	FR.init();
	Ext.fly('loadMsg').fadeOut();
});