<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envío de correo vía Gmail (cuenta desarrollofreepmg@gmail.com) usando
 * PHPMailer (vendorizado a mano en Libraries/PHPMailer, sin Composer).
 *
 * IMPORTANTE: SMTP_PASS NO es la contraseña normal de la cuenta de
 * Gmail. Gmail exige una "contraseña de aplicación" (App Password) de
 * 16 caracteres, generada en:
 *   https://myaccount.google.com/apppasswords
 * (requiere tener la verificación en 2 pasos activada en esa cuenta).
 * Sin eso, el envío falla con un error de autenticación SMTP.
 */
class Mailer
{
    /**
     * Manda el correo con el código de 6 dígitos para recuperar
     * contraseña. Devuelve true si se pudo enviar, false si falló
     * (el error queda registrado con error_log).
     */
    public static function enviarCodigoRecuperacion(string $correoDestino, string $codigo): bool
    {
        $usuarioSmtp = getenv('SMTP_USER') ?: 'desarrollofreepmg@gmail.com';
        $passSmtp    = getenv('SMTP_PASS') ?: '';
        $nombreDesde = getenv('SMTP_FROM_NAME') ?: 'Equipo de Soporte - Sistema de Biblioteca';

        $texto = "Hola,\n\n"
            . "Hemos recibido una solicitud para recuperar la contraseña de tu cuenta.\n\n"
            . "Tu código de verificación es:\n\n"
            . $codigo . "\n\n"
            . "Este código es válido durante 10 minutos y cuenta con un máximo de 3 intentos. "
            . "Una vez utilizado correctamente o superado el número de intentos permitidos, el código quedará invalidado.\n\n"
            . "Si no solicitaste la recuperación de tu contraseña, puedes ignorar este mensaje. "
            . "Tu contraseña actual no será modificada mientras no se complete correctamente el proceso de verificación.\n\n"
            . "Saludos cordiales,\n"
            . "Equipo de Soporte\n"
            . "Sistema de Biblioteca";

        $html = '
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; color: #222; line-height: 1.5;">
            <p>Hola,</p>
            <p>Hemos recibido una solicitud para recuperar la contraseña de tu cuenta.</p>
            <p>Tu código de verificación es:</p>
            <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center; background: #f4f4f4; padding: 16px; border-radius: 8px;">'
                . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</p>
            <p>Este código es válido durante <strong>10 minutos</strong> y cuenta con un máximo de <strong>3 intentos</strong>. Una vez utilizado correctamente o superado el número de intentos permitidos, el código quedará invalidado.</p>
            <p>Si no solicitaste la recuperación de tu contraseña, puedes ignorar este mensaje. Tu contraseña actual no será modificada mientras no se complete correctamente el proceso de verificación.</p>
            <p>Saludos cordiales,<br>Equipo de Soporte<br>Sistema de Biblioteca</p>
        </div>';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $usuarioSmtp;
            $mail->Password   = $passSmtp;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($usuarioSmtp, $nombreDesde);
            $mail->addAddress($correoDestino);

            $mail->Subject = 'Código de verificación - Recuperar contraseña';
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $texto;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Mailer::enviarCodigoRecuperacion ' . $mail->ErrorInfo);
            return false;
        }
    }
}
?>
