import 'package:flutter/material.dart';

/// Itqan Platform Spacing System
/// Based on 4pt grid (matching Tailwind's spacing scale)
class AppSpacing {
  AppSpacing._();

  // ============================================
  // BASE SPACING VALUES
  // ============================================

  /// 0px
  static const double zero = 0;

  /// 2px - Extra extra small
  static const double xxs = 2;

  /// 4px - Extra small
  static const double xs = 4;

  /// 8px - Small
  static const double sm = 8;

  /// 12px - Medium
  static const double md = 12;

  /// 16px - Base/Default
  static const double base = 16;

  /// 20px - Large
  static const double lg = 20;

  /// 24px - Extra large
  static const double xl = 24;

  /// 32px - 2X large
  static const double xxl = 32;

  /// 40px - 3X large
  static const double xxxl = 40;

  /// 48px - 4X large
  static const double xxxxl = 48;

  /// 64px - 5X large
  static const double xxxxxl = 64;

  // ============================================
  // SEMANTIC SPACING
  // ============================================

  /// Screen horizontal padding
  static const double screenPadding = 16;

  /// Card internal padding
  static const double cardPadding = 16;

  /// Card internal padding (small)
  static const double cardPaddingSmall = 12;

  /// Section spacing (between major sections)
  static const double sectionSpacing = 24;

  /// Item spacing (between list items)
  static const double itemSpacing = 12;

  /// Element spacing (between elements within a component)
  static const double elementSpacing = 8;

  /// Icon text gap
  static const double iconTextGap = 8;

  /// Button padding horizontal
  static const double buttonPaddingH = 16;

  /// Button padding vertical
  static const double buttonPaddingV = 12;

  /// Input padding horizontal
  static const double inputPaddingH = 16;

  /// Input padding vertical
  static const double inputPaddingV = 14;

  /// Badge padding horizontal
  static const double badgePaddingH = 10;

  /// Badge padding vertical
  static const double badgePaddingV = 4;

  /// Bottom nav height
  static const double bottomNavHeight = 80;

  /// App bar height
  static const double appBarHeight = 56;

  /// Touch target minimum size (accessibility)
  static const double touchTarget = 48;

  // ============================================
  // BORDER RADIUS
  // ============================================

  /// 4px - Extra small radius
  static const double radiusXs = 4;

  /// 8px - Small radius (buttons, badges)
  static const double radiusSm = 8;

  /// 12px - Medium radius (cards, inputs)
  static const double radiusMd = 12;

  /// 16px - Large radius (modals, sheets)
  static const double radiusLg = 16;

  /// 20px - Extra large radius
  static const double radiusXl = 20;

  /// 24px - 2X large radius (bottom sheets)
  static const double radiusXxl = 24;

  /// Full circle
  static const double radiusFull = 999;

  // ============================================
  // BORDER RADIUS PRESETS
  // ============================================

  static const BorderRadius borderRadiusXs = BorderRadius.all(Radius.circular(radiusXs));
  static const BorderRadius borderRadiusSm = BorderRadius.all(Radius.circular(radiusSm));
  static const BorderRadius borderRadiusMd = BorderRadius.all(Radius.circular(radiusMd));
  static const BorderRadius borderRadiusLg = BorderRadius.all(Radius.circular(radiusLg));
  static const BorderRadius borderRadiusXl = BorderRadius.all(Radius.circular(radiusXl));
  static const BorderRadius borderRadiusXxl = BorderRadius.all(Radius.circular(radiusXxl));
  static const BorderRadius borderRadiusFull = BorderRadius.all(Radius.circular(radiusFull));

  /// Top corners only (for bottom sheets)
  static const BorderRadius borderRadiusTopLg = BorderRadius.only(
    topLeft: Radius.circular(radiusLg),
    topRight: Radius.circular(radiusLg),
  );

  static const BorderRadius borderRadiusTopXxl = BorderRadius.only(
    topLeft: Radius.circular(radiusXxl),
    topRight: Radius.circular(radiusXxl),
  );

  // ============================================
  // EDGE INSETS PRESETS
  // ============================================

  /// Screen padding (horizontal only)
  static const EdgeInsets paddingScreen = EdgeInsets.symmetric(horizontal: screenPadding);

  /// Screen padding (all sides)
  static const EdgeInsets paddingScreenAll = EdgeInsets.all(screenPadding);

  /// Card padding
  static const EdgeInsets paddingCard = EdgeInsets.all(cardPadding);

  /// Card padding small
  static const EdgeInsets paddingCardSmall = EdgeInsets.all(cardPaddingSmall);

  /// Horizontal padding small
  static const EdgeInsets paddingHorizontalSm = EdgeInsets.symmetric(horizontal: sm);

  /// Horizontal padding medium
  static const EdgeInsets paddingHorizontalMd = EdgeInsets.symmetric(horizontal: md);

  /// Horizontal padding base
  static const EdgeInsets paddingHorizontalBase = EdgeInsets.symmetric(horizontal: base);

  /// Vertical padding small
  static const EdgeInsets paddingVerticalSm = EdgeInsets.symmetric(vertical: sm);

  /// Vertical padding medium
  static const EdgeInsets paddingVerticalMd = EdgeInsets.symmetric(vertical: md);

  /// Vertical padding base
  static const EdgeInsets paddingVerticalBase = EdgeInsets.symmetric(vertical: base);

  /// Button padding
  static const EdgeInsets paddingButton = EdgeInsets.symmetric(
    horizontal: buttonPaddingH,
    vertical: buttonPaddingV,
  );

  /// Button padding small
  static const EdgeInsets paddingButtonSmall = EdgeInsets.symmetric(
    horizontal: md,
    vertical: sm,
  );

  /// Input padding
  static const EdgeInsets paddingInput = EdgeInsets.symmetric(
    horizontal: inputPaddingH,
    vertical: inputPaddingV,
  );

  /// Badge padding
  static const EdgeInsets paddingBadge = EdgeInsets.symmetric(
    horizontal: badgePaddingH,
    vertical: badgePaddingV,
  );

  // ============================================
  // SHADOWS
  // ============================================

  /// Subtle shadow (cards)
  static const List<BoxShadow> shadowSm = [
    BoxShadow(
      color: Color(0x0A000000),
      blurRadius: 4,
      offset: Offset(0, 1),
    ),
  ];

  /// Medium shadow (hover states)
  static const List<BoxShadow> shadowMd = [
    BoxShadow(
      color: Color(0x0F000000),
      blurRadius: 8,
      offset: Offset(0, 4),
    ),
  ];

  /// Large shadow (modals, floating elements)
  static const List<BoxShadow> shadowLg = [
    BoxShadow(
      color: Color(0x14000000),
      blurRadius: 16,
      offset: Offset(0, 8),
    ),
  ];

  /// Extra large shadow (overlays)
  static const List<BoxShadow> shadowXl = [
    BoxShadow(
      color: Color(0x1A000000),
      blurRadius: 24,
      offset: Offset(0, 12),
    ),
  ];

  /// Colored shadow - Primary
  static List<BoxShadow> shadowPrimary = [
    BoxShadow(
      color: const Color(0xFF0EA5E9).withOpacity(0.3),
      blurRadius: 20,
      offset: const Offset(0, 8),
    ),
  ];

  /// Colored shadow - Success
  static List<BoxShadow> shadowSuccess = [
    BoxShadow(
      color: const Color(0xFF10B981).withOpacity(0.3),
      blurRadius: 20,
      offset: const Offset(0, 8),
    ),
  ];

  /// Colored shadow - Violet
  static List<BoxShadow> shadowViolet = [
    BoxShadow(
      color: const Color(0xFF8B5CF6).withOpacity(0.3),
      blurRadius: 20,
      offset: const Offset(0, 8),
    ),
  ];

  // ============================================
  // DURATIONS
  // ============================================

  /// Quick animations (button press, toggle)
  static const Duration durationQuick = Duration(milliseconds: 150);

  /// Standard animations (page transitions, modals)
  static const Duration durationStandard = Duration(milliseconds: 300);

  /// Slow animations (complex transitions)
  static const Duration durationSlow = Duration(milliseconds: 500);

  // ============================================
  // CURVES
  // ============================================

  /// Standard easing
  static const Curve curveStandard = Curves.easeInOut;

  /// Decelerate (entering elements)
  static const Curve curveDecelerate = Curves.decelerate;

  /// Accelerate (exiting elements)
  static const Curve curveAccelerate = Curves.easeIn;

  /// Bounce (playful interactions)
  static const Curve curveBounce = Curves.elasticOut;
}

/// AppShadows - Convenience class for shadow presets
/// This allows using AppShadows.sm instead of AppSpacing.shadowSm
class AppShadows {
  AppShadows._();

  static const List<BoxShadow> sm = AppSpacing.shadowSm;
  static const List<BoxShadow> md = AppSpacing.shadowMd;
  static const List<BoxShadow> lg = AppSpacing.shadowLg;
  static const List<BoxShadow> xl = AppSpacing.shadowXl;
  static List<BoxShadow> get primary => AppSpacing.shadowPrimary;
  static List<BoxShadow> get success => AppSpacing.shadowSuccess;
  static List<BoxShadow> get violet => AppSpacing.shadowViolet;
}
