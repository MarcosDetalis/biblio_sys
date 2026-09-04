<?php
/** Catálogo: libros, autores, ejemplares y portadas. */
class Libros extends Controller
{
    public function __construct(){if(session_status()===PHP_SESSION_NONE)session_start();if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}parent::__construct();if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Libros')){$this->views->getView($this,'permisos');exit;}}
    public function index(){$this->views->getView($this,'index');}
    public function listar(){
        $data=$this->model->getLibros();foreach($data as &$r){$id=(int)$r['id'];$r['foto']=$r['imagen']?'<img class="img-thumbnail" src="'.htmlspecialchars($r['imagen']).'" width="80">':'';$activo=(int)$r['estado']===1;$r['estado']=$activo?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>';$r['acciones']=$activo?'<button class="btn btn-primary mr-1" onclick="btnEditarLibro('.$id.')"><i class="fa fa-pencil-square-o"></i></button><button class="btn btn-danger" onclick="btnEliminarLibro('.$id.')"><i class="fa fa-trash-o"></i></button>':'<button class="btn btn-success" onclick="btnReingresarLibro('.$id.')"><i class="fa fa-reply-all"></i></button>';}$data=array_values($data);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;
    }
    public function registrar(){
        $titulo=trim($_POST['titulo']??'');$autor=(int)($_POST['autor']??0);$editorial=(int)($_POST['editorial']??0);$categoria=(int)($_POST['categoria']??0);$cantidad=(int)($_POST['cantidad']??0);$paginas=(int)($_POST['num_pagina']??0);$anio=$_POST['anio_edicion']??'';$descripcion=trim($_POST['descripcion']??'');$id=(int)($_POST['id']??0);
        // Restricción del libro (tabla restricciones): por defecto 1 = "Sin restricción".
        $restriccion=(int)($_POST['restriccion']??1); if($restriccion<=0)$restriccion=1;
        if($titulo===''||$autor<=0||$editorial<=0||$categoria<=0||$cantidad<=0){echo json_encode(['msg'=>'Todos los campos obligatorios deben estar completos','icono'=>'warning']);exit;}
        $portada='Assets/img/libros/logo.png';
        $imagenUrl=trim($_POST['imagenUrl']??'');
        if($imagenUrl!=='' && filter_var($imagenUrl,FILTER_VALIDATE_URL)){
            // Portada subida a Cloudinary desde el navegador (ver fn_libro.js).
            // Es el camino normal: se guarda la URL tal cual, sin tocar el
            // disco del servidor (necesario porque en Clever Cloud el disco
            // es efímero y se pierde en cada redeploy).
            $portada=$imagenUrl;
        }elseif(!empty($_FILES['imagen']['name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])){
            $ext=strtolower(pathinfo($_FILES['imagen']['name'],PATHINFO_EXTENSION));if(!in_array($ext,['jpg','jpeg','png'],true)){echo json_encode(['msg'=>'Formato de imagen no permitido','icono'=>'warning']);exit;}
            $nombre=date('YmdHis').'_'.bin2hex(random_bytes(3)).'.'.$ext;$dir='Assets/img/libros/';if(!is_dir($dir))mkdir($dir,0755,true);move_uploaded_file($_FILES['imagen']['tmp_name'],$dir.$nombre);$portada=$dir.$nombre;
        }elseif(!empty($_POST['foto_actual'])){$portada=$_POST['foto_actual'];}
        $d=$id===0?$this->model->insertarLibros($titulo,$autor,$editorial,$categoria,$cantidad,$paginas,$anio,$descripcion,$portada,$restriccion):$this->model->actualizarLibros($titulo,$autor,$editorial,$categoria,$cantidad,$paginas,$anio,$descripcion,$portada,$id,$restriccion);
        $map=['ok'=>['Libro registrado','success'],'modificado'=>['Libro modificado','success'],'existe'=>['El libro ya existe','warning']];$r=$map[$d]??['Error al guardar el libro','error'];echo json_encode(['msg'=>$r[0],'icono'=>$r[1]]);exit;
    }
    /** Catálogo de restricciones (Sin restricción / Restringido) para el <select>. */
    public function restricciones(){echo json_encode($this->model->getRestricciones(),JSON_UNESCAPED_UNICODE);exit;}
    public function editar($id){echo json_encode($this->model->editLibros($id),JSON_UNESCAPED_UNICODE);exit;}
    public function eliminar($id){$ok=$this->model->estadoLibros(0,$id);echo json_encode(['msg'=>$ok?'Libro dado de baja':'Error al eliminar','icono'=>$ok?'success':'error']);exit;}
    public function reingresar($id){$ok=$this->model->estadoLibros(1,$id);echo json_encode(['msg'=>$ok?'Libro restaurado':'Error al restaurar','icono'=>$ok?'success':'error']);exit;}
    public function verificar($id_libro){$d=$this->model->cantidadDisponible((int)$id_libro);echo json_encode(['cantidad'=>(int)$d['cantidad'],'icono'=>(int)$d['cantidad']>0?'success':'warning','msg'=>(int)$d['cantidad']>0?'Libro disponible':'No hay ejemplares disponibles']);exit;}
    public function buscarLibro(){if(isset($_GET['lb']))echo json_encode($this->model->buscarLibro($_GET['lb']),JSON_UNESCAPED_UNICODE);exit;}
}
?>