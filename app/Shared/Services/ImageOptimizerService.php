<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ImageOptimizerService
{
    private const WEBP_QUALITY = 85;

    private const MAX_WIDTH = 10000;

    private const MAX_HEIGHT = 10000;

    private const MAX_PIXELS =
        40_000_000;

    public function convertToWebP(
        UploadedFile $archivo,
        string $directorioDestino,
        string $nombreBase,
    ): string {
        if (
            !function_exists(
                'imagewebp'
            )
        ) {
            throw new RuntimeException(
                'El servidor no tiene soporte para WebP en GD.'
            );
        }

        $rutaTemporal =
            $archivo->getRealPath();

        if (
            !$rutaTemporal ||
            !is_file($rutaTemporal)
        ) {
            throw new RuntimeException(
                'No se encontró el archivo temporal de la imagen.'
            );
        }

        $info =
            @getimagesize(
                $rutaTemporal
            );

        if ($info === false) {
            throw new RuntimeException(
                'El archivo enviado no es una imagen válida.'
            );
        }

        [
            $width,
            $height
        ] = $info;

        $mime =
            strtolower(
                (string) (
                    $info['mime'] ??
                    ''
                )
            );

        $this->validateDimensions(
            $width,
            $height
        );

        $imagen =
            match ($mime) {
                'image/jpeg' =>
                    @imagecreatefromjpeg(
                        $rutaTemporal
                    ),

                'image/png' =>
                    @imagecreatefrompng(
                        $rutaTemporal
                    ),

                'image/webp' =>
                    @imagecreatefromwebp(
                        $rutaTemporal
                    ),

                default =>
                    throw new RuntimeException(
                        'Tipo de imagen no soportado. Solo JPEG, PNG o WebP.'
                    ),
            };

        if (!$imagen) {
            throw new RuntimeException(
                'No se pudo leer la imagen original.'
            );
        }

        try {
            imagepalettetotruecolor(
                $imagen
            );

            if (
                $mime === 'image/png' ||
                $mime === 'image/webp'
            ) {
                imagealphablending(
                    $imagen,
                    false
                );

                imagesavealpha(
                    $imagen,
                    true
                );
            }

            $directorioDestino =
                rtrim(
                    $directorioDestino,
                    DIRECTORY_SEPARATOR
                );

            if (
                !is_dir(
                    $directorioDestino
                )
            ) {
                if (
                    !mkdir(
                        $directorioDestino,
                        0755,
                        true
                    ) &&
                    !is_dir(
                        $directorioDestino
                    )
                ) {
                    throw new RuntimeException(
                        'No se pudo crear el directorio de destino.'
                    );
                }
            }

            if (
                !is_writable(
                    $directorioDestino
                )
            ) {
                throw new RuntimeException(
                    'El directorio de destino no tiene permisos de escritura.'
                );
            }

            $nombreSeguro =
                $this->sanitizeBaseName(
                    $nombreBase
                );

            $nombreArchivo =
                $nombreSeguro .
                '.webp';

            $rutaAbsoluta =
                $directorioDestino .
                DIRECTORY_SEPARATOR .
                $nombreArchivo;

            $guardado =
                imagewebp(
                    $imagen,
                    $rutaAbsoluta,
                    self::WEBP_QUALITY
                );

            if (!$guardado) {
                throw new RuntimeException(
                    'No se pudo guardar la imagen WebP.'
                );
            }

            return $nombreArchivo;
        } finally {
            imagedestroy(
                $imagen
            );
        }
    }

    private function validateDimensions(
        int $width,
        int $height
    ): void {
        if (
            $width <= 0 ||
            $height <= 0
        ) {
            throw new RuntimeException(
                'Las dimensiones de la imagen no son válidas.'
            );
        }

        if (
            $width >
                self::MAX_WIDTH ||
            $height >
                self::MAX_HEIGHT ||
            ($width * $height) >
                self::MAX_PIXELS
        ) {
            throw new RuntimeException(
                'La imagen supera las dimensiones máximas permitidas.'
            );
        }
    }

    private function sanitizeBaseName(
        string $nombreBase
    ): string {
        $nombre =
            trim(
                $nombreBase
            );

        $nombre =
            basename(
                $nombre
            );

        $nombre =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '-',
                $nombre
            ) ?? '';

        $nombre =
            trim(
                $nombre,
                '-_'
            );

        if ($nombre === '') {
            throw new RuntimeException(
                'El nombre de destino de la imagen no es válido.'
            );
        }

        return mb_substr(
            $nombre,
            0,
            100
        );
    }
}