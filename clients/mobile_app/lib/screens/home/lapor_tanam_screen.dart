import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';

class LaporTanamScreen extends StatefulWidget {
  const LaporTanamScreen({super.key});

  @override
  State<LaporTanamScreen> createState() => _LaporTanamScreenState();
}

class _LaporTanamScreenState extends State<LaporTanamScreen> {
  final _formKey = GlobalKey<FormState>();
  final _estimasiHariController = TextEditingController();
  final _luasTanamController = TextEditingController();
  final _takaranController = TextEditingController();

  String? _selectedLahanId;
  String? _selectedBibitId;
  String? _selectedPupukId;

  DateTime _tanggalTanam = DateTime.now();
  DateTime _tanggalPemupukan = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchTanamMetadata();
    });
  }

  @override
  void dispose() {
    _estimasiHariController.dispose();
    _luasTanamController.dispose();
    _takaranController.dispose();
    super.dispose();
  }

  void _onLahanChanged(String? lahanId, List<dynamic> lahanList) {
    setState(() {
      _selectedLahanId = lahanId;
      if (lahanId != null) {
        final lahan = lahanList.firstWhere(
          (item) => item['id'].toString() == lahanId,
          orElse: () => null,
        );
        final luasTanam =
            lahan?['luas_tanam_hektar'] ?? lahan?['luas_lahan_hektar'];
        if (luasTanam != null) {
          _luasTanamController.text = luasTanam.toString();
        }
      } else {
        _luasTanamController.clear();
      }
    });
  }

  void _onBibitChanged(String? bibitId, List<dynamic> bibitList) {
    setState(() {
      _selectedBibitId = bibitId;
      if (bibitId != null) {
        final bibit = bibitList.firstWhere(
          (item) => item['id'].toString() == bibitId,
          orElse: () => null,
        );
        if (bibit != null && bibit['masa_tanam_hari'] != null) {
          _estimasiHariController.text = bibit['masa_tanam_hari'].toString();
        }
      }
    });
  }

  Future<void> _selectDate(BuildContext context, bool isTanam) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: isTanam ? _tanggalTanam : _tanggalPemupukan,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.green[800]!,
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        if (isTanam) {
          _tanggalTanam = picked;
          // Set pemupukan date to match tanam if it becomes earlier
          if (_tanggalPemupukan.isBefore(_tanggalTanam)) {
            _tanggalPemupukan = _tanggalTanam;
          }
        } else {
          _tanggalPemupukan = picked;
        }
      });
    }
  }

  String _formatDate(DateTime date) {
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
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

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

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    if (_tanggalPemupukan.isBefore(_tanggalTanam)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Tanggal pemupukan tidak boleh lebih awal dari tanggal tanam.',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final provider = context.read<FarmingProvider>();
    final payload = {
      'lahan_id': int.tryParse(_selectedLahanId ?? ''),
      'luas_tanam_hektar': double.tryParse(_luasTanamController.text.trim()),
      'bibit_id': int.tryParse(_selectedBibitId ?? ''),
      'tanggal_tanam':
          '${_tanggalTanam.year}-${_tanggalTanam.month.toString().padLeft(2, '0')}-${_tanggalTanam.day.toString().padLeft(2, '0')}',
      'estimasi_hari_tanam': int.tryParse(_estimasiHariController.text.trim()),
      'pupuk_id': int.tryParse(_selectedPupukId ?? ''),
      'tanggal_pemupukan':
          '${_tanggalPemupukan.year}-${_tanggalPemupukan.month.toString().padLeft(2, '0')}-${_tanggalPemupukan.day.toString().padLeft(2, '0')}',
      'takaran': double.tryParse(_takaranController.text.trim()),
    };

    final success = await provider.submitLaporTanam(payload);
    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Laporan tanam dan pemupukan berhasil disimpan.'),
          backgroundColor: Colors.green,
        ),
      );
      // Reset form
      setState(() {
        _selectedLahanId = null;
        _selectedBibitId = null;
        _selectedPupukId = null;
        _estimasiHariController.clear();
        _luasTanamController.clear();
        _takaranController.clear();
        _tanggalTanam = DateTime.now();
        _tanggalPemupukan = DateTime.now();
      });
    } else {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Gagal Lapor Tanam',
            style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
          ),
          content: Text(
            provider.errorMessage ?? 'Terjadi kesalahan sistem.',
            style: GoogleFonts.inter(),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('OK', style: TextStyle(color: Colors.green[800])),
            ),
          ],
        ),
      );
    }
  }

  Future<void> _deleteSiklus(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(
          'Hapus Laporan Tanam',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        content: Text(
          'Apakah Anda yakin ingin menghapus laporan tanam ini?',
          style: GoogleFonts.inter(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('Batal', style: TextStyle(color: Colors.green[800])),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Hapus', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      if (!mounted) return;
      final provider = context.read<FarmingProvider>();
      final success = await provider.deleteSiklusTanam(id);
      if (!mounted) return;

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Laporan tanam berhasil dihapus.'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              provider.errorMessage ?? 'Gagal menghapus laporan tanam.',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final farmingProvider = context.watch<FarmingProvider>();

    if (farmingProvider.isLoading && farmingProvider.bibitList.isEmpty) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: Colors.green)),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Lapor Tanam',
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
              // Header Info
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFEDF8DC),
                  border: Border.all(color: const Color(0xFFDFECCC)),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    'Estimasi panen dihitung otomatis berdasarkan masa varietas bibit.',
                    style: GoogleFonts.inter(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF3E7D00),
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Error Banner
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
                          'Gagal memuat beberapa data:\n${farmingProvider.errorMessage}',
                          style: GoogleFonts.inter(
                            color: Colors.red[800],
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Wizard
              _buildStepWizard(),
              const SizedBox(height: 20),

              // Card 1: Informasi Tanam
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
                      Row(
                        children: [
                          Icon(
                            Icons.grass_rounded,
                            color: Colors.green[800],
                            size: 20,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Informasi Tanam',
                            style: GoogleFonts.outfit(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: Colors.green[800],
                            ),
                          ),
                        ],
                      ),
                      const Divider(height: 24, color: Color(0xFFE2E8F0)),

                      _buildLabel('Lahan Sawah'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedLahanId,
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          color: Colors.black,
                        ),
                        decoration: _buildInputDecoration(
                          'Pilih Lahan Terverifikasi',
                        ),
                        items: farmingProvider.lahanDropdownList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              '${item['nama_lahan']} - ${item['pemilik_lahan'] ?? '-'}',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) => _onLahanChanged(
                          val,
                          farmingProvider.lahanDropdownList,
                        ),
                        validator: (value) =>
                            value == null ? 'Lahan wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      _buildLabel('Luas Tanam (Ha)'),
                      TextFormField(
                        controller: _luasTanamController,
                        style: GoogleFonts.inter(fontSize: 14),
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: _buildInputDecoration('Contoh: 1.25'),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Wajib diisi';
                          }
                          final val = double.tryParse(value);
                          if (val == null || val <= 0) {
                            return 'Harus positif';
                          }
                          if (_selectedLahanId != null) {
                            final lahan = farmingProvider.lahanDropdownList
                                .firstWhere(
                                  (item) =>
                                      item['id'].toString() == _selectedLahanId,
                                  orElse: () => null,
                                );
                            final luasLahan = double.tryParse(
                              (lahan?['luas_lahan_hektar'] ?? '').toString(),
                            );
                            if (luasLahan != null && val > luasLahan) {
                              return 'Maksimal ${luasLahan.toStringAsFixed(2)} ha';
                            }
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),

                      _buildLabel('Jenis Bibit'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedBibitId,
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          color: Colors.black,
                        ),
                        decoration: _buildInputDecoration('Pilih Bibit'),
                        items: farmingProvider.bibitList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              '${item['nama_bibit']} - ${item['masa_tanam_hari']} Hari',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) =>
                            _onBibitChanged(val, farmingProvider.bibitList),
                        validator: (value) =>
                            value == null ? 'Bibit wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel('Tanggal Tanam'),
                                InkWell(
                                  onTap: () => _selectDate(context, true),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 14,
                                    ),
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      border: Border.all(
                                        color: const Color(0xFFCBD5E1),
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Expanded(
                                          child: Text(
                                            _formatDate(_tanggalTanam),
                                            style: GoogleFonts.inter(
                                              fontSize: 14,
                                              color: Colors.black,
                                            ),
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        Icon(
                                          Icons.calendar_today_rounded,
                                          size: 18,
                                          color: Colors.green[800],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel('Estimasi (Hari)'),
                                TextFormField(
                                  controller: _estimasiHariController,
                                  style: GoogleFonts.inter(fontSize: 14),
                                  keyboardType: TextInputType.number,
                                  decoration: _buildInputDecoration(
                                    'Contoh: 120',
                                  ),
                                  validator: (value) {
                                    if (value == null || value.trim().isEmpty) {
                                      return 'Wajib diisi';
                                    }
                                    final val = int.tryParse(value);
                                    if (val == null || val <= 0) {
                                      return 'Harus positif';
                                    }
                                    return null;
                                  },
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Card 2: Informasi Pemupukan Awal
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
                      Row(
                        children: [
                          Icon(
                            Icons.science_rounded,
                            color: Colors.teal[800],
                            size: 20,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Informasi Pemupukan Awal',
                            style: GoogleFonts.outfit(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: Colors.teal[800],
                            ),
                          ),
                        ],
                      ),
                      const Divider(height: 24, color: Color(0xFFE2E8F0)),

                      _buildLabel('Jenis Pupuk Awal'),
                      DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue: _selectedPupukId,
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          color: Colors.black,
                        ),
                        decoration: _buildInputDecoration('Pilih Pupuk'),
                        items: farmingProvider.pupukList.map((item) {
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(
                              '${item['nama_pupuk']} - ${item['tipe_pupuk'] ?? 'Umum'}',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) =>
                            setState(() => _selectedPupukId = val),
                        validator: (value) =>
                            value == null ? 'Pupuk wajib dipilih' : null,
                      ),
                      const SizedBox(height: 16),

                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel('Tanggal Pemupukan'),
                                InkWell(
                                  onTap: () => _selectDate(context, false),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 14,
                                    ),
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      border: Border.all(
                                        color: const Color(0xFFCBD5E1),
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Expanded(
                                          child: Text(
                                            _formatDate(_tanggalPemupukan),
                                            style: GoogleFonts.inter(
                                              fontSize: 14,
                                              color: Colors.black,
                                            ),
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        Icon(
                                          Icons.calendar_today_rounded,
                                          size: 18,
                                          color: Colors.teal[800],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel('Takaran (Kg)'),
                                TextFormField(
                                  controller: _takaranController,
                                  style: GoogleFonts.inter(fontSize: 14),
                                  keyboardType:
                                      const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                  decoration: _buildInputDecoration(
                                    'Contoh: 20',
                                  ),
                                  validator: (value) {
                                    if (value == null || value.trim().isEmpty) {
                                      return 'Wajib diisi';
                                    }
                                    final val = double.tryParse(value);
                                    if (val == null || val <= 0) {
                                      return 'Harus positif';
                                    }
                                    return null;
                                  },
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Submit Button
              ElevatedButton(
                onPressed: farmingProvider.isLoading ? null : _submitForm,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[800],
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                child: farmingProvider.isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                          color: Colors.white,
                          strokeWidth: 2,
                        ),
                      )
                    : Text(
                        'Mulai Proses Tanam & Pemupukan',
                        style: GoogleFonts.inter(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
              ),
              const SizedBox(height: 24),

              // Section: Proses Tanam Berjalan
              Text(
                'Proses Tanam Berjalan',
                style: GoogleFonts.outfit(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF1E293B),
                ),
              ),
              const SizedBox(height: 12),

              // Active Cycles List
              if (farmingProvider.mySiklusTanam.isEmpty)
                Card(
                  color: Colors.white,
                  surfaceTintColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Center(
                      child: Text(
                        'Belum ada proses tanam aktif.',
                        style: GoogleFonts.inter(
                          color: Colors.grey[500],
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: farmingProvider.mySiklusTanam.length,
                  itemBuilder: (context, index) {
                    final siklus = farmingProvider.mySiklusTanam[index];
                    final progress =
                        double.tryParse(siklus['progress_persen'].toString()) ??
                        0.0;

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      color: Colors.white,
                      surfaceTintColor: Colors.white,
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
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        siklus['nama_lahan'] ?? '-',
                                        style: GoogleFonts.outfit(
                                          fontSize: 15,
                                          fontWeight: FontWeight.bold,
                                          color: const Color(0xFF1E293B),
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 8,
                                          vertical: 2,
                                        ),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFEDF8DC),
                                          borderRadius: BorderRadius.circular(
                                            8,
                                          ),
                                        ),
                                        child: Text(
                                          siklus['nama_bibit'] ?? '-',
                                          style: GoogleFonts.inter(
                                            fontSize: 9,
                                            fontWeight: FontWeight.bold,
                                            color: const Color(0xFF3E7D00),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                IconButton(
                                  onPressed: () => _deleteSiklus(siklus['id']),
                                  icon: const Icon(
                                    Icons.delete_outline_rounded,
                                    color: Colors.red,
                                  ),
                                ),
                              ],
                            ),
                            const Divider(height: 20, color: Color(0xFFF1F5F9)),
                            Wrap(
                              spacing: 12,
                              runSpacing: 4,
                              children: [
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.calendar_today,
                                      size: 10,
                                      color: Colors.grey[400],
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      'Tanam: ${_formatDateStr(siklus['tanggal_tanam'])}',
                                      style: GoogleFonts.inter(
                                        fontSize: 11,
                                        color: Colors.grey[600],
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.event_available,
                                      size: 10,
                                      color: Colors.grey[400],
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      'Panen: ${_formatDateStr(siklus['estimasi_tanggal_panen'])}',
                                      style: GoogleFonts.inter(
                                        fontSize: 11,
                                        color: Colors.grey[600],
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                            if (siklus['pemupukan_awal'] != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Pemupukan awal: ${siklus['pemupukan_awal']['nama_pupuk'] ?? '-'} (${siklus['pemupukan_awal']['takaran'] ?? '0'} kg)',
                                style: GoogleFonts.inter(
                                  fontSize: 11,
                                  color: Colors.grey[500],
                                ),
                              ),
                            ],
                            const SizedBox(height: 4),
                            Text(
                              'Luas tanam: ${siklus['luas_tanam_hektar'] ?? '-'} ha',
                              style: GoogleFonts.inter(
                                fontSize: 11,
                                color: Colors.grey[500],
                              ),
                            ),
                            const SizedBox(height: 12),
                            // Progress bar
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: LinearProgressIndicator(
                                value: progress / 100,
                                backgroundColor: const Color(0xFFF1F5F9),
                                color: const Color(0xFF5EA500),
                                minHeight: 8,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  '${progress.toStringAsFixed(0)}% masa tanam',
                                  style: GoogleFonts.inter(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey[600],
                                  ),
                                ),
                                Text(
                                  '${siklus['hari_tersisa'] ?? 0} hari lagi',
                                  style: GoogleFonts.inter(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF3E7D00),
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
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepWizard() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Panduan 3 Langkah Memulai Masa Tanam',
            style: GoogleFonts.outfit(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildWizardStep('1', 'Pilih Sawah', true),
              _buildConnector(),
              _buildWizardStep('2', 'Pemupukan', true),
              _buildConnector(),
              _buildWizardStep('3', 'Mulai Tanam', true),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWizardStep(String number, String title, bool isActive) {
    return Expanded(
      child: Column(
        children: [
          CircleAvatar(
            radius: 12,
            backgroundColor: isActive
                ? Colors.green[800]
                : const Color(0xFFF1F5F9),
            child: Text(
              number,
              style: GoogleFonts.inter(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: isActive ? Colors.white : const Color(0xFF64748B),
              ),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: GoogleFonts.inter(
              fontSize: 9,
              fontWeight: FontWeight.bold,
              color: isActive ? Colors.green[800] : const Color(0xFF334155),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildConnector() {
    return const Padding(
      padding: EdgeInsets.only(top: 8, left: 1, right: 1),
      child: Icon(
        Icons.arrow_forward_rounded,
        size: 10,
        color: Color(0xFF94A3B8),
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, left: 2),
      child: Text(
        text,
        style: GoogleFonts.inter(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          color: const Color(0xFF475569),
        ),
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
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.green[800]!, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Colors.red, width: 1),
      ),
    );
  }
}
