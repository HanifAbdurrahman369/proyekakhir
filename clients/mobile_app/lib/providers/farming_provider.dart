import 'package:flutter/material.dart';
import '../services/farming_service.dart';

class FarmingProvider extends ChangeNotifier {
  final FarmingService _farmingService;

  bool _isLoading = false;
  Map<String, dynamic> _lahanData = {
    'data': [],
    'total': 0,
    'current_page': 1,
    'last_page': 1,
  };
  double _totalProduksi = 0.0;
  List<dynamic> _mySiklusTanam = [];
  String? _errorMessage;

  // State untuk Riwayat Aktivitas secara independen agar tidak mengganggu dashboard
  Map<String, dynamic> _riwayatLahanData = {
    'data': [],
    'total': 0,
    'current_page': 1,
    'last_page': 1,
  };
  Map<String, dynamic> _riwayatPanenData = {
    'data': [],
    'total': 0,
    'current_page': 1,
    'last_page': 1,
  };
  Map<String, dynamic> _riwayatPupukData = {
    'data': [],
    'total': 0,
    'current_page': 1,
    'last_page': 1,
  };

  bool _isRiwayatLahanLoading = false;
  bool _isRiwayatPanenLoading = false;
  bool _isRiwayatPupukLoading = false;

  // State Pejabat
  double _produksiPejabat = 0.0;
  double _totalLahanPejabat = 0.0;
  Map<int, double> _produksiBulananPejabat = {};
  List<dynamic> _produksiKecamatanPejabat = [];
  List<dynamic> _lahanKecamatanPejabat = [];
  bool _isPejabatLoading = false;

  Map<String, dynamic>? _kabupatenBoundary;
  Map<String, dynamic>? _kecamatanBoundaries;
  Map<String, dynamic>? _lahanMapFeatures;
  bool _isMapLoading = false;

  Map<String, dynamic>? _statistikData;
  bool _isStatistikLoading = false;

  // State Petugas (Nurul)
  int _pendingLahanCount = 0;
  int _pendingPanenCount = 0;
  int _totalPendingCount = 0;
  List<dynamic> _pendingLahanList = [];
  List<dynamic> _pendingPanenList = [];
  List<dynamic> _spasialLahanList = [];
  List<dynamic> _acceptedLahanList = [];
  List<dynamic> _monitoringList = [];
  bool _isPetugasLoading = false;

  // State Petugas (Hanif)
  List<dynamic> _petugasPendingLahan = [];
  List<dynamic> _petugasPendingPanen = [];
  List<dynamic> _petugasNotifikasi = [];
  Map<String, dynamic> _petugasPendingCounts = {
    'pending_lahan': 0,
    'pending_panen': 0,
    'total_pending': 0,
  };
  int _petugasUnreadCount = 0;
  bool _isPetugasActionLoading = false;

  Map<String, dynamic> _petugasSpasialReferensi = {
    'petani': [],
    'kecamatan': [],
    'kelurahan': [],
    'tipe_lahan': [],
  };
  List<dynamic> _petugasSpasialRows = [];
  Map<String, dynamic> _petugasSpasialSummary = {
    'total': 0,
    'sudah_dipetakan': 0,
    'belum_dipetakan': 0,
    'persentase_lengkap': 0,
  };
  bool _isPetugasSpasialLoading = false;


  FarmingProvider(this._farmingService);

  double get produksiPejabat => _produksiPejabat;
  double get totalLahanPejabat => _totalLahanPejabat;
  Map<int, double> get produksiBulananPejabat => _produksiBulananPejabat;
  List<dynamic> get produksiKecamatanPejabat => _produksiKecamatanPejabat;
  List<dynamic> get lahanKecamatanPejabat => _lahanKecamatanPejabat;
  bool get isPejabatLoading => _isPejabatLoading;

  Map<String, dynamic>? get kabupatenBoundary => _kabupatenBoundary;
  Map<String, dynamic>? get kecamatanBoundaries => _kecamatanBoundaries;
  Map<String, dynamic>? get lahanMapFeatures => _lahanMapFeatures;
  bool get isMapLoading => _isMapLoading;

  Map<String, dynamic>? get statistikData => _statistikData;
  bool get isStatistikLoading => _isStatistikLoading;

  // Getters Petugas (Nurul)
  int get pendingLahanCount => _pendingLahanCount;
  int get pendingPanenCount => _pendingPanenCount;
  int get totalPendingCount => _totalPendingCount;
  List<dynamic> get pendingLahanList => _pendingLahanList;
  List<dynamic> get pendingPanenList => _pendingPanenList;
  List<dynamic> get spasialLahanList => _spasialLahanList;
  List<dynamic> get acceptedLahanList => _acceptedLahanList;
  List<dynamic> get monitoringList => _monitoringList;
  bool get isPetugasLoading => _isPetugasLoading;

  // Getters Petugas (Hanif)
  List<dynamic> get petugasPendingLahan => _petugasPendingLahan;
  List<dynamic> get petugasPendingPanen => _petugasPendingPanen;
  List<dynamic> get petugasNotifikasi => _petugasNotifikasi;
  Map<String, dynamic> get petugasPendingCounts => _petugasPendingCounts;
  int get petugasUnreadCount => _petugasUnreadCount;
  bool get isPetugasActionLoading => _isPetugasActionLoading;
  Map<String, dynamic> get petugasSpasialReferensi => _petugasSpasialReferensi;
  List<dynamic> get petugasSpasialRows => _petugasSpasialRows;
  Map<String, dynamic> get petugasSpasialSummary => _petugasSpasialSummary;
  bool get isPetugasSpasialLoading => _isPetugasSpasialLoading;


  bool get isLoading => _isLoading;
  Map<String, dynamic> get lahanData => _lahanData;
  double get totalProduksi => _totalProduksi;
  List<dynamic> get mySiklusTanam => _mySiklusTanam;
  String? get errorMessage => _errorMessage;

  // Getter Riwayat Aktivitas
  Map<String, dynamic> get riwayatLahanData => _riwayatLahanData;
  Map<String, dynamic> get riwayatPanenData => _riwayatPanenData;
  Map<String, dynamic> get riwayatPupukData => _riwayatPupukData;

  bool get isRiwayatLahanLoading => _isRiwayatLahanLoading;
  bool get isRiwayatPanenLoading => _isRiwayatPanenLoading;
  bool get isRiwayatPupukLoading => _isRiwayatPupukLoading;

  /// Fetch all required dashboard data in one go (lahan, total produksi, active cycles)
  Future<void> fetchDashboardData({int lahanPage = 1}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final lahanResult = await _farmingService.getLahan(page: lahanPage);
      _lahanData = lahanResult['data'] as Map<String, dynamic>;

      _totalProduksi = await _farmingService.getTotalProduksi();
      _mySiklusTanam = await _farmingService.getMySiklusTanam();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Memuat riwayat pengajuan lahan sawah dengan pagination
  Future<void> fetchRiwayatLahan({int page = 1}) async {
    _isRiwayatLahanLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _farmingService.getLahan(page: page, perPage: 4);
      _riwayatLahanData = result['data'] as Map<String, dynamic>;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isRiwayatLahanLoading = false;
      notifyListeners();
    }
  }

  /// Memuat riwayat panen padi dengan pagination
  Future<void> fetchRiwayatPanen({int page = 1}) async {
    _isRiwayatPanenLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _farmingService.getRiwayatPanen(page: page);
      _riwayatPanenData = result['data'] as Map<String, dynamic>;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isRiwayatPanenLoading = false;
      notifyListeners();
    }
  }

  // List metadata referensi untuk Tambah Lahan dan Lapor Tanam
  List<dynamic> _kecamatanList = [];
  List<dynamic> _kelurahanList = [];
  List<dynamic> _tipeLahanList = [];
  List<dynamic> _petaniSpasialList = [];
  List<dynamic> _lahanDropdownList = [];
  List<dynamic> _bibitList = [];
  List<dynamic> _pupukList = [];

  // Getter metadata
  List<dynamic> get kecamatanList => _kecamatanList;
  List<dynamic> get kelurahanList => _kelurahanList;
  List<dynamic> get tipeLahanList => _tipeLahanList;
  List<dynamic> get petaniSpasialList => _petaniSpasialList;
  List<dynamic> get lahanDropdownList => _lahanDropdownList;
  List<dynamic> get bibitList => _bibitList;
  List<dynamic> get pupukList => _pupukList;

  /// Memuat riwayat pemberian pupuk dengan pagination
  Future<void> fetchRiwayatPupuk({int page = 1}) async {
    _isRiwayatPupukLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _farmingService.getRiwayatPupuk(page: page);
      _riwayatPupukData = result['data'] as Map<String, dynamic>;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isRiwayatPupukLoading = false;
      notifyListeners();
    }
  }

  /// Memuat semua metadata/referensi yang diperlukan untuk Tambah Lahan Baru
  Future<void> fetchLahanMetadata() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getKecamatan().catchError((e) {
          debugPrint('DEBUG ERROR getKecamatan: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Kecamatan: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getKelurahan().catchError((e) {
          debugPrint('DEBUG ERROR getKelurahan: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Kelurahan: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getTipeLahan().catchError((e) {
          debugPrint('DEBUG ERROR getTipeLahan: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Tipe Lahan: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getPetaniSpasial().catchError((e) {
          debugPrint('DEBUG ERROR getPetaniSpasial: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Penggarap: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
      ]);

      _kecamatanList = results[0];
      _kelurahanList = results[1];
      _tipeLahanList = results[2];
      _petaniSpasialList = results[3];

      debugPrint('DEBUG: Lahan Metadata Loaded:');
      debugPrint('Kecamatan: ${_kecamatanList.length}');
      debugPrint('Kelurahan: ${_kelurahanList.length}');
      debugPrint('Tipe Lahan: ${_tipeLahanList.length}');
      debugPrint('Petani Spasial: ${_petaniSpasialList.length}');
    } catch (e) {
      debugPrint('DEBUG FATAL ERROR: $e');
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Memuat semua referensi dan siklus tanam berjalan untuk Lapor Tanam Baru
  Future<void> fetchTanamMetadata() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getLahanDropdown().catchError((e) {
          debugPrint('DEBUG ERROR getLahanDropdown: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Lahan: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getBibit().catchError((e) {
          debugPrint('DEBUG ERROR getBibit: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Bibit: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getJenisPupuk().catchError((e) {
          debugPrint('DEBUG ERROR getJenisPupuk: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Pupuk: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
        _farmingService.getMySiklusTanam().catchError((e) {
          debugPrint('DEBUG ERROR getMySiklusTanam: $e');
          _errorMessage =
              '${_errorMessage != null ? '$_errorMessage\n' : ''}Siklus: ${e.toString().replaceAll('Exception: ', '')}';
          return [];
        }),
      ]);

      _lahanDropdownList = results[0];
      _bibitList = results[1];
      _pupukList = results[2];
      _mySiklusTanam = results[3];
    } catch (e) {
      debugPrint('DEBUG FATAL ERROR: $e');
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Mengirim data pengajuan lahan sawah baru ke microservice
  Future<bool> submitLahan(Map<String, dynamic> payload) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.submitLahan(payload);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Mengirim laporan tanam baru ke microservice
  Future<bool> submitLaporTanam(Map<String, dynamic> payload) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.submitLaporTanam(payload);
      // Refresh list siklus tanam berjalan setelah submit berhasil
      _mySiklusTanam = await _farmingService.getMySiklusTanam();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Menghapus siklus tanam aktif milik petani
  Future<bool> deleteSiklusTanam(int id) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.deleteSiklusTanam(id);
      // Refresh list siklus tanam berjalan setelah hapus berhasil
      _mySiklusTanam = await _farmingService.getMySiklusTanam();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Mengirim laporan hasil panen padi ke microservice
  Future<bool> submitLaporPanen(Map<String, dynamic> payload) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.submitLaporPanen(payload);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Mengirim perbaikan lahan
  Future<bool> resubmitLahan(int id, Map<String, dynamic> payload) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.resubmitLahan(id, payload);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Mengirim perbaikan hasil panen
  Future<bool> updateLaporPanen(int id, Map<String, dynamic> payload) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.updateLaporPanen(id, payload);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Pejabat: Memuat semua data dashboard pejabat eksekutif sekaligus
  Future<void> fetchPejabatDashboardData() async {
    _isPejabatLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getProduksiPejabat(),
        _farmingService.getTotalLahanPejabat(),
        _farmingService.getProduksiBulananPejabat(),
        _farmingService.getProduksiKecamatanPejabat(),
        _farmingService.getLahanKecamatanPejabat(),
      ]);

      _produksiPejabat = results[0] as double;
      _totalLahanPejabat = results[1] as double;
      _produksiBulananPejabat = results[2] as Map<int, double>;
      _produksiKecamatanPejabat = results[3] as List<dynamic>;
      _lahanKecamatanPejabat = results[4] as List<dynamic>;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPejabatLoading = false;
      notifyListeners();
    }
  }

  /// Pejabat: Memuat data sebaran spasial (batas wilayah kabupaten, kecamatan, dan lahan sawah)
  Future<void> fetchMapData() async {
    _isMapLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getBatasWilayah(),
        _farmingService.getBatasKecamatan(),
        _farmingService.getMapLahan(),
      ]);

      _kabupatenBoundary = results[0];
      _kecamatanBoundaries = results[1];
      _lahanMapFeatures = results[2];
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isMapLoading = false;
      notifyListeners();
    }
  }

  /// Pejabat: Memuat data statistik produksi daerah lengkap
  Future<void> fetchStatistikData() async {
    _isStatistikLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _farmingService.getStatistik();
      _statistikData = result['data'] as Map<String, dynamic>;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isStatistikLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memuat data statistik antrean & data awal dashboard petugas

  Future<void> fetchPetugasDashboardData() async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getPendingLahan(),
        _farmingService.getPendingPanen(),
        _farmingService.getPetaniSpasial(),
        _farmingService.getAcceptedLahan(),
        _farmingService.getMonitoring(),
        _farmingService.getPetugasPendingLahan(),
        _farmingService.getPetugasPendingPanen(),
        _farmingService.getPetugasNotifikasi(),
      ]);

      _pendingLahanList = results[0] as List<dynamic>;
      _pendingPanenList = results[1] as List<dynamic>;
      _spasialLahanList = results[2] as List<dynamic>;
      _acceptedLahanList = results[3] as List<dynamic>;
      _monitoringList = results[4] as List<dynamic>;

      _pendingLahanCount = _pendingLahanList.length;
      _pendingPanenCount = _pendingPanenList.length;
      _totalPendingCount = _pendingLahanCount + _pendingPanenCount;

      _petugasPendingLahan = results[5] as List<dynamic>;
      _petugasPendingPanen = results[6] as List<dynamic>;
      final notifikasiResult = results[7] as Map<String, dynamic>;
      _petugasNotifikasi = notifikasiResult['data'] as List<dynamic>? ?? [];
      _petugasUnreadCount =
          int.tryParse(notifikasiResult['unread_count']?.toString() ?? '0') ??
          0;
      _petugasPendingCounts = Map<String, dynamic>.from(
        notifikasiResult['pending_counts'] as Map? ??
            {
              'pending_lahan': _petugasPendingLahan.length,
              'pending_panen': _petugasPendingPanen.length,
              'total_pending':
                  _petugasPendingLahan.length + _petugasPendingPanen.length,
            },
      );
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memuat ulang daftar lahan pending
  Future<void> fetchPendingLahan() async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _pendingLahanList = await _farmingService.getPendingLahan();
      _pendingLahanCount = _pendingLahanList.length;
      _totalPendingCount = _pendingLahanCount + _pendingPanenCount;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memuat ulang daftar panen pending
  Future<void> fetchPendingPanen() async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _pendingPanenList = await _farmingService.getPendingPanen();
      _pendingPanenCount = _pendingPanenList.length;
      _totalPendingCount = _pendingLahanCount + _pendingPanenCount;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memuat data spasial
  Future<void> fetchSpasialLahan() async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _spasialLahanList = await _farmingService.getSpasialLahan();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Menyetujui pengajuan lahan baru
  Future<bool> approveLahan(int id, int? petaniId) async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.approveLahan(id, petaniId);
      // reload lists & counts
      await fetchPetugasDashboardData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  Future<bool> approvePetugasLahan(int id, {int? petaniId}) async {
    return _runPetugasAction(
      () => _farmingService.approvePetugasLahan(id, petaniId: petaniId),
    );
  }

  Future<bool> rejectPetugasLahan(int id, String reason) async {
    return _runPetugasAction(
      () => _farmingService.rejectPetugasLahan(id, reason),
    );
  }

  Future<bool> approvePetugasPanen(int id) async {
    return _runPetugasAction(() => _farmingService.approvePetugasPanen(id));
  }

  Future<bool> rejectPetugasPanen(int id, String reason) async {
    return _runPetugasAction(
      () => _farmingService.rejectPetugasPanen(id, reason),
    );
  }

  Future<bool> markPetugasNotifikasiRead(int id) async {
    return _runPetugasAction(
      () => _farmingService.markPetugasNotifikasiRead(id),
    );
  }

  Future<bool> _runPetugasAction(
    Future<Map<String, dynamic>> Function() action,
  ) async {
    _isPetugasActionLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await action();
      await fetchPetugasDashboardData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasActionLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Menolak pengajuan lahan baru
  Future<bool> rejectLahan(int id, String alasan) async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.rejectLahan(id, alasan);
      // reload lists & counts
      await fetchPetugasDashboardData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memverifikasi laporan panen
  Future<bool> verifikasiPanen(int id, String status, String? catatan) async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.verifikasiPanen(id, status, catatan);
      // reload lists & counts
      await fetchPetugasDashboardData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memperbarui data spasial lahan
  Future<bool> updateSpasialLahan(int id, Map<String, dynamic> data) async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.updateSpasialLahan(id, data);
      await fetchSpasialLahan();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Memuat data monitoring & accepted lands
  Future<void> fetchMonitoringData() async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getAcceptedLahan(),
        _farmingService.getMonitoring(),
      ]);
      _acceptedLahanList = results[0];
      _monitoringList = results[1];
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchPetugasSpasialData() async {
    _isPetugasSpasialLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _farmingService.getPetugasSpasialReferensi(),
        _farmingService.getPetugasSpasialLahan(),
        _farmingService.getBatasWilayah(),
        _farmingService.getBatasKecamatan(),
      ]);

      _petugasSpasialReferensi = Map<String, dynamic>.from(results[0]);
      final spasialResponse = results[1];
      _petugasSpasialRows = spasialResponse['data'] as List<dynamic>? ?? [];
      _petugasSpasialSummary = Map<String, dynamic>.from(
        spasialResponse['summary'] as Map? ??
            {
              'total': _petugasSpasialRows.length,
              'sudah_dipetakan': 0,
              'belum_dipetakan': 0,
              'persentase_lengkap': 0,
            },
      );
      _kabupatenBoundary = results[2];
      _kecamatanBoundaries = results[3];
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isPetugasSpasialLoading = false;
      notifyListeners();
    }
  }

  /// Petugas: Menyimpan parameter lingkungan
  Future<bool> saveMonitoring(Map<String, dynamic> data) async {
    _isPetugasLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.saveMonitoring(data);
      await fetchMonitoringData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasLoading = false;
      notifyListeners();
    }
  }

  Future<bool> savePetugasSpasial(int id, Map<String, dynamic> payload) async {
    _isPetugasActionLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.savePetugasSpasialLahan(id, payload);
      await fetchPetugasSpasialData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasActionLoading = false;
      notifyListeners();
    }
  }

  Future<bool> deletePetugasSpasial(int id) async {
    _isPetugasActionLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _farmingService.deletePetugasSpasialLahan(id);
      await fetchPetugasSpasialData();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isPetugasActionLoading = false;
      notifyListeners();
    }
  }
}
