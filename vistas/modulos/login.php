<div class="login">
    <div class="login_wrapper">
        <div class="animate form login_form">
            <section class="login_content">
                <form method="post" action="./">
                    <?php echo Seguridad::campoCsrf(); ?>
                    <h1 style="color:aliceblue">Bienvenido</h1>
                    <div>
                        <input type="email" class="form-control" placeholder="Correo electrónico"
                               name="imgEmail" maxlength="254" autocomplete="username" required autofocus>
                    </div>
                    <div>
                        <input type="password" class="form-control" placeholder="Contraseña"
                               name="imgPassword" maxlength="200" autocomplete="current-password" required>
                    </div>
                    <?php if (!empty($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo Seguridad::escapar($_SESSION['login_error']); ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    <div class="text-center">
                        <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Ingresar</button>
                    </div>
                    <div class="clearfix"></div>
                    <div class="separator">
                        <p style="color:aliceblue">Las cuentas son creadas por administración.</p>
                        <div class="clearfix"></div><br>
                        <div><h1 style="color:aliceblue"><i class="fa fa-paw"></i> LalyPet's!</h1></div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
