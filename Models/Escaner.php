
<?php
class EscanerQRModel extends Query
{
    private $id_libro, $estado;
    public function __construct()
    {
        parent::__construct();
    }

    

    // se modifico 
    public function obtenerReservas($id)
    {
        // $sql = "SELECT * FROM reservas_detalle where fkcabecera_id = $id ";
        $sql ="SELECT l.Libro_nombre,e.Estado_solicitud_descripcion,r.Idreserva_cab,d.Reserva_det_cantidad_solicitud,d.Reserva_det_fecha_entraga,
d.Reserva_det_fecha_devolucion FROM reservas_det d  INNER JOIN estados_solicitudes e INNER JOIN reservas_cab r INNER JOIN libros l WHERE d.Tbl_estado_solicitudes_idEstad_solicitud=e.Idestado_solicitud AND  d.Tbl_reservas_cab_idReserva_cab=r.Idreserva_cab AND d.Tbl_libros_idLibro=l.IdLibro AND d.Tbl_reservas_cab_idReserva_cab= $id ";
        $data = $this->selectAll($sql);
        return $data;
    }

    //resta la cantidad de tabla prestamo
    public function actualizarStockLibro($id_libro)
    {
        $sql = "UPDATE prestamo SET cantidad = cantidad - 1 WHERE id_libro  =?";
        $datos = array($id_libro);
        $data = $this->save($sql, $datos);
        return $data;
    }


    public function accionLibro($estado, $id_libro)
    {
        $this->id_libro = $id_libro;
        $this->estado = $estado;
        $sql = "UPDATE prestamo SET estado = ? WHERE id_libro = ?";
        $datos = array($this->estado, $this->id_libro);
        $data = $this->save($sql, $datos);
        return $data;
    }

    // trae la reserva cabecera
    public function getPermisos()
    {
        $sql = "SELECT * FROM permisos";
        $data = $this->selectAll($sql);
        return $data;
    }
    // esteee reserva detalle
    public function getDetallePermisos($id)
    {
        $sql = "SELECT * FROM detalle_permisos WHERE id_usuario = $id";
        $data = $this->selectAll($sql);
        return $data;
    }

    
    public function actualizarDetalle($id_cabecera)
    {
        //Tbl_estado_solicitudes_idEstad_solicitud
        // $query = "UPDATE reservas_detalle SET resdet_estado=7 WHERE fkcabecera_id = ? AND resdet_estado !=4";
        $query = "UPDATE reservas_det SET Tbl_estado_solicitudes_idEstad_solicitud=6 WHERE Tbl_reservas_cab_idReserva_cab = ? AND Tbl_estado_solicitudes_idEstad_solicitud !=1";
        $datos = array($id_cabecera);
        $this->save($query, $datos);
    }

    public function Cancelar_todo($id_cabecera)
    {
        //$query = "UPDATE reservas_detalle SET resdet_estado=7 WHERE fkcabecera_id = ? ";
        $query = "UPDATE reservas_det SET Tbl_estado_solicitudes_idEstad_solicitud=6 WHERE Tbl_reservas_cab_idReserva_cab = ? ";
        $datos = array($id_cabecera);

        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "cancelado";
        } else {
            $res = "error";
        }

        return $res;
    }

// modelos nuevoooo 27/07/2024
    public function actualizarchet($id_cabecera, $id_detalles)
    {
        //$query = "UPDATE reservas_detalle SET resdet_estado=8 WHERE fkcabecera_id = ? and resdet_id =?";
        $query = "UPDATE reservas_det SET Tbl_estado_solicitudes_idEstad_solicitud=1 WHERE Tbl_reservas_cab_idReserva_cab = ? and Idreserva_det =?";
        $datos = array($id_cabecera, $id_detalles);
        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "ok";
        } else {
            $res = "error";
        }
        return $res;
    }
}



