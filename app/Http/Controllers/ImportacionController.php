<?php

namespace App\Http\Controllers;

use App\Exceptions\ArchivoImportacionInvalidoException;
use App\Exceptions\ContribuyenteNoConfiguradoException;
use App\Servicios\Dto\DraftImportacion;
use App\Servicios\ImportadorDocumentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportacionController extends Controller
{
    private const CLAVE_SESION = 'importacion.draft';

    public function formulario(): View
    {
        return view('importacion.index', ['paso' => 'idle']);
    }

    public function procesar(Request $request): View|RedirectResponse
    {
        $request->validate(['archivo' => 'required|file']);

        try {
            $draft = (new ImportadorDocumentos)->previsualizar($request->file('archivo'));
        } catch (ContribuyenteNoConfiguradoException $e) {
            return redirect()->route('configuracion.editar')->with('error', $e->getMessage());
        } catch (ArchivoImportacionInvalidoException $e) {
            return redirect()->route('importar.index')->with('error', $e->getMessage());
        }

        session([self::CLAVE_SESION => $draft->toArray()]);

        return view('importacion.index', ['paso' => 'preview', 'draft' => $draft]);
    }

    public function continuar(): View|RedirectResponse
    {
        $draft = $this->draftDeSesion();
        if ($draft === null) {
            return redirect()->route('importar.index');
        }

        if ($draft->clientesPorClasificar === []) {
            $resultado = (new ImportadorDocumentos)->confirmar($draft);
            session()->forget(self::CLAVE_SESION);

            return view('importacion.index', ['paso' => 'done', 'draft' => $draft, 'resultado' => $resultado]);
        }

        return redirect()->route('importar.clasificar');
    }

    public function clasificar(): View|RedirectResponse
    {
        $draft = $this->draftDeSesion();
        if ($draft === null || $draft->clientesPorClasificar === []) {
            return redirect()->route('importar.index');
        }

        // old('clasificaciones.<nit>') === null es ambiguo: pasa igual si esta
        // es una visita fresca (nada flasheado) que si el nit específico faltó
        // en un reenvío fallido. hasOldInput('clasificaciones') distingue el
        // caso "no hubo reenvío" para no resaltar todo como si fuera un error.
        $nitsFaltantes = session()->hasOldInput('clasificaciones')
            ? collect($draft->clientesPorClasificar)
                ->pluck('nit')
                ->filter(fn (string $nit) => old('clasificaciones.'.$nit) === null)
                ->values()
            : collect();

        return view('importacion.index', [
            'paso' => 'clasificar',
            'draft' => $draft,
            'nitsFaltantes' => $nitsFaltantes,
        ]);
    }

    public function guardarClasificacion(Request $request): View|RedirectResponse
    {
        $draft = $this->draftDeSesion();
        if ($draft === null) {
            return redirect()->route('importar.index');
        }

        $datos = $request->validate([
            'clasificaciones' => 'array',
            'clasificaciones.*' => 'required|in:bien,servicio,combustible',
            'credito' => 'array',
            'credito.*' => 'nullable|in:0,1',
        ]);
        $clasificaciones = $datos['clasificaciones'] ?? [];
        $clasificacionesCredito = array_map(fn ($valor) => (bool) (int) $valor, $datos['credito'] ?? []);

        $candidatosFaltantes = collect($draft->clientesPorClasificar)
            ->reject(fn ($candidato) => array_key_exists($candidato->nit, $clasificaciones));

        if ($candidatosFaltantes->isNotEmpty()) {
            $nombres = $candidatosFaltantes
                ->map(fn ($candidato) => "{$candidato->nombre} ({$candidato->nit})")
                ->implode(', ');

            return back()->withInput()->withErrors([
                'clasificaciones' => "Falta asignar un tipo a: {$nombres}.",
            ]);
        }

        $resultado = (new ImportadorDocumentos)->confirmar($draft, $clasificaciones, $clasificacionesCredito);
        session()->forget(self::CLAVE_SESION);

        return view('importacion.index', ['paso' => 'done', 'draft' => $draft, 'resultado' => $resultado]);
    }

    public function cancelar(): RedirectResponse
    {
        session()->forget(self::CLAVE_SESION);

        return redirect()->route('importar.index');
    }

    private function draftDeSesion(): ?DraftImportacion
    {
        $datos = session(self::CLAVE_SESION);

        return is_array($datos) ? DraftImportacion::fromArray($datos) : null;
    }
}
