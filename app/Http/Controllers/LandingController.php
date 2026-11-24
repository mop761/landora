<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Lead;
use App\Support\LandingRenderer;
use App\Support\WebhookSender;
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
    public function show(Request $request, Landing $landing)
    {
        $esPropietario = auth()->check() && auth()->id() === $landing->user_id;
        abort_unless($landing->status === 'published' || $esPropietario, 404);

        // Si la visita llega desde una campaña (?utm_source=...), se guardan las UTM
        // en campos ocultos del formulario para saber de dónde viene cada lead.
        $utm = [];
        foreach (['source', 'medium', 'campaign'] as $clave) {
            $valor = $request->query('utm_' . $clave);
            if (is_string($valor) && $valor !== '') {
                $utm[$clave] = mb_substr($valor, 0, 100);
            }
        }

        $context = [
            'action' => route('landings.lead', $landing),
            'csrf' => csrf_token(),
            'utm' => $utm,
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

        // Los campos del formulario; los de control (token y UTM ocultas) van aparte.
        $data = $request->except(['_token', '_utm_source', '_utm_medium', '_utm_campaign']);

        // Si no llega ningún dato, no creamos un lead vacío.
        if (empty($data)) {
            return redirect()->route('landings.show', $landing->slug);
        }

        $lead = Lead::create([
            'landing_id' => $landing->id,
            'data' => $data,
            'utm_source' => $this->texto($request->input('_utm_source')),
            'utm_medium' => $this->texto($request->input('_utm_medium')),
            'utm_campaign' => $this->texto($request->input('_utm_campaign')),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        // Si la landing tiene webhook, se le envía el lead. El envío nunca rompe la
        // captura: si falla, queda registrado y se puede reintentar desde el panel.
        if ($landing->webhook_url) {
            WebhookSender::send($landing, $lead, WebhookSender::leadPayload($lead), 'lead.created');
        }

        // Volvemos a la landing con una marca para mostrar el mensaje de gracias.
        return redirect()->route('landings.show', ['landing' => $landing->slug, 'ok' => 1]);
    }

    /**
     * Muestra los leads capturados por una landing (solo su propietario).
     */
    public function leads(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $leads = $landing->leads()->with('latestDelivery')->latest()->get();

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
     * Devuelve un texto corto y limpio (máx. 100 caracteres) o null si no es texto.
     */
    private function texto($valor): ?string
    {
        return (is_string($valor) && trim($valor) !== '') ? mb_substr(trim($valor), 0, 100) : null;
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
