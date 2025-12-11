<?php

namespace App\Http\Resources\Api\V1\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyBrandingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subdomain' => $this->subdomain,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'description' => $this->description,

            // Logo and Favicon
            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon ? $this->getAssetUrl($this->favicon) : null,

            // Brand Colors
            'brand_color' => $this->formatBrandColor(),
            'gradient_palette' => $this->formatGradientPalette(),

            // Settings
            'is_active' => $this->is_active,
            'allow_registration' => $this->allow_registration,
            'maintenance_mode' => $this->maintenance_mode,

            // Localization
            'country' => [
                'code' => $this->country?->value,
                'name' => $this->country?->getLabel(),
            ],
            'timezone' => [
                'code' => $this->timezone?->value,
                'name' => $this->timezone?->getLabel(),
            ],
            'currency' => [
                'code' => $this->currency?->value,
                'symbol' => $this->getCurrencySymbol($this->currency),
                'name' => $this->currency?->getLabel(),
            ],

            // Contact Info
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
                'website' => $this->website,
            ],

            // URLs
            'urls' => [
                'full_domain' => $this->full_domain,
                'full_url' => $this->full_url,
            ],
        ];
    }

    /**
     * Format brand color with all shades
     */
    protected function formatBrandColor(): ?array
    {
        if (!$this->brand_color) {
            return null;
        }

        return [
            'name' => $this->brand_color->value,
            'label' => $this->brand_color->getLabel(),
            'primary' => $this->brand_color->getHexValue(500),
            'shades' => $this->getBrandColorShades(),
        ];
    }

    /**
     * Get all shades for the brand color
     */
    protected function getBrandColorShades(): array
    {
        if (!$this->brand_color) {
            return [];
        }

        $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
        $result = [];

        foreach ($shades as $shade) {
            $result[$shade] = $this->brand_color->getHexValue($shade);
        }

        return $result;
    }

    /**
     * Format gradient palette
     */
    protected function formatGradientPalette(): ?array
    {
        if (!$this->gradient_palette) {
            return null;
        }

        $colors = $this->gradient_palette->getColors();

        return [
            'name' => $this->gradient_palette->value,
            'label' => $this->gradient_palette->getLabel(),
            'from_color' => $colors['from'],
            'to_color' => $colors['to'],
            'gradient_class' => $this->gradient_palette->getGradientClass(),
            'preview_hex' => $this->gradient_palette->getPreviewHex(),
        ];
    }

    /**
     * Get asset URL helper
     */
    protected function getAssetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/' . $path);
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol($currency): ?string
    {
        if (!$currency) {
            return null;
        }

        $symbols = [
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'EGP' => 'ج.م',
            'QAR' => 'ر.ق',
            'KWD' => 'د.ك',
            'BHD' => 'د.ب',
            'OMR' => 'ر.ع',
            'JOD' => 'د.أ',
            'LBP' => 'ل.ل',
            'IQD' => 'د.ع',
            'SYP' => 'ل.س',
            'YER' => 'ر.ي',
            'ILS' => '₪',
            'MAD' => 'د.م',
            'DZD' => 'د.ج',
            'TND' => 'د.ت',
            'LYD' => 'د.ل',
            'SDG' => 'ج.س',
            'SOS' => 'Sh.So',
            'DJF' => 'Fdj',
            'KMF' => 'CF',
            'MRU' => 'UM',
        ];

        return $symbols[$currency->value] ?? $currency->value;
    }
}
