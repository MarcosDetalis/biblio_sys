<?php
class EstudiantesModel extends Query{
    public function __construct()
    {
        parent::__construct();
    }
  
    public function getEstudiantes()
    {
        $sql = "SELECT Idusuario,Usuario_nombre1,Tbl_tipo_usuarios_idTipo_usuario,Tbl_carreras_Idcarrera,Usuario_correo,Usuario_nombre_usuario,Usuario_ci,Usuario_estado  FROM usuarios WHERE Tbl_tipo_usuarios_idTipo_usuario=2";
        $res = $this->selectAll($sql);
        return $res;
    }

    public function insertarEstudiante($nombre,$tipousu,$carrera, $correo, $usunombre, $telefono)
    {
        $verificar = "SELECT * FROM usuarios WHERE Usuario_nombre1 = '$nombre'";
        $existe = $this->select($verificar);
        if (empty($existe)) {
            $query = "INSERT INTO usuarios (Usuario_nombre1,Tbl_tipo_usuarios_idTipo_usuario,Tbl_carreras_Idcarrera,Usuario_correo,Usuario_nombre_usuario,Usuario_ci) VALUES (?,?,?,?,?,?)";
            $datos = array($nombre,$tipousu, $carrera, $correo, $usunombre, $telefono);
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
   
    public function editEstudiante($id)
    {
        $sql = "SELECT  Idusuario,Usuario_nombre1,Tbl_tipo_usuarios_idTipo_usuario,Tbl_carreras_Idcarrera,Usuario_correo,Usuario_nombre_usuario,Usuario_ci   FROM usuarios WHERE Idusuario = $id";
        //$sql="SELECT * FROM usuarios WHERE Idusuario = $id";       
        $res = $this->select($sql);
        return $res;
    }
    
    public function actualizarEstudiante($nombre,$tipousu,$carrera, $correo, $usunombre, $telefono, $id)
    {
        $query = "UPDATE usuarios SET Usuario_nombre1 = ?,Tbl_tipo_usuarios_idTipo_usuario= ?, Tbl_carreras_Idcarrera = ?, Usuario_correo = ?, Usuario_nombre_usuario = ?, Usuario_ci = ? WHERE Idusuario = ?";
        $datos = array($nombre,$tipousu, $carrera, $correo, $usunombre, $telefono, $id);
        $data = $this->save($query, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    }
   
    public function estadoEstudiante($estado, $id)
    {
        $query = "UPDATE usuarios SET Usuario_estado = ? WHERE Idusuario  = ?";
        $datos = array($estado, $id);
        $data = $this->save($query, $datos);
        return $data;
    }

    public function buscarEstudiante($valor)
    {
        $sql = "SELECT Idusuario,Usuario_nombre_usuario, Usuario_nombre1  AS text FROM usuarios WHERE Usuario_nombre_usuario  LIKE '%" . $valor . "%' AND Usuario_estado = 1 OR Usuario_nombre1 LIKE '%" . $valor . "%'  AND Usuario_estado = 1 LIMIT 10";
        $data = $this->selectAll($sql);
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
}
