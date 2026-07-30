<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(): View
    {
        return view('clientes.index', [
            'clientes' => Cliente::query()->orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nit' => 'required|string|max:20|unique:clientes,nit',
            'nombre' => 'required|string|max:255',
            'tipo_default' => 'required|in:bien,servicio,combustible',
        ]);

        Cliente::create($datos);

        return redirect()->route('clientes.index')->with('exito', 'Cliente agregado.');
    }

    public function actualizar(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_default' => 'required|in:bien,servicio,combustible',
        ]);

        $cliente->update($datos);

        return redirect()->route('clientes.index')->with('exito', 'Cliente actualizado.');
    }
}
