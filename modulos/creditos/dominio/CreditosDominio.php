<?php
// Carga el dominio del módulo créditos. Se incluye UNA vez desde el controlador:
//   require_once RAIZ . '/modulos/creditos/dominio/CreditosDominio.php';
// El repositorio no usa Composer ni namespaces: aquí van todos los require_once en orden.

// Excepciones primero: las demás clases las usan en tiempo de ejecución.
require_once __DIR__ . '/CreditosExcepcion.php';
require_once __DIR__ . '/CreditosRequisitoCreditoInvalidoExcepcion.php';
require_once __DIR__ . '/CreditosVentaNoEditableExcepcion.php';

// Entidades y agregado.
require_once __DIR__ . '/CreditosRequisito.php';
require_once __DIR__ . '/CreditosSoporteRequisito.php';
require_once __DIR__ . '/CreditosSolicitudCredito.php';
