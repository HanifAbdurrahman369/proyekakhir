import 'package:dio/dio.dart';
import '../core/network/api_client.dart';

class FarmingService {
  final ApiClient _apiClient;

  FarmingService(this._apiClient);

  Future<Map<String, dynamic>> getLahan({int page = 1, int? perPage}) async {
    try {
      final queryParams = <String, dynamic>{'page': page};
      if (perPage != null) {
        queryParams['per_page'] = perPage;
      }
      final response = await _apiClient.dio.get(
        '/lahan',
        queryParameters: queryParams,
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

  /// Mengirim perbaikan pengajuan lahan (resubmit)
  Future<Map<String, dynamic>> resubmitLahan(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.dio.put('/lahan/$id/resubmit', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal mengirim perbaikan lahan.';
      throw Exception(message);
    }
  }

  /// Mengirim perbaikan laporan hasil panen
  Future<Map<String, dynamic>> updateLaporPanen(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.dio.put('/lapor-panen/$id', data: data);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final message = e.response?.data['message'] ?? 'Gagal mengirim perbaikan panen.';
      throw Exception(message);
    }
  }

  /// Pejabat: Mengambil total produksi seluruh daerah
  Future<double> getProduksiPejabat() async {
    try {
      final response = await _apiClient.dio.get('/produksi-pejabat');
      final data = response.data['data'];
      if (data == null || data['produksi_pejabat'] == null) return 0.0;
      return double.tryParse(data['produksi_pejabat'].toString()) ?? 0.0;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat total produksi pejabat.');
    }
  }

  /// Pejabat: Mengambil total luas lahan aktif
  Future<double> getTotalLahanPejabat() async {
    try {
      final response = await _apiClient.dio.get('/total-lahan');
      final data = response.data['data'];
      if (data == null || data['total_lahan'] == null) return 0.0;
      return double.tryParse(data['total_lahan'].toString()) ?? 0.0;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat total lahan pejabat.');
    }
  }

  /// Pejabat: Mengambil tren produksi bulanan
  Future<Map<int, double>> getProduksiBulananPejabat() async {
    try {
      final response = await _apiClient.dio.get('/produksi-bulanan');
      final rawData = response.data['data'];
      final Map<int, double> result = {};
      if (rawData is Map) {
        rawData.forEach((key, value) {
          final month = int.tryParse(key.toString()) ?? 0;
          final val = double.tryParse(value.toString()) ?? 0.0;
          if (month > 0) result[month] = val;
        });
      }
      return result;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat tren bulanan pejabat.');
    }
  }

  /// Pejabat: Mengambil detail produksi per kecamatan
  Future<List<dynamic>> getProduksiKecamatanPejabat() async {
    try {
      final response = await _apiClient.dio.get('/produksi-kecamatan');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat produksi per kecamatan.');
    }
  }

  /// Pejabat: Mengambil detail lahan per kecamatan
  Future<List<dynamic>> getLahanKecamatanPejabat() async {
    try {
      final response = await _apiClient.dio.get('/lahan-kecamatan');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat luas lahan per kecamatan.');
    }
  }

  /// Pejabat: Mengambil batas wilayah kabupaten Barito Kuala (GeoJSON)
  Future<Map<String, dynamic>> getBatasWilayah() async {
    try {
      final response = await _apiClient.dio.get('/batas-wilayah');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat batas wilayah kabupaten.');
    }
  }

  /// Pejabat: Mengambil batas wilayah kecamatan (GeoJSON)
  Future<Map<String, dynamic>> getBatasKecamatan() async {
    try {
      final response = await _apiClient.dio.get('/batas-kecamatan');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat batas wilayah kecamatan.');
    }
  }

  /// Pejabat: Mengambil data map sebaran lahan sawah (GeoJSON)
  Future<Map<String, dynamic>> getMapLahan() async {
    try {
      final response = await _apiClient.dio.get('/map-lahan');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat sebaran lahan sawah.');
    }
  }

  /// Pejabat: Mengambil data statistik eksekutif lengkap (produksi daerah)
  Future<Map<String, dynamic>> getStatistik() async {
    try {
      final response = await _apiClient.dio.get('/statistik');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat data statistik daerah.');
    }
  }

  /// Petugas: Mengambil daftar pengajuan lahan pending
  Future<List<dynamic>> getPendingLahan() async {
    try {
      final response = await _apiClient.dio.get('/lahan/pending');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat pengajuan lahan pending.');
    }
  }

  /// Petugas: Mengambil daftar laporan hasil panen pending
  Future<List<dynamic>> getPendingPanen() async {
    try {
      final response = await _apiClient.dio.get('/panen/pending');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat laporan panen pending.');
    }
  }

  /// Petugas: Menyetujui pengajuan lahan baru
  Future<Map<String, dynamic>> approveLahan(int id, int? petaniId) async {
    try {
      final payload = <String, dynamic>{};
      if (petaniId != null) {
        payload['petani_id'] = petaniId;
      }
      final response = await _apiClient.dio.post('/lahan/$id/approve', data: payload);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal menyetujui pengajuan lahan.');
    }
  }

  /// Petugas: Menolak pengajuan lahan baru
  Future<Map<String, dynamic>> rejectLahan(int id, String alasanPenolakan) async {
    try {
      final response = await _apiClient.dio.post(
        '/lahan/$id/reject',
        data: {'alasan_penolakan': alasanPenolakan},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal menolak pengajuan lahan.');
    }
  }

  /// Petugas: Memverifikasi laporan hasil panen (DITOLAK / DITERIMA)
  Future<Map<String, dynamic>> verifikasiPanen(int id, String status, String? catatan) async {
    try {
      final response = await _apiClient.dio.post(
        '/panen/$id/verifikasi',
        data: {
          'aksi': status,
          'catatan_verifikasi': catatan ?? '',
        },
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memverifikasi laporan panen.');
    }
  }

  /// Petugas: Mengambil daftar spasial lahan sawah
  Future<List<dynamic>> getSpasialLahan() async {
    try {
      final response = await _apiClient.dio.get(
        '/spasial-lahan',
        queryParameters: {'status': 'ALL', 'kabupaten': 'batola'},
      );
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat data spasial lahan.');
    }
  }

  /// Petugas: Menyimpan/mengubah data spasial lahan
  Future<Map<String, dynamic>> updateSpasialLahan(int id, Map<String, dynamic> payload) async {
    try {
      final response = await _apiClient.dio.put('/spasial-lahan/$id', data: payload);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memperbarui data spasial.');
    }
  }

  /// Petugas: Mengambil daftar lahan sawah terverifikasi (DITERIMA)
  Future<List<dynamic>> getAcceptedLahan() async {
    try {
      final response = await _apiClient.dio.get('/lahan/accepted');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat lahan terverifikasi.');
    }
  }

  /// Petugas: Mengambil riwayat monitoring parameter lingkungan
  Future<List<dynamic>> getMonitoring() async {
    try {
      final response = await _apiClient.dio.get('/monitoring');
      return response.data['data'] as List<dynamic>? ?? [];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal memuat parameter lingkungan.');
    }
  }

  /// Petugas: Menyimpan parameter lingkungan baru
  Future<Map<String, dynamic>> saveMonitoring(Map<String, dynamic> payload) async {
    try {
      final response = await _apiClient.dio.post('/monitoring', data: payload);
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal menyimpan parameter lingkungan.');
    }
  }
}

