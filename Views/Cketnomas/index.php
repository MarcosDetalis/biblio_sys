<?php include "Views/Templates/header.php"; ?>
<script src="<?php echo base_url; ?>Assets/node_modules/html5-qrcode/html5-qrcode.min.js"></script>

<div class="app-title">
    <div>
        <h1><i class="fa fa-dashboard"></i> cketnommas</h1>
    </div>
</div>


<div class="row"> 

<form id="frm_id_mov_det">
<input type="hidden" name="id_mov_cab" value="34">

    <input type="checkbox" name="id_mov_det[]" value="1">
      <span>aipo libro</span>

      <input type="checkbox" name="id_mov_det[]" value="2">
      <span>biografia del malon</span>

                </div>
       
        <button class="btn btn-primary mt-2 btn-block" type="button" onclick="registrarcKETNOMA(event);">Guardar</button>
 
 </form>



</script>
<?php include "Views/Templates/footer.php"; ?>