#!/bin/bash

# SessionStatus Enum Refactoring Script
# This script refactors string literals to use SessionStatus enum constants

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting SessionStatus Enum Refactoring...${NC}\n"

# Target directories
DIRS=(
    "app/Http/Controllers"
    "app/Services"
    "app/Livewire"
)

# Counter for changes
total_files=0
total_changes=0

# Function to add import if not exists
add_import_if_needed() {
    local file="$1"

    # Check if SessionStatus import already exists
    if ! grep -q "use App\\\\Enums\\\\SessionStatus;" "$file"; then
        # Find the namespace line
        if grep -q "^namespace " "$file"; then
            # Add import after namespace and existing use statements
            # Find last use statement line number
            last_use_line=$(grep -n "^use " "$file" | tail -1 | cut -d: -f1)

            if [ -n "$last_use_line" ]; then
                # Insert after last use statement
                sed -i.bak "${last_use_line}a\\
use App\\\\Enums\\\\SessionStatus;
" "$file"
            else
                # Insert after namespace
                namespace_line=$(grep -n "^namespace " "$file" | head -1 | cut -d: -f1)
                sed -i.bak "${namespace_line}a\\
\\
use App\\\\Enums\\\\SessionStatus;
" "$file"
            fi

            return 0
        fi
    fi

    return 1
}

# Function to refactor a file
refactor_file() {
    local file="$1"
    local changes=0

    # Skip if file already has proper enum usage (heuristic check)
    if grep -q "SessionStatus::" "$file"; then
        echo -e "${YELLOW}Skipping (already refactored):${NC} $file"
        return 0
    fi

    echo -e "${GREEN}Processing:${NC} $file"

    # Create backup
    cp "$file" "${file}.backup"

    # Add import
    add_import_if_needed "$file"

    # Refactor patterns (in order of specificity to avoid conflicts)

    # Pattern 1: ->where('status', 'value')
    sed -i.tmp "s/->where('status', 'scheduled')/->where('status', SessionStatus::SCHEDULED->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'completed')/->where('status', SessionStatus::COMPLETED->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'ongoing')/->where('status', SessionStatus::ONGOING->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'cancelled')/->where('status', SessionStatus::CANCELLED->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'ready')/->where('status', SessionStatus::READY->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'absent')/->where('status', SessionStatus::ABSENT->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'unscheduled')/->where('status', SessionStatus::UNSCHEDULED->value)/g" "$file"

    # Pattern 2: ->where("status", "value")
    sed -i.tmp 's/->where("status", "scheduled")/->where("status", SessionStatus::SCHEDULED->value)/g' "$file"
    sed -i.tmp 's/->where("status", "completed")/->where("status", SessionStatus::COMPLETED->value)/g' "$file"
    sed -i.tmp 's/->where("status", "ongoing")/->where("status", SessionStatus::ONGOING->value)/g' "$file"
    sed -i.tmp 's/->where("status", "cancelled")/->where("status", SessionStatus::CANCELLED->value)/g' "$file"
    sed -i.tmp 's/->where("status", "ready")/->where("status", SessionStatus::READY->value)/g' "$file"
    sed -i.tmp 's/->where("status", "absent")/->where("status", SessionStatus::ABSENT->value)/g' "$file"
    sed -i.tmp 's/->where("status", "unscheduled")/->where("status", SessionStatus::UNSCHEDULED->value)/g' "$file"

    # Pattern 3: whereIn('status', ['value'])
    sed -i.tmp "s/whereIn('status', \['scheduled'\])/whereIn('status', [SessionStatus::SCHEDULED->value])/g" "$file"
    sed -i.tmp "s/whereIn('status', \['completed'\])/whereIn('status', [SessionStatus::COMPLETED->value])/g" "$file"
    sed -i.tmp "s/whereIn('status', \['ongoing'\])/whereIn('status', [SessionStatus::ONGOING->value])/g" "$file"
    sed -i.tmp "s/whereIn('status', \['cancelled'\])/whereIn('status', [SessionStatus::CANCELLED->value])/g" "$file"

    # Pattern 4: 'status' => 'value' (in array assignments)
    sed -i.tmp "s/'status' => 'scheduled'/'status' => SessionStatus::SCHEDULED/g" "$file"
    sed -i.tmp "s/'status' => 'completed'/'status' => SessionStatus::COMPLETED/g" "$file"
    sed -i.tmp "s/'status' => 'ongoing'/'status' => SessionStatus::ONGOING/g" "$file"
    sed -i.tmp "s/'status' => 'cancelled'/'status' => SessionStatus::CANCELLED/g" "$file"
    sed -i.tmp "s/'status' => 'ready'/'status' => SessionStatus::READY/g" "$file"
    sed -i.tmp "s/'status' => 'absent'/'status' => SessionStatus::ABSENT/g" "$file"
    sed -i.tmp "s/'status' => 'unscheduled'/'status' => SessionStatus::UNSCHEDULED/g" "$file"

    # Pattern 5: \$status === 'value' or == 'value'
    sed -i.tmp "s/\$status === 'scheduled'/\$status === SessionStatus::SCHEDULED/g" "$file"
    sed -i.tmp "s/\$status === 'completed'/\$status === SessionStatus::COMPLETED/g" "$file"
    sed -i.tmp "s/\$status === 'ongoing'/\$status === SessionStatus::ONGOING/g" "$file"
    sed -i.tmp "s/\$status === 'cancelled'/\$status === SessionStatus::CANCELLED/g" "$file"
    sed -i.tmp "s/\$status === 'ready'/\$status === SessionStatus::READY/g" "$file"
    sed -i.tmp "s/\$status === 'absent'/\$status === SessionStatus::ABSENT/g" "$file"

    sed -i.tmp "s/\$status == 'scheduled'/\$status == SessionStatus::SCHEDULED/g" "$file"
    sed -i.tmp "s/\$status == 'completed'/\$status == SessionStatus::COMPLETED/g" "$file"
    sed -i.tmp "s/\$status == 'ongoing'/\$status == SessionStatus::ONGOING/g" "$file"
    sed -i.tmp "s/\$status == 'cancelled'/\$status == SessionStatus::CANCELLED/g" "$file"
    sed -i.tmp "s/\$status == 'ready'/\$status == SessionStatus::READY/g" "$file"

    # Pattern 6: \$session->status === 'value'
    sed -i.tmp "s/->status === 'scheduled'/->status === SessionStatus::SCHEDULED/g" "$file"
    sed -i.tmp "s/->status === 'completed'/->status === SessionStatus::COMPLETED/g" "$file"
    sed -i.tmp "s/->status === 'ongoing'/->status === SessionStatus::ONGOING/g" "$file"
    sed -i.tmp "s/->status === 'cancelled'/->status === SessionStatus::CANCELLED/g" "$file"
    sed -i.tmp "s/->status === 'ready'/->status === SessionStatus::READY/g" "$file"
    sed -i.tmp "s/->status === 'absent'/->status === SessionStatus::ABSENT/g" "$file"

    # Pattern 7: where('status') with collection filtering
    sed -i.tmp "s/->where('status', 'scheduled')/->where('status', SessionStatus::SCHEDULED->value)/g" "$file"
    sed -i.tmp "s/->where('status', 'completed')/->where('status', SessionStatus::COMPLETED->value)/g" "$file"

    # Check if changes were made
    if ! diff -q "$file" "${file}.backup" > /dev/null 2>&1; then
        changes=1
        ((total_changes++))
        echo -e "${GREEN}  ✓ Refactored${NC}"
    else
        echo -e "${YELLOW}  - No changes needed${NC}"
        # Restore from backup if no real changes
        mv "${file}.backup" "$file"
    fi

    # Clean up temp files
    rm -f "${file}.tmp" "${file}.bak"

    return $changes
}

# Process all directories
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "\n${GREEN}=== Processing directory: $dir ===${NC}\n"

        # Find all PHP files (excluding blade templates and tests)
        while IFS= read -r file; do
            ((total_files++))
            refactor_file "$file"
        done < <(find "$dir" -type f -name "*.php" ! -name "*Blade.php" ! -name "*Test.php")
    fi
done

# Handle routes/web.php separately
if [ -f "routes/web.php" ]; then
    echo -e "\n${GREEN}=== Processing routes/web.php ===${NC}\n"
    ((total_files++))
    refactor_file "routes/web.php"
fi

echo -e "\n${GREEN}===========================================\n"
echo -e "${GREEN}Refactoring Complete!${NC}"
echo -e "${GREEN}Total files processed: $total_files${NC}"
echo -e "${GREEN}Total files refactored: $total_changes${NC}"
echo -e "${GREEN}===========================================\n"

echo -e "${YELLOW}Note: Backup files created with .backup extension${NC}"
echo -e "${YELLOW}Please review changes and run tests before committing${NC}"
