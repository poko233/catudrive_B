<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

final class NumeroALetras
{
    private const UNIDADES = [
        '',
        'UNO',
        'DOS',
        'TRES',
        'CUATRO',
        'CINCO',
        'SEIS',
        'SIETE',
        'OCHO',
        'NUEVE',
        'DIEZ',
        'ONCE',
        'DOCE',
        'TRECE',
        'CATORCE',
        'QUINCE',
        'DIECISÉIS',
        'DIECISIETE',
        'DIECIOCHO',
        'DIECINUEVE',
        'VEINTE',
    ];

    private const DECENAS = [
        2 => 'VEINTE',
        3 => 'TREINTA',
        4 => 'CUARENTA',
        5 => 'CINCUENTA',
        6 => 'SESENTA',
        7 => 'SETENTA',
        8 => 'OCHENTA',
        9 => 'NOVENTA',
    ];

    private const CENTENAS = [
        1 => 'CIENTO',
        2 => 'DOSCIENTOS',
        3 => 'TRESCIENTOS',
        4 => 'CUATROCIENTOS',
        5 => 'QUINIENTOS',
        6 => 'SEISCIENTOS',
        7 => 'SETECIENTOS',
        8 => 'OCHOCIENTOS',
        9 => 'NOVECIENTOS',
    ];

    public static function convertir(float $numero): string
    {
        $entero = (int) floor(abs($numero));

        if ($entero === 0) {
            return 'CERO';
        }

        return self::convertirEntero($entero);
    }

    private static function convertirEntero(int $numero): string
    {
        if ($numero < 21) {
            return self::UNIDADES[$numero];
        }

        if ($numero < 100) {
            $decena = intdiv($numero, 10);
            $unidad = $numero % 10;

            if ($unidad === 0) {
                return self::DECENAS[$decena];
            }

            return self::DECENAS[$decena] . ' Y ' . self::UNIDADES[$unidad];
        }

        if ($numero < 1000) {
            $centena = intdiv($numero, 100);
            $resto = $numero % 100;

            if ($numero === 100) {
                return 'CIEN';
            }

            if ($resto === 0) {
                return self::CENTENAS[$centena];
            }

            return self::CENTENAS[$centena] . ' ' . self::convertirEntero($resto);
        }

        if ($numero < 1_000_000) {
            $millar = intdiv($numero, 1000);
            $resto = $numero % 1000;

            $textoMillar = $millar === 1
                ? 'MIL'
                : self::convertirEntero($millar) . ' MIL';

            if ($resto === 0) {
                return $textoMillar;
            }

            return $textoMillar . ' ' . self::convertirEntero($resto);
        }

        $millon = intdiv($numero, 1_000_000);
        $resto = $numero % 1_000_000;

        $textoMillon = $millon === 1
            ? 'UN MILLÓN'
            : self::convertirEntero($millon) . ' MILLONES';

        if ($resto === 0) {
            return $textoMillon;
        }

        return $textoMillon . ' ' . self::convertirEntero($resto);
    }
}