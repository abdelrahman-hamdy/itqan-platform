#!/usr/bin/env python3
"""
SessionStatus Enum Refactoring Script
Refactors string literals to use SessionStatus enum constants
"""

import re
import os
import sys
from pathlib import Path
from typing import List, Tuple

# Status values to refactor
STATUS_VALUES = [
    'unscheduled',
    'scheduled',
    'ready',
    'ongoing',
    'completed',
    'cancelled',
    'absent'
]

# Status enum constants mapping
STATUS_ENUM_MAP = {
    'unscheduled': 'SessionStatus::UNSCHEDULED',
    'scheduled': 'SessionStatus::SCHEDULED',
    'ready': 'SessionStatus::READY',
    'ongoing': 'SessionStatus::ONGOING',
    'completed': 'SessionStatus::COMPLETED',
    'cancelled': 'SessionStatus::CANCELLED',
    'absent': 'SessionStatus::ABSENT'
}

def has_enum_import(content: str) -> bool:
    """Check if file already has SessionStatus import"""
    return bool(re.search(r'use\s+App\\Enums\\SessionStatus;', content))

def add_enum_import(content: str) -> str:
    """Add SessionStatus import to file"""
    if has_enum_import(content):
        return content

    # Find the last 'use' statement
    use_matches = list(re.finditer(r'^use\s+.*;$', content, re.MULTILINE))

    if use_matches:
        # Insert after last use statement
        last_use = use_matches[-1]
        insert_pos = last_use.end()
        return content[:insert_pos] + '\nuse App\\Enums\\SessionStatus;' + content[insert_pos:]

    # Find namespace and insert after it
    namespace_match = re.search(r'^namespace\s+.*;$', content, re.MULTILINE)
    if namespace_match:
        insert_pos = namespace_match.end()
        return content[:insert_pos] + '\n\nuse App\\Enums\\SessionStatus;' + content[insert_pos:]

    return content

def refactor_file_content(content: str, filename: str) -> Tuple[str, int]:
    """
    Refactor content to use SessionStatus enum
    Returns (new_content, changes_count)
    """
    original_content = content
    changes = 0

    # Skip if already heavily refactored (heuristic)
    if content.count('SessionStatus::') > 10:
        return content, 0

    # Add import first
    content = add_enum_import(content)
    if content != original_content:
        changes += 1

    # Pattern 1: ->where('status', 'value') - needs ->value
    for status in STATUS_VALUES:
        pattern = rf"->where\('status',\s*'{status}'\)"
        replacement = f"->where('status', {STATUS_ENUM_MAP[status]}->value)"
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 2: ->where("status", "value") - needs ->value
    for status in STATUS_VALUES:
        pattern = rf'->where\("status",\s*"{status}"\)'
        replacement = f'->where("status", {STATUS_ENUM_MAP[status]}->value)'
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 3: 'status' => 'value' (in arrays) - direct enum
    for status in STATUS_VALUES:
        pattern = rf"'status'\s*=>\s*'{status}'"
        replacement = f"'status' => {STATUS_ENUM_MAP[status]}"
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 4: "status" => "value" (in arrays) - direct enum
    for status in STATUS_VALUES:
        pattern = rf'"status"\s*=>\s*"{status}"'
        replacement = f'"status" => {STATUS_ENUM_MAP[status]}'
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 5: ->status === 'value' - direct enum
    for status in STATUS_VALUES:
        pattern = rf"->status\s*===\s*'{status}'"
        replacement = f"->status === {STATUS_ENUM_MAP[status]}"
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 6: ->status == 'value' - direct enum
    for status in STATUS_VALUES:
        pattern = rf"->status\s*==\s*'{status}'"
        replacement = f"->status == {STATUS_ENUM_MAP[status]}"
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 7: $status === 'value' - direct enum
    for status in STATUS_VALUES:
        pattern = rf"\$status\s*===\s*'{status}'"
        replacement = f"$status === {STATUS_ENUM_MAP[status]}"
        new_content = re.sub(pattern, replacement, content)
        if new_content != content:
            count = len(re.findall(pattern, content))
            changes += count
            content = new_content

    # Pattern 8: $statusValue === 'value' - direct enum
    for status in STATUS_VALUES:
        pattern = rf"\$statusValue\s*===\s*'{status}'"
        replacement = f"$statusValue === '{status}'"  # Keep this as is - it's comparing against ->value
        # Don't change this pattern as it's already correct

    # Pattern 9: Collection where - needs ->value
    for status in STATUS_VALUES:
        pattern = rf"->where\('status',\s*'{status}'\)"
        # Already handled above
        pass

    return content, changes

def should_skip_file(filepath: Path) -> bool:
    """Check if file should be skipped"""
    skip_patterns = [
        'Blade.php',
        'Test.php',
        '/tests/',
        '/vendor/',
        'SessionStatus.php',  # The enum file itself
    ]

    path_str = str(filepath)
    return any(pattern in path_str for pattern in skip_patterns)

def process_file(filepath: Path) -> Tuple[bool, int]:
    """
    Process a single file
    Returns (success, changes_count)
    """
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            original_content = f.read()

        new_content, changes = refactor_file_content(original_content, filepath.name)

        if changes > 0:
            # Create backup
            backup_path = filepath.with_suffix(filepath.suffix + '.backup')
            with open(backup_path, 'w', encoding='utf-8') as f:
                f.write(original_content)

            # Write refactored content
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)

            print(f"✓ {filepath.relative_to(Path.cwd())}: {changes} changes")
            return True, changes
        else:
            print(f"- {filepath.relative_to(Path.cwd())}: no changes needed")
            return False, 0

    except Exception as e:
        print(f"✗ Error processing {filepath}: {e}")
        return False, 0

def main():
    """Main refactoring function"""
    print("SessionStatus Enum Refactoring Script")
    print("=" * 50)

    # Target directories
    target_dirs = [
        Path('app/Http/Controllers'),
        Path('app/Services'),
        Path('app/Livewire'),
    ]

    # Add routes/web.php
    target_files = [
        Path('routes/web.php')
    ]

    total_files = 0
    total_changes = 0
    refactored_files = 0

    # Process directories
    for target_dir in target_dirs:
        if not target_dir.exists():
            print(f"Warning: {target_dir} does not exist")
            continue

        print(f"\nProcessing directory: {target_dir}")
        print("-" * 50)

        for filepath in target_dir.rglob('*.php'):
            if should_skip_file(filepath):
                continue

            total_files += 1
            success, changes = process_file(filepath)
            if success:
                refactored_files += 1
                total_changes += changes

    # Process individual files
    print(f"\nProcessing routes/web.php")
    print("-" * 50)
    for filepath in target_files:
        if filepath.exists():
            total_files += 1
            success, changes = process_file(filepath)
            if success:
                refactored_files += 1
                total_changes += changes

    # Summary
    print("\n" + "=" * 50)
    print("Refactoring Complete!")
    print(f"Total files processed: {total_files}")
    print(f"Files refactored: {refactored_files}")
    print(f"Total changes made: {total_changes}")
    print("=" * 50)
    print("\nBackup files created with .backup extension")
    print("Please review changes and run tests before committing")

if __name__ == '__main__':
    main()
