import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../models/user.dart';

class AdminDashboard extends StatelessWidget {
  final User? user;

  const AdminDashboard({super.key, required this.user});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Profile Card
          // _buildProfileCard(),
          // const SizedBox(height: 28),
          
          // Statistics
          Text(
            'Status Sistem SIG-PALA',
            style: GoogleFonts.outfit(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  title: 'Total Akun User',
                  value: '45 Akun',
                  subtitle: 'Dalam Database',
                  icon: Icons.people_rounded,
                  color: Colors.teal,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard(
                  title: 'Status Microservice',
                  value: '8 Aktif',
                  subtitle: 'Semua Port Up',
                  icon: Icons.dns_rounded,
                  color: Colors.orange,
                ),
              ),
            ],
          ),
          const SizedBox(height: 28),

          // Quick Action Menu Title
          Text(
            'Akses Fitur Administrator',
            style: GoogleFonts.outfit(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 16),
          
          // Quick Action Grid
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
            childAspectRatio: 1.1,
            children: [
              _buildQuickActionCard(
                title: 'Kelola Pengguna',
                subtitle: 'Manajemen akun user & role',
                icon: Icons.person_add_rounded,
                color: Colors.teal.shade700,
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Membuka Manajemen Akun User...')),
                  );
                },
              ),
              _buildQuickActionCard(
                title: 'Data Master',
                subtitle: 'Kelola tabel master sistem',
                icon: Icons.table_chart_rounded,
                color: Colors.orange.shade800,
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Membuka Manajemen Ekosistem Data Master...')),
                  );
                },
              ),
              _buildQuickActionCard(
                title: 'Eksekusi SQL',
                subtitle: 'Bypass DBA Service Command',
                icon: Icons.terminal_rounded,
                color: Colors.blue.shade700,
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Membuka Konsol SQL Administrator...')),
                  );
                },
              ),
              _buildQuickActionCard(
                title: 'Notifikasi',
                subtitle: 'Pengumuman sistem global',
                icon: Icons.notifications_active_rounded,
                color: Colors.red.shade700,
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Membuka Notifikasi Global...')),
                  );
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: color.withValues(alpha: 0.1),
            radius: 20,
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: GoogleFonts.outfit(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            title,
            style: GoogleFonts.inter(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: const Color(0xFF64748B),
            ),
          ),
          Text(
            subtitle,
            style: GoogleFonts.inter(
              fontSize: 10,
              color: const Color(0xFF94A3B8),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActionCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
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
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: GoogleFonts.inter(
                        fontSize: 11,
                        color: const Color(0xFF64748B),
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
