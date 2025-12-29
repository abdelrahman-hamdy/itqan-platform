<?php

namespace App\Enums;

/**
 * Gradient Palette Enum
 *
 * Defines color gradient themes for UI elements.
 * Used for cards, backgrounds, and visual styling.
 *
 * @see \App\Models\Academy
 */
enum GradientPalette: string
{
    case OCEAN_BREEZE = 'ocean_breeze';
    case SUNSET_GLOW = 'sunset_glow';
    case FOREST_MIST = 'forest_mist';
    case PURPLE_DREAM = 'purple_dream';
    case WARM_FLAME = 'warm_flame';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.gradient_palette.' . $this->value);
    }

    /**
     * Get the gradient colors (from and to)
     */
    public function getColors(): array
    {
        return match ($this) {
            self::OCEAN_BREEZE => [
                'from' => 'cyan-500',
                'to' => 'blue-600',
            ],
            self::SUNSET_GLOW => [
                'from' => 'orange-400',
                'to' => 'pink-600',
            ],
            self::FOREST_MIST => [
                'from' => 'emerald-400',
                'to' => 'teal-600',
            ],
            self::PURPLE_DREAM => [
                'from' => 'purple-500',
                'to' => 'pink-600',
            ],
            self::WARM_FLAME => [
                'from' => 'red-500',
                'to' => 'orange-600',
            ],
        };
    }

    /**
     * Get the gradient CSS class for backgrounds
     */
    public function getGradientClass(): string
    {
        $colors = $this->getColors();
        return "bg-gradient-to-r from-{$colors['from']} to-{$colors['to']}";
    }

    /**
     * Get the gradient CSS class for text
     */
    public function getTextGradientClass(): string
    {
        $colors = $this->getColors();
        return "bg-gradient-to-r from-{$colors['from']} to-{$colors['to']} bg-clip-text text-transparent";
    }

    /**
     * Get a lighter version of the gradient for backgrounds (with opacity)
     */
    public function getLightGradientClass(int $opacity = 15): string
    {
        $colors = $this->getColors();
        return "bg-gradient-to-br from-{$colors['from']}/{$opacity} via-white to-{$colors['to']}/{$opacity}";
    }

    /**
     * Get the 'from' color only
     */
    public function getFromColor(): string
    {
        return $this->getColors()['from'];
    }

    /**
     * Get the 'to' color only
     */
    public function getToColor(): string
    {
        return $this->getColors()['to'];
    }

    /**
     * Get hex color for preview (using 'from' color)
     */
    public function getPreviewHex(): string
    {
        // Extract the color name and shade from the 'from' color
        $fromColor = $this->getFromColor();
        [$colorName, $shade] = explode('-', $fromColor);

        // Use TailwindColor to get the hex value
        try {
            $tailwindColor = TailwindColor::from($colorName);
            return $tailwindColor->getHexValue((int)$shade);
        } catch (\ValueError $e) {
            // Fallback to a default color if the color is not found in TailwindColor enum
            return '#3B82F6'; // blue-500
        }
    }

    /**
     * Get all gradient palettes as an array for select options
     */
    public static function toArray(): array
    {
        $options = [];
        foreach (self::cases() as $palette) {
            $options[$palette->value] = $palette->label();
        }
        return $options;
    }
}
