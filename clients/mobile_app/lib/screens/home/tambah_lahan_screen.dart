import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';

class TambahLahanScreen extends StatefulWidget {
  const TambahLahanScreen({super.key});

  @override
  State<TambahLahanScreen> createState() => _TambahLahanScreenState();
}

class _TambahLahanScreenState extends State<TambahLahanScreen> {
  final _formKey = GlobalKey<FormState>();
  final _namaLahanController = TextEditingController();
  final _luasLahanController = TextEditingController();
  final _alamatController = TextEditingController();

  String? _selectedKecamatanId;
  String? _selectedKelurahanId;
  String? _selectedTipeLahanId;
  String? _selectedPetaniId;

  List<dynamic> _filteredKelurahanList = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchLahanMetadata();
    });
  }

  @override
  void dispose() {
    _namaLahanController.dispose();
    _luasLahanController.dispose();
    _alamatController.dispose();
    super.dispose();
  }

  void _onKecamatanChanged(String? kecamatanId, List<dynamic> allKelurahan) {
    setState(() {
      _selectedKecamatanId = kecamatanId;
      _selectedKelurahanId = null; // Reset kelurahan
      if (kecamatanId != null) {
        _filteredKelurahanList = allKelurahan.where((item) {
          return item['kecamatan_id'].toString() == kecamatanId;
        }).toList();
      } else {
        _filteredKelurahanList = [];
      }
    });
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<FarmingProvider>();
    final payload = {
      'nama_lahan': _namaLahanController.text.trim(),
      'kecamatan_id': int.tryParse(_selectedKecamatanId ?? ''),
      'kelurahan_id': int.tryParse(_selectedKelurahanId ?? ''),
      'tipe_lahan_id': int.tryParse(_selectedTipeLahanId ?? ''),
      'luas_lahan_hektar': double.tryParse(_luasLahanController.text.trim()),
      'petani_id': int.tryParse(_selectedPetaniId ?? ''),
      'alamat_detail': _alamatController.text.trim(),
    };

    final success = await provider.submitLahan(payload);
    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pengajuan lahan baru berhasil dikirim dan menunggu verifikasi petugas'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } else {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Text('Gagal Mengajukan Lahan', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
          content: Text(provider.errorMessage ?? 'Terjadi kesalahan sistem, silakan coba lagi.', style: GoogleFonts.inter()),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('OK', style: TextStyle(color: Colors.green[800])),
            )
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final farmingProvider = context.watch<FarmingProvider>();

    if (farmingProvider.errorMessage != null && farmingProvider.kecamatanList.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: Text(
            'Daftar Lahan Sawah',
            style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
          ),
          backgroundColor: Colors.green[800],
          foregroundColor: Colors.white,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline_rounded, size: 48, color: Colors.red),
                const SizedBox(height: 16),
                Text(
                  farmingProvider.errorMessage!,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.inter(color: Colors.grey[800]),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    context.read<FarmingProvider>().fetchLahanMetadata();
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.green[800]),
                  child: Text('Coba Lagi', style: GoogleFonts.inter(color: Colors.white)),
                )
              ],
            ),
          ),
        ),
      );
    }

    if (farmingProvider.isLoading && farmingProvider.kecamatanList.isEmpty) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: Colors.green)),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Daftar Lahan Sawah',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.green[800],
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Wizard Card
              _buildStepWizard(),
              const SizedBox(height: 20),

              if (farmingProvider.errorMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.red[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.red[200]!),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.error_outline, color: Colors.red[800]),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'Gagal memuat beberapa data: ${farmingProvider.errorMessage}',
                          style: GoogleFonts.inter(color: Colors.red[800], fontSize: 13, fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Form Container Card
              Card(
                color: Colors.white,
                surfaceTintColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                  side: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Tambah Lahan Baru',
                        style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.bold, color: const Color(0xFF14280B)),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Lengkapi data lahan untuk diajukan kepada petugas.',
                        style: GoogleFonts.inter(fontSize: 11, color: Colors.grey[500]),
                      ),
                      const Divider(height: 30, color: Color(0xFFE2E8F0)),

                      // Nama Lahan Input
                      _buildLabel('Nama Lahan'),
                      TextFormField(
                        controller: _namaLahanController,
                        style: GoogleFonts.inter(fontSize: 14),
                        decoration: _buildInputDecoration('Masukkan nama lahan'),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Nama lahan wajib diisi' : null,
                      ),
                      const SizedBox(height: 16),

                      // Kecamatan Dropdown
                      _buildLabel('Kecamatan'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedKecamatanId,
                        style: GoogleFonts.inter(fontSize: 14, color: Colors.black),
                        decoration: _buildInputDecoration('Pilih Kecamatan'),
                        items: farmingProvider.kecamatanList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              item['nama_kecamatan'] ?? item['nama'] ?? '',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) => _onKecamatanChanged(val, farmingProvider.kelurahanList),
                        validator: (value) => value == null ? 'Kecamatan wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      // Kelurahan Dropdown (Filtered)
                      _buildLabel('Kelurahan'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedKelurahanId,
                        style: GoogleFonts.inter(fontSize: 14, color: Colors.black),
                        decoration: _buildInputDecoration('Pilih Kelurahan'),
                        items: _filteredKelurahanList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              item['nama_kelurahan'] ?? item['nama'] ?? '',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) => setState(() => _selectedKelurahanId = val),
                        validator: (value) => value == null ? 'Kelurahan wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      // Tipe Lahan Dropdown
                      _buildLabel('Tipe Lahan'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedTipeLahanId,
                        style: GoogleFonts.inter(fontSize: 14, color: Colors.black),
                        decoration: _buildInputDecoration('Pilih Tipe Lahan'),
                        items: farmingProvider.tipeLahanList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              item['nama_tipe'] ?? item['nama'] ?? '',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) => setState(() => _selectedTipeLahanId = val),
                        validator: (value) => value == null ? 'Tipe lahan wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      // Luas Lahan (Ha) Input
                      _buildLabel('Luas Lahan (Hektar)'),
                      TextFormField(
                        controller: _luasLahanController,
                        style: GoogleFonts.inter(fontSize: 14),
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: _buildInputDecoration('Contoh: 1.5'),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) return 'Luas lahan wajib diisi';
                          final val = double.tryParse(value);
                          if (val == null || val <= 0) return 'Luas lahan harus bernilai positif';
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),

                      // Tugaskan Penggarap Dropdown
                      _buildLabel('Tugaskan Penggarap'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedPetaniId,
                        style: GoogleFonts.inter(fontSize: 14, color: Colors.black),
                        decoration: _buildInputDecoration('Pilih Penggarap'),
                        items: farmingProvider.petaniSpasialList.map((item) {
                          final label = '${item['nama_lengkap'] ?? item['nama'] ?? ''} '
                              '${item['role_id'] == 5 ? '(Brigade Pangan)' : '(Kelompok Tani)'}';
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              label,
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) => setState(() => _selectedPetaniId = val),
                        validator: (value) => value == null ? 'Penggarap wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      // Alamat Detail Textarea
                      _buildLabel('Alamat Lengkap Lahan'),
                      TextFormField(
                        controller: _alamatController,
                        style: GoogleFonts.inter(fontSize: 14),
                        maxLines: 4,
                        decoration: _buildInputDecoration('Masukkan alamat lengkap lahan'),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Alamat lengkap wajib diisi' : null,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Info Box
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFDBEAFE)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Informasi Pengajuan Lahan',
                      style: GoogleFonts.inter(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF1E40AF)),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '- Setelah disetujui, Anda wajib menemui petugas lapangan untuk koordinasi pemetaan area sawah Anda secara langsung.',
                      style: GoogleFonts.inter(fontSize: 12, color: const Color(0xFF1E3A8A), height: 1.4),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '- Data lahan harus terpetakan sebelum digunakan untuk pelaporan hasil panen.',
                      style: GoogleFonts.inter(fontSize: 12, color: const Color(0xFF1E3A8A), height: 1.4),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Action Buttons
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        side: const BorderSide(color: Color(0xFFCBD5E1)),
                      ),
                      child: Text(
                        'Batal',
                        style: GoogleFonts.inter(fontWeight: FontWeight.bold, color: const Color(0xFF475569)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: farmingProvider.isLoading ? null : _submitForm,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green[800],
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      child: farmingProvider.isLoading
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : Text(
                              'Simpan Pengajuan',
                              style: GoogleFonts.inter(fontWeight: FontWeight.bold),
                            ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, left: 2),
      child: Text(
        text,
        style: GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.bold, color: const Color(0xFF475569)),
      ),
    );
  }

  InputDecoration _buildInputDecoration(String hintText) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: GoogleFonts.inter(color: Colors.grey[400], fontSize: 13),
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.green[800]!, width: 2)),
      errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.red, width: 1)),
    );
  }

  Widget _buildStepWizard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Panduan 4 Langkah Pendaftaran Sawah',
            style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF1E293B)),
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildWizardStep('1', 'Isi Form', 'Isi data sawah Anda.', true),
              _buildConnector(),
              _buildWizardStep('2', 'Persetujuan', 'Tunggu disetujui.', false),
              _buildConnector(),
              _buildWizardStep('3', 'Pemetaan', 'Hubungi petugas.', false),
              _buildConnector(),
              _buildWizardStep('4', 'Verifikasi', 'Siap digunakan.', false),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWizardStep(String number, String title, String subtitle, bool isActive) {
    return Expanded(
      child: Column(
        children: [
          CircleAvatar(
            radius: 14,
            backgroundColor: isActive ? Colors.green[800] : const Color(0xFFF1F5F9),
            child: Text(
              number,
              style: GoogleFonts.inter(fontSize: 11, fontWeight: FontWeight.bold, color: isActive ? Colors.white : const Color(0xFF64748B)),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            title,
            style: GoogleFonts.inter(fontSize: 10, fontWeight: FontWeight.bold, color: isActive ? Colors.green[800] : const Color(0xFF334155)),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildConnector() {
    return const Padding(
      padding: EdgeInsets.only(top: 14),
      child: Icon(Icons.arrow_forward_rounded, size: 12, color: Color(0xFF94A3B8)),
    );
  }
}
