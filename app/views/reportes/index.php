<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <style>
        .tarjetas-reportes { display: flex; flex-wrap: wrap; gap: 16px; padding: 0; list-style: none; }
        .tarjeta-reporte { border: 1px solid #999; border-radius: 4px; padding: 12px 16px; width: 260px; }
        .tarjeta-reporte h3 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>Reportes</h1>
    <p>Módulo de solo consulta. Los tres roles (Administrador, Operativo, Visualizador) pueden consultar y exportar todos los reportes.</p>

    <ul class="tarjetas-reportes">
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=movimientos">Movimientos por período</a></h3>
            <p>Traslados, préstamos, devoluciones, requisiciones entregadas, bajas finalizadas y verificaciones, en una sola línea de tiempo.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=bienesActividad">Bienes con actividad</a></h3>
            <p>Bienes que tuvieron al menos un evento en el período: cantidad, primer y último evento.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=bienesSinAsignacion">Bienes sin responsable/asignación</a></h3>
            <p>Detecta bienes Activo con alguna anomalía operativa real — no incluye bienes en Baja.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=prestamos">Préstamos pendientes o vencidos</a></h3>
            <p>Préstamos activos o parcialmente devueltos, con días de vencimiento cuando aplica.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=bajas">Bajas por período</a></h3>
            <p>Bajas finalizadas (u otros estados si se filtran) con su tipo, valor y bodega destino.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=verificaciones">Verificaciones con diferencias</a></h3>
            <p>Verificaciones físicas registradas, usando los datos congelados al momento de verificar.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=ingresos">Ingresos de bienes</a></h3>
            <p>Bienes ingresados en el período, con su forma de ingreso y procedencia/proveedor.</p>
        </li>
        <li class="tarjeta-reporte">
            <h3><a href="index.php?modulo=reportes&accion=resumen">Resumen de movimientos</a></h3>
            <p>Cantidad de operaciones y de bienes involucrados por tipo, dentro de un período.</p>
        </li>
    </ul>

    <p><a href="index.php?modulo=dashboard">Volver al panel principal</a></p>
</body>
</html>
