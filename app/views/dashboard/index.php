<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel principal</title>
</head>
<body>
    <h1>Panel principal</h1>

    <p>Hola, <?php echo htmlspecialchars($nombre_completo ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Rol: <?php echo htmlspecialchars($nombre_rol ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Usuario: <?php echo htmlspecialchars($usuario ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

    <h2>Módulos</h2>
    <ul>
        <li><a href="index.php?modulo=bienes">Bienes</a></li>
        <li><a href="index.php?modulo=responsables">Responsables</a></li>
        <li><a href="index.php?modulo=ubicaciones">Áreas y ubicaciones</a></li>
        <li><a href="index.php?modulo=asignaciones">Asignaciones</a></li>
        <li>Tarjetas de responsabilidad</li>
        <li>Movimientos</li>
        <li>Préstamos</li>
        <li>Devoluciones</li>
        <li>Bajas</li>
        <li>Verificaciones físicas</li>
        <li>Reportes</li>
        <li>Usuarios</li>
        <li>Bitácora</li>
        <li>Respaldos</li>
    </ul>
</body>
</html>
