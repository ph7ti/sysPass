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

namespace SP\Tests\Config;

use PHPUnit\Framework\TestCase;
use SP\Config\FeatureFlags;

/**
 * @package SP\Tests\Config
 */
class FeatureFlagsTest extends TestCase
{
    public function testDefaultsToDisabled()
    {
        $flags = new FeatureFlags(TMP_PATH . DIRECTORY_SEPARATOR . 'features-missing.json');

        $this->assertFalse($flags->isEnabled(FeatureFlags::OIDC));
    }

    public function testLoadsFile()
    {
        $file = TMP_PATH . DIRECTORY_SEPARATOR . 'features.json';

        file_put_contents($file, json_encode([
            FeatureFlags::MFA => true,
            FeatureFlags::OIDC => false,
            FeatureFlags::MULTI_GROUP => 'yes'
        ]));

        $flags = new FeatureFlags($file);

        $this->assertTrue($flags->isEnabled(FeatureFlags::MFA));
        $this->assertFalse($flags->isEnabled(FeatureFlags::OIDC));
        $this->assertTrue($flags->isEnabled(FeatureFlags::MULTI_GROUP));
        $this->assertFalse($flags->isEnabled(FeatureFlags::API_PRINCIPALS));

        unlink($file);
    }
}
