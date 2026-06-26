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
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchPejabatDashboardData();
    });
  }

  String _formatNumber(double val) {
    return val.toStringAsFixed(2).replaceAll('.', ',').replaceFirst(RegExp(r',00$'), '');
  }

  String _getCurrentMonthYear() {
    final now = DateTime.now();
    final months = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return '${months[now.month - 1]} ${now.year}';
  }

  Future<void> _exportPdfReport() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;
    if (token == null) return;

    final baseUrl = ApiEndpoints.baseUrl;
    final uri = Uri.parse(baseUrl);
    final webAppUrl = '${uri.scheme}://${uri.host}:8080';
    final downloadUrl = Uri.parse('$webAppUrl/pejabat/cetak-laporan?token=$token');

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
                          final prod = double.tryParse(item['produksi_pejabat']?.toString() ?? '0') ?? 0.0;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
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
                                        style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 13),
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
                          final lahan = double.tryParse(item['total_lahan']?.toString() ?? '0') ?? 0.0;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
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
                                        style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 13),
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

    if (provider.isPejabatLoading && provider.produksiKecamatanPejabat.isEmpty) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    if (provider.errorMessage != null && provider.produksiKecamatanPejabat.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: Colors.red),
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
                style: ElevatedButton.styleFrom(backgroundColor: Colors.green[800]),
                child: Text('Coba Lagi', style: GoogleFonts.inter(color: Colors.white)),
              )
            ],
          ),
        ),
      );
    }

    final monthlyData = provider.produksiBulananPejabat;
    final maxMonthlyVal = monthlyData.values.isNotEmpty
        ? monthlyData.values.reduce((a, b) => a > b ? a : b)
        : 0.0;
    final maxProduksiForChart = maxMonthlyVal > 0 ? maxMonthlyVal : 1.0;

    final monthNames = [
      'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
      'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
    ];

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
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
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
                ),
                const SizedBox(width: 8),
                ElevatedButton.icon(
                  onPressed: _exportPdfReport,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3E7D00),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    elevation: 1,
                  ),
                  icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                  label: Text(
                    'Export PDF',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                    ),
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
                    onTap: () => _showProduksiKecamatanSheet(context, provider.produksiKecamatanPejabat),
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
                                  const Icon(Icons.arrow_forward_rounded, size: 10, color: Color(0xFF3E7D00)),
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
                    onTap: () => _showLahanKecamatanSheet(context, provider.lahanKecamatanPejabat),
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
                                  const Icon(Icons.arrow_forward_rounded, size: 10, color: Color(0xFF3E7D00)),
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
            const SizedBox(height: 24),

            // 4. Tren Produksi Bulanan (Bar Chart)
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
                  Text(
                    'Tren Produksi Bulanan',
                    style: GoogleFonts.outfit(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF14280B),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Visualisasi cepat untuk pembacaan eksekutif.',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      color: Colors.grey[500],
                    ),
                  ),
                  const SizedBox(height: 28),
                  SizedBox(
                    height: 180,
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: List.generate(12, (index) {
                        final monthNum = index + 1;
                        final total = monthlyData[monthNum] ?? 0.0;
                        final heightFactor = total / maxProduksiForChart;
                        final barHeight = heightFactor * 130;

                        return Expanded(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              if (total > 0)
                                Text(
                                  total.toStringAsFixed(0),
                                  style: GoogleFonts.inter(
                                    fontSize: 8,
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF3E7D00),
                                  ),
                                ),
                              const SizedBox(height: 4),
                              Container(
                                height: barHeight < 15 ? 15 : barHeight,
                                margin: const EdgeInsets.symmetric(horizontal: 3),
                                decoration: BoxDecoration(
                                  gradient: const LinearGradient(
                                    colors: [Color(0xFF65BD00), Color(0xFF3E7D00)],
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                  ),
                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(8)),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                monthNames[index],
                                style: GoogleFonts.inter(
                                  fontSize: 9,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.grey[500],
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                    ),
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
