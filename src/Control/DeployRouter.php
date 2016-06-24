<?php

namespace NAttreid\AppManager\Control;

/**
 * Deploy router
 *
 * @author Attreid <attreid@gmail.com>
 */
class DeployRouter extends \NAttreid\Routers\Router {

    public function createRoutes() {
        $router = $this->getRouter();

        $router[] = new Route('deploy/', 'Application:deploy');
    }

}
