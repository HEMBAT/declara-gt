<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Periodo;
use App\Models\TipoDte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentoController extends Controller
{
    public function index(Request $request): View
    {
        $periodosDisponibles = Documento::query()->distinct()->pluck('periodo')
            ->merge(Periodo::query()->pluck('periodo'))
            ->unique()->sort()->values();

        $periodo = $request->query('periodo') ?: $periodosDisponibles->last();
        $direccion = $request->query('direccion');
        $tipo = $request->query('tipo');

        $documentos = Documento::query()
            ->with(['cliente', 'tipoDte'])
            ->when($periodo, fn ($query) => $query->where('periodo', $periodo))
            ->when($direccion, fn ($query) => $query->where('direccion', $direccion))
            ->when($tipo, fn ($query) => $query->where('tipo', $tipo))
            ->orderBy('fecha_emision')
            ->get();

        return view('documentos.index', [
            'periodosDisponibles' => $periodosDisponibles,
            'periodo' => $periodo,
            'periodoActual' => $periodo,
            'direccion' => $direccion,
            'tipo' => $tipo,
            'documentos' => $documentos,
        ]);
    }

    public function actualizarTipo(Request $request, Documento $documento): JsonResponse
    {
        if ((float) $documento->idp > 0) {
            return response()->json(['ok' => false, 'error' => 'Los documentos con IDP (impuesto de petróleo) siempre son combustible.'], 422);
        }

        $datos = $request->validate([
            'tipo' => 'required|in:bien,servicio,combustible',
        ]);

        $documento->update($datos);

        return response()->json(['ok' => true]);
    }

    public function actualizarCredito(Request $request, Documento $documento): JsonResponse
    {
        if ($documento->direccion->value !== 'recibida') {
            return response()->json(['ok' => false, 'error' => 'Solo los documentos recibidos tienen crédito fiscal.'], 422);
        }

        if (TipoDte::esPequenoContribuyente($documento->tipoDte->codigo)) {
            return response()->json(['ok' => false, 'error' => 'Las facturas de Pequeño Contribuyente (FPEQ/FCAP) nunca generan crédito fiscal.'], 422);
        }

        $datos = $request->validate([
            'genera_credito' => 'required|boolean',
        ]);

        $documento->update($datos);

        return response()->json(['ok' => true]);
    }
}
