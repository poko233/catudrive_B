<?php

declare(strict_types=1);

namespace App\Shared\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

class QrService
{
    private const BASE62 =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

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

        /*
         * Exactamente igual al lector actual:
         * primeros 16 bytes de la clave.
         */
        $this->iv = substr(
            $this->key,
            0,
            16
        );
    }

    public function generateQrImage(
        int $userId,
        int $version = 1
    ): string {
        if ($userId <= 0) {
            throw new RuntimeException(
                'El ID del usuario no es válido.'
            );
        }

        $contenido = $this->encrypt(
            $userId,
            $version
        );

        $qrCode = Encoder::encode(
            $contenido,
            ErrorCorrectionLevel::M(),
            'UTF-8'
        );

        $matrix = $qrCode->getMatrix();

        $width = $matrix->getWidth();
        $height = $matrix->getHeight();

        if (
            $width <= 0 ||
            $height <= 0
        ) {
            throw new RuntimeException(
                'No se pudo generar la matriz QR.'
            );
        }

        if (
            !function_exists(
                'imagecreatetruecolor'
            )
        ) {
            throw new RuntimeException(
                'La extensión GD de PHP es necesaria para generar el QR.'
            );
        }

        $targetSize = 500;
        $marginModules = 1;

        $totalModules =
            $width +
            ($marginModules * 2);

        $moduleSize = max(
            1,
            (int) floor(
                $targetSize /
                $totalModules
            )
        );

        $renderedSize =
            $totalModules *
            $moduleSize;

        $offset = (int) floor(
            ($targetSize - $renderedSize) /
            2
        );

        $image = imagecreatetruecolor(
            $targetSize,
            $targetSize
        );

        if ($image === false) {
            throw new RuntimeException(
                'No se pudo crear la imagen QR.'
            );
        }

        $white = imagecolorallocate(
            $image,
            255,
            255,
            255
        );

        $black = imagecolorallocate(
            $image,
            0,
            0,
            0
        );

        imagefill(
            $image,
            0,
            0,
            $white
        );

        for (
            $y = 0;
            $y < $height;
            $y++
        ) {
            for (
                $x = 0;
                $x < $width;
                $x++
            ) {
                if (
                    $matrix->get(
                        $x,
                        $y
                    ) !== 1
                ) {
                    continue;
                }

                $x1 =
                    $offset +
                    (($x + $marginModules) * $moduleSize);

                $y1 =
                    $offset +
                    (($y + $marginModules) * $moduleSize);

                imagefilledrectangle(
                    $image,
                    $x1,
                    $y1,
                    $x1 + $moduleSize - 1,
                    $y1 + $moduleSize - 1,
                    $black
                );
            }
        }

        ob_start();

        $ok = imagepng(
            $image,
            null,
            9
        );

        $png = ob_get_clean();

        imagedestroy(
            $image
        );

        if (
            !$ok ||
            !is_string($png) ||
            $png === ''
        ) {
            throw new RuntimeException(
                'No se pudo generar el PNG del código QR.'
            );
        }

        return
            'data:image/png;base64,' .
            base64_encode(
                $png
            );
    }

    private function encrypt(
        int $userId,
        int $version
    ): string {
        /*
         * Se conserva user_id para que qrCrypto.ts actual
         * siga funcionando sin ningún cambio.
         */
        $payload = json_encode(
            [
                'user_id' => $userId,
                'v' => $version,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        if (!is_string($payload)) {
            throw new RuntimeException(
                'No se pudo preparar el contenido QR.'
            );
        }

        $ciphertext = openssl_encrypt(
            $payload,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        if (
            !is_string($ciphertext) ||
            $ciphertext === ''
        ) {
            throw new RuntimeException(
                'No se pudo cifrar el contenido QR.'
            );
        }

        return $this->base62Encode(
            $ciphertext
        );
    }

    private function base62Encode(
        string $binary
    ): string {
        if ($binary === '') {
            throw new RuntimeException(
                'No existen bytes para codificar.'
            );
        }

        /*
         * Conversión arbitraria Base256 → Base62
         * sin depender de GMP ni BCMath.
         *
         * Los dígitos se almacenan little-endian.
         */
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
}