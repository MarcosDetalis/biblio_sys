<?php include "Views/Templates/header.php"; ?>
<script src="<?php echo base_url; ?>Assets/node_modules/qrcode/build/qrcode.min.js"></script>
<div class="app-title"><div><h1><i class="fa fa-calendar"></i> Reservas</h1><p>Registro y seguimiento de reservas de ejemplares.</p></div></div>
<button class="btn btn-primary mb-2" onclick="frmReserva()"><i class="fa fa-plus"></i> Nueva reserva</button>
<div class="tile"><div class="table-responsive"><table class="table table-bordered table-hover" id="tblReservas"><thead class="thead-dark"><tr><th>ID</th><th>Número</th><th>Usuario</th><th>Libros</th><th>Estado</th><th>Reserva</th><th>Límite retiro</th><th>QR</th><th></th></tr></thead><tbody></tbody></table></div></div>
<div id="modalReserva" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Nueva reserva</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
<form id="frmReserva" onsubmit="registrarReserva(event)">
    <div class="form-group"><label>Usuario</label><select id="reservaUsuario" name="usuario" class="form-control estudiante" required></select><input type="hidden" id="reservaUsuarioOculto"></div>
    <hr>
    <label>Libros a reservar</label>
    <div class="row">
        <div class="col-7"><select id="reservaLibro" class="form-control libro"></select></div>
        <div class="col-3"><input type="number" id="reservaCantidad" class="form-control" min="1" value="1"></div>
        <div class="col-2"><button type="button" class="btn btn-secondary btn-block" onclick="agregarLibroReserva()"><i class="fa fa-plus"></i></button></div>
    </div>
    <table class="table table-sm mt-2" id="tblCarritoReserva"><thead><tr><th>Libro</th><th>Cantidad</th><th></th></tr></thead><tbody></tbody></table>
    <div id="carritoVacio" class="text-muted small">Todavía no agregaste ningún libro.</div>
    <hr>
    <div class="form-group"><label>Fecha de retiro</label><input type="date" id="fecha_retiro" class="form-control" required></div>
    <div class="form-group"><label>Hora de retiro (07:00 a 20:00)</label><input type="time" id="hora_retiro" class="form-control" min="07:00" max="20:00" step="300" required></div>
    <div class="form-group"><label>Fecha estimada de devolución</label><input type="date" id="fecha_devolucion" class="form-control" required></div>
    <div class="form-group"><label>Hora estimada de devolución (07:00 a 20:00)</label><input type="time" id="hora_devolucion" class="form-control" min="07:00" max="20:00" step="300" required></div>
    <input type="hidden" id="fecha_limite_retiro" name="fecha_limite_retiro">
    <input type="hidden" id="fecha_devolucion_estimada" name="fecha_devolucion_estimada">
    <div id="alertaHorarioReserva" class="alert alert-warning d-none small"></div>
    <div class="form-group"><label>Observación</label><textarea name="observacion" class="form-control"></textarea></div>
    <button class="btn btn-primary" type="submit">Registrar reserva</button>
</form>
</div></div></div></div>

<div id="modalVerQr" class="modal fade"><div class="modal-dialog modal-sm"><div class="modal-content">
    <div class="modal-header bg-info text-white"><h5 class="modal-title">Código QR</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body text-center">
        <p id="qrCodigoTexto" class="small text-muted"></p>
        <canvas id="qrCanvas"></canvas>
        <div class="mt-3"><a id="qrDescargar" class="btn btn-primary btn-block" download="qr.png"><i class="fa fa-download"></i> Descargar</a></div>
    </div>
</div></div></div>

<script>
let tblReservas;
let carritoReserva=[]; // [{id_libro, titulo, cantidad}]
window.addEventListener('load',function(){tblReservas=$('#tblReservas').DataTable({ajax:{url:base_url+'Reservas/listar',dataSrc:''},columns:[{data:'id_reserva'},{data:'numero_reserva'},{data:'usuario'},{data:'titulo'},{data:'estado'},{data:'fecha_reserva'},{data:'fecha_limite_retiro'},{data:'codigo_qr',render:c=>c?`<a href="#" onclick="verQr('${c}');return false;">Ver</a>`:''},{data:null,render:(d,t,r)=>r.activo==1?`<button class="btn btn-danger" onclick="cancelarReserva(${r.id_reserva})"><i class="fa fa-times"></i></button>`:''}],language:{lengthMenu:'Mostrar _MENU_ Entradas',info:'Mostrando _START_ a _END_ de _TOTAL_ Entradas',infoEmpty:'Mostrando 0 a 0 de 0 Entradas',infoFiltered:'(Filtrado de _MAX_ total entradas)',zeroRecords:'Sin resultados encontrados',emptyTable:'No hay reservas',search:'Buscar:',processing:'Procesando...',paginate:{first:'Primero',last:'Último',next:'Siguiente',previous:'Anterior'}}});

    // Alumno/Profesor solo pueden reservar para sí mismos: se registra DESPUÉS
    // del handler genérico que arma el buscador (shown.bs.modal), así el
    // bloqueo se aplica sobre el select2 ya inicializado, sin que quede
    // pisado por esa reinicialización.
    $('#modalReserva').on('shown.bs.modal',function(){
        if(esStaff)return;
        const sel=$('#reservaUsuario');
        sel.val(null).trigger('change');
        const opcion=new Option(usuarioActualNombre,usuarioActualId,true,true);
        sel.append(opcion).trigger('change');
        // Un <select disabled> no se envía al hacer submit del formulario, así que
        // se lo bloquea a la vista y se pasa el valor real por un campo oculto
        // aparte, que sí viaja en el POST.
        sel.prop('disabled',true);
        document.getElementById('reservaUsuarioOculto').name='usuario';
        document.getElementById('reservaUsuarioOculto').value=usuarioActualId;
    });
});
function verQr(codigo){
    document.getElementById('qrCodigoTexto').textContent=codigo;
    const canvas=document.getElementById('qrCanvas');
    QRCode.toCanvas(canvas,codigo,{width:220,margin:2},function(err){
        if(err){alertas('No se pudo generar el QR','error');return;}
        document.getElementById('qrDescargar').href=canvas.toDataURL('image/png');
        document.getElementById('qrDescargar').download=codigo+'.png';
        $('#modalVerQr').modal('show');
    });
}
function frmReserva(){
    document.getElementById('frmReserva').reset();
    $('#reservaUsuario,#reservaLibro').val(null).trigger('change');
    carritoReserva=[];
    pintarCarritoReserva();
    let d=new Date(Date.now()+86400000);
    const pad=n=>String(n).padStart(2,'0');
    document.getElementById('fecha_retiro').value=`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    document.getElementById('hora_retiro').value='08:00';
    let dev=new Date(d.getTime()+3*3600000);
    document.getElementById('fecha_devolucion').value=`${dev.getFullYear()}-${pad(dev.getMonth()+1)}-${pad(dev.getDate())}`;
    document.getElementById('hora_devolucion').value='11:00';
    actualizarFechaHoraOcultos();
    document.getElementById('alertaHorarioReserva').classList.add('d-none');
    $('#modalReserva').modal('show');
}
// Combina fecha_retiro+hora_retiro y fecha_devolucion+hora_devolucion en los
// campos ocultos que espera el servidor ("YYYY-MM-DD HH:MM:00"), y vuelve a
// consultar disponibilidad. Se llama cada vez que cambia cualquiera de los
// 4 campos visibles (fecha/hora de retiro, fecha/hora de devolución).
function actualizarFechaHoraOcultos(){
    const fr=document.getElementById('fecha_retiro').value;
    const hr=document.getElementById('hora_retiro').value;
    const fd=document.getElementById('fecha_devolucion').value;
    const hd=document.getElementById('hora_devolucion').value;
    document.getElementById('fecha_limite_retiro').value=(fr&&hr)?`${fr} ${hr}:00`:'';
    document.getElementById('fecha_devolucion_estimada').value=(fd&&hd)?`${fd} ${hd}:00`:'';
    verificarHorarioReserva();
}
document.addEventListener('change',function(e){
    if(e.target && ['fecha_retiro','hora_retiro','fecha_devolucion','hora_devolucion'].includes(e.target.id)) actualizarFechaHoraOcultos();
});
function agregarLibroReserva(){
    const sel=$('#reservaLibro');
    const idLibro=parseInt(sel.val()||0,10);
    const titulo=sel.find('option:selected').text();
    const cantidad=parseInt($('#reservaCantidad').val()||0,10);
    if(!idLibro){alertas('Seleccione un libro','warning');return;}
    if(!cantidad||cantidad<1){alertas('La cantidad debe ser al menos 1','warning');return;}
    const existente=carritoReserva.find(it=>it.id_libro===idLibro);
    if(existente){existente.cantidad+=cantidad;}
    else{carritoReserva.push({id_libro:idLibro,titulo:titulo,cantidad:cantidad});}
    sel.val(null).trigger('change');
    $('#reservaCantidad').val(1);
    pintarCarritoReserva();
    verificarHorarioReserva();
}
function quitarLibroReserva(idLibro){
    carritoReserva=carritoReserva.filter(it=>it.id_libro!==idLibro);
    pintarCarritoReserva();
    verificarHorarioReserva();
}
function pintarCarritoReserva(){
    const tbody=$('#tblCarritoReserva tbody');
    tbody.empty();
    carritoReserva.forEach(it=>{
        tbody.append(`<tr><td>${it.titulo}</td><td>${it.cantidad}</td><td><button type="button" class="btn btn-sm btn-danger" onclick="quitarLibroReserva(${it.id_libro})"><i class="fa fa-trash"></i></button></td></tr>`);
    });
    $('#carritoVacio').toggle(carritoReserva.length===0);
    $('#tblCarritoReserva').toggle(carritoReserva.length>0);
}
// Consulta en vivo: avisa si algún libro del carrito ya está comprometido
// en la franja horaria elegida, y sugiere el próximo horario libre ese
// mismo día. No bloquea el envío (el servidor vuelve a validar igual),
// solo evita sorpresas antes de enviar el formulario.
function verificarHorarioReserva(){
    const alerta=document.getElementById('alertaHorarioReserva');
    const limite=document.getElementById('fecha_limite_retiro').value;
    const devolucion=document.getElementById('fecha_devolucion_estimada').value;
    if(!carritoReserva.length||!limite||!devolucion){alerta.classList.add('d-none');return;}
    const fd=new FormData();
    fd.append('fecha_limite_retiro',limite);
    fd.append('fecha_devolucion_estimada',devolucion);
    carritoReserva.forEach(it=>{fd.append('libros[]',it.id_libro);fd.append('cantidades[]',it.cantidad);});
    const http=new XMLHttpRequest();
    http.open('POST',base_url+'Reservas/verificarHorario',true);
    http.send(fd);
    http.onreadystatechange=function(){
        if(this.readyState!==4)return;
        try{
            const r=JSON.parse(this.responseText);
            if(r.disponible){alerta.classList.add('d-none');return;}
            let msg=(r.conflictos||[]).join(', ')+' ya está comprometido en esa franja horaria.';
            msg+=r.sugerencia&&r.sugerencia.hora?(' Próximo horario disponible ese día: '+r.sugerencia.hora+'.'):' No hay otro horario disponible ese día, probá con otra fecha.';
            alerta.textContent=msg;
            alerta.classList.remove('d-none');
        }catch(e){alerta.classList.add('d-none');}
    };
}
function registrarReserva(e){
    e.preventDefault();
    if(!carritoReserva.length){alertas('Agregue al menos un libro a la reserva','warning');return;}
    const fd=new FormData(document.getElementById('frmReserva'));
    carritoReserva.forEach(it=>{fd.append('libros[]',it.id_libro);fd.append('cantidades[]',it.cantidad);});
    let x=new XMLHttpRequest();x.open('POST',base_url+'Reservas/registrar');x.send(fd);
    x.onreadystatechange=function(){if(this.readyState===4){let r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#modalReserva').modal('hide');tblReservas.ajax.reload(null,false);}}}
}
function cancelarReserva(id){Swal.fire({title:'¿Cancelar reserva?',icon:'warning',showCancelButton:true,confirmButtonText:'Sí',cancelButtonText:'No'}).then(r=>{if(!r.isConfirmed)return;fetch(base_url+'Reservas/cancelar/'+id).then(x=>x.json()).then(x=>{alertas(x.msg,x.icono);if(x.icono==='success')tblReservas.ajax.reload(null,false);});});}
</script>
<?php include "Views/Templates/footer.php"; ?>
