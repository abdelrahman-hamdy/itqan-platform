<?php
/**
 * Hostinger Server Setup Script
 * 
 * This script should be uploaded to your Hostinger server and accessed via browser
 * to complete the Laravel application setup after files are uploaded.
 * 
 * IMPORTANT: Delete this file after setup is complete for security!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security check - only allow from server
if (php_sapi_name() !== 'cli' && !isset($_SERVER['SERVER_ADDR'])) {
    die('This script can only be run from the server command line or via authorized browser access.');
}

class HostingerSetup
{
    private $errors = [];
    private $success = [];

    public function run()
    {
        echo "🚀 Itqan Platform - Hostinger Server Setup\n";
        echo "==========================================\n\n";

        $this->checkEnvironment();
        $this->checkFileStructure();
        $this->setupEnvironment();
        $this->generateAppKey();
        $this->runMigrations();
        $this->setPermissions();
        $this->optimizeApplication();
        $this->cleanup();

        $this->showResults();
    }

    private function checkEnvironment()
    {
        echo "Step 1: Checking environment...\n";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->errors[] = "PHP 8.2+ required. Current version: " . PHP_VERSION;
        } else {
            $this->success[] = "PHP version: " . PHP_VERSION;
        }

        // Check required extensions
        $requiredExtensions = ['mysql', 'curl', 'json', 'mbstring', 'xml', 'zip', 'gd', 'fileinfo'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "Required PHP extension missing: $ext";
            } else {
                $this->success[] = "PHP extension loaded: $ext";
            }
        }

        echo "✓ Environment check completed\n\n";
    }

    private function checkFileStructure()
    {
        echo "Step 2: Checking file structure...\n";
        
        $requiredFiles = [
            'app',
            'bootstrap',
            'config',
            'database',
            'resources',
            'routes',
            'storage',
            'public',
            'vendor',
            'composer.json',
            'artisan',
            '.env'
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $this->errors[] = "Required file/directory missing: $file";
            } else {
                $this->success[] = "File exists: $file";
            }
        }

        echo "✓ File structure check completed\n\n";
    }

    private function setupEnvironment()
    {
        echo "Step 3: Setting up environment...\n";
        
        if (!file_exists('.env')) {
            if (file_exists('.env.example')) {
                copy('.env.example', '.env');
                $this->success[] = "Created .env from .env.example";
            } else {
                $this->errors[] = "No .env or .env.example file found";
                return;
            }
        }

        // Check if .env has been configured
        $env = $this->readEnv();
        if (empty($env['DB_DATABASE']) || $env['DB_DATABASE'] === 'your_database_name') {
            $this->errors[] = "Please configure database settings in .env file before running this script";
            return;
        }

        $this->success[] = "Environment file configured";
        echo "✓ Environment setup completed\n\n";
    }

    private function generateAppKey()
    {
        echo "Step 4: Generating application key...\n";
        
        if (file_exists('artisan')) {
            $output = shell_exec('php artisan key:generate --force 2>&1');
            if (strpos($output, 'Application key set successfully') !== false) {
                $this->success[] = "Application key generated";
            } else {
                $this->errors[] = "Failed to generate application key: " . $output;
            }
        } else {
            $this->errors[] = "Artisan file not found";
        }

        echo "✓ Application key generation completed\n\n";
    }

    private function runMigrations()
    {
        echo "Step 5: Running database migrations...\n";
        
        if (file_exists('artisan')) {
            $output = shell_exec('php artisan migrate --force 2>&1');
            if (strpos($output, 'Migrating') !== false || strpos($output, 'Nothing to migrate') !== false) {
                $this->success[] = "Database migrations completed";
            } else {
                $this->errors[] = "Migration output: " . $output;
            }
        } else {
            $this->errors[] = "Artisan file not found";
        }

        echo "✓ Database migrations completed\n\n";
    }

    private function setPermissions()
    {
        echo "Step 6: Setting file permissions...\n";
        
        $permissions = [
            'storage' => 0755,
            'storage/framework' => 0755,
            'storage/framework/cache' => 0755,
            'storage/framework/sessions' => 0755,
            'storage/framework/views' => 0755,
            'storage/logs' => 0755,
            'bootstrap/cache' => 0755,
            'public/storage' => 0755
        ];

        foreach ($permissions as $path => $permission) {
            if (file_exists($path)) {
                if (chmod($path, $permission)) {
                    $this->success[] = "Set permissions for $path";
                } else {
                    $this->errors[] = "Failed to set permissions for $path";
                }
            } else {
                $this->errors[] = "Directory not found: $path";
            }
        }

        echo "✓ File permissions set\n\n";
    }

    private function optimizeApplication()
    {
        echo "Step 7: Optimizing application...\n";
        
        if (file_exists('artisan')) {
            $commands = [
                'config:cache',
                'route:cache', 
                'view:cache',
                'optimize'
            ];

            foreach ($commands as $command) {
                $output = shell_exec("php artisan $command --force 2>&1");
                $this->success[] = "Ran: php artisan $command";
            }
        }

        echo "✓ Application optimization completed\n\n";
    }

    private function cleanup()
    {
        echo "Step 8: Cleanup...\n";
        
        // Create a .htaccess in storage to prevent direct access
        $storageHtaccess = "storage/public/.htaccess";
        if (!file_exists($storageHtaccess)) {
            file_put_contents($storageHtaccess, "deny from all\n");
        }

        $this->success[] = "Created security files";
        echo "✓ Cleanup completed\n\n";
    }

    private function readEnv()
    {
        $env = [];
        if (file_exists('.env')) {
            $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value);
                }
            }
        }
        return $env;
    }

    private function showResults()
    {
        echo "🎉 Setup Results\n";
        echo "================\n\n";

        if (!empty($this->success)) {
            echo "✅ Success:\n";
            foreach ($this->success as $item) {
                echo "   • $item\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "   • $error\n";
            }
            echo "\n";
        }

        if (empty($this->errors)) {
            echo "🎊 Setup completed successfully!\n\n";
            echo "Next steps:\n";
            echo "1. Visit your domain to see the application\n";
            echo "2. Go to /admin for the admin panel\n";
            echo "3. Delete this file (hostinger-setup.php) for security\n";
            echo "4. Check storage/logs/laravel.log for any errors\n\n";
            
            echo "Default admin credentials (if seeded):\n";
            echo "Email: admin@itqan.com\n";
            echo "Password: password\n\n";
            
            echo "⚠️  Security reminder: Change default passwords and enable SSL!\n";
        } else {
            echo "⚠️  Setup completed with errors. Please fix the issues above.\n";
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $setup = new HostingerSetup();
    $setup->run();
} else {
    // Browser execution with basic security
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $setup = new HostingerSetup();
        $setup->run();
    } else {
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Itqan Platform - Hostinger Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚀 Itqan Platform - Hostinger Setup</h1>
    
    <div class="warning">
        <h3>⚠️ Security Warning</h3>
        <p>This script will set up your Laravel application on the server. Only run this if you have already uploaded all files to your Hostinger hosting account.</p>
        <p><strong>Delete this file after setup is complete!</strong></p>
    </div>
    
    <h3>Prerequisites:</h3>
    <ul>
        <li>All Laravel files uploaded to public_html</li>
        <li>MySQL database created in Hostinger control panel</li>
        <li>.env file configured with database credentials</li>
    </ul>
    
    <p><a href="?confirm=yes" class="button">Start Setup</a></p>
    
    <h3>What this script will do:</h3>
    <ul>
        <li>Check environment and PHP extensions</li>
        <li>Generate application key</li>
        <li>Run database migrations</li>
        <li>Set proper file permissions</li>
        <li>Optimize application for production</li>
    </ul>
</body>
</html>';
    }
}
?>
