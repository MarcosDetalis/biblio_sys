<?php
class PrestamosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getPrestamos()
    {
        
        $sql="SELECT rc.Idreserva_cab,u.Idusuario,u.Usuario_nombre1,u.Usuario_correo,u.Usuario_ci, rc.Tbl_Estados_solicitudes_idEstado_solicitud,e.Estado_solicitud_descripcion,rc.Reserva_cab_fecha_solicitud,rc.fecha_devuelto FROM usuarios u INNER JOIN reservas_cab rc INNER JOIN estados_solicitudes e WHERE rc.Tbl_usuarios_idUsuarios = u.Idusuario AND rc.Tbl_Estados_solicitudes_idEstado_solicitud=e.Idestado_solicitud";
        $res = $this->selectAll($sql);
        return $res;
    }
    public function insertarPrestamo($estudiante,$libro, $cantidad, string $fecha_prestamo, string $fecha_devolucion, string $observacion)
    {
        $verificar = "SELECT * FROM prestamo WHERE id_libro = '$libro' AND id_estudiante = $estudiante AND estado = 1";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO prestamo(id_estudiante, id_libro, fecha_prestamo, fecha_devolucion, cantidad, observacion) VALUES (?,?,?,?,?,?)";
            $datos = array($estudiante, $libro, $fecha_prestamo, $fecha_devolucion, $cantidad, $observacion);
            $data = $this->insert($query, $datos);
            if ($data > 0) {
                $lib = "SELECT * FROM libro WHERE id = $libro";
                $resLibro = $this->select($lib);
                $total = $resLibro['cantidad'] - $cantidad;
                $libroUpdate = "UPDATE libro SET cantidad = ? WHERE id = ?";
                $datosLibro = array($total, $libro);
                $this->save($libroUpdate, $datosLibro);
                $res = $data;
            } else {
                $res = 0;
            }
        } else {
            $res = "existe";
        }
        return $res;
    }

    // model nuevo
    public function estadoPrestamo($estado, $id)
{
    $query = "UPDATE reservas_cab SET Tbl_Estados_solicitudes_idEstado_solicitud = ? WHERE Idreserva_cab  = ?";
    $datos = array($estado, $id);
    $data = $this->save($query, $datos);
    return $data;
}
public function devueltoPrestamo($estado, $id)
{
    $query = "UPDATE reservas_cab SET Tbl_Estados_solicitudes_idEstado_solicitud = ? WHERE Idreserva_cab  = ?";
    $datos = array($estado, $id);
    $data = $this->save($query, $datos);
    return $data;
}
    public function actualizarPrestamo($estado,$fecha_devolucion,$id)
    {
        $sql = "UPDATE prestamo SET estado = ?, fecha_devolucion= ? WHERE id = ?";
        $datos = array($estado,$fecha_devolucion, $id);
        $data = $this->save($sql, $datos);
        if ($data == 1) {
            $lib = "SELECT * FROM prestamo WHERE id = $id";
            $resLibro = $this->select($lib);
            $id_libro = $resLibro['id_libro'];
            $lib = "SELECT * FROM libro WHERE id = $id_libro";
            $residLibro = $this->select($lib);
            $total = $residLibro['cantidad'] + $resLibro['cantidad'];
            $libroUpdate = "UPDATE libro SET cantidad = ? WHERE id = ?";
            $datosLibro = array($total, $id_libro);
            $this->save($libroUpdate, $datosLibro);
            $res = "ok";
        } else {
            $res = "error";
        }
        return $res;
    }
    public function selectDatos()
    {
        $sql = "SELECT * FROM configuracion";
        $res = $this->select($sql);
        return $res;
    }
    public function getCantLibro($libro)
    {
        $sql = "SELECT * FROM libro WHERE id = $libro";
        $res = $this->select($sql);
        return $res;
    }
    public function selectPrestamoDebe()
    {
        $sql = "SELECT e.id, e.nombre, l.id, l.titulo, p.id, p.id_estudiante, p.id_libro, p.fecha_prestamo, p.fecha_devolucion, p.cantidad, p.observacion, p.estado FROM estudiante e INNER JOIN libro l INNER JOIN prestamo p ON p.id_estudiante = e.id WHERE p.id_libro = l.id AND p.estado = 1 ORDER BY e.nombre ASC";
        $res = $this->selectAll($sql);
        return $res;
    }
    public function verificarPermisos($id_user, $permiso)
    {
        $tiene = false;
        $sql = "SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = $id_user AND p.nombre = '$permiso'";
        $existe = $this->select($sql);
        if ($existe != null || $existe != "") {
            $tiene = true;
        }
        return $tiene;
    }
    public function getPrestamoLibro($id_prestamo)
    {
        $sql = "SELECT e.id, e.codigo, e.nombre, e.carrera, l.id, l.titulo, p.id, p.id_estudiante, p.id_libro, p.fecha_prestamo, p.fecha_devolucion, p.cantidad, p.observacion, p.estado FROM estudiante e INNER JOIN libro l INNER JOIN prestamo p ON p.id_estudiante = e.id WHERE p.id_libro = l.id AND p.id = $id_prestamo";
        $res = $this->select($sql);
        return $res;
    }
}
