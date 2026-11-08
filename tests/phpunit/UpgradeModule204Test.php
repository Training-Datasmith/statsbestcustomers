<?php

class UpgradeModule204Test extends PHPUnit_Framework_TestCase
{
    public function testUpgradeReturnsFalseWhenRegisterHookFails()
    {
        $probe = new UpgradeModule204Probe();
        $probe->registerHookResult = false;
        $probe->unregisterHookResult = true;

        $this->assertFalse(upgrade_module_2_0_4($probe));
        $this->assertSame('displayAdminStatsModules', $probe->lastRegisterHook);
    }

    public function testUpgradeUnregistersTheOldHookEvenWhenThatCallReturnsFalse()
    {
        $probe = new UpgradeModule204Probe();
        $probe->registerHookResult = true;
        $probe->unregisterHookResult = false;

        $this->assertTrue(upgrade_module_2_0_4($probe));
        $this->assertSame(
            array('AdminStatsModules', 'displayAdminStatsModules'),
            array($probe->lastUnregisterHook, $probe->lastRegisterHook)
        );
    }
}

class UpgradeModule204Probe
{
    public $registerHookResult = true;
    public $unregisterHookResult = true;
    public $lastRegisterHook;
    public $lastUnregisterHook;

    public function registerHook($hookName)
    {
        $this->lastRegisterHook = $hookName;

        return $this->registerHookResult;
    }

    public function unregisterHook($hookName)
    {
        $this->lastUnregisterHook = $hookName;

        return $this->unregisterHookResult;
    }
}
