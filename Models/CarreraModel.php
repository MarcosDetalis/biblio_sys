<?php
/** CRUD de carreras académicas. */
class CarreraModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getCarrera(){return $this->selectAll("SELECT id_carrera,nombre AS carrera,descripcion,activo AS estado FROM carreras ORDER BY nombre");}
    public function insertarCarrera($carrera){if($this->selectPrepared("SELECT id_carrera FROM carreras WHERE nombre=? LIMIT 1",[$carrera]))return'existe';return $this->save("INSERT INTO carreras(nombre,activo) VALUES(?,1)",[$carrera])?'ok':'error';}
    public function editCarrera($id){return $this->selectPrepared("SELECT id_carrera,nombre AS carrera,descripcion,activo AS estado FROM carreras WHERE id_carrera=?",[$id]);}
    public function actualizarCarrera($carrera,$id){return $this->save("UPDATE carreras SET nombre=? WHERE id_carrera=?",[$carrera,$id])?'modificado':'error';}
    public function estadoCarrera($estado,$id){return $this->save("UPDATE carreras SET activo=? WHERE id_carrera=?",[$estado,$id]);}
}
?>