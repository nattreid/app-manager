<?php

namespace NAttreid\AppManager\Routing;

use Nette\Application\Routers\Route;

/**
 * Router
 *
 * @author Attreid <attreid@gmail.com>
 */
class Router extends \NAttreid\Routing\Router {

    public function createRoutes() {
        $router = $this->getRouter();

        $router[] = new Route('deploy/', 'AppManager:Deploy:deploy');
    }

}
