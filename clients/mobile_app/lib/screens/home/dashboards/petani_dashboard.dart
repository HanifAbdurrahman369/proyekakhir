import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../models/user.dart';
import '../../../providers/farming_provider.dart';
import '../tambah_lahan_screen.dart';
import '../lapor_tanam_screen.dart';
import '../edit_lapor_tanam_screen.dart';
import '../lapor_panen_screen.dart';
import '../riwayat_aktivitas_screen.dart';

class PetaniDashboard extends StatefulWidget {
  final User? user;

  const PetaniDashboard({super.key, required this.user});

  @override
  State<PetaniDashboard> createState() => _PetaniDashboardState();
}

class _PetaniDashboardState extends State<PetaniDashboard> {
  int _currentLahanPage = 1;

  @override
  void initState() {
    super.initState();
    // Panggil API saat halaman pertama kali dibuka
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchDashboardData(
        lahanPage: _currentLahanPage,
      );
    });
  }

  void _changeLahanPage(int page) {
    setState(() {
      _currentLahanPage = page;
    });
    context.read<FarmingProvider>().fetchDashboardData(lahanPage: page);
  }

  // Helper untuk format tanggal Indonesia sederhana
  String _formatDateStr(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return '-';
    try {
      final parsed = DateTime.parse(dateStr);
      final months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mei',
        'Jun',
        'Jul',
        'Ags',
        'Sep',
        'Okt',
        'Nov',
        'Des',
      ];
      return '${parsed.day} ${months[parsed.month - 1]} ${parsed.year}';
    } catch (_) {
      return dateStr;
    }
  }

  // Helper untuk format desimal ke koma Indonesia
  String _formatDouble(double value) {
    return value.toStringAsFixed(2).replaceAll('.', ',');
  }

  bool _hasPolygon(Map<String, dynamic> lahan) =>
      lahan['polygon_geojson'] != null ||
      lahan['geojson'] != null ||
      lahan['polygon_area'] != null;

  String _statusLahan(Map<String, dynamic> lahan) {
    if (_hasPolygon(lahan)) return 'TERVERIFIKASI';
    return lahan['status_verifikasi']?.toString() ?? 'PENDING';
  }

  Future<void> _openRiwayatPerbaikan({bool closeDetail = false}) async {
    if (closeDetail) Navigator.pop(context);
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const RiwayatAktivitasScreen()),
    );
    if (!mounted) return;
    context.read<FarmingProvider>().fetchDashboardData(
      lahanPage: _currentLahanPage,
    );
  }

  Future<void> _hubungiPetugas(Map<String, dynamic> lahan) async {
    final rawPhone =
        lahan['petugas_no_hp']?.toString().trim() ?? '6285753510996';
    final phone = rawPhone.startsWith('0')
        ? '62${rawPhone.substring(1)}'
        : rawPhone;
    final message =
        'Halo Petugas, pengajuan lahan sawah saya bernama '
        '*${lahan['nama_lahan'] ?? '-'}* seluas '
        '*${lahan['luas_lahan_hektar'] ?? 0} Ha* telah disetujui. '
        'Saya ingin berkoordinasi untuk pemetaan poligon lahan.';
    final uri = Uri.https('wa.me', '/$phone', {'text': message});
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) &&
        mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('WhatsApp tidak dapat dibuka.')),
      );
    }
  }

  Future<void> _showLahanDetail(Map<String, dynamic> lahan) async {
    final status = _statusLahan(lahan);
    final isRejected = status == 'DITOLAK';
    final isAccepted = status == 'DITERIMA' || status == 'TERVERIFIKASI';
    final isMapped = status == 'TERVERIFIKASI';
    final reason =
        lahan['alasan_penolakan'] ??
        lahan['catatan_verifikasi'] ??
        'Perbaiki data lahan Anda.';

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => DraggableScrollableSheet(
        initialChildSize: 0.82,
        minChildSize: 0.55,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, controller) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
          ),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 8, 8),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Detail Lahan',
                        style: GoogleFonts.outfit(
                          fontSize: 19,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(sheetContext),
                      tooltip: 'Tutup',
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView(
                  controller: controller,
                  padding: const EdgeInsets.all(20),
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'STATUS PENGAJUAN',
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF64748B),
                          ),
                        ),
                        _statusBadge(status),
                      ],
                    ),
                    const SizedBox(height: 20),
                    _flowStep(
                      number: 1,
                      title: 'Isi Formulir',
                      description: 'Data lahan telah berhasil disubmit.',
                      state: 'done',
                    ),
                    _flowStep(
                      number: 2,
                      title: 'Persetujuan Petugas',
                      description: isRejected
                          ? 'Pengajuan lahan ditolak.'
                          : isAccepted
                          ? 'Pengajuan telah disetujui petugas.'
                          : 'Tunggu petugas menyetujui pengajuan Anda.',
                      state: isRejected
                          ? 'rejected'
                          : isAccepted
                          ? 'done'
                          : 'active',
                    ),
                    _flowStep(
                      number: 3,
                      title: 'Hubungi Petugas',
                      description: isMapped
                          ? 'Sudah berkoordinasi dengan petugas.'
                          : isAccepted
                          ? 'Tekan tombol Hubungi Petugas via WhatsApp.'
                          : 'Jika disetujui, hubungi petugas untuk pemetaan.',
                      state: isMapped
                          ? 'done'
                          : isAccepted
                          ? 'active'
                          : 'idle',
                    ),
                    _flowStep(
                      number: 4,
                      title: 'Terverifikasi',
                      description: isMapped
                          ? 'Lahan telah terpetakan dan siap digunakan.'
                          : 'Lahan terpetakan dan siap digunakan untuk panen.',
                      state: isMapped ? 'done' : 'idle',
                      showLine: false,
                    ),
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        children: [
                          _detailItem(
                            'Nama Lahan',
                            lahan['nama_lahan']?.toString() ?? '-',
                          ),
                          _detailItem(
                            'Luas Lahan',
                            '${lahan['luas_lahan_hektar'] ?? 0} Ha',
                          ),
                          _detailItem(
                            'Kecamatan',
                            (lahan['nama_kecamatan'] ??
                                    lahan['kecamatan'] ??
                                    '-')
                                .toString(),
                          ),
                          _detailItem(
                            'Alamat Lengkap',
                            lahan['alamat_detail']?.toString() ?? '-',
                            isLast: true,
                          ),
                        ],
                      ),
                    ),
                    if (isRejected) ...[
                      const SizedBox(height: 14),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF2F2),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFFECACA)),
                        ),
                        child: Text(
                          'Catatan Penolakan:\n$reason',
                          style: GoogleFonts.inter(
                            fontSize: 12,
                            color: const Color(0xFFB91C1C),
                            height: 1.45,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const Divider(height: 1),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 18),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    OutlinedButton(
                      onPressed: () => Navigator.pop(sheetContext),
                      child: const Text('Tutup'),
                    ),
                    if (isRejected) ...[
                      const SizedBox(width: 8),
                      ElevatedButton(
                        onPressed: () =>
                            _openRiwayatPerbaikan(closeDetail: true),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red[700],
                          foregroundColor: Colors.white,
                        ),
                        child: const Text('Perbaiki Pengajuan'),
                      ),
                    ] else if (isAccepted) ...[
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        onPressed: () => _hubungiPetugas(lahan),
                        icon: const Icon(Icons.chat_outlined, size: 18),
                        label: const Text('Hubungi Petugas'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF22C55E),
                          foregroundColor: Colors.white,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statusBadge(String status) {
    final color = status == 'DITOLAK'
        ? Colors.red
        : status == 'PENDING'
        ? Colors.orange
        : Colors.green;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status,
        style: GoogleFonts.inter(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: color[700],
        ),
      ),
    );
  }

  Widget _flowStep({
    required int number,
    required String title,
    required String description,
    required String state,
    bool showLine = true,
  }) {
    final color = state == 'rejected'
        ? Colors.red
        : state == 'done' || state == 'active'
        ? Colors.green
        : Colors.grey;
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            width: 34,
            child: Column(
              children: [
                Container(
                  width: 30,
                  height: 30,
                  decoration: BoxDecoration(
                    color: state == 'active'
                        ? color.withValues(alpha: 0.12)
                        : state == 'idle'
                        ? const Color(0xFFE2E8F0)
                        : color,
                    shape: BoxShape.circle,
                    border: state == 'active'
                        ? Border.all(color: color, width: 2)
                        : null,
                  ),
                  alignment: Alignment.center,
                  child: state == 'done'
                      ? const Icon(Icons.check, size: 17, color: Colors.white)
                      : state == 'rejected'
                      ? const Icon(Icons.close, size: 17, color: Colors.white)
                      : Text(
                          '$number',
                          style: GoogleFonts.inter(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: state == 'active'
                                ? color[700]
                                : const Color(0xFF64748B),
                          ),
                        ),
                ),
                if (showLine)
                  Expanded(
                    child: Container(width: 2, color: const Color(0xFFE2E8F0)),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '$number. $title',
                    style: GoogleFonts.inter(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    description,
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      color: const Color(0xFF64748B),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _detailItem(String label, String value, {bool isLast = false}) =>
      Padding(
        padding: EdgeInsets.only(bottom: isLast ? 0 : 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 110,
              child: Text(
                label.toUpperCase(),
                style: GoogleFonts.inter(
                  fontSize: 9,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF94A3B8),
                ),
              ),
            ),
            Expanded(
              child: Text(
                value,
                style: GoogleFonts.inter(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF334155),
                ),
              ),
            ),
          ],
        ),
      );

  @override
  Widget build(BuildContext context) {
    final farmingProvider = context.watch<FarmingProvider>();
    final user = widget.user;
    final roleId = user?.roleId ?? 1;
    final roleName = roleId == 5 ? 'Brigade Pangan' : 'Kelompok Tani';

    final lahanList = farmingProvider.lahanData['data'] as List<dynamic>? ?? [];

    if (farmingProvider.isLoading &&
        farmingProvider.lahanData['data'].isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    if (farmingProvider.errorMessage != null &&
        farmingProvider.lahanData['data'].isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.error_outline_rounded,
                size: 48,
                color: Colors.red,
              ),
              const SizedBox(height: 16),
              Text(
                farmingProvider.errorMessage!,
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(color: Colors.grey[800]),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  context.read<FarmingProvider>().fetchDashboardData(
                    lahanPage: _currentLahanPage,
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[800],
                ),
                child: Text(
                  'Coba Lagi',
                  style: GoogleFonts.inter(color: Colors.white),
                ),
              ),
            ],
          ),
        ),
      );
    }

    final totalLahan = farmingProvider.lahanData['total'] ?? 0;
    final activeCycles = farmingProvider.mySiklusTanam;
    final totalProduksi = farmingProvider.totalProduksi;

    return RefreshIndicator(
      onRefresh: () async {
        await context.read<FarmingProvider>().fetchDashboardData(
          lahanPage: _currentLahanPage,
        );
      },
      color: Colors.green[800],
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // 1. Profile Card
            // _buildProfileCard(user, roleName),
            // const SizedBox(height: 20),

            // 2. Header Dashboard
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEDF8DC),
                    border: Border.all(color: const Color(0xFFDFECCC)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    roleName,
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF3E7D00),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Dashboard aktivitas pertanian',
                  style: GoogleFonts.outfit(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Pantau proses tanam, pemupukan, dan riwayat panen yang terhubung dengan akun Anda.',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    color: Colors.grey[600],
                    height: 1.4,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // 3. Statistics Cards (Lahan Terdaftar & Produksi Tahun Ini)
            LayoutBuilder(
              builder: (context, constraints) {
                final cardWidth =
                    (constraints.maxWidth - 16) / 2; // 2 cards side-by-side
                return Row(
                  children: [
                    _buildStatCard(
                      title: 'LAHAN TERDAFTAR',
                      value: '$totalLahan',
                      desc: 'Pengajuan lahan pada akun Anda',
                      width: cardWidth,
                    ),
                    const SizedBox(width: 16),
                    _buildStatCard(
                      title: 'PRODUKSI TAHUN INI',
                      value: '${_formatDouble(totalProduksi)} Ton',
                      desc: 'Hanya hasil panen yang disetujui petugas',
                      width: cardWidth,
                      isDark: true,
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 16),

            // 4. Action Buttons Row (Sejajar di bawah 2 card statistik utama)
            Row(
              children: [
                Expanded(
                  child: _buildActionButton(
                    label: 'Tambah Lahan',
                    icon: Icons.add_location_alt_rounded,
                    onPressed: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const TambahLahanScreen(),
                        ),
                      );
                      if (!context.mounted) return;
                      context.read<FarmingProvider>().fetchDashboardData(
                        lahanPage: _currentLahanPage,
                      );
                    },
                    textColor: const Color(0xFF3E7D00),
                    bgColor: Colors.white,
                    borderColor: const Color(0xFF3E7D00),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildActionButton(
                    label: 'Lapor Tanam',
                    icon: Icons.grass_rounded,
                    onPressed: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const LaporTanamScreen(),
                        ),
                      );
                      if (!context.mounted) return;
                      context.read<FarmingProvider>().fetchDashboardData(
                        lahanPage: _currentLahanPage,
                      );
                    },
                    textColor: Colors.white,
                    bgColor: const Color(0xFF3E7D00),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildActionButton(
                    label: 'Lapor Hasil Panen',
                    icon: Icons.check_circle_outline_rounded,
                    onPressed: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const LaporPanenScreen(),
                        ),
                      );
                      if (!context.mounted) return;
                      context.read<FarmingProvider>().fetchDashboardData(
                        lahanPage: _currentLahanPage,
                      );
                    },
                    textColor: Colors.white,
                    bgColor: const Color(0xFF203C10),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // 5. Statistics Card (Proses Aktif)
            LayoutBuilder(
              builder: (context, constraints) {
                return _buildStatCard(
                  title: 'PROSES AKTIF',
                  value: '${activeCycles.length}',
                  desc: 'Siklus tanam berjalan yang terhubung dengan akun Anda',
                  width: constraints.maxWidth,
                  isLightGreen: true,
                );
              },
            ),
            const SizedBox(height: 24),

            // 7. Active cycles (Padi dalam masa tanam)
            _buildActiveCyclesSection(activeCycles),
            const SizedBox(height: 24),

            // 7. Lahan List Section
            _buildLahanListSection(lahanList, farmingProvider.lahanData),
          ],
        ),
      ),
    );
  }

  Widget _buildActionButton({
    required String label,
    required VoidCallback? onPressed,
    required Color textColor,
    required Color bgColor,
    Color? borderColor,
    IconData? icon,
  }) {
    final isDisabled = onPressed == null;
    return OutlinedButton(
      onPressed: onPressed,
      style: OutlinedButton.styleFrom(
        foregroundColor: textColor,
        backgroundColor: bgColor,
        disabledForegroundColor: Colors.grey[500],
        disabledBackgroundColor: Colors.grey[200],
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        side: borderColor != null && !isDisabled
            ? BorderSide(color: borderColor, width: 1.5)
            : BorderSide.none,
        elevation: 0,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(
              icon,
              size: 13,
              color: isDisabled ? Colors.grey[500] : textColor,
            ),
            const SizedBox(width: 4),
          ],
          Flexible(
            child: Text(
              label,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: GoogleFonts.inter(
                fontSize: 10,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required String desc,
    required double width,
    bool isDark = false,
    bool isLightGreen = false,
  }) {
    Color cardBg = Colors.white;
    Color borderCol = const Color(0xFFE2E8F0);
    Color titleCol = const Color(0xFF64748B);
    Color valCol = const Color(0xFF14280B);
    Color descCol = const Color(0xFF64748B);

    if (isDark) {
      cardBg = const Color(0xFF203C10);
      borderCol = Colors.transparent;
      titleCol = Colors.white70;
      valCol = Colors.white;
      descCol = Colors.white70;
    } else if (isLightGreen) {
      cardBg = const Color(0xFFF7FCED);
      borderCol = const Color(0xFFDFECCC);
      titleCol = const Color(0xFF3E7D00);
      valCol = const Color(0xFF14280B);
      descCol = const Color(0xFF475569);
    }

    return Container(
      width: width,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardBg,
        border: borderCol != Colors.transparent
            ? Border.all(color: borderCol)
            : null,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: GoogleFonts.inter(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: titleCol,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: GoogleFonts.outfit(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: valCol,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            desc,
            style: GoogleFonts.inter(fontSize: 11, color: descCol, height: 1.3),
          ),
        ],
      ),
    );
  }

  Widget _buildActiveCyclesSection(List<dynamic> activeCycles) {
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
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(
                            Icons.grass_rounded,
                            color: Color(0xFF3E7D00),
                            size: 18,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Padi dalam masa tanam',
                            style: GoogleFonts.outfit(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF14280B),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Progres dihitung otomatis sampai estimasi masa panen.',
                        style: GoogleFonts.inter(
                          fontSize: 11,
                          color: Colors.grey[500],
                          height: 1.3,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEDF8DC),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${activeCycles.length} aktif',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF3E7D00),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (activeCycles.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 16),
              child: Center(
                child: Text(
                  'Belum ada proses tanam aktif.',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    color: Colors.grey[500],
                  ),
                ),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: activeCycles.length,
              separatorBuilder: (context, index) =>
                  const Divider(height: 1, color: Color(0xFFE2E8F0)),
              itemBuilder: (context, index) {
                final cycle = activeCycles[index];
                final progress =
                    double.tryParse(cycle['progress_persen'].toString()) ?? 0.0;
                final remainingDays = cycle['hari_tersisa'] ?? 0;

                return Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  cycle['nama_lahan'] ?? 'Lahan',
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF14280B),
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Bibit: ${cycle['nama_bibit'] ?? '-'} · Laporan tanam',
                                  style: GoogleFonts.inter(
                                    fontSize: 12,
                                    color: Colors.grey[500],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFEDF8DC),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.calendar_today_rounded,
                                  size: 9,
                                  color: Color(0xFF3E7D00),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  'Panen ${_formatDateStr(cycle['estimasi_tanggal_panen'])}',
                                  style: GoogleFonts.inter(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF3E7D00),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 4),
                          InkWell(
                            onTap: () async {
                              await Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      EditLaporTanamScreen(cycleData: cycle),
                                ),
                              );
                              if (!context.mounted) return;
                              context
                                  .read<FarmingProvider>()
                                  .fetchDashboardData(
                                    lahanPage: _currentLahanPage,
                                  );
                            },
                            child: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: Colors.blue[50],
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                Icons.edit_note_rounded,
                                size: 16,
                                color: Colors.blue[700],
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: progress / 100,
                          backgroundColor: Colors.grey[200],
                          color: const Color(0xFF5EA500),
                          minHeight: 10,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            '${progress.toStringAsFixed(0)}% masa tanam',
                            style: GoogleFonts.inter(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF3E7D00),
                            ),
                          ),
                          Text(
                            '$remainingDays hari tersisa',
                            style: GoogleFonts.inter(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _buildLahanListSection(
    List<dynamic> lahanList,
    Map<String, dynamic> lahanData,
  ) {
    final currentPage = lahanData['current_page'] ?? 1;
    final lastPage = lahanData['last_page'] ?? 1;

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
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Daftar lahan sawah',
                  style: GoogleFonts.outfit(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'Status pengajuan dan catatan verifikasi petugas.',
                  style: GoogleFonts.inter(
                    fontSize: 10,
                    color: Colors.grey[500],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (lahanList.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 16),
              child: Center(
                child: Text(
                  'Belum ada lahan yang diajukan.',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    color: Colors.grey[500],
                  ),
                ),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: lahanList.length,
              separatorBuilder: (context, index) =>
                  const Divider(height: 1, color: Color(0xFFE2E8F0)),
              itemBuilder: (context, index) {
                final lahan = lahanList[index] as Map<String, dynamic>;
                final status = _statusLahan(lahan);

                Color statusBg = const Color(0xFFFEF3C7);
                Color statusTxt = const Color(0xFFD97706);
                if (status == 'DITERIMA') {
                  statusBg = const Color(0xFFD1FAE5);
                  statusTxt = const Color(0xFF059669);
                } else if (status == 'DITOLAK') {
                  statusBg = const Color(0xFFFEE2E2);
                  statusTxt = const Color(0xFFDC2626);
                }

                final detail = lahan['alamat_detail'] ?? '-';
                final area = lahan['luas_lahan_hektar'] ?? 0;

                return InkWell(
                  onTap: () => _showLahanDetail(lahan),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    lahan['nama_lahan'] ?? 'Lahan',
                                    style: GoogleFonts.inter(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: const Color(0xFF14280B),
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    '$detail · $area Ha',
                                    style: GoogleFonts.inter(
                                      fontSize: 11,
                                      color: Colors.grey[500],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: statusBg,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                status.replaceAll('_', ' '),
                                style: GoogleFonts.inter(
                                  fontSize: 9,
                                  fontWeight: FontWeight.bold,
                                  color: statusTxt,
                                ),
                              ),
                            ),
                          ],
                        ),
                        if (status == 'DITOLAK') ...[
                          const SizedBox(height: 12),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF2F2),
                              border: Border.all(
                                color: const Color(0xFFFEE2E2),
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  lahan['alasan_penolakan'] ??
                                      lahan['catatan_verifikasi'] ??
                                      'Pengajuan perlu diperbaiki.',
                                  style: GoogleFonts.inter(
                                    fontSize: 11,
                                    color: const Color(0xFFB91C1C),
                                    height: 1.4,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                TextButton(
                                  onPressed: _openRiwayatPerbaikan,
                                  style: TextButton.styleFrom(
                                    padding: EdgeInsets.zero,
                                    minimumSize: const Size(0, 32),
                                    tapTargetSize:
                                        MaterialTapTargetSize.shrinkWrap,
                                    foregroundColor: const Color(0xFFB91C1C),
                                  ),
                                  child: const Text('Perbaiki pengajuan'),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          // Pagination controls (Web-like pagination)
          if (lastPage > 1) ...[
            const Divider(height: 1, color: Color(0xFFE2E8F0)),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Halaman $currentPage dari $lastPage',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      color: Colors.grey[600],
                    ),
                  ),
                  Row(
                    children: [
                      if (currentPage > 1)
                        TextButton(
                          onPressed: () => _changeLahanPage(currentPage - 1),
                          child: Text(
                            'Sebelumnya',
                            style: GoogleFonts.inter(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF3E7D00),
                            ),
                          ),
                        ),
                      if (currentPage < lastPage)
                        TextButton(
                          onPressed: () => _changeLahanPage(currentPage + 1),
                          child: Text(
                            'Selanjutnya',
                            style: GoogleFonts.inter(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF3E7D00),
                            ),
                          ),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
