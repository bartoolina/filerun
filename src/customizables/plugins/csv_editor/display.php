<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<link rel="stylesheet" href="<?php echo $this->url;?>/jspreadsheet/jsuites.css?v=<?php echo $settings->currentVersion;?>" />
	<link rel="stylesheet" href="<?php echo $this->url;?>/jspreadsheet/jspreadsheet.css?v=<?php echo $settings->currentVersion;?>" />
	<link rel="stylesheet" href="<?php echo $this->url;?>/jspreadsheet/jexcel.theme.css?v=<?php echo $settings->currentVersion;?>" />

	<?php \FileRun\UI\JS::insertScripts(['ext']);?>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="<?php echo $config['url']['root'];?>/?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL("Custom Actions: CSV Editor")?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script src="<?php echo $this->url;?>/jspreadsheet/index.min.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="<?php echo $this->url;?>/jspreadsheet/jsuites.min.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script>
		FR.settings = <?php echo $vars;?>;
	</script>
</head>

<body id="theBODY" onload="FR.init()">


</body>
</html>