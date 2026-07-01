class ApiEndpoints {
  // Jika menggunakan koneksi Wi-Fi langsung (pastikan firewall port 8003 terbuka):
  static const String baseUrl = 'http://192.168.1.6:8003/api';
  
  // Jika menggunakan USB debugging dengan adb reverse:
  // static const String baseUrl = 'http://127.0.0.1:8003/api';

  // Auth & User Endpoints
  
  static const String login = '/login';
  static const String register = '/user/register'; // Melalui dynamic gateway proxy ke User Service
  static const String profile = '/auth/profile';  // Melalui gateway proxy ke Auth Service (dengan middleware JWT)
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';

}
