<?php
/** Panel principal, configuración y reportes. */
class Configuracion extends Controller
{
    public function __construct(){
        if(session_status()===PHP_SESSION_NONE)session_start();
        if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}
        parent::__construct();
    }
    public function index(){
        if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Configuracion')){$this->views->getView($this,'permisos');exit;}
        $this->views->getView($this,'index',$this->model->selectConfiguracion());
    }
    public function actualizar(){
        if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Configuracion')){echo json_encode(['msg'=>'No tienes permisos','icono'=>'error']);exit;}
        $nombre=trim($_POST['nombre']??'');$telefono=trim($_POST['telefono']??'');$direccion=trim($_POST['direccion']??'');$correo=trim($_POST['correo']??'');
        if($nombre===''||$telefono===''||$direccion===''||$correo===''){echo json_encode(['msg'=>'Todos los campos son requeridos','icono'=>'warning']);exit;}
        $img='logo.png';
        if(!empty($_FILES['imagen']['name'])&&is_uploaded_file($_FILES['imagen']['tmp_name'])){$ext=strtolower(pathinfo($_FILES['imagen']['name'],PATHINFO_EXTENSION));if(!in_array($ext,['jpg','jpeg','png'],true)){echo json_encode(['msg'=>'Formato de imagen no permitido','icono'=>'warning']);exit;}$img='logo_'.date('YmdHis').'.'.$ext;move_uploaded_file($_FILES['imagen']['tmp_name'],'Assets/img/'.$img);}
        $d=$this->model->actualizarConfig($nombre,$telefono,$direccion,$correo,$img,1);echo json_encode(['msg'=>$d==='modificado'?'Configuración actualizada':'Error al actualizar','icono'=>$d==='modificado'?'success':'error']);exit;
    }
    public function admin(){
        $res=$this->model->getResumen();
        $data=[
            'usuarios'=>['total'=>(int)$res['usuarios_activos']],
            'libros'=>['total'=>(int)$res['libros_activos']],
            'ejemplares'=>['total'=>(int)$res['ejemplares']],
            'disponibles'=>['total'=>(int)$res['disponibles']],
            'reservas'=>['total'=>(int)$res['reservas_activas']],
            'prestamos'=>['total'=>(int)$res['prestamos_activos']],
            'multas'=>['total'=>(int)$res['multas_pendientes']],
            'materias'=>$this->model->selectDatos('materias','activo'),
            'autores'=>$this->model->selectDatos('autores','activo'),
            'editoriales'=>$this->model->selectDatos('editoriales','activo'),
            // Antes esta tarjeta mostraba el total de TODOS los usuarios (admin +
            // bibliotecario + profesor + alumnos), no solo estudiantes.
            'estudiantes'=>['total'=>(int)$res['alumnos_activos']],
            'profesores'=>['total'=>(int)$res['profesores_activos']],
        ];
        $this->views->getView($this,'home',$data);
    }
    public function grafico(){echo json_encode($this->model->getReportes(),JSON_UNESCAPED_UNICODE);exit;}
    public function error(){$this->views->getView($this,'error');}
    public function vacio(){$this->views->getView($this,'vacio');}
    public function verificar(){echo json_encode($this->model->getVerificarPrestamos(date('Y-m-d')),JSON_UNESCAPED_UNICODE);exit;}
    public function libros(){
        $datos=$this->model->selectConfiguracion();$prestamo=$this->model->getVerificarPrestamos(date('Y-m-d'));if(empty($prestamo)){header('Location: '.base_url.'Configuracion/vacio');exit;}
        require_once 'Libraries/pdf/fpdf.php';$pdf=new FPDF('P','mm','letter');$pdf->AddPage();$pdf->SetMargins(10,10,10);$pdf->SetFont('Arial','B',12);$pdf->Cell(195,5,utf8_decode($datos['nombre']),0,1,'C');$pdf->Ln();$pdf->SetFont('Arial','B',10);$pdf->Cell(14,5,'N°',1,0);$pdf->Cell(60,5,'Usuario',1,0);$pdf->Cell(90,5,'Libro',1,0);$pdf->Cell(25,5,'Vencimiento',1,1);$pdf->SetFont('Arial','',9);$i=1;foreach($prestamo as $r){$pdf->Cell(14,5,$i++,1,0);$pdf->Cell(60,5,utf8_decode($r['nombre']),1,0);$pdf->Cell(90,5,utf8_decode($r['titulo']),1,0);$pdf->Cell(25,5,$r['fecha_prestamo'],1,1);} $pdf->Output('prestamos_vencidos.pdf','I');
    }
    public function notificaciones(){
        echo json_encode($this->model->notificaciones((int)$_SESSION['id_usuario'],$this->esStaff()),JSON_UNESCAPED_UNICODE);exit;
    }
}
