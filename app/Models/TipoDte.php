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

    /**
     * Catálogo de tipos de DTE del FEL guatemalteco que la app conoce.
     *
     * Vive aquí y no solo en el seeder para que el importador cree un tipo
     * conocido con sus datos correctos aunque la base nunca se haya vuelto a
     * sembrar, y para distinguir los códigos realmente desconocidos.
     *
     * Signos: NCRE (nota de crédito) resta de la base gravable, es el único
     * signo negativo explícito en el brief. NDEB (nota de débito) es +1 por
     * ser el opuesto simétrico de NCRE: aumenta lo que debe el receptor.
     * NABN (nota de abono) queda marcada "revisar": no lleva IVA y su efecto
     * en el ISR amerita confirmación de un contador, así que por ahora queda
     * fuera de todo cálculo (ver CODIGOS_FUERA_DE_CALCULO).
     *
     * @var array<string, array{nombre: string, signo: int, revisar: bool}>
     */
    public const CATALOGO = [
        'FACT' => ['nombre' => 'Factura', 'signo' => 1, 'revisar' => false],
        'FCAM' => ['nombre' => 'Factura Cambiaria', 'signo' => 1, 'revisar' => false],
        'FPEQ' => ['nombre' => 'Factura Pequeño Contribuyente', 'signo' => 1, 'revisar' => false],
        'FCAP' => ['nombre' => 'Factura Cambiaria Pequeño Contribuyente', 'signo' => 1, 'revisar' => false],
        'FESP' => ['nombre' => 'Factura Especial', 'signo' => 1, 'revisar' => false],
        'RECI' => ['nombre' => 'Recibo', 'signo' => 1, 'revisar' => false],
        'NDEB' => ['nombre' => 'Nota de Débito', 'signo' => 1, 'revisar' => false],
        'NABN' => ['nombre' => 'Nota de Abono', 'signo' => 1, 'revisar' => true],
        'NCRE' => ['nombre' => 'Nota de Crédito', 'signo' => -1, 'revisar' => false],
    ];

    /**
     * Documentos del régimen de Pequeño Contribuyente: pagan un 5% fijo sobre
     * ingresos en vez de trasladar IVA, así que nunca generan crédito fiscal
     * para quien los recibe. Es una regla de la ley, no una preferencia.
     *
     * @var list<string>
     */
    public const CODIGOS_PEQUENO_CONTRIBUYENTE = ['FPEQ', 'FCAP'];

    /**
     * Documentos que se guardan y se cuentan, pero no entran a ningún cálculo
     * (ISR, IVA ni bases del SAT-2237). La nota de abono se emite sin IVA y
     * sumarla como factura inflaría la base del ISR o la de compras.
     *
     * @var list<string>
     */
    public const CODIGOS_FUERA_DE_CALCULO = ['NABN'];

    public static function esPequenoContribuyente(string $codigo): bool
    {
        return in_array($codigo, self::CODIGOS_PEQUENO_CONTRIBUYENTE, true);
    }

    public static function quedaFueraDeCalculo(string $codigo): bool
    {
        return in_array($codigo, self::CODIGOS_FUERA_DE_CALCULO, true);
    }

    public static function esConocido(string $codigo): bool
    {
        return array_key_exists($codigo, self::CATALOGO);
    }

    /**
     * Atributos con que se crea un tipo al importarlo por primera vez: los del
     * catálogo si se conoce, o +1 marcado "revisar" si no.
     *
     * @return array{nombre: string, signo: int, revisar: bool}
     */
    public static function atributosParaCodigo(string $codigo): array
    {
        return self::CATALOGO[$codigo] ?? ['nombre' => $codigo, 'signo' => 1, 'revisar' => true];
    }

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
