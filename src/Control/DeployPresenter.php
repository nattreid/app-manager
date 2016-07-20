<?php

namespace NAttreid\AppManager\Control;

use NAttreid\AppManager\AppManager;

/**
 * Deploy presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
class DeployPresenter extends \Nette\Application\UI\Presenter {

    /** @var AppManager */
    private $app;

    public function __construct(AppManager $app) {
        parent::__construct();
        $this->app = $app;
    }

    /**
     * Hook pro deploy z gitlabu
     */
    public function actionDeploy() {
        $this->app->gitPull();
        $this->app->composerUpdate();
        $this->terminate();
    }

}
