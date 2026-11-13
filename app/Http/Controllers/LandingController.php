<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Support\LandingRenderer;
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
     * Muestra el editor visual de una landing.
     */
    public function edit(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        return view('landings.editor', compact('landing'));
    }

    /**
     * Guarda el diseño (layout JSON) de la landing. Lo llama el editor por AJAX.
     */
    public function updateLayout(Request $request, Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $data = $request->validate([
            'layout' => ['nullable', 'array'],
        ]);

        $landing->update(['layout' => $data['layout'] ?? []]);

        return response()->json(['ok' => true]);
    }

    /**
     * Página pública de la landing (la ve cualquiera, sin login).
     * Renderiza el diseño JSON a HTML usando LandingRenderer.
     *
     * De momento se muestra en cualquier estado; la protección de borradores
     * (solo visibles las publicadas) se añade en el siguiente paso.
     */
    public function show(Landing $landing)
    {
        $html = LandingRenderer::render($landing->layout);
        $customCss = LandingRenderer::customCss($landing->layout);

        return view('landings.public', compact('landing', 'html', 'customCss'));
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
