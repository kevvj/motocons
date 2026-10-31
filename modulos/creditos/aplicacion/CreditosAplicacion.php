<?php
// Carga los casos de uso del módulo créditos (fase 3). Se incluye UNA vez desde el controlador,
// después del dominio y los modelos:
//   require_once RAIZ . '/modulos/creditos/aplicacion/CreditosAplicacion.php';
require_once __DIR__ . '/CreditosAgregado.php';
require_once __DIR__ . '/CreditosListarRequisitos.php';
require_once __DIR__ . '/CreditosRadicarSolicitud.php';
require_once __DIR__ . '/CreditosAdjuntarSoporte.php';
require_once __DIR__ . '/CreditosEvaluarRequisito.php';
require_once __DIR__ . '/CreditosAprobarCredito.php';
require_once __DIR__ . '/CreditosRechazarCredito.php';
require_once __DIR__ . '/CreditosAnularSolicitud.php';
