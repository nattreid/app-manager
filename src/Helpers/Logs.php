<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers;

use NAttreid\Utils\Date;
use NAttreid\Utils\File;
use NAttreid\Utils\Hasher;
use NAttreid\Utils\Strings;
use NAttreid\Utils\TempFile;
use Nette\Application\Responses\FileResponse;
use Nette\IOException;
use Nette\SmartObject;

/**
 * Sluzba logu
 *
 * @property-read Log[] $logs
 *
 * @author Attreid <attreid@gmail.com>
 */
class Logs
{

	use SmartObject;

	/** @var string */
	private $path;

	/** @var Hasher */
	private $hasher;

	/** @var Log[] */
	private $logs;

	public function __construct(string $path, Hasher $hasher)
	{
		if (!Strings::endsWith($path, DIRECTORY_SEPARATOR)) {
			$path .= DIRECTORY_SEPARATOR;
		}
		$this->path = $path;
		$this->hasher = $hasher;
	}

	/**
	 * Vrati seznam logu
	 * @return Log[]
	 */
	protected function getLogs(): array
	{
		if ($this->logs === null) {
			$this->logs = $this->readLogs();
		}
		return $this->logs;
	}

	/**
	 * Vrati konkretni log
	 * @param string $index
	 * @return Log
	 */
	public function getLog(string $index): Log
	{
		return $this->getLogs()[$index];
	}

	/**
	 * Vrati seznam logu
	 * @return Log[]
	 */
	private function readLogs(): array
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
				$logs[$hash] = new Log($hash, $this->path, $file);
			}
		}
		return $logs;
	}

	/**
	 * Smaze logy
	 * @param string|string[] $id
	 */
	public function delete($id = null)
	{
		if ($id === null) {
			foreach ($this->getLogs() as $log) {
				unlink($this->path . $log->name);
			}
		} elseif (is_array($id)) {
			foreach ($id as $key) {
				unlink($this->path . $this->getLog($key)->name);
			}
		} else {
			unlink($this->path . $this->getLog((string) $id)->name);
		}
		$this->logs = null;
	}

	/**
	 * Vrati soubor ke stazeni
	 * @param string $id
	 * @return FileResponse
	 */
	public function getFile(string $id): FileResponse
	{
		$file = $this->getLog($id)->name;
		if (Strings::endsWith($file, '.html')) {
			$contentType = 'text/html';
		} else {
			$contentType = 'text/plain';
		}
		return new FileResponse($this->path . $file, $file, $contentType, false);
	}

	/**
	 * Vrati soubor/y ke stazeni (pokud je jich vice tak je zabali do archivu)
	 * @param string|string[] $id
	 * @return FileResponse
	 */
	public function downloadFile($id): FileResponse
	{
		if (is_array($id)) {
			$file = new TempFile;
			$name = 'Logs_' . Date::getCurrentTimeStamp() . '.zip';
			$archive = [];
			foreach ($id as $i) {
				$archive[] = $this->path . $this->getLog($i)->name;
			}
			File::zip($archive, (string) $file);
			return new FileResponse($file, $name);
		} else {
			$file = $this->getLog((string) $id)->name;
			return new FileResponse($this->path . $file, $file);
		}
	}

}
