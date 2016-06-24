# Správa aplikace pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    appManager: NAtrreid\AppManager\DI\Extension
```

dostupné nastavení
```neon
appManager:
    deploy:
        projectUrl: 'gitAdresaProjektu'
        ip: 'ipGitlabu'
        appDir: %appDir%
        wwwDir: %wwwDir%
        tempDir: %tempDir%
        logDir: %logDir%
        sessionDir: %sessionDir%
        sessionExpiration: '14 days'
```

## Údržba stránek
