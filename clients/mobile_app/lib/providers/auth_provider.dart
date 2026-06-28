import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import '../services/auth_service.dart';

class AuthProvider extends ChangeNotifier {
  final AuthService _authService;
  User? _currentUser;
  String? _token;
  bool _isLoading = false;
  bool _isInitialized = false;

  AuthProvider(this._authService) {
    _loadSession();
  }

  User? get currentUser => _currentUser;
  String? get token => _token;
  bool get isAuthenticated => _token != null;
  bool get isLoading => _isLoading;
  bool get isInitialized => _isInitialized;

  /// Memuat session token yang tersimpan di memori hp saat aplikasi pertama dibuka
  Future<void> _loadSession() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      _token = prefs.getString('auth_token');
      
      if (_token != null) {
        // Ambil data profil terbaru untuk memastikan token masih valid
        _currentUser = await _authService.getProfile();
      }
    } catch (e) {
      // Jika token kedaluwarsa atau server error, logout & bersihkan cache token
      await logout();
    } finally {
      _isLoading = false;
      _isInitialized = true;
      notifyListeners();
    }
  }

  /// Menangani aksi login pengguna
  Future<void> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final data = await _authService.login(email, password);
      _token = data['token'] as String;
      
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', _token!);
      
      _currentUser = User.fromJson(data['user'] as Map<String, dynamic>);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Menangani aksi registrasi petani baru
  Future<void> register({
    required String namaLengkap,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String jenisKelompok,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      await _authService.register(
        namaLengkap: namaLengkap,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        jenisKelompok: jenisKelompok,
      );
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Menangani aksi logout dan menghapus token dari memori lokal
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      _token = null;
      _currentUser = null;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Menangani aksi lupa password
  Future<void> forgotPassword(String email) async {
    _isLoading = true;
    notifyListeners();

    try {
      await _authService.forgotPassword(email);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Menangani aksi atur ulang password
  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      await _authService.resetPassword(
        email: email,
        token: token,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
