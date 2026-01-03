<?php
use FileRun\Files;
use FileRun\Paths;
use FileRun\Lang;
use FileRun\Collections\Collections;

class custom_folder_index extends Files\Plugin {

	static $localeSection = 'Custom Actions: Folder Index';
	static $publicMethods = ['export'];

	function init() {
		$this->JSconfig = [
			"title" => self::t('Folder Index'),
			'iconCls' => 'fa-folder-tree',
			'folder' => true,
			'popup' => true,
			'requires' => ['download', 'isFolderOrCollection'],
			'loadingMsg' => self::t('Loading...')
		];
		$this->settings = [
			[
				'key' => 'full_file_paths',
				'title' => self::t('Show full file paths'),
				'type' => 'checkbox'
			]
		];
	}

	function run() {
		if ($this->data['folder']) {
			$icon = 'fa fa-fw fa-folder';
			if ($this->data['userHomeFolderPath'] == $this->data['fullPath']) {
				$title = Lang::t('My Files');
			} else {
				$title = $this->data['fileName'];
			}
		} else if ($this->data['collection']) {
			$icon = 'fa fa-fw fa-clone';
			$title = $this->data['collectionInfo']['name'];
		}
		$list = $this->prepareData('custom_folder_index::displayDecorator');
		require $this->path.'/display.php';
	}

	function prepareData(string $decorator) {
		$data = $this->prepareRead();

		if ($data['folder'] && is_dir($data['fullPath'])) {
			$list = self::getFolder($data['fullPath'], $decorator);
		} else if ($data['collection']) {
			$collectionItems = Collections\Actions\Read\Prepare::prepareItems($data['collectionInfo']['id']);
			if (!$collectionItems) {
				$error = Collections\Actions\Read\Prepare::getError();
				if (is_array($error)) {
					self::outputError($error['public']);
				}
			}
			$list = [];
			foreach($collectionItems as $collectionItem) {
				if (!file_exists($collectionItem['fullPath'])) {continue;}
				if ($collectionItem['folder']) {
					$collectionItem['items'] = self::getFolder($collectionItem['fullPath'], $decorator, [
						'parentRelativePath' => $collectionItem['relativePath']
					]);
				} else {
					$decorator($collectionItem);
				}
				$list[] = $collectionItem;
			}
		} else {
			self::outputError('The selected path is not a folder nor a collection', 'html');
		}
		usort($list, 'custom_folder_index::sortByFolderAndName');
		return $list;
	}

	static function getFolder(string $path, string $decorator, array $decoratorDetails = []):array {
		return Files\Listing::getTree([
			'path' => $path,
			'recursive' => true,
			'returnRelative' => true,
			'returnFileName' => true,
			'returnFull' => true,
			'dirFilterF' => 'dirFilterByPattern',
			'fileFilterF' => 'fileFilterByPattern',
			'decorator' => $decorator,
			'decoratorDetails' => $decoratorDetails
		]);
	}

	static function displayDecorator(array &$item): void {
		global $config;
		if ($item['folder']) {
			$item['countFolders'] = 0;
			$item['countFiles'] = 0;
			foreach ($item['items'] as $subItem) {
				if ($subItem['folder']) {
					$item['countFolders']++;
				} else {
					$item['countFiles']++;
				}
			}
			usort($item['items'], 'custom_folder_index::sortByFolderAndName');
		}
		$item['pid'] = Paths::getId($item['fullPath'], true);
		$item['url'] = $config['url']['root'].'/index.php/f/'.$item['pid'];
	}

	static function exportDecorator(array &$item, array $decoratorDetails = []): void {
		self::displayDecorator($item);
		if (isset($decoratorDetails['parentRelativePath'])) {
			$item['relativePath'] = gluePath($decoratorDetails['parentRelativePath'], $item['relativePath']);
		}
	}

	static function sortByFolderAndName($a, $b) {
		if ($a['folder']) {
			if ($b['folder']) {
				return strnatcmp($a['fileName'], $b['fileName']);
			}
			return -1;
		}
		if (!$b['folder']) {
			return strnatcmp($a['fileName'], $b['fileName']);
		}
		return 1;
	}

	function export(): void {
		if ($this->data['folder']) {
			if ($this->data['userHomeFolderPath'] == $this->data['fullPath']) {
				$title = Lang::t('My Files');
			} else {
				$title = $this->data['fileName'];
			}
		} else if ($this->data['collection']) {
			$title = $this->data['collectionInfo']['name'];
		}
		$list = $this->prepareData('custom_folder_index::exportDecorator');
		$filename = \FM::stripIlegalCharacters($title.' ('.self::t('Folder Index').' '.date("d-m-y")).').csv';
		header("Content-Disposition: attachment; filename=\"".$filename."\"");
		$out = fopen('php://output', 'wb');
		$fieldNames = ['File name', 'FileRun Relative Path', 'FileRun Direct URL'];
		fputcsv($out, $fieldNames);
		self::exportFolder($list, $out);
		fclose($out);
	}

	static function exportFolder(array $items, $out): void {
		foreach ($items as $item) {
			fputcsv($out, [$item['fileName'], $item['relativePath'], $item['url']]);
			if ($item['folder']) {
				self::exportFolder($item['items'], $out);
			}
		}
	}
}