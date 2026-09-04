<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url; ?>Assets/css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url; ?>Assets/css/font-awesome.min.css">
    <title>Recuperar contraseña</title>
</head>

<body>
    <section class="material-half-bg">
        <div class="cover"></div>
    </section>
    <section class="login-content">
        <div class="logo">
            <h1>Recuperar contraseña</h1>
        </div>
        <div class="login-box">

            <!-- Paso 1: cédula + correo -->
            <form class="login-form" id="frmPaso1" onsubmit="frmSolicitarCodigo(event);">
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-key"></i>Verificar identidad</h3>
                <p class="text-muted">Ingresá tu cédula y tu correo registrados. Si coinciden con una cuenta, te enviaremos un código de verificación por correo.</p>
                <div class="form-group">
                    <label class="control-label">CÉDULA</label>
                    <input class="form-control" type="text" placeholder="Cédula" id="cedula" name="cedula" autofocus required>
                </div>
                <div class="form-group">
                    <label class="control-label">CORREO ELECTRÓNICO</label>
                    <input class="form-control" type="email" placeholder="Correo electrónico" id="correo" name="correo" required>
                </div>
                <div class="alert alert-danger d-none" role="alert" id="alertaPaso1"></div>
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit" id="btnPaso1"><i class="fa fa-paper-plane fa-lg fa-fw"></i>Enviar código</button>
                </div>
            </form>

            <!-- Paso 2: código de 6 dígitos -->
            <form class="login-form d-none" id="frmPaso2" onsubmit="frmVerificarCodigo(event);">
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-envelope-o"></i>Ingresá el código</h3>
                <p class="text-muted">Te enviamos un código de 6 dígitos a tu correo. Es válido durante 10 minutos y tiene un máximo de 3 intentos.</p>
                <div class="form-group">
                    <label class="control-label">CÓDIGO DE VERIFICACIÓN</label>
                    <input class="form-control text-center" style="letter-spacing:8px;font-size:1.4rem;" type="text" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" id="codigo" name="codigo" autofocus required>
                </div>
                <div class="alert alert-danger d-none" role="alert" id="alertaPaso2"></div>
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit" id="btnPaso2"><i class="fa fa-check fa-lg fa-fw"></i>Verificar código</button>
                </div>
                <p class="text-center"><a href="#" id="btnReenviar" onclick="reenviarCodigo(event);">Reenviar código</a></p>
            </form>

            <!-- Paso 3: contraseña nueva -->
            <form class="login-form d-none" id="frmPaso3" onsubmit="frmRestablecer(event);">
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-lock"></i>Nueva contraseña</h3>
                <div class="form-group">
                    <label class="control-label">CONTRASEÑA NUEVA</label>
                    <input class="form-control" type="password" placeholder="Contraseña nueva" id="passwordNueva" name="passwordNueva" minlength="6" required>
                </div>
                <div class="form-group">
                    <label class="control-label">CONFIRMAR CONTRASEÑA</label>
                    <input class="form-control" type="password" placeholder="Confirmar contraseña" id="confirmarPassword" name="confirmarPassword" minlength="6" required>
                </div>
                <div class="alert alert-danger d-none" role="alert" id="alertaPaso3"></div>
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit" id="btnPaso3"><i class="fa fa-save fa-lg fa-fw"></i>Guardar nueva contraseña</button>
                </div>
            </form>

            <p class="text-center mt-2"><a href="<?php echo base_url; ?>">Volver a iniciar sesión</a></p>
        </div>
    </section>
    <!-- Essential javascripts for application to work-->
    <script src="<?php echo base_url; ?>Assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo base_url; ?>Assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo base_url; ?>Assets/js/main.js"></script>
    <script src="<?php echo base_url; ?>Assets/js/pace.min.js"></script>
    <script>
        const base_url = '<?php echo base_url; ?>';
    </script>
    <script src="<?php echo base_url; ?>Assets/js/recuperar.js"></script>
</body>

</html>
