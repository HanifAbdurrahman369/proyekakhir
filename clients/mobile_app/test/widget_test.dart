import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/main.dart';
import 'package:mobile_app/core/network/api_client.dart';
import 'package:mobile_app/providers/auth_provider.dart';
import 'package:mobile_app/screens/auth/login_screen.dart';
import 'package:mobile_app/services/auth_service.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

class _FakeAuthService extends AuthService {
  _FakeAuthService() : super(ApiClient());

  @override
  Future<Map<String, dynamic>> login(String loginId, String password) async {
    return {
      'token': 'token-pengujian',
      'user': {
        'id': 3,
        'nama_lengkap': 'Petugas Pengujian',
        'email': 'petugas@example.test',
        'role_id': 2,
      },
    };
  }
}

class _RecordingNavigatorObserver extends NavigatorObserver {
  int popCount = 0;

  @override
  void didPop(Route<dynamic> route, Route<dynamic>? previousRoute) {
    popCount++;
    super.didPop(route, previousRoute);
  }
}

void main() {
  testWidgets('menampilkan landing screen saat belum login', (tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const MyApp());
    await tester.pumpAndSettle();

    expect(find.text('Masuk'), findsOneWidget);
  });

  testWidgets('login berhasil tidak mem-pop route utama menjadi layar hitam', (
    tester,
  ) async {
    SharedPreferences.setMockInitialValues({});
    final authProvider = AuthProvider(_FakeAuthService());
    final navigatorObserver = _RecordingNavigatorObserver();

    await tester.pumpWidget(
      ChangeNotifierProvider<AuthProvider>.value(
        value: authProvider,
        child: MaterialApp(
          navigatorObservers: [navigatorObserver],
          home: const LoginScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    final fields = find.byType(TextFormField);
    expect(fields, findsNWidgets(3));

    final captchaText = tester
        .widgetList<Text>(find.byType(Text))
        .map((widget) => widget.data ?? '')
        .firstWhere((text) => RegExp(r'^\d+ \+ \d+ = \?$').hasMatch(text));
    final numbers = RegExp(r'^(\d+) \+ (\d+) = \?$').firstMatch(captchaText)!;
    final answer = int.parse(numbers.group(1)!) + int.parse(numbers.group(2)!);

    await tester.enterText(fields.at(0), '6301010101010101');
    await tester.enterText(fields.at(1), 'password');
    await tester.enterText(fields.at(2), answer.toString());
    await tester.ensureVisible(find.text('Masuk'));
    await tester.tap(find.widgetWithText(InkWell, 'Masuk'));
    await tester.pumpAndSettle();

    expect(authProvider.isAuthenticated, isTrue);
    expect(navigatorObserver.popCount, 0);
  });
}
