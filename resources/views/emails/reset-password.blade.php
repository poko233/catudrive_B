<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Recuperación de contraseña
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f1f5f9;
        font-family: Arial, Helvetica, sans-serif;
        color: #0f172a;
    "
>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width: 100%;
        padding: 32px 14px;
        background-color: #f1f5f9;
    "
>
    <tr>
        <td align="center">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="
                    width: 100%;
                    max-width: 540px;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 16px;
                    overflow: hidden;
                "
            >

                {{-- HEADER --}}

                <tr>
                    <td
                        style="
                            background-color: #0f172a;
                            padding: 28px 30px;
                            text-align: center;
                        "
                    >
                        <div
                            style="
                                color: #ffffff;
                                font-size: 22px;
                                font-weight: bold;
                            "
                        >
                            {{ config('app.name', 'MetaSoft') }}
                        </div>

                        <div
                            style="
                                margin-top: 6px;
                                color: #94a3b8;
                                font-size: 13px;
                            "
                        >
                            Recuperación de contraseña
                        </div>
                    </td>
                </tr>

                {{-- CONTENT --}}

                <tr>
                    <td
                        style="
                            padding: 32px 30px;
                        "
                    >

                        <div
                            style="
                                font-size: 18px;
                                font-weight: bold;
                                margin-bottom: 14px;
                                color: #0f172a;
                            "
                        >
                            Hola {{ $userName }}
                        </div>

                        <div
                            style="
                                color: #475569;
                                font-size: 14px;
                                line-height: 22px;
                            "
                        >
                            Recibimos una solicitud para restablecer
                            la contraseña de tu cuenta.
                        </div>

                        <div
                            style="
                                margin-top: 25px;
                                margin-bottom: 8px;
                                text-align: center;
                                color: #64748b;
                                font-size: 12px;
                                font-weight: bold;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                            "
                        >
                            Tu código de seguridad
                        </div>

                        <div
                            style="
                                margin: 10px 0 24px 0;
                                text-align: center;
                            "
                        >
                            <div
                                style="
                                    display: inline-block;
                                    padding: 16px 24px;
                                    background-color: #f8fafc;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 12px;
                                    color: #ef4444;
                                    font-size: 30px;
                                    font-weight: bold;
                                    letter-spacing: 8px;
                                "
                            >
                                {{ $code }}
                            </div>
                        </div>

                        <div
                            style="
                                color: #475569;
                                font-size: 13px;
                                line-height: 21px;
                            "
                        >
                            Ingresa este código en la pantalla de recuperación
                            para crear una nueva contraseña.
                        </div>

                        <div
                            style="
                                margin-top: 16px;
                                padding: 12px 14px;
                                background-color: #fff7ed;
                                border-radius: 10px;
                                color: #9a3412;
                                font-size: 12px;
                                line-height: 19px;
                            "
                        >
                            Este código expirará en
                            <strong>
                                {{ $expireMinutes }} minutos
                            </strong>
                            y solo puede utilizarse una vez.
                        </div>

                        <div
                            style="
                                margin-top: 22px;
                                color: #64748b;
                                font-size: 12px;
                                line-height: 19px;
                            "
                        >
                            Si no solicitaste este cambio, ignora este correo.
                            Tu contraseña actual seguirá funcionando.
                        </div>

                    </td>
                </tr>

                {{-- FOOTER --}}

                <tr>
                    <td
                        style="
                            padding: 18px 30px;
                            background-color: #f8fafc;
                            border-top: 1px solid #e2e8f0;
                            text-align: center;
                            color: #94a3b8;
                            font-size: 11px;
                            line-height: 17px;
                        "
                    >
                        Este es un mensaje automático.
                        No respondas a este correo.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>

</html>