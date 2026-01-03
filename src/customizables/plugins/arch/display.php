<?php
global $config, $settings;
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title></title>
	<?php \FileRun\UI\CSS::insertLink([
		'absoluteURLs' => true,
		'basic' => true
	]);?>
	<style>
		body {
			background-color: transparent;
			overflow:auto;
			text-align: center;
		}
		table {
			overflow:auto;
			background-color: var(--theme-bg);
			border-radius: 2px;
			margin-top:24px;
		}
		table.niceborder td {
			padding:5px;
		}
	</style>
</head>

<body>
	<table border="0" cellspacing="1" class="niceborder" style="min-width:300px;" align="center">
		<?php
		$limit = 200;
		$i = 1;

		foreach ($list as $key => $item) {
			if ($item['type'] == "file" && $item['path']) {
				if ($item['utf8_encoded']) {
					$srcEnc = "UTF-8";
				} else {
					$srcEnc = $config['app']['encoding']['unzip'] ?? S::detectEncoding($item['path']) ?? 'CP850';
				}
				$item['path'] = \S::convert2UTF8($item['path'], $srcEnc);
				$ext = \S::safeForHtml(\FM::getExtension($item['filename']));
				?>
				<tr>
					<td width="32"><div class="extLabel ext_<?php echo $ext;?>"><?php echo $ext;?></div></td>
					<td><div><?php echo S::safeHTML($item['path']);?></div></td>
					<td align="center"><?php echo \FM::formatFileSize($item['filesize']);?></td>
				</tr>
				<?php
				$i++;

				if ($i >= $limit) {
					?>
					<tr>
						<td>&nbsp;</td>
						<td colspan="2">Archive contains more files than displayed in this preview.</td>
					</tr>
					<?php
					break;
				}
			}
		}
		?>
	</table>
</body>
</html>