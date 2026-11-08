<?php
/**
 * PHPUnit bootstrap for statsbestcustomers (PrestaShop module stubs + module load).
 */

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

define('_PS_VERSION_', '1.7.8.11');
define('_DB_PREFIX_', getenv('SBC_DB_PREFIX') ? getenv('SBC_DB_PREFIX') : 'ps_');
define('_PS_USE_SQL_SLAVE_', false);
define('_PS_ROOT_DIR_', dirname(dirname(dirname(__FILE__))));

$stubFile = dirname(__FILE__) . '/stubs/PrestaShopStubs.php';
$moduleFile = dirname(dirname(dirname(__FILE__))) . '/statsbestcustomers.php';
$upgradeFile = dirname(dirname(dirname(__FILE__))) . '/upgrade/upgrade-2.0.4.php';

foreach (array($stubFile, $moduleFile, $upgradeFile) as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing required file: {$path}\n");
        exit(1);
    }
}

require_once $stubFile;
require_once $moduleFile;
require_once $upgradeFile;
require_once dirname(__FILE__) . '/AbstractStatsBestCustomersTestCase.php';
require_once dirname(__FILE__) . '/StatsBestCustomersTestable.php';
require_once dirname(__FILE__) . '/ModuleTestSuite.php';
