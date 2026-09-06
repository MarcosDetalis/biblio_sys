<?php
/** Módulo de reservas de libros. */
class Reservas extends Controller
{
    public function __construct(){if(session_status()===PHP_SESSION_NONE)session_start();if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}parent::__construct();if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Reservas')){$this->views->getView($this,'permisos');exit;}}
    public function index(){$this->views->getView($this,'index');}
    public function listar(){echo json_encode($this->model->listar((int)$_SESSION['id_usuario'],$this->esStaff()),JSON_UNESCAPED_UNICODE);exit;}
    public function registrar(){
        $usuario=(int)($_POST['usuario']??0);$limite=$_POST['fecha_limite_retiro']??'';$obs=trim($_POST['observacion']??'');
        $devolucionEstimada=trim($_POST['fecha_devolucion_estimada']??'')?:null;
        // $_POST['libros'] y $_POST['cantidades'] llegan como arreglos paralelos:
        // libros[]=idLibro1&cantidades[]=2&libros[]=idLibro2&cantidades[]=1 ...
        $librosPost=$_POST['libros']??[];$cantidadesPost=$_POST['cantidades']??[];
        $items=[];
        foreach((array)$librosPost as $i=>$idLibro){
            $items[]=['id_libro'=>(int)$idLibro,'cantidad'=>(int)($cantidadesPost[$i]??1)];
        }
        if($usuario<=0||!$items||$limite===''||!$devolucionEstimada){echo json_encode(['msg'=>'Todos los campos son requeridos, incluida la fecha estimada de devolución','icono'=>'warning']);exit;}
        $r=$this->model->crear($usuario,$items,$limite,$obs,$devolucionEstimada);
        if($r==='sin_items'){echo json_encode(['msg'=>'Agregue al menos un libro con cantidad válida','icono'=>'warning']);exit;}
        if($r==='sin_devolucion'){echo json_encode(['msg'=>'La fecha estimada de devolución es obligatoria','icono'=>'warning']);exit;}
        if(str_starts_with((string)$r,'fuera_de_horario:')){$cual=substr($r,18)==='retiro'?'de retiro':'de devolución';echo json_encode(['msg'=>'La hora '.$cual.' debe estar entre 07:00 y 20:00','icono'=>'warning']);exit;}
        if(str_starts_with((string)$r,'no_existe:')){echo json_encode(['msg'=>'Uno de los libros seleccionados ya no existe','icono'=>'warning']);exit;}
        if(str_starts_with((string)$r,'excede_capacidad:')){[, $titulo, $total]=explode(':',$r,3);echo json_encode(['msg'=>'"'.$titulo.'" tiene '.$total.' ejemplar(es) en total, no se pueden pedir más que eso','icono'=>'warning']);exit;}
        if(str_starts_with((string)$r,'restringido:')){echo json_encode(['msg'=>'El usuario ya tiene una reserva pendiente de "'.substr($r,12).'" (máximo 1 por usuario, por ser un libro con restricción)','icono'=>'warning']);exit;}
        if(str_starts_with((string)$r,'sin_horario:')){
            [, $titulos, $sugerencia]=explode(':',$r,3);
            $msg='"'.$titulos.'" ya está comprometido en esa franja horaria.';
            $msg.=$sugerencia?' El próximo horario disponible ese día es a las '.$sugerencia.'.':' No hay otro horario disponible ese día, probá con otra fecha.';
            echo json_encode(['msg'=>$msg,'icono'=>'warning']);exit;
        }
        if($r==='error'){echo json_encode(['msg'=>'No fue posible crear la reserva','icono'=>'error']);exit;}
        echo json_encode(['msg'=>'Reserva creada. La disponibilidad real de cada ejemplar se confirma en el momento del retiro. Código QR: '.$r,'icono'=>'success','codigo_qr'=>$r]);exit;
    }

    /** Consulta en vivo (AJAX) para avisar antes de confirmar el formulario. */
    public function verificarHorario(){
        $limite=$_POST['fecha_limite_retiro']??'';$devolucionEstimada=$_POST['fecha_devolucion_estimada']??'';
        $librosPost=$_POST['libros']??[];$cantidadesPost=$_POST['cantidades']??[];
        $items=[];
        foreach((array)$librosPost as $i=>$idLibro){
            $items[]=['id_libro'=>(int)$idLibro,'cantidad'=>(int)($cantidadesPost[$i]??1)];
        }
        if(!$items||$limite===''||$devolucionEstimada===''){echo json_encode(['disponible'=>false,'motivo'=>'faltan_datos']);exit;}
        echo json_encode($this->model->verificarHorario($items,$limite,$devolucionEstimada),JSON_UNESCAPED_UNICODE);exit;
    }
    public function cancelar($id){
        $id=(int)$id;
        if(!$this->esStaff() && !$this->model->esDueno($id,(int)$_SESSION['id_usuario'])){
            echo json_encode(['msg'=>'No podés cancelar una reserva que no es tuya','icono'=>'error']);exit;
        }
        $r=$this->model->cancelar($id,(int)$_SESSION['id_usuario'],'Cancelada desde administración');echo json_encode(['msg'=>$r==='ok'?'Reserva cancelada':'No fue posible cancelar','icono'=>$r==='ok'?'success':'error']);exit;
    }
}
?>
