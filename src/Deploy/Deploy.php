<?php

namespace NAttreid\AppManager\Deploy;

use Nette\Http\Request,
    Tracy\Debugger;

/**
 * Deploy
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Deploy {

    /** @var string */
    private $url, $ip;

    /** @var Request */
    private $request;

    public function __construct(Request $request, $url, $ip) {
        $this->request = $request;
        $this->url = $url;
        $this->ip = $ip;
    }

    /**
     * Je povolen pristup
     * @return boolean
     * @throws \InvalidArgumentException
     */
    protected function checkAccess() {
        if ($this->url === NULL || $this->ip === NULL) {
            throw new \InvalidArgumentException('Neni nastaven deploy');
        }
        $remoteAddress = $this->request->getRemoteAddress();
        if ($remoteAddress == $this->ip) {
            $json = file_get_contents('php://input');
            $data = \Nette\Utils\Json::decode($json);

            if ($data) {
                if (isset($data->repository->url)) {
                    if ($this->url == (string) $data->repository->url) {
                        return TRUE;
                    }
                }
            }
        }
        Debugger::log('Unknown access from ' . $this->request->getRemoteHost() . '(' . $remoteAddress . ')', 'deploy');
        return FALSE;
    }

}
