<?php

class StatsBestCustomersHookTest extends AbstractStatsBestCustomersTestCase
{
    protected function setUp()
    {
        parent::setUp();
        StatsBestCustomersTestable::resetRecording();
    }

    public function testHookRendersGuideAndPassesGridParams()
    {
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=statsbestcustomers';
        $module = new StatsBestCustomersTestable();
        $html = $module->hookDisplayAdminStatsModules(array());

        $this->assertInternalType('string', $html);
        $this->assertContains('Best customers', $html);
        $this->assertContains('Guide', $html);
        $this->assertContains('clients\' loyalty', $html);
        $this->assertContains(StatsBestCustomersTestable::GRID_ENGINE_SENTINEL, $html);
        $this->assertContains('export=1', $html);
        $this->assertContains('controller=AdminStats&amp;module=statsbestcustomers', $html);
        $this->assertCount(0, StatsBestCustomersTestable::$csvExportCalls);

        $this->assertCount(1, StatsBestCustomersTestable::$engineCalls);
        $params = StatsBestCustomersTestable::$engineCalls[0];
        $this->assertSame('id_customer', $params['id']);
        $this->assertSame('totalMoneySpent', $params['defaultSortColumn']);
        $this->assertSame('DESC', $params['defaultSortDirection']);
        $this->assertSame('Empty recordset returned', $params['emptyMessage']);
        $this->assertSame('Displaying {0} - {1} of {2}', $params['pagingMessage']);
        $columnIds = array();
        foreach ($params['columns'] as $column) {
            $columnIds[] = $column['id'];
        }
        $this->assertSame(
            array('lastname', 'firstname', 'email', 'totalVisits', 'totalValidOrders', 'totalMoneySpent'),
            $columnIds
        );
    }

    public function testHookEscapesRequestUriInTheExportLink()
    {
        $_SERVER['REQUEST_URI'] = '/admin/stats?q=<script>';
        $module = new StatsBestCustomersTestable();
        $html = $module->hookDisplayAdminStatsModules(array());
        $this->assertNotContains('<script', $html);
        $this->assertContains('export=1', $html);
    }

    public function testExportInvokesCsvExportWithTheGridParams()
    {
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=statsbestcustomers';
        $_GET['export'] = '1';
        $module = new StatsBestCustomersTestable();
        $module->hookDisplayAdminStatsModules(array());
        $this->assertCount(1, StatsBestCustomersTestable::$csvExportCalls);
        $params = StatsBestCustomersTestable::$csvExportCalls[0];
        $this->assertSame('id_customer', $params['id']);
        $this->assertSame('totalMoneySpent', $params['defaultSortColumn']);
    }

    public function testEmptyExportValueDoesNotExport()
    {
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=statsbestcustomers';
        $_GET['export'] = '';
        $module = new StatsBestCustomersTestable();
        $html = $module->hookDisplayAdminStatsModules(array());
        $this->assertCount(0, StatsBestCustomersTestable::$csvExportCalls);
        $this->assertContains(StatsBestCustomersTestable::GRID_ENGINE_SENTINEL, $html);
    }
}
