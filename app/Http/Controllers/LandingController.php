<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Lead;
use App\Support\LandingRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    /**
     * Lista las landings del usuario autenticado (con el número de leads de cada una).
     */
    public function index()
    {
        $landings = Landing::where('user_id', auth()->id())
            ->withCount('leads')
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
     * Publica o despublica una landing (alterna su estado). Solo su propietario.
     */
    public function togglePublish(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $landing->update([
            'status' => $landing->status === 'published' ? 'draft' : 'published',
        ]);

        $mensaje = $landing->status === 'published'
            ? 'Landing publicada. Ya es visible en su URL pública.'
            : 'Landing pasada a borrador. Ya no es visible en público.';

        return redirect()->route('landings.index')->with('ok', $mensaje);
    }

    /**
     * Página pública de la landing (la ve cualquiera, sin login).
     * Solo son públicas las landings publicadas; el propietario puede además
     * previsualizar las suyas en borrador.
     */
    public function show(Landing $landing)
    {
        $esPropietario = auth()->check() && auth()->id() === $landing->user_id;
        abort_unless($landing->status === 'published' || $esPropietario, 404);

        $context = [
            'action' => route('landings.lead', $landing),
            'csrf' => csrf_token(),
        ];

        $html = LandingRenderer::render($landing->layout, $context);
        $customCss = LandingRenderer::customCss($landing->layout);

        return view('landings.public', compact('landing', 'html', 'customCss'));
    }

    /**
     * Guarda un lead enviado desde el formulario público de una landing.
     * Los campos del formulario son variables (los define el usuario en el editor),
     * así que recogemos todo lo enviado (menos el token) y lo guardamos como JSON.
     */
    public function storeLead(Request $request, Landing $landing)
    {
        // Solo se capturan leads en landings publicadas.
        abort_unless($landing->status === 'published', 404);

        $data = $request->except(['_token']);

        // Si no llega ningún dato, no creamos un lead vacío.
        if (empty($data)) {
            return redirect()->route('landings.show', $landing->slug);
        }

        Lead::create([
            'landing_id' => $landing->id,
            'data' => $data,
        ]);

        // Volvemos a la landing con una marca para mostrar el mensaje de gracias.
        return redirect()->route('landings.show', ['landing' => $landing->slug, 'ok' => 1]);
    }

    /**
     * Muestra los leads capturados por una landing (solo su propietario).
     */
    public function leads(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $leads = $landing->leads()->latest()->get();

        return view('landings.leads', compact('landing', 'leads'));
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
