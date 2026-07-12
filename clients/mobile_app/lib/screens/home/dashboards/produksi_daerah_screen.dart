import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../providers/auth_provider.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../../providers/farming_provider.dart';

class ProduksiDaerahScreen extends StatefulWidget {
  const ProduksiDaerahScreen({super.key});

  @override
  State<ProduksiDaerahScreen> createState() => _ProduksiDaerahScreenState();
}

class _ProduksiDaerahScreenState extends State<ProduksiDaerahScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  
  String _searchQuery = '';
  String _selectedYear = 'Semua';
  String _sortBy = 'nama_kecamatan'; // 'nama_kecamatan', 'total_luas', 'total_panen', 'produktivitas'

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _searchController.addListener(() {
      setState(() {
        _searchQuery = _searchController.text;
      });
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchStatistikData();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  String _formatNumber(double val) {
    return val.toStringAsFixed(2).replaceAll('.', ',').replaceFirst(RegExp(r',00$'), '');
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
    final isDataLoading = provider.isStatistikLoading;
    final statData = provider.statistikData;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          'Produksi Daerah',
          style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF047857),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          labelStyle: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 13),
          unselectedLabelStyle: GoogleFonts.poppins(fontWeight: FontWeight.normal, fontSize: 13),
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Statistik & Analisis', icon: Icon(Icons.analytics_rounded, size: 20)),
            Tab(text: 'Tabel Rekapitulasi', icon: Icon(Icons.table_rows_rounded, size: 20)),
          ],
        ),
      ),
      body: isDataLoading && statData == null
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF047857)))
          : statData == null
              ? _buildErrorView()
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildStatistikTab(statData),
                    _buildRekapitulasiTab(statData),
                  ],
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.cloud_off_rounded, size: 64, color: Colors.grey),
            const SizedBox(height: 16),
            Text(
              'Gagal memuat data statistik.',
              style: GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.bold, color: const Color(0xFF1E293B)),
            ),
            const SizedBox(height: 8),
            Text(
              'Pastikan koneksi internet aktif dan server berjalan dengan baik.',
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(color: Colors.grey[600], fontSize: 13),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () {
                context.read<FarmingProvider>().fetchStatistikData();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF047857),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: Text('Coba Lagi', style: GoogleFonts.poppins(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  // ==================== TAB 1: STATISTIK & ANALISIS ====================

  Widget _buildStatistikTab(Map<String, dynamic> data) {
    final summary = data['summary'] as Map<String, dynamic>? ?? {};
    final chartPanen = data['chart_panen_kecamatan'] as List<dynamic>? ?? [];
    final chartTipe = data['chart_luas_tipe_lahan'] as List<dynamic>? ?? [];
    final chartLuasKec = data['chart_luas_kecamatan'] as List<dynamic>? ?? [];
    final kecamatanAll = data['kecamatan_all'] as List<dynamic>? ?? [];
    final kelurahanAll = data['kelurahan_all'] as List<dynamic>? ?? [];
    final lahanAll = data['lahan_all'] as List<dynamic>? ?? [];
    final rekapRows = data['tabel_rekap'] as List<dynamic>? ?? [];

    final double totalLuas = double.tryParse(summary['total_luas_ha']?.toString() ?? '0') ?? 0.0;
    final double totalPanen = double.tryParse(summary['total_panen_ton']?.toString() ?? '0') ?? 0.0;

    return RefreshIndicator(
      onRefresh: () async {
        await context.read<FarmingProvider>().fetchStatistikData();
      },
      color: const Color(0xFF047857),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Row 1: Summary Cards
            Row(
              children: [
                Expanded(
                  child: _buildSummaryCard(
                    'Kecamatan',
                    summary['total_kecamatan']?.toString() ?? '0',
                    Icons.business_rounded,
                    Colors.indigo,
                    onTap: () => _showKecamatanListSheet(context, chartPanen, chartLuasKec, kecamatanAll),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildSummaryCard(
                    'Kelurahan/Desa',
                    summary['total_kelurahan']?.toString() ?? '0',
                    Icons.home_rounded,
                    Colors.blue,
                    onTap: () => _showKelurahanListSheet(context, kelurahanAll),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildSummaryCard(
                    'Lahan Sawah',
                    summary['total_lahan_sawah']?.toString() ?? '0',
                    Icons.landscape_rounded,
                    Colors.green,
                    onTap: () => _showLahanListSheet(context, lahanAll, showArea: false),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildSummaryCard(
                    'Total Luas',
                    '${_formatNumber(totalLuas)} Ha',
                    Icons.area_chart_rounded,
                    const Color(0xFF047857),
                    onTap: () => _showLahanListSheet(context, lahanAll, showArea: true),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            
            // Total Hasil Panen Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF047857), Color(0xFF10B981)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF047857).withValues(alpha: 0.2),
                    blurRadius: 12,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      color: Colors.white24,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(Icons.grass_rounded, color: Colors.white, size: 28),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'TOTAL HASIL PANEN DAERAH',
                          style: GoogleFonts.poppins(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: Colors.white70,
                            letterSpacing: 1.2,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${_formatNumber(totalPanen)} Ton',
                          style: GoogleFonts.poppins(
                            fontSize: 26,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Section: Hasil Panen Per Kecamatan (Bar Visuals)
            _buildSectionHeader('Hasil Panen per Kecamatan', 'Total produksi dalam satuan Ton (Klik untuk detail kelurahan/desa)'),
            const SizedBox(height: 12),
            _buildPanenKecamatanList(chartPanen, rekapRows),
            const SizedBox(height: 24),

            // Section: Sebaran Tipe Lahan
            _buildSectionHeader('Distribusi Tipe Lahan', 'Perbandingan luas lahan (Hektar) per tipe'),
            const SizedBox(height: 12),
            _buildTipeLahanList(chartTipe, lahanAll),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  void _showKecamatanListSheet(BuildContext context, List<dynamic> chartPanen, List<dynamic> chartLuasKec, List<dynamic> kecamatanAll) {
    // Get all unique kecamatan names
    final Set<String> allKecamatanNames = {};
    if (kecamatanAll.isNotEmpty) {
      for (var item in kecamatanAll) {
        final name = item['nama_kecamatan'];
        if (name != null) allKecamatanNames.add(name);
      }
    } else {
      // Fallback
      for (var item in chartPanen) {
        final name = item['nama_kecamatan'];
        if (name != null) allKecamatanNames.add(name);
      }
      for (var item in chartLuasKec) {
        final name = item['nama_kecamatan'];
        if (name != null) allKecamatanNames.add(name);
      }
    }

    final sortedKecamatanList = allKecamatanNames.toList()..sort();

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
                    'Daftar Kecamatan',
                    style: GoogleFonts.poppins(
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
                child: sortedKecamatanList.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data kecamatan.',
                          style: GoogleFonts.poppins(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: sortedKecamatanList.length,
                        itemBuilder: (context, index) {
                          final name = sortedKecamatanList[index];

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                CircleAvatar(
                                  backgroundColor: const Color(0xFFEDF8DC),
                                  foregroundColor: const Color(0xFF047857),
                                  radius: 16,
                                  child: Text(
                                    '${index + 1}',
                                    style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Text(
                                    name,
                                    style: GoogleFonts.poppins(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: const Color(0xFF1E293B),
                                    ),
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

  void _showKelurahanListSheet(BuildContext context, List<dynamic> kelurahanAll) {
    final List<String> allKelurahanNames = [];
    if (kelurahanAll.isNotEmpty) {
      for (var item in kelurahanAll) {
        final name = item['nama_kelurahan'];
        final kec = item['nama_kecamatan'];
        if (name != null) {
          if (kec != null) {
            allKelurahanNames.add('$name (Kec. $kec)');
          } else {
            allKelurahanNames.add(name);
          }
        }
      }
    }

    final sortedKelurahanList = allKelurahanNames..sort();

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
                    'Daftar Kelurahan/Desa',
                    style: GoogleFonts.poppins(
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
                child: sortedKelurahanList.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data kelurahan/desa.',
                          style: GoogleFonts.poppins(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: sortedKelurahanList.length,
                        itemBuilder: (context, index) {
                          final name = sortedKelurahanList[index];

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                CircleAvatar(
                                  backgroundColor: const Color(0xFFEDF8DC),
                                  foregroundColor: const Color(0xFF047857),
                                  radius: 16,
                                  child: Text(
                                    '${index + 1}',
                                    style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Text(
                                    name,
                                    style: GoogleFonts.poppins(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: const Color(0xFF1E293B),
                                    ),
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

  void _showLahanListSheet(BuildContext context, List<dynamic> lahanAll, {required bool showArea}) {
    final isPejabat = context.read<AuthProvider>().currentUser?.roleId == 3;
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
                    showArea ? 'Daftar Luas Lahan Sawah' : 'Daftar Lahan Sawah',
                    style: GoogleFonts.poppins(
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
              const SizedBox(height: 8),
              if (isPejabat) ...[
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () => _exportLahanSawahReport('pdf'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFDC2626),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          elevation: 1,
                        ),
                        icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                        label: Text('Export PDF', style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () => _exportLahanSawahReport('excel'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF16A34A),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          elevation: 1,
                        ),
                        icon: const Icon(Icons.table_view_rounded, size: 16),
                        label: Text('Export Excel', style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
                const Divider(color: Color(0xFFE2E8F0), height: 24),
              ] else ...[
                const Divider(color: Color(0xFFE2E8F0), height: 12),
              ],
              Expanded(
                child: lahanAll.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data lahan sawah.',
                          style: GoogleFonts.poppins(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: lahanAll.length,
                        itemBuilder: (context, index) {
                          final item = lahanAll[index];
                          final name = item['nama_lahan'] ?? 'Lahan';
                          final luas = double.tryParse(item['luas']?.toString() ?? '0') ?? 0.0;
                          final kec = item['nama_kecamatan'] ?? '-';
                          final kel = item['nama_kelurahan'] ?? '-';
                          final pemilik = item['pemilik_nama'] ?? '-';

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                CircleAvatar(
                                  backgroundColor: const Color(0xFFEDF8DC),
                                  foregroundColor: const Color(0xFF047857),
                                  radius: 16,
                                  child: Text(
                                    '${index + 1}',
                                    style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        name,
                                        style: GoogleFonts.poppins(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                          color: const Color(0xFF1E293B),
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Kec. $kec, Kel. $kel',
                                        style: GoogleFonts.poppins(
                                          fontSize: 11,
                                          color: Colors.grey[600],
                                        ),
                                      ),
                                      Text(
                                        'Pemilik: $pemilik',
                                        style: GoogleFonts.poppins(
                                          fontSize: 10,
                                          color: Colors.grey[500],
                                          fontStyle: FontStyle.italic,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                if (showArea)
                                  Text(
                                    '${_formatNumber(luas)} Ha',
                                    style: GoogleFonts.poppins(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 15,
                                      color: const Color(0xFF047857),
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

  Future<void> _exportLahanSawahReport(String format) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;
    if (token == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Silakan login sebagai Pejabat Dinas untuk mengunduh laporan.')),
        );
      }
      return;
    }

    final baseUrl = ApiEndpoints.baseUrl;
    final uri = Uri.parse(baseUrl);
    final webAppUrl = '${uri.scheme}://${uri.host}:8080';
    final downloadUrl = Uri.parse('$webAppUrl/pejabat/lahan-sawah/$format?token=$token');

    if (await canLaunchUrl(downloadUrl)) {
      await launchUrl(downloadUrl, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Tidak dapat mengunduh laporan Lahan Sawah ($format).')),
        );
      }
    }
  }

  Future<void> _exportKecamatanProduksiReport(String format) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;
    if (token == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Silakan login sebagai Pejabat Dinas untuk mengunduh laporan.')),
        );
      }
      return;
    }

    final baseUrl = ApiEndpoints.baseUrl;
    final uri = Uri.parse(baseUrl);
    final webAppUrl = '${uri.scheme}://${uri.host}:8080';
    final downloadUrl = Uri.parse('$webAppUrl/pejabat/produksi-kecamatan/$format?token=$token');

    if (await canLaunchUrl(downloadUrl)) {
      await launchUrl(downloadUrl, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Tidak dapat mengunduh laporan Produksi Kecamatan ($format).')),
        );
      }
    }
  }

  Future<void> _exportKelurahanProduksiReport(String kecamatanName, String format) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;
    if (token == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Silakan login sebagai Pejabat Dinas untuk mengunduh laporan.')),
        );
      }
      return;
    }

    final baseUrl = ApiEndpoints.baseUrl;
    final uri = Uri.parse(baseUrl);
    final webAppUrl = '${uri.scheme}://${uri.host}:8080';
    final downloadUrl = Uri.parse('$webAppUrl/pejabat/produksi-kelurahan/$format?token=$token&kecamatan=${Uri.encodeComponent(kecamatanName)}');

    if (await canLaunchUrl(downloadUrl)) {
      await launchUrl(downloadUrl, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Tidak dapat mengunduh laporan Desa/Kelurahan ($format).')),
        );
      }
    }
  }

  Widget _buildSummaryCard(String title, String value, IconData icon, Color color, {VoidCallback? onTap}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
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
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        title,
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey[500],
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Icon(icon, color: color.withValues(alpha: 0.8), size: 18),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  value,
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF1E293B),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 4,
              height: 18,
              decoration: BoxDecoration(
                color: const Color(0xFF047857),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                title,
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF1E293B),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 2),
        Padding(
          padding: const EdgeInsets.only(left: 12.0),
          child: Text(
            subtitle,
            style: GoogleFonts.poppins(fontSize: 11, color: Colors.grey[500]),
          ),
        ),
      ],
    );
  }

  Widget _buildPanenKecamatanList(List<dynamic> chartPanen, List<dynamic> rekapRows) {
    if (chartPanen.isEmpty) {
      return _buildEmptyListCard('Belum ada data panen per kecamatan.');
    }

    // Find max value for normalization
    double maxPanen = 1.0;
    for (var item in chartPanen) {
      final val = double.tryParse(item['total_panen']?.toString() ?? '0') ?? 0.0;
      if (val > maxPanen) maxPanen = val;
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          ...List.generate(chartPanen.length, (index) {
            final item = chartPanen[index];
            final name = item['nama_kecamatan'] ?? 'Kecamatan';
            final val = double.tryParse(item['total_panen']?.toString() ?? '0') ?? 0.0;
            final percent = val / maxPanen;

            return Container(
              margin: const EdgeInsets.only(bottom: 12.0),
              child: InkWell(
                onTap: () {
                  _showKelurahanPanenSheet(context, name, rekapRows);
                },
                borderRadius: BorderRadius.circular(16),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8.0, horizontal: 8.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              name,
                              style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF334155)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Row(
                            children: [
                              Text(
                                '${_formatNumber(val)} Ton',
                                style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF047857)),
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.chevron_right_rounded, size: 16, color: Colors.grey),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: LinearProgressIndicator(
                          value: percent,
                          backgroundColor: const Color(0xFFF1F5F9),
                          color: const Color(0xFF047857),
                          minHeight: 8,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
          const Divider(color: Color(0xFFE2E8F0), height: 24),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _exportKecamatanProduksiReport('pdf'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFDC2626),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    elevation: 1,
                  ),
                  icon: const Icon(Icons.picture_as_pdf_rounded, size: 14),
                  label: Text(
                    'Export PDF',
                    style: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _exportKecamatanProduksiReport('excel'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF16A34A),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    elevation: 1,
                  ),
                  icon: const Icon(Icons.table_view_rounded, size: 14),
                  label: Text(
                    'Export Excel',
                    style: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showKelurahanPanenSheet(BuildContext context, String kecamatanName, List<dynamic> rekapRows) {
    // Filter kelurahan by selected kecamatan name
    final filteredKelurahan = rekapRows.where((row) {
      final kec = row['nama_kecamatan']?.toString().toLowerCase();
      return kec == kecamatanName.toLowerCase();
    }).toList();

    double totalPanen = 0.0;
    double totalLuas = 0.0;
    int totalLahan = 0;

    for (var row in filteredKelurahan) {
      totalPanen += double.tryParse(row['total_panen']?.toString() ?? '0') ?? 0.0;
      totalLuas += double.tryParse(row['total_luas']?.toString() ?? '0') ?? 0.0;
      totalLahan += int.tryParse(row['jumlah_lahan']?.toString() ?? '0') ?? 0;
    }

    final double avgProduktivitas = totalLuas > 0 ? totalPanen / totalLuas : 0.0;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      backgroundColor: Colors.white,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Detail Produksi Kecamatan',
                          style: GoogleFonts.poppins(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF14280B),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Kecamatan $kecamatanName',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              const Divider(color: Color(0xFFE2E8F0), height: 24),
              
              // Grid 2x2 data kecamatan
              Row(
                children: [
                  Expanded(
                    child: _buildKecamatanDetailCard(
                      'Total Hasil Panen',
                      '${_formatNumber(totalPanen)} Ton',
                      Icons.grass_rounded,
                      const Color(0xFF047857),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildKecamatanDetailCard(
                      'Luas Lahan',
                      '${_formatNumber(totalLuas)} Ha',
                      Icons.landscape_rounded,
                      const Color(0xFF0284C7),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildKecamatanDetailCard(
                      'Jumlah Lahan Sawah',
                      '$totalLahan Lahan',
                      Icons.layers_rounded,
                      const Color(0xFFD97706),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildKecamatanDetailCard(
                      'Produktivitas Rata-rata',
                      '${_formatNumber(avgProduktivitas)} Ton/Ha',
                      Icons.trending_up_rounded,
                      const Color(0xFF7C3AED),
                    ),
                  ),
                ],
              ),
              
              const Divider(color: Color(0xFFE2E8F0), height: 32),
              
              Text(
                'Unduh Laporan Kecamatan',
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _exportKelurahanProduksiReport(kecamatanName, 'pdf'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        elevation: 1,
                      ),
                      icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                      label: Text(
                        'Export PDF',
                        style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _exportKelurahanProduksiReport(kecamatanName, 'excel'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF16A34A),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        elevation: 1,
                      ),
                      icon: const Icon(Icons.table_view_rounded, size: 16),
                      label: Text(
                        'Export Excel',
                        style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  Widget _buildKecamatanDetailCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 18),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  title,
                  style: GoogleFonts.poppins(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF64748B),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: GoogleFonts.poppins(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF1E293B),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTipeLahanList(List<dynamic> chartTipe, List<dynamic> lahanAll) {
    if (chartTipe.isEmpty) {
      return _buildEmptyListCard('Belum ada data tipe lahan.');
    }

    double totalLuas = 0.0;
    for (var item in chartTipe) {
      totalLuas += double.tryParse(item['total_luas']?.toString() ?? '0') ?? 0.0;
    }
    if (totalLuas == 0.0) totalLuas = 1.0;

    final List<Color> colors = [Colors.blue, Colors.green, Colors.orange, Colors.purple, Colors.pink];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          ...List.generate(chartTipe.length, (index) {
            final item = chartTipe[index];
            final name = item['nama_tipe'] ?? item['tipe_lahan'] ?? 'Belum Ditentukan';
            final val = double.tryParse(item['total_luas']?.toString() ?? '0') ?? 0.0;
            final pct = (val / totalLuas) * 100;

            return Container(
              margin: const EdgeInsets.only(bottom: 12.0),
              child: InkWell(
                onTap: () => _showLahanByTipeSheet(context, name, lahanAll),
                borderRadius: BorderRadius.circular(16),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              name,
                              style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF334155)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Row(
                            children: [
                              Text(
                                '${_formatNumber(val)} Ha (${pct.toStringAsFixed(1)}%)',
                                style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey[700]),
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.chevron_right_rounded, size: 16, color: Colors.grey),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: LinearProgressIndicator(
                          value: pct / 100,
                          backgroundColor: const Color(0xFFF1F5F9),
                          color: colors[index % colors.length],
                          minHeight: 8,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
          const Divider(color: Color(0xFFE2E8F0), height: 24),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _exportLahanSawahReport('pdf'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFDC2626),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    elevation: 1,
                  ),
                  icon: const Icon(Icons.picture_as_pdf_rounded, size: 14),
                  label: Text(
                    'Export PDF',
                    style: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _exportLahanSawahReport('excel'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF16A34A),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    elevation: 1,
                  ),
                  icon: const Icon(Icons.table_view_rounded, size: 14),
                  label: Text(
                    'Export Excel',
                    style: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showLahanByTipeSheet(BuildContext context, String tipeName, List<dynamic> lahanAll) {
    final filteredLahan = lahanAll.where((lahan) {
      final type = lahan['tipe_lahan']?.toString().toLowerCase() ?? '';
      return type == tipeName.toLowerCase();
    }).toList();

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
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tipeName,
                          style: GoogleFonts.poppins(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF14280B),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Daftar Lahan Terverifikasi',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
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
                child: filteredLahan.isEmpty
                    ? Center(
                        child: Text(
                          'Belum ada data lahan sawah.',
                          style: GoogleFonts.poppins(color: Colors.grey[500]),
                        ),
                      )
                    : ListView.builder(
                        itemCount: filteredLahan.length,
                        itemBuilder: (context, index) {
                          final item = filteredLahan[index];
                          final name = item['nama_lahan'] ?? 'Lahan Sawah';
                          final pemilik = item['pemilik_nama'] ?? '-';
                          final luas = double.tryParse(item['luas']?.toString() ?? '0') ?? 0.0;
                          final kecamatan = item['nama_kecamatan'] ?? '-';
                          final kelurahan = item['nama_kelurahan'] ?? '-';

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                CircleAvatar(
                                  backgroundColor: const Color(0xFFEDF8DC),
                                  foregroundColor: const Color(0xFF047857),
                                  radius: 16,
                                  child: Text(
                                    '${index + 1}',
                                    style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        name,
                                        style: GoogleFonts.poppins(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                          color: const Color(0xFF1E293B),
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Pemilik: $pemilik • Kec. $kecamatan, Kel. $kelurahan',
                                        style: GoogleFonts.poppins(
                                          fontSize: 11,
                                          color: Colors.grey[600],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFEDF8DC),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    '${_formatNumber(luas)} Ha',
                                    style: GoogleFonts.poppins(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 13,
                                      color: const Color(0xFF047857),
                                    ),
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



  Widget _buildEmptyListCard(String text) {
    return Container(
      padding: const EdgeInsets.all(24),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Text(
        text,
        style: GoogleFonts.poppins(color: Colors.grey[500], fontSize: 13),
      ),
    );
  }

  // ==================== TAB 2: TABEL REKAPITULASI ====================

  Widget _buildRekapitulasiTab(Map<String, dynamic> data) {
    final rawRekap = data['tabel_rekap'] as List<dynamic>? ?? [];
    final isPejabat = context.watch<AuthProvider>().currentUser?.roleId == 3;

    // Filter by Search Query & Year
    List<dynamic> filteredList = rawRekap.where((item) {
      final loc = '${item['nama_kecamatan'] ?? ''} ${item['nama_kelurahan'] ?? ''}'.toLowerCase();
      final matchSearch = _searchQuery.isEmpty || loc.contains(_searchQuery.toLowerCase());
      
      final year = item['tahun_lbs']?.toString() ?? '';
      final matchYear = _selectedYear == 'Semua' || year == _selectedYear;

      return matchSearch && matchYear;
    }).toList();

    // Sort List
    filteredList.sort((a, b) {
      if (_sortBy == 'nama_kecamatan') {
        final String nameA = a['nama_kecamatan']?.toString() ?? '';
        final String nameB = b['nama_kecamatan']?.toString() ?? '';
        return nameA.compareTo(nameB);
      } else if (_sortBy == 'total_luas') {
        final double valA = double.tryParse(a['total_luas']?.toString() ?? '0') ?? 0.0;
        final double valB = double.tryParse(b['total_luas']?.toString() ?? '0') ?? 0.0;
        return valB.compareTo(valA); // Descending
      } else if (_sortBy == 'total_panen') {
        final double valA = double.tryParse(a['total_panen']?.toString() ?? '0') ?? 0.0;
        final double valB = double.tryParse(b['total_panen']?.toString() ?? '0') ?? 0.0;
        return valB.compareTo(valA); // Descending
      } else if (_sortBy == 'produktivitas') {
        final double luasA = double.tryParse(a['total_luas']?.toString() ?? '0') ?? 0.0;
        final double panenA = double.tryParse(a['total_panen']?.toString() ?? '0') ?? 0.0;
        final double prodA = luasA > 0 ? panenA / luasA : 0.0;

        final double luasB = double.tryParse(b['total_luas']?.toString() ?? '0') ?? 0.0;
        final double panenB = double.tryParse(b['total_panen']?.toString() ?? '0') ?? 0.0;
        final double prodB = luasB > 0 ? panenB / luasB : 0.0;

        return prodB.compareTo(prodA); // Descending
      }
      return 0;
    });

    return Column(
      children: [
        // Filter Panel
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.white,
          child: Column(
            children: [
              // Search Input
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: TextField(
                  controller: _searchController,
                  style: GoogleFonts.poppins(fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Cari kecamatan atau kelurahan...',
                    hintStyle: GoogleFonts.poppins(fontSize: 13, color: Colors.grey[500]),
                    prefixIcon: const Icon(Icons.search, color: Color(0xFF047857), size: 18),
                    border: InputBorder.none,
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  ),
                ),
              ),
              const SizedBox(height: 8),

              // Filter Year & Sort Dropdowns
              Row(
                children: [
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedYear,
                          isExpanded: true,
                          style: GoogleFonts.poppins(fontSize: 12, color: const Color(0xFF1E293B), fontWeight: FontWeight.bold),
                          items: const [
                            DropdownMenuItem(value: 'Semua', child: Text('Semua Tahun')),
                            DropdownMenuItem(value: '2017', child: Text('Tahun LBS 2017')),
                            DropdownMenuItem(value: '2024', child: Text('Tahun LBS 2024')),
                          ],
                          onChanged: (val) {
                            if (val != null) {
                              setState(() {
                                _selectedYear = val;
                              });
                            }
                          },
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _sortBy,
                          isExpanded: true,
                          style: GoogleFonts.poppins(fontSize: 12, color: const Color(0xFF047857), fontWeight: FontWeight.bold),
                          items: const [
                            DropdownMenuItem(value: 'nama_kecamatan', child: Text('Urut: Nama A-Z')),
                            DropdownMenuItem(value: 'total_luas', child: Text('Urut: Luas Terluas')),
                            DropdownMenuItem(value: 'total_panen', child: Text('Urut: Panen Terbanyak')),
                            DropdownMenuItem(value: 'produktivitas', child: Text('Urut: Terproduktif')),
                          ],
                          onChanged: (val) {
                            if (val != null) {
                              setState(() {
                                _sortBy = val;
                              });
                            }
                          },
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // List View of Rekap
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async {
              await context.read<FarmingProvider>().fetchStatistikData();
            },
            color: const Color(0xFF047857),
            child: filteredList.isEmpty
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Text(
                        'Data rekap tidak ditemukan.',
                        style: GoogleFonts.poppins(color: Colors.grey[500], fontSize: 13),
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: filteredList.length,
                    itemBuilder: (context, index) {
                      final item = filteredList[index];
                      final double luas = double.tryParse(item['total_luas']?.toString() ?? '0') ?? 0.0;
                      final double panen = double.tryParse(item['total_panen']?.toString() ?? '0') ?? 0.0;
                      final double prod = luas > 0 ? panen / luas : 0.0;
                      final rincian = item['rincian_tipe_lahan'] as List<dynamic>? ?? [];

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(20),
                          side: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        color: Colors.white,
                        child: ExpansionTile(
                          shape: const Border(),
                          collapsedShape: const Border(),
                          tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          title: Text(
                            item['nama_kecamatan'] ?? '-',
                            style: GoogleFonts.poppins(
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                              color: const Color(0xFF1E293B),
                            ),
                          ),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4.0),
                            child: Text(
                              'Kel/Desa: ${item['nama_kelurahan'] ?? '-'} • Tahun: ${item['tahun_lbs'] ?? '-'}',
                              style: GoogleFonts.poppins(fontSize: 11, color: Colors.grey[500]),
                            ),
                          ),
                          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                          expandedCrossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const Divider(color: Color(0xFFE2E8F0), height: 12),
                            const SizedBox(height: 6),
                            
                            // Stats Row
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                _buildExpandedStatItem('Jumlah Lahan', '${item['jumlah_lahan']} Lahan'),
                                _buildExpandedStatItem('Total Luas', '${_formatNumber(luas)} Ha'),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                _buildExpandedStatItem('Hasil Panen', '${_formatNumber(panen)} Ton'),
                                _buildExpandedStatItem('Produktivitas', '${_formatNumber(prod)} Ton/Ha'),
                              ],
                            ),
                            
                            // Rincian per Tipe Lahan
                            if (rincian.isNotEmpty) ...[
                              const SizedBox(height: 16),
                              Text(
                                'Rincian Per Tipe Lahan:',
                                style: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey[600]),
                              ),
                              const SizedBox(height: 6),
                              Wrap(
                                spacing: 6,
                                runSpacing: 6,
                                children: List.generate(rincian.length, (tipeIdx) {
                                  final tipe = rincian[tipeIdx];
                                  final tipeLuas = double.tryParse(tipe['total_luas']?.toString() ?? '0') ?? 0.0;
                                  if (tipeLuas <= 0) return const SizedBox.shrink();
                                  
                                  return Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(color: const Color(0xFFE2E8F0)),
                                    ),
                                    child: Text(
                                      '${tipe['nama_tipe'] ?? 'Lahan'}: ${_formatNumber(tipeLuas)} Ha',
                                      style: GoogleFonts.poppins(fontSize: 10, color: const Color(0xFF334155), fontWeight: FontWeight.bold),
                                    ),
                                  );
                                }),
                              ),
                            ],
                            if (isPejabat) ...[
                              const SizedBox(height: 16),
                              ElevatedButton.icon(
                                onPressed: () {
                                  final kecName = item['nama_kecamatan'];
                                  if (kecName != null) {
                                    _exportKelurahanProduksiReport(kecName, 'pdf');
                                  }
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFDC2626),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  elevation: 0,
                                ),
                                icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                                label: Text(
                                  'Cetak PDF',
                                  style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildExpandedStatItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(fontSize: 10, color: Colors.grey[500], fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: GoogleFonts.poppins(fontSize: 13, color: const Color(0xFF1E293B), fontWeight: FontWeight.bold),
        ),
      ],
    );
  }
}
