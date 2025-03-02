<?php
class UsuariosModel extends Query{
    // agergo variables tiposus y carrera dein inputs de vista hidden
    private $usuario, $nombre, $clave, $id, $estado,$tipousu,$carrera;
    public function __construct()
    {
        parent::__construct();
    }
    // actualmente no se utiliza
    public function getUsuario($usuario, $clave)
    {
        $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$clave' AND estado = 1";
        $data = $this->select($sql);
        return $data;
    }
    
    public function getUsuarios()
    {
        $sql = "SELECT Idusuario,Tbl_tipo_usuarios_idTipo_usuario,Tbl_carreras_Idcarrera,Usuario_nombre_usuario,
        Usuario_nombre1,Usuario_estado  FROM usuarios WHERE Tbl_tipo_usuarios_idTipo_usuario=1 AND  Tbl_carreras_Idcarrera=3";
        $data = $this->selectAll($sql);
        return $data;
    }
    public function registrarUsuario($tipousu,$carrera,$usuario, $nombre, $clave)
    {
        $this->usuario = $usuario;
        $this->nombre = $nombre;
        $this->clave = $clave;
        $this->tipousu = $tipousu;
        $this->carrera = $carrera;
        $vericar = "SELECT * FROM usuarios WHERE Usuario_nombre_usuario = '$this->usuario'";
        $existe = $this->select($vericar);
        if (empty($existe)) {
            # code...
            $sql = "INSERT INTO usuarios(Tbl_tipo_usuarios_idTipo_usuario,Tbl_carreras_Idcarrera,Usuario_nombre_usuario,Usuario_nombre1,
             clave) VALUES (?,?,?,?,?)";
            $datos = array($this->tipousu,$this->carrera,$this->usuario, $this->nombre, $this->clave);
            $data = $this->save($sql, $datos);
            if ($data == 1) {
                $res = "ok";
            }else{
                $res = "error";
            }
        }else{
            $res = "existe";
        }
        return $res;
    }
    public function modificarUsuario($usuario, $nombre, $id)
    {
        $this->usuario = $usuario;
        $this->nombre = $nombre;
        $this->id = $id;
        $sql = "UPDATE usuarios SET Usuario_nombre_usuario = ?, Usuario_nombre1 = ? WHERE Idusuario = ?";
        $datos = array($this->usuario, $this->nombre, $this->id);
        $data = $this->save($sql, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    }

    // actulamente no se utiliza
    public function editarUser($id)
    {
        $sql = "SELECT * FROM usuarios WHERE id = $id";
        $data = $this->select($sql);
        return $data;
    }
    public function accionUser($estado, $id)
    {
        $this->id = $id;
        $this->estado = $estado;
        $sql = "UPDATE usuarios SET Usuario_estado = ? WHERE Idusuario = ?";
        $datos = array($this->estado, $this->id);
        $data = $this->save($sql, $datos);
        return $data;
    }
    public function getPermisos()
    {
        $sql = "SELECT * FROM permisos";
        $data = $this->selectAll($sql);
        return $data;
    }
    public function getDetallePermisos($id)
    {
        $sql = "SELECT * FROM detalles_permisos WHERE Tbl_Usuarios_idUsuario = $id";
        $data = $this->selectAll($sql);
        return $data;
    }
    public function deletePermisos($id)
    {
        $sql = "DELETE FROM detalles_permisos WHERE Tbl_Usuarios_idUsuario = ?";
        $datos = array($id);
        $data = $this->save($sql, $datos);
        return $data;
    }
    public function actualizarPermisos($usuario, $permiso)
    {
        $sql = "INSERT INTO detalles_permisos(Tbl_Usuarios_idUsuario, Tbl_permisos_id_permiso) VALUES (?,?)";
            $datos = array($usuario, $permiso);
            $data = $this->save($sql, $datos);
            if ($data == 1) {
                $res = "ok";
            } else {
                $res = "error";
            }
        return $res;
    }
    // comentado temporalmente
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


    // actualmente no se utiliza
    public function actualizarPass($clave, $id)
    {
        $sql = "UPDATE usuarios SET clave = ? WHERE id = ?";
        $datos = array($clave, $id);
        $data = $this->save($sql, $datos);
        if ($data == 1) {
            $res = "modificado";
        } else {
            $res = "error";
        }
        return $res;
    }
}
?>