<?php

declare(strict_types=1);

namespace NAttreid\AppManager\DI;

use NAttreid\AppManager\AppManager;
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
		'backupDir' => [],
		'maxRows' => null,
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
		foreach ($config['backupDir'] as $key => $dir) {
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
			->setArguments([$config['maxRows']]);

		$builder->addDefinition($this->prefix('logs'))
			->setType(Logs::class)
			->setArguments([$config['logDir']]);

		$builder->addDefinition($this->prefix('backup'))
			->setType(Backup::class)
			->setArguments([$config['backupDir']]);

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

		$builder->getDefinition('application.presenterFactory')
			->addSetup('setMapping', [
				['AppManager' => 'NAttreid\AppManager\Control\*Presenter']
			]);
	}

}
