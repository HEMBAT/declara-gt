<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El archivo subido no cumple los requisitos mínimos para ser importado
 * (extensión, tipo MIME, tamaño, hoja u columnas esperadas).
 */
class ArchivoImportacionInvalidoException extends RuntimeException {}
