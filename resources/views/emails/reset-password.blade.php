<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recuperación de contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 830px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <tr>
            <td style="background-color: #28a745; color: white; text-align: center; padding: 20px 0;">
            <img width="200" height="60" alt="logo" class="img-fluid" src="https://aldasa.pe/assets/images/logo-aldasape-color.png" style="background: white;padding: 11px;border-radius: 10px;">    
            <h2 style="margin: 0;">Recuperación de Contraseña</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px;">
                <p style="font-size: 16px; color: #333;">
                    Hola 👋 <strong>{{ $email }}</strong>,<br><br>
                    Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.
                </p>

                <p style="text-align: center; margin: 30px 0;">
                    <a href="{{ $resetLink }}" 
                       style="background-color: #28a745; color: white; text-decoration: none; padding: 14px 25px; border-radius: 5px; font-weight: bold;">
                       Restablecer Contraseña
                    </a>
                </p>

                <p style="font-size: 14px; color: #666;">
                    Si no solicitaste este cambio, puedes ignorar este mensaje. 
                    Este enlace expirará en 60 minutos por razones de seguridad.
                </p>

                <p style="margin-top: 40px; font-size: 14px; color: #999; text-align: center;">
                    © {{ date('Y') }} - Tu Aplicación. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
