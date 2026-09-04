<?php include "Views/Templates/header.php"; ?>
<div class="app-title"><div><h1><i class="fa fa-bar-chart"></i> Reporte de reservas</h1><p>Pendientes, retiradas, devueltas y canceladas, filtrable por fecha, estado y usuario.</p></div></div>

<div class="row mb-3" id="contadoresReporte">
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cReserva">0</h3><div class="text-muted small">Pendientes</div></div></div>
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cParcial">0</h3><div class="text-muted small">Parciales</div></div></div>
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cRetirado">0</h3><div class="text-muted small">Retirados</div></div></div>
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cDevuelto">0</h3><div class="text-muted small">Devueltos</div></div></div>
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cCancelada">0</h3><div class="text-muted small">Canceladas</div></div></div>
    <div class="col-md-2 col-6 mb-2"><div class="tile text-center"><h3 class="mb-0" id="cVencida">0</h3><div class="text-muted small">Vencidas</div></div></div>
</div>

<div class="tile mb-3">
    <div class="row">
        <div class="col-md-2"><label class="small mb-1">Desde</label><input type="date" id="fDesde" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="small mb-1">Hasta</label><input type="date" id="fHasta" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="small mb-1">Estado</label>
            <select id="fEstado" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="Reserva">Pendiente</option>
                <option value="Parcial">Parcial</option>
                <option value="Retirado">Retirado</option>
                <option value="Devuelto">Devuelto</option>
                <option value="Cancelada">Cancelada</option>
                <option value="Vencida">Vencida</option>
            </select>
        </div>
        <div class="col-md-2"><label class="small mb-1">Tipo</label>
            <select id="fTipo" class="form-control form-control-sm">
                <option value="">Alumno y Profesor</option>
                <option value="1">Solo Alumno</option>
                <option value="2">Solo Profesor</option>
            </select>
        </div>
        <div class="col-md-3"><label class="small mb-1">Nombre o cédula</label><input type="text" id="fTexto" class="form-control form-control-sm" placeholder="Buscar usuario..."></div>
        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary btn-sm btn-block" onclick="aplicarFiltros()"><i class="fa fa-filter"></i></button></div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <button class="btn btn-secondary btn-sm" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar filtros</button>
            <a class="btn btn-danger btn-sm" id="btnExportarPdf" target="_blank" href="#"><i class="fa fa-file-pdf-o"></i> Exportar PDF (con estos filtros)</a>
        </div>
    </div>
</div>

<div class="tile"><div class="table-responsive"><table class="table table-bordered table-hover" id="tblReporte">
    <thead class="thead-dark"><tr><th>Reserva</th><th>Usuario</th><th>Tipo</th><th>Libros</th><th>Fecha</th><th>Estado</th></tr></thead>
    <tbody></tbody>
</table></div></div>

<script>
let tblReporte;
function renderBadgeEstadoReporte(estado){
    const colores={Reserva:'secondary',Parcial:'info',Retirado:'warning',Devuelto:'success',Cancelada:'danger',Vencida:'dark'};
    return `<span class="badge badge-${colores[estado]||'secondary'}">${estado}</span>`;
}
function armarQuery(){
    const p=new URLSearchParams();
    const desde=document.getElementById('fDesde').value; if(desde)p.append('fecha_desde',desde);
    const hasta=document.getElementById('fHasta').value; if(hasta)p.append('fecha_hasta',hasta);
    const estado=document.getElementById('fEstado').value; if(estado)p.append('estado',estado);
    const tipo=document.getElementById('fTipo').value; if(tipo)p.append('tipo',tipo);
    const texto=document.getElementById('fTexto').value.trim(); if(texto)p.append('texto',texto);
    return p.toString();
}
function actualizarContadores(filas){
    const cont={Reserva:0,Parcial:0,Retirado:0,Devuelto:0,Cancelada:0,Vencida:0};
    filas.forEach(r=>{ if(cont[r.estado_simple]!==undefined) cont[r.estado_simple]++; });
    document.getElementById('cReserva').textContent=cont.Reserva;
    document.getElementById('cParcial').textContent=cont.Parcial;
    document.getElementById('cRetirado').textContent=cont.Retirado;
    document.getElementById('cDevuelto').textContent=cont.Devuelto;
    document.getElementById('cCancelada').textContent=cont.Cancelada;
    document.getElementById('cVencida').textContent=cont.Vencida;
}
function aplicarFiltros(){
    const qs=armarQuery();
    document.getElementById('btnExportarPdf').href=base_url+'EscanerQr/reportePdf'+(qs?'?'+qs:'');
    tblReporte.ajax.url(base_url+'EscanerQr/reporte'+(qs?'?'+qs:'')).load(actualizarContadores);
}
function limpiarFiltros(){
    document.getElementById('fDesde').value='';document.getElementById('fHasta').value='';
    document.getElementById('fEstado').value='';document.getElementById('fTipo').value='';document.getElementById('fTexto').value='';
    aplicarFiltros();
}
window.addEventListener('load',function(){
    tblReporte=$('#tblReporte').DataTable({
        ajax:{url:base_url+'EscanerQr/reporte',dataSrc:function(json){actualizarContadores(json);return json;}},
        columns:[
            {data:'numero_reserva'},
            {data:'usuario'},
            {data:'tipo_usuario'},
            {data:'libros'},
            {data:'fecha_reserva'},
            {data:'estado_simple',render:renderBadgeEstadoReporte}
        ],
        order:[[4,'desc']],
        language:{lengthMenu:'Mostrar _MENU_ Entradas',info:'Mostrando _START_ a _END_ de _TOTAL_ Entradas',infoEmpty:'Mostrando 0 a 0 de 0 Entradas',infoFiltered:'(Filtrado de _MAX_ total entradas)',zeroRecords:'Sin resultados encontrados',emptyTable:'No hay reservas',search:'Buscar:',processing:'Procesando...',paginate:{first:'Primero',last:'Último',next:'Siguiente',previous:'Anterior'}}
    });
    document.getElementById('btnExportarPdf').href=base_url+'EscanerQr/reportePdf';
});
</script>
<?php include "Views/Templates/footer.php"; ?>
