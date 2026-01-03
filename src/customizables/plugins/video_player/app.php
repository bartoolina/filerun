<?php

class custom_video_player extends \FileRun\Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = 'Custom Actions: Video Player';
	static $publicMethods = ['openInBrowser', 'stream', 'thumb'];

	function init() {
		$this->JSconfig = [
			'title' => self::t('Video Player'),
			'iconCls' => 'fa fa-fw fa-play-circle-o',
			'useWith' => ['wvideo'],
			'popup' => true,
			'requires' => ['download']
		];
		$this->settings = [
			[
				'key' => 'allow_without_download',
				'title' => self::t('Allow playback without download permission'),
				'type' => 'checkbox',
				'helpText' => self::t('Use this option only if your files are not confidential.')
			],
			[
				'key' => 'loop_playback',
				'title' => self::t('Loop playback'),
				'type' => 'checkbox'
			]
		];
	}

	function run() {
		if ($this->isLimitedPreview() && !$this->getSetting('allow_without_download')) {
			$this->centeredThumb();
			return true;
		}
		$URL = $this->actionURL.'&method=stream';
		if ($_GET['videoStartTime']) {
			$URL .= '#t='.\S::fromHTML($_GET['videoStartTime']);
		}
		$mime = \FM::mime_type($this->data['fileName']);
		$loop = $this->getSetting('loop_playback');
		require $this->path.'/display.php';
		return true;
	}

	function stream() {
		$this->streamFileForPreview();
	}
}