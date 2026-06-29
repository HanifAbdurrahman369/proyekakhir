import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';
import 'petugas_spasial_screen.dart';
import 'petugas_verifikasi_screen.dart';

class PetugasNotifikasiScreen extends StatefulWidget {
  const PetugasNotifikasiScreen({super.key});

  @override
  State<PetugasNotifikasiScreen> createState() =>
      _PetugasNotifikasiScreenState();
}

class _PetugasNotifikasiScreenState extends State<PetugasNotifikasiScreen> {
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
    final items = provider.petugasNotifikasi;

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Notifikasi Petugas',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF3E7D00),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: const Color(0xFF3E7D00),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            _buildSummary(provider),
            const SizedBox(height: 14),
            if (provider.isPetugasLoading && items.isEmpty)
              const Padding(
                padding: EdgeInsets.only(top: 80),
                child: Center(
                  child: CircularProgressIndicator(color: Color(0xFF3E7D00)),
                ),
              )
            else if (items.isEmpty)
              _buildEmpty()
            else
              ...items.map(
                (raw) => _buildNotificationCard(
                  Map<String, dynamic>.from(raw as Map),
                ),
              ),
            if (provider.errorMessage != null) ...[
              const SizedBox(height: 14),
              _buildErrorBox(provider.errorMessage!),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSummary(FarmingProvider provider) {
    final totalPending = provider.petugasPendingCounts['total_pending'] ?? 0;
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFEDF8DC),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.notifications_active_rounded,
              color: Color(0xFF3E7D00),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Kotak notifikasi petugas',
                  style: GoogleFonts.outfit(
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  '${provider.petugasUnreadCount} belum dibaca - $totalPending antrean aktif',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    color: const Color(0xFF64748B),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationCard(Map<String, dynamic> item) {
    final isRead = item['is_read'].toString() == '1' || item['is_read'] == true;
    final id = int.tryParse(item['id']?.toString() ?? '');
    final targetUrl = item['target_url']?.toString() ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(
          color: isRead ? const Color(0xFFE2E8F0) : const Color(0xFFBBF7D0),
        ),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: () => _openNotification(id, targetUrl),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: isRead
                        ? const Color(0xFFF1F5F9)
                        : const Color(0xFFDCFCE7),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(
                    isRead
                        ? Icons.mark_email_read_outlined
                        : Icons.mark_email_unread_outlined,
                    color: isRead
                        ? const Color(0xFF64748B)
                        : const Color(0xFF16A34A),
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item['judul']?.toString() ?? 'Notifikasi',
                        style: GoogleFonts.outfit(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item['pesan']?.toString() ?? '-',
                        style: GoogleFonts.inter(
                          fontSize: 12,
                          color: const Color(0xFF64748B),
                          height: 1.4,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          _buildMiniBadge(
                            isRead ? 'Dibaca' : 'Baru',
                            isRead
                                ? const Color(0xFF64748B)
                                : const Color(0xFF16A34A),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _formatDateTime(item['created_at']?.toString()),
                              style: GoogleFonts.inter(
                                fontSize: 10,
                                color: const Color(0xFF94A3B8),
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                const Icon(
                  Icons.chevron_right_rounded,
                  color: Color(0xFF94A3B8),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMiniBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: GoogleFonts.inter(
          fontSize: 9,
          fontWeight: FontWeight.bold,
          color: color,
        ),
      ),
    );
  }

  Future<void> _openNotification(int? id, String targetUrl) async {
    if (id != null) {
      await context.read<FarmingProvider>().markPetugasNotifikasiRead(id);
    }
    if (!mounted) return;

    if (targetUrl.contains('manajemen-data-spasial')) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const PetugasSpasialScreen()),
      );
    } else {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const PetugasVerifikasiScreen()),
      );
    }
  }

  Widget _buildEmpty() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 44),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.notifications_none_rounded,
            size: 42,
            color: Color(0xFF94A3B8),
          ),
          const SizedBox(height: 10),
          Text(
            'Belum ada notifikasi petugas.',
            style: GoogleFonts.inter(
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
        style: GoogleFonts.inter(
          fontSize: 12,
          color: const Color(0xFFB91C1C),
          height: 1.4,
        ),
      ),
    );
  }

  String _formatDateTime(String? value) {
    if (value == null || value.isEmpty) return '-';
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
      return '${parsed.day} ${months[parsed.month - 1]} ${parsed.year} ${parsed.hour.toString().padLeft(2, '0')}:${parsed.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return value;
    }
  }
}
