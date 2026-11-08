<?php

if (!class_exists('Module')) {
    class Module
    {
        public function __construct()
        {
        }

        public $name;
        public $tab;
        public $version;
        public $author;
        public $need_instance;
        public $displayName;
        public $description;
        public $ps_versions_compliancy;

        private static $installReturns = true;
        private static $registerHookReturns = true;
        private static $recordedHooks = array();
        private static $unregisterCalls = array();
        private static $registerCalls = array();

        public static function resetStatics()
        {
            self::$installReturns = true;
            self::$registerHookReturns = true;
            self::$recordedHooks = array();
            self::$unregisterCalls = array();
            self::$registerCalls = array();
        }

        public static function setInstallReturns($value)
        {
            self::$installReturns = (bool) $value;
        }

        public static function setRegisterHookReturns($value)
        {
            self::$registerHookReturns = (bool) $value;
        }

        public static function getRecordedHooks()
        {
            return self::$recordedHooks;
        }

        public static function getUnregisterCalls()
        {
            return self::$unregisterCalls;
        }

        public static function getRegisterCalls()
        {
            return self::$registerCalls;
        }

        public function install()
        {
            return self::$installReturns;
        }

        public function registerHook($hookName)
        {
            self::$registerCalls[] = $hookName;
            if (self::$registerHookReturns) {
                self::$recordedHooks[] = $hookName;
            }

            return self::$registerHookReturns;
        }

        public function unregisterHook($hookName)
        {
            self::$unregisterCalls[] = $hookName;

            return true;
        }

        protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
        {
            $catalog = array(
                'Empty recordset returned' => 'Empty recordset returned',
                'Displaying %1$s of %2$s' => 'Displaying %1$s of %2$s',
                'Last Name' => 'Last Name',
                'First Name' => 'First Name',
                'Email' => 'Email',
                'Visits' => 'Visits',
                'Valid orders' => 'Valid orders',
                'Money spent' => 'Money spent',
                'Best customers' => 'Best customers',
                'Enrich your stats, add a list of the best customers to the dashboard.' => 'Enrich your stats, add a list of the best customers to the dashboard.',
                'Guide' => 'Guide',
                'Develop clients\' loyalty' => 'Develop clients\' loyalty',
                'Keeping a client can be more profitable than gaining a new one. That is one of the many reasons it is necessary to cultivate customer loyalty.' => 'Keeping a client can be more profitable than gaining a new one. That is one of the many reasons it is necessary to cultivate customer loyalty.',
                'Word of mouth is also a means for getting new, satisfied clients. A dissatisfied customer can hurt your e-reputation and obstruct future sales goals.' => 'Word of mouth is also a means for getting new, satisfied clients. A dissatisfied customer can hurt your e-reputation and obstruct future sales goals.',
                'In order to achieve this goal, you can organize:' => 'In order to achieve this goal, you can organize:',
                'Punctual operations: commercial rewards (personalized special offers, product or service offered), non commercial rewards (priority handling of an order or a product), pecuniary rewards (bonds, discount coupons, payback).' => 'Punctual operations: commercial rewards (personalized special offers, product or service offered), non commercial rewards (priority handling of an order or a product), pecuniary rewards (bonds, discount coupons, payback).',
                'Sustainable operations: loyalty points or cards, which not only justify communication between merchant and client, but also offer advantages to clients (private offers, discounts).' => 'Sustainable operations: loyalty points or cards, which not only justify communication between merchant and client, but also offer advantages to clients (private offers, discounts).',
                'These operations encourage clients to buy products and visit your online store more regularly.' => 'These operations encourage clients to buy products and visit your online store more regularly.',
                'CSV Export' => 'CSV Export',
            );

            $string = isset($catalog[$id]) ? $catalog[$id] : $id;

            if (empty($parameters)) {
                return $string;
            }

            return vsprintf($string, array_values($parameters));
        }
    }
}

if (!class_exists('ModuleGrid')) {
    class ModuleGrid extends Module
    {
        public $_values = array();
        public $_totalCount = 0;
        public $_employee;
        public $_sort;
        public $_direction;
        public $_start;
        public $_limit;

        public function getDate()
        {
            if (!is_object($this->_employee)) {
                throw new RuntimeException('Employee is not set on module grid');
            }

            $from = $this->_employee->stats_date_from;
            $to = $this->_employee->stats_date_to;

            return ' \'' . pSQL($from) . ' 00:00:00\' AND \'' . pSQL($to) . ' 23:59:59\' ';
        }

        protected function csvExport($datas)
        {
        }

        public function engine($params)
        {
            return '';
        }
    }
}

if (!class_exists('Shop')) {
    class Shop
    {
        const SHARE_CUSTOMER = 'share_customer';

        public static function addSqlRestriction($share = false, $alias = null)
        {
            if ($share !== self::SHARE_CUSTOMER || $alias !== 'c') {
                throw new InvalidArgumentException('Unexpected Shop::addSqlRestriction arguments');
            }

            return ' AND c.id_shop IN (1) ';
        }
    }
}

if (!class_exists('Validate')) {
    class Validate
    {
        public static function isName($name)
        {
            return is_string($name) && preg_match('/^[^0-9!<>,;?=+()@#"°{}_$%:¤|]*$/u', $name);
        }

        public static function isSortDirection($value)
        {
            return $value !== null && ($value === 'ASC' || $value === 'DESC');
        }

        public static function isUnsignedInt($value)
        {
            return (is_numeric($value) || is_string($value))
                && (string) (int) $value === (string) $value
                && $value < 4294967296
                && $value >= 0;
        }
    }
}

if (!class_exists('Tools')) {
    class Tools
    {
        public static function getValue($key, $default = false)
        {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }

            return $default;
        }

        public static function safeOutput($string)
        {
            return htmlspecialchars(strip_tags((string) $string), ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!class_exists('Configuration')) {
    class Configuration
    {
        public static function get($key)
        {
            if ($key === 'PS_CURRENCY_DEFAULT') {
                return '5';
            }

            return null;
        }
    }
}

if (!class_exists('Currency')) {
    class Currency
    {
        public static $lastConstructorId = null;
        public $iso_code = 'EUR';

        public function __construct($id = null)
        {
            self::$lastConstructorId = $id;
        }

        public static function resetStatics()
        {
            self::$lastConstructorId = null;
        }
    }
}

if (!class_exists('Db')) {
    class Db
    {
        /** @var PDO */
        private static $pdo;

        /** @var array */
        private static $queries = array();

        /** @var int|null */
        private static $lastFoundRows = null;

        public static function resetStatics()
        {
            self::$queries = array();
            self::$lastFoundRows = null;
        }

        public static function getRecordedQueries()
        {
            return self::$queries;
        }

        public static function getLastQuery()
        {
            $count = count(self::$queries);

            return $count ? self::$queries[$count - 1] : '';
        }

        public static function getInstance($slave = false)
        {
            if (!self::$pdo) {
                self::connect();
                self::createSchema();
            }

            return new self();
        }

        public static function connect()
        {
            $host = getenv('SBC_DB_HOST') ? getenv('SBC_DB_HOST') : '127.0.0.1';
            $name = getenv('SBC_DB_NAME') ? getenv('SBC_DB_NAME') : 'statsbestcustomers';
            $user = getenv('SBC_DB_USER') ? getenv('SBC_DB_USER') : 'sbc';
            $pass = getenv('SBC_DB_PASSWORD') ? getenv('SBC_DB_PASSWORD') : 'sbc';

            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8';
            self::$pdo = new PDO($dsn, $user, $pass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ));
        }

        public static function createSchema()
        {
            $prefix = _DB_PREFIX_;
            self::$pdo->exec(
                'CREATE TABLE IF NOT EXISTS `' . $prefix . 'customer` (
                    `id_customer` INT UNSIGNED NOT NULL,
                    `lastname` VARCHAR(64) NOT NULL,
                    `firstname` VARCHAR(64) NOT NULL,
                    `email` VARCHAR(128) NOT NULL,
                    `id_shop` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`id_customer`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            );
            self::$pdo->exec(
                'CREATE TABLE IF NOT EXISTS `' . $prefix . 'orders` (
                    `id_order` INT UNSIGNED NOT NULL,
                    `id_customer` INT UNSIGNED NOT NULL,
                    `total_paid_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
                    `conversion_rate` DECIMAL(13,6) NOT NULL DEFAULT 1.000000,
                    `invoice_date` DATETIME NOT NULL,
                    `date_add` DATETIME NOT NULL,
                    `valid` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            );
            self::$pdo->exec(
                'CREATE TABLE IF NOT EXISTS `' . $prefix . 'guest` (
                    `id_guest` INT UNSIGNED NOT NULL,
                    `id_customer` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`id_guest`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            );
            self::$pdo->exec(
                'CREATE TABLE IF NOT EXISTS `' . $prefix . 'connections` (
                    `id_connections` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_guest` INT UNSIGNED NOT NULL,
                    `date_add` DATETIME NOT NULL,
                    PRIMARY KEY (`id_connections`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            );
        }

        public static function truncateAll()
        {
            if (!self::$pdo) {
                self::connect();
                self::createSchema();
            }

            $prefix = _DB_PREFIX_;
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            self::$pdo->exec('TRUNCATE TABLE `' . $prefix . 'connections`');
            self::$pdo->exec('TRUNCATE TABLE `' . $prefix . 'guest`');
            self::$pdo->exec('TRUNCATE TABLE `' . $prefix . 'orders`');
            self::$pdo->exec('TRUNCATE TABLE `' . $prefix . 'customer`');
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            self::$queries = array();
            self::$lastFoundRows = null;
        }

        public function executeS($sql)
        {
            self::$queries[] = $sql;
            $statement = self::$pdo->query($sql);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (stripos($sql, 'SQL_CALC_FOUND_ROWS') !== false) {
                self::$lastFoundRows = (int) self::$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
            }

            return $rows;
        }

        public function getValue($sql)
        {
            self::$queries[] = $sql;
            if (stripos($sql, 'FOUND_ROWS()') !== false) {
                if (self::$lastFoundRows === null) {
                    $statement = self::$pdo->query('SELECT FOUND_ROWS()');

                    return (string) $statement->fetchColumn();
                }

                return (string) self::$lastFoundRows;
            }

            $statement = self::$pdo->query($sql);
            $row = $statement->fetch(PDO::FETCH_NUM);

            return $row ? $row[0] : false;
        }

        public function insertCustomer($id, $lastname, $firstname, $email, $idShop)
        {
            $prefix = _DB_PREFIX_;
            $sql = 'INSERT INTO `' . $prefix . 'customer`
                (`id_customer`, `lastname`, `firstname`, `email`, `id_shop`)
                VALUES (' . (int) $id . ', ' . self::$pdo->quote($lastname) . ', ' . self::$pdo->quote($firstname) . ', '
                . self::$pdo->quote($email) . ', ' . (int) $idShop . ')';
            self::$pdo->exec($sql);
        }

        public function insertOrder($idOrder, $idCustomer, $total, $rate, $invoiceDate, $dateAdd, $valid)
        {
            $prefix = _DB_PREFIX_;
            $sql = 'INSERT INTO `' . $prefix . 'orders`
                (`id_order`, `id_customer`, `total_paid_tax_incl`, `conversion_rate`, `invoice_date`, `date_add`, `valid`)
                VALUES (' . (int) $idOrder . ', ' . (int) $idCustomer . ', ' . $total . ', ' . $rate . ', '
                . self::$pdo->quote($invoiceDate) . ', ' . self::$pdo->quote($dateAdd) . ', ' . (int) $valid . ')';
            self::$pdo->exec($sql);
        }

        public function insertGuest($idGuest, $idCustomer)
        {
            $prefix = _DB_PREFIX_;
            self::$pdo->exec(
                'INSERT INTO `' . $prefix . 'guest` (`id_guest`, `id_customer`) VALUES ('
                . (int) $idGuest . ', ' . (int) $idCustomer . ')'
            );
        }

        public function insertConnection($idGuest, $dateAdd)
        {
            $prefix = _DB_PREFIX_;
            self::$pdo->exec(
                'INSERT INTO `' . $prefix . 'connections` (`id_guest`, `date_add`) VALUES ('
                . (int) $idGuest . ', ' . self::$pdo->quote($dateAdd) . ')'
            );
        }

        public function countCustomers()
        {
            $prefix = _DB_PREFIX_;

            return (int) self::$pdo->query('SELECT COUNT(*) FROM `' . $prefix . 'customer`')->fetchColumn();
        }
    }
}

if (!function_exists('pSQL')) {
    function pSQL($string)
    {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $string);
    }
}

if (!function_exists('bqSQL')) {
    function bqSQL($string)
    {
        return str_replace('`', '', $string);
    }
}
