<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tipos_dte')]
#[Fillable(['codigo', 'nombre', 'signo', 'revisar'])]
class TipoDte extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'signo' => 'integer',
            'revisar' => 'boolean',
        ];
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
