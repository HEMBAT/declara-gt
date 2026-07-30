<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nit', 'nombre', 'tipo_default', 'genera_credito_default'])]
class Cliente extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo_default' => TipoDocumento::class,
            'genera_credito_default' => 'boolean',
        ];
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
