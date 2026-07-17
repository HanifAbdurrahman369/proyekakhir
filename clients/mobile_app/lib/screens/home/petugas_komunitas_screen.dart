import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../core/network/api_client.dart';
import '../../services/auth_service.dart';
import '../../services/farming_service.dart';

class PetugasKomunitasScreen extends StatefulWidget {
  const PetugasKomunitasScreen({super.key});

  @override
  State<PetugasKomunitasScreen> createState() => _PetugasKomunitasScreenState();
}

class _PetugasKomunitasScreenState extends State<PetugasKomunitasScreen> {
  final ApiClient _apiClient = ApiClient();
  late final AuthService _authService = AuthService(_apiClient);
  late final FarmingService _farmingService = FarmingService(_apiClient);

  bool _isLoading = true;
  List<dynamic> _komunitasList = [];
  List<dynamic> _kecamatanList = [];
  List<dynamic> _kelurahanList = [];
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  List<int> _asIntList(dynamic value) {
    dynamic parsed = value;
    if (value is String && value.trim().isNotEmpty) {
      try {
        parsed = jsonDecode(value);
      } catch (_) {
        parsed = const [];
      }
    }
    if (parsed is! List) return const [];
    return parsed.map(_asInt).whereType<int>().toList();
  }

  String _text(dynamic value) {
    final result = value?.toString().trim() ?? '';
    return result.isEmpty ? '-' : result;
  }

  Future<void> _fetchData() async {
    try {
      final results = await Future.wait<dynamic>([
        _authService.getKomunitas(page: 1),
        _farmingService.getKecamatan(),
        _farmingService.getKelurahan(),
      ]);
      final response = results[0] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _komunitasList =
            (response['data'] as List<dynamic>?) ??
            (response['komunitas'] as List<dynamic>?) ??
            [];
        _kecamatanList = results[1] as List<dynamic>;
        _kelurahanList = results[2] as List<dynamic>;
        _errorMessage = null;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _errorMessage = error.toString().replaceFirst('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  Future<void> _deleteKomunitas(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Komunitas'),
        content: const Text(
          'Yakin ingin menghapus komunitas ini? Data pengguna yang terkait '
          'dapat mencegah penghapusan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Hapus', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirm != true) return;

    setState(() => _isLoading = true);
    try {
      await _authService.deleteKomunitas(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Komunitas berhasil dihapus.'),
          backgroundColor: Colors.green,
        ),
      );
      await _fetchData();
    } catch (error) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _showFormDialog({Map<String, dynamic>? komunitas}) async {
    final isEdit = komunitas != null;
    final formKey = GlobalKey<FormState>();
    final namaKomunitasController = TextEditingController(
      text: isEdit ? komunitas['nama_komunitas']?.toString() ?? '' : '',
    );
    final nikController = TextEditingController(
      text: isEdit ? komunitas['nik']?.toString() ?? '' : '',
    );
    final namaKetuaController = TextEditingController(
      text: isEdit ? komunitas['nama']?.toString() ?? '' : '',
    );
    final nomorHpController = TextEditingController(
      text: isEdit ? komunitas['nomor_hp']?.toString() ?? '' : '',
    );
    final namaBppController = TextEditingController(
      text: isEdit ? komunitas['nama_bpp']?.toString() ?? '' : '',
    );
    final alamatController = TextEditingController(
      text: isEdit ? komunitas['alamat']?.toString() ?? '' : '',
    );

    String selectedJenis =
        komunitas?['jenis_komunitas']?.toString() ?? 'kelompok_tani';
    int? selectedKecamatan = _asInt(komunitas?['wilayah_kecamatan_id']);
    final kelurahanIds = _asIntList(komunitas?['wilayah_kelurahan_ids']);
    int? selectedKelurahan = kelurahanIds.isEmpty ? null : kelurahanIds.first;
    String selectedStatus =
        komunitas?['status_keanggotaan']?.toString() ?? 'AKTIF';
    var isSaving = false;

    await showDialog<void>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setModalState) {
          final filteredKelurahan = _kelurahanList
              .where(
                (item) =>
                    selectedKecamatan != null &&
                    _asInt(item['kecamatan_id']) == selectedKecamatan,
              )
              .toList();
          if (selectedKelurahan != null &&
              !filteredKelurahan.any(
                (item) => _asInt(item['id']) == selectedKelurahan,
              )) {
            selectedKelurahan = null;
          }

          return Dialog(
            insetPadding: const EdgeInsets.all(16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(22),
            ),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 620, maxHeight: 720),
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 8, 12),
                    child: Row(
                      children: [
                        Expanded(
                          child: Text(
                            isEdit ? 'Edit Komunitas' : 'Tambah Komunitas',
                            style: GoogleFonts.outfit(
                              fontWeight: FontWeight.bold,
                              fontSize: 18,
                            ),
                          ),
                        ),
                        IconButton(
                          onPressed: isSaving
                              ? null
                              : () => Navigator.pop(dialogContext),
                          icon: const Icon(Icons.close),
                          tooltip: 'Tutup',
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: Form(
                      key: formKey,
                      child: ListView(
                        padding: const EdgeInsets.all(20),
                        children: [
                          DropdownButtonFormField<String>(
                            initialValue: selectedJenis,
                            decoration: _decoration('Jenis Entitas'),
                            items: const [
                              DropdownMenuItem(
                                value: 'kelompok_tani',
                                child: Text('Kelompok Tani'),
                              ),
                              DropdownMenuItem(
                                value: 'brigade_pangan',
                                child: Text('Brigade Pangan'),
                              ),
                            ],
                            onChanged: isSaving
                                ? null
                                : (value) => setModalState(
                                    () => selectedJenis = value!,
                                  ),
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: namaKomunitasController,
                            label: 'Nama Entitas (Komunitas)',
                            requiredMessage: 'Nama komunitas wajib diisi',
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: nikController,
                            label: 'NIK Ketua / Penanggung Jawab',
                            keyboardType: TextInputType.number,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(16),
                            ],
                            validator: (value) {
                              final nik = value?.trim() ?? '';
                              if (nik.isEmpty) return 'NIK wajib diisi';
                              if (nik.length != 16) {
                                return 'NIK harus tepat 16 digit';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: namaKetuaController,
                            label: 'Nama Ketua / Penanggung Jawab',
                            requiredMessage: 'Nama ketua wajib diisi',
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: nomorHpController,
                            label: 'Nomor HP',
                            keyboardType: TextInputType.phone,
                            inputFormatters: [
                              FilteringTextInputFormatter.allow(
                                RegExp(r'[0-9+]'),
                              ),
                              LengthLimitingTextInputFormatter(20),
                            ],
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<int>(
                            initialValue: selectedKecamatan,
                            isExpanded: true,
                            decoration: _decoration('Kecamatan'),
                            items: _kecamatanList
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: _asInt(item['id']),
                                    child: Text(
                                      _text(
                                        item['nama_kecamatan'] ?? item['nama'],
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .where((item) => item.value != null)
                                .toList(),
                            onChanged: isSaving
                                ? null
                                : (value) => setModalState(() {
                                    selectedKecamatan = value;
                                    selectedKelurahan = null;
                                  }),
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<int>(
                            initialValue: selectedKelurahan,
                            isExpanded: true,
                            decoration: _decoration('Kelurahan'),
                            items: filteredKelurahan
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: _asInt(item['id']),
                                    child: Text(
                                      _text(
                                        item['nama_kelurahan'] ?? item['nama'],
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .where((item) => item.value != null)
                                .toList(),
                            onChanged: isSaving
                                ? null
                                : (value) => setModalState(
                                    () => selectedKelurahan = value,
                                  ),
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: namaBppController,
                            label: 'Nama BPP',
                          ),
                          const SizedBox(height: 14),
                          _formField(
                            controller: alamatController,
                            label: 'Alamat Sekretariat',
                            maxLines: 3,
                          ),
                          if (isEdit) ...[
                            const SizedBox(height: 14),
                            DropdownButtonFormField<String>(
                              initialValue: selectedStatus,
                              decoration: _decoration('Status Keanggotaan'),
                              items: const [
                                DropdownMenuItem(
                                  value: 'AKTIF',
                                  child: Text('Aktif'),
                                ),
                                DropdownMenuItem(
                                  value: 'TIDAK_AKTIF',
                                  child: Text('Tidak Aktif'),
                                ),
                              ],
                              onChanged: isSaving
                                  ? null
                                  : (value) => setModalState(
                                      () => selectedStatus = value!,
                                    ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                  const Divider(height: 1),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton(
                          onPressed: isSaving
                              ? null
                              : () => Navigator.pop(dialogContext),
                          child: const Text('Batal'),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton(
                          onPressed: isSaving
                              ? null
                              : () async {
                                  if (!formKey.currentState!.validate()) return;
                                  setModalState(() => isSaving = true);
                                  final payload = <String, dynamic>{
                                    'jenis_komunitas': selectedJenis,
                                    'nama_komunitas': namaKomunitasController
                                        .text
                                        .trim(),
                                    'nik': nikController.text.trim(),
                                    'nama': namaKetuaController.text.trim(),
                                    'nomor_hp':
                                        nomorHpController.text.trim().isEmpty
                                        ? null
                                        : nomorHpController.text.trim(),
                                    'wilayah_kecamatan_id': selectedKecamatan,
                                    'wilayah_kelurahan_ids':
                                        selectedKelurahan == null
                                        ? null
                                        : [selectedKelurahan],
                                    'nama_bpp':
                                        namaBppController.text.trim().isEmpty
                                        ? null
                                        : namaBppController.text.trim(),
                                    'alamat':
                                        alamatController.text.trim().isEmpty
                                        ? null
                                        : alamatController.text.trim(),
                                    if (isEdit)
                                      'status_keanggotaan': selectedStatus,
                                  };
                                  try {
                                    if (isEdit) {
                                      await _authService.updateKomunitas(
                                        _asInt(komunitas['id'])!,
                                        payload,
                                      );
                                    } else {
                                      await _authService.createKomunitas(
                                        payload,
                                      );
                                    }
                                    if (!mounted || !dialogContext.mounted) {
                                      return;
                                    }
                                    Navigator.pop(dialogContext);
                                    ScaffoldMessenger.of(
                                      this.context,
                                    ).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          'Komunitas berhasil '
                                          '${isEdit ? 'diperbarui' : 'ditambahkan'}.',
                                        ),
                                        backgroundColor: Colors.green,
                                      ),
                                    );
                                    setState(() => _isLoading = true);
                                    await _fetchData();
                                  } catch (error) {
                                    setModalState(() => isSaving = false);
                                    if (!mounted) return;
                                    ScaffoldMessenger.of(
                                      this.context,
                                    ).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          error.toString().replaceFirst(
                                            'Exception: ',
                                            '',
                                          ),
                                        ),
                                        backgroundColor: Colors.red,
                                      ),
                                    );
                                  }
                                },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green[800],
                            foregroundColor: Colors.white,
                          ),
                          child: isSaving
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text('Simpan'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );

    namaKomunitasController.dispose();
    nikController.dispose();
    namaKetuaController.dispose();
    nomorHpController.dispose();
    namaBppController.dispose();
    alamatController.dispose();
  }

  InputDecoration _decoration(String label) => InputDecoration(
    labelText: label,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
  );

  Widget _formField({
    required TextEditingController controller,
    required String label,
    String? requiredMessage,
    String? Function(String?)? validator,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      decoration: _decoration(label),
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      maxLines: maxLines,
      validator:
          validator ??
          (requiredMessage == null
              ? null
              : (value) => value == null || value.trim().isEmpty
                    ? requiredMessage
                    : null),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          'Manajemen Komunitas',
          style: GoogleFonts.outfit(
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: Colors.green[800],
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _isLoading ? null : () => _showFormDialog(),
        backgroundColor: Colors.green[800],
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add),
        label: const Text('Tambah Data'),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }
    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 48, color: Colors.red),
              const SizedBox(height: 16),
              Text(
                'Gagal memuat data komunitas\n$_errorMessage',
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(color: Colors.red[700]),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  setState(() {
                    _isLoading = true;
                    _errorMessage = null;
                  });
                  _fetchData();
                },
                child: const Text('Coba Lagi'),
              ),
            ],
          ),
        ),
      );
    }
    if (_komunitasList.isEmpty) {
      return RefreshIndicator(
        onRefresh: _fetchData,
        child: ListView(
          children: const [
            SizedBox(height: 180),
            Icon(Icons.groups_outlined, size: 54, color: Color(0xFFCBD5E1)),
            SizedBox(height: 12),
            Center(child: Text('Belum ada data komunitas.')),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchData,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 92),
        itemCount: _komunitasList.length,
        itemBuilder: (context, index) {
          final komunitas = _komunitasList[index] as Map<String, dynamic>;
          final status = _text(komunitas['status_keanggotaan']);
          final isActive = status == 'AKTIF';
          final jenis = _text(
            komunitas['jenis_komunitas'],
          ).replaceAll('_', ' ').toUpperCase();

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 0,
            color: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: const BorderSide(color: Color(0xFFE2E8F0)),
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
                        child: Text(
                          _text(komunitas['nama_komunitas']),
                          style: GoogleFonts.outfit(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: const Color(0xFF0F172A),
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: (isActive ? Colors.green : Colors.red)
                              .withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          status,
                          style: GoogleFonts.inter(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: isActive
                                ? Colors.green[800]
                                : Colors.red[700],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _detailRow('Jenis', jenis),
                  _detailRow('NIK', _text(komunitas['nik'])),
                  _detailRow('Ketua / PJ', _text(komunitas['nama'])),
                  _detailRow('Nomor HP', _text(komunitas['nomor_hp'])),
                  _detailRow('Alamat', _text(komunitas['alamat'])),
                  const Divider(height: 22),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        onPressed: () => _showFormDialog(komunitas: komunitas),
                        icon: const Icon(Icons.edit_outlined, size: 17),
                        label: const Text('Edit'),
                      ),
                      TextButton.icon(
                        onPressed: () =>
                            _deleteKomunitas(_asInt(komunitas['id'])!),
                        icon: const Icon(Icons.delete_outline, size: 17),
                        label: const Text('Hapus'),
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.red,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _detailRow(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 3),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 92,
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
              fontWeight: FontWeight.w600,
              color: const Color(0xFF334155),
            ),
          ),
        ),
      ],
    ),
  );
}
