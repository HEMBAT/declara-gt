<?php

namespace App\Http\Controllers;

use App\Enums\TipoDeclaracion;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\Periodo;
use App\Models\Retencion;
use App\Servicios\CalculoDesgloseSat2237PeriodoService;
use App\Servicios\CalculoIsrPeriodoService;
use App\Servicios\CalculoIvaPeriodoService;
use App\Servicios\ConteoDocumentosPeriodoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            $datos['remanenteSegunSat'] = Declaracion::query()
                ->where('tipo', TipoDeclaracion::Iva)
                ->where('periodo', $periodoSeleccionado)
                ->value('remanente_anterior_sat');
            $datos['remanenteCalculadoAnterior'] = Declaracion::remanenteIvaCalculadoAnterior($periodoSeleccionado);
            $datos['desglose'] = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo($periodoSeleccionado);
            $datos['totalFacturado'] = $documentosActivos->sum(fn (Documento $d) => (float) $d->gran_total * $d->tipoDte->signo);
            $datos['totalIva'] = $documentosActivos->sum(fn (Documento $d) => (float) $d->iva * $d->tipoDte->signo);
            $datos['cantidadFacturas'] = $documentosActivos->count();
            $datos['cantRetenciones'] = Retencion::query()->where('periodo', $periodoSeleccionado)->count();
            $datos['conteoDocumentos'] = (new ConteoDocumentosPeriodoService)->contarPeriodo($periodoSeleccionado);
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

    /**
     * Registra (o borra, si viene vacío) el remanente del período anterior
     * que muestra Declaraguate, y recalcula este período y los siguientes
     * para que el nuevo remanente se arrastre por la cadena.
     */
    public function guardarRemanenteSat(Request $request, string $periodo): RedirectResponse
    {
        // Se acepta tal como se copia de Declaraguate: "1,858" o "Q1,858.00".
        $request->merge([
            'remanente_anterior_sat' => str_replace([',', ' ', 'Q'], '', (string) $request->input('remanente_anterior_sat')),
        ]);

        $datos = $request->validate([
            'remanente_anterior_sat' => ['nullable', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
        ], [
            'remanente_anterior_sat.regex' => 'Escribe el remanente como un monto positivo, por ejemplo 1858 o 1858.00.',
        ]);

        $remanente = ($datos['remanente_anterior_sat'] ?? '') === '' ? null : $datos['remanente_anterior_sat'];

        DB::transaction(function () use ($periodo, $remanente) {
            $servicio = new CalculoIvaPeriodoService;

            // Garantiza que exista la fila del período antes de anotarle el remanente.
            $servicio->calcularPeriodo($periodo);

            Declaracion::query()
                ->where('tipo', TipoDeclaracion::Iva)
                ->where('periodo', $periodo)
                ->update(['remanente_anterior_sat' => $remanente]);

            $servicio->recalcularDesde($periodo);
        });

        return redirect()->route('dashboard', ['periodo' => $periodo])->with(
            'exito',
            $remanente === null ? 'Se volvió a usar el remanente calculado por el programa.' : 'Remanente según Declaraguate guardado.'
        );
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
