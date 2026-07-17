import 'package:dio/dio.dart';
import '../core/constants/api_endpoints.dart';
import '../core/network/api_client.dart';
import '../models/user.dart';

class AuthService {
  final ApiClient _apiClient;

  AuthService(this._apiClient);

  /// Mengirim request login ke backend
  Future<Map<String, dynamic>> login(String loginId, String password) async {
    try {
      final response = await _apiClient.dio.post(
        ApiEndpoints.login,
        data: {'login_id': loginId, 'password': password},
      );

      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message =
          e.response?.data['message'] ??
          'Gagal melakukan login. Silakan periksa jaringan Anda.';
      throw Exception(message);
    }
  }

  /// Mengirim request registrasi baru (sebagai petani) ke backend
  Future<Map<String, dynamic>> register({
    required String nik,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String jenisKelompok,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        ApiEndpoints.register,
        data: {
          'nik': nik,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'jenis_kelompok': jenisKelompok,
        },
      );

      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final errorData = e.response?.data;
      String errorMsg = 'Registrasi gagal.';

      if (errorData != null && errorData['errors'] != null) {
        final errors = errorData['errors'] as Map<String, dynamic>;
        errorMsg = errors.values.map((e) => (e as List).join(', ')).join('\n');
      } else if (errorData != null && errorData['message'] != null) {
        errorMsg = errorData['message'];
      }

      throw Exception(errorMsg);
    }
  }

  /// Mengambil data profil user yang aktif menggunakan token
  Future<User> getProfile() async {
    try {
      final response = await _apiClient.dio.get(ApiEndpoints.profile);
      final userData = response.data['user'];
      return User.fromJson(userData as Map<String, dynamic>);
    } on DioException catch (e) {
      final message =
          e.response?.data['message'] ?? 'Gagal memuat profil pengguna.';
      throw Exception(message);
    }
  }

  /// Memperbarui data profil user aktif tanpa mengubah password atau role.
  Future<User> updateProfile({
    required String namaLengkap,
    required String email,
    String? noHp,
    String? alamat,
    int? wilayahKecamatanId,
    int? wilayahKelurahanId,
  }) async {
    try {
      final response = await _apiClient.dio.put(
        ApiEndpoints.profile,
        data: {
          'nama_lengkap': namaLengkap,
          'email': email,
          'no_hp': noHp,
          'alamat': alamat,
          'wilayah_kecamatan_id': wilayahKecamatanId,
          'wilayah_kelurahan_id': wilayahKelurahanId,
        },
      );
      final userData = response.data['user'];
      return User.fromJson(userData as Map<String, dynamic>);
    } on DioException catch (e) {
      final errorData = e.response?.data;
      final message = errorData is Map && errorData['message'] != null
          ? errorData['message'].toString()
          : 'Gagal memperbarui profil pengguna.';
      throw Exception(message);
    }
  }

  /// Mengirim request reset password (link email) ke backend
  Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final response = await _apiClient.dio.post(
        ApiEndpoints.forgotPassword,
        data: {'email': email},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message =
          e.response?.data['message'] ??
          'Gagal mengirim link reset password. Silakan periksa jaringan Anda.';
      throw Exception(message);
    }
  }

  /// Mengirim request reset password baru dengan token ke backend
  Future<Map<String, dynamic>> resetPassword({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        ApiEndpoints.resetPassword,
        data: {
          'email': email,
          'token': token,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message =
          e.response?.data['message'] ??
          'Gagal mengubah password. Silakan periksa token Anda.';
      throw Exception(message);
    }
  }

  Future<Map<String, dynamic>> getUsers({int page = 1}) async {
    try {
      final response = await _apiClient.dio.get(
        '/users',
        queryParameters: {'page': page},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat pengguna.');
    }
  }

  Future<Map<String, dynamic>> getKomunitas({int page = 1}) async {
    try {
      final response = await _apiClient.dio.get(
        '/komunitas',
        queryParameters: {'page': page},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat komunitas.');
    }
  }

  Future<Map<String, dynamic>> createKomunitas(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _apiClient.dio.post('/komunitas', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(
        e.response?.data['message'] ?? 'Gagal menambah komunitas.',
      );
    }
  }

  Future<Map<String, dynamic>> updateKomunitas(
    int id,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _apiClient.dio.put('/komunitas/$id', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(
        e.response?.data['message'] ?? 'Gagal mengubah komunitas.',
      );
    }
  }

  Future<Map<String, dynamic>> deleteKomunitas(int id) async {
    try {
      final response = await _apiClient.dio.delete('/komunitas/$id');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(
        e.response?.data['message'] ?? 'Gagal menghapus komunitas.',
      );
    }
  }
}
