import 'package:flutter/foundation.dart';

class ApiEndpoints {
  static const String _configuredBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );

  /// `flutter run` pada emulator Android terhubung ke service lokal Windows.
  /// Build release tetap terhubung ke API produksi yang sama dengan website.
  static String get baseUrl {
    if (_configuredBaseUrl.isNotEmpty) {
      return _configuredBaseUrl;
    }

    return kReleaseMode
        ? 'https://api.sigpala.my.id/api'
        : 'http://10.0.2.2:8003/api';
  }

  // Auth & User Endpoints
  static const String login = '/login';
  static const String register = '/register';
  static const String profile = '/auth/profile';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';
}
