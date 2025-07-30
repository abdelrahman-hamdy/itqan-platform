@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
@endphp

@if($currentAcademy)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update brand name
        const brandElement = document.querySelector('.fi-logo');
        if (brandElement) {
            // Update text content
            const textElement = brandElement.querySelector('span, .fi-logo-text');
            if (textElement) {
                textElement.textContent = '{{ $currentAcademy->name }}';
            } else {
                // If no text element exists, add one
                const newTextElement = document.createElement('span');
                newTextElement.textContent = '{{ $currentAcademy->name }}';
                newTextElement.className = 'fi-logo-text font-semibold text-lg';
                brandElement.appendChild(newTextElement);
            }
            
            @if($currentAcademy->logo)
            // Update logo if academy has one
            const logoImg = brandElement.querySelector('img, svg');
            if (logoImg) {
                if (logoImg.tagName === 'IMG') {
                    logoImg.src = '{{ $currentAcademy->logo }}';
                    logoImg.alt = '{{ $currentAcademy->name }}';
                } else {
                    // Replace SVG with IMG
                    const newImg = document.createElement('img');
                    newImg.src = '{{ $currentAcademy->logo }}';
                    newImg.alt = '{{ $currentAcademy->name }}';
                    newImg.className = 'h-8 w-auto';
                    logoImg.parentNode.replaceChild(newImg, logoImg);
                }
            } else {
                // Add logo if none exists
                const newImg = document.createElement('img');
                newImg.src = '{{ $currentAcademy->logo }}';
                newImg.alt = '{{ $currentAcademy->name }}';
                newImg.className = 'h-8 w-auto mr-2';
                brandElement.insertBefore(newImg, brandElement.firstChild);
            }
            @endif
        }
        
        // Update favicon
        const favicon = document.querySelector('link[rel="icon"]');
        @if($currentAcademy->logo)
        if (favicon) {
            favicon.href = '{{ $currentAcademy->logo }}';
        } else {
            const newFavicon = document.createElement('link');
            newFavicon.rel = 'icon';
            newFavicon.href = '{{ $currentAcademy->logo }}';
            document.head.appendChild(newFavicon);
        }
        @endif
        
        // Update page title
        document.title = 'لوحة التحكم - {{ $currentAcademy->name }}';
    });
</script>
@endif 