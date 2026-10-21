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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Crypt;

defined('APP_ROOT') || die();

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use SP\Core\Exceptions\SPException;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;

/**
 * Class CryptPKI para el manejo de las funciones para PKI
 *
 * @package SP
 */
final class CryptPKI
{
    const KEY_SIZE = 2048;
    const PUBLIC_KEY_FILE = CONFIG_PATH . DIRECTORY_SEPARATOR . 'pubkey.pem';
    const PRIVATE_KEY_FILE = CONFIG_PATH . DIRECTORY_SEPARATOR . 'key.pem';

    /**
     * @var FileHandler
     */
    private $publicKeyFile;
    /**
     * @var FileHandler
     */
    private $privateKeyFile;

    /**
     * @throws SPException
     */
    public function __construct()
    {
        $this->setUp();
    }

    /**
     * Check if private and public keys exist
     *
     * @return void
     * @throws SPException
     */
    private function setUp()
    {
        $this->publicKeyFile = new FileHandler(self::PUBLIC_KEY_FILE);
        $this->privateKeyFile = new FileHandler(self::PRIVATE_KEY_FILE);

        try {
            $this->publicKeyFile->checkFileExists();
            $this->privateKeyFile->checkFileExists();

            if ($this->getKeySize() !== self::KEY_SIZE) {
                $this->createKeys();
            }
        } catch (FileException $e) {
            processException($e);

            $this->createKeys();
        } catch (\Exception $e) {
            processException($e);

            $this->createKeys();
        }
    }

    /**
     * Crea el par de claves pública y privada
     *
     * @throws FileException
     */
    public function createKeys()
    {
        $privateKey = RSA::createKey(self::KEY_SIZE);

        $this->publicKeyFile->save($privateKey->getPublicKey()->toString('PKCS8'));
        $this->privateKeyFile->save($privateKey->toString('PKCS1'));

        chmod(CryptPKI::PRIVATE_KEY_FILE, 0600);
    }

    /**
     * @return int
     */
    public static function getMaxDataSize()
    {
        return (self::KEY_SIZE / 8) - 11;
    }

    /**
     * Encriptar datos con la clave pública
     *
     * @param string $data los datos a encriptar
     *
     * @return string
     * @throws FileException
     */
    public function encryptRSA($data)
    {
        $key = PublicKeyLoader::load($this->getPublicKey())
            ->withPadding(RSA::ENCRYPTION_PKCS1);

        return $key->encrypt($data);
    }

    /**
     * Devuelve la clave pública desde el archivo
     *
     * @return string
     * @throws FileException
     */
    public function getPublicKey()
    {
        return $this->publicKeyFile
            ->checkFileExists()
            ->readToString();
    }

    /**
     * Desencriptar datos cifrados con la clave pública
     *
     * @param string $data los datos a desencriptar
     *
     * @return string
     * @throws FileException
     */
    public function decryptRSA($data)
    {
        try {
            $key = PublicKeyLoader::load($this->getPrivateKey())
                ->withPadding(RSA::ENCRYPTION_PKCS1);

            return $key->decrypt($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Devuelve la clave privada desde el archivo
     *
     * @return string
     * @throws FileException
     */
    public function getPrivateKey()
    {
        return $this->privateKeyFile
            ->checkFileExists()
            ->readToString();
    }

    /**
     * @return int
     * @throws FileException
     */
    public function getKeySize()
    {
        $key = PublicKeyLoader::load($this->getPrivateKey());

        return $key->getLength();
    }
}