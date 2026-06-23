import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';

class LaporPanenScreen extends StatefulWidget {
  const LaporPanenScreen({super.key});

  @override
  State<LaporPanenScreen> createState() => _LaporPanenScreenState();
}

class _LaporPanenScreenState extends State<LaporPanenScreen> {
  final _formKey = GlobalKey<FormState>();
  final _hasilPanenController = TextEditingController();

  String? _selectedSiklusId;
  DateTime _tanggalPanen = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchTanamMetadata();
    });
  }

  @override
  void dispose() {
    _hasilPanenController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _tanggalPanen,
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

    if (picked != null && picked != _tanggalPanen) {
      setState(() {
        _tanggalPanen = picked;
      });
    }
  }

  String _formatDate(DateTime date) {
    final months = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<FarmingProvider>();
    final payload = {
      'siklus_tanam_id': int.tryParse(_selectedSiklusId ?? ''),
      'tanggal_panen': '${_tanggalPanen.year}-${_tanggalPanen.month.toString().padLeft(2, '0')}-${_tanggalPanen.day.toString().padLeft(2, '0')}',
      'hasil_panen': double.tryParse(_hasilPanenController.text.trim()),
    };

    final success = await provider.submitLaporPanen(payload);
    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Laporan hasil panen berhasil dikirim dan menunggu verifikasi petugas.'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } else {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Text('Gagal Lapor Panen', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
          content: Text(provider.errorMessage ?? 'Terjadi kesalahan sistem.', style: GoogleFonts.inter()),
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

    // Saring siklus tanam yang siap dilaporkan untuk dipanen
    // Sesuai filter pada web app: collect($siklusTanam)->filter(fn($item) => !empty($item['can_report_harvest']))
    final siapPanen = farmingProvider.mySiklusTanam.where((item) {
      return item['can_report_harvest'] == true;
    }).toList();

    if (farmingProvider.isLoading && farmingProvider.mySiklusTanam.isEmpty) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: Colors.green)),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Lapor Panen',
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

              // Form Card
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
                        'Data Panen Aktual',
                        style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.bold, color: const Color(0xFF14280B)),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Pastikan hasil ditulis dalam satuan ton.',
                        style: GoogleFonts.inter(fontSize: 11, color: Colors.grey[500]),
                      ),
                      const Divider(height: 30, color: Color(0xFFE2E8F0)),

                      // Dropdown Siklus Tanam Siap Panen
                      _buildLabel('Proses Tanam Siap Panen'),
                      DropdownButtonFormField<String>(
                        initialValue: _selectedSiklusId,
                        style: GoogleFonts.inter(fontSize: 14, color: Colors.black),
                        isExpanded: true,
                        decoration: _buildInputDecoration('Pilih proses tanam'),
                        items: siapPanen.map((item) {
                          final label = '${item['nama_lahan']} - ${item['nama_bibit']}';
                          return DropdownMenuItem<String>(
                            value: item['id'].toString(),
                            child: Text(label, overflow: TextOverflow.ellipsis),
                          );
                        }).toList(),
                        onChanged: siapPanen.isEmpty ? null : (val) => setState(() => _selectedSiklusId = val),
                        validator: (value) => value == null ? 'Proses tanam wajib dipilih' : null,
                      ),
                      if (siapPanen.isEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF3C7),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFFFDE68A)),
                          ),
                          child: Text(
                            'Belum ada proses tanam yang mencapai estimasi panen.',
                            style: GoogleFonts.inter(fontSize: 11, color: const Color(0xFF92400E), fontWeight: FontWeight.w600),
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),

                      // Date Panen
                      _buildLabel('Tanggal Panen'),
                      InkWell(
                        onTap: () => _selectDate(context),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          decoration: BoxDecoration(
                            border: Border.all(color: const Color(0xFFCBD5E1)),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                _formatDate(_tanggalPanen),
                                style: GoogleFonts.inter(fontSize: 13, fontWeight: FontWeight.w600),
                              ),
                              Icon(Icons.calendar_today_rounded, size: 16, color: Colors.green[800]),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Hasil Panen (Ton) Input
                      _buildLabel('Hasil Panen (Ton)'),
                      TextFormField(
                        controller: _hasilPanenController,
                        style: GoogleFonts.inter(fontSize: 14),
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: _buildInputDecoration('Contoh: 4.50'),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) return 'Hasil panen wajib diisi';
                          final val = double.tryParse(value);
                          if (val == null || val < 0) return 'Hasil panen harus bernilai positif';
                          return null;
                        },
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
                child: Text(
                  'Petugas akan memeriksa laporan. Hanya laporan berstatus DITERIMA yang memperbarui hasil lahan, riwayat panen, peta publik, dan statistik.',
                  style: GoogleFonts.inter(fontSize: 12, color: const Color(0xFF1E40AF), height: 1.4),
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
                      onPressed: (farmingProvider.isLoading || siapPanen.isEmpty) ? null : _submitForm,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green[800],
                        foregroundColor: Colors.white,
                        disabledBackgroundColor: Colors.grey[300],
                        disabledForegroundColor: Colors.grey[500],
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      child: farmingProvider.isLoading
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : Text(
                              'Kirim untuk Verifikasi',
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
            'Panduan 3 Langkah Lapor Hasil Panen',
            style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.bold, color: const Color(0xFF1E293B)),
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildWizardStep('1', 'Lapor Hasil', 'Masukkan data.', true),
              _buildConnector(),
              _buildWizardStep('2', 'Verifikasi', 'Review petugas.', false),
              _buildConnector(),
              _buildWizardStep('3', 'Selesai', 'Masuk statistik.', false),
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
