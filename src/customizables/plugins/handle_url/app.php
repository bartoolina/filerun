<?php
use \FileRun\Lang;

class custom_handle_url extends \FileRun\Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = 'Custom Actions: Link Opener';

	function init() {
		$this->JSconfig = [
			'title' => self::t('Link Opener'),
			'iconCls' => 'fa-share-square-o',
			'extensions' => ['url'],
			'newTab' => true,
			'requiredUserPerms' => ['preview'],
			'requires' => ['preview'],
			'replaceDoubleClickAction' => true
		];
	}
	function run() {
		$this->data['contents'] = $this->readFile();
		$c = explode("\n", $this->data['contents']);
		foreach ($c as $r) {
			if (stripos($r, 'URL=') !== false) {
				$url = str_ireplace(['URL=', '\''], [''], $r);
				header('Location: '.$url);
				echo \FileRun\Lang::t('Click <a href="%1" target="_blank">here</a> to open the link', self::$localeSection, [$url]);
				echo '<script>document.location.href = \''.\S::safeJS($url).'\';</script>';
				exit();
			}
		}
		Lang::d('No URL found. Here are the file contents:');
		echo '<br>';
		echo nl2br(\S::safeForHtml($this->data['contents']));
	}
}
