<?php

namespace App\Http\Controllers;

use App\Models\ParametroImpuesto;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ParametroImpuestoController extends Controller
{
    public function index(): View
    {
        return view('parametros.index', [
            'iva' => ParametroImpuesto::query()->where('tipo', 'IVA')->orderBy('vigente_desde')->get(),
            'isr' => ParametroImpuesto::query()->where('tipo', 'ISR_TRAMO')->orderBy('vigente_desde')->orderBy('limite_inferior')->get(),
        ]);
    }

    /**
     * Agrega una nueva vigencia de IVA, cerrando la anterior en la misma
     * transacción. Los parámetros nunca se editan ni se borran (§3 del brief).
     */
    public function nuevaVigenciaIva(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tasa' => 'required|numeric|min:0|max:100',
            'vigente_desde' => 'required|date',
        ]);

        DB::transaction(function () use ($datos) {
            $nuevaDesde = Carbon::parse($datos['vigente_desde']);

            ParametroImpuesto::query()->where('tipo', 'IVA')->whereNull('vigente_hasta')
                ->update(['vigente_hasta' => $nuevaDesde->copy()->subDay()]);

            ParametroImpuesto::create([
                'tipo' => 'IVA',
                'tasa' => number_format(((float) $datos['tasa']) / 100, 4, '.', ''),
                'limite_inferior' => null,
                'limite_superior' => null,
                'vigente_desde' => $nuevaDesde,
                'vigente_hasta' => null,
            ]);
        });

        return redirect()->route('parametros.index')->with('exito', 'Nueva vigencia de IVA agregada.');
    }

    public function nuevaVigenciaIsr(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tramo1_tasa' => 'required|numeric|min:0|max:100',
            'tramo1_limite' => 'required|numeric|min:0.01',
            'tramo2_tasa' => 'required|numeric|min:0|max:100',
            'vigente_desde' => 'required|date',
        ]);

        DB::transaction(function () use ($datos) {
            $nuevaDesde = Carbon::parse($datos['vigente_desde']);

            ParametroImpuesto::query()->where('tipo', 'ISR_TRAMO')->whereNull('vigente_hasta')
                ->update(['vigente_hasta' => $nuevaDesde->copy()->subDay()]);

            $limite1 = number_format((float) $datos['tramo1_limite'], 2, '.', '');
            $limite2Inferior = number_format(((float) $datos['tramo1_limite']) + 0.01, 2, '.', '');

            ParametroImpuesto::create([
                'tipo' => 'ISR_TRAMO',
                'tasa' => number_format(((float) $datos['tramo1_tasa']) / 100, 4, '.', ''),
                'limite_inferior' => '0.00',
                'limite_superior' => $limite1,
                'vigente_desde' => $nuevaDesde,
                'vigente_hasta' => null,
            ]);

            ParametroImpuesto::create([
                'tipo' => 'ISR_TRAMO',
                'tasa' => number_format(((float) $datos['tramo2_tasa']) / 100, 4, '.', ''),
                'limite_inferior' => $limite2Inferior,
                'limite_superior' => null,
                'vigente_desde' => $nuevaDesde,
                'vigente_hasta' => null,
            ]);
        });

        return redirect()->route('parametros.index')->with('exito', 'Nueva vigencia de ISR agregada.');
    }
}
