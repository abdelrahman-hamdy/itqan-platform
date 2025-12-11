import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Locale Cubit for managing app language
class LocaleCubit extends Cubit<Locale> {
  LocaleCubit() : super(const Locale('ar')); // Default to Arabic

  /// Supported locales
  static const List<Locale> supportedLocales = [
    Locale('ar'), // Arabic (primary)
    Locale('en'), // English
  ];

  /// Switch to Arabic
  void setArabic() {
    emit(const Locale('ar'));
  }

  /// Switch to English
  void setEnglish() {
    emit(const Locale('en'));
  }

  /// Toggle between Arabic and English
  void toggleLocale() {
    if (state.languageCode == 'ar') {
      emit(const Locale('en'));
    } else {
      emit(const Locale('ar'));
    }
  }

  /// Set locale by language code
  void setLocale(String languageCode) {
    if (languageCode == 'ar' || languageCode == 'en') {
      emit(Locale(languageCode));
    }
  }

  /// Check if current locale is RTL
  bool get isRtl => state.languageCode == 'ar';

  /// Get text direction based on current locale
  TextDirection get textDirection =>
      isRtl ? TextDirection.rtl : TextDirection.ltr;
}
