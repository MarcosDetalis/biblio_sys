<?php
/** CRUD de editoriales. */
class EditorialModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getEditorial(){return $this->selectAll("SELECT id_editorial AS Ideditorial,nombre AS Editorial_descripcion,ciudad,pais,sitio_web,activo AS edi_estado FROM editoriales ORDER BY nombre");}
    public function insertarEditorial($editorial){if($this->selectPrepared("SELECT id_editorial FROM editoriales WHERE nombre=? LIMIT 1",[$editorial]))return'existe';return $this->save("INSERT INTO editoriales(nombre,activo) VALUES(?,1)",[$editorial])?'ok':'error';}
    public function editEditorial($id){return $this->selectPrepared("SELECT id_editorial AS Ideditorial,nombre AS Editorial_descripcion,ciudad,pais,sitio_web,activo AS edi_estado FROM editoriales WHERE id_editorial=?",[$id]);}
    public function actualizarEditorial($editorial,$id){return $this->save("UPDATE editoriales SET nombre=? WHERE id_editorial=?",[$editorial,$id])?'modificado':'error';}
    public function estadoEditorial($estado,$id){return $this->save("UPDATE editoriales SET activo=? WHERE id_editorial=?",[$estado,$id]);}
    public function buscarEditorial($valor){return $this->selectAllPrepared("SELECT id_editorial AS Ideditorial,nombre AS text FROM editoriales WHERE activo=1 AND nombre LIKE ? LIMIT 10",['%'.$valor.'%']);}
}
?>