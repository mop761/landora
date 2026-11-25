<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Lead;
use App\Support\LandingRenderer;
use App\Support\Slugs;
use App\Support\VisitTracker;
use App\Support\WebhookSender;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        // Se genera un slug libre. Si justo a la vez otra petición se queda con el mismo,
        // el índice UNIQUE de la base de datos rechaza el insert: se reintenta (máx. 3 veces).
        for ($intento = 1; ; $intento++) {
            try {
                Landing::create([
                    'user_id' => auth()->id(),
                    'name' => $data['name'],
                    'slug' => Slugs::unico($data['name']),
                    'status' => 'draft',
                ]);
                break;
            } catch (QueryException $e) {
                if (! Slugs::esDuplicado($e) || $intento >= 3) {
                    throw $e;
                }
            }
        }

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
     * Ajustes de la landing: nombre y slug.
     */
    public function settings(Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        return view('landings.settings', compact('landing'));
    }

    /**
     * Guarda el nombre y el slug. El slug se normaliza ("Máster Moda" -> "master-moda") y se
     * comprueba que tenga formato válido, que no lo use otra landing y que no esté reservado.
     */
    public function updateSettings(Request $request, Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'required', 'string', 'max:191', 'regex:' . Slugs::FORMATO,
                Rule::unique('landings', 'slug')->ignore($landing->id),
                Rule::notIn(Slugs::RESERVADOS),
            ],
        ], [
            'slug.required' => 'El slug no puede quedar vacío: usa letras, números o guiones.',
            'slug.regex' => 'El slug solo puede tener letras minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe otra landing con ese slug. Elige otro.',
            'slug.not_in' => 'Ese slug está reservado por la aplicación (por ejemplo /login). Elige otro.',
        ]);

        $landing->name = $data['name'];
        $landing->slug = $data['slug'];

        try {
            $landing->save();
        } catch (QueryException $e) {
            // Otra petición ha ocupado el slug entre la comprobación y el guardado.
            if (! Slugs::esDuplicado($e)) {
                throw $e;
            }

            return back()->withInput()->withErrors(['slug' => 'Ese slug acaba de ocuparse. Prueba con otro.']);
        }

        return redirect()->route('landings.settings', $landing)
            ->with('ok', 'Ajustes guardados. Dirección pública: /' . $landing->slug);
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

        // Se cuenta la visita para la analítica. No cuentan las del propietario (previsualizar)
        // ni la recarga que se produce tras enviar el formulario (?ok=1).
        if ($landing->status === 'published' && ! $esPropietario && ! $request->query('ok')) {
            VisitTracker::record($request, $landing);
        }

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
}
