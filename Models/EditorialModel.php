<?php
class EditorialModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getEditorial()
    {
        $sql = "SELECT * FROM editoriales";
        $res = $this->selectAll($sql);
        return $res;
    }
    public function insertarEditorial($editorial)
    {
        $verificar = "SELECT * FROM editoriales WHERE Editorial_descripcion = '$editorial'";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO editoriales(Editorial_descripcion) VALUES (?)";
            $datos = array($editorial);
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
    public function editEditorial($id)
    {
        $sql = "SELECT * FROM editoriales WHERE Ideditorial = $id";
        $res = $this->select($sql);
        return $res;
    }
    public function actualizarEditorial($editorial, $id)
    {
        $query = "UPDATE editoriales SET Editorial_descripcion = ? WHERE Ideditorial = ?";
        $datos = array($editorial, $id);
        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    } 
    
    public function estadoEditorial($estado, $id)
    {
        $query = "UPDATE editoriales SET edi_estado = ? WHERE Ideditorial = ?";
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
        $sql = "SELECT Ideditorial, Editorial_descripcion AS text FROM editoriales WHERE Editorial_descripcion LIKE '%" . $valor . "%'  AND edi_estado = 1 LIMIT 10";
        $data = $this->selectAll($sql);
        return $data;
    } 
}
