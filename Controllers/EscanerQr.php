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
 
//funcion para obtener datos de del ajax js 
    public function obtenerDatos($id){

       $data=  $this->model->obtenerReservas($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
        //aparatado nuevo 
    public function actualizarStock($id_libro){
        $data=  $this->model->actualizarStockLibro($id_libro);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

// OBSERVACION FUNCIONA PERO CUADO ACTIVA UN CON ID LIBRO ACTIVO TODO


//  controlador que inactivo el libro de la reserva
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
    // controlador  para cambiar de estado inactivo a activo del libro
     

    // // controlador para cambiar los estados a activos
    // public function cambiarEstadoActivo( $checkedItems)
    // {
    //     $checkedItemsArray = explode(',', $checkedItems);
    //     $data = $this->model->updateDetalle( $checkedItemsArray);
    //     if ($data == 1) {
    //         $msg = array('msg' => 'Libros Confirmados', 'icono' => 'success');
    //     } else {
    //         $msg = array('msg' => 'Error al restaurar el libro seleccionado', 'icono' => 'error');
    //     }
    //     echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    //     die();
    // }


    // controlador para cambiar estado nuevooooooooooooo 29/07/2024
    public function cambiarEstadosChecks()
    {
        $id_cabecera = strClean($_POST['id_mov_cabecera']);//verificar ya en el model hace referncia a una sola tabla
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
