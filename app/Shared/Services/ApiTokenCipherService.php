<?php

declare(strict_types=1);

namespace App\Shared\Services;

use RuntimeException;

/**
 * Cifra/descifra el api_token de Empresa.
 *
 * Reutiliza el mismo patrón criptográfico que QrService:
 *
 * - AES-256-CBC
 * - IV fijo = primeros 16 bytes de la clave
 * - Clave desde config('qr.secret_key') (64 hex)
 * - Ciphertext codificado en Base62 (sin dependencias GMP/BCMath)
 *
 * El frontend descifra con la misma lógica que decryptQrData()
 * (ver nota técnica al final del entregable).
 */
final class ApiTokenCipherService
{
    private const BASE62 =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const CIPHER = 'AES-256-CBC';

    private const BLOCK_SIZE = 16;

    private string $key;

    private string $iv;

    public function __construct()
    {
        $secret = (string) config(
            'qr.secret_key',
            ''
        );

        $secret = preg_replace(
            '/[^0-9a-fA-F]/',
            '',
            $secret
        ) ?? '';

        if (strlen($secret) !== 64) {
            throw new RuntimeException(
                'QR_SECRET_KEY debe contener exactamente 64 caracteres hexadecimales.'
            );
        }

        $key = hex2bin($secret);

        if (
            $key === false ||
            strlen($key) !== 32
        ) {
            throw new RuntimeException(
                'QR_SECRET_KEY no es una clave AES-256 válida.'
            );
        }

        $this->key = $key;
        $this->iv = substr($this->key, 0, 16);
    }

    /**
     * Cifra un plaintext no vacío y devuelve el ciphertext en Base62.
     */
    public function encrypt(
        string $plaintext
    ): string {
        if ($plaintext === '') {
            throw new RuntimeException(
                'El token no puede estar vacío para cifrar.'
            );
        }

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        if (
            !is_string($ciphertext) ||
            $ciphertext === ''
        ) {
            throw new RuntimeException(
                'No se pudo cifrar el token.'
            );
        }

        return $this->base62Encode(
            $ciphertext
        );
    }

    /**
     * Descifra un Base62 previamente producido por encrypt().
     * Devuelve null si el input no es válido o está corrupto.
     */
    public function decrypt(
        string $encoded
    ): ?string {
        if ($encoded === '') {
            return null;
        }

        try {
            $ciphertext = $this->base62Decode(
                $encoded
            );
        } catch (RuntimeException) {
            return null;
        }

        if (
            $ciphertext === '' ||
            strlen($ciphertext) % self::BLOCK_SIZE !== 0
        ) {
            /*
             * Ciphertext corrupto o con leading-zero perdido.
             * No exponemos el detalle al llamador.
             */

            return null;
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        if (
            !is_string($plaintext) ||
            $plaintext === ''
        ) {
            return null;
        }

        return $plaintext;
    }

    /**
     * Descifra un Base62 y devuelve la máscara visible.
     */
    public function maskEncrypted(
        ?string $encoded
    ): ?string {
        if (
            $encoded === null ||
            $encoded === ''
        ) {
            return null;
        }

        $plaintext = $this->decrypt(
            $encoded
        );

        return $plaintext === null
            ? null
            : $this->mask($plaintext);
    }

    /**
     * Enmascara un plaintext: primeros 3 + "***" + últimos 3.
     *
     * - Si tiene 6 chars o menos: "***"
     * - Si tiene 7 o más: "abc***xyz"
     */
    public function mask(
        ?string $plaintext
    ): ?string {
        if (
            $plaintext === null ||
            $plaintext === ''
        ) {
            return null;
        }

        if (strlen($plaintext) <= 6) {
            return '***';
        }

        return substr(
            $plaintext,
            0,
            3
        )
            . '***'
            . substr(
                $plaintext,
                -3
            );
    }

    /**
     * Codifica bytes binarios como string Base62 (big-endian).
     *
     * Idéntico al algoritmo de QrService para mantener compatibilidad
     * con el frontend.
     */
    private function base62Encode(
        string $binary
    ): string {
        if ($binary === '') {
            throw new RuntimeException(
                'No existen bytes para codificar.'
            );
        }

        $digits = [0];

        $length = strlen(
            $binary
        );

        for (
            $i = 0;
            $i < $length;
            $i++
        ) {
            $carry = ord(
                $binary[$i]
            );

            $count = count(
                $digits
            );

            for (
                $j = 0;
                $j < $count;
                $j++
            ) {
                $value =
                    ($digits[$j] * 256) +
                    $carry;

                $digits[$j] =
                    $value % 62;

                $carry = intdiv(
                    $value,
                    62
                );
            }

            while ($carry > 0) {
                $digits[] =
                    $carry % 62;

                $carry = intdiv(
                    $carry,
                    62
                );
            }
        }

        $result = '';

        for (
            $i = count($digits) - 1;
            $i >= 0;
            $i--
        ) {
            $result .= self::BASE62[
                $digits[$i]
            ];
        }

        return $result ?: '0';
    }

    /**
     * Decodifica Base62 a bytes binarios.
     *
     * Inverso exacto de base62Encode().
     *
     * Nota: al igual que base62Encode(), los leading zeros del
     * ciphertext no se preservan (misma limitación que QrService).
     * decrypt() detecta esto con el check "múltiplo de 16" y
     * devuelve null → el admin re-ingresa el token.
     */
    private function base62Decode(
        string $encoded
    ): string {
        if ($encoded === '') {
            return '';
        }

        $bytes = [0];

        $length = strlen(
            $encoded
        );

        for (
            $i = 0;
            $i < $length;
            $i++
        ) {
            $carry = strpos(
                self::BASE62,
                $encoded[$i]
            );

            if ($carry === false) {
                throw new RuntimeException(
                    'Cadena Base62 inválida.'
                );
            }

            $count = count(
                $bytes
            );

            for (
                $j = 0;
                $j < $count;
                $j++
            ) {
                $value =
                    ($bytes[$j] * 62) +
                    $carry;

                $bytes[$j] =
                    $value % 256;

                $carry = intdiv(
                    $value,
                    256
                );
            }

            while ($carry > 0) {
                $bytes[] =
                    $carry % 256;

                $carry = intdiv(
                    $carry,
                    256
                );
            }
        }

        $binary = '';

        for (
            $i = count($bytes) - 1;
            $i >= 0;
            $i--
        ) {
            $binary .= chr(
                $bytes[$i]
            );
        }

        return $binary;
    }
}