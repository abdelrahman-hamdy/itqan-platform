#!/bin/bash

# LiveKit Integration Test Script
# Tests all components of the LiveKit integration

set -e

echo "🧪 Testing LiveKit Integration..."
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counter
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run a test
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_code="$3"
    
    TESTS_RUN=$((TESTS_RUN + 1))
    echo -e "${BLUE}📋 Testing: $test_name${NC}"
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASSED: $test_name${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ FAILED: $test_name${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

# Function to test HTTP endpoint
test_http() {
    local name="$1"
    local url="$2"
    local expected_code="$3"
    
    TESTS_RUN=$((TESTS_RUN + 1))
    echo -e "${BLUE}📋 Testing: $name${NC}"
    
    local response_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
    
    if [ "$response_code" = "$expected_code" ]; then
        echo -e "${GREEN}✅ PASSED: $name (HTTP $response_code)${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ FAILED: $name (Expected: $expected_code, Got: $response_code)${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo -e "${BLUE}🔍 Phase 1: Environment Tests${NC}"
echo "=================================="

# Test Docker availability
run_test "Docker is available" "command -v docker"
run_test "Docker is running" "docker info"
run_test "Docker Compose is available" "command -v docker-compose"

echo ""
echo -e "${BLUE}🐳 Phase 2: Service Tests${NC}"
echo "=================================="

# Test if LiveKit containers are running
if docker-compose -f docker-compose.livekit.yml ps -q | grep -q .; then
    echo -e "${GREEN}✅ LiveKit containers are running${NC}"
    
    # Test individual services
    test_http "LiveKit Server Health Check" "http://localhost:7880/" "200"
    run_test "Redis Connection" "docker exec itqan-livekit-redis redis-cli ping | grep -q PONG"
    
else
    echo -e "${YELLOW}⚠️  LiveKit containers not running. Starting them...${NC}"
    docker-compose -f docker-compose.livekit.yml up -d
    echo -e "${BLUE}⏳ Waiting for services to start...${NC}"
    sleep 10
    
    test_http "LiveKit Server Health Check" "http://localhost:7880/" "200"
    run_test "Redis Connection" "docker exec itqan-livekit-redis redis-cli ping | grep -q PONG"
fi

echo ""
echo -e "${BLUE}📁 Phase 3: File System Tests${NC}"
echo "=================================="

# Test directory structure
run_test "LiveKit config directory exists" "[ -d 'livekit-config' ]"
run_test "LiveKit config file exists" "[ -f 'livekit-config/livekit.yaml' ]"
run_test "Recordings directory exists" "[ -d 'storage/livekit-recordings' ]"
run_test "Docker compose file exists" "[ -f 'docker-compose.livekit.yml' ]"
run_test "Docker compose is valid" "docker-compose -f docker-compose.livekit.yml config"

echo ""
echo -e "${BLUE}⚙️ Phase 4: Laravel Configuration Tests${NC}"
echo "=================================="

# Test Laravel configuration
run_test "LiveKit config file exists" "[ -f 'config/livekit.php' ]"
run_test "Laravel can load config" "php artisan config:show livekit.server_url"
run_test "LiveKit service can be instantiated" "php -r 'require \"vendor/autoload.php\"; \$app = require \"bootstrap/app.php\"; new \App\Services\LiveKitService();'"

echo ""
echo -e "${BLUE}🔧 Phase 5: Laravel Command Tests${NC}"
echo "=================================="

# Test Laravel commands
run_test "Meeting creation command exists" "php artisan list | grep -q 'meetings:create-scheduled'"
run_test "Meeting cleanup command exists" "php artisan list | grep -q 'meetings:cleanup-expired'"
run_test "Meeting creation command runs" "php artisan meetings:create-scheduled --dry-run"
run_test "Meeting cleanup command runs" "php artisan meetings:cleanup-expired --dry-run"

echo ""
echo -e "${BLUE}📅 Phase 6: Scheduler Tests${NC}"
echo "=================================="

# Test scheduled tasks
run_test "Scheduled tasks are configured" "php artisan schedule:list | grep -q 'meetings:create-scheduled'"
run_test "Schedule run works" "php artisan schedule:run --verbose"

echo ""
echo -e "${BLUE}🗄️ Phase 7: Database Tests${NC}"
echo "=================================="

# Test database models and migrations
run_test "Video Settings table exists" "php artisan tinker --execute=\"\Schema::hasTable('video_settings');\""
run_test "Teacher Video Settings table exists" "php artisan tinker --execute=\"\Schema::hasTable('teacher_video_settings');\""
run_test "VideoSettings model can be instantiated" "php artisan tinker --execute=\"new \App\Models\VideoSettings();\""
run_test "TeacherVideoSettings model can be instantiated" "php artisan tinker --execute=\"new \App\Models\TeacherVideoSettings();\""

echo ""
echo -e "${BLUE}🌐 Phase 8: Integration Tests${NC}"
echo "=================================="

# Test full integration
if php artisan tinker --execute="echo \App\Models\Academy::first() ? 'OK' : 'NO_ACADEMIES';" 2>/dev/null | grep -q "OK"; then
    run_test "Academy settings can be created" "php artisan tinker --execute=\"\$academy = \App\Models\Academy::first(); \App\Models\VideoSettings::forAcademy(\$academy);\""
    run_test "Video settings can be tested" "php artisan tinker --execute=\"\$academy = \App\Models\Academy::first(); \$settings = \App\Models\VideoSettings::forAcademy(\$academy); \$settings->testConfiguration();\""
else
    echo -e "${YELLOW}⚠️  No academies found in database - skipping academy-specific tests${NC}"
fi

echo ""
echo "========================================"
echo -e "${BLUE}📊 Test Results Summary${NC}"
echo "========================================"
echo -e "${BLUE}Tests Run:${NC} $TESTS_RUN"
echo -e "${GREEN}Tests Passed:${NC} $TESTS_PASSED"
echo -e "${RED}Tests Failed:${NC} $TESTS_FAILED"

# Calculate success rate
if [ $TESTS_RUN -gt 0 ]; then
    SUCCESS_RATE=$(( TESTS_PASSED * 100 / TESTS_RUN ))
    echo -e "${BLUE}Success Rate:${NC} $SUCCESS_RATE%"
fi

echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED!${NC}"
    echo -e "${GREEN}✨ Your LiveKit integration is working perfectly! ✨${NC}"
    echo ""
    echo -e "${BLUE}🚀 Next Steps:${NC}"
    echo "  • Visit: /admin/video-settings to configure"
    echo "  • Visit: /teacher-panel/{tenant}/teacher-video-settings for personal settings"
    echo "  • Create a test session to see auto-meeting creation"
    echo "  • Start the scheduler: php artisan schedule:work"
    echo ""
    exit 0
else
    echo -e "${RED}⚠️  Some tests failed. Please check the following:${NC}"
    echo ""
    echo -e "${YELLOW}Common Issues:${NC}"
    echo "  • Ensure Docker Desktop is running"
    echo "  • Run: ./start-livekit.sh"
    echo "  • Check: php artisan config:clear"
    echo "  • Verify: curl http://localhost:7880/"
    echo ""
    echo -e "${BLUE}🔧 Troubleshooting:${NC}"
    echo "  • Check Docker logs: docker-compose -f docker-compose.livekit.yml logs"
    echo "  • Check Laravel logs: tail -f storage/logs/laravel.log"
    echo "  • Test connectivity: curl -v http://localhost:7880/"
    echo ""
    exit 1
fi
