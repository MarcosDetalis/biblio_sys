<?php
/** Controlador de autenticación, usuarios y permisos. */
class Usuarios extends Controller
{
    public function __construct(){if(session_status()===PHP_SESSION_NONE)session_start();parent::__construct();}
    public function index(){if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}$this->views->getView($this,'index');}
    public function listar(){
        if(empty($_SESSION['activo'])){http_response_code(401);exit;}
        try{
            // Administrador y Bibliotecario ven a todos; cualquier otro rol
            // (Alumno, Profesor) solo ve su propio registro.
            $data=$this->esStaff()?$this->model->getUsuarios():$this->model->getUsuarioPorId((int)$_SESSION['id_usuario']);
            foreach($data as &$row){
                $id=(int)$row['Idusuario'];$activo=(int)$row['Usuario_estado']===1;
                $row['estado']=$activo?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>';
                if($this->esStaff()){
                    $row['acciones']=$activo?'<div class="d-flex"><button class="btn btn-dark mr-1" onclick="btnRolesUser('.$id.')"><i class="fa fa-key"></i></button><button class="btn btn-primary mr-1" onclick="btnEditarUser('.$id.')"><i class="fa fa-pencil-square-o"></i></button><button class="btn btn-danger" onclick="btnEliminarUser('.$id.')"><i class="fa fa-trash-o"></i></button></div>':'<button class="btn btn-success" onclick="btnReingresarUser('.$id.')"><i class="fa fa-reply-all"></i></button>';
                }else{
                    // Un Alumno/Profesor puede ver sus propios datos, pero no
                    // desactivarse ni auto-asignarse permisos ni cambiarse el tipo.
                    $row['acciones']='<button class="btn btn-primary" onclick="btnEditarUser('.$id.')"><i class="fa fa-pencil-square-o"></i> Editar mis datos</button>';
                }
                $row['id']=$id;$row['usuario']=$row['correo'];$row['nombre']=$row['nombres'].' '.$row['apellidos'];
            }
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;
        }catch(Throwable $e){error_log('Usuarios/listar '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible cargar los usuarios'],JSON_UNESCAPED_UNICODE);exit;}
    }
    public function catalogos(){echo json_encode($this->model->getCatalogos(),JSON_UNESCAPED_UNICODE);exit;}
    public function validar(){ $usuario=trim($_POST['usuario']??'');$clave=$_POST['clave']??'';if($usuario===''||$clave===''){echo json_encode(['msg'=>'Todos los campos son requeridos','icono'=>'warning']);exit;}$data=$this->model->getUsuario($usuario,$clave);if($data){session_regenerate_id(true);$_SESSION['id_usuario']=(int)$data['id'];$_SESSION['usuario']=$data['usuario'];$_SESSION['nombre']=$data['nombre'];$_SESSION['rol']=$data['rol']??'';$_SESSION['activo']=true;$this->model->registrarAcceso($data['id']);
        // Vencimiento automático (ver docstring de marcarReservasVencidas): staff revisa
        // todo el sistema, cualquier otro rol revisa solamente sus propias reservas.
        $this->model->marcarReservasVencidas($this->esStaff()?null:(int)$data['id']);
        echo json_encode(['msg'=>'Procesando','icono'=>'success']);}else echo json_encode(['msg'=>'Usuario o contraseña incorrecta','icono'=>'warning']);exit;}
    public function registrar(){
        if(empty($_SESSION['activo'])){http_response_code(401);exit;}
        $tipo=(int)($_POST['id_tipo_usuario']??0);$carrera=(int)($_POST['id_carrera']??0);$cedula=trim($_POST['cedula']??'');$nombres=trim($_POST['nombres']??'');$apellidos=trim($_POST['apellidos']??'');$correo=trim($_POST['correo']??'');$telefono=trim($_POST['telefono']??'');$clave=$_POST['clave']??'';$confirmar=$_POST['confirmar']??'';$id=(int)($_POST['Idusuario']??0);

        if(!$this->esStaff()){
            // Crear un usuario nuevo es una acción administrativa.
            if($id===0){echo json_encode(['msg'=>'No tenés permiso para crear usuarios','icono'=>'error']);exit;}
            // Un Alumno/Profesor solo puede modificar su propio registro...
            if($id!==(int)$_SESSION['id_usuario']){echo json_encode(['msg'=>'No podés modificar los datos de otro usuario','icono'=>'error']);exit;}
            // ...y nunca puede cambiarse a sí mismo el Tipo (evita auto-escalar a Administrador).
            $actual=$this->model->getUsuarioPorId($id);
            $tipo=$actual?(int)($actual[0]['id_tipo_usuario']??$tipo):$tipo;
        }

        if($tipo<=0||$cedula===''||$nombres===''||$apellidos===''||$correo===''){echo json_encode(['msg'=>'Complete todos los campos obligatorios','icono'=>'warning']);exit;}
        // El Rol siempre se calcula a partir del Tipo (no se confía en el select del navegador,
        // que puede quedar desincronizado). Si un Tipo no tiene un Rol equivalente, se usa lo enviado.
        $rol=$this->model->obtenerRolPorTipo($tipo);
        if($rol<=0)$rol=(int)($_POST['id_rol']??0);
        if($rol<=0){echo json_encode(['msg'=>'No fue posible determinar el rol para el tipo de usuario seleccionado','icono'=>'warning']);exit;}
        if($tipo!==1)$carrera=0;
        if($id===0){if($clave===''||$clave!==$confirmar){echo json_encode(['msg'=>$clave===''?'La contraseña es requerida':'Las contraseñas no coinciden','icono'=>'warning']);exit;}$d=$this->model->registrarUsuario($tipo,$carrera,$cedula,$nombres,$apellidos,$correo,$telefono,$clave,$rol);}else{$d=$this->model->modificarUsuario($tipo,$carrera,$cedula,$nombres,$apellidos,$correo,$telefono,$id,$rol);}
        $m=['ok'=>['Usuario registrado','success'],'cedula_existe'=>['La cédula ya está registrada','warning'],'correo_existe'=>['El correo ya está registrado','warning'],'existe'=>['La cédula o el correo ya existe','warning'],'modificado'=>['Usuario modificado','success']];$r=$m[$d]??['Error al guardar el usuario','error'];echo json_encode(['msg'=>$r[0],'icono'=>$r[1]],JSON_UNESCAPED_UNICODE);exit;
    }
    public function editar(int $id){
        if(empty($_SESSION['activo'])){http_response_code(401);exit;}
        if(!$this->esStaff() && $id!==(int)$_SESSION['id_usuario']){http_response_code(403);echo json_encode(['error'=>'No autorizado']);exit;}
        echo json_encode($this->model->editarUser($id),JSON_UNESCAPED_UNICODE);exit;
    }
    // Dar de baja/reingresar cuentas es exclusivamente administrativo.
    public function eliminar(int $id){if(!$this->esStaff()){http_response_code(403);echo json_encode(['msg'=>'No autorizado','icono'=>'error']);exit;}echo json_encode($this->estadoUsuario(0,$id,'Usuario dado de baja'));exit;}
    public function reingresar(int $id){if(!$this->esStaff()){http_response_code(403);echo json_encode(['msg'=>'No autorizado','icono'=>'error']);exit;}echo json_encode($this->estadoUsuario(1,$id,'Usuario restaurado'));exit;}
    private function estadoUsuario($estado,$id,$msg){$ok=$this->model->accionUser($estado,$id);return['msg'=>$ok?$msg:'Error al actualizar','icono'=>$ok?'success':'error'];}
    // Asignar permisos es exclusivamente administrativo (si no, cualquiera podría auto-otorgarse permisos).
    public function permisos($id){
        if(!$this->esStaff()){http_response_code(403);echo 'No autorizado';exit;}
        $data=$this->model->getPermisos();$asignados=[];foreach($this->model->getDetallePermisos($id) as $a)$asignados[(int)$a['Tbl_permisos_id_permiso']]=true;echo '<div class="row"><input type="hidden" name="Tbl_Usuarios_idUsuario" value="'.(int)$id.'">';foreach($data as $row){$pid=(int)$row['Idpermiso'];echo '<div class="d-inline mx-3 text-center"><hr><label class="font-weight-bold">'.htmlspecialchars($row['Permiso_descripcion']).'</label><div><input type="checkbox" name="permisos[]" value="'.$pid.'" '.(isset($asignados[$pid])?'checked':'').'></div></div>';}echo '</div><button class="btn btn-primary mt-3 btn-block" type="button" onclick="registrarPermisos(event);">Actualizar</button>';exit;
    }
    public function registrarPermisos(){
        if(!$this->esStaff()){http_response_code(403);echo json_encode('No autorizado');exit;}
        $id=(int)($_POST['Tbl_Usuarios_idUsuario']??0);$permisos=$_POST['permisos']??[];$this->model->deletePermisos($id);foreach((array)$permisos as $p)$this->model->actualizarPermisos($id,(int)$p);echo json_encode('ok');exit;
    }
    public function cambiarPas(){ $id=(int)($_SESSION['id_usuario']??0);$actual=$_POST['clave_actual']??'';$nueva=$_POST['clave_nueva']??'';$confirm=$_POST['clave_confirmar']??'';$hashActual=$this->model->getPasswordHash($id);if(!$hashActual||!password_verify($actual,$hashActual)){echo json_encode(['msg'=>'Contraseña actual incorrecta','icono'=>'warning']);exit;}if($nueva===''||$nueva!==$confirm){echo json_encode(['msg'=>'Las contraseñas nuevas no coinciden','icono'=>'warning']);exit;}echo json_encode(['msg'=>$this->model->actualizarPass(password_hash($nueva,PASSWORD_BCRYPT),$id)==='modificado'?'Contraseña modificada':'Error al modificar','icono'=>'success']);exit;}
    public function salir(){if(session_status()===PHP_SESSION_NONE)session_start();$_SESSION=[];session_destroy();header('Location: '.base_url);exit;}

    /**
     * ============================================================
     * RECUPERACIÓN DE CONTRASEÑA MEDIANTE CORREO ELECTRÓNICO
     * ============================================================
     * Flujo público (no requiere sesión iniciada) en 3 pasos, usando
     * la tabla compartida password_resets (misma base MySQL que usa
     * backend-main en Node):
     *   1) recuperarSolicitar: cédula + correo -> genera y envía un
     *      código de 6 dígitos (vigente 10 min, máx. 3 intentos).
     *   2) recuperarVerificar: código -> entrega un token de un solo
     *      uso que habilita el paso 3.
     *   3) recuperarRestablecer: token + contraseña nueva.
     */
    private const RECUPERAR_CODIGO_MIN_VIGENCIA = 10;
    private const RECUPERAR_CODIGO_MAX_INTENTOS = 3;
    private const RECUPERAR_TOKEN_MIN_VIGENCIA = 10;

    /** Pantalla pública "¿Olvidaste tu contraseña?". */
    public function recuperar(){
        $this->views->getView($this,'recuperar');
    }

    public function recuperarSolicitar(){
        $cedula=trim($_POST['cedula']??'');
        $correo=trim($_POST['correo']??'');

        if($cedula===''||$correo===''){
            echo json_encode(['msg'=>'Ingresá tu cédula y tu correo','icono'=>'warning']);exit;
        }

        $usuario=$this->model->getUsuarioActivoPorCedulaCorreo($cedula,$correo);

        // Respuesta genérica a propósito: no confirma si la cédula/correo
        // pertenecen a una cuenta real, para no facilitar enumeración.
        if(!$usuario){
            echo json_encode(['msg'=>'Si los datos son correctos, te enviamos un código de verificación a tu correo registrado.','icono'=>'success']);exit;
        }

        $this->model->invalidarResetsPendientes((int)$usuario['id']);

        $codigo=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
        $codigoHash=hash('sha256',$codigo);

        $this->model->crearReset((int)$usuario['id'],$codigoHash,self::RECUPERAR_CODIGO_MIN_VIGENCIA);

        require_once __DIR__.'/../Libraries/Mailer.php';
        $enviado=Mailer::enviarCodigoRecuperacion($usuario['correo'],$codigo);

        if(!$enviado){
            echo json_encode(['msg'=>'No fue posible enviar el correo de verificación. Intentá nuevamente en unos minutos.','icono'=>'error']);exit;
        }

        echo json_encode(['msg'=>'Si los datos son correctos, te enviamos un código de verificación a tu correo registrado.','icono'=>'success']);exit;
    }

    public function recuperarVerificar(){
        $cedula=trim($_POST['cedula']??'');
        $correo=trim($_POST['correo']??'');
        $codigo=trim($_POST['codigo']??'');

        $errorGenerico=['msg'=>'Código inválido o vencido. Solicitá uno nuevo.','icono'=>'warning'];

        if($cedula===''||$correo===''||$codigo===''){
            echo json_encode(['msg'=>'Faltan datos para verificar el código','icono'=>'warning']);exit;
        }

        $usuario=$this->model->getUsuarioActivoPorCedulaCorreo($cedula,$correo);
        if(!$usuario){echo json_encode($errorGenerico);exit;}

        $reset=$this->model->getResetActivoPorUsuario((int)$usuario['id']);
        if(!$reset){echo json_encode($errorGenerico);exit;}

        if((int)$reset['intentos']>=self::RECUPERAR_CODIGO_MAX_INTENTOS){
            $this->model->invalidarReset((int)$reset['id']);
            echo json_encode(['msg'=>'Se agotaron los intentos. Solicitá un nuevo código.','icono'=>'warning']);exit;
        }

        if(!hash_equals($reset['codigo_hash'],hash('sha256',$codigo))){
            $intentosRestantes=self::RECUPERAR_CODIGO_MAX_INTENTOS-((int)$reset['intentos']+1);
            $this->model->incrementarIntentoReset((int)$reset['id']);
            if($intentosRestantes<=0){
                $this->model->invalidarReset((int)$reset['id']);
                echo json_encode(['msg'=>'Código incorrecto. Se agotaron los intentos, solicitá un nuevo código.','icono'=>'warning']);exit;
            }
            echo json_encode(['msg'=>"Código incorrecto. Te quedan {$intentosRestantes} intento(s).",'icono'=>'warning']);exit;
        }

        // Código correcto: se marca verificado y se emite un token de un
        // solo uso (de vida corta) que habilita el paso 3.
        $token=bin2hex(random_bytes(32));
        $tokenHash=hash('sha256',$token);
        $this->model->marcarResetVerificado((int)$reset['id'],$tokenHash,self::RECUPERAR_TOKEN_MIN_VIGENCIA);

        echo json_encode(['msg'=>'Código verificado','icono'=>'success','resetToken'=>$token],JSON_UNESCAPED_UNICODE);exit;
    }

    public function recuperarRestablecer(){
        $token=$_POST['resetToken']??'';
        $nueva=$_POST['passwordNueva']??'';
        $confirmar=$_POST['confirmarPassword']??'';

        if($token===''||$nueva===''){
            echo json_encode(['msg'=>'Faltan datos para restablecer la contraseña','icono'=>'warning']);exit;
        }
        if($nueva!==$confirmar){
            echo json_encode(['msg'=>'Las contraseñas no coinciden','icono'=>'warning']);exit;
        }
        if(strlen($nueva)<6){
            echo json_encode(['msg'=>'La nueva contraseña debe tener al menos 6 caracteres','icono'=>'warning']);exit;
        }

        $tokenHash=hash('sha256',$token);
        $reset=$this->model->getResetPorToken($tokenHash);

        if(!$reset){
            echo json_encode(['msg'=>'La verificación venció o ya fue utilizada. Solicitá un nuevo código.','icono'=>'warning']);exit;
        }

        $ok=$this->model->actualizarPass(password_hash($nueva,PASSWORD_BCRYPT),(int)$reset['id_usuario']);
        $this->model->invalidarReset((int)$reset['id']);

        echo json_encode(['msg'=>$ok==='modificado'?'Contraseña restablecida. Ya podés iniciar sesión.':'No fue posible actualizar la contraseña','icono'=>$ok==='modificado'?'success':'error']);exit;
    }
}
?>