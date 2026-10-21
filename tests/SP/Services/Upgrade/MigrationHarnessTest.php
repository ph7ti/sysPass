<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Upgrade;

use PHPUnit\Framework\TestCase;
use SP\Services\Upgrade\MigrationHarness;
use SP\Services\Upgrade\UpgradeException;

/**
 * @package SP\Tests\Services\Upgrade
 */
class MigrationHarnessTest extends TestCase
{
    public function testGetPending()
    {
        $harness = new MigrationHarness();

        $this->assertEmpty($harness->getPending('310.19042701'));
        $this->assertNotEmpty($harness->getPending('300.18010101'));
    }

    public function testDryRunDoesNotTargetMissingFiles()
    {
        $harness = new MigrationHarness();

        $plan = $harness->dryRun('310.19042701');

        $this->assertSame('310.19042701', $plan['version']);
        $this->assertNotEmpty($plan['queries']);
        $this->assertNull($plan['rollback']);
    }

    public function testMissingRollbackThrows()
    {
        $this->expectException(UpgradeException::class);

        (new MigrationHarness())->getRollbackStatements('310.19042701');
    }

    public function testRollbackStatementsAreParsed()
    {
        $sqlPath = TMP_PATH . DIRECTORY_SEPARATOR . 'migrations';
        $rollbackPath = $sqlPath . DIRECTORY_SEPARATOR . 'rollback';

        mkdir($sqlPath, 0777, true);
        mkdir($rollbackPath, 0777, true);

        file_put_contents($sqlPath . DIRECTORY_SEPARATOR . '99900000000.sql', 'CREATE TABLE T (id int) $$');
        file_put_contents($rollbackPath . DIRECTORY_SEPARATOR . '99900000000.sql', 'DROP TABLE T $$');

        $harness = new MigrationHarness($sqlPath, $rollbackPath);
        $plan = $harness->dryRun('999.00000000');

        $this->assertCount(1, $plan['queries']);
        $this->assertSame($rollbackPath . DIRECTORY_SEPARATOR . '99900000000.sql', $plan['rollback']);
        $this->assertSame(['DROP TABLE T'], $harness->getRollbackStatements('999.00000000'));

        unlink($sqlPath . DIRECTORY_SEPARATOR . '99900000000.sql');
        unlink($rollbackPath . DIRECTORY_SEPARATOR . '99900000000.sql');
        rmdir($rollbackPath);
        rmdir($sqlPath);
    }
}
