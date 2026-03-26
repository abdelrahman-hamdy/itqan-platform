<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillStudentGender extends Command
{
    protected $signature = 'app:backfill-student-gender {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Backfill NULL gender on student_profiles using KSA naming culture heuristics';

    /**
     * Common Arabic female names (KSA culture).
     */
    protected array $femaleNames = [
        'فاطمة', 'عائشة', 'مريم', 'نورة', 'سارة', 'هند', 'أمل', 'منى',
        'ليلى', 'رقية', 'خديجة', 'زينب', 'حفصة', 'أسماء', 'سمية', 'رنا',
        'دانة', 'لمى', 'ريم', 'غادة', 'هيفاء', 'سلمى', 'بشرى', 'نجلاء',
        'أروى', 'رهام', 'نوف', 'العنود', 'مها', 'دلال', 'جوهرة', 'حصة',
        'موضي', 'لطيفة', 'شيخة', 'بدرية', 'منيرة', 'جميلة', 'سعاد', 'نوال',
        'هدى', 'نادية', 'سحر', 'إيمان', 'آمنة', 'رحاب', 'أميرة', 'ابتسام',
        'وفاء', 'صفاء', 'رجاء', 'سناء', 'هناء', 'حنان', 'سهام', 'نهى',
        'ياسمين', 'جنان', 'رشا', 'لينا', 'دينا', 'رانيا', 'هالة', 'علياء',
        'شيماء', 'نورا', 'سمر', 'عبير', 'لمياء', 'ناديه', 'نوره',
        'ساره', 'هيا', 'لجين', 'رزان', 'تالا', 'جود', 'رغد', 'وجدان',
        'بيان', 'تهاني', 'خلود', 'مشاعل', 'أثير', 'لولوة', 'ديمة', 'وعد',
        'حلا', 'ملاك', 'جنى', 'تالين', 'سيلين', 'روان', 'ريماس', 'جوري',
        'لمار', 'ميرة', 'شهد', 'يارا', 'لانا', 'ريتاج', 'سدن', 'تيماء',
        'عهود', 'بتول', 'نجود', 'رقيه', 'فوزية', 'نجاة', 'حياة',
        // Transliterated
        'fatima', 'aisha', 'maryam', 'noura', 'sara', 'sarah', 'hind', 'amal',
        'mona', 'layla', 'leila', 'khadija', 'zainab', 'asma', 'rana', 'dana',
        'reem', 'reema', 'ghada', 'salma', 'huda', 'nadia', 'iman', 'amina',
        'amira', 'wafa', 'hanan', 'yasmin', 'rasha', 'lina', 'dina', 'rania',
        'hala', 'shimaa', 'nora', 'abeer', 'hayat', 'lujain', 'razan', 'tala',
        'jood', 'raghad', 'meshael', 'atheer', 'deema', 'waad', 'malak', 'jana',
        'jouri', 'shahd', 'yara', 'lana', 'rawan', 'maira',
    ];

    protected ?array $femaleNamesLookup = null;

    protected function getFemaleNamesLookup(): array
    {
        return $this->femaleNamesLookup ??= array_flip(
            array_map('mb_strtolower', array_unique($this->femaleNames))
        );
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $students = DB::table('student_profiles')
            ->join('users', 'student_profiles.user_id', '=', 'users.id')
            ->whereNull('student_profiles.gender')
            ->select([
                'student_profiles.id',
                'student_profiles.user_id',
                'users.first_name',
                'users.name',
                'users.gender as user_gender',
            ])
            ->get();

        if ($students->isEmpty()) {
            $this->info('No students with NULL gender found.');

            return self::SUCCESS;
        }

        $this->info("Found {$students->count()} students with NULL gender.");

        $maleCount = 0;
        $femaleCount = 0;
        $updates = [];

        foreach ($students as $student) {
            if ($student->user_gender) {
                $gender = $student->user_gender;
            } else {
                $gender = $this->inferGenderFromName($student->first_name ?? $student->name);
            }

            $updates[] = [
                'id' => $student->id,
                'name' => $student->first_name ?? $student->name,
                'gender' => $gender,
            ];

            if ($gender === 'female') {
                $femaleCount++;
            } else {
                $maleCount++;
            }
        }

        $this->table(
            ['ID', 'Name', 'Inferred Gender'],
            collect($updates)->map(fn ($u) => [$u['id'], $u['name'], $u['gender']])->toArray()
        );

        $this->info("Summary: {$maleCount} male, {$femaleCount} female");

        if ($isDryRun) {
            $this->warn('Dry run — no changes made.');

            return self::SUCCESS;
        }

        // Batch update: 2 queries instead of N
        $maleIds = collect($updates)->where('gender', 'male')->pluck('id')->toArray();
        $femaleIds = collect($updates)->where('gender', 'female')->pluck('id')->toArray();

        if ($maleIds) {
            DB::table('student_profiles')->whereIn('id', $maleIds)->update(['gender' => 'male']);
        }
        if ($femaleIds) {
            DB::table('student_profiles')->whereIn('id', $femaleIds)->update(['gender' => 'female']);
        }

        Log::info('BackfillStudentGender completed', [
            'total' => count($updates),
            'male' => $maleCount,
            'female' => $femaleCount,
        ]);

        $this->info('Student gender backfill completed successfully.');

        return self::SUCCESS;
    }

    protected function inferGenderFromName(?string $name): string
    {
        if (! $name) {
            return 'male';
        }

        $name = trim($name);
        $nameLower = mb_strtolower($name);

        // O(1) hash lookup against known female names
        if (isset($this->getFemaleNamesLookup()[$nameLower])) {
            return 'female';
        }

        // Arabic name ending in taa marbuta (ة) — strong female indicator
        if (mb_substr($name, -1) === 'ة') {
            return 'female';
        }

        // Names starting with أم (Umm = mother of)
        if (mb_strpos($name, 'أم ') === 0) {
            return 'female';
        }

        return 'male';
    }
}
