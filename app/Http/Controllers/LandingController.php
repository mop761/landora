<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    /**
     * Lista las landings del usuario autenticado.
     */
    public function index()
    {
        $landings = Landing::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('landings.index', compact('landings'));
    }

    /**
     * Crea una nueva landing.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        Landing::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'status' => 'draft',
        ]);

        return redirect()->route('landings.index')->with('ok', 'Landing creada correctamente.');
    }

    /**
     * Elimina una landing (solo su propietario).
     */
    public function destroy(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $landing->delete();

        return redirect()->route('landings.index')->with('ok', 'Landing eliminada.');
    }

    /**
     * Genera un slug único a partir del nombre.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'landing';
        $slug = $base;
        $i = 1;

        while (Landing::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
