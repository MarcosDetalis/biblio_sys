<?php
/** CRUD de materias. En biblioteca_v3 las materias son un catálogo independiente. */
class MateriaModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getMaterias(){return $this->selectAll("SELECT id_materia AS Idmateria,nombre AS Materia_descripcion,activo AS materia_estado FROM materias ORDER BY nombre");}
    public function insertarMateria($materia){if($this->selectPrepared("SELECT id_materia FROM materias WHERE nombre=? LIMIT 1",[$materia]))return'existe';return $this->save("INSERT INTO materias(nombre,activo) VALUES(?,1)",[$materia])?'ok':'error';}
    public function editMateria($id){return $this->selectPrepared("SELECT id_materia AS Idmateria,nombre AS Materia_descripcion,activo AS materia_estado FROM materias WHERE id_materia=?",[$id]);}
    public function actualizarMateria($materia,$id){$dup=$this->selectPrepared("SELECT id_materia FROM materias WHERE nombre=? AND id_materia<>? LIMIT 1",[$materia,$id]);if($dup)return'existe';return $this->save("UPDATE materias SET nombre=? WHERE id_materia=?",[$materia,$id])?'modificado':'error';}
    public function estadoMateria($estado,$id){return $this->save("UPDATE materias SET activo=? WHERE id_materia=?",[$estado,$id]);}
    public function buscarMateria($valor){return $this->selectAllPrepared("SELECT id_materia AS id,nombre AS text FROM materias WHERE activo=1 AND nombre LIKE ? LIMIT 20",['%'.$valor.'%']);}
}
?>