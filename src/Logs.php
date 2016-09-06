<?php

namespace NAttreid\AppManager;

use NAttreid\Utils\Date;
use NAttreid\Utils\File;
use NAttreid\Utils\Hasher;
use NAttreid\Utils\Number;
use NAttreid\Utils\TempFile;
use Nette\Application\Responses\FileResponse;
use Nette\IOException;
use Nette\Utils\Strings;

/**
 * Sluzba logu
 *
 * @property-read array $logs
 * @author Attreid <attreid@gmail.com>
 */
class Logs
{

	use \Nette\SmartObject;

	/** @var string */
	private $path;

	/** @var Hasher */
	private $hasher;

	/** @var array */
	private $logs;

	public function __construct($path, Hasher $hasher)
	{
		if (!\Nette\Utils\Strings::endsWith($path, DIRECTORY_SEPARATOR)) {
			$path .= DIRECTORY_SEPARATOR;
		}
		$this->path = $path;
		$this->hasher = $hasher;
	}

	/**
	 * Vrati seznam logu
	 * @return array
	 */
	public function getLogs()
	{
		if ($this->logs === NULL) {
			$this->logs = $this->readLogs();
		}
		return $this->logs;
	}

	/**
	 * Vrati konkretni log
	 * @param int $index
	 * @return array
	 */
	public function getLog($index)
	{
		return $this->getLogs()[$index];
	}

	/**
	 * Vrati seznam logu
	 * @return array
	 */
	private function readLogs()
	{
		$logs = [];
		$dir = @dir($this->path);
		if (!$dir) {
			throw new IOException("getLogs: Failed opening directory '$this->path' for reading");
		}
		while ($file = $dir->read()) {
			if ($file == '.' || $file == '..' || $file == 'web.config' || $file == '.htaccess') {
				continue;
			} else {
				$hash = $this->hasher->hash($file);
				$logs[$hash] = [
					'id' => $hash,
					'name' => $file,
					'size' => Number::size(filesize($this->path . $file)),
					'changed' => filemtime($this->path . $file)
				];
			}
		}
		return $logs;
	}

	/**
	 * Smaze logy
	 * @param int|array $id
	 */
	public function delete($id)
	{
		if (is_array($id)) {
			foreach ($id as $key) {
				unlink($this->path . $this->getLog($key)['name']);
			}
		} else {
			unlink($this->path . $this->getLog($id)['name']);
		}
		$this->logs = NULL;
	}

	/**
	 * Vrati soubor ke stazeni
	 * @param int $id
	 * @return FileResponse
	 */
	public function getFile($id)
	{
		$file = $this->getLog($id)['name'];
		if (Strings::endsWith($file, '.html')) {
			$contentType = 'text/html';
		} else {
			$contentType = 'text/plain';
		}
		return new FileResponse($this->path . $file, $file, $contentType, FALSE);
	}

	/**
	 * Vrati soubor/y ke stazeni (pokud je jich vice tak je zabali do archivu)
	 * @param int|array $id
	 * @return FileResponse
	 */
	public function downloadFile($id)
	{
		if (is_array($id)) {
			$file = new TempFile;
			$name = 'Logs_' . Date::getCurrentTimeStamp() . '.zip';
			$archive = [];
			foreach ($id as $i) {
				$archive[] = $this->path . $this->getLog($i)['name'];
			}
			File::zip($archive, $file);
			return new FileResponse($file, $name);
		} else {
			$file = $this->getLog($id)['name'];
			return new FileResponse($this->path . $file, $file);
		}
	}

}
