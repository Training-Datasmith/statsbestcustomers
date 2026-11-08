<?php

class StatsBestCustomersMetadataTest extends AbstractStatsBestCustomersTestCase
{
    public function testConstructorSetsIdentityColumnsAndCurrencyId()
    {
        $module = new statsbestcustomers();
        $this->assertSame('statsbestcustomers', get_class($module));
        $this->assertSame('statsbestcustomers', $module->name);
        $this->assertSame('administration', $module->tab);
        $this->assertSame('2.1.0', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertSame('1.7.1.0', $module->ps_versions_compliancy['min']);
        $this->assertSame('1.7.8.11', $module->ps_versions_compliancy['max']);
        $this->assertSame(5, Currency::$lastConstructorId);

        $reflection = new ReflectionObject($module);
        $columnsProp = $reflection->getProperty('columns');
        $columnsProp->setAccessible(true);
        $columns = $columnsProp->getValue($module);

        $expectedIds = array(
            'lastname',
            'firstname',
            'email',
            'totalVisits',
            'totalValidOrders',
            'totalMoneySpent',
        );
        $this->assertCount(6, $columns);
        foreach ($columns as $index => $column) {
            $this->assertSame($expectedIds[$index], $column['id']);
            $this->assertSame($expectedIds[$index], $column['dataIndex']);
            $this->assertSame('center', $column['align']);
        }

        $moneyHeader = $columns[5]['header'];
        $this->assertContains('(EUR)', $moneyHeader);
        $this->assertSame('Best customers', $module->displayName);
    }

    public function testConfigXmlNameAndVersionMatchTheClass()
    {
        $module = new statsbestcustomers();
        $xmlPath = dirname(dirname(dirname(__FILE__))) . '/config.xml';
        $xml = simplexml_load_file($xmlPath);
        $this->assertSame('statsbestcustomers', (string) $xml->name);
        $this->assertSame($module->version, (string) $xml->version);
    }
}
