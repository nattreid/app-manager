<?php

namespace NAttreid\AppManager\Deploy;

use Nette\Http\Request,
    Tracy\Debugger;

/**
 * Composer
 *
 * @author attreid
 */
class Composer extends Deploy {

    use \Nette\SmartObject;

    /** @var string */
    private $composerDir = '/composer';

    /** @var string */
    private $composer = '/composer.phar';

    /** @var string */
    private $path, $tempDir, $composer;

    public function __construct($appDir, $tempDir, $url, $ip, Request $request) {
        parent::__construct($request, $url, $ip);
        $this->path = $appDir . '/..';
        $this->tempDir = $tempDir;
    }

    /**
     * Vrati composer
     * @return string
     */
    private function getComposer() {
        $composer = $this->tempDir . $this->composer;
        if (!file_exists($composer)) {
            $command = 'cd ' . $this->tempDir . ';'
                    . 'php -r "readfile(\'https://getcomposer.org/installer\');" > composer-setup.php;'
                    . 'php -r "if (hash_file(\'SHA384\', \'composer-setup.php\') === \'a52be7b8724e47499b039d53415953cc3d5b459b9d9c0308301f867921c19efc623b81dfef8fc2be194a5cf56945d223\') { echo \'Installer verified\'; } else { echo \'Installer corrupt\'; unlink(\'composer-setup.php\'); } echo PHP_EOL;";'
                    . 'php composer-setup.php;'
                    . 'php -r "unlink(\'composer-setup.php\');";';
            exec($command, $output);
            foreach ($output as $str) {
                if (!empty($str)) {
                    Debugger::log($str, 'composer');
                }
            }
        }
        return $composer;
    }

    /**
     * Aktualizuje composer
     */
    public function update() {
        $composer = $this->getComposer();

        $temp = $this->temp . $this->composerDir;
        if (!file_exists($temp)) {
            mkdir($temp);
        }
        $command = 'cd ' . $this->path . ';'
                . 'COMPOSER_HOME="' . $temp . '" ' . $composer . ' self-update 2>&1;'
                . 'COMPOSER_HOME="' . $temp . '" ' . $composer . ' update 2>&1;';
        exec($command, $output);
        foreach ($output as $str) {
            if (!empty($str)) {
                Debugger::log($str, 'composer');
            }
        }
    }

    /**
     * Akutalizuje composer, pokud je pristup z povolene adresy
     * 
     * @throws \InvalidArgumentException
     */
    public function authorizedUpdate() {
        if ($this->checkAccess()) {
            $this->update();
        }
    }

}
