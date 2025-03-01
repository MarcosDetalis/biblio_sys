<?php
class EscanerQr extends Controller{
    public function __construct() {
        session_start();
        parent::__construct();
    }
    public function index()
    {
        if (empty($_SESSION['activo'])) {
            header("location: " . base_url);
        }
        
        
        $this->views->getView($this, "index");
    }
 
 
    public function obtenerDatos($id){

       $data=  $this->model->obtenerReservas($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
        //actulamente no se usa
    public function actualizarStock($id_libro){
        $data=  $this->model->actualizarStockLibro($id_libro);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }




// no se usa actualmente
    public function estadoInactivo(int $id_libro)
    {
        $data = $this->model->accionLibro(0, $id_libro);
        if ($data == 1) {
            $msg = array('msg' => 'Usuario dado de baja', 'icono' => 'success');
        }else{
            $msg = array('msg' => 'Error al eliminar', 'icono' => 'error');
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }


    
    public function cambiarEstadosChecks()
    {
        $id_cabecera = strClean($_POST['id_mov_cabecera']);
        $id_detalles = $_POST['id_detalle'];
       
        if ($id_detalles != "") {
            foreach ($id_detalles as $detalle) {
            $this->model->actualizarDetalle($id_cabecera);
            $data= $this->model->actualizarchet($id_cabecera, $detalle);
               
            }
        }else{
            $data= $this->model->Cancelar_todo($id_cabecera);  
        }
        echo json_encode( $data );
        die();
    }


}
