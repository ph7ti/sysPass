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

namespace SP\Config;

defined('APP_ROOT') || die();

/**
 * Feature flags used to roll out security-sensitive changes gradually.
 *
 * Values are read from app/config/features.json and can be overridden with
 * environment variables named SYSPASS_FEATURE_<FLAG> (e.g.
 * SYSPASS_FEATURE_OIDC=1). Flags default to disabled.
 *
 * @package SP\Config
 */
final class FeatureFlags
{
    const OIDC = 'oidc';
    const MFA = 'mfa';
    const MULTI_GROUP = 'multi_group';
    const API_PRINCIPALS = 'api_principals';
    const ABSOLUTE_SESSION_TIMEOUT = 'absolute_session_timeout';

    /**
     * @var array
     */
    private $flags = [];
    /**
     * @var string
     */
    private $file;

    /**
     * @param string|null $file
     */
    public function __construct(?string $file = null)
    {
        $this->file = $file ?: CONFIG_PATH . DIRECTORY_SEPARATOR . 'features.json';

        if (is_readable($this->file)) {
            $data = json_decode((string)file_get_contents($this->file), true);

            if (is_array($data)) {
                $this->flags = $data;
            }
        }

        foreach ($this->flags as $name => $value) {
            $this->flags[$name] = $this->toBool($value);
        }

        $this->loadEnvironmentOverrides();
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isEnabled(string $name): bool
    {
        $environment = getenv('SYSPASS_FEATURE_' . strtoupper($name));

        if ($environment !== false) {
            return $this->toBool($environment);
        }

        return isset($this->flags[$name]) && $this->flags[$name] === true;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->flags;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
    }

    private function loadEnvironmentOverrides()
    {
        $known = [
            self::OIDC,
            self::MFA,
            self::MULTI_GROUP,
            self::API_PRINCIPALS,
            self::ABSOLUTE_SESSION_TIMEOUT
        ];

        foreach ($known as $name) {
            $environment = getenv('SYSPASS_FEATURE_' . strtoupper($name));

            if ($environment !== false) {
                $this->flags[$name] = $this->toBool($environment);
            }
        }
    }
}
