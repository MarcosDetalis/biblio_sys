<?php
class MateriaModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getMaterias()
    {
        
        $sql="SELECT m.Idmateria,c.Carrera_descripcion,m.Materia_descripcion,m.materia_estado FROM materias m INNER JOIN carreras c WHERE m.Tbl_carreras_idCarrera=c.Idcarrera";
        $res = $this->selectAll($sql);
        return $res;
    }
    public function insertarMateria($carrera,$materia)
    {
        $verificar = "SELECT * FROM materias WHERE Materia_descripcion = '$materia'";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO materias (Tbl_carreras_idCarrera,Materia_descripcion) VALUES (?,?)";
            $datos = array($carrera,$materia);
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

    public function editMateria($id)
    {
        $sql = "SELECT * FROM materias WHERE Idmateria = $id";
        $res = $this->select($sql);
        return $res;
    } 

    public function actualizarMateria($carrera,$materia, $id)
    {
        $query = "UPDATE materias SET Tbl_carreras_idCarrera=?, Materia_descripcion = ? WHERE Idmateria = ?";
        $datos = array($carrera,$materia, $id);
        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    }
    
    public function estadoMateria($estado, $id)
    {
        $query = "UPDATE materias SET materia_estado = ? WHERE Idmateria = ?";
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
   
    public function buscarMateria($valor)
    {
        $sql = "SELECT Idmateria, Materia_descripcion AS text FROM materias WHERE Materia_descripcion LIKE '%" . $valor . "%'  AND materia_estado = 1 LIMIT 10";
        $data = $this->selectAll($sql);
        return $data;
    }
}
