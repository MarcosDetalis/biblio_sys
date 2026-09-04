<?php
class Materia extends Controller
{
    public function __construct(){
        if(session_status()===PHP_SESSION_NONE)session_start();
        if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}
        parent::__construct();
        if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Materia')){$this->views->getView($this,'permisos');exit;}
    }
    public function index(){$this->views->getView($this,'index');}
    public function listar(){try{
        $data=$this->model->getMaterias();
        foreach($data as &$r){$id=(int)$r['Idmateria'];$activo=(int)$r['materia_estado']===1;$r['materia_estado']=$activo?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>';$r['acciones']=$activo?'<button class="btn btn-primary" onclick="btnEditarMat('.$id.')"><i class="fa fa-pencil-square-o"></i></button> <button class="btn btn-danger" onclick="btnEliminarMat('.$id.')"><i class="fa fa-trash-o"></i></button>':'<button class="btn btn-success" onclick="btnReingresarMat('.$id.')"><i class="fa fa-reply-all"></i></button>';}
        echo json_encode(array_values($data),JSON_UNESCAPED_UNICODE);exit;
    }catch(Throwable $e){error_log('Materia/listar '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible cargar las materias'],JSON_UNESCAPED_UNICODE);exit;}}
    public function registrar(){
        $materia=trim($_POST['Materia_descripcion']??'');$id=(int)($_POST['Idmateria']??0);
        if($materia===''){echo json_encode(['msg'=>'El nombre de la materia es requerido','icono'=>'warning']);exit;}
        $d=$id===0?$this->model->insertarMateria($materia):$this->model->actualizarMateria($materia,$id);
        $m=['ok'=>['Materia registrada','success'],'existe'=>['La materia ya existe','warning'],'modificado'=>['Materia modificada','success']];$r=$m[$d]??['Error al guardar la materia','error'];echo json_encode(['msg'=>$r[0],'icono'=>$r[1]],JSON_UNESCAPED_UNICODE);exit;
    }
    public function editar($id){echo json_encode($this->model->editMateria((int)$id),JSON_UNESCAPED_UNICODE);exit;}
    public function eliminar($id){$ok=$this->model->estadoMateria(0,(int)$id);echo json_encode(['msg'=>$ok?'Materia dada de baja':'Error al eliminar','icono'=>$ok?'success':'error']);exit;}
    public function reingresar($id){$ok=$this->model->estadoMateria(1,(int)$id);echo json_encode(['msg'=>$ok?'Materia restaurada':'Error al restaurar','icono'=>$ok?'success':'error']);exit;}
    public function buscarMateria(){echo json_encode($this->model->buscarMateria(trim($_GET['q']??'')),JSON_UNESCAPED_UNICODE);exit;}
}
?>