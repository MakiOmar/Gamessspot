<?php

namespace App\Http\Controllers;

use App\Services\NextLoad\NextLoadDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Login-free Next Load check page, shared by URL with a secret key (e.g. with Next Load support).
 */
class NextLoadDiagnosticsController extends Controller
{
    public function __invoke(Request $request, NextLoadDiagnostics $diagnostics): Response
    {
        $key = (string) config('services.nextload.diagnostics_key');
        // Unknown or missing key looks like a missing page so the URL cannot be probed.
        abort_if($key === '' || ! hash_equals($key, (string) $request->query('key', '')), 404);

        return response()
            ->view('nextload.diagnostics', array('report' => $diagnostics->run(), 'ranAt' => now()))
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
