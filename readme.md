# Správa aplikace pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    appManager: NAtrreid\AppManager\DI\AppManagerExtension
```

Dostupné nastavení
```neon
appManager:
    deploy:
        projectUrl: 'homepageProjektu'
        secretToken: 'tajnyTokenProjektu' # pro github a gitlab
        type: 'github' # github, gitlab, bitbucket
    sessionExpiration: '14 days'
    maxRows: 1000 # nebo null pro zadne omezeni insert
    backupDir:
        - %wwwDir%/zalohovanyAdresar
```
Payload webhooks musí být **JSON**

## Použití
```php
/** @var \NAttreid\AppManager\AppManager @inject */
public $app;
```

### Údržba stránek
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

### Invalidace cache
Pro invalidaci pomocí metody je třeba přidat
```php
$app->onInvalideCache[]=function(){
    $this->cache->clean();
};
```