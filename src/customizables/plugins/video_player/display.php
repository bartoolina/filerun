<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<style>
		body {
			border: 0;
			margin: 0;
			padding: 0;
			overflow: hidden;
		}
		video {
			width: 100%;
            height: 100%;
		}
	</style>
</head>
<body oncontextmenu="return false;">
<video controls="controls" controlsList="nodownload" autoplay <?php if ($loop) {echo 'loop';}?> preload="auto" src="<?php echo $URL;?>" <?php if ($mime) {echo 'type="'.$mime.'"';}?>></video>
</body>
</html>