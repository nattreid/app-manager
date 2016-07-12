# Správa aplikace pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    appManager: NAtrreid\AppManager\DI\AppManagerExtension
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

použití
```php
/** @var \NAttreid\AppManager\AppManager @inject */
public $app;
```

## Údržba stránek
Přidejte do **index.php**. Soubor **.maintenance.php** se zobrazí pouze když bude údžba zapnutá. Vypnout se dá přidáním parametru do url **maintenanceOff** nebo pres konzoli *php index.php maintenanceOff*
```php
$maintenance = isset($maintenance) ? $maintenance : __DIR__ . '/../temp/maintenance';
if (file_exists($maintenance)) {
    if (isset($_GET['maintenanceOff']) || (isset($argv) && $argv[1] == 'maintenanceOff')) {
        unlink($maintenance);
        echo "Maintenance off\n";
        exit;
    }
    require '.maintenance.php';
}
```

## Invalidace cache
Pro invalidaci pomocí metody je třeba přidat
```php
$app->onInvalideCache[]=function(){
    $this->cache->clean();
};
```