<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LinkController extends Controller
{
    private function listing(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|in:active,disabled,expired']);

        return Link::where('user_id', $request->user()->id)
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$q.'%')->orWhere('code', 'like', '%'.$q.'%')->orWhere('url', 'like', '%'.$q.'%')))
            ->when(($filters['status'] ?? '') === 'active', fn ($q) => $q->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when(($filters['status'] ?? '') === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when(($filters['status'] ?? '') === 'expired', fn ($q) => $q->where('is_active', true)->where('expires_at', '<=', now()))
            ->latest('id')->paginate(15)->withQueryString();
    }

    public function dashboard(Request $request)
    {
        $query = Link::where('user_id', $request->user()->id);

        return view('dashboard', [
            'links' => $this->listing($request),
            'total' => (clone $query)->count(),
            'clicks' => (clone $query)->sum('clicks'),
            'active' => (clone $query)->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
        ]);
    }

    public function index(Request $request)
    {
        return $this->listing($request);
    }

    private function validated(Request $request, ?Link $link = null): array
    {
        return $request->validate([
            'title' => 'nullable|string|max:160',
            'url' => [$link ? 'sometimes' : 'required', 'bail', 'url:http,https', 'max:8192', function ($attribute, $value, $fail) {
                $parts = parse_url($value);
                if (isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20\x7f]/', $value)) {
                    $fail('Use an HTTP or HTTPS URL without credentials or spaces.');
                }
                if (strtolower($parts['host'] ?? '') === strtolower(parse_url(config('shortener.url'), PHP_URL_HOST) ?? '')) {
                    $fail('Use the original destination, rather than another short link.');
                }
            }],
            'alias' => ['nullable', 'string', 'min:3', 'max:40', 'regex:/\A[a-z0-9][a-z0-9_-]*\z/', Rule::notIn(config('shortener.reserved')), Rule::unique('links', 'code')->ignore($link?->id), ...($link ? [Rule::in([$link->code])] : [])],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $hash = hash('sha256', $data['url']);
        $existing = empty($data['alias']) && empty($data['expires_at']) && ($data['is_active'] ?? true)
            ? Link::where('user_id', $request->user()->id)->where('url_hash', $hash)->where('url', $data['url'])->where('is_active', true)->whereNull('expires_at')->first() : null;
        if ($existing) {
            return $this->saved($request, $existing, false);
        }
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = $data['alias'] ?? Str::lower(Str::random(6));
            if (in_array($code, config('shortener.reserved'))) {
                continue;
            }
            $link = new Link(collect($data)->except('alias')->all());
            $link->code = $code;
            $link->url_hash = $hash;
            $link->user_id = $request->user()->id;
            try {
                $link->save();

                return $this->saved($request, $link, true);
            } catch (UniqueConstraintViolationException $error) {
                if (! empty($data['alias'])) {
                    throw ValidationException::withMessages(['alias' => 'This alias is already in use.']);
                }
            }
        }
        abort(503, 'A short link could not be allocated. Please try again.');
    }

    private function saved(Request $request, Link $link, bool $created)
    {
        return $request->is('api/*') ? response()->json(['data' => $link], $created ? 201 : 200)
            : to_route('dashboard')->with('status', $created ? 'Your short link is ready.' : 'This destination already has a short link.')->with('short_url', $link->short_url);
    }

    private function owned(Request $request, Link $link): void
    {
        abort_unless($link->user_id === $request->user()->id, 404);
    }

    public function show(Request $request, Link $link)
    {
        $this->owned($request, $link);

        return response()->json(['data' => $link]);
    }

    public function edit(Request $request, Link $link)
    {
        $this->owned($request, $link);

        return view('edit', compact('link'));
    }

    public function update(Request $request, Link $link)
    {
        $this->owned($request, $link);
        $data = $this->validated($request, $link);
        if (! empty($data['alias'])) {
            $data['code'] = $data['alias'];
        }
        unset($data['alias']);
        if (isset($data['url'])) {
            $data['url_hash'] = hash('sha256', $data['url']);
        }
        $link->update($data);

        return $request->is('api/*') ? response()->json(['data' => $link]) : to_route('dashboard')->with('status', 'Link updated.');
    }

    public function destroy(Request $request, Link $link)
    {
        $this->owned($request, $link);
        // Retain the code so a disabled contract link cannot be reassigned.
        $link->update(['is_active' => false]);

        return $request->is('api/*') ? response()->noContent() : to_route('dashboard')->with('status', 'Link disabled.');
    }

    public function redirect(Request $request, string $code)
    {
        $link = Link::where('code', $code)->firstOrFail();
        abort_unless($link->is_active && (! $link->expires_at || $link->expires_at->isFuture()), 410);
        if ($request->isMethod('GET')) {
            Link::whereKey($link->id)->increment('clicks', 1, ['last_clicked_at' => now()]);
        }

        return redirect()->away($link->url, 302)->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
