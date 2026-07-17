import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_endpoints.dart';

class ApiClient {
  final Dio dio;

  ApiClient()
    : dio = Dio(
        BaseOptions(
          baseUrl: ApiEndpoints.baseUrl,
          connectTimeout: const Duration(seconds: 30),
          receiveTimeout: const Duration(seconds: 30),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      ) {
    _initInterceptors();
  }

  void _initInterceptors() {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('auth_token');
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException e, handler) {
          // Error handler global (bisa diperluas untuk menangani unauthorized 401)
          return handler.next(e);
        },
      ),
    );

    // Jangan pernah mencetak password, token, atau data pengguna pada build produksi.
    if (kDebugMode) {
      dio.interceptors.add(
        LogInterceptor(
          requestHeader: false,
          requestBody: false,
          responseHeader: false,
          responseBody: false,
          error: true,
        ),
      );
    }
  }
}
