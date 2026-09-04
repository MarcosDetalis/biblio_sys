<?php
/** Operaciones del lector QR sobre reservas de biblioteca_v3. */
class EscanerQRModel extends Query
{
    public function __construct(){parent::__construct();}
    public function obtenerReservas($id)
    {
        return $this->selectAllPrepared("SELECT l.titulo AS Libro_nombre,
            er.nombre AS Estado_solicitud_descripcion,r.id_estado_reserva AS Tbl_estado_solicitudes_idEstad_solicitud,
            r.id_reserva AS Tbl_reservas_cab_idReserva_cab,rd.id_reserva_detalle AS Idreserva_det,
            rd.id_ejemplar,rd.estado AS detalle_estado,r.fecha_limite_retiro AS Reserva_det_fecha_devolucion,
            CONCAT(u.nombres,' ',u.apellidos) AS alumno
            FROM reserva_detalle rd
            JOIN reservas r ON r.id_reserva=rd.id_reserva
            JOIN usuarios u ON u.id_usuario=r.id_usuario
            JOIN estados_reserva er ON er.id_estado_reserva=r.id_estado_reserva
            JOIN ejemplares e ON e.id_ejemplar=rd.id_ejemplar
            JOIN libros l ON l.id_libro=e.id_libro
            WHERE r.id_reserva=? ORDER BY rd.id_reserva_detalle",[$id]);
    }
    public function obtenerPorQr($codigo)
    {
        return $this->selectAllPrepared("SELECT r.id_reserva, r.numero_reserva, r.id_usuario,
            CONCAT(u.nombres,' ',u.apellidos) AS usuario, u.correo,
            r.fecha_limite_retiro, er.nombre AS estado_reserva,
            qr.codigo_qr, qr.fecha_expiracion, qr.utilizado,
            rd.id_reserva_detalle, rd.id_ejemplar, rd.estado AS estado_detalle,
            l.titulo, e.codigo_ejemplar, e.codigo_barra
            FROM qr_reserva qr
            JOIN reservas r ON r.id_reserva=qr.id_reserva
            JOIN usuarios u ON u.id_usuario=r.id_usuario
            JOIN estados_reserva er ON er.id_estado_reserva=r.id_estado_reserva
            JOIN reserva_detalle rd ON rd.id_reserva=r.id_reserva
            JOIN ejemplares e ON e.id_ejemplar=rd.id_ejemplar
            JOIN libros l ON l.id_libro=e.id_libro
            WHERE qr.codigo_qr=?
              AND qr.utilizado=0
              AND qr.fecha_expiracion>=NOW()
            ORDER BY rd.id_reserva_detalle",[$codigo]);
    }

    public function confirmarRetiro($idReserva,$usuario)
    {
        try {
            $dias=$this->select("SELECT CAST(valor AS UNSIGNED) dias FROM parametros WHERE grupo_parametro='PRESTAMO' AND nombre='DIAS_PRESTAMO_ALUMNO' AND activo=1 LIMIT 1");
            $venc=date('Y-m-d',strtotime('+'.((int)($dias['dias']??7)).' days'));
            $stmt=$this->con->prepare("CALL sp_confirmar_retiro_qr(?,?,?)");
            $stmt->execute([$idReserva,$usuario,$venc]);
            $result=$stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ? 'ok' : 'error';
        }catch(Throwable $e){error_log($e->getMessage());return'error';}
    }

    public function actualizarStockLibro($id_libro){
        $ej=$this->selectPrepared("SELECT id_ejemplar FROM ejemplares WHERE id_libro=? AND id_estado_ejemplar=1 AND activo=1 LIMIT 1",[$id_libro]);
        return $ej?1:0;
    }
    public function accionLibro($estado,$id_libro){return $this->save("UPDATE ejemplares SET id_estado_ejemplar=? WHERE id_libro=? AND activo=1",[$estado,$id_libro]);}
    public function getPermisos(){return $this->selectAll("SELECT * FROM permisos WHERE activo=1");}
    public function getDetallePermisos($id){return $this->selectAllPrepared("SELECT rp.id_permiso AS Tbl_permisos_id_permiso FROM usuario_rol ur JOIN rol_permiso rp ON rp.id_rol=ur.id_rol WHERE ur.id_usuario=? AND ur.activo=1",[$id]);}
    public function actualizarDetalle($id_cabecera){
        return $this->save("UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva=? AND estado='RESERVADO'",[$id_cabecera]);
    }
    public function Cancelar_todo($id_cabecera){
        try{$this->call("CALL sp_cancelar_reserva(?,?,?)",[$id_cabecera,1,'Cancelación desde lector QR']);return'cancelado';}catch(Throwable $e){error_log($e->getMessage());return'error';}
    }
    public function actualizarchet($id_cabecera,$id_detalles){
        return $this->save("UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva=? AND id_reserva_detalle=?",[$id_cabecera,$id_detalles])?'ok':'error';
    }
}
?>