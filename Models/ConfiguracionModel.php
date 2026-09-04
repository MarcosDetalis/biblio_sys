<?php
/** Dashboard y parámetros generales del sistema. */
class ConfiguracionModel extends Query
{
    public function __construct(){parent::__construct();}

    /**
     * La estructura v3 no tiene una tabla configuracion. Se utiliza la tabla
     * parametros existente con grupo SISTEMA para evitar crear otra tabla.
     */
    public function selectConfiguracion()
    {
        $rows=$this->selectAll("SELECT nombre,valor FROM parametros WHERE grupo_parametro='SISTEMA' AND activo=1 ORDER BY id_parametro");
        $data=['id'=>1,'nombre'=>'Biblioteca Universitaria','telefono'=>'','direccion'=>'','correo'=>'','foto'=>'logo.png'];
        foreach($rows as $r){$data[$r['nombre']]=$r['valor'];}
        return $data;
    }
    public function actualizarConfig($nombre,$telefono,$direccion,$correo,$img,$id)
    {
        $vals=['nombre'=>$nombre,'telefono'=>$telefono,'direccion'=>$direccion,'correo'=>$correo,'foto'=>$img?:'logo.png'];
        foreach($vals as $k=>$v){
            $ex=$this->selectPrepared("SELECT id_parametro FROM parametros WHERE grupo_parametro='SISTEMA' AND nombre=? LIMIT 1",[$k]);
            if($ex)$this->save("UPDATE parametros SET valor=? WHERE id_parametro=?",[$v,$ex['id_parametro']]);
            else $this->save("INSERT INTO parametros(grupo_parametro,nombre,valor,descripcion,activo) VALUES('SISTEMA',?,?,?,1)",[$k,$v,'Configuración general del sistema']);
        }
        return 'modificado';
    }
    public function selectDatos($nombre,$estado){
        $permitidas=['usuarios'=>'activo','libros'=>'activo','ejemplares'=>'activo','categorias'=>'activo','autores'=>'activo','editoriales'=>'activo'];
        if(!isset($permitidas[$nombre]))return['total'=>0];
        return $this->select("SELECT COUNT(*) total FROM {$nombre} WHERE {$permitidas[$nombre]}=1");
    }
    public function selectDatosespeciales($nombre,$estado,$tipousuarios){
        if($nombre!=='usuarios')return['total'=>0];
        return $this->selectPrepared("SELECT COUNT(*) total FROM usuarios WHERE activo=1 AND id_tipo_usuario=?",[(int)$tipousuarios]);
    }
    public function getReportes(){return $this->selectAll("SELECT titulo,cantidad FROM (SELECT l.id_libro,l.titulo,COUNT(pd.id_prestamo_detalle) cantidad FROM libros l LEFT JOIN ejemplares e ON e.id_libro=l.id_libro LEFT JOIN prestamo_detalle pd ON pd.id_ejemplar=e.id_ejemplar WHERE l.activo=1 GROUP BY l.id_libro,l.titulo) x ORDER BY cantidad DESC");}
    public function getVerificarPrestamos($date){return $this->selectAllPrepared("SELECT p.id_prestamo AS id,p.id_usuario AS id_estudiante,p.fecha_prestamo,p.fecha_devolucion,
        COUNT(pd.id_ejemplar) cantidad,p.id_estado_prestamo estado,CONCAT(u.nombres,' ',u.apellidos) nombre,l.titulo
        FROM prestamos p JOIN usuarios u ON u.id_usuario=p.id_usuario JOIN prestamo_detalle pd ON pd.id_prestamo=p.id_prestamo
        JOIN ejemplares e ON e.id_ejemplar=pd.id_ejemplar JOIN libros l ON l.id_libro=e.id_libro
        WHERE p.fecha_vencimiento < ? AND p.fecha_devolucion IS NULL GROUP BY p.id_prestamo",[$date]);}
    public function getResumen(){
        return $this->select("SELECT
            (SELECT COUNT(*) FROM usuarios WHERE activo=1) usuarios_activos,
            (SELECT COUNT(*) FROM usuarios WHERE activo=1 AND id_tipo_usuario=1) alumnos_activos,
            (SELECT COUNT(*) FROM usuarios WHERE activo=1 AND id_tipo_usuario=2) profesores_activos,
            (SELECT COUNT(*) FROM libros WHERE activo=1) libros_activos,
            (SELECT COUNT(*) FROM ejemplares WHERE activo=1) ejemplares,
            (SELECT COUNT(*) FROM ejemplares WHERE activo=1 AND id_estado_ejemplar=1) disponibles,
            (SELECT COUNT(*) FROM reservas WHERE activo=1) reservas_activas,
            (SELECT COUNT(*) FROM prestamos WHERE activo=1 AND fecha_devolucion IS NULL) prestamos_activos,
            (SELECT COUNT(*) FROM multas WHERE pagada=0) multas_pendientes");
    }

    /**
     * Campanita de notificaciones (header.php). Administrador y Bibliotecario
     * ven un resumen agregado de TODO el sistema; Alumno y Profesor ven
     * únicamente el estado de sus propias reservas y préstamos.
     * Se considera "por vencer" cuando falta 1 día o menos.
     */
    public function notificaciones($idUsuario,$esStaff){
        if($esStaff){
            $retiroPendiente=$this->select("SELECT COUNT(DISTINCT r.id_reserva) c FROM reservas r WHERE r.activo=1 AND EXISTS(SELECT 1 FROM reserva_detalle rd WHERE rd.id_reserva=r.id_reserva AND rd.estado IN ('SOLICITADO','RESERVADO'))");
            $retiroPorVencer=$this->select("SELECT COUNT(DISTINCT r.id_reserva) c FROM reservas r WHERE r.activo=1 AND r.fecha_limite_retiro BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY) AND EXISTS(SELECT 1 FROM reserva_detalle rd WHERE rd.id_reserva=r.id_reserva AND rd.estado IN ('SOLICITADO','RESERVADO'))");
            $devolucionPendiente=$this->select("SELECT COUNT(*) c FROM prestamos WHERE activo=1 AND fecha_devolucion IS NULL AND fecha_vencimiento>=CURDATE()");
            $devolucionVencida=$this->select("SELECT COUNT(*) c FROM prestamos WHERE activo=1 AND fecha_devolucion IS NULL AND fecha_vencimiento<CURDATE()");
            $items=[];
            if((int)$retiroPendiente['c']>0)$items[]=['tipo'=>'retiro_pendiente','mensaje'=>$retiroPendiente['c'].' reserva(s) pendientes de retiro'];
            if((int)$retiroPorVencer['c']>0)$items[]=['tipo'=>'retiro_por_vencer','mensaje'=>$retiroPorVencer['c'].' reserva(s) por vencer su plazo de retiro'];
            if((int)$devolucionPendiente['c']>0)$items[]=['tipo'=>'devolucion_pendiente','mensaje'=>$devolucionPendiente['c'].' préstamo(s) activos en circulación'];
            if((int)$devolucionVencida['c']>0)$items[]=['tipo'=>'devolucion_vencida','mensaje'=>$devolucionVencida['c'].' préstamo(s) vencidos sin devolver'];
            return ['total'=>(int)$retiroPorVencer['c']+(int)$devolucionVencida['c'],'items'=>$items];
        }

        $misPendientes=$this->selectAllPrepared(
            "SELECT r.id_reserva,r.numero_reserva,r.fecha_limite_retiro
             FROM reservas r
             WHERE r.id_usuario=? AND r.activo=1
               AND EXISTS(SELECT 1 FROM reserva_detalle rd WHERE rd.id_reserva=r.id_reserva AND rd.estado IN ('SOLICITADO','RESERVADO'))
             ORDER BY r.fecha_limite_retiro",[$idUsuario]
        );
        $misPrestamos=$this->selectAllPrepared(
            "SELECT p.id_prestamo,p.numero_prestamo,p.fecha_vencimiento
             FROM prestamos p
             WHERE p.id_usuario=? AND p.activo=1 AND p.fecha_devolucion IS NULL
             ORDER BY p.fecha_vencimiento",[$idUsuario]
        );

        $items=[];$urgentes=0;
        foreach($misPendientes as $r){
            $vencePronto=strtotime($r['fecha_limite_retiro'])-time()<=86400;
            if($vencePronto)$urgentes++;
            $items[]=['tipo'=>$vencePronto?'retiro_por_vencer':'retiro_pendiente',
                'mensaje'=>$vencePronto
                    ? 'Tu reserva '.$r['numero_reserva'].' vence pronto para retirar ('.$r['fecha_limite_retiro'].')'
                    : 'Tu reserva '.$r['numero_reserva'].' está lista para retirar'];
        }
        foreach($misPrestamos as $p){
            $vencido=strtotime($p['fecha_vencimiento'])<strtotime(date('Y-m-d'));
            if($vencido)$urgentes++;
            $items[]=['tipo'=>$vencido?'devolucion_vencida':'devolucion_pendiente',
                'mensaje'=>$vencido
                    ? 'Tu préstamo '.$p['numero_prestamo'].' está vencido, devolvé el libro cuanto antes'
                    : 'Tenés pendiente devolver el préstamo '.$p['numero_prestamo'].' (vence '.$p['fecha_vencimiento'].')'];
        }
        return ['total'=>$urgentes,'items'=>$items];
    }
}
?>