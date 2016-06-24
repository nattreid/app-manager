<?php

namespace NAttreid\AppManager\Control;

/**
 * Deploy presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
class DeployPresenter extends \Nette\Application\UI\Presenter {

    /** @var \NAttreid\AppManager\AppManager @inject */
    public $app;

    /**
     * Hook pro deploy z gitlabu
     */
    public function actionDeploy() {
        $this->app->gitPull();
        $this->app->composerUpdate();
        $this->terminate();
    }

}
