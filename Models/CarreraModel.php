<?php
class CarreraModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getCarrera()
    {
        $sql = "SELECT * FROM Carrera";
        $res = $this->selectAll($sql);
        return $res;
    }


    public function insertarCarrera($carrera)
    {
        $verificar = "SELECT * FROM Carrera WHERE carrera = '$carrera'";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO Carrera(carrera) VALUES (?)";
            $datos = array($carrera);
            $data = $this->save($query, $datos);
            if ($data == 1) {
                $res = "ok";
            } else {
                $res = "error";
            }
        } else {
            $res = "existe";
        }
        return $res;
    }





    public function editCarrera($id)
    {
        $sql = "SELECT * FROM Carrera WHERE id_carrera = $id";
        $res = $this->select($sql);
        return $res;
    }
    public function actualizarCarrera($carrera, $id)
    {
        $query = "UPDATE Carrera SET carrera = ? WHERE id_carrera = ?";
        $datos = array($carrera, $id);
        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    }
    public function estadoCarrera($estado, $id)
    {
        $query = "UPDATE Carrera SET estado = ? WHERE id_carrera = ?";
        $datos = array($estado, $id);
        $data = $this->save($query, $datos);
        return $data;
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
    public function buscarEditorial($valor)
    {
        $sql = "SELECT id, editorial AS text FROM editorial WHERE editorial LIKE '%" . $valor . "%'  AND estado = 1 LIMIT 10";
        $data = $this->selectAll($sql);
        return $data;
    }
}
