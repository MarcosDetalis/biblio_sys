<?php
class CketnomasModel extends Query{
   
    public function __construct()
    {
        parent::__construct();
    }
    public function preUPDATE($id_mov_cab)
    {
         $query = "UPDATE prueba_vists SET estado=5 WHERE id_cab = ? AND estado !=4";
             $datos = array($id_mov_cab);
             $this->save($query, $datos);
          
    }
    public function Cancelar_todo($id_mov_cab)
    {
         $query = "UPDATE prueba_vists SET estado=5 WHERE id_cab = ? ";
             $datos = array($id_mov_cab);

             $data = $this->save($query, $datos);
             if ($data == 1) {
                 $res = "cancelado";
             } else {
                 $res = "error";
             }

           
        return $res;
          
    }


    public function actualizarchet($id_mov_cab, $id_mov_det)
    {
         $query = "UPDATE prueba_vists SET estado=4 WHERE id_cab = ? and id_det =?";
             $datos = array($id_mov_cab, $id_mov_det);
            $data = $this->save($query, $datos);
             if ($data == 1) {
                 $res = "ok";
             } else {
                 $res = "error";
             }

           
        return $res;
    }
  
}
?>