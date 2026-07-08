import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';

class PetugasLahanTermonitorContent extends StatelessWidget {
  final bool showIntro;

  const PetugasLahanTermonitorContent({super.key, this.showIntro = true});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
    final previewLands = _asList(provider.lahanTermonitorPreview['lands']);
    final previewSensors = _asList(provider.lahanTermonitorPreview['sensors']);
    final storedLands = provider.lahanTermonitorList
        .map((item) => _asMap(item))
        .toList();
    final storedSensors = provider.lahanTermonitorMonitoring
        .map((item) => _asMap(item))
        .toList();

    final isInitialLoading =
        provider.isLahanTermonitorLoading &&
        previewLands.isEmpty &&
        storedLands.isEmpty;

    if (isInitialLoading) {
      return const Padding(
        padding: EdgeInsets.only(top: 72),
        child: Center(
          child: CircularProgressIndicator(color: Color(0xFF3E7D00)),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (showIntro) ...[_buildIntroCard(), const SizedBox(height: 14)],
        _buildSyncButton(context, provider),
        const SizedBox(height: 14),
        _buildPreviewCard(previewLands, previewSensors),
        const SizedBox(height: 14),
        _buildStoredCard(storedLands, storedSensors),
        if (provider.errorMessage != null) ...[
          const SizedBox(height: 14),
          _buildErrorBox(provider.errorMessage!),
        ],
      ],
    );
  }

  Widget _buildIntroCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: const Color(0xFFEDF8DC),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.sensors_rounded, color: Color(0xFF3E7D00)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Lahan Termonitor (IoT)',
                  style: GoogleFonts.outfit(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Tarik data lahan dan log sensor terbaru dari perangkat Huma.',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    color: const Color(0xFF64748B),
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSyncButton(BuildContext context, FarmingProvider provider) {
    final isBusy =
        provider.isPetugasActionLoading || provider.isLahanTermonitorLoading;

    return ElevatedButton.icon(
      onPressed: isBusy
          ? null
          : () async {
              final messenger = ScaffoldMessenger.of(context);
              final success = await provider.syncLahanTermonitor();
              messenger.showSnackBar(
                SnackBar(
                  content: Text(
                    success
                        ? (provider.lahanTermonitorSyncMessage ??
                              'Sinkronisasi data Huma berhasil.')
                        : (provider.errorMessage ??
                              'Gagal sinkronisasi data Huma.'),
                  ),
                  backgroundColor: success
                      ? const Color(0xFF3E7D00)
                      : const Color(0xFFB91C1C),
                ),
              );
            },
      icon: isBusy
          ? const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white,
              ),
            )
          : const Icon(Icons.sync_rounded),
      label: Text(
        isBusy ? 'Menyinkronkan...' : 'Sinkronkan Data Huma',
        style: GoogleFonts.inter(fontWeight: FontWeight.bold),
      ),
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF3E7D00),
        foregroundColor: Colors.white,
        disabledBackgroundColor: const Color(0xFF94A3B8),
        elevation: 0,
        padding: const EdgeInsets.symmetric(vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
    );
  }

  Widget _buildPreviewCard(
    List<Map<String, dynamic>> lands,
    List<Map<String, dynamic>> sensors,
  ) {
    return _sectionCard(
      title: 'Preview Data (Dari Huma)',
      badge: 'Belum Tersimpan',
      badgeColor: const Color(0xFFD97706),
      child: lands.isEmpty
          ? _emptyText('Tidak ada data preview dari Huma.')
          : Column(
              children: lands.map((land) {
                final deviceId = _value(land, ['device_id']);
                final logsCount = sensors
                    .where(
                      (sensor) => _value(sensor, ['device_id']) == deviceId,
                    )
                    .length;

                return _dataRow(
                  icon: Icons.landscape_rounded,
                  title: _value(land, ['nama_lahan', 'name'], fallback: '-'),
                  subtitle: _value(land, ['alamat', 'address'], fallback: '-'),
                  trailingTitle: deviceId.isEmpty ? '-' : deviceId,
                  trailingSubtitle: '$logsCount logs',
                );
              }).toList(),
            ),
    );
  }

  Widget _buildStoredCard(
    List<Map<String, dynamic>> lands,
    List<Map<String, dynamic>> sensors,
  ) {
    return _sectionCard(
      title: 'Data Tersimpan (SiTani)',
      badge: 'Tersinkron',
      badgeColor: const Color(0xFF3E7D00),
      child: lands.isEmpty
          ? _emptyText('Belum ada lahan Huma yang tersimpan.')
          : Column(
              children: lands.map((land) {
                final note = _jsonMap(land['catatan_verifikasi']);
                final deviceId = _text(note['huma_device_id']);
                final latestSensor = sensors
                    .cast<Map<String, dynamic>?>()
                    .firstWhere(
                      (sensor) =>
                          sensor != null &&
                          _value(sensor, ['device_id']) == deviceId,
                      orElse: () => null,
                    );
                final sensorText = latestSensor == null
                    ? 'Belum ada sensor'
                    : 'pH ${_value(latestSensor, ['ph_tanah'])} - ${_formatDate(_value(latestSensor, ['waktu_rekam']))}';

                return _dataRow(
                  icon: Icons.sensors_rounded,
                  title: _value(land, ['nama_lahan'], fallback: '-'),
                  subtitle: _value(land, [
                    'nama_kecamatan',
                    'alamat_detail',
                  ], fallback: '-'),
                  trailingTitle: deviceId.isEmpty ? '-' : deviceId,
                  trailingSubtitle: sensorText,
                );
              }).toList(),
            ),
    );
  }

  Widget _sectionCard({
    required String title,
    required String badge,
    required Color badgeColor,
    required Widget child,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: GoogleFonts.outfit(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      color: const Color(0xFF14280B),
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 9,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: badgeColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    badge,
                    style: GoogleFonts.inter(
                      fontSize: 9,
                      fontWeight: FontWeight.w800,
                      color: badgeColor,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          child,
        ],
      ),
    );
  }

  Widget _dataRow({
    required IconData icon,
    required String title,
    required String subtitle,
    required String trailingTitle,
    required String trailingSubtitle,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36,
            height: 36,
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
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          SizedBox(
            width: 88,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  trailingTitle,
                  textAlign: TextAlign.right,
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF334155),
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  trailingSubtitle,
                  textAlign: TextAlign.right,
                  style: GoogleFonts.inter(
                    fontSize: 10,
                    color: const Color(0xFF94A3B8),
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _emptyText(String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 34),
      child: Text(
        text,
        textAlign: TextAlign.center,
        style: GoogleFonts.inter(fontSize: 12, color: const Color(0xFF64748B)),
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

  static List<Map<String, dynamic>> _asList(dynamic value) {
    if (value is List) {
      return value.map((item) => _asMap(item)).toList();
    }
    return <Map<String, dynamic>>[];
  }

  static Map<String, dynamic> _asMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return <String, dynamic>{};
  }

  static Map<String, dynamic> _jsonMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    if (value is String && value.trim().isNotEmpty) {
      try {
        final decoded = jsonDecode(value);
        return _asMap(decoded);
      } catch (_) {}
    }
    return <String, dynamic>{};
  }

  static String _value(
    Map<String, dynamic>? item,
    List<String> keys, {
    String fallback = '',
  }) {
    if (item == null) return fallback;
    for (final key in keys) {
      final value = item[key];
      final text = _text(value);
      if (text.isNotEmpty) return text;
    }
    return fallback;
  }

  static String _text(dynamic value) {
    return value?.toString().trim() ?? '';
  }

  static String _formatDate(String value) {
    if (value.isEmpty) return '-';
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
