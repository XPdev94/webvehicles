<?php

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    ?>
    <!doctype html>
    <html lang="es">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Servicio no disponible · OSG Vehículos</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <main class="shell shell-login">
            <section class="card">
                <h1>Versión nueva de Vehículos</h1>
                <p>Esta versión necesita un servidor con PHP 8 y el controlador PDO para MySQL.</p>
                <a class="button button-secondary" href="../index.php">Abrir el registro actual</a>
            </section>
        </main>
    </body>

    </html>
    <?php
    exit;
}

define('WEBVEHICLES_ENTRY', true);
require dirname(__FILE__) . '/app.php';
