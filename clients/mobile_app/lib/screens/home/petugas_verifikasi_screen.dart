import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';
import 'petugas_spasial_screen.dart';

class PetugasVerifikasiScreen extends StatefulWidget {
  const PetugasVerifikasiScreen({super.key});

  @override
  State<PetugasVerifikasiScreen> createState() =>
      _PetugasVerifikasiScreenState();
}

class _PetugasVerifikasiScreenState extends State<PetugasVerifikasiScreen> {
  int _tabIndex = 0;

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
    final pendingLahan = provider.petugasPendingLahan;
    final pendingPanen = provider.petugasPendingPanen;

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Verifikasi Data Petani',
          style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF047857),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: _refresh,
            color: const Color(0xFF047857),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _buildIntro(pendingLahan.length, pendingPanen.length),
                const SizedBox(height: 14),
                _buildTabSwitch(pendingLahan.length, pendingPanen.length),
                const SizedBox(height: 14),
                if (provider.isPetugasLoading &&
                    pendingLahan.isEmpty &&
                    pendingPanen.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 80),
                    child: Center(
                      child: CircularProgressIndicator(
                        color: Color(0xFF047857),
                      ),
                    ),
                  )
                else if (_tabIndex == 0)
                  _buildLahanList(pendingLahan)
                else
                  _buildPanenList(pendingPanen),
                if (provider.errorMessage != null) ...[
                  const SizedBox(height: 14),
                  _buildErrorBox(provider.errorMessage!),
                ],
              ],
            ),
          ),
          if (provider.isPetugasActionLoading)
            const Positioned(
              left: 0,
              right: 0,
              top: 0,
              child: LinearProgressIndicator(
                color: Color(0xFF047857),
                minHeight: 3,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildIntro(int lahanCount, int panenCount) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFFEDF8DC),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              '${lahanCount + panenCount} DATA MENUNGGU',
              style: GoogleFonts.poppins(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                color: const Color(0xFF047857),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Antrean verifikasi petugas',
            style: GoogleFonts.poppins(
              fontSize: 21,
              fontWeight: FontWeight.w800,
              color: const Color(0xFF14280B),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Periksa detail pengajuan sebelum menyetujui. Lahan yang disetujui dapat dilanjutkan ke pemetaan titik dan polygon.',
            style: GoogleFonts.poppins(
              fontSize: 12,
              color: const Color(0xFF64748B),
              height: 1.45,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabSwitch(int lahanCount, int panenCount) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          _buildTabButton(0, 'Lahan', lahanCount, Icons.landscape_rounded),
          _buildTabButton(1, 'Panen', panenCount, Icons.fact_check_rounded),
        ],
      ),
    );
  }

  Widget _buildTabButton(int index, String label, int count, IconData icon) {
    final selected = _tabIndex == index;
    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _tabIndex = index),
        borderRadius: BorderRadius.circular(12),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF203C10) : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 16,
                color: selected ? Colors.white : const Color(0xFF64748B),
              ),
              const SizedBox(width: 6),
              Text(
                '$label ($count)',
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: selected ? Colors.white : const Color(0xFF334155),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLahanList(List<dynamic> items) {
    if (items.isEmpty) {
      return _buildEmptyState('Belum ada pengajuan lahan baru.');
    }

    return Column(
      children: items.map((raw) {
        final item = Map<String, dynamic>.from(raw as Map);
        final id = int.tryParse(_val(item, ['id']));
        return _buildWorkCard(
          badge: 'PENDING',
          title: _val(item, ['nama_lahan'], fallback: 'Lahan Sawah'),
          subtitle: _val(item, ['nama_petani', 'pemilik_lahan'], fallback: '-'),
          details: [
            _InfoLine(
              Icons.location_on_outlined,
              '${_val(item, ['nama_kelurahan'], fallback: '-')} / ${_val(item, ['nama_kecamatan'], fallback: '-')}',
            ),
            _InfoLine(
              Icons.straighten_rounded,
              '${_formatNumber(item['luas_lahan_hektar'])} Ha',
            ),
            _InfoLine(
              Icons.home_work_outlined,
              _val(item, ['alamat_detail'], fallback: 'Alamat belum diisi'),
            ),
          ],
          onDetail: () => _showLahanDetail(item),
          onApprove: id == null ? null : () => _confirmApproveLahan(id),
          onReject: id == null
              ? null
              : () => _showRejectDialog(
                  title: 'Tolak Pengajuan Lahan',
                  label: _val(item, [
                    'nama_lahan',
                  ], fallback: 'Pengajuan lahan'),
                  onSubmit: (reason) => _runAction(
                    () => context.read<FarmingProvider>().rejectPetugasLahan(
                      id,
                      reason,
                    ),
                    'Pengajuan lahan ditolak.',
                  ),
                ),
        );
      }).toList(),
    );
  }

  Widget _buildPanenList(List<dynamic> items) {
    if (items.isEmpty) {
      return _buildEmptyState('Belum ada laporan panen berstatus pending.');
    }

    return Column(
      children: items.map((raw) {
        final item = Map<String, dynamic>.from(raw as Map);
        final id = int.tryParse(_val(item, ['id']));
        return _buildWorkCard(
          badge: 'PENDING',
          title: _val(item, ['nama_lahan'], fallback: 'Laporan Panen'),
          subtitle: _val(item, ['nama_petani'], fallback: '-'),
          details: [
            _InfoLine(
              Icons.grass_rounded,
              '${_val(item, ['nama_bibit'], fallback: '-')} - ${_val(item, ['varietas'], fallback: '-')}',
            ),
            _InfoLine(
              Icons.calendar_today_rounded,
              'Tanam ${_formatDate(_val(item, ['tanggal_tanam']))} - Panen ${_formatDate(_val(item, ['tanggal_panen']))}',
            ),
            _InfoLine(
              Icons.scale_rounded,
              '${_formatNumber(item['hasil_panen'] ?? item['hasil_panen_ton'])} Ton',
            ),
          ],
          onDetail: () => _showPanenDetail(item),
          onApprove: id == null ? null : () => _confirmApprovePanen(id),
          onReject: id == null
              ? null
              : () => _showRejectDialog(
                  title: 'Tolak Laporan Panen',
                  label: _val(item, ['nama_lahan'], fallback: 'Laporan panen'),
                  onSubmit: (reason) => _runAction(
                    () => context.read<FarmingProvider>().rejectPetugasPanen(
                      id,
                      reason,
                    ),
                    'Laporan panen ditolak.',
                  ),
                ),
        );
      }).toList(),
    );
  }

  Widget _buildWorkCard({
    required String badge,
    required String title,
    required String subtitle,
    required List<_InfoLine> details,
    required VoidCallback onDetail,
    required VoidCallback? onApprove,
    required VoidCallback? onReject,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: GoogleFonts.poppins(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        subtitle,
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: const Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
                _buildStatusBadge(badge),
              ],
            ),
            const SizedBox(height: 12),
            ...details.map(
              (line) => Padding(
                padding: const EdgeInsets.only(bottom: 7),
                child: Row(
                  children: [
                    Icon(line.icon, size: 15, color: const Color(0xFF047857)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        line.text,
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: const Color(0xFF475569),
                          height: 1.35,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: onDetail,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF047857),
                      side: const BorderSide(color: Color(0xFFDFECCC)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: Text(
                      'Detail',
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton(
                    onPressed: onApprove,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF047857),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: Text(
                      'Setujui',
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filledTonal(
                  onPressed: onReject,
                  style: IconButton.styleFrom(
                    backgroundColor: const Color(0xFFFEF2F2),
                    foregroundColor: const Color(0xFFDC2626),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  icon: const Icon(Icons.close_rounded),
                  tooltip: 'Tolak',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBadge(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        border: Border.all(color: const Color(0xFFFDE68A)),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: GoogleFonts.poppins(
          fontSize: 9,
          fontWeight: FontWeight.w800,
          color: const Color(0xFFD97706),
        ),
      ),
    );
  }

  Future<void> _confirmApproveLahan(int id) async {
    final ok = await _confirm(
      title: 'Setujui pengajuan lahan?',
      message:
          'Data akan masuk sebagai lahan legal dan bisa dilanjutkan ke manajemen spasial.',
    );
    if (ok != true || !mounted) return;
    final success = await context.read<FarmingProvider>().approvePetugasLahan(
      id,
    );
    if (!mounted) return;
    _showResult(
      success,
      success
          ? 'Pengajuan lahan disetujui. Lanjutkan pemetaan spasial bila diperlukan.'
          : null,
    );
    if (success) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const PetugasSpasialScreen()),
      );
    }
  }

  Future<void> _confirmApprovePanen(int id) async {
    final ok = await _confirm(
      title: 'Setujui laporan panen?',
      message:
          'Data panen akan menjadi riwayat panen resmi dan mempengaruhi statistik produktivitas.',
    );
    if (ok != true || !mounted) return;
    await _runAction(
      () => context.read<FarmingProvider>().approvePetugasPanen(id),
      'Laporan panen disetujui.',
    );
  }

  Future<bool?> _confirm({required String title, required String message}) {
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          title,
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.bold,
            color: const Color(0xFF14280B),
          ),
        ),
        content: Text(
          message,
          style: GoogleFonts.poppins(
            fontSize: 13,
            height: 1.45,
            color: const Color(0xFF475569),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(
              'Batal',
              style: GoogleFonts.poppins(color: const Color(0xFF64748B)),
            ),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF047857),
              foregroundColor: Colors.white,
            ),
            child: Text(
              'Setujui',
              style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _showRejectDialog({
    required String title,
    required String label,
    required Future<void> Function(String reason) onSubmit,
  }) async {
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          title,
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.bold,
            color: const Color(0xFF991B1B),
          ),
        ),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF14280B),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: controller,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Tuliskan alasan penolakan...',
                  hintStyle: GoogleFonts.poppins(fontSize: 12),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: Color(0xFF047857)),
                  ),
                ),
                validator: (value) {
                  if ((value ?? '').trim().length < 5) {
                    return 'Alasan minimal 5 karakter.';
                  }
                  return null;
                },
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(
              'Batal',
              style: GoogleFonts.poppins(color: const Color(0xFF64748B)),
            ),
          ),
          ElevatedButton(
            onPressed: () {
              if (formKey.currentState?.validate() == true) {
                Navigator.pop(context, controller.text.trim());
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFDC2626),
              foregroundColor: Colors.white,
            ),
            child: Text(
              'Tolak',
              style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
    controller.dispose();

    if (reason != null && reason.isNotEmpty) {
      await onSubmit(reason);
    }
  }

  Future<void> _runAction(
    Future<bool> Function() action,
    String successMessage,
  ) async {
    final success = await action();
    if (!mounted) return;
    _showResult(success, success ? successMessage : null);
  }

  void _showResult(bool success, String? successMessage) {
    final provider = context.read<FarmingProvider>();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? (successMessage ?? 'Aksi berhasil.')
              : (provider.errorMessage ?? 'Aksi gagal.'),
        ),
        backgroundColor: success
            ? const Color(0xFF047857)
            : const Color(0xFFB91C1C),
      ),
    );
  }

  void _showLahanDetail(Map<String, dynamic> item) {
    _showDetailSheet(
      title: _val(item, ['nama_lahan'], fallback: 'Detail Pengajuan Lahan'),
      badge: 'PENGAJUAN LAHAN',
      rows: [
        _DetailRow(
          'Pengaju',
          _val(item, ['nama_petani', 'pemilik_lahan'], fallback: '-'),
        ),
        _DetailRow('Email', _val(item, ['email_petani'], fallback: '-')),
        _DetailRow(
          'Pemilik Lahan',
          _val(item, ['pemilik_lahan'], fallback: '-'),
        ),
        _DetailRow(
          'Wilayah',
          '${_val(item, ['nama_kelurahan'], fallback: '-')} / ${_val(item, ['nama_kecamatan'], fallback: '-')}',
        ),
        _DetailRow('Luas', '${_formatNumber(item['luas_lahan_hektar'])} Ha'),
        _DetailRow('Alamat', _val(item, ['alamat_detail'], fallback: '-')),
      ],
    );
  }

  void _showPanenDetail(Map<String, dynamic> item) {
    _showDetailSheet(
      title: _val(item, ['nama_lahan'], fallback: 'Detail Laporan Panen'),
      badge: 'LAPORAN PANEN',
      rows: [
        _DetailRow('Pengaju', _val(item, ['nama_petani'], fallback: '-')),
        _DetailRow('Penggarap', _val(item, ['nama_penggarap'], fallback: '-')),
        _DetailRow(
          'Bibit',
          '${_val(item, ['nama_bibit'], fallback: '-')} - ${_val(item, ['varietas'], fallback: '-')}',
        ),
        _DetailRow('Tanggal Tanam', _formatDate(_val(item, ['tanggal_tanam']))),
        _DetailRow('Tanggal Panen', _formatDate(_val(item, ['tanggal_panen']))),
        _DetailRow(
          'Hasil Panen',
          '${_formatNumber(item['hasil_panen'] ?? item['hasil_panen_ton'])} Ton',
        ),
        _DetailRow(
          'Produktivitas Pengajuan',
          '${_formatNumber(item['produktivitas_pengajuan_ton_ha'])} Ton/Ha',
        ),
        _DetailRow(
          'Wilayah',
          '${_val(item, ['nama_kelurahan'], fallback: '-')} / ${_val(item, ['nama_kecamatan'], fallback: '-')}',
        ),
      ],
    );
  }

  void _showDetailSheet({
    required String title,
    required String badge,
    required List<_DetailRow> rows,
  }) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.72,
        minChildSize: 0.35,
        maxChildSize: 0.92,
        builder: (context, controller) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
          ),
          child: ListView(
            controller: controller,
            padding: const EdgeInsets.all(20),
            children: [
              Center(
                child: Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE2E8F0),
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                badge,
                style: GoogleFonts.poppins(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF047857),
                  letterSpacing: 0.8,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                title,
                style: GoogleFonts.poppins(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF14280B),
                ),
              ),
              const SizedBox(height: 16),
              ...rows.map(
                (row) => Container(
                  margin: const EdgeInsets.only(bottom: 10),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        row.label.toUpperCase(),
                        style: GoogleFonts.poppins(
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                          color: const Color(0xFF94A3B8),
                        ),
                      ),
                      const SizedBox(height: 5),
                      Text(
                        row.value,
                        style: GoogleFonts.poppins(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: const Color(0xFF14280B),
                          height: 1.35,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 42),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.task_alt_rounded,
            size: 42,
            color: Color(0xFF94A3B8),
          ),
          const SizedBox(height: 10),
          Text(
            message,
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(
              fontSize: 13,
              color: const Color(0xFF64748B),
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
        style: GoogleFonts.poppins(
          fontSize: 12,
          color: const Color(0xFFB91C1C),
          height: 1.4,
        ),
      ),
    );
  }

  String _val(
    Map<String, dynamic> item,
    List<String> keys, {
    String fallback = '',
  }) {
    for (final key in keys) {
      final value = item[key];
      if (value != null && value.toString().trim().isNotEmpty) {
        return value.toString();
      }
    }
    return fallback;
  }

  String _formatNumber(dynamic value) {
    final parsed = double.tryParse(value?.toString() ?? '') ?? 0;
    final result = parsed.toStringAsFixed(
      parsed.truncateToDouble() == parsed ? 0 : 2,
    );
    return result.replaceAll('.', ',');
  }

  String _formatDate(String value) {
    if (value.trim().isEmpty || value == '-') return '-';
    try {
      final parsed = DateTime.parse(value);
      const months = [
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
      return value;
    }
  }
}

class _InfoLine {
  final IconData icon;
  final String text;

  const _InfoLine(this.icon, this.text);
}

class _DetailRow {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);
}
