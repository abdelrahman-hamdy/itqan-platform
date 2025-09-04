<?php

namespace App\Enums;

enum QuranSurah: string
{
    case AL_FATIHA = 'الفاتحة';
    case AL_BAQARAH = 'البقرة';
    case AL_IMRAN = 'آل عمران';
    case AN_NISA = 'النساء';
    case AL_MAIDAH = 'المائدة';
    case AL_ANAM = 'الأنعام';
    case AL_ARAF = 'الأعراف';
    case AL_ANFAL = 'الأنفال';
    case AT_TAWBAH = 'التوبة';
    case YUNUS = 'يونس';
    case HUD = 'هود';
    case YUSUF = 'يوسف';
    case AR_RAD = 'الرعد';
    case IBRAHIM = 'إبراهيم';
    case AL_HIJR = 'الحجر';
    case AN_NAHL = 'النحل';
    case AL_ISRA = 'الإسراء';
    case AL_KAHF = 'الكهف';
    case MARYAM = 'مريم';
    case TA_HA = 'طه';
    case AL_ANBIYA = 'الأنبياء';
    case AL_HAJJ = 'الحج';
    case AL_MUMINUN = 'المؤمنون';
    case AN_NUR = 'النور';
    case AL_FURQAN = 'الفرقان';
    case ASH_SHUARA = 'الشعراء';
    case AN_NAML = 'النمل';
    case AL_QASAS = 'القصص';
    case AL_ANKABUT = 'العنكبوت';
    case AR_RUM = 'الروم';
    case LUQMAN = 'لقمان';
    case AS_SAJDAH = 'السجدة';
    case AL_AHZAB = 'الأحزاب';
    case SABA = 'سبأ';
    case FATIR = 'فاطر';
    case YA_SIN = 'يس';
    case AS_SAFFAT = 'الصافات';
    case SAD = 'ص';
    case AZ_ZUMAR = 'الزمر';
    case GHAFIR = 'غافر';
    case FUSSILAT = 'فصلت';
    case ASH_SHURA = 'الشورى';
    case AZ_ZUKHRUF = 'الزخرف';
    case AD_DUKHAN = 'الدخان';
    case AL_JATHIYAH = 'الجاثية';
    case AL_AHQAF = 'الأحقاف';
    case MUHAMMAD = 'محمد';
    case AL_FATH = 'الفتح';
    case AL_HUJURAT = 'الحجرات';
    case QAF = 'ق';
    case ADH_DHARIYAT = 'الذاريات';
    case AT_TUR = 'الطور';
    case AN_NAJM = 'النجم';
    case AL_QAMAR = 'القمر';
    case AR_RAHMAN = 'الرحمن';
    case AL_WAQIAH = 'الواقعة';
    case AL_HADID = 'الحديد';
    case AL_MUJADILAH = 'المجادلة';
    case AL_HASHR = 'الحشر';
    case AL_MUMTAHINAH = 'الممتحنة';
    case AS_SAFF = 'الصف';
    case AL_JUMUAH = 'الجمعة';
    case AL_MUNAFIQUN = 'المنافقون';
    case AT_TAGHABUN = 'التغابن';
    case AT_TALAQ = 'الطلاق';
    case AT_TAHRIM = 'التحريم';
    case AL_MULK = 'الملك';
    case AL_QALAM = 'القلم';
    case AL_HAQQAH = 'الحاقة';
    case AL_MAARIJ = 'المعارج';
    case NUH = 'نوح';
    case AL_JINN = 'الجن';
    case AL_MUZZAMMIL = 'المزمل';
    case AL_MUDDATHTHIR = 'المدثر';
    case AL_QIYAMAH = 'القيامة';
    case AL_INSAN = 'الإنسان';
    case AL_MURSALAT = 'المرسلات';
    case AN_NABA = 'النبأ';
    case AN_NAZIAT = 'النازعات';
    case ABASA = 'عبس';
    case AT_TAKWIR = 'التكوير';
    case AL_INFITAR = 'الانفطار';
    case AL_MUTAFFIFIN = 'المطففين';
    case AL_INSHIQAQ = 'الانشقاق';
    case AL_BURUJ = 'البروج';
    case AT_TARIQ = 'الطارق';
    case AL_ALA = 'الأعلى';
    case AL_GHASHIYAH = 'الغاشية';
    case AL_FAJR = 'الفجر';
    case AL_BALAD = 'البلد';
    case ASH_SHAMS = 'الشمس';
    case AL_LAYL = 'الليل';
    case AD_DUHA = 'الضحى';
    case ASH_SHARH = 'الشرح';
    case AT_TIN = 'التين';
    case AL_ALAQ = 'العلق';
    case AL_QADR = 'القدر';
    case AL_BAYYINAH = 'البينة';
    case AZ_ZALZALAH = 'الزلزلة';
    case AL_ADIYAT = 'العاديات';
    case AL_QARIAH = 'القارعة';
    case AT_TAKATHUR = 'التكاثر';
    case AL_ASR = 'العصر';
    case AL_HUMAZAH = 'الهمزة';
    case AL_FIL = 'الفيل';
    case QURAYSH = 'قريش';
    case AL_MAUN = 'الماعون';
    case AL_KAWTHAR = 'الكوثر';
    case AL_KAFIRUN = 'الكافرون';
    case AN_NASR = 'النصر';
    case AL_MASAD = 'المسد';
    case AL_IKHLAS = 'الإخلاص';
    case AL_FALAQ = 'الفلق';
    case AN_NAS = 'الناس';

    /**
     * Get all surah names as array for select options
     */
    public static function getAllSurahs(): array
    {
        return array_combine(
            array_column(self::cases(), 'name'),
            array_column(self::cases(), 'value')
        );
    }

    /**
     * Get surah number (1-114)
     */
    public function getNumber(): int
    {
        return array_search($this, self::cases()) + 1;
    }

    /**
     * Get surah by number (1-114)
     */
    public static function getByNumber(int $number): ?self
    {
        if ($number < 1 || $number > 114) {
            return null;
        }

        return self::cases()[$number - 1] ?? null;
    }

    /**
     * Get surah name with number
     */
    public function getNameWithNumber(): string
    {
        return $this->getNumber().'. '.$this->value;
    }

    /**
     * Get Arabic name for a surah (handles both English and Arabic input)
     */
    public static function getArabicName(string $input): string
    {
        // If input is already Arabic (contains Arabic characters), return as is
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $input)) {
            return $input;
        }

        // Try to find by English name (case insensitive)
        $allSurahs = self::getAllSurahs();

        // First try exact match with the name
        if (isset($allSurahs[$input])) {
            return $allSurahs[$input];
        }

        // Try case-insensitive search
        foreach ($allSurahs as $englishName => $arabicName) {
            if (strtolower($englishName) === strtolower($input)) {
                return $arabicName;
            }
        }

        // Try partial match (for cases like "Al-Fatiha" vs "AL_FATIHA")
        $normalizedInput = strtoupper(str_replace(['-', ' '], '_', $input));
        foreach ($allSurahs as $englishName => $arabicName) {
            if (strtoupper($englishName) === $normalizedInput) {
                return $arabicName;
            }
        }

        // If no match found, return the original input
        return $input;
    }
}
