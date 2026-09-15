<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripBoldTags
{
    /**
     * Handle an incoming request and strip bold tags/styles from text inputs.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove HTML bold tags <b>, </b>, <strong>, </strong> (case insensitive)
                $value = preg_replace('/<\/?(b|strong)[^>]*>/i', '', $value);
                
                // Remove inline CSS font-weight: bold / 700 / etc.
                $value = preg_replace('/font-weight\s*:\s*(bold|[5-9]00)\s*;?/i', '', $value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
