import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/network/api_client.dart';
import '../../providers/auth_provider.dart';
import '../../services/farming_service.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _namaController = TextEditingController();
  final _emailController = TextEditingController();
  final _noHpController = TextEditingController();
  final _alamatController = TextEditingController();
  final FarmingService _farmingService = FarmingService(ApiClient());
  List<dynamic> _kecamatanList = [];
  List<dynamic> _kelurahanList = [];
  int? _selectedKecamatanId;
  int? _selectedKelurahanId;
  bool _isLoadingReferensi = false;
  String? _referensiError;

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().currentUser;
    _namaController.text = user?.namaLengkap ?? '';
    _emailController.text = user?.email ?? '';
    _noHpController.text = user?.noHp ?? '';
    _alamatController.text = user?.alamat ?? '';
    _selectedKecamatanId = user?.wilayahKecamatanId;
    _selectedKelurahanId = user?.wilayahKelurahanIds.isNotEmpty == true
        ? user!.wilayahKelurahanIds.first
        : null;
    if (user?.roleId == 2) {
      _loadReferensi();
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  Future<void> _loadReferensi() async {
    setState(() {
      _isLoadingReferensi = true;
      _referensiError = null;
    });
    try {
      final result = await Future.wait<dynamic>([
        _farmingService.getKecamatan(),
        _farmingService.getKelurahan(),
      ]);
      if (!mounted) return;
      setState(() {
        _kecamatanList = result[0] as List<dynamic>;
        _kelurahanList = result[1] as List<dynamic>;
        _isLoadingReferensi = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoadingReferensi = false;
        _referensiError = error.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  @override
  void dispose() {
    _namaController.dispose();
    _emailController.dispose();
    _noHpController.dispose();
    _alamatController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<AuthProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);

    try {
      await provider.updateProfile(
        namaLengkap: _namaController.text.trim(),
        email: _emailController.text.trim(),
        noHp: _noHpController.text.trim().isEmpty
            ? null
            : _noHpController.text.trim(),
        alamat: _alamatController.text.trim().isEmpty
            ? null
            : _alamatController.text.trim(),
        wilayahKecamatanId: provider.currentUser?.roleId == 2
            ? _selectedKecamatanId
            : null,
        wilayahKelurahanId: provider.currentUser?.roleId == 2
            ? _selectedKelurahanId
            : null,
      );
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Profil berhasil diperbarui.'),
          backgroundColor: Colors.green,
        ),
      );
      navigator.pop();
    } catch (error) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AuthProvider>();
    final user = provider.currentUser;
    final desa = user?.wilayahKelurahanNama.join(', ');
    final instansi = user?.instansiAsal == 'BPP'
        ? (user?.namaBpp ?? 'BPP')
        : 'DINAS PERTANIAN TANAMAN PANGAN DAN HORTIKULTURA';
    final filteredKelurahan = _kelurahanList
        .where(
          (item) =>
              _selectedKecamatanId != null &&
              _asInt(item['kecamatan_id']) == _selectedKecamatanId,
        )
        .toList();

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Edit Profil',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.green[800],
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildInfoCard(
                title: 'Wilayah Terdaftar',
                children: [
                  _buildInfoRow('Kecamatan', user?.wilayahKecamatanNama ?? '-'),
                  _buildInfoRow(
                    'Kelurahan/Desa',
                    (desa == null || desa.isEmpty) ? '-' : desa,
                  ),
                  _buildInfoRow('Asal Petugas', instansi),
                ],
              ),
              const SizedBox(height: 16),
              Card(
                color: Colors.white,
                surfaceTintColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(18),
                  side: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Data Diri',
                        style: GoogleFonts.outfit(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      const SizedBox(height: 16),
                      _buildField(
                        label: 'Nama Lengkap',
                        controller: _namaController,
                        validator: (value) =>
                            value == null || value.trim().isEmpty
                            ? 'Nama wajib diisi'
                            : null,
                      ),
                      const SizedBox(height: 14),
                      _buildField(
                        label: 'Email',
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Email wajib diisi';
                          }
                          if (!value.contains('@')) {
                            return 'Email tidak valid';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      _buildField(
                        label: 'Nomor HP',
                        controller: _noHpController,
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 14),
                      _buildField(
                        label: 'Alamat',
                        controller: _alamatController,
                        maxLines: 3,
                      ),
                      if (user?.roleId == 2) ...[
                        const SizedBox(height: 18),
                        const Divider(),
                        const SizedBox(height: 10),
                        Text(
                          'Wilayah Kerja Petugas',
                          style: GoogleFonts.outfit(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF14280B),
                          ),
                        ),
                        const SizedBox(height: 12),
                        _buildReadOnlyField(
                          label: 'Asal Petugas (Instansi)',
                          value: instansi,
                        ),
                        const SizedBox(height: 14),
                        if (_isLoadingReferensi)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.all(16),
                              child: CircularProgressIndicator(),
                            ),
                          )
                        else if (_referensiError != null)
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.red[50],
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.red[200]!),
                            ),
                            child: Column(
                              children: [
                                Text(
                                  'Referensi wilayah gagal dimuat: '
                                  '$_referensiError',
                                  style: GoogleFonts.inter(
                                    fontSize: 12,
                                    color: Colors.red[700],
                                  ),
                                ),
                                TextButton(
                                  onPressed: _loadReferensi,
                                  child: const Text('Coba Lagi'),
                                ),
                              ],
                            ),
                          )
                        else ...[
                          DropdownButtonFormField<int>(
                            initialValue:
                                _kecamatanList.any(
                                  (item) =>
                                      _asInt(item['id']) ==
                                      _selectedKecamatanId,
                                )
                                ? _selectedKecamatanId
                                : null,
                            isExpanded: true,
                            decoration: _inputDecoration(
                              'Kecamatan Wilayah Kerja',
                            ),
                            items: _kecamatanList
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: _asInt(item['id']),
                                    child: Text(
                                      (item['nama_kecamatan'] ??
                                              item['nama'] ??
                                              '-')
                                          .toString(),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .where((item) => item.value != null)
                                .toList(),
                            onChanged: (value) {
                              setState(() {
                                _selectedKecamatanId = value;
                                _selectedKelurahanId = null;
                              });
                            },
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<int>(
                            key: ValueKey(
                              'profile-kelurahan-$_selectedKecamatanId',
                            ),
                            initialValue:
                                filteredKelurahan.any(
                                  (item) =>
                                      _asInt(item['id']) ==
                                      _selectedKelurahanId,
                                )
                                ? _selectedKelurahanId
                                : null,
                            isExpanded: true,
                            decoration: _inputDecoration(
                              'Kelurahan Wilayah Kerja',
                            ),
                            items: filteredKelurahan
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: _asInt(item['id']),
                                    child: Text(
                                      (item['nama_kelurahan'] ??
                                              item['nama'] ??
                                              '-')
                                          .toString(),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .where((item) => item.value != null)
                                .toList(),
                            onChanged: _selectedKecamatanId == null
                                ? null
                                : (value) => setState(
                                    () => _selectedKelurahanId = value,
                                  ),
                          ),
                        ],
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: provider.isLoading ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[800],
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
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
                    : Text(
                        'Simpan Profil',
                        style: GoogleFonts.inter(fontWeight: FontWeight.bold),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildField({
    required String label,
    required TextEditingController controller,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: validator,
      maxLines: maxLines,
      style: GoogleFonts.inter(fontSize: 14),
      decoration: InputDecoration(
        labelText: _inputDecoration(label).labelText,
        labelStyle: _inputDecoration(label).labelStyle,
        filled: _inputDecoration(label).filled,
        fillColor: _inputDecoration(label).fillColor,
        border: _inputDecoration(label).border,
        enabledBorder: _inputDecoration(label).enabledBorder,
        focusedBorder: _inputDecoration(label).focusedBorder,
      ),
    );
  }

  InputDecoration _inputDecoration(String label) => InputDecoration(
    labelText: label,
    labelStyle: GoogleFonts.inter(fontSize: 12, color: const Color(0xFF64748B)),
    filled: true,
    fillColor: Colors.white,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: BorderSide(color: Colors.green[800]!, width: 2),
    ),
  );

  Widget _buildReadOnlyField({required String label, required String value}) {
    return InputDecorator(
      decoration: _inputDecoration(
        label,
      ).copyWith(fillColor: const Color(0xFFF8FAFC)),
      child: Text(
        value,
        style: GoogleFonts.inter(fontSize: 13, color: const Color(0xFF64748B)),
      ),
    );
  }

  Widget _buildInfoCard({
    required String title,
    required List<Widget> children,
  }) {
    return Card(
      color: const Color(0xFFEDF8DC),
      surfaceTintColor: const Color(0xFFEDF8DC),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: const BorderSide(color: Color(0xFFDFECCC)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: GoogleFonts.outfit(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF14280B),
              ),
            ),
            const SizedBox(height: 10),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 108,
            child: Text(
              label,
              style: GoogleFonts.inter(
                fontSize: 11,
                color: const Color(0xFF64748B),
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: const Color(0xFF203C10),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
