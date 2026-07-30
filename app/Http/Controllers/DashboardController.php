<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Periodo;
use App\Models\Retencion;
use App\Servicios\CalculoDesgloseSat2237PeriodoService;
use App\Servicios\CalculoIsrPeriodoService;
use App\Servicios\CalculoIvaPeriodoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $periodosDisponibles = $this->periodosDisponibles();

        $periodoQuery = $request->query('periodo');
        if ($periodoQuery === '__none__') {
            $periodoSeleccionado = null;
        } elseif (is_string($periodoQuery) && $periodoQuery !== '') {
            $periodoSeleccionado = $periodoQuery;
        } else {
            $periodoSeleccionado = $periodosDisponibles->last();
        }

        $datos = [
            'periodosDisponibles' => $periodosDisponibles,
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodoActual' => $periodoSeleccionado,
        ];

        if ($periodoSeleccionado !== null) {
            $documentosActivos = Documento::query()
                ->with('tipoDte')
                ->paraCalculoIsr($periodoSeleccionado)
                ->get();

            $datos['resultado'] = (new CalculoIsrPeriodoService)->calcularPeriodo($periodoSeleccionado);
            $datos['resultadoIva'] = (new CalculoIvaPeriodoService)->calcularPeriodo($periodoSeleccionado);
            $datos['desglose'] = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo($periodoSeleccionado);
            $datos['totalFacturado'] = $documentosActivos->sum(fn (Documento $d) => (float) $d->gran_total * $d->tipoDte->signo);
            $datos['totalIva'] = $documentosActivos->sum(fn (Documento $d) => (float) $d->iva * $d->tipoDte->signo);
            $datos['cantidadFacturas'] = $documentosActivos->count();
            $datos['cantRetenciones'] = Retencion::query()->where('periodo', $periodoSeleccionado)->count();
            $datos['periodo'] = Periodo::find($periodoSeleccionado);
            // Cualquier documento del período (emitido o recibido) basta para salir del estado vacío,
            // ya que el IVA general también depende de las recibidas.
            $datos['tieneDocumentos'] = Documento::query()->where('periodo', $periodoSeleccionado)->exists();
        }

        return view('dashboard.index', $datos);
    }

    public function marcarCalculado(string $periodo): RedirectResponse
    {
        Periodo::query()->updateOrCreate(['periodo' => $periodo], ['estado' => 'calculado']);

        return redirect()->route('dashboard', ['periodo' => $periodo]);
    }

    public function marcarDeclarado(string $periodo): RedirectResponse
    {
        Periodo::query()->updateOrCreate(['periodo' => $periodo], [
            'estado' => 'declarado',
            'fecha_presentacion' => now()->toDateString(),
        ]);

        return redirect()->route('dashboard', ['periodo' => $periodo]);
    }

    private function periodosDisponibles(): Collection
    {
        return Documento::query()->distinct()->pluck('periodo')
            ->merge(Periodo::query()->pluck('periodo'))
            ->unique()
            ->sort()
            ->values();
    }
}
