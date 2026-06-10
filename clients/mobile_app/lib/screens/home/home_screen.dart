import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final user = authProvider.currentUser;

    // Menentukan teks deskripsi role berdasarkan role_id
    String roleText = 'Pengguna';
    if (user?.roleId == 1) roleText = 'Petani';
    if (user?.roleId == 2) roleText = 'Petugas Lapangan';
    if (user?.roleId == 3) roleText = 'Pejabat';
    if (user?.roleId == 4) roleText = 'Administrator';

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
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // User Profile Card
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.green.shade800, Colors.green.shade600],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.green.shade900.withValues(alpha: 0.15),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 30,
                        backgroundColor: Colors.white24,
                        child: Text(
                          user != null && user.namaLengkap.isNotEmpty
                              ? user.namaLengkap.substring(0, 1).toUpperCase()
                              : 'U',
                          style: GoogleFonts.outfit(
                            fontSize: 26,
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.namaLengkap ?? 'User',
                              style: GoogleFonts.outfit(
                                fontSize: 20,
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white24,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                roleText,
                                style: GoogleFonts.inter(
                                  fontSize: 12,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const Divider(color: Colors.white30, height: 32),
                  Row(
                    children: [
                      const Icon(Icons.email_outlined, color: Colors.white70, size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          user?.email ?? '-',
                          style: GoogleFonts.inter(color: Colors.white70, fontSize: 14),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  if (user?.noHp != null && user!.noHp!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.phone_outlined, color: Colors.white70, size: 18),
                        const SizedBox(width: 8),
                        Text(
                          user.noHp!,
                          style: GoogleFonts.inter(color: Colors.white70, fontSize: 14),
                        ),
                      ],
                    ),
                  ],
                  if (user?.alamat != null && user!.alamat!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.home_outlined, color: Colors.white70, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            user.alamat!,
                            style: GoogleFonts.inter(color: Colors.white70, fontSize: 14),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 32),
            Text(
              'Akses Fitur',
              style: GoogleFonts.outfit(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF1B3D2F),
              ),
            ),
            const SizedBox(height: 16),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              children: [
                _buildQuickActionCard(
                  context,
                  title: 'Informasi Lahan',
                  subtitle: 'Manajemen spasial lahan',
                  icon: Icons.map_rounded,
                  color: Colors.teal.shade700,
                ),
                _buildQuickActionCard(
                  context,
                  title: 'Riwayat Panen',
                  subtitle: 'Data produksi pala',
                  icon: Icons.agriculture_rounded,
                  color: Colors.orange.shade700,
                ),
                _buildQuickActionCard(
                  context,
                  title: 'Aktivitas Tani',
                  subtitle: 'Catat kegiatan harian',
                  icon: Icons.event_note_rounded,
                  color: Colors.blue.shade700,
                ),
                _buildQuickActionCard(
                  context,
                  title: 'Notifikasi',
                  subtitle: 'Pesan dan info dinas',
                  icon: Icons.notifications_rounded,
                  color: Colors.red.shade700,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActionCard(
    BuildContext context, {
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          CircleAvatar(
            backgroundColor: color.withValues(alpha: 0.1),
            child: Icon(icon, color: color),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.outfit(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF1B3D2F),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: GoogleFonts.inter(
                  fontSize: 11,
                  color: Colors.grey[600],
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
