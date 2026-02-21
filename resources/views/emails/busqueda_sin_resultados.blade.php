<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nueva búsqueda sin resultados</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">

<table align="center" width="100%" cellpadding="0" cellspacing="0"
       style="max-width: 830px; background-color: white; border-radius: 8px;
       overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

    <!-- HEADER -->
    <tr>
        <td style="background-color: #28a745; color: white; text-align: center; padding: 20px 0;">
            <img width="200" height="60" alt="ALDASA PE"
                 src="https://aldasa.pe/assets/images/logo-aldasape-color.png"
                 style="background: white; padding: 11px; border-radius: 10px;">
            <h2 style="margin: 15px 0 0 0;">Nueva búsqueda sin resultados</h2>
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding: 30px;">

            <p style="font-size: 16px; color: #333;">
                Se ha registrado una nueva búsqueda sin resultados en la plataforma.
                Un posible cliente desea ser contactado.
            </p>

            <!-- DATOS DEL CLIENTE -->
            <table width="100%" cellpadding="10" cellspacing="0"
                   style="border-collapse: collapse; margin-top: 20px;">

                <tr style="background-color: #f9f9f9;">
                    <td style="border: 1px solid #ddd;"><strong>Nombre</strong></td>
                    <td style="border: 1px solid #ddd;">{{ $data['nombre'] }}</td>
                </tr>

                <tr>
                    <td style="border: 1px solid #ddd;"><strong>Teléfono</strong></td>
                    <td style="border: 1px solid #ddd;">{{ $data['telefono'] }}</td>
                </tr>

                <tr style="background-color: #f9f9f9;">
                    <td style="border: 1px solid #ddd;"><strong>Correo</strong></td>
                    <td style="border: 1px solid #ddd;">{{ $data['correo'] }}</td>
                </tr>

            </table>

            <!-- MENSAJE -->
            <div style="margin-top: 30px;">
                <h3 style="color: #28a745;">Detalles de la búsqueda:</h3>
                <div style="background-color: #f8f8f8; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0; font-size: 14px; color: #444;">
                    {!! nl2br(e($data['mensaje'])) !!}
                </div>
            </div>

            <!-- FOOTER -->
            <p style="margin-top: 40px; font-size: 14px; color: #999; text-align: center;">
                © {{ date('Y') }} ALDASA PE - Todos los derechos reservados.
            </p>

        </td>
    </tr>

</table>

</body>
</html>