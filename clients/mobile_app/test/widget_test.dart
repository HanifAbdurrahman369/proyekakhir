import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/main.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('menampilkan landing screen saat belum login', (tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const MyApp());
    await tester.pumpAndSettle();

    expect(find.text('Masuk'), findsOneWidget);
  });
}
