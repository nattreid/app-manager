<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Control;

use NAttreid\AppManager\AppManager;
use Nette\Application\UI\Presenter;

/**
 * Deploy presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
class DeployPresenter extends Presenter
{

	/** @var AppManager */
	private $app;

	public function __construct(AppManager $app)
	{
		parent::__construct();
		$this->app = $app;
	}

	/**
	 * Hook pro deploy z gitlabu
	 */
	public function actionDeploy(): void
	{
		$this->app->gitPull();
		$this->app->composerUpdate();
		$this->terminate();
	}

}
