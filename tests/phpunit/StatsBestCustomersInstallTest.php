<?php

class StatsBestCustomersInstallTest extends AbstractStatsBestCustomersTestCase
{
    public function testInstallRegistersDisplayAdminStatsModulesHook()
    {
        Module::setInstallReturns(true);
        Module::setRegisterHookReturns(true);
        $module = new statsbestcustomers();
        $this->assertTrue($module->install());
        $this->assertSame(array('displayAdminStatsModules'), Module::getRecordedHooks());
    }

    public function testInstallReturnsFalseWhenParentInstallFails()
    {
        Module::setInstallReturns(false);
        Module::setRegisterHookReturns(true);
        $module = new statsbestcustomers();
        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::getRecordedHooks());
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        Module::setInstallReturns(true);
        Module::setRegisterHookReturns(false);
        $module = new statsbestcustomers();
        $this->assertFalse($module->install());
    }
}
