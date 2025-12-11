import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'colors.dart';

/// Itqan Platform Typography System
/// Primary: Tajawal (Arabic-optimized)
/// Secondary: Cairo (Headings)
/// Tertiary: Amiri (Quranic text)
class AppTypography {
  AppTypography._();

  // ============================================
  // FONT FAMILIES
  // ============================================

  /// Primary font - Tajawal (Arabic optimized, clean UI)
  static String get primaryFont => GoogleFonts.tajawal().fontFamily!;

  /// Secondary font - Cairo (Bold headings)
  static String get secondaryFont => GoogleFonts.cairo().fontFamily!;

  /// Quranic font - Amiri (Traditional Arabic script)
  static String get quranFont => GoogleFonts.amiri().fontFamily!;

  // ============================================
  // TEXT THEME
  // ============================================

  static TextTheme get textTheme => TextTheme(
        // Display styles (rarely used, for splash/hero)
        displayLarge: GoogleFonts.cairo(
          fontSize: 57,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
          height: 1.12,
        ),
        displayMedium: GoogleFonts.cairo(
          fontSize: 45,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
          height: 1.16,
        ),
        displaySmall: GoogleFonts.cairo(
          fontSize: 36,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.22,
        ),

        // Headline styles (page titles, section headers)
        headlineLarge: GoogleFonts.cairo(
          fontSize: 32,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
          height: 1.25,
        ),
        headlineMedium: GoogleFonts.cairo(
          fontSize: 28,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.29,
        ),
        headlineSmall: GoogleFonts.cairo(
          fontSize: 24,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.33,
        ),

        // Title styles (card titles, list item titles)
        titleLarge: GoogleFonts.tajawal(
          fontSize: 22,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.27,
        ),
        titleMedium: GoogleFonts.tajawal(
          fontSize: 16,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.5,
          letterSpacing: 0.15,
        ),
        titleSmall: GoogleFonts.tajawal(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.43,
          letterSpacing: 0.1,
        ),

        // Body styles (main content text)
        bodyLarge: GoogleFonts.tajawal(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: AppColors.textPrimary,
          height: 1.5,
          letterSpacing: 0.5,
        ),
        bodyMedium: GoogleFonts.tajawal(
          fontSize: 14,
          fontWeight: FontWeight.w400,
          color: AppColors.textSecondary,
          height: 1.43,
          letterSpacing: 0.25,
        ),
        bodySmall: GoogleFonts.tajawal(
          fontSize: 12,
          fontWeight: FontWeight.w400,
          color: AppColors.textTertiary,
          height: 1.33,
          letterSpacing: 0.4,
        ),

        // Label styles (buttons, badges, form labels)
        labelLarge: GoogleFonts.tajawal(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
          height: 1.43,
          letterSpacing: 0.1,
        ),
        labelMedium: GoogleFonts.tajawal(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          color: AppColors.textSecondary,
          height: 1.33,
          letterSpacing: 0.5,
        ),
        labelSmall: GoogleFonts.tajawal(
          fontSize: 11,
          fontWeight: FontWeight.w500,
          color: AppColors.textTertiary,
          height: 1.45,
          letterSpacing: 0.5,
        ),
      );

  // ============================================
  // CUSTOM TEXT STYLES
  // ============================================

  /// Price display (large numbers)
  static TextStyle get priceStyle => GoogleFonts.cairo(
        fontSize: 28,
        fontWeight: FontWeight.w800,
        color: AppColors.textPrimary,
        height: 1.2,
      );

  /// Price small (subscription cards)
  static TextStyle get priceSmall => GoogleFonts.cairo(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        height: 1.2,
      );

  /// Stat number (dashboard stats)
  static TextStyle get statNumber => GoogleFonts.cairo(
        fontSize: 24,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
        height: 1.2,
      );

  /// Quran text style
  static TextStyle get quranText => GoogleFonts.amiri(
        fontSize: 22,
        fontWeight: FontWeight.w400,
        color: AppColors.textPrimary,
        height: 2.0,
      );

  /// Quran verse number
  static TextStyle get quranVerse => GoogleFonts.amiri(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        color: AppColors.textSecondary,
        height: 1.5,
      );

  /// Button text
  static TextStyle get buttonLarge => GoogleFonts.tajawal(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        height: 1.5,
      );

  static TextStyle get buttonMedium => GoogleFonts.tajawal(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        height: 1.43,
      );

  static TextStyle get buttonSmall => GoogleFonts.tajawal(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        height: 1.33,
      );

  /// Badge text
  static TextStyle get badge => GoogleFonts.tajawal(
        fontSize: 11,
        fontWeight: FontWeight.w600,
        height: 1.0,
        letterSpacing: 0.3,
      );

  /// Tab label
  static TextStyle get tabLabel => GoogleFonts.tajawal(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        height: 1.43,
      );

  /// Bottom nav label
  static TextStyle get navLabel => GoogleFonts.tajawal(
        fontSize: 12,
        fontWeight: FontWeight.w500,
        height: 1.33,
      );

  /// Form input text
  static TextStyle get inputText => GoogleFonts.tajawal(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        color: AppColors.textPrimary,
        height: 1.5,
      );

  /// Form hint text
  static TextStyle get inputHint => GoogleFonts.tajawal(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        color: AppColors.textMuted,
        height: 1.5,
      );

  /// Form label
  static TextStyle get inputLabel => GoogleFonts.tajawal(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        color: AppColors.textSecondary,
        height: 1.43,
      );

  /// Error text
  static TextStyle get errorText => GoogleFonts.tajawal(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        color: AppColors.error,
        height: 1.33,
      );

  /// Link text
  static TextStyle get link => GoogleFonts.tajawal(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        color: AppColors.primary,
        height: 1.43,
        decoration: TextDecoration.underline,
      );

  /// Caption/helper text
  static TextStyle get caption => GoogleFonts.tajawal(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        color: AppColors.textTertiary,
        height: 1.33,
      );

  // ============================================
  // CONVENIENCE GETTERS FOR TEXT THEME STYLES
  // ============================================

  /// Headline styles
  static TextStyle get headlineLarge => textTheme.headlineLarge!;
  static TextStyle get headlineMedium => textTheme.headlineMedium!;
  static TextStyle get headlineSmall => textTheme.headlineSmall!;

  /// Title styles
  static TextStyle get titleLarge => textTheme.titleLarge!;
  static TextStyle get titleMedium => textTheme.titleMedium!;
  static TextStyle get titleSmall => textTheme.titleSmall!;

  /// Body styles
  static TextStyle get bodyLarge => textTheme.bodyLarge!;
  static TextStyle get bodyMedium => textTheme.bodyMedium!;
  static TextStyle get bodySmall => textTheme.bodySmall!;

  /// Label styles
  static TextStyle get labelLarge => textTheme.labelLarge!;
  static TextStyle get labelMedium => textTheme.labelMedium!;
  static TextStyle get labelSmall => textTheme.labelSmall!;
}
