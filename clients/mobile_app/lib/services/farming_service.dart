import 'package:dio/dio.dart';
import '../core/network/api_client.dart';

class FarmingService {
  final ApiClient _apiClient;

  FarmingService(this._apiClient);

  /// Mengambil data lahan milik petani (termasuk pagination)
  Future<Map<String, dynamic>> getLahan({int page = 1}) async {
    try {
      final response = await _apiClient.dio.get(
        '/lahan',
        queryParameters: {'page': page},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data lahan.';
      throw Exception(message);
    }
  }

  /// Mengambil total produksi hasil panen tahun ini
  Future<double> getTotalProduksi() async {
    try {
      final response = await _apiClient.dio.get('/total-produksi');
      final data = response.data['data'];
      if (data == null || data['total_produksi'] == null) {
        return 0.0;
      }
      return double.tryParse(data['total_produksi'].toString()) ?? 0.0;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat total produksi.';
      throw Exception(message);
    }
  }

  /// Mengambil daftar siklus tanam aktif milik petani
  Future<List<dynamic>> getMySiklusTanam() async {
    try {
      final response = await _apiClient.dio.get('/my-siklus-tanam');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat siklus tanam.';
      throw Exception(message);
    }
  }

  /// Mengambil data riwayat panen (dengan pagination)
  Future<Map<String, dynamic>> getRiwayatPanen({int page = 1}) async {
    try {
      final response = await _apiClient.dio.get(
        '/riwayat-panen',
        queryParameters: {'riwayat_page': page, 'per_page': 5},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat riwayat panen.';
      throw Exception(message);
    }
  }

  /// Mengambil data riwayat pemupukan (dengan pagination)
  Future<Map<String, dynamic>> getRiwayatPupuk({int page = 1}) async {
    try {
      final response = await _apiClient.dio.get(
        '/siklus-pupuk',
        queryParameters: {'pupuk_page': page, 'per_page': 5},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat riwayat pemupukan.';
      throw Exception(message);
    }
  }

  /// Mengambil daftar kecamatan
  Future<List<dynamic>> getKecamatan() async {
    try {
      final response = await _apiClient.dio.get('/kecamatan');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data kecamatan.';
      throw Exception(message);
    }
  }

  /// Mengambil daftar kelurahan
  Future<List<dynamic>> getKelurahan() async {
    try {
      final response = await _apiClient.dio.get('/kelurahan');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data kelurahan.';
      throw Exception(message);
    }
  }

  /// Mengambil daftar tipe lahan
  Future<List<dynamic>> getTipeLahan() async {
    try {
      final response = await _apiClient.dio.get('/tipe-lahan');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data tipe lahan.';
      throw Exception(message);
    }
  }

  /// Mengambil referensi spasial lahan (termasuk list petani penggarap)
  Future<List<dynamic>> getPetaniSpasial() async {
    try {
      final response = await _apiClient.dio.get('/spasial-lahan/referensi');
      final data = response.data['data'];
      return (data['petani'] ?? []) as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data petani penggarap.';
      throw Exception(message);
    }
  }

  /// Mengirimkan pengajuan lahan baru
  Future<Map<String, dynamic>> submitLahan(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.dio.post('/lahan', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal mengirim pengajuan lahan.';
      throw Exception(message);
    }
  }

  /// Mengambil data dropdown lahan terverifikasi milik petani
  Future<List<dynamic>> getLahanDropdown() async {
    try {
      final response = await _apiClient.dio.get('/lahan/dropdown');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data dropdown lahan.';
      throw Exception(message);
    }
  }

  /// Mengambil data bibit
  Future<List<dynamic>> getBibit() async {
    try {
      final response = await _apiClient.dio.get('/bibit');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data bibit.';
      throw Exception(message);
    }
  }

  /// Mengambil data jenis pupuk
  Future<List<dynamic>> getJenisPupuk() async {
    try {
      final response = await _apiClient.dio.get('/jenis-pupuk');
      return response.data['data'] as List<dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal memuat data jenis pupuk.';
      throw Exception(message);
    }
  }

  /// Mengirimkan laporan tanam baru
  Future<Map<String, dynamic>> submitLaporTanam(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.dio.post('/activities', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal menyimpan laporan tanam.';
      throw Exception(message);
    }
  }

  /// Menghapus siklus tanam berjalan
  Future<Map<String, dynamic>> deleteSiklusTanam(int id) async {
    try {
      final response = await _apiClient.dio.delete('/activities/$id');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal menghapus laporan tanam.';
      throw Exception(message);
    }
  }

  /// Mengirimkan laporan panen baru
  Future<Map<String, dynamic>> submitLaporPanen(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.dio.post('/lapor-panen', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal mengirim laporan hasil panen.';
      throw Exception(message);
    }
  }
}
