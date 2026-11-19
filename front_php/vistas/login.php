<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white text-center py-3">
                <h4 class="m-0">Iniciar Sesión</h4>
            </div>
            <div class="card-body p-4">
                
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $e): ?>
                                <li><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="/login">
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="username">Usuario</label>
                        <input type="text" name="username" id="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="password">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">Entrar al Sistema</button>
                </form>

            </div>
        </div>
    </div>
</div>