<?php

namespace NAttreid\AppManager\Deploy;

use Nette\Http\Request,
    Tracy\Debugger;

/**
 * Gitlab
 *
 * @author Attreid <attreid@gmail.com>
 */
class Gitlab extends Deploy {

    use \Nette\SmartObject;

    /** @var string */
    private $path;

    public function __construct($appDir, $url, $ip, Request $request) {
        parent::__construct($request, $url, $ip);
        $this->path = $appDir . '/..';
    }

    /**
     * Aktualizuje z gitlabu
     */
    public function update() {
        $command = 'cd ' . $this->path . ';'
                . 'git checkout -- app/;'
                . 'git checkout -- bin/;'
                . 'git checkout -- vendor/others/;'
                . 'git checkout -- www/.htaccess;'
                . 'git checkout -- .gitignore;'
                . 'git pull" 2>&1;'
                . 'rm temp/cache/* -rf;'
                . 'rm www/webtemp/* -rf;';
        exec($command, $output);
        foreach ($output as $str) {
            Debugger::log($str, 'gitlab');
        }
    }

    /**
     * Akutalizuje z gitlabu, pokud je pristup z povolene adresy
     * 
     * @throws \InvalidArgumentException
     */
    public function authorizedUpdate() {
        if ($this->checkAccess()) {
            $this->update();
        }
    }

}
