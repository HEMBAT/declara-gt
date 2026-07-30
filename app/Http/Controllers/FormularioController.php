<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Periodo;
use App\Servicios\CalculoDesgloseSat2237PeriodoService;
use App\Servicios\CalculoIvaPeriodoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormularioController extends Controller
{
    public function index(Request $request): View
    {
        $periodosDisponibles = Documento::query()->distinct()->pluck('periodo')
            ->merge(Periodo::query()->pluck('periodo'))
            ->unique()->sort()->values();

        $periodo = $request->query('periodo') ?: $periodosDisponibles->last();

        $datos = [
            'periodosDisponibles' => $periodosDisponibles,
            'periodo' => $periodo,
            'periodoActual' => $periodo,
        ];

        if ($periodo !== null) {
            $datos['desglose'] = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo($periodo);
            $datos['resultadoIva'] = (new CalculoIvaPeriodoService)->calcularPeriodo($periodo);
        }

        return view('formulario.index', $datos);
    }
}
