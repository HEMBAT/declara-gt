<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contribuyente;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\Retencion;
use App\Models\RetencionIva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConfiguracionController extends Controller
{
    public function editar(): View
    {
        return view('configuracion.editar', [
            'contribuyente' => Contribuyente::actual(),
            'nitBloqueado' => Contribuyente::nitBloqueado(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nit' => 'required|string|max:20',
            'nombre' => 'required|string|max:255',
        ]);

        $contribuyente = Contribuyente::actual();

        if ($contribuyente && Contribuyente::nitBloqueado() && $datos['nit'] !== $contribuyente->nit) {
            throw ValidationException::withMessages([
                'nit' => 'No se puede cambiar el NIT porque ya existen documentos importados. Usa "Reiniciar datos" primero.',
            ]);
        }

        if ($contribuyente) {
            $contribuyente->update($datos);
        } else {
            Contribuyente::create($datos);
        }

        // Lo más común es que el usuario llegue aquí porque un intento de
        // importar lo redirigió al no tener contribuyente configurado; lo
        // mandamos derecho a continuar en vez de dejarlo varado aquí.
        return redirect()->route('importar.index')->with('exito', 'Datos del contribuyente guardados.');
    }

    public function reiniciar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'confirmar_nit' => 'required|string',
        ]);

        $contribuyente = Contribuyente::actual();

        if (! $contribuyente || $datos['confirmar_nit'] !== $contribuyente->nit) {
            throw ValidationException::withMessages([
                'confirmar_nit' => 'El NIT ingresado no coincide. No se realizó ningún cambio.',
            ]);
        }

        DB::transaction(function () use ($contribuyente) {
            Documento::query()->delete();
            Retencion::query()->delete();
            RetencionIva::query()->delete();
            Declaracion::query()->delete();
            Cliente::query()->delete();
            $contribuyente->delete();
        });

        return redirect()->route('configuracion.editar')->with('exito', 'Los datos se reiniciaron. Configura un nuevo contribuyente para continuar.');
    }

    public function respaldo(): BinaryFileResponse
    {
        $contribuyente = Contribuyente::actual();
        $nit = $contribuyente ? str_replace('-', '', $contribuyente->nit) : 'sin-nit';
        $nombreArchivo = sprintf('declara-gt_%s_%s.sqlite', $nit, now()->format('Y-m-d'));

        $directorio = storage_path('app/respaldos');
        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $rutaTemporal = $directorio.'/'.$nombreArchivo;

        DB::statement('VACUUM INTO ?', [$rutaTemporal]);

        return response()->download($rutaTemporal, $nombreArchivo)->deleteFileAfterSend(true);
    }
}
