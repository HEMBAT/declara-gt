<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * No se puede importar sin que el contribuyente (dueño de la instalación)
 * esté configurado, ya que su NIT es lo que determina la dirección
 * (emitida/recibida) de cada documento.
 */
class ContribuyenteNoConfiguradoException extends RuntimeException {}
