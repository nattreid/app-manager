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
Přidejte do **index.php**. Soubor **.maintenance.php** se zobrazí pouze když bude údžba zapnutá. Vypnout se dá přidáním parametru do url **maintenanceOff=1**
```php
$maintenance = isset($maintenance) ? $maintenance : __DIR__ . '/../temp/maintenance';
if (file_exists($maintenance)) {
    if ((isset($_GET['maintenanceOff']) && $_GET['maintenanceOff'] == TRUE) || (isset($argv) && $argv[1] == 'maintenanceOff')) {
        unlink($maintenance);
        echo "Maintenance off\n";
        exit;
    }
    require '.maintenance.php';
}
```