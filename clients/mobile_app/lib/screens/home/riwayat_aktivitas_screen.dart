import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../providers/farming_provider.dart';
import 'input_pemupukan_sheet.dart';

class RiwayatAktivitasScreen extends StatefulWidget {
  const RiwayatAktivitasScreen({super.key});

  @override
  State<RiwayatAktivitasScreen> createState() => _RiwayatAktivitasScreenState();
}

class _RiwayatAktivitasScreenState extends State<RiwayatAktivitasScreen> {
  @override
  void initState() {
    super.initState();
    // Memuat data awal dari backend saat halaman dibuka
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadLahanData(1);
      _loadPanenData(1);
      _loadPupukData(1);
      context.read<FarmingProvider>().fetchLahanMetadata();
    });
  }

  void _loadLahanData(int page) {
    context.read<FarmingProvider>().fetchRiwayatLahan(page: page);
  }

  void _loadPanenData(int page) {
    context.read<FarmingProvider>().fetchRiwayatPanen(page: page);
  }

  void _loadPupukData(int page) {
    context.read<FarmingProvider>().fetchRiwayatPupuk(page: page);
  }

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

  String _formatNumber(dynamic val) {
    if (val == null) return '0';
    if (val is num) {
      return val
          .toStringAsFixed(2)
          .replaceAll('.', ',')
          .replaceFirst(RegExp(r',00$'), '');
    }
    return val.toString();
  }

  Future<void> _showContactOfficerDialog(
    BuildContext context,
    String namaLahan,
  ) async {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(28),
          ),
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.white,
          titlePadding: const EdgeInsets.only(
            left: 24,
            right: 16,
            top: 16,
            bottom: 0,
          ),
          title: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  'Hubungi Petugas Pemetaan',
                  style: GoogleFonts.outfit(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF1E293B),
                  ),
                ),
              ),
              IconButton(
                onPressed: () => Navigator.pop(context),
                icon: const Icon(Icons.close_rounded),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 60,
                height: 60,
                decoration: const BoxDecoration(
                  color: Color(0xFFECFDF5),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.map_outlined,
                  color: Color(0xFF059669),
                  size: 32,
                ),
              ),
              const SizedBox(height: 16),
              RichText(
                textAlign: TextAlign.center,
                text: TextSpan(
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    color: const Color(0xFF475569),
                    height: 1.5,
                  ),
                  children: [
                    const TextSpan(
                      text: 'Untuk melanjutkan ke tahap pemetaan lahan ',
                    ),
                    TextSpan(
                      text: namaLahan,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF059669),
                      ),
                    ),
                    const TextSpan(
                      text:
                          ', silakan segera menghubungi petugas dinas pertanian.',
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'Anda dapat berkoordinasi langsung untuk menjadwalkan kunjungan pemetaan lahan sawah Anda.',
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(
                  fontSize: 11,
                  color: const Color(0xFF94A3B8),
                  height: 1.4,
                ),
              ),
            ],
          ),
          actionsAlignment: MainAxisAlignment.center,
          actionsPadding: const EdgeInsets.only(
            left: 24,
            right: 24,
            bottom: 24,
          ),
          actions: [
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () async {
                  final url = Uri.parse('https://wa.me/6285753510996');
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  } else {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Tidak dapat membuka WhatsApp.'),
                        ),
                      );
                    }
                  }
                },
                icon: const Icon(Icons.chat_bubble_outline_rounded, size: 18),
                label: Text(
                  'Hubungi via WhatsApp',
                  style: GoogleFonts.inter(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final farmingProvider = context.watch<FarmingProvider>();

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        backgroundColor: const Color(0xFFF4F9F4),
        appBar: AppBar(
          title: Text(
            'Riwayat Aktivitas',
            style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
          ),
          backgroundColor: Colors.green[800],
          foregroundColor: Colors.white,
          elevation: 2,
          bottom: TabBar(
            indicatorColor: Colors.white,
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white70,
            labelStyle: GoogleFonts.inter(
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
            unselectedLabelStyle: GoogleFonts.inter(
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
            tabs: const [
              Tab(text: 'Lahan'),
              Tab(text: 'Panen'),
              Tab(text: 'Pemupukan'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildLahanTab(farmingProvider),
            _buildPanenTab(farmingProvider),
            _buildPupukTab(farmingProvider),
          ],
        ),
        floatingActionButton: Builder(
          builder: (BuildContext context) {
            final tabController = DefaultTabController.of(context);
            return AnimatedBuilder(
              animation: tabController,
              builder: (context, child) {
                if (tabController.index == 2) {
                  return FloatingActionButton.extended(
                    onPressed: () => _showInputPemupukanSheet(context),
                    backgroundColor: Colors.green[800],
                    foregroundColor: Colors.white,
                    icon: const Icon(Icons.add_rounded),
                    label: Text(
                      'Pemupukan',
                      style: GoogleFonts.inter(fontWeight: FontWeight.bold),
                    ),
                  );
                }
                return const SizedBox.shrink();
              },
            );
          },
        ),
      ),
    );
  }

  // ================= DIALOG / BOTTOM SHEET PEMUPUKAN =================
  void _showInputPemupukanSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (BuildContext context) {
        return const InputPemupukanSheet();
      },
    );
  }

  // ================= TAB 1: RIWAYAT LAHAN =================
  Widget _buildLahanTab(FarmingProvider provider) {
    if (provider.isRiwayatLahanLoading &&
        provider.riwayatLahanData['data'].isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    final dataMap = provider.riwayatLahanData;
    final list = dataMap['data'] as List<dynamic>? ?? [];
    final currentPage = dataMap['current_page'] ?? 1;
    final lastPage = dataMap['last_page'] ?? 1;

    if (list.isEmpty) {
      return _buildEmptyState('Belum ada catatan pengajuan lahan baru.');
    }

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async => _loadLahanData(1),
            color: Colors.green[800],
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              itemBuilder: (context, index) {
                final item = list[index];
                final statusRaw = item['status_verifikasi'] ?? 'PENDING';
                final statusSpasial =
                    item['status_spasial'] ?? 'BELUM_DIPETAKAN';
                final catatanLahan =
                    item['catatan_verifikasi'] ??
                    item['alasan_penolakan'] ??
                    '';

                String statusText = statusRaw;
                if (statusRaw == 'DITERIMA') {
                  statusText = statusSpasial == 'SUDAH_DIPETAKAN'
                      ? 'TERVERIFIKASI'
                      : 'DISETUJUI';
                }

                Color badgeBg;
                Color badgeText;
                if (statusRaw == 'DITERIMA') {
                  badgeBg = const Color(0xFFEDF8DC);
                  badgeText = const Color(0xFF3E7D00);
                } else if (statusRaw == 'DITOLAK') {
                  badgeBg = const Color(0xFFFEE2E2);
                  badgeText = const Color(0xFFDC2626);
                } else {
                  badgeBg = const Color(0xFFFEF3C7);
                  badgeText = const Color(0xFFD97706);
                }

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  color: Colors.white,
                  surfaceTintColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                item['nama_lahan'] ?? '-',
                                style: GoogleFonts.outfit(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF1E293B),
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: badgeBg,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                statusText.replaceAll('_', ' '),
                                style: GoogleFonts.inter(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: badgeText,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        _buildDetailRow(
                          Icons.pin_drop_rounded,
                          'Alamat',
                          item['alamat_detail'] ?? '-',
                        ),
                        const SizedBox(height: 6),
                        _buildDetailRow(
                          Icons.square_foot_rounded,
                          'Luas Lahan',
                          '${_formatNumber(item['luas_lahan_hektar'])} Ha',
                        ),
                        if (catatanLahan.toString().isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: statusRaw == 'DITOLAK'
                                  ? const Color(0xFFFFF5F5)
                                  : const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: statusRaw == 'DITOLAK'
                                    ? const Color(0xFFFEE2E2)
                                    : const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Text(
                              'Catatan: $catatanLahan',
                              style: GoogleFonts.inter(
                                fontSize: 11,
                                color: statusRaw == 'DITOLAK'
                                    ? const Color(0xFF991B1B)
                                    : Colors.grey[700],
                                fontStyle: FontStyle.italic,
                              ),
                            ),
                          ),
                        ],
                        if (statusRaw == 'DITOLAK') ...[
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: () =>
                                  _showResubmitLahanDialog(context, item),
                              icon: const Icon(
                                Icons.edit_note_rounded,
                                size: 18,
                              ),
                              label: Text(
                                'Perbaiki Pengajuan Lahan',
                                style: GoogleFonts.inter(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                ),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF3E7D00),
                                foregroundColor: Colors.white,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(
                                  vertical: 12,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                              ),
                            ),
                          ),
                        ],
                        if (statusRaw == 'DITERIMA' &&
                            statusSpasial == 'BELUM_DIPETAKAN') ...[
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: () => _showContactOfficerDialog(
                                context,
                                item['nama_lahan'] ?? '-',
                              ),
                              icon: const Icon(
                                Icons.chat_bubble_outline_rounded,
                                size: 18,
                              ),
                              label: Text(
                                'Hubungi Petugas',
                                style: GoogleFonts.inter(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                ),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF059669),
                                foregroundColor: Colors.white,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(
                                  vertical: 12,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
        _buildPaginationController(currentPage, lastPage, _loadLahanData),
      ],
    );
  }

  // ================= TAB 2: RIWAYAT PANEN =================
  Widget _buildPanenTab(FarmingProvider provider) {
    if (provider.isRiwayatPanenLoading &&
        provider.riwayatPanenData['data'].isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    final dataMap = provider.riwayatPanenData;
    final list = dataMap['data'] as List<dynamic>? ?? [];
    final currentPage = dataMap['current_page'] ?? 1;
    final lastPage = dataMap['last_page'] ?? 1;

    if (list.isEmpty) {
      return _buildEmptyState('Belum ada riwayat panen yang diinput.');
    }

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async => _loadPanenData(1),
            color: Colors.green[800],
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              itemBuilder: (context, index) {
                final item = list[index];
                final statusRaw = item['status_verifikasi'] ?? 'PENDING';
                final catatan = item['catatan_verifikasi'] ?? '';

                Color badgeBg;
                Color badgeText;
                if (statusRaw == 'DITERIMA') {
                  badgeBg = const Color(0xFFEDF8DC);
                  badgeText = const Color(0xFF3E7D00);
                } else if (statusRaw == 'DITOLAK') {
                  badgeBg = const Color(0xFFFEE2E2);
                  badgeText = const Color(0xFFDC2626);
                } else {
                  badgeBg = const Color(0xFFFEF3C7);
                  badgeText = const Color(0xFFD97706);
                }

                final lahan = item['lahan'] as Map<String, dynamic>?;
                final bibit = item['bibit'] as Map<String, dynamic>?;

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  color: Colors.white,
                  surfaceTintColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                lahan?['nama_lahan'] ?? '-',
                                style: GoogleFonts.outfit(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF1E293B),
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: badgeBg,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                statusRaw,
                                style: GoogleFonts.inter(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: badgeText,
                                ),
                              ),
                            ),
                          ],
                        ),
                        if (lahan?['luas_lahan_hektar'] != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            'Luas Lahan: ${_formatNumber(lahan?['luas_lahan_hektar'])} Ha',
                            style: GoogleFonts.inter(
                              fontSize: 11,
                              color: Colors.grey[500],
                            ),
                          ),
                        ],
                        const Divider(height: 20, color: Color(0xFFF1F5F9)),
                        _buildDetailRow(
                          Icons.grass_rounded,
                          'Bibit',
                          bibit?['nama_bibit'] ?? '-',
                        ),
                        const SizedBox(height: 6),
                        _buildDetailRow(
                          Icons.calendar_month_rounded,
                          'Tgl Tanam',
                          _formatDateStr(item['tanggal_tanam']),
                        ),
                        const SizedBox(height: 6),
                        _buildDetailRow(
                          Icons.task_alt_rounded,
                          'Tgl Panen',
                          _formatDateStr(item['tanggal_panen']),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Hasil Panen',
                              style: GoogleFonts.inter(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: Colors.grey[600],
                              ),
                            ),
                            Text(
                              '${_formatNumber(item['hasil_panen'])} Ton',
                              style: GoogleFonts.outfit(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF3E7D00),
                              ),
                            ),
                          ],
                        ),
                        if (catatan.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: statusRaw == 'DITOLAK'
                                  ? const Color(0xFFFFF5F5)
                                  : const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: statusRaw == 'DITOLAK'
                                    ? const Color(0xFFFEE2E2)
                                    : const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Text(
                              'Catatan: $catatan',
                              style: GoogleFonts.inter(
                                fontSize: 11,
                                color: statusRaw == 'DITOLAK'
                                    ? const Color(0xFF991B1B)
                                    : Colors.grey[700],
                                fontStyle: FontStyle.italic,
                              ),
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () =>
                                _showUpdatePanenDialog(context, item),
                            icon: const Icon(
                              Icons.edit_note_rounded,
                              size: 18,
                            ),
                            label: Text(
                              'Perbaiki Laporan Panen',
                              style: GoogleFonts.inter(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF3E7D00),
                              foregroundColor: Colors.white,
                              elevation: 0,
                              padding: const EdgeInsets.symmetric(
                                vertical: 12,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
        _buildPaginationController(currentPage, lastPage, _loadPanenData),
      ],
    );
  }

  // ================= TAB 3: RIWAYAT PEMUPUKAN =================
  Widget _buildPupukTab(FarmingProvider provider) {
    if (provider.isRiwayatPupukLoading &&
        provider.riwayatPupukData['data'].isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    final dataMap = provider.riwayatPupukData;
    final list = dataMap['data'] as List<dynamic>? ?? [];
    final currentPage = dataMap['current_page'] ?? 1;
    final lastPage = dataMap['last_page'] ?? 1;

    if (list.isEmpty) {
      return _buildEmptyState('Belum ada catatan pemupukan yang disimpan.');
    }

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async => _loadPupukData(1),
            color: Colors.green[800],
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              itemBuilder: (context, index) {
                final item = list[index];

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  color: Colors.white,
                  surfaceTintColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item['nama_lahan'] ?? '-',
                          style: GoogleFonts.outfit(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF1E293B),
                          ),
                        ),
                        const Divider(height: 20, color: Color(0xFFF1F5F9)),
                        _buildDetailRow(
                          Icons.science_rounded,
                          'Jenis Pupuk',
                          item['nama_pupuk'] ?? '-',
                        ),
                        const SizedBox(height: 6),
                        _buildDetailRow(
                          Icons.category_rounded,
                          'Tipe Pupuk',
                          item['tipe_pupuk'] ?? '-',
                        ),
                        const SizedBox(height: 6),
                        _buildDetailRow(
                          Icons.calendar_month_rounded,
                          'Tgl Pemupukan',
                          _formatDateStr(item['tanggal_pemupukan']),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Takaran Pupuk',
                              style: GoogleFonts.inter(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: Colors.grey[600],
                              ),
                            ),
                            Text(
                              '${_formatNumber(item['takaran'])} Kg',
                              style: GoogleFonts.outfit(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF0F766E),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
        _buildPaginationController(currentPage, lastPage, _loadPupukData),
      ],
    );
  }

  // ================= UTILS WIDGET =================

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: Colors.grey[500]),
        const SizedBox(width: 8),
        Text(
          '$label:',
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: Colors.grey[500],
          ),
        ),
        const SizedBox(width: 4),
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
    );
  }

  Widget _buildEmptyState(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.history_rounded, size: 64, color: Colors.grey[300]),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.inter(
                fontSize: 14,
                color: Colors.grey[500],
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaginationController(
    int currentPage,
    int lastPage,
    Function(int) onPageChanged,
  ) {
    if (lastPage <= 1) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          ElevatedButton.icon(
            onPressed: currentPage > 1
                ? () => onPageChanged(currentPage - 1)
                : null,
            icon: const Icon(Icons.chevron_left_rounded, size: 18),
            label: Text(
              'Sebelumnya',
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
            style: ElevatedButton.styleFrom(
              elevation: 0,
              backgroundColor: const Color(0xFFE2E8F0),
              foregroundColor: const Color(0xFF334155),
              disabledBackgroundColor: Colors.grey[100],
              disabledForegroundColor: Colors.grey[400],
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
          Text(
            'Halaman $currentPage dari $lastPage',
            style: GoogleFonts.inter(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          ElevatedButton.icon(
            onPressed: currentPage < lastPage
                ? () => onPageChanged(currentPage + 1)
                : null,
            icon: const Icon(Icons.chevron_right_rounded, size: 18),
            label: Text(
              'Selanjutnya',
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
            style: ElevatedButton.styleFrom(
              elevation: 0,
              backgroundColor: const Color(0xFF3E7D00),
              foregroundColor: Colors.white,
              disabledBackgroundColor: Colors.grey[100],
              disabledForegroundColor: Colors.grey[400],
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showResubmitLahanDialog(
    BuildContext context,
    Map<String, dynamic> item,
  ) {
    final farmingProvider = context.read<FarmingProvider>();

    if (farmingProvider.kecamatanList.isEmpty) {
      farmingProvider.fetchLahanMetadata();
    }

    final formKey = GlobalKey<FormState>();
    final namaLahanController = TextEditingController(
      text: item['nama_lahan'] ?? '',
    );
    final luasLahanController = TextEditingController(
      text: item['luas_lahan_hektar']?.toString() ?? '',
    );
    final alamatController = TextEditingController(
      text: item['alamat_detail'] ?? '',
    );

    String? selectedKecId = item['kecamatan_id']?.toString();
    String? selectedKelId = item['kelurahan_id']?.toString();
    String? selectedTipeLahanId = item['tipe_lahan_id']?.toString();
    String? selectedPetaniId = item['petani_id']?.toString();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            final provider = context.watch<FarmingProvider>();

            if (provider.isLoading && provider.kecamatanList.isEmpty) {
              return const AlertDialog(
                content: SizedBox(
                  height: 100,
                  child: Center(
                    child: CircularProgressIndicator(color: Colors.green),
                  ),
                ),
              );
            }

            final filteredKelurahan = provider.kelurahanList.where((k) {
              return k['kecamatan_id'].toString() == selectedKecId;
            }).toList();

            if (selectedKelId != null &&
                !filteredKelurahan.any(
                  (k) => k['id'].toString() == selectedKelId,
                )) {
              selectedKelId = null;
            }

            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              title: Text(
                'Perbaiki Pengajuan Lahan',
                style: GoogleFonts.outfit(
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF14280B),
                ),
              ),
              content: SizedBox(
                width: double.maxFinite,
                child: Form(
                  key: formKey,
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        TextFormField(
                          controller: namaLahanController,
                          style: GoogleFonts.inter(fontSize: 14),
                          decoration: const InputDecoration(
                            labelText: 'Nama Lahan',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          validator: (value) =>
                              value == null || value.trim().isEmpty
                              ? 'Nama lahan wajib diisi'
                              : null,
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          isExpanded: true,
                          initialValue: selectedKecId,
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: Colors.black,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Kecamatan',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          items: provider.kecamatanList.map((k) {
                            return DropdownMenuItem<String>(
                              value: k['id'].toString(),
                              child: Text(
                                k['nama_kecamatan'] ?? k['nama'] ?? '',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            setDialogState(() {
                              selectedKecId = val;
                              selectedKelId = null;
                            });
                          },
                          validator: (value) =>
                              value == null ? 'Kecamatan wajib dipilih' : null,
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          isExpanded: true,
                          initialValue: selectedKelId,
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: Colors.black,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Kelurahan',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          items: filteredKelurahan.map((k) {
                            return DropdownMenuItem<String>(
                              value: k['id'].toString(),
                              child: Text(
                                k['nama_kelurahan'] ?? k['nama'] ?? '',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            setDialogState(() {
                              selectedKelId = val;
                            });
                          },
                          validator: (value) =>
                              value == null ? 'Kelurahan wajib dipilih' : null,
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          isExpanded: true,
                          initialValue: selectedTipeLahanId,
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: Colors.black,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Tipe Lahan',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          items: provider.tipeLahanList.map((t) {
                            return DropdownMenuItem<String>(
                              value: t['id'].toString(),
                              child: Text(
                                t['nama_tipe'] ?? t['nama'] ?? '',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            setDialogState(() {
                              selectedTipeLahanId = val;
                            });
                          },
                          validator: (value) =>
                              value == null ? 'Tipe lahan wajib dipilih' : null,
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          isExpanded: true,
                          initialValue: selectedPetaniId,
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: Colors.black,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Petani Penggarap',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          items: provider.petaniSpasialList.map((p) {
                            return DropdownMenuItem<String>(
                              value: p['id'].toString(),
                              child: Text(
                                p['nama_lengkap'] ?? p['nama'] ?? '',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            setDialogState(() {
                              selectedPetaniId = val;
                            });
                          },
                          validator: (value) =>
                              value == null ? 'Petani wajib dipilih' : null,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: luasLahanController,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          style: GoogleFonts.inter(fontSize: 14),
                          decoration: const InputDecoration(
                            labelText: 'Luas Lahan (Ha)',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Luas lahan wajib diisi';
                            }
                            if (double.tryParse(value) == null) {
                              return 'Masukkan angka yang valid';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: alamatController,
                          maxLines: 2,
                          style: GoogleFonts.inter(fontSize: 14),
                          decoration: const InputDecoration(
                            labelText: 'Alamat Detail',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          validator: (value) =>
                              value == null || value.trim().isEmpty
                              ? 'Alamat wajib diisi'
                              : null,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text(
                    'Batal',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ),
                ElevatedButton(
                  onPressed: provider.isLoading
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;
                          final payload = {
                            'nama_lahan': namaLahanController.text.trim(),
                            'kecamatan_id': int.tryParse(selectedKecId ?? ''),
                            'kelurahan_id': int.tryParse(selectedKelId ?? ''),
                            'tipe_lahan_id': int.tryParse(
                              selectedTipeLahanId ?? '',
                            ),
                            'luas_lahan_hektar': double.tryParse(
                              luasLahanController.text.trim(),
                            ),
                            'petani_id': int.tryParse(selectedPetaniId ?? ''),
                            'alamat_detail': alamatController.text.trim(),
                          };
                          final success = await provider.resubmitLahan(
                            item['id'],
                            payload,
                          );
                          if (!context.mounted) return;
                          if (success) {
                            Navigator.pop(context);
                            _loadLahanData(1);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text(
                                  'Perbaikan pengajuan lahan berhasil dikirim.',
                                ),
                                backgroundColor: Colors.green,
                              ),
                            );
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  provider.errorMessage ??
                                      'Gagal mengirim perbaikan.',
                                ),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3E7D00),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: provider.isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text('Kirim Perbaikan'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showUpdatePanenDialog(BuildContext context, Map<String, dynamic> item) {
    final formKey = GlobalKey<FormState>();
    final hasilController = TextEditingController(
      text: item['hasil_panen']?.toString() ?? '',
    );
    DateTime selectedDate = item['tanggal_panen'] != null
        ? DateTime.tryParse(item['tanggal_panen'].toString()) ?? DateTime.now()
        : DateTime.now();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            final provider = context.watch<FarmingProvider>();

            Future<void> pickDate() async {
              final DateTime? picked = await showDatePicker(
                context: context,
                initialDate: selectedDate,
                firstDate: DateTime(2020),
                lastDate: DateTime.now(),
                builder: (context, child) {
                  return Theme(
                    data: Theme.of(context).copyWith(
                      colorScheme: ColorScheme.light(
                        primary: Colors.green[800]!,
                        onPrimary: Colors.white,
                        onSurface: Colors.black,
                      ),
                    ),
                    child: child!,
                  );
                },
              );
              if (picked != null && picked != selectedDate) {
                setDialogState(() {
                  selectedDate = picked;
                });
              }
            }

            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              title: Text(
                'Perbaiki Laporan Panen',
                style: GoogleFonts.outfit(
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF14280B),
                ),
              ),
              content: Form(
                key: formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Lahan: ${item['lahan']?['nama_lahan'] ?? '-'}',
                      style: GoogleFonts.inter(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[700],
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: hasilController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      style: GoogleFonts.inter(fontSize: 14),
                      decoration: const InputDecoration(
                        labelText: 'Hasil Panen (Ton)',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Hasil panen wajib diisi';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Masukkan angka yang valid';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    InkWell(
                      onTap: pickDate,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 12,
                        ),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                'Tanggal Panen: ${_formatDateStr(selectedDate.toIso8601String())}',
                                style: GoogleFonts.inter(fontSize: 14),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            const Icon(
                              Icons.calendar_today_rounded,
                              size: 18,
                              color: Colors.grey,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text(
                    'Batal',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ),
                ElevatedButton(
                  onPressed: provider.isLoading
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;
                          final payload = {
                            'tanggal_panen':
                                '${selectedDate.year}-${selectedDate.month.toString().padLeft(2, '0')}-${selectedDate.day.toString().padLeft(2, '0')}',
                            'hasil_panen': double.tryParse(
                              hasilController.text.trim(),
                            ),
                          };
                          final success = await provider.updateLaporPanen(
                            item['id'],
                            payload,
                          );
                          if (!context.mounted) return;
                          if (success) {
                            Navigator.pop(context);
                            _loadPanenData(1);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text(
                                  'Perbaikan laporan panen berhasil dikirim.',
                                ),
                                backgroundColor: Colors.green,
                              ),
                            );
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  provider.errorMessage ??
                                      'Gagal mengirim perbaikan.',
                                ),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3E7D00),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: provider.isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text('Kirim Perbaikan'),
                ),
              ],
            );
          },
        );
      },
    );
  }
}
