<?php
use FileRun\Utils\HTTP;
use FileRun\Paths;
use FileRun\Perms;
use FileRun\Share;
use FileRun\Lang;
use FileRun\Files\Actions\Read;

class custom_link extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: External link";

	function init() {

		$this->JSconfig = [
			"title" => self::t('External link'),
			'iconCls' => 'fa-external-link-square',
			'useWith'=> ['nothing'],
			"createNew" => [
				"title" => self::t('External link'),
				'fn' => 'FR.actions.createURL()'
			],
			"requiredUserPerms" => ["upload"],
			'requires' => ['upload']
		];
	}

	function run() {
		$postedURL = \S::fromHTML($_POST['url']);
		if (!$postedURL) {
			jsonFeedback(false, 'Missing URL');
		}
		$postedURL = filter_var($postedURL, FILTER_SANITIZE_URL);
		if (!filter_var($postedURL, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
			jsonFeedback(false, 'The URL does not seem valid');
		}
		$parsed = parse_url($postedURL);
		if (!$parsed) {
			jsonFeedback(false, 'Failed to parse the URL');
		}
		if (!in_array($parsed['scheme'], ['http', 'https'])) {
			jsonFeedback(false, 'Only http(s) URLs are supported');
		}

		$fileSize = 0;
		$rs = self::getMoreDetails($postedURL) ?: $postedURL;
		if (is_array($rs) && $rs['fileName']) {
			$fileName = $rs['fileName'];
			$url = $postedURL;
		} else {
			$url = $rs;
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36';
			$headers = [
				'User-Agent' => $userAgent
			];

			$headFailed = false;
			$rs = HTTP::fetchFromURL([
				'url' => $url,
				'method' => 'HEAD',
				'returnResponse' => true,
				'headers' => $headers,
				'timeout' => 2
			]);
			$getTitle = false;
			if ($rs && !in_array($rs->getStatusCode(), [403, 404])) {
				if ($rs->hasHeader('Content-Type')) {
					$contentType = $rs->getHeader('Content-Type');
					if (isset($contentType[0])) {
						$contentType = $contentType[0];
						if (stripos($contentType, 'text/html') !== false) {
							$getTitle = true;
						}
					}
				}
			} else {
				$headFailed = true;
			}

			if ($headFailed || $getTitle) {
				$rs = HTTP::fetchFromURL([
					'url' => $url,
					'method' => 'GET',
					'returnResponse' => true,
					'headers' => $headers,
					'timeout' => 2
				]);
				if ($rs && !in_array($rs->getStatusCode(), [403, 404])) {
					if ($rs->hasHeader('Content-Type')) {
						$contentType = $rs->getHeader('Content-Type');
						if (isset($contentType[0])) {
							$contentType = $contentType[0];
							if (stripos($contentType, 'text/html') !== false) {
								$getTitle = true;
							}
						}
					}
				}
			}
			if ($getTitle) {
				$body = $rs->getBody();
				if ($body) {
					$pageContent = $body->read(2024);
					if ($pageContent) {
						$res = preg_match("/<title>(.*)<\/title>/siU", $pageContent, $title_matches);
				        if ($res) {
					        $title = preg_replace('/\s+/', ' ', $title_matches[1]);
				        }
			        }
				}
			} else {
				if ($rs && $rs->hasHeader('Content-Disposition')) {
					$cd = $rs->getHeader('Content-Disposition');
					if (is_array($cd)) {
						$cd = $cd[0];
						if ($cd) {
							$parsed = \GuzzleHttp\Psr7\parse_header($cd);
							if (is_array($parsed)) {
								$parsed = $parsed[0];
								if ($parsed) {
									$fileName = $parsed['filename'];
								}
							}
						}
					}
				}
				if ($rs && $rs->hasHeader('Content-Length')) {
					$cl = $rs->getHeader('Content-Length');
					if (is_array($cl)) {
						$fileSize = $cl[0];
					}
				}
			}
		}

		$fileRunData = [
			'FileRunData' => [
				'created' => time(),
				'URL' => $url
			]
		];
		if ($postedURL != $url) {
			$fileRunData['DirectURL'] = $url;
		}
		if ($title) {
			$fileRunData['PageTitle'] = $title;
		}
		if ($fileName) {
			$fileRunData['ActualFileName'] = $fileName;
		}
		if ($fileSize) {
			$fileRunData['ActualFileSize'] = $fileSize;
		}

		$contents = '[InternetShortcut]'."\r\n".
			'URL='.$url."\r\n\r\n".
			'; '.base64_encode(json_encode($fileRunData));

		if (!$fileName && !$title) {
			$fileName = \S::withoutPrefix('www.', $parsed['host']);
			if ($parsed['path']) {
				$fileName .= ' '.\FM::basename($parsed['path']);
			}
		} else {
			if ($title) {$fileName = $title;}
		}
		$fileName = \S::convert2UTF8(\FM::stripIlegalCharacters(trim($fileName)));
		if (file_exists(gluePath($this->data['fullPath'], $fileName.'.url'))) {
			$fileName .= ' '.time();
		}
		$fileName .= '.url';
		$this->data['relativePath'] = gluePath($this->data['relativePath'], $fileName);
		$this->writeFile([
			'preventOverwrite' => true,
			'source' => 'string',
			'contents' => $contents
		]);
		jsonOutput([
			'success' => true,
			'msg' => 'File successfully created',
			'fileName' => $fileName
		]);
	}

	static function getMoreDetails($url) {
		$parsed = parse_url($url);
		if ($parsed['host'] == $_SERVER['HTTP_HOST']) {
			if ($parsed['fragment']) {
				$relativePath = gluePath('/ROOT', \S::fromHTML($parsed['fragment'], true));
				$data = Read\Prepare::prepare($relativePath);
				if ($data && $data['fileName']) {
					if ($data['userHomeFolderPath'] && $data['fullPath']) {
						return ['fileName' => Lang::t('My Files', 'Main Interface')];
					}
					return ['fileName' => $data['fileName']];
				}
			} else if ($parsed['path']) {
				$key = 'index.php/f/';
				$pos = stripos($parsed['path'], $key);
				if ($pos === false) {return false;}
				$fileId = substr($parsed['path'], $pos+strlen($key));
				if (!$fileId) {return false;}
				$pathInfo = Paths::getById($fileId);
				if (!$pathInfo) {return false;}
				$fullPath = $pathInfo['path'];
				$userHomeFolderPath = Perms::getOne('homefolder');
				if ($userHomeFolderPath) {
					if ($fullPath == $userHomeFolderPath) {
						return ['fileName' => Lang::t('My Files', 'Main Interface')];
					}
					if (\FM::inPath($fullPath, $userHomeFolderPath)) {
						return ['fileName' => \FM::basename($fullPath)];
					}
				}
				if (Perms::check('users_may_see')) {
					$shares = Share::getShares();
					foreach ($shares as $shareInfo) {
						if ($shareInfo['path'] == $fullPath) {
							return ['fileName' => $shareInfo['alias'] ?? \FM::basename($fullPath)];
						}
						if ($shareInfo['type'] == 'folder' && \FM::inPath($fullPath, $shareInfo['path'])) {
							return ['fileName' => \FM::basename($fullPath)];
						}
					}
				}
			}
			return false;
		}
		if ($parsed['host'] == 'drive.google.com') {
			$parts = explode('/', $parsed['path']);
			$id = $parts[3];
			if (!$id) {return false;}
			return $parsed['scheme'].'://'.$parsed['host'].'/uc?export=download&id='.$id;
		}
		if ($parsed['host'] == 'www.dropbox.com') {
			if (\FM::firstname($parsed['path']) == 'sh') {
				return false;
			}
			parse_str($parsed['query'], $query);
			$query['dl'] = '1';
			$query = http_build_query($query);
			return gluePath($parsed['scheme'].'://'.$parsed['host'], $parsed['path'].'?'.$query);
		}
		return false;
	}
}