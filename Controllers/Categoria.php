<?php
/**
 * Gestión de categorías del catálogo (Tecnología, Literatura, Historia,
 * etc.). Reemplaza a "Materia" como clasificación de libros: es la que
 * realmente usa el catálogo del alumno/profesor para filtrar
 * (ver GET /api/libros/categorias del backend Node).
 */
class Categoria extends Controller
{
    public function __construct(){
        if(session_status()===PHP_SESSION_NONE)session_start();
        if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}
        parent::__construct();
        if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Categoria')){$this->views->getView($this,'permisos');exit;}
    }
    public function index(){$this->views->getView($this,'index');}
    public function listar(){try{
        $data=$this->model->getCategorias();
        foreach($data as &$r){$id=(int)$r['Idcategoria'];$activo=(int)$r['categoria_estado']===1;$r['categoria_estado']=$activo?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>';$r['acciones']=$activo?'<button class="btn btn-primary" onclick="btnEditarCat('.$id.')"><i class="fa fa-pencil-square-o"></i></button> <button class="btn btn-danger" onclick="btnEliminarCat('.$id.')"><i class="fa fa-trash-o"></i></button>':'<button class="btn btn-success" onclick="btnReingresarCat('.$id.')"><i class="fa fa-reply-all"></i></button>';}
        echo json_encode(array_values($data),JSON_UNESCAPED_UNICODE);exit;
    }catch(Throwable $e){error_log('Categoria/listar '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible cargar las categorías'],JSON_UNESCAPED_UNICODE);exit;}}
    public function registrar(){
        $categoria=trim($_POST['Categoria_descripcion']??'');$id=(int)($_POST['Idcategoria']??0);
        if($categoria===''){echo json_encode(['msg'=>'El nombre de la categoría es requerido','icono'=>'warning']);exit;}
        $d=$id===0?$this->model->insertarCategoria($categoria):$this->model->actualizarCategoria($categoria,$id);
        $m=['ok'=>['Categoría registrada','success'],'existe'=>['La categoría ya existe','warning'],'modificado'=>['Categoría modificada','success']];$r=$m[$d]??['Error al guardar la categoría','error'];echo json_encode(['msg'=>$r[0],'icono'=>$r[1]],JSON_UNESCAPED_UNICODE);exit;
    }
    public function editar($id){echo json_encode($this->model->editCategoria((int)$id),JSON_UNESCAPED_UNICODE);exit;}
    public function eliminar($id){$ok=$this->model->estadoCategoria(0,(int)$id);echo json_encode(['msg'=>$ok?'Categoría dada de baja':'No se puede desactivar: todavía hay libros activos con esta categoría','icono'=>$ok?'success':'warning']);exit;}
    public function reingresar($id){$ok=$this->model->estadoCategoria(1,(int)$id);echo json_encode(['msg'=>$ok?'Categoría restaurada':'Error al restaurar','icono'=>$ok?'success':'error']);exit;}
    public function buscarCategoria(){echo json_encode($this->model->buscarCategoria(trim($_GET['q']??'')),JSON_UNESCAPED_UNICODE);exit;}
}
?>
