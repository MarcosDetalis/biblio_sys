// Módulo de autores. Se mantiene separado para que el botón no dependa de inline JS.
$(function(){ $(document).on('click','#btnNuevoAutor',function(){ if(typeof window.frmAutor==='function') window.frmAutor(); }); });
