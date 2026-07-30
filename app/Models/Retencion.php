<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('retenciones')]
#[Fillable(['periodo', 'monto_isr', 'descripcion'])]
class Retencion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monto_isr' => 'decimal:2',
        ];
    }
}
