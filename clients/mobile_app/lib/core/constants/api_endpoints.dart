class ApiEndpoints {
  // Menggunakan 10.0.2.2 untuk mengakses localhost komputer host dari Emulator Android Studio
  static const String baseUrl = 'http://10.0.2.2:8003/api';
  
  // Jika menggunakan koneksi Wi-Fi langsung (sesuaikan dengan IP lokal saat ini):
  // static const String baseUrl = 'http://10.191.194.225:8003/api';
  // static const String baseUrl = 'http://192.168.1.6:8003/api';

  // Auth & User Endpoints
  
  static const String login = '/login';
  static const String register = '/user/register'; // Melalui dynamic gateway proxy ke User Service
  static const String profile = '/auth/profile';  // Melalui gateway proxy ke Auth Service (dengan middleware JWT)
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';

}
