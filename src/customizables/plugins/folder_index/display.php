<?php
global $settings, $config;
?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<link href="<?php echo $this->url;?>/style.css" rel="stylesheet" />
	<?php \FileRun\UI\JS::insertScripts(['ext']);?>
	<script>
	var FR = {};
	Ext.onReady(function() {
		new Ext.Viewport({
			layout: 'fit',
			items: {
				autoScroll: true,
				tbar: {
					cls: 'fr-viewport-top-bar',
					items: [
						{
							iconCls: 'fa-print',
							text: 'Print',
							cls: 'no-print',
							style: 'margin-right:10px',
							handler: function(){window.print();}, scope: this
						},
						{
							iconCls: 'fa-download',
							text: 'Export',
							cls: 'no-print',
							handler: function(){
								document.location.href = '<?php echo $this->actionURL.'&method=export'; ?>';
							}, scope: this
						}
					]
				},
				contentEl: 'content'
			}
		});
	});
	</script>
	<script src="<?php echo $config['url']['root'];?>/?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL(self::$localeSection)?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>

</head>

<body>

<div id="content">
<?php
echo '<span class="title"><i class="'.$icon.'"></i> '.\S::safeForHtml($title).'</span>';
$displayFullPaths = self::getSetting('full_file_paths');
function displayFolder($items) {
	global $config, $displayFullPaths;
	if (count($items) == 0) {return;}
	echo '<div class="list">';
	foreach ($items as $item) {
		if ($item['folder']) {
			echo '<div class="folder">';
				echo '<a href="'.$item['url'].'" target="_blank" class="folderName"><i class="fa fa-fw fa-folder"></i> '.\S::safeForHtml($item['fileName']).'</a>';
				echo '<span class="comment">';
				if ($item['countFolders'] == 1) {
					echo custom_folder_index::t('One folder');
				} else if ($item['countFolders'] > 1) {
					echo custom_folder_index::t('%1 folders', [$item['countFolders']]);
				}
				echo '</span>';

				echo '<span class="comment">';
				if ($item['countFiles'] == 1) {
					echo custom_folder_index::t('One file');
				} else if ($item['countFiles'] > 1) {
					echo custom_folder_index::t('%1 files', [$item['countFiles']]);
				}
				echo '</span>';

				displayFolder($item['items']);
			echo '</div>';

		} else {
			$displayName = $displayFullPaths ? $item['relativePath'] : $item['fileName'];
			echo '<div class="fileName"><a href="'.$item['url'].'" target="_blank"> '.\S::safeForHtml($displayName).'</a></div>';
		}
	}
	echo '</div>';
}
displayFolder($list);
?>
</div>

</body>
</html>