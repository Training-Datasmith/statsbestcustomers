<?php

class StatsBestCustomersGetDataTest extends AbstractStatsBestCustomersTestCase
{
    private $inWindow = '2020-01-15 12:00:00';
    private $outWindow = '2019-06-01 12:00:00';

    public function testSumsValidOrdersInTheWindowUsingTheOrderConversionRate()
    {
        $this->seedCustomer(1, 'Buyer', 'One', 'buyer1@example.com');
        $this->seedOrder(1, 1, '10.004000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedOrder(2, 1, '10.004000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedOrder(3, 1, '50.000000', '1.000000', $this->outWindow, $this->outWindow, 1);
        $this->seedOrder(4, 1, '99.000000', '1.000000', $this->inWindow, $this->inWindow, 0);

        $module = $this->makeModuleForGetData();
        $module->getData();

        $this->assertCount(1, $module->_values);
        $row = $module->_values[0];
        $this->assertTrue(is_numeric($row['totalMoneySpent']));
        $this->assertSame('20.01', sprintf('%.2f', $row['totalMoneySpent']));
        $this->assertSame('2', (string) $row['totalValidOrders']);
    }

    public function testOrderingCustomerWithNoVisitsIsStillListed()
    {
        $this->seedCustomer(1, 'Quiet', 'Buyer', 'quiet@example.com');
        $this->seedOrder(10, 1, '25.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->getData();

        $this->assertCount(1, $module->_values);
        $row = $module->_values[0];
        $this->assertSame('0', (string) $row['totalVisits']);
        $this->assertSame('1', (string) $row['totalValidOrders']);
    }

    public function testVisitsAreNotMultipliedByOrders()
    {
        $this->seedCustomer(1, 'Visitor', 'Two', 'visitor2@example.com');
        $this->seedOrder(20, 1, '5.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedOrder(21, 1, '5.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedGuest(100, 1);
        $this->seedConnection(100, $this->inWindow);
        $this->seedConnection(100, $this->inWindow);
        $this->seedConnection(100, $this->inWindow);
        $this->seedConnection(100, $this->outWindow);

        $module = $this->makeModuleForGetData();
        $module->getData();

        $this->assertCount(1, $module->_values);
        $this->assertSame('3', (string) $module->_values[0]['totalVisits']);
    }

    public function testCustomersWithoutAnInWindowOrderAreExcluded()
    {
        $this->seedCustomer(1, 'Control', 'Customer', 'control@example.com');
        $this->seedOrder(30, 1, '12.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $this->seedCustomer(2, 'Only', 'Visits', 'visits@example.com');
        $this->seedGuest(200, 2);
        $this->seedConnection(200, $this->inWindow);

        $this->seedCustomer(3, 'Outside', 'Order', 'outside@example.com');
        $this->seedOrder(31, 3, '15.000000', '1.000000', $this->outWindow, $this->outWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->getData();

        $ids = array();
        foreach ($module->_values as $row) {
            $ids[] = (int) $row['id_customer'];
        }
        $this->assertSame(array(1), $ids);
    }

    public function testShopRestrictionDropsOtherShops()
    {
        $this->seedCustomer(1, 'Shop', 'One', 'shop1@example.com', 1);
        $this->seedOrder(40, 1, '10.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $this->seedCustomer(2, 'Shop', 'Two', 'shop2@example.com', 2);
        $this->seedOrder(41, 2, '99.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->getData();

        $this->assertCount(1, $module->_values);
        $this->assertSame(1, (int) $module->_values[0]['id_customer']);
    }

    public function testSortsByMoneySpentDescending()
    {
        $this->seedCustomer(1, 'Low', 'Spender', 'low@example.com');
        $this->seedOrder(50, 1, '10.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $this->seedCustomer(2, 'High', 'Spender', 'high@example.com');
        $this->seedOrder(51, 2, '40.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_sort = 'totalMoneySpent';
        $module->_direction = 'DESC';
        $module->getData();

        $this->assertSame(2, (int) $module->_values[0]['id_customer']);
    }

    public function testSortsByMoneySpentAscending()
    {
        $this->seedCustomer(1, 'Low', 'Spender', 'low@example.com');
        $this->seedOrder(52, 1, '10.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $this->seedCustomer(2, 'High', 'Spender', 'high@example.com');
        $this->seedOrder(53, 2, '40.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_sort = 'totalMoneySpent';
        $module->_direction = 'ASC';
        $module->getData();

        $this->assertSame(1, (int) $module->_values[0]['id_customer']);
    }

    public function testRejectsASortThatIsNotAName()
    {
        $this->seedCustomer(1, 'Alpha', 'One', 'a1@example.com');
        $this->seedOrder(60, 1, '5.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedCustomer(2, 'Beta', 'Two', 'b2@example.com');
        $this->seedOrder(61, 2, '7.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_sort = 'bad;drop';
        $module->getData();

        $this->assertSame(2, Db::getInstance()->countCustomers());
        $this->assertCount(2, $module->_values);
        $this->assertNotContains('bad;drop', Db::getLastQuery());
    }

    public function testRejectsADirectionThatIsNotAscOrDesc()
    {
        $this->seedCustomer(1, 'Low', 'Dir', 'lowdir@example.com');
        $this->seedOrder(70, 1, '5.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedCustomer(2, 'High', 'Dir', 'highdir@example.com');
        $this->seedOrder(71, 2, '25.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_sort = 'totalMoneySpent';
        $module->_direction = 'SIDEWAYS';
        $module->getData();

        $this->assertSame(1, (int) $module->_values[0]['id_customer']);
        $this->assertNotContains('SIDEWAYS', Db::getLastQuery());
    }

    public function testLimitAndFoundRows()
    {
        $this->seedCustomer(1, 'Adams', 'A', 'a@example.com');
        $this->seedOrder(80, 1, '1.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedCustomer(2, 'Brown', 'B', 'b@example.com');
        $this->seedOrder(81, 2, '2.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedCustomer(3, 'Clark', 'C', 'c@example.com');
        $this->seedOrder(82, 3, '3.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_sort = 'lastname';
        $module->_direction = 'ASC';
        $module->_start = 0;
        $module->_limit = 1;
        $module->getData();

        $this->assertCount(1, $module->_values);
        $this->assertSame('Adams', $module->_values[0]['lastname']);
        $this->assertSame(3, $module->_totalCount);
    }

    public function testNonIntegerLimitIsNotConcatenated()
    {
        $this->seedCustomer(1, 'One', 'L', 'one@example.com');
        $this->seedOrder(90, 1, '1.000000', '1.000000', $this->inWindow, $this->inWindow, 1);
        $this->seedCustomer(2, 'Two', 'L', 'two@example.com');
        $this->seedOrder(91, 2, '2.000000', '1.000000', $this->inWindow, $this->inWindow, 1);

        $module = $this->makeModuleForGetData();
        $module->_limit = '1 OR 1';
        $module->getData();

        $this->assertCount(2, $module->_values);
        $this->assertNotContains('OR 1', Db::getLastQuery());
        $this->assertSame(2, Db::getInstance()->countCustomers());
    }

    public function testEmptyCatalogReturnsAnEmptyArrayAndZeroTotal()
    {
        $module = $this->makeModuleForGetData();
        $module->_sort = 'lastname';
        $module->_direction = 'ASC';
        $module->getData();

        $this->assertInternalType('array', $module->_values);
        $this->assertCount(0, $module->_values);
        $this->assertSame(0, $module->_totalCount);
    }

    private function seedGuest($idGuest, $idCustomer)
    {
        Db::getInstance()->insertGuest($idGuest, $idCustomer);
    }

    private function seedConnection($idGuest, $dateAdd)
    {
        Db::getInstance()->insertConnection($idGuest, $dateAdd);
    }
}
