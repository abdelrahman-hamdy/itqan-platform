#!/bin/bash

echo "🚀 Setting up Itqan Platform Subdomain Development"
echo "=================================================="

# Get current academies from database
echo "📋 Current academies in database:"
php artisan tinker --execute="
\$academies = App\Models\Academy::all(['name', 'subdomain']);
foreach(\$academies as \$academy) {
    echo \$academy->subdomain . '.itqan-platform.test -> ' . \$academy->name . PHP_EOL;
}
"

echo ""
echo "🔧 Setup Options:"
echo "1. Manual hosts file setup"
echo "2. Laravel Valet setup (recommended)"
echo ""

echo "📝 MANUAL SETUP:"
echo "Add these lines to your /etc/hosts file:"
echo "127.0.0.1    itqan-platform.test"
echo "127.0.0.1    itqan.itqan-platform.test"
echo "127.0.0.1    alnoor.itqan-platform.test"
echo "127.0.0.1    blaza.itqan-platform.test"
echo ""
echo "Then run: sudo nano /etc/hosts"
echo ""

echo "🚀 VALET SETUP (RECOMMENDED):"
echo "If you have Laravel Valet installed:"
echo "1. valet park (in this directory)"
echo "2. Valet automatically handles *.test subdomains"
echo ""
echo "If you don't have Valet:"
echo "1. composer global require laravel/valet"
echo "2. valet install"
echo "3. valet park"
echo ""

echo "🌐 TEST URLS:"
echo "Main: http://itqan-platform.test"
echo "Admin: http://itqan-platform.test/admin"
echo "Subdomains:"
echo "- http://itqan.itqan-platform.test"
echo "- http://alnoor.itqan-platform.test"
echo "- http://blaza.itqan-platform.test"
echo ""

echo "✅ Setup complete! Choose your preferred method above." 