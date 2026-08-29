<div class="login">
    <a class="hiddenanchor" id="signup"></a>
    <a class="hiddenanchor" id="signin"></a>

    <div class="login_wrapper">
        <div class="animate form login_form">
            <section class="login_content">
            <form method="post">
                <?php echo Seguridad::campoCsrf(); ?>
                <h1 style="color:aliceblue">Bienvenido</h1>
                <div>
                <input type="text" class="form-control" placeholder="Username" name="imgUsuario" required />
                </div>
                <div>
                <input type="password" class="form-control" placeholder="Password" name="imgPassword" required />
                </div>
                <div>
                <div class="text-center">
                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Ingersar</button>
                </div>
                <?php
                    //instanciamos al controlador tambirn al metodo de inicio de session
                    $login= new ControladorUsuarios();
                    $login ->ctrIngresarUsuarios()
                ?>
                <a class="reset_pass" style="color:aliceblue" href="#">Ha perdido su contraseña?</a>
                </div>

                <div class="clearfix"></div>

                <div class="separator">
                <p class="change_link" style="color:aliceblue">Nuevo usuario?
                    <a href="#signup" class="to_register" style="color:aliceblue"> Comunicarse con la administradora </a>
                </p>

                <div class="clearfix"></div>
                <br />

                <div>
                    <h1 style="color:aliceblue"><i class="fa fa-paw" style="color:aliceblue"></i> LalyPet's!</h1>
                    
                </div>
                </div>
            </form>
            </section>
        </div>

        <div id="register" class="animate form registration_form">
            <section class="login_content">
            <form>
                <h1>Create Account</h1>
                <div>
                <input type="text" class="form-control" placeholder="Username" required="" />
                </div>
                <div>
                <input type="email" class="form-control" placeholder="Email" required="" />
                </div>
                <div>
                <input type="password" class="form-control" placeholder="Password" required="" />
                </div>
                <div>
                <a class="btn btn-default submit" href="index.html">Submit</a>
                </div>

                <div class="clearfix"></div>

                <div class="separator">
                <p class="change_link">Already a member ?
                    <a href="#signin" class="to_register"> Log in </a>
                </p>

                <div class="clearfix"></div>
                <br />

                <div>
                    <h1><i class="fa fa-paw"></i> Gentelella Alela!</h1>
                    <p>©2016 All Rights Reserved. Gentelella Alela! is a Bootstrap 3 template. Privacy and Terms</p>
                </div>
                </div>
            </form>
            </section>
        </div>
    </div>
</div>
