#!/bin/bash

# Script to update views to use gradient_palette instead of secondary_color

echo "Updating view files to use gradient_palette..."

# Define the PHP code block to add gradient palette variables
GRADIENT_PHP="@php
    // Get gradient palette for this academy
    \$gradientPalette = \$academy?->gradient_palette ?? \\App\\Enums\\GradientPalette::OCEAN_BREEZE;
    \$colors = \$gradientPalette->getColors();
    \$gradientFrom = \$colors['from'];
    \$gradientTo = \$colors['to'];
@endphp

"

# List of files to update (academy components and static pages)
FILES=(
    "resources/views/academy/components/academic-section.blade.php"
    "resources/views/academy/components/quran-section.blade.php"
    "resources/views/academy/components/recorded-courses.blade.php"
    "resources/views/academy/static/privacy-policy.blade.php"
    "resources/views/academy/static/about-us.blade.php"
    "resources/views/academy/static/terms.blade.php"
    "resources/views/academy/static/refund-policy.blade.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "Processing $file..."

        # Replace secondary with gradient variables
        sed -i '' \
            -e 's/from-primary\/\([0-9]*\) to-secondary\/\([0-9]*\)/from-{{ $gradientFrom }}\/\1 to-{{ $gradientTo }}\/\2/g' \
            -e 's/from-secondary\/\([0-9]*\) to-primary\/\([0-9]*\)/from-{{ $gradientTo }}\/\1 to-{{ $gradientFrom }}\/\2/g' \
            -e 's/from-primary to-secondary/from-{{ $gradientFrom }} to-{{ $gradientTo }}/g' \
            -e 's/from-secondary to-primary/from-{{ $gradientTo }} to-{{ $gradientFrom }}/g' \
            -e 's/from-secondary/from-{{ $gradientTo }}/g' \
            -e 's/to-secondary/to-{{ $gradientTo }}/g' \
            "$file"

        echo "✓ Updated $file"
    else
        echo "✗ File not found: $file"
    fi
done

echo "Done! Gradient palette updates applied."
