<?php
/** CRUD de autores. */
class AutorModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getAutor(){return $this->selectAll("SELECT id_autor AS Idautor,nombres AS Autor_nombres,apellidos AS Autor_apellidos,nacionalidad AS Autor_pais,activo AS Autor_estado,CONCAT(nombres,' ',apellidos) AS nombre_completo FROM autores ORDER BY apellidos,nombres");}
    public function insertarAutor($nombres,$apellidos,$pais){if($this->selectPrepared("SELECT id_autor FROM autores WHERE nombres=? AND apellidos=? LIMIT 1",[$nombres,$apellidos]))return'existe';return $this->save("INSERT INTO autores(nombres,apellidos,nacionalidad,activo) VALUES(?,?,?,1)",[$nombres,$apellidos,$pais])?'ok':'error';}
    public function editAutor($id){return $this->selectPrepared("SELECT id_autor AS Idautor,nombres AS Autor_nombres,apellidos AS Autor_apellidos,nacionalidad AS Autor_pais,activo AS Autor_estado FROM autores WHERE id_autor=?",[$id]);}
    public function actualizarAutor($nombres,$apellidos,$pais,$id){$dup=$this->selectPrepared("SELECT id_autor FROM autores WHERE nombres=? AND apellidos=? AND id_autor<>? LIMIT 1",[$nombres,$apellidos,$id]);if($dup)return'existe';return $this->save("UPDATE autores SET nombres=?,apellidos=?,nacionalidad=? WHERE id_autor=?",[$nombres,$apellidos,$pais,$id])?'modificado':'error';}
    public function estadoAutor($estado,$id){return $this->save("UPDATE autores SET activo=? WHERE id_autor=?",[$estado,$id]);}
    public function buscarAutor($valor){$v='%'.$valor.'%';return $this->selectAllPrepared("SELECT id_autor AS id,CONCAT(nombres,' ',apellidos) AS text FROM autores WHERE activo=1 AND (nombres LIKE ? OR apellidos LIKE ? OR CONCAT(nombres,' ',apellidos) LIKE ?) ORDER BY apellidos,nombres LIMIT 20",[$v,$v,$v]);}
}
?>