<?php
use \CloudConvert\CloudConvert;
use \CloudConvert\Models\Job;
use \CloudConvert\Models\Task;
use \CloudConvert\Transport\HttpTransport;

class custom_cloudconvert extends \FileRun\Files\Plugin {

	var $online = true;
	var $autoload = true;
	static $localeSection = "Custom Actions: CloudConvert";
	static $publicMethods = ['requestConversion', 'getStatus'];

	function init() {
		$this->settings = [
			[
				'key' => 'APIKey',
				'title' => self::t('API key'),
				'comment' => self::t('Get it from %1', ['<a href="https://cloudconvert.com/api" target="_blank">https://cloudconvert.com/api</a>'])
			]
		];
		$this->JSconfig = [
            'nonTouch' => true,
			"title" => self::t("CloudConvert"),
			'icon' => 'images/icons/cloudconvert.png',
			"popup" => true, 'width' => 460, 'height' => 400,
			"requiredUserPerms" => ["download", "upload"],
			"requires" => ["download", "create"]
		];
	}

	function isDisabled() {
		return ($this->getSetting('APIKey') == '');
	}

	function run() {
		$ext = \FM::getExtension($this->data['fullPath']);
		$ccHttp = new HttpTransport([]);
		try {
			$response = $ccHttp->get($ccHttp->getBaseUri() .'/convert/formats', ['filter[input_format]' => $ext]);
			$rs = json_decode($response->getBody(), true);
		} catch(Exception $e) {
			self::outputError($e->getMessage());
		}
		require $this->path."/display.php";
	}

	function getClient(): CloudConvert {
		return new CloudConvert([
		    'api_key' => $this->getSetting('APIKey'),
		    'sandbox' => false
		]);
	}

	function requestConversion() {
		$filePointer = $this->readFile(['returnFilePointer' => true]);
		$cloudconvert = $this->getClient();
		$uploadName = 'FileRun-Upload';
		$convertName = 'FileRun-Convert';
		try {
			$job = (new Job())
			    ->addTask(new Task('import/upload',$uploadName))
			    ->addTask(
			        (new Task('convert', $convertName))
			            ->set('input', $uploadName)
			            ->set('output_format', \S::fromHTML($_POST['format']))
			    )
			    ->addTask(
			        (new Task('export/url', 'FileRun-Export'))
			            ->set('input', $convertName)
			    );
			$cloudconvert->jobs()->create($job);
			$tasks = $job->getTasks();
			$uploadTask = $tasks->whereOperation('import/upload')[0];
			$cloudconvert->tasks()->upload($uploadTask, $filePointer, $this->data['fileName']);
		} catch (\RuntimeException $e) {
			self::outputError($e->getMessage());
		}

		$exportTask = $tasks->whereOperation('export/url')[0];
		jsonOutput(['msg' => self::t('File transferred'), 'taskId' => $exportTask->getId()]);
	}

	function getStatus() {
		$cloudconvert = $this->getClient();
		try {
			$task = $cloudconvert->tasks()->get($_REQUEST['taskId']);
		} catch (\RuntimeException $e) {
			self::outputError($e->getMessage());
		}
		$status = $task->getStatus();
		if ($status != 'finished') {
			jsonOutput([
				'success' => false,
				'msg' => 'CloudConvert: '.$status,
				'step' => $status
			]);
		}
		$this->downloadConverted($task);
	}

	private function downloadConverted($task) {
		$file = $task->getResult()->files[0];
		$newName = self::t('[converted]').' '.$file->filename;
		$this->data['relativePath'] = \FM::newName($this->data['relativePath'], $newName);
		$data = $this->prepareWrite();
		$tempFilePath = $data['fullPath'].'.cloudconvert.tmp';
		$rs = \FileRun\Utils\HTTP::fetchFromURL([
			'url' => $file->url,
			'savePath' => $tempFilePath
		]);
		if (!$rs) {
			jsonOutput([
				'success' => false,
				'msg' => 'Failed to save the downloaded file',
				'step' => 'error'
			]);
		}
		$this->writeFile([
			'source' => 'move',
			'moveFullPath' => $tempFilePath
		]);
		jsonOutput([
			'success' => true,
			'msg' => 'Converted file was saved',
			'step' => 'downloaded',
			'newFileName' => $newName
		]);
	}
}