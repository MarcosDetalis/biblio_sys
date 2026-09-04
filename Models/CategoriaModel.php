<?php
/**
 * CRUD de categorías (tabla `categorias`). Es la clasificación que usa
 * el catálogo del alumno/profesor para filtrar libros — reemplaza a
 * "Materia" en el formulario de libros.
 */
class CategoriaModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getCategorias(){return $this->selectAll("SELECT id_categoria AS Idcategoria,nombre AS Categoria_descripcion,activo AS categoria_estado FROM categorias ORDER BY nombre");}
    public function insertarCategoria($categoria){if($this->selectPrepared("SELECT id_categoria FROM categorias WHERE nombre=? LIMIT 1",[$categoria]))return'existe';return $this->save("INSERT INTO categorias(nombre,activo) VALUES(?,1)",[$categoria])?'ok':'error';}
    public function editCategoria($id){return $this->selectPrepared("SELECT id_categoria AS Idcategoria,nombre AS Categoria_descripcion,activo AS categoria_estado FROM categorias WHERE id_categoria=?",[$id]);}
    public function actualizarCategoria($categoria,$id){$dup=$this->selectPrepared("SELECT id_categoria FROM categorias WHERE nombre=? AND id_categoria<>? LIMIT 1",[$categoria,$id]);if($dup)return'existe';return $this->save("UPDATE categorias SET nombre=? WHERE id_categoria=?",[$categoria,$id])?'modificado':'error';}
    public function estadoCategoria($estado,$id){
        if((int)$estado===0){
            // No tiene sentido desactivar una categoría que todavía usa algún
            // libro activo: dejaría de aparecer en el filtro del catálogo sin
            // que el bibliotecario se dé cuenta de por qué.
            $enUso=$this->selectPrepared("SELECT 1 FROM libros WHERE id_categoria=? AND activo=1 LIMIT 1",[$id]);
            if($enUso) return false;
        }
        return $this->save("UPDATE categorias SET activo=? WHERE id_categoria=?",[$estado,$id]);
    }
    public function buscarCategoria($valor){return $this->selectAllPrepared("SELECT id_categoria AS id,nombre AS text FROM categorias WHERE activo=1 AND nombre LIKE ? LIMIT 20",['%'.$valor.'%']);}
}
?>
