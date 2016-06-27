<?php

namespace NAttreid\AppManager\Control;

use Nette\Application\Routers\Route;

/**
 * Router
 *
 * @author Attreid <attreid@gmail.com>
 */
class Router extends \NAttreid\Routers\Router {

    public function createRoutes() {
        $router = $this->getRouter();

        $router[] = new Route('deploy/', 'AppManager:Deploy:deploy');
    }

}
