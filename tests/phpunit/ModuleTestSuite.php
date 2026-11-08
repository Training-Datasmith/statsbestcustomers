<?php

class ModuleTestSuite
{
    public static function defaultFiles()
    {
        $base = dirname(__FILE__);

        return array(
            $base . '/DirectoryGuardTest.php',
            $base . '/StatsBestCustomersInstallTest.php',
            $base . '/StatsBestCustomersMetadataTest.php',
            $base . '/StatsBestCustomersHookTest.php',
            $base . '/StatsBestCustomersGetDataTest.php',
            $base . '/UpgradeModule204Test.php',
        );
    }

    public static function suite()
    {
        $files = self::defaultFiles();
        $order = getenv('SBC_TEST_FILE_ORDER');
        if ($order !== false && $order !== '') {
            $files = explode(':', $order);
        }

        $suite = new PHPUnit_Framework_TestSuite('module-tests');
        foreach ($files as $file) {
            if (!is_file($file)) {
                throw new RuntimeException('Missing test file: ' . $file);
            }
            $suite->addTestFile($file);
        }

        return $suite;
    }
}
