<?php
/** Reservas, préstamos y devoluciones. */
class Prestamos extends Controller
{
    public function __construct(){if(session_status()===PHP_SESSION_NONE)session_start();if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}parent::__construct();if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'Prestamos')){$this->views->getView($this,'permisos');exit;}}
    public function index(){$this->views->getView($this,'index');}
    public function listar(){
        $data=$this->model->getPrestamos();
        foreach($data as &$r){$id=(int)$r['Idreserva_cab'];$estado=(int)$r['Tbl_Estados_solicitudes_idEstado_solicitud'];$r['fecha_devuelto']=$r['fecha_cancelacion']??'';
            if($estado===1||$estado===2){$r['Tbl_Estados_solicitudes_idEstado_solicitud']='<span class="badge badge-warning">'.$r['Estado_solicitud_descripcion'].'</span>';$r['acciones']='<button class="btn btn-primary mr-1" onclick="btnEntregar('.$id.')"><i class="fa fa-check"></i></button>';}elseif($estado===6){$r['Tbl_Estados_solicitudes_idEstado_solicitud']='<span class="badge badge-success">Finalizada</span>';$r['acciones']='<button class="btn btn-success" onclick="btnEstadoDevuelto('.$id.')"><i class="fa fa-undo"></i></button>';}else{$r['Tbl_Estados_solicitudes_idEstado_solicitud']='<span class="badge badge-secondary">'.$r['Estado_solicitud_descripcion'].'</span>';$r['acciones']='';}}
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;
    }
    public function registrar(){
        $libro=(int)($_POST['libro']??0);$estudiante=(int)($_POST['estudiante']??0);$cantidad=(int)($_POST['cantidad']??0);$fecha=$_POST['fecha_devolucion']??'';$obs=trim($_POST['observacion']??'');
        if($libro<=0||$estudiante<=0||$cantidad<=0||$fecha===''){echo json_encode(['msg'=>'Todos los campos son requeridos','icono'=>'warning']);exit;}
        $d=$this->model->getCantLibro($libro);if(!$d||((int)$d['disponibles']<$cantidad)){echo json_encode(['msg'=>'No hay suficientes ejemplares disponibles','icono'=>'warning']);exit;}
        $id=$this->model->insertarPrestamo($estudiante,$libro,$cantidad,date('Y-m-d'),$fecha,$obs);
        echo json_encode($id?['msg'=>'Préstamo registrado','icono'=>'success','id'=>$id]:['msg'=>'No fue posible registrar el préstamo','icono'=>'error']);exit;
    }
    public function entregar($id){echo json_encode(['msg'=>'Esta acción quedó reemplazada por el Escáner QR (retiro/devolución parcial). Usá esa pantalla.','icono'=>'warning']);exit;}
    public function activarPrestamo($id){echo json_encode(['msg'=>'Esta acción quedó reemplazada por el Escáner QR (retiro/devolución parcial). Usá esa pantalla.','icono'=>'warning']);exit;}
    public function devolucionPrestamo($id){echo json_encode(['msg'=>'Esta acción quedó reemplazada por el Escáner QR (retiro/devolución parcial). Usá esa pantalla.','icono'=>'warning']);exit;}
    public function pdf(){
        $datos=$this->model->selectDatos();$prestamo=$this->model->selectPrestamoDebe();if(empty($prestamo)){header('Location: '.base_url.'Configuracion/vacio');exit;}
        require_once 'Libraries/pdf/fpdf.php';$pdf=new FPDF('P','mm','letter');$pdf->AddPage();$pdf->SetMargins(10,10,10);$pdf->SetTitle('Préstamos');$pdf->SetFont('Arial','B',12);$pdf->Cell(195,5,utf8_decode($datos['valor']??'Biblioteca Universitaria'),0,1,'C');$pdf->Ln();$pdf->SetFont('Arial','B',10);
        // Anchos ajustados para que la fecha (datetime completo, ej. "2026-08-21 01:01")
        // entre sin desbordarse sobre la columna de Cantidad. N°14 + Usuario50 + Libro70 + Fecha40 + Cant15 = 189mm.
        $pdf->Cell(14,5,'N°',1,0);$pdf->Cell(50,5,'Usuario',1,0);$pdf->Cell(70,5,'Libro',1,0);$pdf->Cell(40,5,'Fecha',1,0,'C');$pdf->Cell(15,5,'Cant.',1,1,'C');
        $pdf->SetFont('Arial','',9);$i=1;
        foreach($prestamo as $r){
            $fecha=$r['fecha_prestamo']?date('Y-m-d H:i',strtotime($r['fecha_prestamo'])):'';
            $pdf->Cell(14,5,$i++,1,0);
            $pdf->Cell(50,5,utf8_decode(strlen($r['nombre']??'')>28?substr($r['nombre'],0,25).'...':($r['nombre']??'')),1,0);
            $pdf->Cell(70,5,utf8_decode(strlen($r['titulo']??'')>42?substr($r['titulo'],0,39).'...':($r['titulo']??'')),1,0);
            $pdf->Cell(40,5,$fecha,1,0,'C');
            $pdf->Cell(15,5,$r['cantidad'],1,1,'C');
        }
        $pdf->Output('prestamos.pdf','I');
    }
    public function ticked($id_prestamo){$prestamo=$this->model->getPrestamoLibro((int)$id_prestamo);if(empty($prestamo)){header('Location: '.base_url.'Configuracion/vacio');exit;}require_once 'Libraries/pdf/fpdf.php';$pdf=new FPDF('P','mm',[80,200]);$pdf->AddPage();$pdf->SetMargins(5,5,5);$pdf->SetFont('Arial','B',10);$pdf->Cell(70,5,'Biblioteca Universitaria',0,1,'C');$pdf->Ln();$pdf->SetFont('Arial','B',8);$pdf->Cell(60,5,'Libro',1,0);$pdf->Cell(10,5,'Cant.',1,1);$pdf->SetFont('Arial','',8);$pdf->Cell(60,5,utf8_decode($prestamo['titulo']),1,0);$pdf->Cell(10,5,$prestamo['cantidad'],1,1);$pdf->Ln();$pdf->Cell(70,5,'Usuario',0,1,'C');$pdf->Cell(70,5,utf8_decode($prestamo['nombre']),0,1,'C');$pdf->Cell(70,5,'Fecha: '.($prestamo['fecha_prestamo']?date('Y-m-d H:i',strtotime($prestamo['fecha_prestamo'])):''),0,1,'C');$pdf->Output('prestamo.pdf','I');}
}
?>