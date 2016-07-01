<?php

namespace NAttreid\AppManager;

use NAttreid\Utils\File,
    Nette\Utils\Finder,
    Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Tracy\Debugger,
    NAttreid\AppManager\Deploy\Gitlab,
    NAttreid\AppManager\Deploy\Composer,
    Nextras\Dbal\Connection;

/**
 * Sprava aplikace
 * 
 * @author Attreid <attreid@gmail.com>
 * 
 * @todo predelat repository cache
 */
class AppManager {

    use \Nette\SmartObject;

    /** @var string */
    private $appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration, $webLoaderDir;

    /** @var Gitlab */
    private $gitlab;

    /** @var Composer */
    private $composer;

    /** @var Connection */
    private $db;

    /** @var Cache */
    private $cache;

    public function __construct($appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration, IStorage $cacheStorage, Gitlab $gitlab, Composer $composer, Connection $db) {
        $this->appDir = $appDir;
        $this->wwwDir = $wwwDir;
        $this->tempDir = $tempDir;
        $this->logDir = $logDir;
        $this->sessionDir = $sessionDir;
        $this->webLoaderDir = $wwwDir . '/' . \WebLoader\Nette\Extension::DEFAULT_TEMP_PATH;
        $this->sessionExpiration = $sessionExpiration;

        $this->cache = new Cache($cacheStorage);

        $this->gitlab = $gitlab;
        $this->composer = $composer;

        $this->db = $db;
    }

    /**
     * Smazani cache
     */
    public function clearCache() {
        File::removeDir($this->tempDir . '/cache', FALSE);
        if (file_exists($this->webLoaderDir)) {
            foreach (Finder::findFiles('*')
                    ->exclude('.htaccess', 'web.config')
                    ->in($this->webLoaderDir) as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Smazani expirovane session (default je nastaven na maximalni dobu expirace session)
     * @param string $expiration format 1 minutes, 14 days atd
     */
    public function clearSession($expiration = NULL) {
        if ($expiration === NULL) {
            $expiration = $this->sessionExpiration;
        }
        foreach (Finder::findFiles('*')->date('<', '- ' . $expiration)
                ->exclude('.htaccess', 'web.config')
                ->in($this->sessionDir) as $file) {
            unlink($file);
        }
    }

    /**
     * Smaze temp
     */
    public function clearTemp() {
        foreach (Finder::findFiles('*')
                ->exclude('.htaccess', 'web.config')
                ->in($this->tempDir) as $file) {
            unlink($file);
        }
        foreach (Finder::findDirectories('*')
                ->in($this->tempDir) as $dir) {
            File::removeDir($dir);
        }
    }

    /**
     * Smazani logu
     */
    public function clearLog() {
        foreach (Finder::findFiles('*')
                ->exclude('.htaccess', 'web.config')
                ->in($this->logDir) as $file) {
            unlink($file);
        }
    }

    /**
     * Smaze cache modelu
     * @todo predelat repository cache
     */
    public function cleanModelCache() {
        $this->cache->clean([
            Cache::TAGS => [\NAttreid\Orm\Mapper::TAG_MODEL]
        ]);
    }

    /**
     * Smaze CSS cache
     */
    public function clearCss() {
        if (file_exists($this->webLoaderDir)) {
            foreach (Finder::findFiles('*.css')
                    ->exclude('.htaccess', 'web.config')
                    ->in($this->webLoaderDir) as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Smaze Javascript cache
     */
    public function clearJs() {
        if (file_exists($this->webLoaderDir)) {
            foreach (Finder::findFiles('*.js')
                    ->exclude('.htaccess', 'web.config')
                    ->in($this->webLoaderDir) as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Zapne nebo vypne udrzbu stranek (zobrazi se stranka udrzby)
     * @param boolean $set
     */
    public function maintenance($set = TRUE) {
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
     * @param boolean $force
     * @throws \InvalidArgumentException
     */
    public function composerUpdate($force = FALSE) {
        $this->maintenance();
        if ($force) {
            $this->composer->update();
        } else {
            $this->composer->authorizedUpdate();
        }
        $this->maintenance(FALSE);
    }

    /**
     * Deploy
     * @param boolean $force
     * @throws \InvalidArgumentException
     */
    public function gitPull($force = FALSE) {
        $this->maintenance();
        if ($force) {
            $this->gitlab->update();
        } else {
            $this->gitlab->authorizedUpdate();
        }
        $this->maintenance(FALSE);
    }

    /**
     * Vrati zalohu databaze
     * @return \NAttreid\Utils\TempFile
     */
    public function backupDatabase() {
        $backup = new \NAttreid\Utils\TempFile('backup.sql', TRUE);
        $tables = $this->db->query('SHOW TABLES')->fetchPairs();

        $backup->write("SET NAMES utf8;\n");
        $backup->write("SET time_zone = '+00:00';");
        $backup->write("SET foreign_key_checks = 0;");
        $backup->write("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';");

        foreach ($tables as $table) {
            $backup->write("DROP TABLE $table;\n");

            $createTable = $this->db->query("SHOW CREATE TABLE $table")->fetchField(1);
            $backup->write("$createTable;\n\n");

            $query = $this->db->query("SELECT * FROM $table");

            $rows = $query->fetchAll();
            $numColumn = $query->getColumnCount();

            $insert = [];
            foreach ($rows as $row) {
                $cols = [];
                for ($col = 0; $col < $numColumn; $col++) {
                    $row[$col] = addslashes($row[$col]);
                    $row[$col] = preg_replace("/\n/", "\\n", $row[$col]);
                    if (isset($row[$col])) {
                        $cols[] = '"' . $row[$col] . '"';
                    } else {
                        $cols[] .= '""';
                    }
                }
                $insert[] = implode(',', $cols);
            }

            if (!empty($insert)) {
                $backup->write("INSERT INTO $table VALUES\n(" . implode("),\n(", $insert) . ");\n");
            }

            $backup->write("\n\n");
        }

        // zip
        $archive = new \NAttreid\Utils\TempFile;
        File::zip($backup, $archive);

        return $archive;
    }

    /**
     * Smaze vsechny tabulky v databazi
     */
    public function dropDatabase() {
        $tables = $this->db->query('SHOW TABLES')->fetchPairs();
        if (!empty($tables)) {
            $this->db->query('SET foreign_key_checks = 0');
            $this->db->query('DROP TABLE ' . implode(',', $tables));
            $this->db->query('SET foreign_key_checks = 1');
        }
        $this->db->getStructure()->rebuild();
    }

    /**
     * Nahraje databazi
     * @param string $file
     * @return boolean
     */
    public function loadDatabase($file) {
        $this->db->beginTransaction();
        try {
            $this->db->query('SET foreign_key_checks = 0');
            \Nette\Database\Helpers::loadFromFile($this->db->getConnection(), $file);
            $this->db->query('SET foreign_key_checks = 1');
            $this->db->commit();
            return TRUE;
        } catch (\PDOException $ex) {
            Debugger::log($ex, \Tracy\ILogger::ERROR);
            $this->db->rollBack();
        }
        return FALSE;
    }

}
