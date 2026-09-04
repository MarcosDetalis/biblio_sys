<?php
/**
 * Gestión de alumnos.
 *
 * En biblioteca_v3 un alumno es un registro de usuarios con
 * id_tipo_usuario = 1 y rol Alumno. La cédula es el identificador que
 * también puede utilizarse para iniciar sesión.
 */
class EstudiantesModel extends Query
{
    public function __construct(){ parent::__construct(); }

    public function getEstudiantes(){
        return $this->selectAll("SELECT
            u.id_usuario AS Idusuario,
            CONCAT(u.nombres,' ',u.apellidos) AS Usuario_nombre1,
            u.id_tipo_usuario AS Tbl_tipo_usuarios_idTipo_usuario,
            t.nombre AS tipo_usuario,
            u.id_carrera AS Tbl_carreras_Idcarrera,
            c.nombre AS carrera,
            u.correo AS Usuario_correo,
            u.cedula AS Usuario_ci,
            u.telefono AS Usuario_telefono,
            u.activo AS Usuario_estado
            FROM usuarios u
            LEFT JOIN tipos_usuario t ON t.id_tipo_usuario=u.id_tipo_usuario
            LEFT JOIN carreras c ON c.id_carrera=u.id_carrera
            WHERE u.id_tipo_usuario=1
            ORDER BY u.apellidos,u.nombres");
    }

    public function insertarEstudiante($nombres,$apellidos,$carrera,$correo,$cedula,$telefono){
        if($this->selectPrepared("SELECT id_usuario FROM usuarios WHERE cedula=? LIMIT 1",[$cedula])) return 'cedula_existe';
        if($this->selectPrepared("SELECT id_usuario FROM usuarios WHERE correo=? LIMIT 1",[$correo])) return 'correo_existe';
        try{
            $this->beginTransaction();
            $id=$this->insert("INSERT INTO usuarios(id_tipo_usuario,id_carrera,cedula,nombres,apellidos,correo,telefono,password_hash,activo)
                VALUES(1,?,?,?,?,?,?,?,1)",[
                    (int)$carrera,$cedula,$nombres,$apellidos,$correo,$telefono,password_hash('123456',PASSWORD_BCRYPT)
                ]);
            if(!$id){$this->rollback();return 'error';}
            $rol=$this->select("SELECT id_rol FROM roles WHERE nombre='Alumno' AND activo=1 LIMIT 1");
            if(!$rol){$this->rollback();return 'error';}
            $this->save("INSERT INTO usuario_rol(id_usuario,id_rol,activo) VALUES(?,?,1)",[$id,$rol['id_rol']]);
            $this->commit();
            return 'ok';
        }catch(Throwable $e){$this->rollback();error_log('EstudiantesModel::insertarEstudiante '.$e->getMessage());return 'error';}
    }

    public function editEstudiante($id){
        return $this->selectPrepared("SELECT
            u.id_usuario AS Idusuario,
            u.id_carrera AS Tbl_carreras_Idcarrera,
            u.correo AS Usuario_correo,
            u.cedula AS Usuario_ci,
            u.telefono AS Usuario_telefono,
            u.nombres AS nombres,
            u.apellidos AS apellidos,
            u.activo AS Usuario_estado
            FROM usuarios u WHERE u.id_usuario=? AND u.id_tipo_usuario=1",[$id]);
    }

    public function actualizarEstudiante($nombres,$apellidos,$carrera,$correo,$cedula,$telefono,$id){
        $dup=$this->selectPrepared("SELECT id_usuario FROM usuarios WHERE (correo=? OR cedula=?) AND id_usuario<>? LIMIT 1",[$correo,$cedula,$id]);
        if($dup){
            $c=$this->selectPrepared("SELECT id_usuario FROM usuarios WHERE cedula=? AND id_usuario<>? LIMIT 1",[$cedula,$id]);
            return $c ? 'cedula_existe' : 'correo_existe';
        }
        return $this->save("UPDATE usuarios SET nombres=?,apellidos=?,id_carrera=?,correo=?,cedula=?,telefono=? WHERE id_usuario=? AND id_tipo_usuario=1",
            [$nombres,$apellidos,$carrera,$correo,$cedula,$telefono,$id])?'modificado':'error';
    }

    public function estadoEstudiante($estado,$id){return $this->save("UPDATE usuarios SET activo=? WHERE id_usuario=? AND id_tipo_usuario=1",[$estado,$id]);}

    /**
     * Usado por el Select2 remoto de "estudiante" en Reservas y Solicitud
     * (Prestamos). Incluye Alumnos Y Profesores: ambos pueden reservar o
     * pedir préstamos, a diferencia del módulo "Estudiantes" (gestión CRUD),
     * que es exclusivamente para alumnos.
     */
    public function buscarEstudiante($valor){
        $valor='%'.$valor.'%';
        // Select2 necesita una propiedad id; antes se devolvía Idusuario,
        // por lo que el resultado se mostraba pero no podía seleccionarse.
        return $this->selectAllPrepared("SELECT
            u.id_usuario AS id,
            CONCAT(u.nombres,' ',u.apellidos,
                CASE WHEN u.id_tipo_usuario=2 THEN ' (Profesor)' ELSE '' END) AS text,
            u.correo,
            u.cedula
            FROM usuarios u
            WHERE u.activo=1 AND u.id_tipo_usuario IN (1,2)
              AND (u.correo LIKE ? OR u.cedula LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ? OR CONCAT(u.nombres,' ',u.apellidos) LIKE ?)
            ORDER BY u.apellidos,u.nombres LIMIT 20",
            [$valor,$valor,$valor,$valor,$valor]);
    }
}
?>