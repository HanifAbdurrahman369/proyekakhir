import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

// Imports
import 'core/network/api_client.dart';
import 'services/auth_service.dart';
import 'providers/auth_provider.dart';
import 'screens/home/home_screen.dart';
import 'screens/landing_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        // 1. ApiClient (Singleton HTTP Client)
        Provider<ApiClient>(
          create: (_) => ApiClient(),
        ),
        // 2. AuthService (Bergantung pada ApiClient)
        ProxyProvider<ApiClient, AuthService>(
          update: (_, apiClient, _) => AuthService(apiClient),
        ),
        // 3. AuthProvider (State Management, bergantung pada AuthService)
        ChangeNotifierProxyProvider<AuthService, AuthProvider>(
          create: (context) => AuthProvider(context.read<AuthService>()),
          update: (_, authService, authProvider) =>
              authProvider ?? AuthProvider(authService),
        ),
      ],
      child: MaterialApp(
        title: 'SITANI Mobile',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: Colors.green,
            primary: Colors.green[800],
            secondary: Colors.teal[700],
            surface: const Color(0xFFF4F9F4),
          ),
          textTheme: GoogleFonts.interTextTheme(Theme.of(context).textTheme),
        ),
        home: const AuthWrapper(),
      ),
    );
  }
}

/// Widget Wrapper untuk mengecek status autentikasi secara dinamis
class AuthWrapper extends StatelessWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();

    // Tampilkan loading screen jika session sedang diinisialisasi
    if (!authProvider.isInitialized) {
      return const Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(
                color: Colors.green,
              ),
              SizedBox(height: 16),
              Text(
                'Memuat Aplikasi...',
                style: TextStyle(
                  color: Colors.grey,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      );
    }

    // Arahkan ke HomeScreen jika terautentikasi, jika tidak ke LandingScreen
    if (authProvider.isAuthenticated) {
      return const HomeScreen();
    } else {
      return const LandingScreen();
    }
  }
}
