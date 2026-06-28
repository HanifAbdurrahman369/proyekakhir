import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../../models/user.dart';
import '../../../providers/farming_provider.dart';
import '../petugas_notifikasi_screen.dart';
import '../petugas_spasial_screen.dart';
import '../petugas_verifikasi_screen.dart';

class PetugasDashboard extends StatefulWidget {
  final User? user;

  const PetugasDashboard({super.key, required this.user});

  @override
  State<PetugasDashboard> createState() => _PetugasDashboardState();
}

class _PetugasDashboardState extends State<PetugasDashboard> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchPetugasDashboardData();
    });
  }

  Future<void> _refresh() =>
      context.read<FarmingProvider>().fetchPetugasDashboardData();

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
    final totalPending =
        int.tryParse(
          provider.petugasPendingCounts['total_pending']?.toString() ?? '',
        ) ??
        (provider.petugasPendingLahan.length +
            provider.petugasPendingPanen.length);
    final pendingLahan =
        int.tryParse(
          provider.petugasPendingCounts['pending_lahan']?.toString() ?? '',
        ) ??
        provider.petugasPendingLahan.length;
    final pendingPanen =
        int.tryParse(
          provider.petugasPendingCounts['pending_panen']?.toString() ?? '',
        ) ??
        provider.petugasPendingPanen.length;

    if (provider.isPetugasLoading &&
        provider.petugasPendingLahan.isEmpty &&
        provider.petugasPendingPanen.isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF3E7D00)),
      );
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      color: const Color(0xFF3E7D00),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildProfileCard(widget.user),
            const SizedBox(height: 18),
            _buildHeader(),
            const SizedBox(height: 16),
            LayoutBuilder(
              builder: (context, constraints) {
                final width = (constraints.maxWidth - 12) / 2;
                return Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    _buildStatCard(
                      width: constraints.maxWidth,
                      title: 'TOTAL ANTREAN',
                      value: '$totalPending',
                      desc:
                          'Pengajuan lahan dan laporan panen yang menunggu verifikasi.',
                      icon: Icons.pending_actions_rounded,
                      dark: true,
                    ),
                    _buildStatCard(
                      width: width,
                      title: 'PENGAJUAN LAHAN',
                      value: '$pendingLahan',
                      desc: 'Menunggu pemeriksaan petugas.',
                      icon: Icons.landscape_rounded,
                    ),
                    _buildStatCard(
                      width: width,
                      title: 'LAPORAN PANEN',
                      value: '$pendingPanen',
                      desc: 'Menunggu legalisasi data panen.',
                      icon: Icons.fact_check_rounded,
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 20),
            _buildMenuSection(),
            const SizedBox(height: 20),
            _buildQueuePreview(provider),
            if (provider.errorMessage != null) ...[
              const SizedBox(height: 14),
              _buildErrorBox(provider.errorMessage!),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFFEDF8DC),
            border: Border.all(color: const Color(0xFFDFECCC)),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            'PETUGAS LAPANGAN',
            style: GoogleFonts.inter(
              fontSize: 10,
              fontWeight: FontWeight.w800,
              color: const Color(0xFF3E7D00),
              letterSpacing: 0.8,
            ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Dashboard operasional petugas',
          style: GoogleFonts.outfit(
            fontSize: 22,
            fontWeight: FontWeight.w800,
            color: const Color(0xFF14280B),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Kelola verifikasi data petani dan pemetaan lahan sawah langsung dari aplikasi mobile.',
          style: GoogleFonts.inter(
            fontSize: 12,
            color: const Color(0xFF64748B),
            height: 1.4,
          ),
        ),
      ],
    );
  }

  Widget _buildProfileCard(User? user) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF3E7D00), Color(0xFF5EA500)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF3E7D00).withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 26,
                backgroundColor: Colors.white24,
                child: Text(
                  user != null && user.namaLengkap.isNotEmpty
                      ? user.namaLengkap.substring(0, 1).toUpperCase()
                      : 'P',
                  style: GoogleFonts.outfit(
                    fontSize: 22,
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user?.namaLengkap ?? 'Petugas Lapangan',
                      style: GoogleFonts.outfit(
                        fontSize: 18,
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
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
                        'Petugas Lapangan',
                        style: GoogleFonts.inter(
                          fontSize: 10,
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
          const Divider(color: Colors.white24, height: 24),
          Row(
            children: [
              const Icon(Icons.email_outlined, color: Colors.white70, size: 16),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  user?.email ?? '-',
                  style: GoogleFonts.inter(color: Colors.white70, fontSize: 13),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          if (user?.noHp != null && user!.noHp!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(
                  Icons.phone_outlined,
                  color: Colors.white70,
                  size: 16,
                ),
                const SizedBox(width: 6),
                Text(
                  user.noHp!,
                  style: GoogleFonts.inter(color: Colors.white70, fontSize: 13),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required double width,
    required String title,
    required String value,
    required String desc,
    required IconData icon,
    bool dark = false,
  }) {
    final bg = dark ? const Color(0xFF203C10) : Colors.white;
    final titleColor = dark ? Colors.white70 : const Color(0xFF64748B);
    final textColor = dark ? Colors.white : const Color(0xFF14280B);
    final descColor = dark ? Colors.white70 : const Color(0xFF64748B);

    return Container(
      width: width,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: bg,
        border: dark ? null : Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: dark
                  ? Colors.white.withValues(alpha: 0.14)
                  : const Color(0xFFEDF8DC),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(
              icon,
              color: dark ? Colors.white : const Color(0xFF3E7D00),
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.inter(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: titleColor,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: GoogleFonts.outfit(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                  ),
                ),
                Text(
                  desc,
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    color: descColor,
                    height: 1.3,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Menu kerja petugas',
          style: GoogleFonts.outfit(
            fontSize: 17,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF14280B),
          ),
        ),
        const SizedBox(height: 10),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.08,
          children: [
            _buildMenuCard(
              title: 'Verifikasi Data',
              subtitle: 'Setujui atau tolak lahan dan panen.',
              icon: Icons.verified_user_rounded,
              color: const Color(0xFF3E7D00),
              onTap: () => _push(const PetugasVerifikasiScreen()),
            ),
            _buildMenuCard(
              title: 'Data Spasial',
              subtitle: 'Buat titik dan polygon lahan.',
              icon: Icons.map_rounded,
              color: const Color(0xFF0F766E),
              onTap: () => _push(const PetugasSpasialScreen()),
            ),
            _buildMenuCard(
              title: 'Notifikasi',
              subtitle: 'Pantau pengajuan masuk.',
              icon: Icons.notifications_active_rounded,
              color: const Color(0xFFD97706),
              onTap: () => _push(const PetugasNotifikasiScreen()),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildMenuCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: color, size: 22),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.outfit(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF14280B),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      color: const Color(0xFF64748B),
                      height: 1.25,
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
    );
  }

  Widget _buildQueuePreview(FarmingProvider provider) {
    final latestLahan = provider.petugasPendingLahan.take(2).toList();
    final latestPanen = provider.petugasPendingPanen.take(2).toList();

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Antrean terbaru',
                        style: GoogleFonts.outfit(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Ringkasan pekerjaan yang perlu ditangani.',
                        style: GoogleFonts.inter(
                          fontSize: 11,
                          color: const Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () => _push(const PetugasVerifikasiScreen()),
                  child: Text(
                    'Buka',
                    style: GoogleFonts.inter(
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF3E7D00),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (latestLahan.isEmpty && latestPanen.isEmpty)
            Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'Belum ada antrean verifikasi.',
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(
                  fontSize: 12,
                  color: const Color(0xFF64748B),
                ),
              ),
            )
          else ...[
            ...latestLahan.map(
              (item) => _buildPreviewRow(
                icon: Icons.landscape_rounded,
                badge: 'LAHAN',
                title: item['nama_lahan']?.toString() ?? 'Pengajuan lahan',
                subtitle:
                    '${item['nama_petani'] ?? item['pemilik_lahan'] ?? '-'} - ${item['nama_kecamatan'] ?? '-'}',
              ),
            ),
            ...latestPanen.map(
              (item) => _buildPreviewRow(
                icon: Icons.fact_check_rounded,
                badge: 'PANEN',
                title: item['nama_lahan']?.toString() ?? 'Laporan panen',
                subtitle:
                    '${item['nama_petani'] ?? '-'} - ${item['hasil_panen_label'] ?? '${item['hasil_panen'] ?? 0} Ton'}',
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPreviewRow({
    required IconData icon,
    required String badge,
    required String title,
    required String subtitle,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: const Color(0xFFEDF8DC),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, size: 18, color: const Color(0xFF3E7D00)),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    color: const Color(0xFF64748B),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              badge,
              style: GoogleFonts.inter(
                fontSize: 9,
                fontWeight: FontWeight.bold,
                color: const Color(0xFFD97706),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorBox(String message) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF2F2),
        border: Border.all(color: const Color(0xFFFEE2E2)),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(
        message,
        style: GoogleFonts.inter(
          fontSize: 12,
          color: const Color(0xFFB91C1C),
          height: 1.4,
        ),
      ),
    );
  }

  void _push(Widget screen) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => screen)).then((
      _,
    ) {
      if (mounted) _refresh();
    });
  }
}
