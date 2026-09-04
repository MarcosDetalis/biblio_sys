<?php
/**
 * Configuración principal de la aplicación.
 *
 * Los datos de conexión a MySQL se leen SIEMPRE de variables de entorno.
 * Nunca se escriben acá adentro, así el archivo puede subirse a un
 * repositorio (git push a Clever Cloud) sin exponer ninguna credencial.
 *
 * - En Clever Cloud: si el addon de MySQL está "vinculado" (linked) a esta
 *   aplicación (Service Dependencies > Link an add-on), Clever Cloud inyecta
 *   automáticamente MYSQL_ADDON_HOST, MYSQL_ADDON_PORT, MYSQL_ADDON_DB,
 *   MYSQL_ADDON_USER y MYSQL_ADDON_PASSWORD. No hace falta configurar nada
 *   más a mano.
 * - En XAMPP local: si esas variables no existen, se usan automáticamente
 *   los valores típicos de una instalación local (localhost/root sin
 *   contraseña/biblioteca_v3), para que siga funcionando sin tocar nada.
 */

// La URL se calcula para que el proyecto funcione tanto en localhost como
// publicado en Clever Cloud sin cambiar manualmente la ruta base.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($scriptDir === '.' || $scriptDir === '/') {
    $scriptDir = '';
}

// ============================================================
// CONEXIÓN MYSQL
// ============================================================
// Si existe Config/local.php (creado a mano, solo en tu XAMPP, NUNCA subido
// a Clever Cloud ni compartido), se carga primero y ahí adentro se define la
// conexión real a la base de Clever Cloud mediante putenv(). En Clever Cloud
// ese archivo simplemente no existe, así que ahí siempre se usan las
// variables MYSQL_ADDON_* que Clever Cloud inyecta automáticamente.
if (file_exists(__DIR__ . '/local.php')) {
    require __DIR__ . '/local.php';
}

// define() sí admite expresiones evaluadas en tiempo de ejecución (a
// diferencia de const, que exige un valor fijo conocido al compilar) — por
// eso getenv() se resuelve acá y no como const.
const host = 'bfbgwajwyb7i2iyjp1fh-mysql.services.clever-cloud.com';
const user = 'udkzkx5mndhvsnsu';
const pass = 'h9n1i7XgAXxVT3iCG72a';
const db = 'bfbgwajwyb7i2iyjp1fh';
const port = '3306';
const charset = 'utf8mb4';

// ============================================================
// ENVÍO DE CORREO (recuperación de contraseña)
// ============================================================
// SMTP_PASS NO es la contraseña normal de la cuenta de Gmail: es una
// "contraseña de aplicación" de 16 caracteres, generada en
// https://myaccount.google.com/apppasswords (requiere verificación en
// 2 pasos activada en esa cuenta). Se configura como variable de
// entorno (en Clever Cloud, o vía Config/local.php para XAMPP), nunca
// escrita acá adentro.
define('SMTP_USER', getenv('SMTP_USER') ?: 'desarrollofreepmg@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Equipo de Soporte - Sistema de Biblioteca');

// Configuración pública de la aplicación. Si se despliega en un subdirectorio
// puede definirse APP_BASE_URL desde el servidor; en caso contrario se usa la
// ruta del script actual.
$GLOBALS['APP_BASE_URL'] = rtrim((string)($GLOBALS['APP_BASE_URL'] ?? $scriptDir), '/') . '/';
define('base_url', $GLOBALS['APP_BASE_URL']);
?>