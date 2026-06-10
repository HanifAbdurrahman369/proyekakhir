import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import 'dashboards/petani_dashboard.dart';
import 'dashboards/petugas_dashboard.dart';
import 'dashboards/pejabat_dashboard.dart';
import 'dashboards/admin_dashboard.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final user = authProvider.currentUser;

    // Helper untuk merender sub-dasbor yang sesuai berdasarkan role_id
    Widget buildDashboard(int? roleId) {
      switch (roleId) {
        case 1:
          return PetaniDashboard(user: user);
        case 2:
          return PetugasDashboard(user: user);
        case 3:
          return PejabatDashboard(user: user);
        case 4:
          return AdminDashboard(user: user);
        default:
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline_rounded, size: 48, color: Colors.grey),
                  const SizedBox(height: 16),
                  Text(
                    'Role tidak teridentifikasi di sistem.',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.inter(
                      fontSize: 16,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          );
      }
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Dashboard SITANI',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.green[800],
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Logout',
            onPressed: () async {
              final confirm = await showDialog<bool>(
                context: context,
                builder: (context) => AlertDialog(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  title: Text('Logout', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
                  content: Text('Apakah Anda yakin ingin keluar dari aplikasi?', style: GoogleFonts.inter()),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text('Batal', style: TextStyle(color: Colors.green[800])),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text('Keluar', style: TextStyle(color: Colors.red[700])),
                    ),
                  ],
                ),
              );

              if (confirm == true) {
                await authProvider.logout();
              }
            },
          ),
        ],
      ),
      body: buildDashboard(user?.roleId),
    );
  }
}
