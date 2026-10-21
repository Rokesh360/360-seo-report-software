<?php

namespace App\Http\Controllers;

use App\Services\MetaCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MetaCatalogController extends Controller
{
    public function index(Request $request, MetaCatalogService $service): View
    {
        $filters = $request->validate([
            'range' => ['nullable', 'in:' . implode(',', array_keys(MetaCatalogService::RANGES))],
            'since' => ['nullable', 'required_if:range,custom', 'date', 'before_or_equal:today'],
            'until' => ['nullable', 'required_if:range,custom', 'date', 'after_or_equal:since', 'before_or_equal:today'],
        ]);

        $filters['range'] ??= 'last_30d';

        try {
            $data = $service->dashboard($filters);
            $error = null;
        } catch (InvalidArgumentException $e) {
            $data = null;
            $error = $e->getMessage();
        }

        return view('meta_catalog.index', [
            'filters' => $filters,
            'ranges'  => MetaCatalogService::RANGES,
            'data'    => $data,
            'error'   => $error,
        ]);
    }

    public function connect(Request $request, MetaCatalogService $service): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        return redirect()->away($service->loginUrl($state));
    }

    public function callback(Request $request, MetaCatalogService $service): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Facebook login was cancelled or denied.');
        }

        $expected = $request->session()->pull('meta_oauth_state');

        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Invalid login state. Please try again.');
        }

        try {
            $service->exchangeCode((string) $request->query('code'));
        } catch (RuntimeException $e) {
            return redirect()->route('meta-catalog.index')->with('error', $e->getMessage());
        }

        return redirect()->route('meta-catalog.index');
    }
}