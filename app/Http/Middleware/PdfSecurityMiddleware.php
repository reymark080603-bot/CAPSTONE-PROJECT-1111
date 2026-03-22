<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PdfSecurityMiddleware
{
    /**
     * Handle an incoming request for PDF files
     * Add security headers to prevent downloads
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if this is a PDF file request
        if (str_contains($request->path(), '.pdf') || 
            str_contains($request->getRequestUri(), '.pdf') ||
            $request->headers->get('Accept') === 'application/pdf') {
            
            // Add security headers to discourage downloads
            $response->headers->set('Content-Disposition', 'inline; filename="document.pdf"');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            
            // Add Content Security Policy
            $response->headers->set('Content-Security-Policy', 
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "object-src 'none'; " .
                "frame-ancestors 'self'"
            );
        }
        
        return $response;
    }
}