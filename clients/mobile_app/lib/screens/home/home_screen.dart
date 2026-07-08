import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../models/user.dart';
import 'dashboards/petani_dashboard.dart';
import 'dashboards/petugas_dashboard.dart';
import 'dashboards/pejabat_dashboard.dart';
import 'dashboards/admin_dashboard.dart';
import 'riwayat_aktivitas_screen.dart';
import 'tambah_lahan_screen.dart';
import 'lapor_tanam_screen.dart';
import 'lapor_panen_screen.dart';
import 'sebaran_lahan_screen.dart';
import 'dashboards/produksi_daerah_screen.dart';
import 'petugas_lahan_termonitor_screen.dart';
import 'petugas_spasial_screen.dart';
import 'petugas_verifikasi_screen.dart';
import 'edit_profile_screen.dart';

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
        case 5:
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
                  const Icon(
                    Icons.error_outline_rounded,
                    size: 48,
                    color: Colors.grey,
                  ),
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

    // Helper untuk logout konfirmasi
    Future<void> handleLogout() async {
      final confirm = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Logout',
            style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
          ),
          content: Text(
            'Apakah Anda yakin ingin keluar dari aplikasi?',
            style: GoogleFonts.inter(),
          ),
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
    }

    // Builder untuk Sidebar Drawer
    Widget buildDrawer(User? user) {
      final roleId = user?.roleId ?? 1;
      final wilayahDesa = user?.wilayahKelurahanNama.join(', ');
      final roleName = switch (roleId) {
        1 => 'Kelompok Tani',
        2 => 'Petugas Lapangan',
        3 => 'Pejabat',
        4 => 'Administrator',
        5 => 'Brigade Pangan',
        _ => 'Pengguna',
      };

      final currentMonth = DateTime.now().month;
      final isKelompokTaniAllowed = (currentMonth >= 1 && currentMonth <= 9);
      final isBrigadePanganAllowed = [10, 11, 12, 1].contains(currentMonth);
      final isAllowedToPlant =
          (roleId == 1 && isKelompokTaniAllowed) ||
          (roleId == 5 && isBrigadePanganAllowed);

      // Hanya tampilkan menu petani jika peran adalah kelompok tani (1) atau brigade pangan (5)
      final isFarmerRole = (roleId == 1 || roleId == 5);

      return Drawer(
        backgroundColor: Colors.white,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Drawer Header
            Container(
              padding: EdgeInsets.only(
                top: MediaQuery.of(context).padding.top + 24,
                bottom: 24,
                left: 20,
                right: 20,
              ),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF3E7D00), Color(0xFF5EA500)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: Colors.white24,
                    child: Text(
                      user != null && user.namaLengkap.isNotEmpty
                          ? user.namaLengkap.substring(0, 1).toUpperCase()
                          : 'U',
                      style: GoogleFonts.outfit(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    user?.namaLengkap ?? 'Pengguna',
                    style: GoogleFonts.outfit(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    user?.email ?? '-',
                    style: GoogleFonts.inter(
                      fontSize: 12,
                      color: Colors.white70,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white24,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      roleName,
                      style: GoogleFonts.inter(
                        fontSize: 9,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  if (roleId == 2 &&
                      ((user?.wilayahKecamatanNama ?? '').isNotEmpty ||
                          (wilayahDesa ?? '').isNotEmpty)) ...[
                    const SizedBox(height: 8),
                    Text(
                      [
                        if ((user?.wilayahKecamatanNama ?? '').isNotEmpty)
                          user!.wilayahKecamatanNama!,
                        if ((wilayahDesa ?? '').isNotEmpty) wilayahDesa!,
                      ].join(' - '),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.inter(
                        fontSize: 10,
                        color: Colors.white70,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ],
              ),
            ),

            // Drawer Body
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  if (isFarmerRole) ...[
                    // Category: Lahan & Produksi
                    _buildCategoryHeader('LAHAN & PRODUKSI'),
                    _buildDrawerItem(
                      icon: Icons.landscape_rounded,
                      label: roleId == 5 ? 'Proses Tanam' : 'Lahan Sawah',
                      isSelected: true,
                      onTap: () => Navigator.pop(context),
                    ),

                    // Category: Aktivitas
                    _buildCategoryHeader('AKTIVITAS'),
                    if (roleId == 1)
                      _buildDrawerItem(
                        icon: Icons.add_location_alt_rounded,
                        label: 'Daftar Lahan Sawah',
                        onTap: () {
                          Navigator.pop(context);
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const TambahLahanScreen(),
                            ),
                          );
                        },
                      ),
                    _buildDrawerItem(
                      icon: isAllowedToPlant
                          ? Icons.grass_rounded
                          : Icons.lock_outline_rounded,
                      label: isAllowedToPlant
                          ? 'Lapor Tanam'
                          : 'Lapor Tanam (Kunci)',
                      isLocked: !isAllowedToPlant,
                      onTap: isAllowedToPlant
                          ? () {
                              Navigator.pop(context);
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      const LaporTanamScreen(),
                                ),
                              );
                            }
                          : null,
                    ),
                    if (roleId == 1)
                      _buildDrawerItem(
                        icon: Icons.scale_rounded,
                        label: 'Lapor Panen',
                        onTap: () {
                          Navigator.pop(context);
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const LaporPanenScreen(),
                            ),
                          );
                        },
                      ),
                    _buildDrawerItem(
                      icon: Icons.history_edu_rounded,
                      label: 'Riwayat Aktivitas',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) =>
                                const RiwayatAktivitasScreen(),
                          ),
                        );
                      },
                    ),
                  ] else if (roleId == 2) ...[
                    _buildCategoryHeader('OPERASIONAL PETUGAS'),
                    _buildDrawerItem(
                      icon: Icons.dashboard_rounded,
                      label: 'Beranda Petugas',
                      isSelected: true,
                      onTap: () => Navigator.pop(context),
                    ),
                    _buildDrawerItem(
                      icon: Icons.map_rounded,
                      label: 'Manajemen Data Spasial',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => const PetugasSpasialScreen(),
                          ),
                        );
                      },
                    ),
                    _buildDrawerItem(
                      icon: Icons.sensors_rounded,
                      label: 'Lahan Termonitor (IoT)',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) =>
                                const PetugasLahanTermonitorScreen(),
                          ),
                        );
                      },
                    ),
                    _buildDrawerItem(
                      icon: Icons.verified_user_rounded,
                      label: 'Verifikasi Data Petani',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) =>
                                const PetugasVerifikasiScreen(),
                          ),
                        );
                      },
                    ),
                    _buildDrawerItem(
                      icon: Icons.manage_accounts_rounded,
                      label: 'Edit Profil',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => const EditProfileScreen(),
                          ),
                        );
                      },
                    ),
                  ] else if (roleId == 3) ...[
                    // Category: LAPORAN EKSEKUTIF
                    _buildCategoryHeader('LAPORAN EKSEKUTIF'),
                    _buildDrawerItem(
                      icon: Icons.dashboard_rounded,
                      label: 'Statistik Utama',
                      isSelected: true,
                      onTap: () => Navigator.pop(context),
                    ),
                    _buildDrawerItem(
                      icon: Icons.map_rounded,
                      label: 'Sebaran Lahan',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => const SebaranLahanScreen(),
                          ),
                        );
                      },
                    ),

                    // Category: ANALISIS DATA
                    _buildCategoryHeader('ANALISIS DATA'),
                    _buildDrawerItem(
                      icon: Icons.analytics_rounded,
                      label: 'Produksi Daerah',
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => const ProduksiDaerahScreen(),
                          ),
                        );
                      },
                    ),
                  ] else ...[
                    // Default menu for other roles
                    _buildCategoryHeader('MENU UTAMA'),
                    _buildDrawerItem(
                      icon: Icons.dashboard_rounded,
                      label: 'Dashboard',
                      isSelected: true,
                      onTap: () => Navigator.pop(context),
                    ),
                  ],
                  const Divider(color: Color(0xFFE2E8F0)),
                  _buildDrawerItem(
                    icon: Icons.logout_rounded,
                    label: 'Keluar',
                    iconColor: Colors.red[700],
                    textColor: Colors.red[700],
                    onTap: () {
                      Navigator.pop(context);
                      handleLogout();
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/images/logo.png',
              height: 32,
              fit: BoxFit.contain,
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Sistem Informasi',
                  style: GoogleFonts.inter(
                    fontSize: 10,
                    fontWeight: FontWeight.w500,
                    color: Colors.white70,
                  ),
                ),
                Text(
                  'Dinas Pertanian',
                  style: GoogleFonts.outfit(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ],
        ),
        backgroundColor: Colors.green[800],
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Logout',
            onPressed: handleLogout,
          ),
        ],
      ),
      drawer: buildDrawer(user),
      body: buildDashboard(user?.roleId),
    );
  }

  Widget _buildCategoryHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        title,
        style: GoogleFonts.inter(
          fontSize: 9,
          fontWeight: FontWeight.w800,
          color: const Color(0xFF94A3B8),
          letterSpacing: 1.5,
        ),
      ),
    );
  }

  Widget _buildDrawerItem({
    required IconData icon,
    required String label,
    VoidCallback? onTap,
    bool isSelected = false,
    bool isLocked = false,
    Color? iconColor,
    Color? textColor,
  }) {
    final activeColor = const Color(0xFF3E7D00);
    final activeBg = const Color(0xFFEDF8DC);

    Color finalIconColor =
        iconColor ?? (isSelected ? activeColor : const Color(0xFF64748B));
    Color finalTextColor =
        textColor ??
        (isSelected ? const Color(0xFF203C10) : const Color(0xFF334155));

    if (isLocked) {
      finalIconColor = const Color(0xFFCBD5E1);
      finalTextColor = const Color(0xFF94A3B8);
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      child: Material(
        color: isSelected ? activeBg : Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: isSelected
              ? const BorderSide(color: Color(0xFFDFECCC))
              : BorderSide.none,
        ),
        clipBehavior: Clip.antiAlias,
        child: ListTile(
          dense: true,
          visualDensity: VisualDensity.compact,
          leading: Icon(icon, color: finalIconColor, size: 18),
          title: Text(
            label,
            style: GoogleFonts.inter(
              fontSize: 13,
              fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
              color: finalTextColor,
            ),
          ),
          onTap: onTap,
          enabled: !isLocked && onTap != null,
        ),
      ),
    );
  }

  // ignore: unused_element
  void _showProduksiDaerahInfoDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'Laporan Produksi Daerah',
          style: GoogleFonts.outfit(
            fontWeight: FontWeight.bold,
            color: const Color(0xFF14280B),
          ),
        ),
        content: Text(
          'Grafik tren produksi bulanan sudah tersedia di dashboard Statistik Utama. Untuk laporan analisis produksi daerah interaktif yang lebih lengkap (termasuk filter tipe lahan dan grafik produktivitas), silakan buka aplikasi SiTani versi Web.',
          style: GoogleFonts.inter(
            fontSize: 14,
            height: 1.5,
            color: const Color(0xFF475569),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            style: TextButton.styleFrom(
              foregroundColor: const Color(0xFF3E7D00),
            ),
            child: Text(
              'OK',
              style: GoogleFonts.inter(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}
