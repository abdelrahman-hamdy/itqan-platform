import 'package:flutter/material.dart';

/// Itqan Platform Color System
/// Matching the web app TailwindCSS design system
class AppColors {
  AppColors._();

  // ============================================
  // PRIMARY BRAND COLORS
  // ============================================

  /// Primary Sky Blue - Main brand color
  static const Color primary = Color(0xFF0EA5E9);
  static const Color primaryLight = Color(0xFF38BDF8);
  static const Color primaryDark = Color(0xFF0284C7);

  /// Primary color shades (Tailwind sky scale)
  static const Color primary50 = Color(0xFFF0F9FF);
  static const Color primary100 = Color(0xFFE0F2FE);
  static const Color primary200 = Color(0xFFBAE6FD);
  static const Color primary300 = Color(0xFF7DD3FC);
  static const Color primary400 = Color(0xFF38BDF8);
  static const Color primary500 = Color(0xFF0EA5E9);
  static const Color primary600 = Color(0xFF0284C7);
  static const Color primary700 = Color(0xFF0369A1);

  /// Secondary Dark Slate
  static const Color secondary = Color(0xFF0F172A);
  static const Color secondary50 = Color(0xFFF8FAFC);
  static const Color secondary100 = Color(0xFFF1F5F9);
  static const Color secondary200 = Color(0xFFE2E8F0);
  static const Color secondary300 = Color(0xFFCBD5E1);
  static const Color secondary400 = Color(0xFF94A3B8);
  static const Color secondary500 = Color(0xFF64748B);
  static const Color secondary600 = Color(0xFF475569);
  static const Color secondary700 = Color(0xFF334155);

  /// Accent Emerald Green
  static const Color accent = Color(0xFF10B981);
  static const Color accent50 = Color(0xFFECFDF5);
  static const Color accent100 = Color(0xFFD1FAE5);
  static const Color accent200 = Color(0xFFA7F3D0);
  static const Color accent300 = Color(0xFF6EE7B7);
  static const Color accent400 = Color(0xFF34D399);
  static const Color accent500 = Color(0xFF10B981);
  static const Color accent600 = Color(0xFF059669);
  static const Color accent700 = Color(0xFF047857);

  // ============================================
  // STATUS COLORS
  // ============================================

  /// Blue - Scheduled sessions
  static const Color scheduled = Color(0xFF3B82F6);
  static const Color scheduledLight = Color(0xFFDBEAFE);
  static const Color scheduledDark = Color(0xFF1D4ED8);

  /// Green - Ongoing/Live/Completed
  static const Color ongoing = Color(0xFF22C55E);
  static const Color ongoingLight = Color(0xFFDCFCE7);
  static const Color ongoingDark = Color(0xFF16A34A);
  static const Color completed = Color(0xFF22C55E);
  static const Color completedLight = Color(0xFFDCFCE7);

  /// Gray - Cancelled/Inactive
  static const Color cancelled = Color(0xFF6B7280);
  static const Color cancelledLight = Color(0xFFF3F4F6);
  static const Color cancelledDark = Color(0xFF4B5563);

  /// Amber - Warning/Unscheduled/Preparing
  static const Color warning = Color(0xFFF59E0B);
  static const Color warningLight = Color(0xFFFEF3C7);
  static const Color warningDark = Color(0xFFD97706);

  /// Red - Absent/Error
  static const Color error = Color(0xFFEF4444);
  static const Color errorLight = Color(0xFFFEE2E2);
  static const Color errorDark = Color(0xFFDC2626);
  static const Color absent = Color(0xFFEF4444);

  // ============================================
  // UI COLORS
  // ============================================

  /// Background colors
  static const Color background = Color(0xFFF8FAFC);
  static const Color backgroundWhite = Color(0xFFFFFFFF);
  static const Color backgroundDark = Color(0xFF0F172A);

  /// Surface colors (cards, dialogs)
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceVariant = Color(0xFFF1F5F9);

  /// Border colors
  static const Color border = Color(0xFFE2E8F0);
  static const Color borderLight = Color(0xFFF1F5F9);
  static const Color borderDark = Color(0xFFCBD5E1);

  /// Text colors
  static const Color textPrimary = Color(0xFF0F172A);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color textTertiary = Color(0xFF94A3B8);
  static const Color textLight = Color(0xFFFFFFFF);
  static const Color textMuted = Color(0xFF9CA3AF);

  /// Divider
  static const Color divider = Color(0xFFE5E7EB);

  // ============================================
  // GRADIENT COLORS
  // ============================================

  /// Education gradient (indigo-blue-indigo)
  static const Color gradientEducationStart = Color(0xFF4F46E5);
  static const Color gradientEducationMiddle = Color(0xFF2563EB);
  static const Color gradientEducationEnd = Color(0xFF4338CA);

  /// Primary gradient (sky)
  static const Color gradientPrimaryStart = Color(0xFF0EA5E9);
  static const Color gradientPrimaryEnd = Color(0xFF0284C7);

  /// Success gradient (emerald)
  static const Color gradientSuccessStart = Color(0xFF10B981);
  static const Color gradientSuccessEnd = Color(0xFF059669);

  /// Violet gradient
  static const Color gradientVioletStart = Color(0xFF8B5CF6);
  static const Color gradientVioletEnd = Color(0xFF7C3AED);

  // ============================================
  // SESSION TYPE COLORS
  // ============================================

  /// Quran session colors
  static const Color quran = Color(0xFF10B981);
  static const Color quranLight = Color(0xFFECFDF5);

  /// Academic session colors
  static const Color academic = Color(0xFF3B82F6);
  static const Color academicLight = Color(0xFFDBEAFE);

  /// Interactive course colors
  static const Color interactive = Color(0xFF8B5CF6);
  static const Color interactiveLight = Color(0xFFEDE9FE);

  // ============================================
  // GRADIENTS
  // ============================================

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primary500, primary600],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient educationGradient = LinearGradient(
    colors: [gradientEducationStart, gradientEducationMiddle, gradientEducationEnd],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient successGradient = LinearGradient(
    colors: [gradientSuccessStart, gradientSuccessEnd],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient violetGradient = LinearGradient(
    colors: [gradientVioletStart, gradientVioletEnd],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient quranGradient = LinearGradient(
    colors: [accent400, accent600],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient academicGradient = LinearGradient(
    colors: [scheduled, scheduledDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient interactiveGradient = LinearGradient(
    colors: [gradientVioletStart, gradientVioletEnd],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient errorGradient = LinearGradient(
    colors: [error, errorDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // ============================================
  // COLOR ALIASES FOR TYPE COLORS
  // ============================================

  /// Aliases for session type colors
  static const Color quranColor = quran;
  static const Color academicColor = academic;
  static const Color interactiveColor = interactive;
}
