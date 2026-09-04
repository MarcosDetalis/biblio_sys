<?php
class Estudiantes extends Controller
{
    // Acciones de solo lectura que también usan Reservas y Solicitud (Prestamos)
    // para el buscador de estudiantes (Select2). No deben exigir permiso total
    // de administración de estudiantes, sino permiso de cualquiera de los
    // módulos que necesitan seleccionar un estudiante.
    private static $accionesBusqueda = ['buscarEstudiante'];

    public function __construct(){
        if(session_status()===PHP_SESSION_NONE)session_start();
        if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}
        parent::__construct();
        $segmentos = explode('/', trim($_GET['url'] ?? '', '/'));
        $metodo = $segmentos[1] ?? 'index';
        if(in_array($metodo, self::$accionesBusqueda, true)){
            $autorizado = $this->model->verificarPermisos($_SESSION['id_usuario'],'Estudiantes')
                || $this->model->verificarPermisos($_SESSION['id_usuario'],'Reservas')
                || $this->model->verificarPermisos($_SESSION['id_usuario'],'Prestamos');
            if(!$autorizado){http_response_code(403);echo json_encode([]);exit;}
            return;
        }
        if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Estudiantes')){$this->views->getView($this,'permisos');exit;}
    }
    public function index(){$this->views->getView($this,'index');}
    public function listar(){
        try{
            $data=$this->model->getEstudiantes();
            foreach($data as &$r){
                $id=(int)$r['Idusuario'];
                $activo=(int)$r['Usuario_estado']===1;
                $r['Usuario_estado']=$activo?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>';
                $r['acciones']=$activo?'<button class="btn btn-primary mr-1" onclick="btnEditarEst('.$id.')"><i class="fa fa-pencil-square-o"></i></button><button class="btn btn-danger" onclick="btnEliminarEst('.$id.')"><i class="fa fa-trash-o"></i></button>':'<button class="btn btn-success" onclick="btnReingresarEst('.$id.')"><i class="fa fa-reply-all"></i></button>';
            }
            echo json_encode(array_values($data),JSON_UNESCAPED_UNICODE);exit;
        }catch(Throwable $e){http_response_code(500);echo json_encode(['error'=>'No fue posible cargar los estudiantes'],JSON_UNESCAPED_UNICODE);exit;}
    }
    public function registrar(){
        $nombres=trim($_POST['nombres']??'');
        $apellidos=trim($_POST['apellidos']??'');
        $carrera=(int)($_POST['id_carrera']??0);
        $correo=trim($_POST['correo']??'');
        $cedula=trim($_POST['cedula']??'');
        $telefono=trim($_POST['telefono']??'');
        $id=(int)($_POST['Idusuario']??0);
        if($nombres===''||$apellidos===''||$carrera<=0||$correo===''||$cedula===''||$telefono===''){echo json_encode(['msg'=>'Todos los campos son requeridos','icono'=>'warning']);exit;}
        $d=$id===0?$this->model->insertarEstudiante($nombres,$apellidos,$carrera,$correo,$cedula,$telefono):$this->model->actualizarEstudiante($nombres,$apellidos,$carrera,$correo,$cedula,$telefono,$id);
        $m=['ok'=>['Estudiante registrado. Contraseña inicial: 123456','success'],'cedula_existe'=>['La cédula ya está registrada','warning'],'correo_existe'=>['El correo ya está registrado','warning'],'existe'=>['La cédula o el correo ya existe','warning'],'modificado'=>['Estudiante modificado','success']];
        $r=$m[$d]??['Error al guardar el estudiante','error'];echo json_encode(['msg'=>$r[0],'icono'=>$r[1]],JSON_UNESCAPED_UNICODE);exit;
    }
    public function editar($id){echo json_encode($this->model->editEstudiante((int)$id),JSON_UNESCAPED_UNICODE);exit;}
    public function eliminar($id){$ok=$this->model->estadoEstudiante(0,(int)$id);echo json_encode(['msg'=>$ok?'Estudiante dado de baja':'Error al eliminar','icono'=>$ok?'success':'error']);exit;}
    public function reingresar($id){$ok=$this->model->estadoEstudiante(1,(int)$id);echo json_encode(['msg'=>$ok?'Estudiante restaurado':'Error al restaurar','icono'=>$ok?'success':'error']);exit;}
    public function buscarEstudiante(){echo json_encode($this->model->buscarEstudiante(trim($_GET['est']??'')),JSON_UNESCAPED_UNICODE);exit;}
}
?>