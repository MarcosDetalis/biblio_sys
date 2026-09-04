<?php
/**
 * Conexión PDO centralizada a MySQL/Percona de Clever Cloud.
 *
 * Se utiliza PDO con consultas preparadas y excepciones para evitar que cada
 * modelo tenga que administrar su propia conexión.
 */
class Conexion
{
    private $conect;

    public function __construct()
    {
        $dsn = 'mysql:host=' . host . ';port=' . port . ';dbname=' . db . ';charset=' . charset;

        try {
            $this->conect = new PDO($dsn, user, pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Error de conexión MySQL: ' . $e->getMessage());
            throw new RuntimeException('No fue posible conectar con la base de datos.');
        }
    }

    public function conect()
    {
        return $this->conect;
    }
}
?>