<?php
/**
 * Capa de acceso a datos.
 * Centraliza SELECT, INSERT/UPDATE/DELETE, transacciones y llamadas a
 * procedimientos almacenados de biblioteca_v3.
 */
class Query extends Conexion
{
    protected $con;

    public function __construct()
    {
        $pdo = new Conexion();
        $this->con = $pdo->conect();
    }

    public function select(string $sql)
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function selectAll(string $sql)
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectPrepared(string $sql, array $datos = [])
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($datos);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function selectAllPrepared(string $sql, array $datos = [])
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($datos);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(string $sql, array $datos = [])
    {
        $stmt = $this->con->prepare($sql);
        return $stmt->execute($datos) ? 1 : 0;
    }

    /**
     * Igual que save(), pero devuelve la cantidad real de filas afectadas.
     * execute() de PDO devuelve true aunque el UPDATE/DELETE no haya
     * afectado ninguna fila, por lo que save() no sirve para detectar
     * "no existía la fila que quería actualizar".
     */
    public function saveFilas(string $sql, array $datos = []): int
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($datos);
        return $stmt->rowCount();
    }

    public function insert(string $sql, array $datos = [])
    {
        $stmt = $this->con->prepare($sql);
        if (!$stmt->execute($datos)) {
            return 0;
        }
        return $this->con->lastInsertId();
    }

    protected function beginTransaction(): void
    {
        if (!$this->con->inTransaction()) {
            $this->con->beginTransaction();
        }
    }

    protected function commit(): void
    {
        if ($this->con->inTransaction()) {
            $this->con->commit();
        }
    }

    protected function rollback(): void
    {
        if ($this->con->inTransaction()) {
            $this->con->rollBack();
        }
    }

    /**
     * Ejecuta un CALL sin resultado tabular.
     * Después de una llamada a procedimiento se libera el result set para
     * que PDO pueda ejecutar la siguiente sentencia sobre la misma conexión.
     */
    protected function call(string $sql, array $datos = []): void
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($datos);
        while ($stmt->nextRowset()) {
            // Consume result sets secundarios del procedimiento.
        }
        $stmt->closeCursor();
    }

    /**
     * Comprueba permisos de la aplicación usando roles y rol_permiso de
     * biblioteca_v3. Los nombres antiguos de módulos se traducen a los
     * códigos de permiso de la nueva estructura.
     */
    public function verificarPermisos($idUsuario, $modulo): bool
    {
        // Antes había un atajo aquí que le daba todos los permisos al usuario
        // con id_usuario=1 sin mirar su rol real. Era un agujero de seguridad:
        // cualquier cuenta (incluso un Alumno) que terminara teniendo ese ID
        // por casualidad (ej. el primer registro de la base) quedaba con
        // acceso total de administrador. Se eliminó: el rol Administrador ya
        // tiene sus permisos reales asignados en rol_permiso.
        $mapa = [
            'Usuarios' => ['USR_VIEW', 'USR_EDIT'],
            'Estudiantes' => ['USR_VIEW', 'USR_EDIT'],
            'Libros' => ['BOOK_VIEW', 'BOOK_EDIT'],
            'Autor' => ['BOOK_VIEW', 'BOOK_EDIT'],
            'Editorial' => ['BOOK_VIEW', 'BOOK_EDIT'],
            'Materia' => ['BOOK_VIEW', 'BOOK_EDIT'],
            'Categoria' => ['BOOK_VIEW', 'BOOK_EDIT'],
            'Carrera' => ['USR_VIEW', 'USR_EDIT'],
            'Prestamos' => ['PRESTAMO'],
            'Reservas' => ['RES_CREATE', 'RES_CANCEL'],
            'EscanerQR' => ['PRESTAMO'],
            'Configuracion' => ['REPORTES'],
        ];

        $codigos = $mapa[$modulo] ?? [$modulo];
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $sql = "SELECT 1
                FROM usuario_rol ur
                INNER JOIN rol_permiso rp ON rp.id_rol = ur.id_rol
                INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
                WHERE ur.id_usuario = ?
                  AND ur.activo = 1
                  AND p.activo = 1
                  AND p.codigo IN ($placeholders)
                LIMIT 1";
        return (bool)$this->selectPrepared($sql, array_merge([(int)$idUsuario], $codigos));
    }
}
?>