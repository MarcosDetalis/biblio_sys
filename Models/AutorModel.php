<?php
class AutorModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getAutor()
    {
        $sql = "SELECT * FROM autores";
        $res = $this->selectAll($sql);
        return $res;
    }
// modificar
    public function insertarAutor($nombre,$pais)
    {
        $verificar = "SELECT * FROM autores WHERE Autor_nombres = '$nombre'";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO autores(Autor_nombres, Autor_pais) VALUES (?, ?)";
            $datos = array($nombre, $pais);
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
     public function editAutor($id)
     {
         $sql = "SELECT Idautor,Autor_nombres,Autor_pais,Autor_estado FROM autores WHERE Idautor = $id";
         $res = $this->select($sql);
         return $res;
     }
     public function actualizarAutor($nombre, $pais, $id)
     {
         $query = "UPDATE autores SET Autor_nombres = ?, Autor_pais = ? WHERE Idautor = ?";
         $datos = array($nombre, $pais ,$id);
         $data = $this->save($query, $datos);
         if ($data == 1) {
             $res = "modificado";
         } else {
             $res = "error";
         }
         return $res;
     }
     public function estadoAutor($estado, $id)
     {
         $query = "UPDATE autores SET Autor_estado = ? WHERE Idautor = ?";
         $datos = array($estado, $id);
         $data = $this->save($query, $datos);
         return $data;
     }

        //comentando anteiormente
    // public function verificarPermisos($id_user, $permiso)
    // {
    //     $tiene = false;
    //     $sql = "SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = $id_user AND p.nombre = '$permiso'";
    //     $existe = $this->select($sql);
    //     if ($existe != null || $existe != "") {
    //         $tiene = true;
    //     }
    //     return $tiene;
    // }
    public function buscarAutor($valor)
    {
        $sql = "SELECT Idautor, Autor_nombres AS text FROM Autores WHERE Autor_nombres LIKE '%" . $valor . "%'  AND Autor_estado = 1 LIMIT 10";
        $data = $this->selectAll($sql);
        return $data;
    }
}
