<?php

$maintenance = isset($maintenance) ? $maintenance : __DIR__ . '/../temp/maintenance';
if (file_exists($maintenance)) {
    if ((isset($_GET['maintenanceOff']) && $_GET['maintenanceOff'] == TRUE) || (isset($argv) && $argv[1] == 'maintenanceOff')) {
        unlink($maintenance);
        echo "Maintenance off\n";
        exit;
    }
    require '.maintenance.php';
}