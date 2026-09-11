<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Shared\Models\Encomienda;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EncomiendaQrService
{
    private const PREFIX = 'CATU-ENC';

    public function contenido(Encomienda $encomienda): string
    {
        if (!$encomienda->viajeEncomienda) {
            throw ValidationException::withMessages([
                'encomienda' => 'La encomienda debe estar asignada a un viaje para generar su QR.',
            ]);
        }

        if ($encomienda->estaAnulada()) {
            throw ValidationException::withMessages([
                'encomienda' => 'No se puede generar el QR de una encomienda anulada.',
            ]);
        }

        if (!$encomienda->qr_token) {
            throw ValidationException::withMessages([
                'encomienda' => 'La encomienda asignada no tiene un QR generado.',
            ]);
        }

        return self::PREFIX . '|' . $encomienda->qr_token;
    }

    public function tokenDesdeContenido(string $contenido): string
    {
        $partes = explode('|', trim($contenido));

        if (count($partes) !== 2 || $partes[0] !== self::PREFIX || strlen($partes[1]) !== 64) {
            throw ValidationException::withMessages([
                'qr' => 'El código QR no corresponde a una encomienda válida.',
            ]);
        }

        return $partes[1];
    }

    public function imagen(string $contenido): string
    {
        $qrCode = Encoder::encode($contenido, ErrorCorrectionLevel::M(), 'UTF-8');
        $matrix = $qrCode->getMatrix();
        $width = $matrix->getWidth();
        $height = $matrix->getHeight();

        if ($width <= 0 || $height <= 0 || !function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('No se pudo generar la imagen QR de la encomienda.');
        }

        $targetSize = 500;
        $marginModules = 2;
        $totalModules = $width + ($marginModules * 2);
        $moduleSize = max(1, (int) floor($targetSize / $totalModules));
        $renderedSize = $totalModules * $moduleSize;
        $offset = (int) floor(($targetSize - $renderedSize) / 2);
        $image = imagecreatetruecolor($targetSize, $targetSize);

        if ($image === false) {
            throw new RuntimeException('No se pudo crear la imagen QR de la encomienda.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $x1 = $offset + (($x + $marginModules) * $moduleSize);
                $y1 = $offset + (($y + $marginModules) * $moduleSize);
                imagefilledrectangle($image, $x1, $y1, $x1 + $moduleSize - 1, $y1 + $moduleSize - 1, $black);
            }
        }

        ob_start();
        $ok = imagepng($image, null, 9);
        $png = ob_get_clean();
        imagedestroy($image);

        if (!$ok || !is_string($png) || $png === '') {
            throw new RuntimeException('No se pudo generar el PNG del código QR.');
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

}
