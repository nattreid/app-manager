<?php

namespace NAttreid\AppManager\Helpers;

use NAttreid\AppManager\Helpers\Database\SQL;
use NAttreid\Utils\File;
use NAttreid\Utils\TempFile;

/**
 * Class Backup
 *
 * @author Attreid <attreid@gmail.com>
 */
class Backup
{
	/** @var string[] */
	private $dirs;

	/** @var SQL */
	private $database;

	/**
	 * Backup constructor.
	 */
	public function __construct(array $dirs, SQL $database)
	{
		$this->dirs = $dirs;
		$this->database = $database;
	}

	/**
	 * Vrati zalohu databaze
	 * @return TempFile
	 */
	public function backup()
	{
		$backup[] = $this->database->backupDatabase();
		foreach ($this->dirs as $dir) {
			$backup[] = $dir;
		}

		$archive = new TempFile;
		File::zip($backup, $archive);

		return $archive;
	}
}