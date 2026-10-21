<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SP\Core\Exceptions\SPException;
use SP\Storage\Database\DatabaseConnectionData;

/**
 * Class DatabaseBaseTest
 *
 * Caso de test para tests que requieran consultas a la BBDD
 *
 * @package SP\Tests
 */
abstract class DatabaseTestCase extends TestCase
{
    /**
     * @var DatabaseConnectionData
     */
    protected static $databaseConnectionData;
    /**
     * @var string
     */
    protected static $dataset = 'syspass.xml';
    /**
     * @var PDO
     */
    private static $pdo;
    /**
     * @var FixtureConnection
     */
    protected $conn;

    /**
     * Loads the dataset before each test, replacing the PHPUnit DbUnit trait.
     *
     * @throws SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadDataSet($this->getDataSetFile());
    }

    /**
     * Returns the test database connection.
     *
     * @return FixtureConnection
     * @throws SPException
     */
    final public function getConnection(): FixtureConnection
    {
        if ($this->conn === null) {
            if (self::$pdo === null) {
                self::$pdo = getDbHandler()->getConnection();
            }

            $this->conn = new FixtureConnection(self::$pdo);
        }

        return $this->conn;
    }

    /**
     * @return string
     */
    protected function getDataSetFile(): string
    {
        return RESOURCE_DIR . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . self::$dataset;
    }

    /**
     * @param string $file
     *
     * @throws SPException
     */
    private function loadDataSet(string $file)
    {
        $this->getConnection();

        $data = simplexml_load_file($file);

        if ($data === false) {
            static::fail(sprintf('Unable to load dataset "%s"', $file));
        }

        $data->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($data->database->table_data as $table) {
                $tableName = (string)$table['name'];

                self::$pdo->exec(sprintf('TRUNCATE TABLE `%s`', $tableName));

                foreach ($table->row as $row) {
                    $columns = [];
                    $values = [];
                    $binary = [];
                    $null = [];

                    foreach ($row->field as $field) {
                        $attributes = $field->attributes('http://www.w3.org/2001/XMLSchema-instance');

                        $columns[] = sprintf('`%s`', (string)$field['name']);
                        $values[] = (string)$field;
                        $binary[] = (string)$attributes['type'] === 'xs:hexBinary';
                        $null[] = in_array(strtolower((string)$attributes['nil']), ['true', '1'], true);
                    }

                    if (empty($columns)) {
                        continue;
                    }

                    $statement = self::$pdo->prepare(sprintf(
                        'INSERT INTO `%s` (%s) VALUES (%s)',
                        $tableName,
                        implode(', ', $columns),
                        implode(', ', array_fill(0, count($columns), '?'))
                    ));

                    foreach ($values as $index => $value) {
                        if ($null[$index]) {
                            $statement->bindValue($index + 1, null, PDO::PARAM_NULL);
                        } else {
                            $statement->bindValue(
                                $index + 1,
                                $binary[$index] ? pack('H*', $value) : $value,
                                $binary[$index] ? PDO::PARAM_LOB : PDO::PARAM_STR
                            );
                        }
                    }

                    $statement->execute();
                }
            }
        } finally {
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}