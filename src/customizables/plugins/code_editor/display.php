<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<?php \FileRun\UI\JS::insertScripts(['ext']);?>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="<?php echo $config['url']['root'];?>/?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL("Custom Actions: Text Editor")?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script src="<?php echo $this->url;?>/ace/ace.js"></script>
	<script src="<?php echo $this->url;?>/ace/ext-modelist.js"></script>
	<script>
		FR.settings = <?php echo $vars;?>;
	</script>
</head>

<body id="theBODY">

<textarea style="display:none;width:100%;height:100%" id="textContents" class="x-form-field"><?php echo S::safeHTML($this->data['contents']);?></textarea>

</body>
</html>