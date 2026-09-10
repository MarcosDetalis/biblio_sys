<?php
/** Operaciones del lector QR sobre reservas de biblioteca_v3. */
class EscanerQRModel extends Query
{
    public function __construct(){parent::__construct();}
    public function obtenerReservas($id)
    {
        return $this->selectAllPrepared("SELECT l.titulo AS Libro_nombre,
            er.nombre AS Estado_solicitud_descripcion,r.id_estado_reserva AS Tbl_estado_solicitudes_idEstad_solicitud,
            r.id_reserva AS Tbl_reservas_cab_idReserva_cab,rd.id_reserva_detalle AS Idreserva_det,
            rd.id_ejemplar,rd.estado AS detalle_estado,r.fecha_limite_retiro AS Reserva_det_fecha_devolucion,
            CONCAT(u.nombres,' ',u.apellidos) AS alumno
            FROM reserva_detalle rd
            JOIN reservas r ON r.id_reserva=rd.id_reserva
            JOIN usuarios u ON u.id_usuario=r.id_usuario
            JOIN estados_reserva er ON er.id_estado_reserva=r.id_estado_reserva
            JOIN ejemplares e ON e.id_ejemplar=rd.id_ejemplar
            JOIN libros l ON l.id_libro=e.id_libro
            WHERE r.id_reserva=? ORDER BY rd.id_reserva_detalle",[$id]);
    }
    /**
     * Listado unificado de "transacciones de préstamo" reducido a la máquina
     * de estados simple: Reserva -> (Parcial) -> Retirado -> Devuelto.
     * "Parcial" aparece cuando, dentro de la misma reserva, ya se retiró
     * algo pero todavía queda algo pendiente (retiro parcial).
     * $estado filtra por 'Reserva'|'Parcial'|'Retirado'|'Devuelto'; null = todas.
     */
    private function baseTransacciones(){
        return "SELECT r.id_reserva,r.numero_reserva,
                r.id_usuario,u.id_tipo_usuario,t.nombre AS tipo_usuario,
                CONCAT(u.nombres,' ',u.apellidos) AS usuario,u.cedula,
                GROUP_CONCAT(DISTINCT CONCAT(l.titulo,' (',lc.cant,')') ORDER BY l.titulo SEPARATOR ', ') AS libros,
                r.fecha_reserva,r.fecha_limite_retiro,r.fecha_devolucion_estimada,
                qr.codigo_qr,
                (SELECT COUNT(*) FROM reserva_detalle rdp WHERE rdp.id_reserva=r.id_reserva AND rdp.estado IN ('SOLICITADO','RESERVADO')) AS pendientes_count,
                (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO') AS prestados_count,
                (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO') AS devueltos_count,
                CASE
                    WHEN r.id_estado_reserva=4 THEN 'Cancelada'
                    WHEN r.id_estado_reserva=5 THEN 'Vencida'
                    WHEN (SELECT COUNT(*) FROM reserva_detalle rdp WHERE rdp.id_reserva=r.id_reserva AND rdp.estado IN ('SOLICITADO','RESERVADO'))>0
                        THEN 'Reserva'
                    WHEN (SELECT COUNT(*) FROM reserva_detalle rde WHERE rde.id_reserva=r.id_reserva AND rde.estado='ENTREGADO')=0
                        THEN 'Cancelada'
                    WHEN (SELECT COUNT(*) FROM reserva_detalle rdc WHERE rdc.id_reserva=r.id_reserva AND rdc.estado IN ('CANCELADO','SIN_DISPONIBILIDAD'))>0
                        THEN 'Parcial'
                    WHEN (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO')>0
                         AND (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO')=0
                        THEN 'Retirado'
                    WHEN (SELECT COUNT(*) FROM prestamo_detalle pdp JOIN prestamos pp ON pp.id_prestamo=pdp.id_prestamo WHERE pp.id_reserva=r.id_reserva AND pdp.estado='PRESTADO')=0
                         AND (SELECT COUNT(*) FROM prestamo_detalle pdv JOIN prestamos pv ON pv.id_prestamo=pdv.id_prestamo WHERE pv.id_reserva=r.id_reserva AND pdv.estado='DEVUELTO')>0
                        THEN 'Devuelto'
                    ELSE 'Parcial'
                END AS estado_simple
            FROM reservas r
            JOIN usuarios u ON u.id_usuario=r.id_usuario
            LEFT JOIN tipos_usuario t ON t.id_tipo_usuario=u.id_tipo_usuario
            LEFT JOIN reserva_detalle rd ON rd.id_reserva=r.id_reserva
            LEFT JOIN libros l ON l.id_libro=rd.id_libro
            LEFT JOIN (SELECT rd2.id_libro,rd2.id_reserva,COUNT(*) cant FROM reserva_detalle rd2 GROUP BY rd2.id_reserva,rd2.id_libro) lc ON lc.id_reserva=r.id_reserva AND lc.id_libro=l.id_libro
            LEFT JOIN qr_reserva qr ON qr.id_reserva=r.id_reserva";
    }

    /**
     * Reporte de reservas para el panel de Reportes. Filtros opcionales:
     * fecha_desde/fecha_hasta (sobre fecha_reserva), estado (uno de los
     * estado_simple: Reserva/Retirado/Devuelto/Cancelada/Vencida/Parcial),
     * tipo (1=Alumno,2=Profesor) y texto (nombre/cédula/correo del usuario).
     * Siempre disponible completo (sin acotar por dueño): es un reporte de
     * uso exclusivo de Administrador/Bibliotecario.
     */
    public function reporte($filtros=[]){
        $sql="SELECT * FROM (".$this->baseTransacciones()." GROUP BY r.id_reserva) t WHERE 1=1";
        $params=[];
        if(!empty($filtros['fecha_desde'])){$sql.=" AND DATE(t.fecha_reserva)>=?";$params[]=$filtros['fecha_desde'];}
        if(!empty($filtros['fecha_hasta'])){$sql.=" AND DATE(t.fecha_reserva)<=?";$params[]=$filtros['fecha_hasta'];}
        if(!empty($filtros['estado'])){$sql.=" AND t.estado_simple=?";$params[]=$filtros['estado'];}
        if(!empty($filtros['tipo'])){$sql.=" AND t.id_tipo_usuario=?";$params[]=(int)$filtros['tipo'];}
        if(!empty($filtros['texto'])){$sql.=" AND (t.usuario LIKE ? OR t.cedula LIKE ?)";$like='%'.$filtros['texto'].'%';$params[]=$like;$params[]=$like;}
        $sql.=" ORDER BY t.fecha_reserva DESC";
        return $params?$this->selectAllPrepared($sql,$params):$this->selectAll($sql);
    }

    /**
     * $idUsuario/$esStaff: Administrador y Bibliotecario ven todas las
     * transacciones; cualquier otro rol solo ve las suyas propias.
     */
    public function listarTransacciones($estado=null,$idUsuario=null,$esStaff=true){
        $sql=$this->baseTransacciones();
        $condiciones=[];$params=[];
        if(!$esStaff){$condiciones[]="r.id_usuario=?";$params[]=$idUsuario;}
        if($condiciones)$sql.=" WHERE ".implode(' AND ',$condiciones);
        $sql.=" GROUP BY r.id_reserva";
        if($estado){
            $sql="SELECT * FROM (".$sql.") t WHERE estado_simple=?";
            $params[]=$estado;
        }else{
            $sql.=" ORDER BY r.fecha_reserva DESC";
        }
        return $params?$this->selectAllPrepared($sql,$params):$this->selectAll($sql);
    }

    /**
     * Busca la transacción por nombre de usuario, cédula, número de reserva
     * o número/ID de préstamo, para poder cambiar el estado sin depender
     * de la cámara (bibliotecario que no tiene la cámara a mano, o el alumno
     * perdió el papel/QR). $idUsuario/$esStaff: igual que en listarTransacciones,
     * un Alumno/Profesor solo puede encontrar sus propias transacciones así
     * escriba el nombre de otra persona.
     */
    public function buscarTransaccion($texto,$idUsuario=null,$esStaff=true){
        $texto=trim($texto);
        $sql=$this->baseTransacciones()."
            WHERE (u.nombres LIKE ? OR u.apellidos LIKE ? OR u.cedula LIKE ?
               OR r.numero_reserva LIKE ?
               OR r.id_reserva=?
               OR EXISTS(SELECT 1 FROM prestamos px WHERE px.id_reserva=r.id_reserva AND (px.numero_prestamo LIKE ? OR px.id_prestamo=?)))";
        $like='%'.$texto.'%';
        $idNum=ctype_digit($texto)?(int)$texto:0;
        $params=[$like,$like,$like,$like,$idNum,$like,$idNum];
        if(!$esStaff){$sql.=" AND r.id_usuario=?";$params[]=$idUsuario;}
        $sql.=" GROUP BY r.id_reserva ORDER BY r.fecha_reserva DESC LIMIT 25";
        return $this->selectAllPrepared($sql,$params);
    }


    /**
     * Estado "mixto" de una reserva para el retiro/devolución parcial:
     * separa sus ejemplares en 3 grupos —
     *   pendientes: todavía sin retirar (reserva_detalle.estado='RESERVADO')
     *   prestados:  ya retirados y sin devolver (prestamo_detalle.estado='PRESTADO')
     *   devueltos:  ya devueltos (solo informativo)
     * Una misma reserva puede tener ítems en varios grupos a la vez (retiro
     * parcial), así que la pantalla del escáner ya no es "todo o nada": el
     * operador tilda cuáles retira y cuáles devuelve, cada grupo con su
     * propio botón de confirmar.
     */
    public function esDuenoReserva($idReserva,$idUsuario):bool{
        $r=$this->selectPrepared("SELECT 1 FROM reservas WHERE id_reserva=? AND id_usuario=?",[$idReserva,$idUsuario]);
        return (bool)$r;
    }

    /**
     * Asigna ejemplares reales a las filas SOLICITADO (pedidas al crear
     * la reserva, todavía sin ejemplar) de UNA reserva puntual. Se llama
     * SIEMPRE justo antes de mostrar la pantalla de retiro (QR o
     * búsqueda manual): es el momento en que recién se decide qué
     * ejemplar le toca a quién. El primer operador que llega a procesar
     * una reserva y hay stock se lo lleva (FOR UPDATE evita que dos
     * operadores asignen el mismo ejemplar al mismo tiempo); lo que no
     * consigue ejemplar queda en SIN_DISPONIBILIDAD para esa unidad
     * puntual, sin afectar el resto de la reserva ni otras reservas.
     */
    private function asignarPendientes($idReserva){
        $solicitados=$this->selectAllPrepared(
            "SELECT id_reserva_detalle,id_libro FROM reserva_detalle WHERE id_reserva=? AND estado='SOLICITADO'",
            [$idReserva]
        );
        if(!$solicitados) return;
        try{
            $this->beginTransaction();
            foreach($solicitados as $s){
                $ejemplar=$this->selectPrepared(
                    "SELECT id_ejemplar FROM ejemplares WHERE id_libro=? AND id_estado_ejemplar=1 AND activo=1 ORDER BY id_ejemplar LIMIT 1 FOR UPDATE",
                    [$s['id_libro']]
                );
                if($ejemplar){
                    $this->call("CALL sp_asignar_ejemplar_reserva_detalle(?,?)",[$s['id_reserva_detalle'],$ejemplar['id_ejemplar']]);
                }else{
                    $this->save("UPDATE reserva_detalle SET estado='SIN_DISPONIBILIDAD' WHERE id_reserva_detalle=?",[$s['id_reserva_detalle']]);
                }
            }

            // Si después de intentar asignar TODO lo pedido no quedó nada
            // realmente retirable (ni un solo ejemplar asignado, ni nada ya
            // retirado/devuelto antes) — o sea, la reserva entera quedó sin
            // disponibilidad — se cierra el encabezado como "Cancelada" para
            // que deje de figurar eternamente como "Pendiente" en cualquier
            // listado (admin y del propio usuario). El detalle de la reserva
            // sigue explicando el motivo real (sección "Sin disponibilidad").
            $quedaAlgoRetirable=$this->selectPrepared(
                "SELECT 1 FROM reserva_detalle WHERE id_reserva=? AND estado IN ('RESERVADO','ENTREGADO','DEVUELTO') LIMIT 1",
                [$idReserva]
            );
            if(!$quedaAlgoRetirable){
                $estadoActual=$this->selectPrepared("SELECT id_estado_reserva FROM reservas WHERE id_reserva=?",[$idReserva]);
                if($estadoActual && !in_array((int)$estadoActual['id_estado_reserva'],[4,5],true)){
                    $this->save("UPDATE reservas SET id_estado_reserva=4, activo=0, fecha_cancelacion=NOW() WHERE id_reserva=?",[$idReserva]);
                    $this->save("INSERT INTO historial_reserva(id_reserva,id_estado_reserva,fecha_evento) VALUES(?,4,NOW())",[$idReserva]);
                }
            }

            $this->commit();
        }catch(Throwable $e){$this->rollback();error_log('EscanerQrModel::asignarPendientes '.$e->getMessage());}
    }

    private function estadoMixtoReserva($idReserva){
        $this->asignarPendientes($idReserva);

        $reserva=$this->selectPrepared(
            "SELECT r.id_reserva,r.id_usuario AS id_usuario_dueno,r.numero_reserva,r.fecha_limite_retiro,r.fecha_devolucion_estimada,r.activo,
                    CONCAT(u.nombres,' ',u.apellidos) AS usuario,u.correo
             FROM reservas r JOIN usuarios u ON u.id_usuario=r.id_usuario
             WHERE r.id_reserva=?",[$idReserva]
        );
        if(!$reserva) return null;

        $pendientes=$this->selectAllPrepared(
            "SELECT rd.id_reserva_detalle,l.titulo,e.codigo_ejemplar
             FROM reserva_detalle rd
             JOIN ejemplares e ON e.id_ejemplar=rd.id_ejemplar
             JOIN libros l ON l.id_libro=e.id_libro
             WHERE rd.id_reserva=? AND rd.estado='RESERVADO'
             ORDER BY l.titulo",[$idReserva]
        );
        $prestados=$this->selectAllPrepared(
            "SELECT pd.id_prestamo_detalle,pd.id_prestamo,p.numero_prestamo,p.fecha_prestamo,p.fecha_vencimiento,l.titulo,e.codigo_ejemplar
             FROM prestamo_detalle pd
             JOIN prestamos p ON p.id_prestamo=pd.id_prestamo
             JOIN ejemplares e ON e.id_ejemplar=pd.id_ejemplar
             JOIN libros l ON l.id_libro=e.id_libro
             WHERE p.id_reserva=? AND pd.estado='PRESTADO'
             ORDER BY l.titulo",[$idReserva]
        );
        $devueltos=$this->selectAllPrepared(
            "SELECT pd.id_prestamo_detalle,l.titulo,p.fecha_devolucion
             FROM prestamo_detalle pd
             JOIN prestamos p ON p.id_prestamo=pd.id_prestamo
             JOIN ejemplares e ON e.id_ejemplar=pd.id_ejemplar
             JOIN libros l ON l.id_libro=e.id_libro
             WHERE p.id_reserva=? AND pd.estado='DEVUELTO'
             ORDER BY l.titulo",[$idReserva]
        );
        $cancelados=$this->selectAllPrepared(
            "SELECT rd.id_reserva_detalle,l.titulo
             FROM reserva_detalle rd
             JOIN libros l ON l.id_libro=rd.id_libro
             WHERE rd.id_reserva=? AND rd.estado='CANCELADO'
             ORDER BY l.titulo",[$idReserva]
        );
        // Unidades que se pidieron pero, al momento del retiro, ya no
        // quedaba ningún ejemplar libre (ver asignarPendientes arriba).
        $sinDisponibilidad=$this->selectAllPrepared(
            "SELECT rd.id_reserva_detalle,l.titulo
             FROM reserva_detalle rd
             JOIN libros l ON l.id_libro=rd.id_libro
             WHERE rd.id_reserva=? AND rd.estado='SIN_DISPONIBILIDAD'
             ORDER BY l.titulo",[$idReserva]
        );

        return ['reserva'=>$reserva,'pendientes'=>$pendientes,'prestados'=>$prestados,'devueltos'=>$devueltos,'cancelados'=>$cancelados,'sinDisponibilidad'=>$sinDisponibilidad];
    }

    /** Igual que obtenerEstadoQr pero buscando directo por id_reserva (para el botón "Gestionar" del listado, sin cámara). */
    public function obtenerEstadoPorReserva($idReserva){
        $estado=$this->estadoMixtoReserva($idReserva);
        if(!$estado) return ['tipo'=>'error','motivo'=>'no_existe'];
        if(!$estado['pendientes'] && !$estado['prestados'] && !$estado['sinDisponibilidad']){
            return ['tipo'=>'error','motivo'=>$estado['devueltos']?'ya_devuelto':'todo_cancelado'];
        }
        return array_merge(['tipo'=>'mixto'],$estado);
    }

    /**
     * El QR sigue siendo válido (utilizado=0) hasta el momento del retiro:
     * ese es el único momento en que se decide qué se lleva el usuario. Lo
     * que no se retira ahí se cancela automáticamente (ver
     * confirmarRetiroParcial), así que después de ese momento el QR ya no
     * vuelve a mostrar nada "pendiente de retirar" — solo lo que quedó
     * prestado y falta devolver.
     */
    public function obtenerEstadoQr($codigo)
    {
        $qr=$this->selectPrepared("SELECT id_reserva,utilizado,fecha_expiracion FROM qr_reserva WHERE codigo_qr=? LIMIT 1",[$codigo]);
        if(!$qr) return ['tipo'=>'error','motivo'=>'no_existe'];

        $estado=$this->estadoMixtoReserva($qr['id_reserva']);
        if(!$estado) return ['tipo'=>'error','motivo'=>'no_existe'];

        if($estado['pendientes'] && strtotime($qr['fecha_expiracion'])<time()){
            return ['tipo'=>'error','motivo'=>'expirado'];
        }
        if(!$estado['pendientes'] && !$estado['prestados'] && !$estado['sinDisponibilidad']){
            return ['tipo'=>'error','motivo'=>$estado['devueltos']?'ya_devuelto':'todo_cancelado'];
        }
        return array_merge(['tipo'=>'mixto'],$estado);
    }

    /**
     * Retira SOLO los ejemplares seleccionados de una reserva, y CANCELA
     * automáticamente todo lo demás que haya quedado pendiente (RESERVADO)
     * en esa misma reserva: no hay "volver otro día" por lo no tildado — el
     * momento del retiro es el único momento en que se decide, y lo que no
     * se retira ahí libera el ejemplar para que otro usuario pueda pedirlo.
     * Como no queda nada pendiente después de esta acción, la reserva
     * siempre se cierra y el QR se marca utilizado.
     */
    public function confirmarRetiroParcial($idReserva,array $idsDetalle,$usuario){
        $idsDetalle=array_values(array_unique(array_map('intval',$idsDetalle)));
        if(!$idsDetalle) return 'sin_seleccion';
        try{
            $this->beginTransaction();

            $r=$this->selectPrepared("SELECT id_estado_reserva,id_usuario FROM reservas WHERE id_reserva=? FOR UPDATE",[$idReserva]);
            if(!$r){$this->rollback();return'error_reserva';}

            $placeholders=implode(',',array_fill(0,count($idsDetalle),'?'));
            $filas=$this->selectAllPrepared(
                "SELECT id_reserva_detalle,id_ejemplar FROM reserva_detalle
                 WHERE id_reserva=? AND estado='RESERVADO' AND id_reserva_detalle IN ($placeholders) FOR UPDATE",
                array_merge([$idReserva],$idsDetalle)
            );
            if(count($filas)!==count($idsDetalle)){$this->rollback();return'seleccion_invalida';}

            $dias=$this->select("SELECT CAST(valor AS UNSIGNED) dias FROM parametros WHERE grupo_parametro='PRESTAMO' AND nombre='DIAS_PRESTAMO_ALUMNO' AND activo=1 LIMIT 1");
            $venc=date('Y-m-d',strtotime('+'.((int)($dias['dias']??7)).' days'));
            $numero='PRE-'.date('YmdHis').'-'.random_int(10,99);
            $this->save("INSERT INTO prestamos(numero_prestamo,id_reserva,id_usuario,id_estado_prestamo,fecha_prestamo,fecha_vencimiento,activo) VALUES(?,?,?,1,NOW(),?,1)",[$numero,$idReserva,$r['id_usuario'],$venc]);
            $idPrestamo=(int)$this->con->lastInsertId();

            foreach($filas as $f){
                $this->save("INSERT INTO prestamo_detalle(id_prestamo,id_ejemplar,estado,observacion) VALUES(?,?,'PRESTADO','Retiro parcial confirmado')",[$idPrestamo,$f['id_ejemplar']]);
                $this->save("UPDATE ejemplares SET id_estado_ejemplar=3 WHERE id_ejemplar=?",[$f['id_ejemplar']]);
                $this->save("INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia,observacion) VALUES(?,'PRESTAMO',?,'Retiro parcial')",[$f['id_ejemplar'],'prestamo #'.$idPrestamo]);
                $this->save("UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva_detalle=?",[$f['id_reserva_detalle']]);
            }

            // Todo lo que seguía RESERVADO y no se tildó para retirar se cancela
            // automáticamente acá: libera el ejemplar (vuelve a Disponible) para
            // que pueda reservarlo otro usuario.
            $noRetirados=$this->selectAllPrepared(
                "SELECT id_reserva_detalle,id_ejemplar FROM reserva_detalle WHERE id_reserva=? AND estado='RESERVADO'",
                [$idReserva]
            );
            $cantidadCancelada=count($noRetirados);
            foreach($noRetirados as $nr){
                $this->save("UPDATE reserva_detalle SET estado='CANCELADO' WHERE id_reserva_detalle=?",[$nr['id_reserva_detalle']]);
                $this->save("UPDATE ejemplares SET id_estado_ejemplar=1 WHERE id_ejemplar=?",[$nr['id_ejemplar']]);
                $this->save("INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia,observacion) VALUES(?,'CANCELACION',?,'No se retiró junto con el resto de la reserva')",[$nr['id_ejemplar'],'reserva #'.$idReserva]);
            }

            // Nunca queda nada pendiente después de esta acción: se cierra la reserva y el QR.
            $this->save("UPDATE reservas SET id_estado_reserva=6,activo=0 WHERE id_reserva=?",[$idReserva]);
            $this->save("UPDATE qr_reserva SET utilizado=1,fecha_utilizacion=NOW() WHERE id_reserva=? AND utilizado=0",[$idReserva]);

            $comentario='Retiro parcial: '.count($idsDetalle).' ejemplar(es) retirado(s)';
            if($cantidadCancelada>0)$comentario.=', '.$cantidadCancelada.' cancelado(s) automáticamente por no haberse retirado';
            $this->save("INSERT INTO historial_reserva(id_reserva,id_estado_reserva,id_usuario,comentario,fecha_evento) VALUES(?,6,?,?,NOW())",[$idReserva,$usuario,$comentario]);
            $this->save("INSERT INTO historial_prestamo(id_prestamo,id_estado_prestamo,id_usuario,comentario,fecha_evento) VALUES(?,1,?,?,NOW())",[$idPrestamo,$usuario,'Préstamo generado por retiro parcial']);

            $this->commit();
            return ['ok'=>true,'id_prestamo'=>$idPrestamo,'cancelados'=>$cantidadCancelada];
        }catch(Throwable $e){$this->rollback();error_log('EscanerQrModel::confirmarRetiroParcial '.$e->getMessage());return'error';}
    }

    /**
     * Devuelve SOLO los ejemplares seleccionados (de uno o varios préstamos
     * distintos, si hicieron falta varios retiros parciales). Cada préstamo
     * afectado se cierra (fecha_devolucion) recién cuando ya no le queda
     * ningún ejemplar en estado PRESTADO.
     */
    public function confirmarDevolucionParcial(array $idsDetalle){
        $idsDetalle=array_values(array_unique(array_map('intval',$idsDetalle)));
        if(!$idsDetalle) return 'sin_seleccion';
        try{
            $this->beginTransaction();
            $placeholders=implode(',',array_fill(0,count($idsDetalle),'?'));
            $filas=$this->selectAllPrepared(
                "SELECT pd.id_prestamo_detalle,pd.id_ejemplar,pd.id_prestamo
                 FROM prestamo_detalle pd
                 WHERE pd.estado='PRESTADO' AND pd.id_prestamo_detalle IN ($placeholders) FOR UPDATE",
                $idsDetalle
            );
            if(count($filas)!==count($idsDetalle)){$this->rollback();return'seleccion_invalida';}

            foreach($filas as $f){
                $this->save("UPDATE ejemplares SET id_estado_ejemplar=1 WHERE id_ejemplar=?",[$f['id_ejemplar']]);
                $this->save("UPDATE prestamo_detalle SET estado='DEVUELTO' WHERE id_prestamo_detalle=?",[$f['id_prestamo_detalle']]);
                $this->save("INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia) VALUES(?,'DEVOLUCION',?)",[$f['id_ejemplar'],'prestamo_detalle #'.$f['id_prestamo_detalle']]);
            }
            $idsPrestamos=array_unique(array_column($filas,'id_prestamo'));
            foreach($idsPrestamos as $idPrestamo){
                $quedan=$this->selectPrepared("SELECT COUNT(*) c FROM prestamo_detalle WHERE id_prestamo=? AND estado='PRESTADO'",[$idPrestamo]);
                if((int)($quedan['c']??0)===0){
                    $this->save("UPDATE prestamos SET fecha_devolucion=CURDATE() WHERE id_prestamo=? AND fecha_devolucion IS NULL",[$idPrestamo]);
                }
            }
            $this->commit();
            return 'ok';
        }catch(Throwable $e){$this->rollback();error_log('EscanerQrModel::confirmarDevolucionParcial '.$e->getMessage());return'error';}
    }

    public function actualizarStockLibro($id_libro){
        $ej=$this->selectPrepared("SELECT id_ejemplar FROM ejemplares WHERE id_libro=? AND id_estado_ejemplar=1 AND activo=1 LIMIT 1",[$id_libro]);
        return $ej?1:0;
    }
    public function accionLibro($estado,$id_libro){return $this->save("UPDATE ejemplares SET id_estado_ejemplar=? WHERE id_libro=? AND activo=1",[$estado,$id_libro]);}
    public function getPermisos(){return $this->selectAll("SELECT * FROM permisos WHERE activo=1");}
    public function getDetallePermisos($id){return $this->selectAllPrepared("SELECT rp.id_permiso AS Tbl_permisos_id_permiso FROM usuario_rol ur JOIN rol_permiso rp ON rp.id_rol=ur.id_rol WHERE ur.id_usuario=? AND ur.activo=1",[$id]);}
    public function actualizarDetalle($id_cabecera){
        return $this->save("UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva=? AND estado='RESERVADO'",[$id_cabecera]);
    }
    public function Cancelar_todo($id_cabecera){
        try{$this->call("CALL sp_cancelar_reserva(?,?,?)",[$id_cabecera,1,'Cancelación desde lector QR']);return'cancelado';}catch(Throwable $e){error_log($e->getMessage());return'error';}
    }
    public function actualizarchet($id_cabecera,$id_detalles){
        return $this->save("UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva=? AND id_reserva_detalle=?",[$id_cabecera,$id_detalles])?'ok':'error';
    }
}
?>