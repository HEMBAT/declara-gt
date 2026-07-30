<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Periodo;
use App\Models\Retencion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RetencionController extends Controller
{
    public function index(Request $request): View
    {
        $periodosDisponibles = Documento::query()->distinct()->pluck('periodo')
            ->merge(Periodo::query()->pluck('periodo'))
            ->merge([now()->format('Y-m')])
            ->unique()->sort()->values();

        $periodo = $request->query('periodo') ?: $periodosDisponibles->last();

        $retenciones = Retencion::query()
            ->where('periodo', $periodo)
            ->orderByDesc('created_at')
            ->get();

        return view('retenciones.index', [
            'periodosDisponibles' => $periodosDisponibles,
            'periodo' => $periodo,
            'periodoActual' => $periodo,
            'retenciones' => $retenciones,
            'totalRetenciones' => $retenciones->sum('monto_isr'),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'periodo' => 'required|date_format:Y-m',
            'monto_isr' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Retencion::create($datos);

        return redirect()->route('retenciones.index', ['periodo' => $datos['periodo']])->with('exito', 'Retención registrada.');
    }

    public function eliminar(Retencion $retencion): RedirectResponse
    {
        $periodo = $retencion->periodo;
        $retencion->delete();

        return redirect()->route('retenciones.index', ['periodo' => $periodo])->with('exito', 'Retención eliminada.');
    }
}
