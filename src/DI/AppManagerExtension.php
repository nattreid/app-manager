<?php

declare(strict_types=1);

namespace NAttreid\AppManager\DI;

use NAttreid\AppManager\AppManager;
use NAttreid\AppManager\Control\DeployPresenter;
use NAttreid\AppManager\Helpers\Backup;
use NAttreid\AppManager\Helpers\Database\SQL;
use NAttreid\AppManager\Helpers\Deploy\Composer;
use NAttreid\AppManager\Helpers\Deploy\Git;
use NAttreid\AppManager\Helpers\Files;
use NAttreid\AppManager\Helpers\Info;
use NAttreid\AppManager\Helpers\Logs;
use NAttreid\AppManager\Routing\Router;
use NAttreid\Routing\RouterFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceDefinition;

/**
 * Rozsireni
 *
 * @author Attreid <attreid@gmail.com>
 */
class AppManagerExtension extends CompilerExtension
{

	private $defaults = [
		'deploy' => [
			'projectUrl' => null,
			'type' => null,
			'secretToken' => null
		],
		'backup' => [
			'dir' => [],
			'excludeTables' => [],
			'maxRows' => null,
		],
		'appDir' => '%appDir%',
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%',
		'logDir' => '%logDir%',
		'sessionDir' => '%sessionDir%',
		'sessionExpiration' => '14 days',
	];

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$config['appDir'] = Helpers::expand($config['appDir'], $builder->parameters);
		$config['wwwDir'] = Helpers::expand($config['wwwDir'], $builder->parameters);
		$config['tempDir'] = Helpers::expand($config['tempDir'], $builder->parameters);
		$config['logDir'] = Helpers::expand($config['logDir'], $builder->parameters);
		$config['sessionDir'] = Helpers::expand($config['sessionDir'], $builder->parameters);
		foreach ($config['backup']['dir'] as $key => $dir) {
			$config['backupDir'][$key] = Helpers::expand($dir, $builder->parameters);
		}

		$builder->addDefinition($this->prefix('composer'))
			->setType(Composer::class)
			->setArguments([$config['appDir'], $config['tempDir'], $config['deploy']]);
		$builder->addDefinition($this->prefix('gitlab'))
			->setType(Git::class)
			->setArguments([$config['appDir'], $config['deploy']]);

		$builder->addDefinition($this->prefix('appManager'))
			->setType(AppManager::class)
			->setArguments([$config['tempDir']]);

		$builder->addDefinition($this->prefix('files'))
			->setType(Files::class)
			->setArguments([$config['appDir'], $config['wwwDir'], $config['tempDir'], $config['logDir'], $config['sessionDir'], $config['sessionExpiration']]);

		$builder->addDefinition($this->prefix('info'))
			->setType(Info::class);

		$builder->addDefinition($this->prefix('sql'))
			->setType(SQL::class)
			->setArguments([$config['backup']['maxRows'], $config['backup']['excludeTables']]);

		$builder->addDefinition($this->prefix('logs'))
			->setType(Logs::class)
			->setArguments([$config['logDir']]);

		$builder->addDefinition($this->prefix('backup'))
			->setType(Backup::class)
			->setArguments([$config['backup']['dir']]);

		$builder->addDefinition($this->prefix('router'))
			->setType(Router::class);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$router = $builder->getByType(RouterFactory::class);
		try {
			$builder->getDefinition($router)
				->addSetup('addRouter', ['@' . $this->prefix('router'), RouterFactory::PRIORITY_SYSTEM]);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/routing'");
		}

		$appManager = $builder->getByType(AppManager::class);
		foreach ($this->findByType(DeployPresenter::class) as $def) {
			$def->addSetup('setAppManager', [$builder->getDefinition($appManager)]);
		}

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				['AppManager' => 'NAttreid\AppManager\Control\*Presenter']
			]);
	}

	/**
	 *
	 * @param string $type
	 * @return ServiceDefinition[]
	 */
	private function findByType(string $type): array
	{
		$type = ltrim($type, '\\');
		return array_filter($this->getContainerBuilder()->getDefinitions(), function (ServiceDefinition $def) use ($type) {
			return is_a($def->getType(), $type, true) || is_a($def->getImplement(), $type, true);
		});
	}

}
