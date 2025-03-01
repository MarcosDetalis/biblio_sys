

function btncambiarEstado(event){
    event.preventDefault();
    console.log("holaa");
    const checkedCheckboxes = document.querySelectorAll('input[name="id_detalle[]"]:checked');
  
      // Crear un array para almacenar los valores
      const selectedValues = [];
      checkedCheckboxes.forEach(checkbox => {
        selectedValues.push(checkbox.value);
      });
  
      // Mostrar los valores en la consola
      console.log('Valores seleccionados:', selectedValues);
      
    const http = new XMLHttpRequest();
    const frm = document.getElementById("myForm");
    const url = base_url + "EscanerQR/cambiarEstadosChecks";
    http.open("POST", url);
    http.send(new FormData(frm));
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
          
            if (res == 'ok') {
                alertas('Libros Chequeados', 'success');
                // location.reload();
            }if (res == 'cancelado') {
                alertas('SE CANCELO', 'success');
            }
            if (res ==''){
                alertas('Error pos error', 'error');
            }
            
        }
     }
  }