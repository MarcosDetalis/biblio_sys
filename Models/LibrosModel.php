<?php
/** Catálogo de libros y creación de ejemplares físicos. */
class LibrosModel extends Query
{
    public function __construct(){parent::__construct();}
    public function getLibros(){
        return $this->selectAll("SELECT l.id_libro AS id,l.titulo,
            COUNT(e.id_ejemplar) AS cantidad,
            SUM(CASE WHEN e.id_estado_ejemplar=1 THEN 1 ELSE 0 END) AS disponibles,
            COALESCE(GROUP_CONCAT(DISTINCT CONCAT(a.nombres,' ',a.apellidos) SEPARATOR ', '),'Sin autor') AS autor,
            COALESCE(ed.nombre,'Sin editorial') AS editorial,
            COALESCE(m.nombre,'Sin categoría') AS categoria,
            l.portada AS imagen,l.descripcion,l.activo AS estado,
            l.numero_paginas AS num_pagina,l.anio_publicacion AS anio_edicion,
            l.id_restriccion,COALESCE(res.nombre,'Sin restricción') AS restriccion
            FROM libros l
            LEFT JOIN ejemplares e ON e.id_libro=l.id_libro AND e.activo=1
            LEFT JOIN autores_libros al ON al.id_libro=l.id_libro
            LEFT JOIN autores a ON a.id_autor=al.id_autor
            LEFT JOIN editoriales ed ON ed.id_editorial=l.id_editorial
            LEFT JOIN categorias m ON m.id_categoria=l.id_categoria
            LEFT JOIN restricciones res ON res.id_restriccion=l.id_restriccion
            GROUP BY l.id_libro,l.titulo,ed.nombre,m.nombre,l.portada,l.descripcion,l.activo,l.numero_paginas,l.anio_publicacion,l.id_restriccion,res.nombre
            ORDER BY l.titulo");
    }
    /** Catálogo de restricciones (Sin restricción, Restringido) para el <select> del formulario. */
    public function getRestricciones(){
        return $this->selectAll("SELECT id_restriccion AS id, nombre AS text FROM restricciones WHERE activo=1 ORDER BY id_restriccion");
    }
    public function insertarLibros($titulo,$id_autor,$id_editorial,$id_categoria,$cantidad,$num_pagina,$anio_edicion,$descripcion,$imgNombre,$id_restriccion=1){
        if($this->selectPrepared("SELECT id_libro FROM libros WHERE titulo=? LIMIT 1",[$titulo]))return'existe';
        try{
            $this->beginTransaction();
            $id=$this->insert("INSERT INTO libros(titulo,numero_paginas,anio_publicacion,descripcion,portada,id_editorial,id_categoria,id_restriccion,activo) VALUES(?,?,?,?,?,?,?,?,1)",[$titulo,$num_pagina,$anio_edicion!==''?(int)$anio_edicion:null,$descripcion,$imgNombre,$id_editorial,$id_categoria,$id_restriccion]);
            $this->save("INSERT INTO autores_libros(id_libro,id_autor,tipo_autoria) VALUES(?,?,'Principal')",[$id,$id_autor]);
            $cantidad=max(1,(int)$cantidad);
            for($i=1;$i<=$cantidad;$i++){
                $codigo='EJ-'.str_pad((string)$id,4,'0',STR_PAD_LEFT).'-'.str_pad((string)$i,2,'0',STR_PAD_LEFT);
                $barra='784'.str_pad((string)$id,6,'0',STR_PAD_LEFT).str_pad((string)$i,3,'0',STR_PAD_LEFT);
                $this->save("INSERT INTO ejemplares(id_libro,id_estado_ejemplar,codigo_ejemplar,codigo_barra,fecha_adquisicion,activo) VALUES(?,?,?, ?,CURDATE(),1)",[$id,1,$codigo,$barra]);
            }
            $this->commit();return'ok';
        }catch(Throwable $e){$this->rollback();error_log($e->getMessage());return'error';}
    }
    public function editLibros($id){
        return $this->selectPrepared("SELECT l.id_libro AS id_libro,l.titulo,l.portada AS imagen,
            l.descripcion,l.numero_paginas AS num_pagina,l.anio_publicacion AS anio_edicion,
            l.id_editorial,l.id_categoria,l.id_restriccion,MIN(al.id_autor) AS id_autor,
            COALESCE(MAX(CONCAT(a.nombres,' ',a.apellidos)),'') AS autor,
            COALESCE(ed.nombre,'') AS editorial,COALESCE(m.nombre,'') AS categoria,
            COUNT(e.id_ejemplar) AS cantidad
            FROM libros l
            LEFT JOIN autores_libros al ON al.id_libro=l.id_libro
            LEFT JOIN autores a ON a.id_autor=al.id_autor
            LEFT JOIN editoriales ed ON ed.id_editorial=l.id_editorial
            LEFT JOIN categorias m ON m.id_categoria=l.id_categoria
            LEFT JOIN ejemplares e ON e.id_libro=l.id_libro AND e.activo=1
            WHERE l.id_libro=? GROUP BY l.id_libro",[$id]);
    }
    public function actualizarLibros($titulo,$id_autor,$id_editorial,$id_categoria,$cantidad,$num_pagina,$anio_edicion,$descripcion,$imagenUrl,$id,$id_restriccion=1){
        try{
            $this->beginTransaction();
            $ok=$this->save("UPDATE libros SET titulo=?,id_editorial=?,id_categoria=?,numero_paginas=?,anio_publicacion=?,descripcion=?,portada=?,id_restriccion=? WHERE id_libro=?",[$titulo,$id_editorial,$id_categoria,$num_pagina,$anio_edicion!==''?(int)$anio_edicion:null,$descripcion,$imagenUrl,$id_restriccion,$id]);
            if(!$ok){$this->rollback();return'error';}
            $this->save("DELETE FROM autores_libros WHERE id_libro=?",[$id]);
            $this->save("INSERT INTO autores_libros(id_libro,id_autor,tipo_autoria) VALUES(?,?,'Principal')",[$id,$id_autor]);
            $actual=$this->selectPrepared("SELECT COUNT(*) cantidad FROM ejemplares WHERE id_libro=? AND activo=1",[$id]);
            $objetivo=max(1,(int)$cantidad); $dif=$objetivo-(int)$actual['cantidad'];
            if($dif>0){for($i=1;$i<=$dif;$i++){$n=(int)$actual['cantidad']+$i;$codigo='EJ-'.str_pad((string)$id,4,'0',STR_PAD_LEFT).'-'.str_pad((string)$n,2,'0',STR_PAD_LEFT);$barra='784'.str_pad((string)$id,6,'0',STR_PAD_LEFT).str_pad((string)$n,3,'0',STR_PAD_LEFT);$this->save("INSERT INTO ejemplares(id_libro,id_estado_ejemplar,codigo_ejemplar,codigo_barra,fecha_adquisicion,activo) VALUES(?,?,?, ?,CURDATE(),1)",[$id,1,$codigo,$barra]);}}
            elseif($dif<0){$this->save("UPDATE ejemplares SET activo=0,id_estado_ejemplar=6 WHERE id_ejemplar IN (SELECT x.id_ejemplar FROM (SELECT id_ejemplar FROM ejemplares WHERE id_libro=? AND activo=1 AND id_estado_ejemplar=1 ORDER BY id_ejemplar DESC LIMIT ?) x)",[$id,abs($dif)]);}
            $this->commit();return'modificado';
        }catch(Throwable $e){$this->rollback();error_log($e->getMessage());return'error';}
    }
    public function estadoLibros($estado,$id){return $this->save("UPDATE libros SET activo=? WHERE id_libro=?",[$estado,$id]);}
    public function buscarLibro($valor){return $this->selectAllPrepared("SELECT id_libro AS id,titulo AS text FROM libros WHERE activo=1 AND (titulo LIKE ? OR isbn LIKE ? OR codigo_catalogo LIKE ?) LIMIT 10",['%'.$valor.'%','%'.$valor.'%','%'.$valor.'%']);}
    public function cantidadDisponible($id){$r=$this->selectPrepared("SELECT COUNT(*) cantidad FROM ejemplares WHERE id_libro=? AND activo=1 AND id_estado_ejemplar=1",[$id]);return $r?:['cantidad'=>0];}
}
?>