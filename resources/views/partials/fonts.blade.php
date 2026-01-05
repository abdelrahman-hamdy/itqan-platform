{{-- ==========================================================================
   Reusable Fonts Partial
   ==========================================================================
   Include this partial in any layout that needs the standard Itqan fonts.
   Usage: @include('partials.fonts')

   Fonts included:
   - Tajawal: Primary Arabic font for UI and body text
   - Cairo: Alternative Arabic font for headings (optional, loaded on demand)
--}}

{{-- Preconnect for faster font loading --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

{{-- Primary Font: Tajawal (Arabic & English UI) --}}
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

{{-- Secondary Font: Cairo (Headings - load if needed) --}}
@if($includeCairo ?? false)
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endif
