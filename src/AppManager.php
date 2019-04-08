<?php

declare(strict_types=1);

namespace NAttreid\AppManager;

use InvalidArgumentException;
use NAttreid\AppManager\Helpers\Backup;
use NAttreid\AppManager\Helpers\Database\SQL;
use NAttreid\AppManager\Helpers\Deploy\Composer;
use NAttreid\AppManager\Helpers\Deploy\Git;
use NAttreid\AppManager\Helpers\Files;
use NAttreid\AppManager\Helpers\Info;
use NAttreid\AppManager\Helpers\Logs;
use NAttreid\Utils\TempFile;
use Nette\SmartObject;

/**
 * Sprava aplikace
 *
 * @property-read Logs $logs
 * @property-read Info $info
 *
 * @author Attreid <attreid@gmail.com>
 */
class AppManager
{
	use SmartObject;

	/** @var callable[] */
	public $onInvalidateCache = [];

	/** @var Git */
	private $git;

	/** @var Composer */
	private $composer;

	/** @var SQL */
	private $db;

	/** @var Files */
	private $files;

	/** @var string */
	private $tempDir;

	/** @var Backup */
	private $backup;

	/** @var Logs */
	private $logs;

	/** @var Info */
	private $info;

	public function __construct(string $tempDir, Git $git, Composer $composer, SQL $db, Files $files, Backup $backup, Logs $logs, Info $info)
	{
		$this->tempDir = $tempDir;
		$this->git = $git;
		$this->composer = $composer;
		$this->db = $db;
		$this->files = $files;
		$this->backup = $backup;
		$this->logs = $logs;
		$this->info = $info;
	}

	/**
	 * Invaliduje cache
	 */
	public function invalidateCache(): void
	{
		$this->onInvalidateCache();
	}

	/**
	 * Smazani cache
	 */
	public function clearCache(): void
	{
		$this->files->clearCache();
	}

	/**
	 * Smazani expirovane session (default je nastaven na maximalni dobu expirace session)
	 * @param string $expiration format 1 minutes, 14 days atd
	 */
	public function clearSession(string $expiration = null): void
	{
		$this->files->clearSession($expiration);
	}

	/**
	 * Smaze temp
	 */
	public function clearTemp(): void
	{
		$this->files->clearTemp();
	}

	/**
	 * Smazani logu
	 */
	public function clearLog(): void
	{
		$this->files->clearLog();
	}

	/**
	 * Smaze CSS cache
	 */
	public function clearCss(): void
	{
		$this->files->clearCss();
	}

	/**
	 * Smaze Javascript cache
	 */
	public function clearJs(): void
	{
		$this->files->clearJs();
	}

	/**
	 * Zapne nebo vypne udrzbu stranek (zobrazi se stranka udrzby)
	 * @param bool $set
	 */
	public function maintenance(bool $set = true): void
	{
		$file = $this->tempDir . '/maintenance';
		if ($set) {
			file_put_contents($file, '');
		} else {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * Aktualizace zdrojovych kodu pomoci composeru
	 * @param bool $force
	 * @throws InvalidArgumentException
	 */
	public function composerUpdate(bool $force = false): void
	{
		$this->maintenance();
		if ($force) {
			$this->composer->update();
		} else {
			$this->composer->authorizedUpdate();
		}
		$this->maintenance(false);
	}

	/**
	 * Deploy
	 * @param bool $force
	 * @throws InvalidArgumentException
	 */
	public function gitPull(bool $force = false): void
	{
		$this->maintenance();
		if ($force) {
			$this->git->update();
		} else {
			$this->git->authorizedUpdate();
		}
		$this->maintenance(false);
	}

	/**
	 * Vrati zalohu databaze
	 * @param TempFile|null $file
	 * @return TempFile
	 */
	public function backupDatabase(TempFile $file = null): TempFile
	{
		return $this->db->compressBackupDatabase($file);
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase(): void
	{
		$this->db->dropDatabase();
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase(string $file): void
	{
		$this->db->loadDatabase($file);
	}

	/**
	 * Vrati zalohu
	 * @param TempFile|null $file
	 * @return TempFile
	 */
	public function backup(TempFile $file = null): TempFile
	{
		return $this->backup->backup($file);
	}

	/**
	 * @return Logs
	 */
	protected function getLogs(): Logs
	{
		return $this->logs;
	}

	/**
	 * @return Info
	 */
	protected function getInfo(): Info
	{
		return $this->info;
	}

}
