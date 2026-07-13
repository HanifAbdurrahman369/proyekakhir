class ApiEndpoints {
  // Bisa dioverride saat build:
  // flutter build apk --dart-define=API_BASE_URL=http://IP_BACKEND:8003/api
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.sigpala.my.id/api',
  );

  // Auth & User Endpoints
  static const String login = '/login';
  static const String register = '/register';
  static const String profile = '/auth/profile';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';
}
