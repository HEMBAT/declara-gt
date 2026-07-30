<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case Bien = 'bien';
    case Servicio = 'servicio';
    case Combustible = 'combustible';
}
