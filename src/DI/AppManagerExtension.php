<?php

declare(strict_types=1);

namespace NAttreid\AppManager\DI;

use NAttreid\AppManager\AppManager;
use NAttreid\AppManager\Helpers\Backup;
use NAttreid\AppManager\Helpers\Database\SQL;
use NAttreid\AppManager\Helpers\Deploy\Composer;
use NAttreid\AppManager\Helpers\Deploy\Gitlab;
use NAttreid\AppManager\Helpers\Files;
use NAttreid\AppManager\Helpers\Info;
use NAttreid\AppManager\Helpers\Logs;
use NAttreid\AppManager\Routing\Router;
use NAttreid\Routing\RouterFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\MissingServiceException;

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
			'ip' => null
		],
		'backupDir' => [],
		'appDir' => '%appDir%',
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%',
		'logDir' => '%logDir%',
		'sessionDir' => '%sessionDir%',
		'sessionExpiration' => '14 days',
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$config['appDir'] = Helpers::expand($config['appDir'], $builder->parameters);
		$config['wwwDir'] = Helpers::expand($config['wwwDir'], $builder->parameters);
		$config['tempDir'] = Helpers::expand($config['tempDir'], $builder->parameters);
		$config['logDir'] = Helpers::expand($config['logDir'], $builder->parameters);
		$config['sessionDir'] = Helpers::expand($config['sessionDir'], $builder->parameters);
		foreach ($config['backupDir'] as $key => $dir) {
			$config['backupDir'][$key] = Helpers::expand($dir, $builder->parameters);
		}

		$deploy = $config['deploy'];
		$builder->addDefinition($this->prefix('composer'))
			->setClass(Composer::class)
			->setArguments([$config['appDir'], $config['tempDir'], $deploy['projectUrl'], $deploy['ip']]);
		$builder->addDefinition($this->prefix('gitlab'))
			->setClass(Gitlab::class)
			->setArguments([$config['appDir'], $deploy['projectUrl'], $deploy['ip']]);

		$builder->addDefinition($this->prefix('appManager'))
			->setClass(AppManager::class)
			->setArguments([$config['tempDir']]);

		$builder->addDefinition($this->prefix('files'))
			->setClass(Files::class)
			->setArguments([$config['appDir'], $config['wwwDir'], $config['tempDir'], $config['logDir'], $config['sessionDir'], $config['sessionExpiration']]);

		$builder->addDefinition($this->prefix('info'))
			->setClass(Info::class);

		$builder->addDefinition($this->prefix('sql'))
			->setClass(SQL::class);

		$builder->addDefinition($this->prefix('logs'))
			->setClass(Logs::class)
			->setArguments([$config['logDir']]);

		$builder->addDefinition($this->prefix('backup'))
			->setClass(Backup::class)
			->setArguments([$config['backupDir']]);

		$builder->addDefinition($this->prefix('router'))
			->setClass(Router::class);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$router = $builder->getByType(RouterFactory::class);
		try {
			$builder->getDefinition($router)
				->addSetup('addRouter', ['@' . $this->prefix('router'), RouterFactory::PRIORITY_SYSTEM]);
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'nattreid/routing'");
		}

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				['AppManager' => 'NAttreid\AppManager\Control\*Presenter']
			]);
	}

}
