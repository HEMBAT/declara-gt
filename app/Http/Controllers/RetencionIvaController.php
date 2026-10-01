<?php

namespace App\Http\Controllers;

use App\Models\RetencionIva;
use App\Servicios\CalculoIvaPeriodoService;
use App\Support\Formato;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Constancias de retención de IVA y saldo de retenciones del SAT-2237
 * (cuadro 7). Cada cambio recalcula el IVA desde el período tocado, porque
 * el saldo de retenciones se arrastra mes a mes igual que el remanente de
 * crédito.
 */
class RetencionIvaController extends Controller
{
    private const REGLA_MONTO = ['nullable', 'regex:/^\d{1,10}(\.\d{1,2})?$/'];

    public function guardar(Request $request): RedirectResponse
    {
        $request->merge(['monto' => Formato::limpiarMontoIngresado($request->input('monto'))]);

        $datos = $request->validate([
            'periodo' => 'required|date_format:Y-m',
            'monto' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/', 'not_regex:/^0+(\.0+)?$/'],
            'descripcion' => 'nullable|string|max:255',
        ], [
            'monto.regex' => 'Escribe el monto como un número positivo, por ejemplo 96.43.',
            'monto.not_regex' => 'El monto debe ser mayor que cero.',
        ]);

        RetencionIva::create($datos);
        (new CalculoIvaPeriodoService)->recalcularDesde($datos['periodo']);

        return redirect()->route('retenciones.index', ['periodo' => $datos['periodo']])->with('exito', 'Retención de IVA registrada.');
    }

    public function eliminar(RetencionIva $retencionIva): RedirectResponse
    {
        $periodo = $retencionIva->periodo;
        $retencionIva->delete();
        (new CalculoIvaPeriodoService)->recalcularDesde($periodo);

        return redirect()->route('retenciones.index', ['periodo' => $periodo])->with('exito', 'Retención de IVA eliminada.');
    }

    /**
     * Registra lo que muestra Declaraguate para el saldo de retenciones del
     * período: el remanente anterior y, si hubo, la devolución acreditada en
     * cuenta bancaria con su número de resolución. Un campo vacío se borra.
     */
    public function guardarSaldo(Request $request, string $periodo): RedirectResponse
    {
        $request->merge([
            'remanente_retenciones_anterior_sat' => Formato::limpiarMontoIngresado($request->input('remanente_retenciones_anterior_sat')),
            'acreditamiento_retenciones' => Formato::limpiarMontoIngresado($request->input('acreditamiento_retenciones')),
        ]);

        $datos = $request->validate([
            'remanente_retenciones_anterior_sat' => self::REGLA_MONTO,
            'acreditamiento_retenciones' => self::REGLA_MONTO,
            'resolucion_acreditamiento' => 'nullable|string|max:50|required_with:acreditamiento_retenciones',
        ], [
            'remanente_retenciones_anterior_sat.regex' => 'Escribe el remanente como un monto positivo, por ejemplo 1585.',
            'acreditamiento_retenciones.regex' => 'Escribe el monto acreditado como un número positivo.',
            'resolucion_acreditamiento.required_with' => 'Anota el número de resolución del SAT que autorizó el acreditamiento.',
        ]);

        $vacioANull = fn (?string $valor): ?string => ($valor ?? '') === '' ? null : $valor;

        (new CalculoIvaPeriodoService)->anotarEnPeriodo($periodo, [
            'remanente_retenciones_anterior_sat' => $vacioANull($datos['remanente_retenciones_anterior_sat'] ?? null),
            'acreditamiento_retenciones' => $vacioANull($datos['acreditamiento_retenciones'] ?? null),
            'resolucion_acreditamiento' => $vacioANull($datos['resolucion_acreditamiento'] ?? null),
        ]);

        return redirect()->route('retenciones.index', ['periodo' => $periodo])->with('exito', 'Saldo de retenciones de IVA actualizado.');
    }
}
