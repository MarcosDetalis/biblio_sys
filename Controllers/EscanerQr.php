<?php
/** Lector QR para convertir una reserva lista para retiro en préstamo. */
class EscanerQr extends Controller
{
    public function __construct(){if(session_status()===PHP_SESSION_NONE)session_start();if(empty($_SESSION['activo'])){header('Location: '.base_url);exit;}parent::__construct();if(!$this->model->verificarPermisos($_SESSION['id_usuario'],'EscanerQR')){$this->views->getView($this,'permisos');exit;}}
    public function index(){$this->views->getView($this,'index');}
    public function obtenerDatos($id){echo json_encode($this->model->obtenerReservas((int)$id),JSON_UNESCAPED_UNICODE);exit;}
    public function obtenerDatosQr($codigo){
        $r=$this->model->obtenerEstadoQr($codigo);
        if(!$this->esStaff() && isset($r['reserva']) && (int)$r['reserva']['id_usuario_dueno']!==(int)$_SESSION['id_usuario']){
            echo json_encode(['tipo'=>'error','motivo'=>'no_existe']);exit; // no revela que existe, solo que "no aplica" a este usuario
        }
        echo json_encode($r,JSON_UNESCAPED_UNICODE);exit;
    }
    // "Gestionar" desde el listado: mismo estado mixto que el QR, pero buscando directo por id_reserva.
    public function obtenerEstadoReserva($idReserva){
        $idReserva=(int)$idReserva;
        if(!$this->esStaff() && !$this->model->esDuenoReserva($idReserva,(int)$_SESSION['id_usuario'])){
            echo json_encode(['tipo'=>'error','motivo'=>'no_existe']);exit;
        }
        echo json_encode($this->model->obtenerEstadoPorReserva($idReserva),JSON_UNESCAPED_UNICODE);exit;
    }
    // Retiro parcial: solo staff (es una acción física del mostrador, no un autoservicio del alumno).
    public function confirmarRetiroParcial(){
        if(!$this->esStaff()){echo json_encode(['msg'=>'Solo el personal de biblioteca puede confirmar un retiro','icono'=>'error']);exit;}
        $idReserva=(int)($_POST['id_reserva']??0);
        $ids=array_map('intval',(array)($_POST['ids']??[]));
        $r=$this->model->confirmarRetiroParcial($idReserva,$ids,(int)$_SESSION['id_usuario']);
        $map=['sin_seleccion'=>['Seleccioná al menos un libro para retirar','warning'],'seleccion_invalida'=>['Esa selección ya no está disponible (puede que otro operador ya la haya procesado)','warning'],'error_reserva'=>['La reserva no existe','warning'],'error'=>['No fue posible generar el préstamo','error']];
        if(is_array($r)){$cancelados=(int)($r['cancelados']??0);echo json_encode(['msg'=>'Retiro confirmado y préstamo generado'.($cancelados>0?' ('.$cancelados.' libro'.($cancelados===1?'':'s').' cancelado'.($cancelados===1?'':'s').' automáticamente por no haberse tildado)':''),'icono'=>'success']);exit;}
        $m=$map[$r]??$map['error'];echo json_encode(['msg'=>$m[0],'icono'=>$m[1]]);exit;
    }
    // Devolución parcial: solo staff, misma razón.
    public function confirmarDevolucionParcial(){
        if(!$this->esStaff()){echo json_encode(['msg'=>'Solo el personal de biblioteca puede confirmar una devolución','icono'=>'error']);exit;}
        $ids=array_map('intval',(array)($_POST['ids']??[]));
        $r=$this->model->confirmarDevolucionParcial($ids);
        $map=['ok'=>['Devolución registrada','success'],'sin_seleccion'=>['Seleccioná al menos un libro para devolver','warning'],'seleccion_invalida'=>['Esa selección ya no está disponible','warning'],'error'=>['No fue posible registrar la devolución','error']];
        $m=$map[$r]??$map['error'];echo json_encode(['msg'=>$m[0],'icono'=>$m[1]]);exit;
    }
    // RF-01: listado filtrable por estado simplificado (Reserva/Retirado/Devuelto). Alumno/Profesor solo ven lo suyo.
    public function listar($estado=null){echo json_encode($this->model->listarTransacciones($estado?urldecode($estado):null,(int)$_SESSION['id_usuario'],$this->esStaff()),JSON_UNESCAPED_UNICODE);exit;}
    // RF-03: búsqueda por nombre de usuario, cédula, número de reserva o ID/número de préstamo (alternativa al QR). Igual, acotada al propio usuario si no es staff.
    public function buscar($texto){echo json_encode($this->model->buscarTransaccion(urldecode($texto),(int)$_SESSION['id_usuario'],$this->esStaff()),JSON_UNESCAPED_UNICODE);exit;}
    public function actualizarStock($id_libro){echo json_encode($this->model->actualizarStockLibro((int)$id_libro));exit;}
    public function estadoInactivo(int $id_libro){$d=$this->model->accionLibro(0,$id_libro);echo json_encode(['msg'=>$d?'Ejemplares actualizados':'Error al actualizar','icono'=>$d?'success':'error']);exit;}
    public function cambiarEstadosChecks(){echo json_encode(['msg'=>'Use el flujo de confirmación del QR','icono'=>'info']);exit;}

    // ---- Reporte de reservas (pendientes/retirados/devueltos/cancelados), filtrable. Solo staff. ----
    public function reportes(){
        if(!$this->esStaff()){$this->views->getView($this,'permisos');exit;}
        $this->views->getView($this,'reporte');
    }
    public function reporte(){
        if(!$this->esStaff()){http_response_code(403);echo json_encode(['error'=>'No autorizado']);exit;}
        $filtros=[
            'fecha_desde'=>$_GET['fecha_desde']??'',
            'fecha_hasta'=>$_GET['fecha_hasta']??'',
            'estado'=>$_GET['estado']??'',
            'tipo'=>$_GET['tipo']??'',
            'texto'=>$_GET['texto']??'',
        ];
        echo json_encode($this->model->reporte($filtros),JSON_UNESCAPED_UNICODE);exit;
    }
    public function reportePdf(){
        if(!$this->esStaff()){header('Location: '.base_url);exit;}
        $filtros=[
            'fecha_desde'=>$_GET['fecha_desde']??'',
            'fecha_hasta'=>$_GET['fecha_hasta']??'',
            'estado'=>$_GET['estado']??'',
            'tipo'=>$_GET['tipo']??'',
            'texto'=>$_GET['texto']??'',
        ];
        $datos=$this->model->reporte($filtros);
        if(empty($datos)){header('Location: '.base_url.'Configuracion/vacio');exit;}
        require_once 'Libraries/pdf/fpdf.php';
        $pdf=new FPDF('P','mm','letter');$pdf->AddPage();$pdf->SetMargins(10,10,10);$pdf->SetTitle('Reporte de reservas');
        $pdf->SetFont('Arial','B',12);$pdf->Cell(195,5,utf8_decode('Biblioteca Universitaria - Reporte de reservas'),0,1,'C');
        $sub=[];
        if($filtros['fecha_desde']||$filtros['fecha_hasta'])$sub[]='Fecha: '.($filtros['fecha_desde']?:'...').' a '.($filtros['fecha_hasta']?:'...');
        if($filtros['estado'])$sub[]='Estado: '.$filtros['estado'];
        if($filtros['tipo'])$sub[]='Tipo: '.($filtros['tipo']==1?'Alumno':'Profesor');
        if($filtros['texto'])$sub[]='Búsqueda: '.$filtros['texto'];
        if($sub){$pdf->SetFont('Arial','',9);$pdf->Cell(195,5,utf8_decode(implode(' | ',$sub)),0,1,'C');}
        $pdf->Ln();
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(38,5,'N Reserva',1,0);$pdf->Cell(42,5,'Usuario',1,0);$pdf->Cell(65,5,'Libros',1,0);$pdf->Cell(25,5,'Fecha',1,0,'C');$pdf->Cell(25,5,'Estado',1,1,'C');
        $pdf->SetFont('Arial','',8);
        foreach($datos as $r){
            $pdf->Cell(38,5,$r['numero_reserva'],1,0);
            $pdf->Cell(42,5,utf8_decode(recortarUtf8($r['usuario']??'',24)),1,0);
            $pdf->Cell(65,5,utf8_decode(recortarUtf8($r['libros']??'',39)),1,0);
            $pdf->Cell(25,5,date('Y-m-d',strtotime($r['fecha_reserva'])),1,0,'C');
            $pdf->Cell(25,5,utf8_decode($r['estado_simple']),1,1,'C');
        }
        $pdf->Output('reporte_reservas.pdf','I');
    }
}
?>
