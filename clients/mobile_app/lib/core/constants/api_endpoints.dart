class ApiEndpoints {
  // Gunakan IP default Android Emulator (10.0.2.2) untuk terhubung ke localhost PC.
  // Jika menggunakan device fisik atau emulator lain, ubah sesuai dengan IP PC Anda (misal: 192.168.1.x)
  static const String baseUrl = 'http://192.168.1.7:8003/api';

  // Auth & User Endpoints
  
  static const String login = '/login';
  static const String register = '/user/register'; // Melalui dynamic gateway proxy ke User Service
  static const String profile = '/auth/profile';  // Melalui gateway proxy ke Auth Service (dengan middleware JWT)
}
