<?php
/** Gestión de usuarios, autenticación, roles y permisos. */
class UsuariosModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getUsuario($usuario,$clave=null){
        $data=$this->selectPrepared("SELECT u.id_usuario AS id,u.correo AS usuario,CONCAT(u.nombres,' ',u.apellidos) AS nombre,u.password_hash,u.activo,r.id_rol,r.nombre AS rol
            FROM usuarios u LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.activo=1 LEFT JOIN roles r ON r.id_rol=ur.id_rol AND r.activo=1
            WHERE (u.correo=? OR u.cedula=?) AND u.activo=1 LIMIT 1",[$usuario,$usuario]);
        if(!$data||$clave===null)return$data;return password_verify($clave,$data['password_hash'])?$data:null;
    }
    public function registrarAcceso($id):void{$this->save("UPDATE usuarios SET ultimo_acceso=NOW() WHERE id_usuario=?",[$id]);}
    public function getUsuarios(){return $this->selectAll("SELECT u.id_usuario AS Idusuario,u.cedula,u.id_tipo_usuario,t.nombre AS tipo_usuario,u.id_carrera,c.nombre AS carrera,u.correo,u.nombres,u.apellidos,u.telefono,u.activo AS Usuario_estado,COALESCE(GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.id_rol SEPARATOR ', '),'Sin rol') AS rol FROM usuarios u LEFT JOIN tipos_usuario t ON t.id_tipo_usuario=u.id_tipo_usuario LEFT JOIN carreras c ON c.id_carrera=u.id_carrera LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.activo=1 LEFT JOIN roles r ON r.id_rol=ur.id_rol GROUP BY u.id_usuario ORDER BY u.id_usuario");}
    /** Igual que getUsuarios() pero acotado a un solo id (Alumno/Profesor viendo solo su propio registro). */
    public function getUsuarioPorId($id){return $this->selectAllPrepared("SELECT u.id_usuario AS Idusuario,u.cedula,u.id_tipo_usuario,t.nombre AS tipo_usuario,u.id_carrera,c.nombre AS carrera,u.correo,u.nombres,u.apellidos,u.telefono,u.activo AS Usuario_estado,COALESCE(GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.id_rol SEPARATOR ', '),'Sin rol') AS rol FROM usuarios u LEFT JOIN tipos_usuario t ON t.id_tipo_usuario=u.id_tipo_usuario LEFT JOIN carreras c ON c.id_carrera=u.id_carrera LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.activo=1 LEFT JOIN roles r ON r.id_rol=ur.id_rol WHERE u.id_usuario=? GROUP BY u.id_usuario",[$id]);}
    public function getCatalogos(){return ['tipos'=>$this->selectAll("SELECT id_tipo_usuario AS id,nombre AS text FROM tipos_usuario WHERE activo=1 ORDER BY id_tipo_usuario"),'carreras'=>$this->selectAll("SELECT id_carrera AS id,nombre AS text FROM carreras WHERE activo=1 ORDER BY nombre"),'roles'=>$this->selectAll("SELECT id_rol AS id,nombre AS text FROM roles WHERE activo=1 ORDER BY id_rol")];}
    /**
     * "Tipo de usuario" (tipos_usuario) y "Rol" (roles) son catálogos
     * separados que comparten los mismos nombres (Alumno, Profesor,
     * Bibliotecario, Administrador) pero con IDs distintos. En vez de
     * confiar en que el navegador mantenga sincronizados los dos <select>,
     * el Rol correcto se resuelve siempre en el servidor a partir del Tipo,
     * por nombre. Así nunca puede quedar desincronizado, sin importar lo
     * que llegue en el POST.
     */
    public function obtenerRolPorTipo($idTipo){
        $r=$this->selectPrepared("SELECT r.id_rol FROM tipos_usuario t JOIN roles r ON r.nombre=t.nombre AND r.activo=1 WHERE t.id_tipo_usuario=? LIMIT 1",[$idTipo]);
        return $r?(int)$r['id_rol']:0;
    }
    public function registrarUsuario($tipo,$carrera,$cedula,$nombres,$apellidos,$correo,$telefono,$clave,$rol){
        if($this->selectPrepared("SELECT id_usuario FROM usuarios WHERE cedula=? LIMIT 1",[$cedula]))return'cedula_existe';
        if($this->selectPrepared("SELECT id_usuario FROM usuarios WHERE correo=? LIMIT 1",[$correo]))return'correo_existe';
        try{$this->beginTransaction();$id=$this->insert("INSERT INTO usuarios(id_tipo_usuario,id_carrera,cedula,nombres,apellidos,correo,telefono,password_hash,activo) VALUES(?,?,?,?,?,?,?,?,1)",[$tipo,$carrera?:null,$cedula,$nombres,$apellidos,$correo,$telefono,password_hash($clave,PASSWORD_BCRYPT)]);if(!$id){$this->rollback();return'error';}$this->save("INSERT INTO usuario_rol(id_usuario,id_rol,activo) VALUES(?,?,1)",[$id,$rol]);$this->commit();return'ok';}catch(Throwable $e){$this->rollback();error_log('UsuariosModel::registrarUsuario '.$e->getMessage());return'error';}
    }
    public function modificarUsuario($tipo,$carrera,$cedula,$nombres,$apellidos,$correo,$telefono,$id,$rol){
        $dup=$this->selectPrepared("SELECT id_usuario FROM usuarios WHERE (correo=? OR cedula=?) AND id_usuario<>? LIMIT 1",[$correo,$cedula,$id]);if($dup){$c=$this->selectPrepared("SELECT id_usuario FROM usuarios WHERE cedula=? AND id_usuario<>? LIMIT 1",[$cedula,$id]);return $c?'cedula_existe':'correo_existe';}
        try{$this->beginTransaction();$ok=$this->save("UPDATE usuarios SET id_tipo_usuario=?,id_carrera=?,cedula=?,nombres=?,apellidos=?,correo=?,telefono=? WHERE id_usuario=?",[$tipo,$carrera?:null,$cedula,$nombres,$apellidos,$correo,$telefono,$id]);if(!$ok){$this->rollback();return'error';}
            // Si el usuario ya tiene un rol distinto activo, filasRol=1 y basta con el UPDATE.
            // Si el UPDATE no afectó ninguna fila (no tenía rol activo, o ya tenía justo ese
            // mismo id_rol) hay que verificar explícitamente si existe la fila antes de decidir
            // si corresponde insertar una nueva; así nunca queda un usuario con el rol viejo.
            $filasRol=$this->saveFilas("UPDATE usuario_rol SET id_rol=? WHERE id_usuario=? AND activo=1",[$rol,$id]);
            if($filasRol===0){
                $activo=$this->selectPrepared("SELECT id_rol FROM usuario_rol WHERE id_usuario=? AND activo=1 LIMIT 1",[$id]);
                if(!$activo){
                    $this->save("INSERT INTO usuario_rol(id_usuario,id_rol,activo) VALUES(?,?,1)",[$id,$rol]);
                }
                // si $activo existe pero no se actualizó, ya tenía exactamente ese id_rol: nada que hacer.
            }
            $this->commit();return'modificado';}catch(Throwable $e){$this->rollback();error_log('UsuariosModel::modificarUsuario '.$e->getMessage());return'error';}
    }
    public function editarUser($id){return $this->selectPrepared("SELECT u.id_usuario AS Idusuario,u.id_tipo_usuario,u.id_carrera,u.cedula,u.nombres,u.apellidos,u.correo,u.telefono,ur.id_rol,u.activo FROM usuarios u LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.activo=1 WHERE u.id_usuario=?",[$id]);}
    public function accionUser($estado,$id){return $this->save("UPDATE usuarios SET activo=? WHERE id_usuario=?",[(int)$estado,$id]);}
    public function getPermisos(){return $this->selectAll("SELECT id_permiso AS Idpermiso,codigo,nombre AS Permiso_descripcion,descripcion,modulo,activo FROM permisos WHERE activo=1 ORDER BY modulo,codigo");}
    public function getDetallePermisos($id){return $this->selectAllPrepared("SELECT rp.id_permiso AS Tbl_permisos_id_permiso FROM usuario_rol ur INNER JOIN rol_permiso rp ON rp.id_rol=ur.id_rol WHERE ur.id_usuario=? AND ur.activo=1",[$id]);}
    public function deletePermisos($id){return $this->save("DELETE rp FROM rol_permiso rp INNER JOIN usuario_rol ur ON ur.id_rol=rp.id_rol WHERE ur.id_usuario=?",[$id]);}
    public function actualizarPermisos($usuario,$permiso){$rol=$this->selectPrepared("SELECT id_rol FROM usuario_rol WHERE id_usuario=? AND activo=1 LIMIT 1",[$usuario]);if(!$rol)return'error';return $this->save("INSERT INTO rol_permiso(id_rol,id_permiso) SELECT ?,? WHERE NOT EXISTS(SELECT 1 FROM rol_permiso WHERE id_rol=? AND id_permiso=?)",[$rol['id_rol'],$permiso,$rol['id_rol'],$permiso])?'ok':'error';}
    public function getPasswordHash($id){$r=$this->selectPrepared("SELECT password_hash FROM usuarios WHERE id_usuario=?",[$id]);return$r['password_hash']??null;}
    public function actualizarPass($clave,$id){return$this->save("UPDATE usuarios SET password_hash=? WHERE id_usuario=?",[$clave,$id])?'modificado':'error';}

    /**
     * Vencimiento automático de reservas, disparado desde el login (ver
     * Usuarios::validar()). $idUsuario=null revisa TODAS las reservas
     * del sistema (Administrador/Bibliotecario); un id puntual revisa
     * solamente las reservas de ese usuario (Alumno/Profesor).
     */
    public function marcarReservasVencidas($idUsuario=null):void{
        try{$this->call("CALL sp_marcar_reservas_vencidas(?)",[$idUsuario]);}
        catch(Throwable $e){error_log('marcarReservasVencidas: '.$e->getMessage());}
    }

    /**
     * ============================================================
     * RECUPERACIÓN DE CONTRASEÑA MEDIANTE CORREO ELECTRÓNICO
     * ============================================================
     * Usa la tabla compartida password_resets (misma base MySQL que
     * usa backend-main en Node), así que un código generado desde un
     * sistema puede verificarse/usarse desde el otro.
     */

    /** Usuario activo que coincide EXACTO con cédula + correo (verificación de identidad). */
    public function getUsuarioActivoPorCedulaCorreo($cedula,$correo){
        return $this->selectPrepared("SELECT id_usuario AS id, correo FROM usuarios WHERE cedula=? AND correo=? AND activo=1 LIMIT 1",[$cedula,$correo]);
    }

    /** Invalida cualquier código de recuperación sin usar que tenga el usuario, antes de generar uno nuevo. */
    public function invalidarResetsPendientes($idUsuario):void{
        $this->save("UPDATE password_resets SET usado=1 WHERE id_usuario=? AND usado=0",[$idUsuario]);
    }

    /** Guarda el código de verificación (ya hasheado con sha256) con su vigencia. */
    public function crearReset($idUsuario,$codigoHash,$minutosVigencia){
        return $this->insert("INSERT INTO password_resets(id_usuario,codigo_hash,expira_en) VALUES(?,?,DATE_ADD(NOW(),INTERVAL ? MINUTE))",[$idUsuario,$codigoHash,$minutosVigencia]);
    }

    /** Último código pendiente (no usado, no verificado, no vencido) de un usuario. */
    public function getResetActivoPorUsuario($idUsuario){
        return $this->selectPrepared("SELECT id_reset AS id, codigo_hash, intentos FROM password_resets WHERE id_usuario=? AND usado=0 AND verificado=0 AND expira_en>NOW() ORDER BY id_reset DESC LIMIT 1",[$idUsuario]);
    }

    public function incrementarIntentoReset($idReset):void{
        $this->save("UPDATE password_resets SET intentos=intentos+1 WHERE id_reset=?",[$idReset]);
    }

    public function invalidarReset($idReset):void{
        $this->save("UPDATE password_resets SET usado=1 WHERE id_reset=?",[$idReset]);
    }

    /** Marca el código como verificado y guarda el hash del token que habilita el paso 3. */
    public function marcarResetVerificado($idReset,$tokenHash,$minutosVigenciaToken):void{
        $this->save("UPDATE password_resets SET verificado=1, token_hash=?, expira_en=DATE_ADD(NOW(),INTERVAL ? MINUTE) WHERE id_reset=?",[$tokenHash,$minutosVigenciaToken,$idReset]);
    }

    /** Busca un reset verificado, no usado y vigente a partir del hash del token (paso 3). */
    public function getResetPorToken($tokenHash){
        return $this->selectPrepared("SELECT id_reset AS id, id_usuario FROM password_resets WHERE token_hash=? AND verificado=1 AND usado=0 AND expira_en>NOW() LIMIT 1",[$tokenHash]);
    }
}
?>