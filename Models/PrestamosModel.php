<?php
/**
 * Operaciones de reservas y préstamos.
 * Las operaciones críticas utilizan los procedimientos almacenados de
 * biblioteca_v3 para centralizar las reglas de negocio.
 */
class PrestamosModel extends Query
{
    public function __construct(){parent::__construct();}

    public function getPrestamos()
    {
        return $this->selectAll("SELECT r.id_reserva AS Idreserva_cab,
            u.id_usuario AS Idusuario,CONCAT(u.nombres,' ',u.apellidos) AS Usuario_nombre1,
            u.correo AS Usuario_correo,u.telefono AS Usuario_ci,
            r.id_estado_reserva AS Tbl_Estados_solicitudes_idEstado_solicitud,
            er.nombre AS Estado_solicitud_descripcion,r.fecha_reserva AS Reserva_cab_fecha_solicitud,
            r.fecha_cancelacion,r.fecha_expiracion,r.activo,
            r.numero_reserva
            FROM reservas r
            INNER JOIN usuarios u ON u.id_usuario=r.id_usuario
            INNER JOIN estados_reserva er ON er.id_estado_reserva=r.id_estado_reserva
            ORDER BY r.fecha_reserva DESC");
    }

    /** Crea un préstamo directo tomando un ejemplar disponible. */
    public function insertarPrestamo($estudiante,$libro,$cantidad,string $fecha_prestamo,string $fecha_devolucion,string $observacion)
    {
        $cantidad=max(1,(int)$cantidad);
        $disp=$this->selectPrepared("SELECT e.id_ejemplar FROM ejemplares e WHERE e.id_libro=? AND e.id_estado_ejemplar=1 AND e.activo=1 ORDER BY e.id_ejemplar LIMIT 1",[$libro]);
        if(!$disp)return 0;
        $numero='PRE-'.date('YmdHis').'-'.random_int(10,99);
        $estado=$this->select("SELECT id_estado_prestamo FROM estados_prestamo WHERE nombre='Activo' LIMIT 1");
        $estadoId=$estado['id_estado_prestamo']??1;
        try{
            $this->call("CALL sp_confirmar_prestamo(?,?,?,?,?)",[$numero,null,$estudiante,$estadoId,$fecha_devolucion]);
            $p=$this->select("SELECT LAST_INSERT_ID() AS id_prestamo");
            // sp_confirmar_prestamo devuelve el id mediante SELECT; con PDO es más
            // seguro consultar el préstamo recién creado por su número.
            $p=$this->selectPrepared("SELECT id_prestamo FROM prestamos WHERE numero_prestamo=?",[$numero]);
            if(!$p){$this->rollback();return 0;}
            for($i=0;$i<$cantidad;$i++){
                $ej=$i===0?$disp:$this->selectPrepared("SELECT e.id_ejemplar FROM ejemplares e WHERE e.id_libro=? AND e.id_estado_ejemplar=1 AND e.activo=1 AND e.id_ejemplar<>? ORDER BY e.id_ejemplar LIMIT 1",[$libro,$disp['id_ejemplar']]);
                if(!$ej)return 0;
                $this->call("CALL sp_agregar_prestamo_detalle(?,?)",[$p['id_prestamo'],$ej['id_ejemplar']]);
            }
            return (int)$p['id_prestamo'];
        }catch(Throwable $e){error_log($e->getMessage());return 0;}
    }

    /**
     * Método utilizado por el flujo QR antiguo. En biblioteca_v3 se reemplaza
     * por la transición de reserva a préstamo.
     */
    public function estadoPrestamo($estado,$id)
    {
        return $this->save("UPDATE reservas SET id_estado_reserva=?,activo=CASE WHEN ? IN(4,5,6) THEN 0 ELSE activo END WHERE id_reserva=?",[$estado,$estado,$id]);
    }
    public function devueltoPrestamo($estado,$id){return $this->estadoPrestamo($estado,$id);}

    public function actualizarPrestamo($estado,$fecha_devolucion,$id)
    {
        try{
            $this->call("CALL sp_registrar_devolucion(?)",[$id]);
            return 'ok';
        }catch(Throwable $e){error_log($e->getMessage());return'error';}
    }

    public function selectDatos(){
        return $this->select("SELECT nombre,valor FROM parametros WHERE grupo_parametro='SISTEMA' AND activo=1 ORDER BY id_parametro LIMIT 1") ?: [];
    }

    public function getCantLibro($libro){return $this->selectPrepared("SELECT l.id_libro AS id,l.titulo,COUNT(e.id_ejemplar) AS cantidad,
        SUM(e.id_estado_ejemplar=1) AS disponibles FROM libros l LEFT JOIN ejemplares e ON e.id_libro=l.id_libro AND e.activo=1 WHERE l.id_libro=? GROUP BY l.id_libro",[$libro]);}

    public function selectPrestamoDebe()
    {
        return $this->selectAll("SELECT p.id_prestamo AS id,p.id_usuario AS id_estudiante,p.numero_prestamo,
            CONCAT(u.nombres,' ',u.apellidos) AS nombre,l.titulo,p.fecha_prestamo,p.fecha_devolucion,
            COUNT(pd.id_ejemplar) AS cantidad,p.observacion,p.id_estado_prestamo AS estado
            FROM prestamos p JOIN usuarios u ON u.id_usuario=p.id_usuario
            JOIN prestamo_detalle pd ON pd.id_prestamo=p.id_prestamo
            JOIN ejemplares e ON e.id_ejemplar=pd.id_ejemplar
            JOIN libros l ON l.id_libro=e.id_libro
            WHERE p.activo=1 AND p.fecha_devolucion IS NULL
            GROUP BY p.id_prestamo,u.nombres,u.apellidos,l.titulo,p.fecha_prestamo,p.fecha_devolucion,p.observacion
            ORDER BY u.apellidos,u.nombres");
    }

    public function getPrestamoLibro($id_prestamo)
    {
        return $this->selectPrepared("SELECT p.id_prestamo AS id,p.id_usuario AS id_estudiante,
            CONCAT(u.nombres,' ',u.apellidos) AS nombre,c.nombre AS carrera,l.titulo,
            p.fecha_prestamo,p.fecha_devolucion,p.observacion,
            COUNT(pd.id_ejemplar) AS cantidad,p.id_estado_prestamo AS estado
            FROM prestamos p JOIN usuarios u ON u.id_usuario=p.id_usuario
            LEFT JOIN carreras c ON c.id_carrera=u.id_carrera
            JOIN prestamo_detalle pd ON pd.id_prestamo=p.id_prestamo
            JOIN ejemplares e ON e.id_ejemplar=pd.id_ejemplar
            JOIN libros l ON l.id_libro=e.id_libro
            WHERE p.id_prestamo=? GROUP BY p.id_prestamo",[$id_prestamo]);
    }

    public function renovar($id,$nueva,$usuario){try{$this->call("CALL sp_renovar_prestamo(?,?,?)",[$id,$nueva,$usuario]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}}
    public function reservar($numero,$usuario,$estado,$limite){
        try{
            $this->call("CALL sp_crear_reserva(?,?,?,?,@id_reserva)",[$numero,$usuario,$estado,$limite]);
            $r=$this->select("SELECT @id_reserva AS id_reserva");return (int)($r['id_reserva']??0);
        }catch(Throwable $e){error_log($e->getMessage());return 0;}
    }
    public function agregarReservaDetalle($idReserva,$idEjemplar){try{$this->call("CALL sp_agregar_reserva_detalle(?,?)",[$idReserva,$idEjemplar]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}}
    public function cancelarReserva($id,$usuario,$motivo){try{$this->call("CALL sp_cancelar_reserva(?,?,?)",[$id,$usuario,$motivo]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}}
    public function confirmarReserva($id,$usuario){try{$this->call("CALL sp_confirmar_reserva(?,?)",[$id,$usuario]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}}
    public function devolverPorReserva($id){
        $p=$this->selectPrepared("SELECT id_prestamo FROM prestamos WHERE id_reserva=? AND fecha_devolucion IS NULL ORDER BY id_prestamo DESC LIMIT 1",[$id]);
        if(!$p)return'no_prestamo';
        try{$this->call("CALL sp_registrar_devolucion(?)",[$p['id_prestamo']]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}
    }
}
?>