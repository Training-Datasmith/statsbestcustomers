<?php

abstract class AbstractStatsBestCustomersTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Db::truncateAll();
        Module::resetStatics();
        Currency::resetStatics();
        Db::resetStatics();
        $_GET = array();
        $_POST = array();
        unset($_SERVER['REQUEST_URI']);
    }

    protected function makeEmployee($from = '2020-01-01', $to = '2020-01-31')
    {
        $employee = new stdClass();
        $employee->stats_date_from = $from;
        $employee->stats_date_to = $to;

        return $employee;
    }

    protected function makeModuleForGetData($from = '2020-01-01', $to = '2020-01-31')
    {
        $module = new statsbestcustomers();
        $module->_employee = $this->makeEmployee($from, $to);
        $module->_sort = 'totalMoneySpent';
        $module->_direction = 'DESC';
        $module->_start = 0;
        $module->_limit = 50;

        return $module;
    }

    protected function seedCustomer($id, $lastname, $firstname, $email, $idShop = 1)
    {
        Db::getInstance()->insertCustomer($id, $lastname, $firstname, $email, $idShop);
    }

    protected function seedOrder($idOrder, $idCustomer, $total, $rate, $invoiceDate, $dateAdd, $valid)
    {
        Db::getInstance()->insertOrder($idOrder, $idCustomer, $total, $rate, $invoiceDate, $dateAdd, $valid);
    }
}
