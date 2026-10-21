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

namespace SP\Services\Upgrade;

use SP\Storage\Database\MySQLFileParser;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Migration planning and rollback helpers.
 *
 * MySQL DDL is not transactional, so a "dry run" only plans and validates a
 * migration. Rollback is only possible when the migration ships an explicit
 * schemas/rollback/<version>.sql file.
 *
 * @package SP\Services\Upgrade
 */
final class MigrationHarness
{
    const ROLLBACK_DIR = SQL_PATH . DIRECTORY_SEPARATOR . 'rollback';

    /**
     * @var string
     */
    private $sqlPath;
    /**
     * @var string
     */
    private $rollbackPath;

    /**
     * @param string|null $sqlPath
     * @param string|null $rollbackPath
     */
    public function __construct(?string $sqlPath = null, ?string $rollbackPath = null)
    {
        $this->sqlPath = $sqlPath ?: SQL_PATH;
        $this->rollbackPath = $rollbackPath ?: self::ROLLBACK_DIR;
    }

    /**
     * @param string $currentVersion
     *
     * @return array
     */
    public function getPending(string $currentVersion): array
    {
        $pending = [];

        foreach (UpgradeDatabaseService::UPGRADES as $version) {
            if (VersionUtil::checkVersion($currentVersion, $version)) {
                $pending[] = $version;
            }
        }

        return $pending;
    }

    /**
     * Plans a migration without executing it.
     *
     * @param string $version
     *
     * @return array
     * @throws UpgradeException
     */
    public function dryRun(string $version): array
    {
        $file = $this->getMigrationFile($version);
        $rollback = $this->getRollbackFile($version);

        return [
            'version' => $version,
            'file' => $file,
            'queries' => $this->parse($file),
            'rollback' => is_readable($rollback) ? $rollback : null,
        ];
    }

    /**
     * Returns the statements needed to undo a migration.
     *
     * @param string $version
     *
     * @return array
     * @throws UpgradeException
     */
    public function getRollbackStatements(string $version): array
    {
        $file = $this->getRollbackFile($version);

        if (!is_readable($file)) {
            throw new UpgradeException(
                __u('Rollback file not found'),
                UpgradeException::CRITICAL,
                $file
            );
        }

        return $this->parse($file);
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function getMigrationFile(string $version): string
    {
        return $this->sqlPath . DIRECTORY_SEPARATOR . str_replace('.', '', $version) . '.sql';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function getRollbackFile(string $version): string
    {
        return $this->rollbackPath . DIRECTORY_SEPARATOR . str_replace('.', '', $version) . '.sql';
    }

    /**
     * @param string $file
     *
     * @return array
     * @throws UpgradeException
     */
    private function parse(string $file): array
    {
        try {
            return array_map('trim', (new MySQLFileParser(new FileHandler($file)))->parse('$$'));
        } catch (FileException $e) {
            throw new UpgradeException(
                __u('Unable to read migration file'),
                UpgradeException::CRITICAL,
                $file,
                $e->getCode(),
                $e
            );
        }
    }
}
