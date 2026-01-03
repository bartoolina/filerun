FR = {
	showEl: function(id) {
		document.getElementById(id).style.display='flex';
	},
	hideEl: function(id) {
		document.getElementById(id).style.display='none';
	},
	initUploader: function () {
		this.status = document.getElementById('upStatus');
		FR.showEl('choose');
		if (!UploadChunkSize) {UploadChunkSize = 2086912;}
		this.flow = new Flow({
			target: URLRoot+'/?module=weblinks&section=public&page=upload', progressCallbacksInterval: 100, startOnSubmit: false, maxChunkRetries: 3, resumeLargerThan: 10485760, maxSimultaneous: 1, entireFolder: true, chunkSize: UploadChunkSize
		});
		this.flow.on('filesSubmitted', function() {
			FR.hideEl('choose');
			if (isFileRequest) {
				if (document.getElementById('senderName').value == '') {
					FR.showEl('giveName');
					document.getElementById('senderName').focus();
					return false;
				}
			}
			FR.startUpload();
		});
		this.flow.on('uploadStart', function() {
			FR.status.innerHTML = 'Upload starting...';
		});
		this.flow.on('progress', function(flow) {
			var percent = Math.floor(flow.getProgress()*100);
			var completed = FR.formatFileSize(flow.completedBytes);
			var total = FR.formatFileSize(flow.size);
			FR.status.innerHTML = 'Uploading...'+percent+'%'+'<br><div class="tip">'+completed+' / '+total+'</div>';
		});
		this.flow.on('fileSuccess', function(file, message) {
			try {var rs =  eval('(function(){return'+message+';})()');} catch (er) {
				FR.status.innerHTML = 'Unexpected server reply: ' + message;
			}
		});
		this.flow.on('fileError', function(file, message) {
			try {var rs = eval('(function(){return'+message+';})()');} catch (er){
				FR.status.innerHTML = 'Unexpected server reply: '+message;
			}
			if (rs && rs.msg) {FR.status.innerHTML = rs.msg;}
		});
		this.flow.on('complete', function() {
			FR.status.innerHTML = '';
			FR.hideEl('upStatus');
			if (isFileRequest) {
				FR.hideEl('giveName');
			}
			FR.showEl('success');
		});
		FlowUtils.DropZoneManager.add({
			domNode: document.body, findTarget: function(e) {return {el: document.body};}, overClass: 'dragged-over-from-desk',
			onDrop: this.flow.onDrop, scope: this
		});
	},
	selectFiles: function() {
		this.flow.removeAll();
		this.flow.browseFiles({singleFile: false});
	},
	startUpload: function() {
		if (isFileRequest) {
			var n = document.getElementById('senderName');
			var senderName = n.value;
			if (senderName == '') {
				n.focus();
				n.classList.add('invalid');
				return false;
			}
			FR.hideEl('giveName');
		}
		FR.showEl('upStatus');
		FR.flow.files.forEach(function(f) {
			var params = {id: WebLinkId};
			if (UploadToPath) {params.path = UploadToPath;}
			if (WebLinkPass) {params.pass = WebLinkPass;}
			if (isFileRequest) {params.senderName = senderName;}
			f.query = params;
		});
		this.flow.start();
	},
	reset: function() {
		if (isFileRequest) {
			document.getElementById('senderName').classList.remove('invalid');
		}
		FR.hideEl('success');
		FR.showEl('choose');
	},
	formatFileSize : function(size) {
	    var i = Math.floor( Math.log(size) / Math.log(1024) );
	    return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
	},
	T: function(str) {
		return str;
	}
};