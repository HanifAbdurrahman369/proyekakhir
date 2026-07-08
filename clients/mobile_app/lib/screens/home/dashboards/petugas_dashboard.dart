import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../../models/user.dart';
import '../../../providers/farming_provider.dart';
import '../petugas_lahan_termonitor_content.dart';
import '../petugas_verifikasi_screen.dart';
import '../../admin_komunitas_screen.dart';

class PetugasDashboard extends StatefulWidget {
  final User? user;

  const PetugasDashboard({super.key, required this.user});

  @override
  State<PetugasDashboard> createState() => _PetugasDashboardState();
}

class _PetugasDashboardState extends State<PetugasDashboard> {
  String _currentView =
      'dashboard'; // 'dashboard', 'verifikasi', 'spasial', 'termonitor'
  String _verifikasiTab = 'lahan'; // 'lahan', 'panen'
  String _spasialTab = 'belum'; // 'belum', 'sudah'

  // Search controllers
  final TextEditingController _spasialSearchController =
      TextEditingController();
  String _spasialSearchQuery = '';

  // Form controllers for Parameter Lingkungan
  final _parameterFormKey = GlobalKey<FormState>();
  int? _selectedLahanId;
  DateTime _tanggalCek = DateTime.now();
  final TextEditingController _phAirController = TextEditingController();
  final TextEditingController _tinggiAirController = TextEditingController();
  String _statusAir = 'Normal';
  String _kekeruhanAir = 'Jernih';
  final TextEditingController _catatanPetugasController =
      TextEditingController();

  @override
  void initState() {
    super.initState();
    _spasialSearchController.addListener(() {
      setState(() {
        _spasialSearchQuery = _spasialSearchController.text.toLowerCase();
      });
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchPetugasDashboardData();
    });
  }

  @override
  void dispose() {
    _spasialSearchController.dispose();
    _phAirController.dispose();
    _tinggiAirController.dispose();
    _catatanPetugasController.dispose();
    super.dispose();
  }

  Future<void> _refresh() {
    final provider = context.read<FarmingProvider>();
    if (_currentView == 'termonitor') {
      return provider.fetchLahanTermonitorData();
    }
    return provider.fetchPetugasDashboardData();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
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
            _buildViewHeader(),
            const SizedBox(height: 20),
            if (provider.errorMessage != null)
              _buildErrorBox(provider.errorMessage!)
            else
              _buildViewContent(provider),
          ],
        ),
      ),
    );
  }

  // Helpers for formatters
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

  String _formatDouble(double value) {
    return value.toStringAsFixed(2).replaceAll('.', ',');
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _tanggalCek,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.green[800]!,
              onPrimary: Colors.white,
              onSurface: Colors.black87,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _tanggalCek) {
      setState(() {
        _tanggalCek = picked;
      });
    }
  }

  // Header render with back button capability
  Widget _buildViewHeader() {
    return Row(
      children: [
        if (_currentView != 'dashboard') ...[
          IconButton(
            icon: const Icon(
              Icons.arrow_back_ios_new_rounded,
              color: Color(0xFF14280B),
              size: 20,
            ),
            onPressed: () {
              setState(() {
                _currentView = 'dashboard';
              });
            },
          ),
          const SizedBox(width: 4),
        ],
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFFEDF8DC),
                  border: Border.all(color: const Color(0xFFDFECCC)),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'SiTani BATOLA',
                  style: GoogleFonts.inter(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF3E7D00),
                    letterSpacing: 1.2,
                  ),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                _currentView == 'dashboard'
                    ? 'Dashboard Petugas'
                    : _currentView == 'verifikasi'
                    ? 'Verifikasi Data Petani'
                    : _currentView == 'spasial'
                    ? 'Manajemen Data Spasial'
                    : _currentView == 'termonitor'
                    ? 'Lahan Termonitor (IoT)'
                    : 'Parameter Lingkungan',
                style: GoogleFonts.outfit(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF14280B),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  // Render view based on state selection
  Widget _buildViewContent(FarmingProvider provider) {
    switch (_currentView) {
      case 'dashboard':
        return _buildDashboardView(provider);
      case 'verifikasi':
        return _buildVerifikasiView(provider);
      case 'spasial':
        return _buildSpasialView(provider);
      case 'termonitor':
        return const PetugasLahanTermonitorContent(showIntro: false);
      case 'parameter':
        return _buildParameterView(provider);
      default:
        return _buildDashboardView(provider);
    }
  }

  // Sub-view 1: Ringkasan (Dashboard)
  Widget _buildDashboardView(FarmingProvider provider) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // 3 Statistic cards
        _buildWilayahPetugasCard(),
        const SizedBox(height: 12),
        _buildStatCard(
          title: 'Total Antrean',
          value: '${provider.totalPendingCount}',
          subtitle: 'Lahan baru + laporan hasil panen pending',
          icon: Icons.hourglass_empty_rounded,
          color: const Color(0xFF203C10),
          bgColor: const Color(0xFFEDF8DC),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                title: 'Pengajuan Lahan',
                value: '${provider.pendingLahanCount}',
                subtitle: 'Menunggu verifikasi',
                icon: Icons.landscape_rounded,
                color: Colors.amber[800]!,
                bgColor: Colors.amber[50]!,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                title: 'Laporan Panen',
                value: '${provider.pendingPanenCount}',
                subtitle: 'Menunggu legalisasi',
                icon: Icons.scale_rounded,
                color: Colors.green[800]!,
                bgColor: Colors.green[50]!,
              ),
            ),
          ],
        ),
        const SizedBox(height: 28),

        // Section Title
        Text(
          'Ringkasan Tugas Petugas',
          style: GoogleFonts.outfit(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Gunakan menu kerja berikut untuk menjalankan alur verifikasi dan pemetaan lahan.',
          style: GoogleFonts.inter(
            fontSize: 11,
            color: const Color(0xFF64748B),
          ),
        ),
        const SizedBox(height: 16),

        // Quick Action cards
        _buildActionMenuCard(
          title: 'Verifikasi Data Petani',
          subtitle: 'Setujui atau tolak pengajuan lahan & hasil panen.',
          icon: Icons.verified_user_rounded,
          color: Colors.blue[700]!,
          onTap: () {
            setState(() {
              _currentView = 'verifikasi';
              _verifikasiTab = 'lahan';
            });
          },
        ),
        const SizedBox(height: 12),
        _buildActionMenuCard(
          title: 'Manajemen Data Spasial',
          subtitle: 'Buat titik lokasi dan polygon batas area lahan sawah.',
          icon: Icons.map_rounded,
          color: Colors.teal[700]!,
          onTap: () {
            setState(() {
              _currentView = 'spasial';
              _spasialTab = 'belum';
            });
            provider.fetchSpasialLahan();
          },
        ),
        const SizedBox(height: 12),
        _buildActionMenuCard(
          title: 'Lahan Termonitor (IoT)',
          subtitle: 'Sinkronisasi data lahan dan sensor dari Huma.',
          icon: Icons.sensors_rounded,
          color: Colors.orange[800]!,
          onTap: () {
            setState(() {
              _currentView = 'termonitor';
            });
            provider.fetchLahanTermonitorData();
          },
        ),
        const SizedBox(height: 12),
        _buildActionMenuCard(
          title: 'Manajemen Komunitas',
          subtitle: 'Kelola data kelompok tani dan brigade pangan.',
          icon: Icons.groups_rounded,
          color: Colors.indigo[600]!,
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => const AdminKomunitasScreen(),
              ),
            );
          },
        ),
      ],
    );
  }

  // Sub-view 2: Verifikasi
  Widget _buildVerifikasiView(FarmingProvider provider) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Tab header: Lahan vs Panen
        Row(
          children: [
            Expanded(
              child: _buildTabButton(
                label: 'Lahan Baru (${provider.pendingLahanCount})',
                isActive: _verifikasiTab == 'lahan',
                onTap: () => setState(() => _verifikasiTab = 'lahan'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildTabButton(
                label: 'Hasil Panen (${provider.pendingPanenCount})',
                isActive: _verifikasiTab == 'panen',
                onTap: () => setState(() => _verifikasiTab = 'panen'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),

        // List builder based on selected sub-tab
        if (_verifikasiTab == 'lahan')
          _buildPendingLahanList(provider)
        else
          _buildPendingPanenList(provider),
      ],
    );
  }

  Widget _buildPendingLahanList(FarmingProvider provider) {
    final list = provider.pendingLahanList;
    if (list.isEmpty) {
      return _buildEmptyState('Belum ada pengajuan lahan baru.');
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: list.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final item = list[index];
        final id = item['id'];
        final pengaju =
            item['nama_petani'] ??
            item['petani']?['nama_lengkap'] ??
            item['user']?['nama_lengkap'] ??
            '-';
        final email =
            item['email_petani'] ??
            item['petani']?['email'] ??
            item['user']?['email'] ??
            '-';
        final namaLahan =
            item['nama_lahan'] ?? item['lahan']?['nama_lahan'] ?? '-';
        final pemilik = item['pemilik_lahan'] ?? '-';
        final luas = item['luas_lahan_hektar'] != null
            ? '${_formatDouble(double.parse(item['luas_lahan_hektar'].toString()))} Ha'
            : '-';
        final kecamatan =
            item['nama_kecamatan'] ??
            item['kecamatan']?['nama_kecamatan'] ??
            '-';
        final kelurahan =
            item['nama_kelurahan'] ??
            item['kelurahan']?['nama_kelurahan'] ??
            '-';

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      namaLahan,
                      style: GoogleFonts.outfit(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF14280B),
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.amber[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.amber[200]!),
                    ),
                    child: Text(
                      'PENDING',
                      style: GoogleFonts.inter(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Colors.amber[800],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _buildDetailRow('Pengaju', '$pengaju ($email)'),
              _buildDetailRow('Pemilik Lahan', pemilik),
              _buildDetailRow('Wilayah', '$kecamatan / $kelurahan'),
              _buildDetailRow('Luas Lahan', luas),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  OutlinedButton(
                    onPressed: () =>
                        _showRejectLahanDialog(context, id, namaLahan),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red[700],
                      side: BorderSide(color: Colors.red[200]!),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                    ),
                    child: Text(
                      'Tolak',
                      style: GoogleFonts.inter(fontWeight: FontWeight.w600),
                    ),
                  ),
                  const SizedBox(width: 10),
                  ElevatedButton(
                    onPressed: () => _showApproveLahanDialog(
                      context,
                      id,
                      namaLahan,
                      provider,
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green[800],
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                    ),
                    child: Text(
                      'Setujui',
                      style: GoogleFonts.inter(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildPendingPanenList(FarmingProvider provider) {
    final list = provider.pendingPanenList;
    if (list.isEmpty) {
      return _buildEmptyState('Belum ada laporan panen berstatus PENDING.');
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: list.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final item = list[index];
        final id = item['id'];
        final pengaju =
            item['nama_petani'] ??
            item['petani']?['nama_lengkap'] ??
            item['user']?['nama_lengkap'] ??
            '-';
        final email = item['email_petani'] ?? item['petani']?['email'] ?? '-';
        final phone = item['no_hp_petani'] ?? item['petani']?['no_hp'] ?? '-';
        final namaLahan =
            item['nama_lahan'] ?? item['lahan']?['nama_lahan'] ?? '-';
        final pemilik =
            item['pemilik_lahan'] ?? item['lahan']?['pemilik_lahan'] ?? '-';
        final bibit = item['nama_bibit'] ?? item['bibit']?['nama_bibit'] ?? '-';
        final varietas = item['varietas'] ?? item['bibit']?['varietas'] ?? '-';
        final tanamDate = _formatDateStr(item['tanggal_tanam']);
        final panenDate = _formatDateStr(item['tanggal_panen']);
        final hasil = item['hasil_panen'] != null
            ? '${_formatDouble(double.parse(item['hasil_panen'].toString()))} Ton'
            : '0 Ton';

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      namaLahan,
                      style: GoogleFonts.outfit(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF14280B),
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.amber[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.amber[200]!),
                    ),
                    child: Text(
                      'PENDING',
                      style: GoogleFonts.inter(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Colors.amber[800],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _buildDetailRow('Pengaju', '$pengaju ($email, HP: $phone)'),
              _buildDetailRow('Pemilik Lahan', pemilik),
              _buildDetailRow('Bibit / Varietas', '$bibit ($varietas)'),
              _buildDetailRow('Tanggal Tanam', tanamDate),
              _buildDetailRow('Tanggal Panen', panenDate),
              _buildDetailRow(
                'Hasil Panen',
                hasil,
                isBoldValue: true,
                valueColor: const Color(0xFF3E7D00),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  OutlinedButton(
                    onPressed: () =>
                        _showRejectPanenDialog(context, id, namaLahan),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red[700],
                      side: BorderSide(color: Colors.red[200]!),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                    ),
                    child: Text(
                      'Tolak',
                      style: GoogleFonts.inter(fontWeight: FontWeight.w600),
                    ),
                  ),
                  const SizedBox(width: 10),
                  ElevatedButton(
                    onPressed: () =>
                        _showApprovePanenDialog(context, id, namaLahan),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green[800],
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                    ),
                    child: Text(
                      'Setujui',
                      style: GoogleFonts.inter(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  // Dialog actions for Lahan
  void _showApproveLahanDialog(
    BuildContext context,
    int id,
    String namaLahan,
    FarmingProvider provider,
  ) {
    int? localSelectedPetaniId;
    final listPetani = provider.petaniSpasialList;

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              title: Text(
                'Setujui Pengajuan Lahan',
                style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Anda akan menyetujui pengajuan "$namaLahan". Silakan pilih petani penggarap terlebih dahulu.',
                    style: GoogleFonts.inter(
                      fontSize: 13,
                      color: Colors.grey[700],
                    ),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<int>(
                    decoration: InputDecoration(
                      labelText: 'Pilih Penggarap',
                      labelStyle: GoogleFonts.inter(fontSize: 13),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 8,
                      ),
                    ),
                    initialValue: localSelectedPetaniId,
                    items: listPetani.map<DropdownMenuItem<int>>((item) {
                      final name = item['nama_lengkap'] ?? item['nama'] ?? '-';
                      final group = item['role_id'] == 5
                          ? '(Brigade Pangan)'
                          : '(Kelompok Tani)';
                      return DropdownMenuItem<int>(
                        value: item['id'],
                        child: Text(
                          '$name $group',
                          style: GoogleFonts.inter(fontSize: 12),
                          overflow: TextOverflow.ellipsis,
                        ),
                      );
                    }).toList(),
                    onChanged: (val) {
                      setDialogState(() {
                        localSelectedPetaniId = val;
                      });
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text(
                    'Batal',
                    style: GoogleFonts.inter(color: Colors.grey[600]),
                  ),
                ),
                ElevatedButton(
                  onPressed: localSelectedPetaniId == null
                      ? null
                      : () async {
                          final provider = context.read<FarmingProvider>();
                          final messenger = ScaffoldMessenger.of(context);
                          Navigator.pop(context);
                          final success = await provider.approveLahan(
                            id,
                            localSelectedPetaniId,
                          );
                          messenger.showSnackBar(
                            SnackBar(
                              content: Text(
                                success
                                    ? 'Pengajuan lahan berhasil disetujui!'
                                    : 'Gagal menyetujui pengajuan lahan.',
                              ),
                              backgroundColor: success
                                  ? Colors.green[800]
                                  : Colors.red[800],
                            ),
                          );
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green[800],
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: Text(
                    'Setujui Pengajuan',
                    style: GoogleFonts.inter(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showRejectLahanDialog(BuildContext context, int id, String namaLahan) {
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Tolak Pengajuan Lahan',
            style: GoogleFonts.outfit(
              fontWeight: FontWeight.bold,
              color: Colors.red[800],
            ),
          ),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Alasan penolakan "$namaLahan" akan dikirimkan kepada petani sebagai pedoman perbaikan.',
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    color: Colors.grey[700],
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: controller,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText:
                        'Contoh: Alamat lahan belum lengkap, lokasi tidak sesuai wilayah.',
                    hintStyle: GoogleFonts.inter(
                      fontSize: 12,
                      color: Colors.grey[400],
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  validator: (val) {
                    if (val == null || val.trim().length < 5) {
                      return 'Masukkan minimal 5 karakter alasan.';
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
                style: GoogleFonts.inter(color: Colors.grey[600]),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                if (formKey.currentState!.validate()) {
                  final provider = context.read<FarmingProvider>();
                  final messenger = ScaffoldMessenger.of(context);
                  Navigator.pop(context);
                  final success = await provider.rejectLahan(
                    id,
                    controller.text.trim(),
                  );
                  messenger.showSnackBar(
                    SnackBar(
                      content: Text(
                        success
                            ? 'Pengajuan lahan berhasil ditolak!'
                            : 'Gagal menolak pengajuan lahan.',
                      ),
                      backgroundColor: success
                          ? Colors.orange[800]
                          : Colors.red[800],
                    ),
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red[700],
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: Text(
                'Kirim Penolakan',
                style: GoogleFonts.inter(fontWeight: FontWeight.bold),
              ),
            ),
          ],
        );
      },
    );
  }

  // Dialog actions for Panen
  void _showApprovePanenDialog(BuildContext context, int id, String namaLahan) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Setujui Laporan Panen',
            style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
          ),
          content: Text(
            'Apakah Anda yakin ingin menyetujui laporan hasil panen untuk lahan "$namaLahan"? Data produksi sawah ini akan langsung ter-update di publik.',
            style: GoogleFonts.inter(fontSize: 13, color: Colors.grey[700]),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(
                'Batal',
                style: GoogleFonts.inter(color: Colors.grey[600]),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                final provider = context.read<FarmingProvider>();
                final messenger = ScaffoldMessenger.of(context);
                Navigator.pop(context);
                final success = await provider.verifikasiPanen(
                  id,
                  'DITERIMA',
                  '',
                );
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                      success
                          ? 'Laporan hasil panen berhasil disetujui!'
                          : 'Gagal memverifikasi laporan panen.',
                    ),
                    backgroundColor: success
                        ? Colors.green[800]
                        : Colors.red[800],
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.green[800],
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: Text(
                'Ya, Setujui',
                style: GoogleFonts.inter(fontWeight: FontWeight.bold),
              ),
            ),
          ],
        );
      },
    );
  }

  void _showRejectPanenDialog(BuildContext context, int id, String namaLahan) {
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Tolak Laporan Panen',
            style: GoogleFonts.outfit(
              fontWeight: FontWeight.bold,
              color: Colors.red[800],
            ),
          ),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Masukkan catatan alasan penolakan hasil panen "$namaLahan".',
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    color: Colors.grey[700],
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: controller,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText:
                        'Contoh: Berat hasil panen tidak realistis, atau dokumen pendukung salah.',
                    hintStyle: GoogleFonts.inter(
                      fontSize: 12,
                      color: Colors.grey[400],
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  validator: (val) {
                    if (val == null || val.trim().length < 5) {
                      return 'Masukkan minimal 5 karakter catatan.';
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
                style: GoogleFonts.inter(color: Colors.grey[600]),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                if (formKey.currentState!.validate()) {
                  final provider = context.read<FarmingProvider>();
                  final messenger = ScaffoldMessenger.of(context);
                  Navigator.pop(context);
                  final success = await provider.verifikasiPanen(
                    id,
                    'DITOLAK',
                    controller.text.trim(),
                  );
                  messenger.showSnackBar(
                    SnackBar(
                      content: Text(
                        success
                            ? 'Laporan hasil panen ditolak!'
                            : 'Gagal memverifikasi laporan panen.',
                      ),
                      backgroundColor: success
                          ? Colors.orange[800]
                          : Colors.red[800],
                    ),
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red[700],
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: Text(
                'Kirim Penolakan',
                style: GoogleFonts.inter(fontWeight: FontWeight.bold),
              ),
            ),
          ],
        );
      },
    );
  }

  // Sub-view 3: Data Spasial
  Widget _buildSpasialView(FarmingProvider provider) {
    // Filter spatial list based on tab & query
    bool hasSpatialData(dynamic item) {
      final polygon =
          item['polygon_geojson'] ?? item['geojson'] ?? item['polygon_area'];
      return polygon != null && polygon.toString().isNotEmpty;
    }

    final filteredList = provider.spasialLahanList.where((item) {
      // tab selection filter
      if (_spasialTab == 'belum') {
        final isApproved = item['status_verifikasi'] == 'DITERIMA';
        if (!isApproved || hasSpatialData(item)) return false;
      } else {
        if (!hasSpatialData(item)) return false;
      }

      // query filter
      if (_spasialSearchQuery.isEmpty) return true;
      final name = (item['nama_lahan'] ?? '').toString().toLowerCase();
      final owner = (item['pemilik_lahan'] ?? item['nama_petani'] ?? '')
          .toString()
          .toLowerCase();
      final kec = (item['nama_kecamatan'] ?? '').toString().toLowerCase();
      final kel = (item['nama_kelurahan'] ?? '').toString().toLowerCase();
      return name.contains(_spasialSearchQuery) ||
          owner.contains(_spasialSearchQuery) ||
          kec.contains(_spasialSearchQuery) ||
          kel.contains(_spasialSearchQuery);
    }).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Tab Header: Belum vs Sudah Dipetakan
        Row(
          children: [
            Expanded(
              child: _buildTabButton(
                label: 'Belum Dipetakan',
                isActive: _spasialTab == 'belum',
                onTap: () => setState(() => _spasialTab = 'belum'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildTabButton(
                label: 'Sudah Dipetakan',
                isActive: _spasialTab == 'sudah',
                onTap: () => setState(() => _spasialTab = 'sudah'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),

        // Search Input
        TextField(
          controller: _spasialSearchController,
          decoration: InputDecoration(
            hintText: 'Cari nama lahan, wilayah, atau pemilik...',
            prefixIcon: const Icon(Icons.search, color: Color(0xFF64748B)),
            fillColor: Colors.white,
            filled: true,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
            ),
            contentPadding: const EdgeInsets.symmetric(vertical: 8),
          ),
        ),
        const SizedBox(height: 16),

        // Count of matches
        Text(
          'Ditemukan ${filteredList.length} lahan sawah',
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF64748B),
          ),
        ),
        const SizedBox(height: 12),

        if (filteredList.isEmpty)
          _buildEmptyState('Tidak ada data lahan sawah untuk kriteria ini.')
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: filteredList.length,
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final item = filteredList[index];
              final namaLahan = item['nama_lahan'] ?? '-';
              final owner = item['pemilik_lahan'] ?? item['nama_petani'] ?? '-';
              final kec = item['nama_kecamatan'] ?? '-';
              final kel = item['nama_kelurahan'] ?? '-';
              final luas = item['luas_lahan_hektar'] != null
                  ? '${_formatDouble(double.parse(item['luas_lahan_hektar'].toString()))} Ha'
                  : '-';
              final mapped = hasSpatialData(item);

              return Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            namaLahan,
                            style: GoogleFonts.outfit(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF14280B),
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: mapped ? Colors.green[50] : Colors.amber[50],
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: mapped
                                  ? Colors.green[200]!
                                  : Colors.amber[200]!,
                            ),
                          ),
                          child: Text(
                            mapped ? 'SUDAH DIPETAKAN' : 'BELUM DIPETAKAN',
                            style: GoogleFonts.inter(
                              fontSize: 9,
                              fontWeight: FontWeight.bold,
                              color: mapped
                                  ? Colors.green[800]
                                  : Colors.amber[800],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _buildDetailRow('Pemilik / Petani', owner),
                    _buildDetailRow('Wilayah', '$kec / $kel'),
                    _buildDetailRow('Luas Lahan', luas),
                    if (mapped) ...[
                      _buildDetailRow('Latitude', '${item['latitude'] ?? '-'}'),
                      _buildDetailRow(
                        'Longitude',
                        '${item['longitude'] ?? '-'}',
                      ),
                    ],
                    const SizedBox(height: 16),
                    Align(
                      alignment: Alignment.centerRight,
                      child: ElevatedButton.icon(
                        onPressed: () => _showAturSpasialDialog(context, item),
                        icon: const Icon(
                          Icons.edit_location_alt_rounded,
                          size: 16,
                        ),
                        label: Text(
                          mapped ? 'Ubah Batas Wilayah' : 'Petakan Area Lahan',
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.teal[800],
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
      ],
    );
  }

  // Spatial data mapping form dialog (with Interactive map picker!)
  void _showAturSpasialDialog(BuildContext context, dynamic item) {
    final latController = TextEditingController(
      text: item['latitude']?.toString() ?? '-3.300000',
    );
    final lngController = TextEditingController(
      text: item['longitude']?.toString() ?? '114.600000',
    );
    final geojsonController = TextEditingController(
      text: item['polygon_geojson'] ?? item['geojson'] ?? '',
    );
    final luasController = TextEditingController(
      text: item['luas_lahan_hektar']?.toString() ?? '',
    );
    final formKey = GlobalKey<FormState>();

    double currentLat = double.tryParse(latController.text) ?? -3.300000;
    double currentLng = double.tryParse(lngController.text) ?? 114.600000;

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              insetPadding: const EdgeInsets.all(12),
              title: Text(
                'Atur Data Spasial Lahan',
                style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
              ),
              content: SizedBox(
                width: MediaQuery.of(context).size.width * 0.9,
                child: Form(
                  key: formKey,
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          'Peta Lokasi Sawah: "${item['nama_lahan']}"',
                          style: GoogleFonts.inter(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          'Ketuk pada peta untuk memindahkan titik tengah koordinat.',
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 12),

                        // Map Point Picker
                        SizedBox(
                          height: 200,
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(16),
                            child: Stack(
                              children: [
                                FlutterMap(
                                  options: MapOptions(
                                    initialCenter: LatLng(
                                      currentLat,
                                      currentLng,
                                    ),
                                    initialZoom: 14,
                                    onTap: (tapPosition, point) {
                                      setDialogState(() {
                                        currentLat = point.latitude;
                                        currentLng = point.longitude;
                                        latController.text = point.latitude
                                            .toStringAsFixed(6);
                                        lngController.text = point.longitude
                                            .toStringAsFixed(6);
                                      });
                                    },
                                  ),
                                  children: [
                                    TileLayer(
                                      urlTemplate:
                                          'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                                      userAgentPackageName:
                                          'com.agriculture.app',
                                    ),
                                    MarkerLayer(
                                      markers: [
                                        Marker(
                                          point: LatLng(currentLat, currentLng),
                                          width: 40,
                                          height: 40,
                                          child: const Icon(
                                            Icons.location_on_rounded,
                                            color: Colors.red,
                                            size: 38,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                                Positioned(
                                  right: 8,
                                  bottom: 8,
                                  child: Container(
                                    padding: const EdgeInsets.all(6),
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      borderRadius: BorderRadius.circular(8),
                                      boxShadow: const [
                                        BoxShadow(
                                          color: Colors.black26,
                                          blurRadius: 4,
                                        ),
                                      ],
                                    ),
                                    child: const Icon(
                                      Icons.touch_app_rounded,
                                      color: Colors.green,
                                      size: 20,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Form Inputs
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: latController,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                      decimal: true,
                                    ),
                                decoration: InputDecoration(
                                  labelText: 'Latitude',
                                  labelStyle: GoogleFonts.inter(fontSize: 12),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                ),
                                validator: (val) {
                                  if (val == null ||
                                      double.tryParse(val) == null) {
                                    return 'Harus angka';
                                  }
                                  return null;
                                },
                                onChanged: (val) {
                                  final num = double.tryParse(val);
                                  if (num != null) {
                                    setDialogState(() {
                                      currentLat = num;
                                    });
                                  }
                                },
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: TextFormField(
                                controller: lngController,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                      decimal: true,
                                    ),
                                decoration: InputDecoration(
                                  labelText: 'Longitude',
                                  labelStyle: GoogleFonts.inter(fontSize: 12),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                ),
                                validator: (val) {
                                  if (val == null ||
                                      double.tryParse(val) == null) {
                                    return 'Harus angka';
                                  }
                                  return null;
                                },
                                onChanged: (val) {
                                  final num = double.tryParse(val);
                                  if (num != null) {
                                    setDialogState(() {
                                      currentLng = num;
                                    });
                                  }
                                },
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: luasController,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: InputDecoration(
                            labelText: 'Luas Lahan Estimasi (Ha)',
                            labelStyle: GoogleFonts.inter(fontSize: 12),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          validator: (val) {
                            if (val == null || double.tryParse(val) == null) {
                              return 'Masukkan nilai luas numerik.';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: geojsonController,
                          maxLines: 4,
                          decoration: InputDecoration(
                            labelText: 'Batas Polygon (GeoJSON String)',
                            labelStyle: GoogleFonts.inter(fontSize: 12),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                            hintText:
                                '{"type":"Polygon","coordinates":[[[114.6, -3.3], ...]]}',
                            hintStyle: GoogleFonts.inter(
                              fontSize: 11,
                              color: Colors.grey[300],
                            ),
                          ),
                          validator: (val) {
                            if (val == null || val.trim().isEmpty) {
                              return 'GeoJSON batas wilayah wajib diisi.';
                            }
                            // simple json validation
                            if (!val.trim().startsWith('{') ||
                                !val.trim().endsWith('}')) {
                              return 'Format GeoJSON tidak valid.';
                            }
                            return null;
                          },
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
                    style: GoogleFonts.inter(color: Colors.grey[600]),
                  ),
                ),
                ElevatedButton(
                  onPressed: () async {
                    if (formKey.currentState!.validate()) {
                      final provider = context.read<FarmingProvider>();
                      final messenger = ScaffoldMessenger.of(context);
                      Navigator.pop(context);
                      final payload = {
                        'kecamatan_id': item['kecamatan_id'],
                        'kelurahan_id': item['kelurahan_id'],
                        'tipe_lahan_id': item['tipe_lahan_id'] ?? 1,
                        'nama_lahan': item['nama_lahan'],
                        'luas_lahan_hektar': double.parse(luasController.text),
                        'alamat_detail': item['alamat_detail'] ?? '',
                        'latitude': double.parse(latController.text),
                        'longitude': double.parse(lngController.text),
                        'polygon_geojson': geojsonController.text.trim(),
                      };
                      final success = await provider.updateSpasialLahan(
                        item['id'],
                        payload,
                      );
                      messenger.showSnackBar(
                        SnackBar(
                          content: Text(
                            success
                                ? 'Data spasial lahan berhasil disimpan!'
                                : 'Gagal menyimpan data spasial.',
                          ),
                          backgroundColor: success
                              ? Colors.green[800]
                              : Colors.red[800],
                        ),
                      );
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.teal[800],
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: Text(
                    'Simpan Peta',
                    style: GoogleFonts.inter(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  // Sub-view 4: Parameter Lingkungan
  Widget _buildParameterView(FarmingProvider provider) {
    final acceptedLahan = provider.acceptedLahanList;
    final monitoring = provider.monitoringList;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Logging Form Card
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
            boxShadow: const [
              BoxShadow(
                color: Colors.black12,
                blurRadius: 4,
                offset: Offset(0, 2),
              ),
            ],
          ),
          child: Form(
            key: _parameterFormKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Catat Kondisi Lingkungan Lapangan',
                  style: GoogleFonts.outfit(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF14280B),
                  ),
                ),
                const SizedBox(height: 12),

                // Lahan Dropdown
                DropdownButtonFormField<int>(
                  decoration: InputDecoration(
                    labelText: 'Pilih Lahan Sawah',
                    labelStyle: GoogleFonts.inter(fontSize: 13),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  initialValue: _selectedLahanId,
                  items: acceptedLahan.map<DropdownMenuItem<int>>((item) {
                    return DropdownMenuItem<int>(
                      value: item['id'],
                      child: Text(
                        '${item['nama_lahan'] ?? '-'} (${item['nama_kecamatan'] ?? '-'})',
                        style: GoogleFonts.inter(fontSize: 12),
                      ),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedLahanId = val;
                    });
                  },
                  validator: (val) {
                    if (val == null) return 'Lahan sawah harus dipilih';
                    return null;
                  },
                ),
                const SizedBox(height: 12),

                // Date Cek
                InkWell(
                  onTap: () => _selectDate(context),
                  child: InputDecorator(
                    decoration: InputDecoration(
                      labelText: 'Tanggal Pengecekan',
                      labelStyle: GoogleFonts.inter(fontSize: 13),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      suffixIcon: const Icon(
                        Icons.calendar_today_rounded,
                        size: 18,
                      ),
                    ),
                    child: Text(
                      '${_tanggalCek.day}-${_tanggalCek.month}-${_tanggalCek.year}',
                      style: GoogleFonts.inter(fontSize: 13),
                    ),
                  ),
                ),
                const SizedBox(height: 12),

                // pH Air & Tinggi Muka Air
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _phAirController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: InputDecoration(
                          labelText: 'pH Air (0 - 14)',
                          labelStyle: GoogleFonts.inter(fontSize: 13),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (val) {
                          if (val == null || val.isEmpty) {
                            return 'Wajib diisi';
                          }
                          final num = double.tryParse(val);
                          if (num == null || num < 0 || num > 14) {
                            return 'pH tidak valid';
                          }
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: TextFormField(
                        controller: _tinggiAirController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: InputDecoration(
                          labelText: 'Tinggi Air (cm)',
                          labelStyle: GoogleFonts.inter(fontSize: 13),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (val) {
                          if (val == null || val.isEmpty) {
                            return 'Wajib diisi';
                          }
                          if (double.tryParse(val) == null) {
                            return 'Harus angka';
                          }
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Status Air & Kekeruhan
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        decoration: InputDecoration(
                          labelText: 'Status Air',
                          labelStyle: GoogleFonts.inter(fontSize: 13),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        initialValue: _statusAir,
                        items: ['Normal', 'Pasang', 'Surut', 'Banjir'].map((
                          st,
                        ) {
                          return DropdownMenuItem(
                            value: st,
                            child: Text(
                              st,
                              style: GoogleFonts.inter(fontSize: 12),
                            ),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _statusAir = val;
                            });
                          }
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        decoration: InputDecoration(
                          labelText: 'Kekeruhan Air',
                          labelStyle: GoogleFonts.inter(fontSize: 13),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        initialValue: _kekeruhanAir,
                        items: ['Jernih', 'Agak Keruh', 'Keruh', 'Sangat Keruh']
                            .map((st) {
                              return DropdownMenuItem(
                                value: st,
                                child: Text(
                                  st,
                                  style: GoogleFonts.inter(fontSize: 12),
                                ),
                              );
                            })
                            .toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _kekeruhanAir = val;
                            });
                          }
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Catatan
                TextFormField(
                  controller: _catatanPetugasController,
                  maxLines: 2,
                  decoration: InputDecoration(
                    labelText: 'Catatan Lapangan',
                    labelStyle: GoogleFonts.inter(fontSize: 13),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Submit parameter button
                ElevatedButton(
                  onPressed: () async {
                    if (_parameterFormKey.currentState!.validate()) {
                      final payload = {
                        'lahan_id': _selectedLahanId,
                        'tanggal_cek':
                            '${_tanggalCek.year}-${_tanggalCek.month.toString().padLeft(2, '0')}-${_tanggalCek.day.toString().padLeft(2, '0')}',
                        'ph_air': double.parse(_phAirController.text),
                        'tinggi_muka_air': double.parse(
                          _tinggiAirController.text,
                        ),
                        'status_air': _statusAir,
                        'kekeruhan_air': _kekeruhanAir,
                        'catatan_petugas': _catatanPetugasController.text,
                        'latitude': 0,
                        'longitude': 0,
                      };
                      final messenger = ScaffoldMessenger.of(context);
                      final success = await provider.saveMonitoring(payload);
                      if (success) {
                        setState(() {
                          _phAirController.clear();
                          _tinggiAirController.clear();
                          _catatanPetugasController.clear();
                          _selectedLahanId = null;
                        });
                      }
                      messenger.showSnackBar(
                        SnackBar(
                          content: Text(
                            success
                                ? 'Parameter lingkungan lapangan berhasil dicatat!'
                                : 'Gagal mencatat parameter lingkungan.',
                          ),
                          backgroundColor: success
                              ? Colors.green[800]
                              : Colors.red[800],
                        ),
                      );
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3E7D00),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  child: Text(
                    'Simpan Catatan Monitoring',
                    style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 24),

        // History Log List
        Text(
          'Riwayat Parameter Lapangan',
          style: GoogleFonts.outfit(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 12),

        if (monitoring.isEmpty)
          _buildEmptyState('Belum ada riwayat catatan monitoring.')
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: monitoring.length,
            separatorBuilder: (context, index) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final item = monitoring[index];
              final namaLahan =
                  item['nama_lahan'] ??
                  item['lahan']?['nama_lahan'] ??
                  'Lahan Sawah';
              final ph = item['ph_air']?.toString() ?? '-';
              final tinggi = item['tinggi_muka_air'] != null
                  ? '${item['tinggi_muka_air']} cm'
                  : '-';
              final status = item['status_air'] ?? '-';
              final kekeruhan = item['kekeruhan_air'] ?? '-';
              final tanggal = _formatDateStr(item['tanggal_cek']);
              final catatan = item['catatan_petugas'] ?? '-';

              return Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            namaLahan,
                            style: GoogleFonts.outfit(
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF14280B),
                            ),
                          ),
                        ),
                        Text(
                          tanggal,
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 12),
                    Row(
                      children: [
                        Expanded(child: _buildDetailRow('pH Air', ph)),
                        Expanded(child: _buildDetailRow('Tinggi Air', tinggi)),
                      ],
                    ),
                    Row(
                      children: [
                        Expanded(child: _buildDetailRow('Status Air', status)),
                        Expanded(
                          child: _buildDetailRow('Kekeruhan', kekeruhan),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Catatan: $catatan',
                      style: GoogleFonts.inter(
                        fontSize: 11,
                        color: Colors.grey[600],
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
      ],
    );
  }

  Widget _buildWilayahPetugasCard() {
    final user = widget.user;
    final desa = user?.wilayahKelurahanNama.join(', ');
    final instansi = user?.instansiAsal == 'BPP'
        ? (user?.namaBpp ?? 'BPP')
        : 'DINAS PERTANIAN TANAMAN PANGAN DAN HORTIKULTURA';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Wilayah Kerja',
            style: GoogleFonts.outfit(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF14280B),
            ),
          ),
          const SizedBox(height: 8),
          _buildDetailRow('Kecamatan', user?.wilayahKecamatanNama ?? '-'),
          _buildDetailRow(
            'Kelurahan/Desa',
            (desa == null || desa.isEmpty) ? '-' : desa,
          ),
          _buildDetailRow('Asal Petugas', instansi),
        ],
      ),
    );
  }

  // Component UI Builders
  Widget _buildStatCard({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: bgColor,
            radius: 20,
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: GoogleFonts.outfit(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF64748B),
                  ),
                ),
                Text(
                  subtitle,
                  style: GoogleFonts.inter(
                    fontSize: 9,
                    color: const Color(0xFF94A3B8),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionMenuCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                CircleAvatar(
                  backgroundColor: color.withValues(alpha: 0.1),
                  child: Icon(icon, color: color),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: GoogleFonts.outfit(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF0F172A),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: GoogleFonts.inter(
                          fontSize: 11,
                          color: const Color(0xFF64748B),
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
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

  Widget _buildTabButton({
    required String label,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFF3E7D00) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isActive ? const Color(0xFF3E7D00) : const Color(0xFFE2E8F0),
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: GoogleFonts.outfit(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: isActive ? Colors.white : const Color(0xFF64748B),
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(
    String label,
    String value, {
    bool isBoldValue = false,
    Color? valueColor,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: GoogleFonts.inter(
                fontSize: 11,
                fontWeight: FontWeight.w500,
                color: const Color(0xFF94A3B8),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: isBoldValue ? FontWeight.bold : FontWeight.w600,
                color: valueColor ?? const Color(0xFF334155),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      alignment: Alignment.center,
      child: Column(
        children: [
          const Icon(
            Icons.info_outline_rounded,
            color: Color(0xFFCBD5E1),
            size: 40,
          ),
          const SizedBox(height: 12),
          Text(
            text,
            textAlign: TextAlign.center,
            style: GoogleFonts.inter(
              fontSize: 13,
              color: const Color(0xFF94A3B8),
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  // ignore: unused_element
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
