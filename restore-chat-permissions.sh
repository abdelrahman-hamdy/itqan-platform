#!/bin/bash

echo "🔒 Restoring chat permission checks..."

# Restore permission check in idFetchData method
sed -i '' '837,843s|^\(\s*\)//|\1|' app/Http/Controllers/vendor/Chatify/MessagesController.php

# Restore permission check in send method
sed -i '' '943,948s|^\(\s*\)//|\1|' app/Http/Controllers/vendor/Chatify/MessagesController.php

# Clear caches
php artisan config:clear
php artisan route:clear

echo "✅ Permission checks restored!"
echo "📝 Teachers can now only message students they teach"
echo "📝 Students can message any teacher"
echo ""
echo "To create a teaching relationship, run:"
echo "php artisan tinker"
echo "> AcademicSubscription::create(['teacher_id' => 3, 'student_id' => 2, ...]);"