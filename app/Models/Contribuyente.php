<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('contribuyente')]
#[Fillable(['nit', 'nombre'])]
class Contribuyente extends Model
{
    use HasFactory;

    public static function actual(): ?self
    {
        return self::first();
    }

    public static function nitBloqueado(): bool
    {
        return Documento::query()->exists();
    }
}
