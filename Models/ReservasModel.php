<?php
/** Gestión de reservas y generación del identificador QR. */
class ReservasModel extends Query
{
    public function __construct(){parent::__construct();}
    /**
     * $idUsuario/$esStaff filtran el resultado: Administrador y Bibliotecario
     * ven todas las reservas; cualquier otro rol (Alumno, Profesor) solo ve
     * las suyas propias.
     */
    public function listar($idUsuario,$esStaff){
        $sql="SELECT r.id_reserva,r.numero_reserva,
            CONCAT(u.nombres,' ',u.apellidos) AS usuario,u.correo,
            GROUP_CONCAT(DISTINCT CONCAT(l.titulo,' (',lc.cant,')') ORDER BY l.titulo SEPARATOR ', ') AS titulo,
            SUM(1) AS cantidad_total,
            CASE
                WHEN r.id_estado_reserva=4 THEN 'Cancelada'
                WHEN r.id_estado_reserva=5 THEN 'Vencida'
                WHEN (SELECT COUNT(*) FROM reserva_detalle rdp WHERE rdp.id_reserva=r.id_reserva AND rdp.estado IN ('SOLICITADO','RESERVADO'))>0
                    THEN 'Reserva'
                WHEN (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO')=0
                     AND (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO')=0
                    THEN 'Cancelada'
                WHEN (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO')>0
                     AND (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO')=0
                    THEN 'Retirado'
                WHEN (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO')=0
                     AND (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO')>0
                    THEN 'Devuelto'
                ELSE 'Parcial'
            END AS estado,
            r.fecha_reserva,r.fecha_limite_retiro,r.fecha_devolucion_estimada,
            r.activo,qr.codigo_qr
            FROM reservas r
            JOIN usuarios u ON u.id_usuario=r.id_usuario
            LEFT JOIN reserva_detalle rd ON rd.id_reserva=r.id_reserva
            LEFT JOIN libros l ON l.id_libro=rd.id_libro
            LEFT JOIN (SELECT rd2.id_libro,rd2.id_reserva,COUNT(*) cant FROM reserva_detalle rd2 GROUP BY rd2.id_reserva,rd2.id_libro) lc ON lc.id_reserva=r.id_reserva AND lc.id_libro=l.id_libro
            LEFT JOIN qr_reserva qr ON qr.id_reserva=r.id_reserva";
        $params=[];
        if(!$esStaff){$sql.=" WHERE r.id_usuario=?";$params[]=$idUsuario;}
        $sql.=" GROUP BY r.id_reserva ORDER BY r.fecha_reserva DESC";
        return $params?$this->selectAllPrepared($sql,$params):$this->selectAll($sql);
    }
    /**
     * Horario de atención de la biblioteca para retiro y devolución.
     * Debe coincidir exactamente con el backend Node
     * (HORA_APERTURA/HORA_CIERRE) y con src/data/horarios.js del
     * frontend.
     */
    private const HORA_APERTURA = '07:00';
    private const HORA_CIERRE = '20:00';
    /** Cada cuántos minutos se prueba un horario candidato al sugerir el próximo libre. */
    private const PASO_SUGERENCIA_MIN = 15;

    private function horaDentroDeAtencion($horaStr){
        return $horaStr >= self::HORA_APERTURA && $horaStr <= self::HORA_CIERRE;
    }

    /**
     * Cuántas unidades de un libro están comprometidas (SOLICITADO,
     * RESERVADO o ENTREGADO) en reservas activas cuya franja
     * [fecha_limite_retiro, fecha_devolucion_estimada] se superpone
     * con la franja [$inicio, $fin) evaluada.
     */
    private function unidadesOcupadas($idLibro,$inicio,$fin){
        $r=$this->selectPrepared(
            "SELECT COUNT(*) c FROM reserva_detalle rd
             JOIN reservas r ON r.id_reserva=rd.id_reserva
             WHERE rd.id_libro=? AND rd.estado IN ('SOLICITADO','RESERVADO','ENTREGADO')
               AND r.id_estado_reserva NOT IN (4,5)
               AND r.fecha_limite_retiro<? AND r.fecha_devolucion_estimada>?",
            [$idLibro,$fin,$inicio]
        );
        return (int)($r['c']??0);
    }

    /**
     * Verifica si, para la franja pedida, TODOS los libros de $items
     * entran dentro de la capacidad disponible (considerando otras
     * reservas activas que se superponen en el tiempo). Si no,
     * busca el próximo horario ese mismo día (misma duración) en el
     * que sí entren todos juntos.
     *
     * Devuelve ['disponible'=>bool,'conflictos'=>[titulos],'sugerencia'=>['hora'=>..,'fecha_devolucion'=>..]|null]
     */
    private function franjaDisponible(array $items,$limite,$devolucionEstimada){
        $probar=function($inicio,$fin) use ($items){
            $conflictos=[];
            foreach($items as $it){
                $idLibro=(int)$it['id_libro'];$cantidad=(int)$it['cantidad'];
                $libro=$this->selectPrepared(
                    "SELECT titulo,(SELECT COUNT(*) FROM ejemplares WHERE id_libro=libros.id_libro AND activo=1) AS total
                     FROM libros WHERE id_libro=?",[$idLibro]
                );
                if(!$libro) continue;
                $ocupados=$this->unidadesOcupadas($idLibro,$inicio,$fin);
                if($ocupados+$cantidad>(int)$libro['total']){
                    $conflictos[]=$libro['titulo'];
                }
            }
            return $conflictos;
        };

        $conflictos=$probar($limite,$devolucionEstimada);
        if(!$conflictos) return ['disponible'=>true];

        $horaSolicitada=date('H:i',strtotime($limite));
        $duracionSeg=strtotime($devolucionEstimada)-strtotime($limite);

        $diaLimite=date('Y-m-d',strtotime($limite));
        $cierreTs=strtotime($diaLimite.' '.self::HORA_CIERRE.':00');
        $candidatoTs=strtotime($limite)+self::PASO_SUGERENCIA_MIN*60;

        while($candidatoTs<=$cierreTs){
            $inicioCandidato=date('Y-m-d H:i:s',$candidatoTs);
            $finCandidato=date('Y-m-d H:i:s',$candidatoTs+$duracionSeg);
            if(!$probar($inicioCandidato,$finCandidato)){
                return ['disponible'=>false,'conflictos'=>$conflictos,'sugerencia'=>['hora'=>date('H:i',$candidatoTs),'fecha_devolucion'=>$finCandidato]];
            }
            $candidatoTs+=self::PASO_SUGERENCIA_MIN*60;
        }
        return ['disponible'=>false,'conflictos'=>$conflictos,'sugerencia'=>null];
    }

    /** Consulta en vivo (AJAX) para que el formulario avise antes de confirmar. */
    public function verificarHorario(array $items,$limite,$devolucionEstimada){
        $horaRetiro=date('H:i',strtotime($limite));
        $horaDevolucion=date('H:i',strtotime($devolucionEstimada));
        if(!$this->horaDentroDeAtencion($horaRetiro)||!$this->horaDentroDeAtencion($horaDevolucion)){
            return ['disponible'=>false,'conflictos'=>[],'sugerencia'=>null,'motivo'=>'fuera_de_horario'];
        }
        return $this->franjaDisponible($items,$limite,$devolucionEstimada);
    }

    /**
     * Crea una reserva para uno o varios libros, cada uno con su cantidad
     * (copias/ejemplares) solicitada. $items es un arreglo de
     * ['id_libro'=>int, 'cantidad'=>int]. YA NO bloquea ni asigna ningún
     * ejemplar físico acá: solo deja constancia de qué se pidió (filas
     * SOLICITADO en reserva_detalle, sin id_ejemplar). El ejemplar real
     * se asigna recién en el momento del retiro (ver
     * EscanerQrModel::estadoMixtoReserva), a quien llegue primero a
     * buscarlo. Sí puede rechazarse por franja horaria: si para el
     * horario pedido ya no entra en la capacidad del libro (considerando
     * otras reservas activas que se superponen en el tiempo), no se crea.
     */
    public function crear($usuario,array $items,$limite,$observacion,$devolucionEstimada=null)
    {
        $items=array_values(array_filter($items,fn($it)=>(int)($it['id_libro']??0)>0 && (int)($it['cantidad']??0)>0));
        if(!$items)return'sin_items';
        if(!$devolucionEstimada)return'sin_devolucion';

        $horaRetiro=date('H:i',strtotime($limite));
        $horaDevolucion=date('H:i',strtotime($devolucionEstimada));
        if(!$this->horaDentroDeAtencion($horaRetiro))return'fuera_de_horario:retiro';
        if(!$this->horaDentroDeAtencion($horaDevolucion))return'fuera_de_horario:devolucion';
        try{
            $this->beginTransaction();

            foreach($items as $it){
                $idLibro=(int)$it['id_libro'];$cantidad=(int)$it['cantidad'];

                $libroInfo=$this->selectPrepared("SELECT titulo,id_restriccion,(SELECT COUNT(*) FROM ejemplares WHERE id_libro=libros.id_libro AND activo=1) AS total_ejemplares FROM libros WHERE id_libro=?",[$idLibro]);
                if(!$libroInfo){$this->rollback();return'no_existe:'.$idLibro;}

                // Nunca se puede pedir más de lo que ese libro tiene en total
                // (sin importar el estado actual de cada ejemplar): pedir 100
                // de un libro con 3 no tiene sentido, aunque el modelo nuevo
                // ya no exija que estén libres en este momento.
                if($cantidad>(int)$libroInfo['total_ejemplares']){
                    $this->rollback();
                    return 'excede_capacidad:'.$libroInfo['titulo'].':'.$libroInfo['total_ejemplares'];
                }

                // Libros con restricción especial (cualquier id_restriccion distinto de
                // "Sin restricción"): un mismo usuario no puede tener más de 1 ejemplar
                // en reservas pendientes de ese libro al mismo tiempo (pedido sin asignar
                // -SOLICITADO- o ya asignado -RESERVADO-, cualquiera de los dos cuenta).
                $esRestringido = $libroInfo['id_restriccion']!==null && (int)$libroInfo['id_restriccion']!==1;
                if($esRestringido){
                    $yaTiene=$this->selectPrepared(
                        "SELECT COUNT(*) c FROM reserva_detalle rd
                         JOIN reservas r ON r.id_reserva=rd.id_reserva
                         WHERE r.id_usuario=? AND rd.id_libro=? AND rd.estado IN ('SOLICITADO','RESERVADO') AND r.activo=1",
                        [$usuario,$idLibro]
                    );
                    $yaTiene=(int)($yaTiene['c']??0);
                    if($yaTiene+$cantidad>1){
                        $this->rollback();
                        return 'restringido:'.($libroInfo['titulo']??('#'.$idLibro));
                    }
                }
            }

            // Franja horaria: sí puede rechazar la creación (ver franjaDisponible).
            $franja=$this->franjaDisponible($items,$limite,$devolucionEstimada);
            if(!$franja['disponible']){
                $this->rollback();
                $sugerenciaHora=$franja['sugerencia']['hora']??null;
                return 'sin_horario:'.implode(', ',$franja['conflictos']).':'.($sugerenciaHora??'');
            }

            $numero='RES-'.date('YmdHis').'-'.random_int(10,99);
            $this->call("CALL sp_crear_reserva(?,?,?,?,@id_reserva)",[$numero,$usuario,1,$limite]);
            $r=$this->select("SELECT @id_reserva AS id_reserva");$id=(int)($r['id_reserva']??0);
            if(!$id){$this->rollback();return'error';}

            foreach($items as $it){
                $idLibro=(int)$it['id_libro'];$cantidad=(int)$it['cantidad'];
                for($i=0;$i<$cantidad;$i++){
                    $this->call("CALL sp_solicitar_reserva_detalle(?,?)",[$id,$idLibro]);
                }
            }

            $codigo='QR-'.$numero.'-'.bin2hex(random_bytes(4));
            $hash=hash('sha256',$codigo);
            $this->call("CALL sp_generar_qr(?,?,?,?)",[$id,$codigo,$hash,date('Y-m-d H:i:s',strtotime($limite))]);
            $this->save("UPDATE reservas SET observacion=?,fecha_devolucion_estimada=? WHERE id_reserva=?",[$observacion,$devolucionEstimada,$id]);
            $this->commit();return $codigo;
        }catch(Throwable $e){$this->rollback();error_log('ReservasModel::crear '.$e->getMessage());return'error';}
    }
    public function esDueno($idReserva,$idUsuario):bool{
        $r=$this->selectPrepared("SELECT 1 FROM reservas WHERE id_reserva=? AND id_usuario=?",[$idReserva,$idUsuario]);
        return (bool)$r;
    }
    public function cancelar($id,$usuario,$motivo){try{$this->call("CALL sp_cancelar_reserva(?,?,?)",[$id,$usuario,$motivo]);return'ok';}catch(Throwable $e){error_log($e->getMessage());return'error';}}
}
?>
