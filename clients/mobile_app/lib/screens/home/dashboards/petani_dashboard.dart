import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../../models/user.dart';
import '../../../providers/farming_provider.dart';
import '../tambah_lahan_screen.dart';
import '../lapor_tanam_screen.dart';
import '../lapor_panen_screen.dart';

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

  // Helper untuk format nama bulan Indonesia
  String _getIndonesianMonthName(int month) {
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
    return months[month - 1];
  }

  // Helper untuk format desimal ke koma Indonesia
  String _formatDouble(double value) {
    return value.toStringAsFixed(2).replaceAll('.', ',');
  }

  @override
  Widget build(BuildContext context) {
    final farmingProvider = context.watch<FarmingProvider>();
    final user = widget.user;
    final roleId = user?.roleId ?? 1;
    final roleName = roleId == 5 ? 'Brigade Pangan' : 'Kelompok Tani';

    // Pengecekan masa tanam (Sesuai logic backend web app)
    final currentMonth = DateTime.now().month;
    final isKelompokTaniAllowed = (currentMonth >= 1 && currentMonth <= 9);
    final isBrigadePanganAllowed = [10, 11, 12, 1].contains(currentMonth);
    final isAllowedToPlant =
        (roleId == 1 && isKelompokTaniAllowed) ||
        (roleId == 5 && isBrigadePanganAllowed);

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

    final lahanList = farmingProvider.lahanData['data'] as List<dynamic>;
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
            _buildProfileCard(user, roleName),
            const SizedBox(height: 20),

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

            // 3. Action Buttons Row (Web-like design)
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  if (roleId == 1) ...[
                    _buildActionButton(
                      label: 'Tambah Lahan',
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
                    const SizedBox(width: 8),
                  ],
                  _buildActionButton(
                    label: isAllowedToPlant
                        ? 'Lapor Tanam'
                        : 'Lapor Tanam (Kunci)',
                    onPressed: isAllowedToPlant
                        ? () async {
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
                          }
                        : null,
                    textColor: isAllowedToPlant
                        ? Colors.white
                        : Colors.grey.shade500,
                    bgColor: isAllowedToPlant
                        ? const Color(0xFF3E7D00)
                        : Colors.grey[300]!,
                  ),
                  if (roleId == 1) ...[
                    const SizedBox(width: 8),
                    _buildActionButton(
                      label: 'Lapor Hasil Panen',
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
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),

            // 4. Lock Alert Banner
            if (!isAllowedToPlant) ...[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFFBEB),
                  border: Border.all(color: const Color(0xFFFDE68A)),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.warning_amber_rounded,
                      color: Color(0xFFD97706),
                      size: 20,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Masa Tanam Sedang Terkunci',
                            style: GoogleFonts.inter(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF92400E),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Saat ini (Bulan ${_getIndonesianMonthName(currentMonth)}) bukan jadwal masa tanam Anda. Masa tanam untuk ${roleId == 5 ? 'Brigade Pangan adalah Oktober - Januari' : 'Kelompok Tani adalah Januari - September'}. Tombol lapor tanam dinonaktifkan sementara.',
                            style: GoogleFonts.inter(
                              fontSize: 11,
                              color: const Color(0xFFB45309),
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
            ],

            // 5. Statistics Cards
            LayoutBuilder(
              builder: (context, constraints) {
                final cardWidth =
                    (constraints.maxWidth - 16) / 2; // 2 cards side-by-side
                return Wrap(
                  spacing: 16,
                  runSpacing: 16,
                  children: [
                    _buildStatCard(
                      title: roleId == 5 ? 'PROSES AKTIF' : 'LAHAN TERDAFTAR',
                      value: roleId == 5
                          ? '${activeCycles.length}'
                          : '$totalLahan',
                      desc: roleId == 5
                          ? 'Siklus tanam yang sedang digarap'
                          : 'Pengajuan lahan pada akun Anda',
                      width: cardWidth,
                    ),
                    _buildStatCard(
                      title: 'PRODUKSI TAHUN INI',
                      value: '${_formatDouble(totalProduksi)} Ton',
                      desc: 'Hanya hasil panen yang disetujui petugas',
                      width: cardWidth,
                      isDark: true,
                    ),
                    _buildStatCard(
                      title: 'ATURAN MASA TANAM',
                      value: roleId == 5
                          ? 'Oktober - Januari'
                          : 'Januari - September',
                      desc: roleId == 5
                          ? 'Bibit unggul lahan Kelompok Tani induk'
                          : 'Bibit lokal sebagai pemilik lahan',
                      width: constraints.maxWidth,
                      isLightGreen: true,
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 24),

            // 6. Active cycles (Padi dalam masa tanam)
            _buildActiveCyclesSection(activeCycles),
            const SizedBox(height: 24),

            // 7. Lahan List Section
            _buildLahanListSection(
              lahanList,
              farmingProvider.lahanData,
              roleId,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileCard(User? user, String roleName) {
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
                      user?.namaLengkap ?? 'Petani',
                      style: GoogleFonts.outfit(
                        fontSize: 18,
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
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
                        roleName,
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

  Widget _buildActionButton({
    required String label,
    required VoidCallback? onPressed,
    required Color textColor,
    required Color bgColor,
    Color? borderColor,
  }) {
    return OutlinedButton(
      onPressed: onPressed,
      style: OutlinedButton.styleFrom(
        foregroundColor: textColor,
        backgroundColor: bgColor,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
        side: borderColor != null
            ? BorderSide(color: borderColor, width: 1.5)
            : BorderSide.none,
        elevation: 0,
      ),
      child: Text(
        label,
        style: GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.bold),
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
                final isBrigade = cycle['peran_pelapor'] == 'brigade_pangan';

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
                                  'Bibit: ${cycle['nama_bibit'] ?? '-'} · ${isBrigade ? 'Brigade Pangan' : 'Kelompok Tani'}',
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
    int roleId,
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
                  roleId == 5
                      ? 'Daftar lahan garapan Brigade Pangan'
                      : 'Daftar lahan milik Kelompok Tani',
                  style: GoogleFonts.outfit(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  roleId == 5
                      ? 'Daftar lahan yang Anda garap dan kelola.'
                      : 'Status pengajuan dan catatan verifikasi petugas.',
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
                  roleId == 5
                      ? 'Belum ada lahan yang ditugaskan.'
                      : 'Belum ada lahan yang diajukan.',
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
                final lahan = lahanList[index];
                final status = lahan['status_verifikasi'] ?? 'PENDING';

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
                            border: Border.all(color: const Color(0xFFFEE2E2)),
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
                            ],
                          ),
                        ),
                      ],
                    ],
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
