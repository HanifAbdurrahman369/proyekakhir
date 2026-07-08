import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../models/user.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/farming_provider.dart';
import '../../../core/constants/api_endpoints.dart';

class PejabatDashboard extends StatefulWidget {
  final User? user;

  const PejabatDashboard({super.key, required this.user});

  @override
  State<PejabatDashboard> createState() => _PejabatDashboardState();
}

class _PejabatDashboardState extends State<PejabatDashboard> {
  bool _isKecamatanExpanded = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchPejabatDashboardData();
    });
  }

  String _formatNumber(double val) {
    return val
        .toStringAsFixed(2)
        .replaceAll('.', ',')
        .replaceFirst(RegExp(r',00$'), '');
  }

  String _getCurrentMonthYear() {
    final now = DateTime.now();
    final months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ];
    return '${months[now.month - 1]} ${now.year}';
  }

  Future<void> _exportPdfReport() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;
    if (token == null) return;

    final baseUrl = ApiEndpoints.baseUrl;
    final uri = Uri.parse(baseUrl);
    final webAppUrl = '${uri.scheme}://${uri.host}:8000'; // Default port artisan serve
    final downloadUrl = Uri.parse(
      '$webAppUrl/pejabat/cetak-laporan?token=$token',
    );

    if (await canLaunchUrl(downloadUrl)) {
      await launchUrl(downloadUrl, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tidak dapat mengunduh laporan PDF.')),
        );
      }
    }
  }

  // ================= DETAIL BOTTOM SHEETS =================

  void _showProduksiKecamatanSheet(BuildContext context, List<dynamic> list) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      backgroundColor: Colors.white,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Produksi per Kecamatan',
                    style: GoogleFonts.outfit(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF14280B),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              const Divider(color: Color(0xFFE2E8F0), height: 24),
              Expanded(
                child: list.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data produksi.',
                          style: GoogleFonts.inter(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: list.length,
                        itemBuilder: (context, index) {
                          final item = list[index];
                          final prod =
                              double.tryParse(
                                item['produksi_pejabat']?.toString() ?? '0',
                              ) ??
                              0.0;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      backgroundColor: Colors.green[50],
                                      foregroundColor: Colors.green[800],
                                      radius: 18,
                                      child: Text(
                                        '${index + 1}',
                                        style: GoogleFonts.inter(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Text(
                                      item['nama_kecamatan'] ?? '-',
                                      style: GoogleFonts.inter(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14,
                                        color: const Color(0xFF1E293B),
                                      ),
                                    ),
                                  ],
                                ),
                                Text(
                                  '${_formatNumber(prod)} Ton',
                                  style: GoogleFonts.outfit(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15,
                                    color: Colors.green[800],
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showLahanKecamatanSheet(BuildContext context, List<dynamic> list) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      backgroundColor: Colors.white,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Luas Lahan per Kecamatan',
                    style: GoogleFonts.outfit(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF14280B),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              const Divider(color: Color(0xFFE2E8F0), height: 24),
              Expanded(
                child: list.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data luas lahan.',
                          style: GoogleFonts.inter(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: list.length,
                        itemBuilder: (context, index) {
                          final item = list[index];
                          final lahan =
                              double.tryParse(
                                item['total_lahan']?.toString() ?? '0',
                              ) ??
                              0.0;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      backgroundColor: Colors.blue[50],
                                      foregroundColor: Colors.blue[800],
                                      radius: 18,
                                      child: Text(
                                        '${index + 1}',
                                        style: GoogleFonts.inter(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Text(
                                      item['nama_kecamatan'] ?? '-',
                                      style: GoogleFonts.inter(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14,
                                        color: const Color(0xFF1E293B),
                                      ),
                                    ),
                                  ],
                                ),
                                Text(
                                  '${_formatNumber(lahan)} Ha',
                                  style: GoogleFonts.outfit(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15,
                                    color: Colors.blue[800],
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();

    if (provider.isPejabatLoading &&
        provider.produksiKecamatanPejabat.isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    if (provider.errorMessage != null &&
        provider.produksiKecamatanPejabat.isEmpty) {
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
                provider.errorMessage!,
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(color: Colors.grey[800]),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  context.read<FarmingProvider>().fetchPejabatDashboardData();
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

    final kecamatanData = provider.produksiKecamatanPejabat;
    final maxKecamatanVal = kecamatanData.isNotEmpty
        ? kecamatanData
              .map(
                (item) =>
                    double.tryParse(
                      item['produksi_pejabat']?.toString() ?? '0',
                    ) ??
                    0.0,
              )
              .reduce((a, b) => a > b ? a : b)
        : 0.0;
    final maxProduksiKecamatanForChart = maxKecamatanVal > 0
        ? maxKecamatanVal
        : 1.0;

    return RefreshIndicator(
      onRefresh: () async {
        await context.read<FarmingProvider>().fetchPejabatDashboardData();
      },
      color: Colors.green[800],
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // 1. Profile Card (Pejabat Dinas Style)
            // _buildProfileCard(),
            // const SizedBox(height: 24),

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
                    'Dashboard Pejabat',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF3E7D00),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Statistik Eksekutif',
                  style: GoogleFonts.outfit(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Analisis Data Komoditas Daerah — ${_getCurrentMonthYear()}',
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // 3. Stats Cards (Total Produksi & Lahan Aktif)
            Row(
              children: [
                // Card Total Produksi
                Expanded(
                  child: InkWell(
                    onTap: () => _showProduksiKecamatanSheet(
                      context,
                      provider.produksiKecamatanPejabat,
                    ),
                    borderRadius: BorderRadius.circular(26),
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      height: 190,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(26),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'TOTAL PRODUKSI',
                                style: GoogleFonts.inter(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.grey[500],
                                  letterSpacing: 1.0,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                _formatNumber(provider.produksiPejabat),
                                style: GoogleFonts.outfit(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF14280B),
                                ),
                              ),
                              Text(
                                'Ton',
                                style: GoogleFonts.inter(
                                  fontSize: 12,
                                  color: Colors.grey[400],
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                          Column(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: 0.75,
                                  backgroundColor: Colors.grey[100],
                                  color: const Color(0xFF3E7D00),
                                  minHeight: 6,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Text(
                                    'Detail wilayah',
                                    style: GoogleFonts.inter(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w600,
                                      color: const Color(0xFF3E7D00),
                                    ),
                                  ),
                                  const SizedBox(width: 2),
                                  const Icon(
                                    Icons.arrow_forward_rounded,
                                    size: 10,
                                    color: Color(0xFF3E7D00),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                // Card Lahan Aktif
                Expanded(
                  child: InkWell(
                    onTap: () => _showLahanKecamatanSheet(
                      context,
                      provider.lahanKecamatanPejabat,
                    ),
                    borderRadius: BorderRadius.circular(26),
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      height: 190,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(26),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'LAHAN AKTIF',
                                style: GoogleFonts.inter(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.grey[500],
                                  letterSpacing: 1.0,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                _formatNumber(provider.totalLahanPejabat),
                                style: GoogleFonts.outfit(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF14280B),
                                ),
                              ),
                              Text(
                                'Ha',
                                style: GoogleFonts.inter(
                                  fontSize: 12,
                                  color: Colors.grey[400],
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                          Column(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: 0.60,
                                  backgroundColor: Colors.grey[100],
                                  color: const Color(0xFF10B981),
                                  minHeight: 6,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Text(
                                    'Detail wilayah',
                                    style: GoogleFonts.inter(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w600,
                                      color: const Color(0xFF3E7D00),
                                    ),
                                  ),
                                  const SizedBox(width: 2),
                                  const Icon(
                                    Icons.arrow_forward_rounded,
                                    size: 10,
                                    color: Color(0xFF3E7D00),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Button Export PDF di bawah Card Lahan & Produksi
            ElevatedButton.icon(
              onPressed: _exportPdfReport,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF3E7D00),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.symmetric(vertical: 14),
                elevation: 2,
                shadowColor: const Color(0xFF3E7D00).withValues(alpha: 0.2),
              ),
              icon: const Icon(Icons.picture_as_pdf_rounded, size: 20),
              label: Text(
                'Export PDF Laporan',
                style: GoogleFonts.inter(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // 4. Tren Produksi per Kecamatan (Horizontal Progress Bar List)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(28),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Tren Produksi per Kecamatan',
                        style: GoogleFonts.outfit(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      Icon(
                        Icons.bar_chart_rounded,
                        color: Colors.green[800],
                        size: 20,
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Produksi komoditas per kecamatan dalam 1 tahun.',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      color: Colors.grey[500],
                    ),
                  ),
                  const SizedBox(height: 20),
                  kecamatanData.isEmpty
                      ? SizedBox(
                          height: 150,
                          child: Center(
                            child: Text(
                              'Belum ada data produksi.',
                              style: GoogleFonts.inter(color: Colors.grey[500]),
                            ),
                          ),
                        )
                      : Column(
                          children: [
                            ...List.generate(
                              _isKecamatanExpanded
                                  ? kecamatanData.length
                                  : (kecamatanData.length > 5
                                        ? 5
                                        : kecamatanData.length),
                              (index) {
                                final item = kecamatanData[index];
                                final name = item['nama_kecamatan'] ?? '-';
                                final total =
                                    double.tryParse(
                                      item['produksi_pejabat']?.toString() ??
                                          '0',
                                    ) ??
                                    0.0;
                                final percent = maxProduksiKecamatanForChart > 0
                                    ? total / maxProduksiKecamatanForChart
                                    : 0.0;

                                return Container(
                                  margin: const EdgeInsets.only(bottom: 14),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              name,
                                              style: GoogleFonts.inter(
                                                fontSize: 13,
                                                fontWeight: FontWeight.bold,
                                                color: const Color(0xFF1E293B),
                                              ),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Text(
                                            '${_formatNumber(total)} Ton',
                                            style: GoogleFonts.outfit(
                                              fontSize: 13,
                                              fontWeight: FontWeight.bold,
                                              color: Colors.green[800],
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      LayoutBuilder(
                                        builder: (context, constraints) {
                                          final barWidth =
                                              constraints.maxWidth * percent;
                                          return Stack(
                                            children: [
                                              Container(
                                                height: 8,
                                                width: constraints.maxWidth,
                                                decoration: BoxDecoration(
                                                  color: const Color(
                                                    0xFFF1F5F9,
                                                  ),
                                                  borderRadius:
                                                      BorderRadius.circular(4),
                                                ),
                                              ),
                                              Container(
                                                height: 8,
                                                width:
                                                    barWidth < 4 && percent > 0
                                                    ? 4
                                                    : barWidth,
                                                decoration: BoxDecoration(
                                                  gradient:
                                                      const LinearGradient(
                                                        colors: [
                                                          Color(0xFF65BD00),
                                                          Color(0xFF3E7D00),
                                                        ],
                                                      ),
                                                  borderRadius:
                                                      BorderRadius.circular(4),
                                                ),
                                              ),
                                            ],
                                          );
                                        },
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                            if (kecamatanData.length > 5) ...[
                              const Divider(
                                color: Color(0xFFF1F5F9),
                                height: 24,
                              ),
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    _isKecamatanExpanded =
                                        !_isKecamatanExpanded;
                                  });
                                },
                                style: TextButton.styleFrom(
                                  padding: EdgeInsets.zero,
                                  minimumSize: const Size(0, 0),
                                  tapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      _isKecamatanExpanded
                                          ? 'Tampilkan Lebih Sedikit'
                                          : 'Tampilkan Seluruh Kecamatan',
                                      style: GoogleFonts.inter(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: const Color(0xFF3E7D00),
                                      ),
                                    ),
                                    const SizedBox(width: 4),
                                    Icon(
                                      _isKecamatanExpanded
                                          ? Icons.keyboard_arrow_up_rounded
                                          : Icons.keyboard_arrow_down_rounded,
                                      size: 16,
                                      color: const Color(0xFF3E7D00),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                ],
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
