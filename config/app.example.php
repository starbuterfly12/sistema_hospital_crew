<?php

return [
    'base_url' => 'http://IP_O_HOST_INTERNO/sistema_hospital',

    // Entorno de ejecucion. Controla la visibilidad de errores en pantalla (H-02):
    //   'development' -> se muestran los errores (uso local / pruebas)
    //   'production'  -> los errores NO se muestran, solo se registran en el log
    // En el servidor institucional DEBE quedar en 'production'.
    'env' => 'development',
];
