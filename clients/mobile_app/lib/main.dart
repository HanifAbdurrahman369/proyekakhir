import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:app_links/app_links.dart';

// Imports
import 'core/network/api_client.dart';
import 'services/auth_service.dart';
import 'providers/auth_provider.dart';
import 'services/farming_service.dart';
import 'providers/farming_provider.dart';
import 'screens/home/home_screen.dart';
import 'screens/landing_screen.dart';
import 'screens/auth/reset_password_screen.dart';


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
        // 2. AuthService & FarmingService
        ProxyProvider<ApiClient, AuthService>(
          update: (_, apiClient, _) => AuthService(apiClient),
        ),
        ProxyProvider<ApiClient, FarmingService>(
          update: (_, apiClient, _) => FarmingService(apiClient),
        ),
        // 3. AuthProvider & FarmingProvider
        ChangeNotifierProxyProvider<AuthService, AuthProvider>(
          create: (context) => AuthProvider(context.read<AuthService>()),
          update: (_, authService, authProvider) =>
              authProvider ?? AuthProvider(authService),
        ),
        ChangeNotifierProxyProvider<FarmingService, FarmingProvider>(
          create: (context) => FarmingProvider(context.read<FarmingService>()),
          update: (_, farmingService, farmingProvider) =>
              farmingProvider ?? FarmingProvider(farmingService),
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
class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  late final AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;

  @override
  void initState() {
    super.initState();
    _initDeepLinks();
  }

  void _initDeepLinks() async {
    _appLinks = AppLinks();

    // Tangani link jika aplikasi ditutup (cold start)
    try {
      final initialUri = await _appLinks.getInitialLink();
      if (initialUri != null) {
        _handleDeepLink(initialUri);
      }
    } catch (e) {
      debugPrint('Error getting initial link: $e');
    }

    // Tangani link jika aplikasi sedang aktif atau berada di background
    _linkSubscription = _appLinks.uriLinkStream.listen((uri) {
      _handleDeepLink(uri);
    }, onError: (err) {
      debugPrint('Error listening to uri link stream: $err');
    });
  }

  void _handleDeepLink(Uri uri) {
    debugPrint('Received Deep Link: $uri');
    if (uri.scheme == 'sigpala' && uri.host == 'reset-password') {
      final pathSegments = uri.pathSegments;
      String token = pathSegments.isNotEmpty ? pathSegments.first : '';
      String email = uri.queryParameters['email'] ?? '';

      // Cek format alternatif query parameter: sigpala://reset-password?token={token}&email={email}
      if (token.isEmpty) {
        token = uri.queryParameters['token'] ?? '';
      }

      if (token.isNotEmpty) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => ResetPasswordScreen(
                token: token,
                email: email,
              ),
            ),
          );
        });
      }
    }
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    super.dispose();
  }

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
