<?php

namespace NAttreid\AppManager\DI;

/**
 * Rozsireni
 * 
 * @author Attreid <attreid@gmail.com>
 */
class Extension extends \Nette\DI\CompilerExtension {

    private $defaults = [
        'deploy' => [
            'projectUrl' => NULL,
            'ip' => NULL
        ],
        'appDir' => '%appDir%',
        'wwwDir' => '%wwwDir%',
        'tempDir' => '%tempDir%',
        'logDir' => '%logDir%',
        'sessionDir' => '%sessionDir%',
        'sessionExpiration' => '14 days',
    ];

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults, $this->getConfig());

        $config['appDir'] = \Nette\DI\Helpers::expand($config['appDir'], $this->getContainerBuilder()->parameters);
        $config['wwwDir'] = \Nette\DI\Helpers::expand($config['wwwDir'], $this->getContainerBuilder()->parameters);
        $config['tempDir'] = \Nette\DI\Helpers::expand($config['tempDir'], $this->getContainerBuilder()->parameters);
        $config['logDir'] = \Nette\DI\Helpers::expand($config['logDir'], $this->getContainerBuilder()->parameters);
        $config['sessionDir'] = \Nette\DI\Helpers::expand($config['sessionDir'], $this->getContainerBuilder()->parameters);

        $deploy = $config['deploy'];
        $builder->addDefinition($this->prefix('composer'))
                ->setClass('NAttreid\AppManager\Deploy\Composer')
                ->setArguments([$config['appDir'], $config['tempDir'], $deploy['projectUrl'], $deploy['ip']]);
        $builder->addDefinition($this->prefix('gitlab'))
                ->setClass('NAttreid\AppManager\Deploy\Gitlab')
                ->setArguments([ $config['appDir'], $deploy['projectUrl'], $deploy['ip']]);

        $builder->addDefinition($this->prefix('appManager'))
                ->setClass('NAttreid\AppManager\AppManager')
                ->setArguments([ $config['appDir'], $config['wwwDir'], $config['tempDir'], $config['logDir'], $config['sessionDir'], $config['sessionExpiration']]);

        $builder->addDefinition($this->prefix('info'))
                ->setClass('NAttreid\AppManager\Info');

        $builder->addDefinition($this->prefix('logs'))
                ->setClass('NAttreid\AppManager\Logs')
                ->setArguments([ $config['logDir']]);

        $builder->addDefinition($this->prefix('router'))
                ->setClass('NAttreid\AppManager\Control\DeployRouter');
    }

    public function beforeCompile() {
        $builder = $this->getContainerBuilder();
        $router = $builder->getByType('NAttreid\Routers\RouterFactory');
        $builder->getDefinition($router)
                ->addSetup('addRouter', ['@' . $this->prefix('router'), 0]);

        $builder->getDefinition('presenterFactory')
                ->addSetup('setMapping', array(
                    array('AppManager' => 'NAttreid\AppManager\Control\*Presenter')
        ));
    }

}
