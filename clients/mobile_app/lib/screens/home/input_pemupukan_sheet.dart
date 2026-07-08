import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/farming_provider.dart';

class InputPemupukanSheet extends StatefulWidget {
  const InputPemupukanSheet({super.key});

  @override
  State<InputPemupukanSheet> createState() => _InputPemupukanSheetState();
}

class _InputPemupukanSheetState extends State<InputPemupukanSheet> {
  final _formKey = GlobalKey<FormState>();

  int? _selectedSiklusId;
  int? _selectedPupukId;
  DateTime? _selectedDate;
  final TextEditingController _takaranController = TextEditingController();

  bool _isSubmitting = false;

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF166534), // green-800
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih tanggal pemupukan terlebih dahulu'),
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final payload = {
      'siklus_tanam_id': _selectedSiklusId,
      'pupuk_id': _selectedPupukId,
      'tanggal_pemupukan': DateFormat('yyyy-MM-dd').format(_selectedDate!),
      'takaran': double.tryParse(_takaranController.text) ?? 0,
    };

    final provider = context.read<FarmingProvider>();
    final success = await provider.submitPemupukan(payload);

    if (!mounted) return;

    setState(() => _isSubmitting = false);

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Catatan pemupukan berhasil disimpan'),
          backgroundColor: Color(0xFF059669),
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage ?? 'Gagal menyimpan catatan'),
          backgroundColor: Colors.red[700],
        ),
      );
    }
  }

  @override
  void dispose() {
    _takaranController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
    final siklusList = provider.mySiklusTanam;
    final pupukList = provider.pupukList;

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: 24,
        right: 24,
        top: 24,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Input Pemupukan',
                    style: GoogleFonts.outfit(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF1E293B),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Dropdown Siklus Tanam
              DropdownButtonFormField<int>(
                value: _selectedSiklusId,
                decoration: _inputDecoration(
                  'Pilih Siklus Tanam / Lahan',
                  Icons.grass_rounded,
                ),
                items: siklusList.map((s) {
                  final lahan = s['lahan'] as Map<String, dynamic>?;
                  final namaLahan = lahan?['nama_lahan'] ?? 'Siklus ${s['id']}';
                  return DropdownMenuItem<int>(
                    value: s['id'] as int,
                    child: Text(
                      namaLahan,
                      style: GoogleFonts.inter(fontSize: 14),
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedSiklusId = val),
                validator: (val) => val == null ? 'Pilih lahan/siklus' : null,
              ),
              const SizedBox(height: 16),

              // Dropdown Jenis Pupuk
              DropdownButtonFormField<int>(
                value: _selectedPupukId,
                decoration: _inputDecoration(
                  'Pilih Jenis Pupuk',
                  Icons.science_rounded,
                ),
                items: pupukList.map((p) {
                  return DropdownMenuItem<int>(
                    value: p['id'] as int,
                    child: Text(
                      p['nama_pupuk'],
                      style: GoogleFonts.inter(fontSize: 14),
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedPupukId = val),
                validator: (val) => val == null ? 'Pilih jenis pupuk' : null,
              ),
              const SizedBox(height: 16),

              // Tanggal Pemupukan
              InkWell(
                onTap: () => _selectDate(context),
                borderRadius: BorderRadius.circular(12),
                child: InputDecorator(
                  decoration: _inputDecoration(
                    'Tanggal Pemupukan',
                    Icons.calendar_month_rounded,
                  ),
                  child: Text(
                    _selectedDate == null
                        ? 'Pilih Tanggal'
                        : DateFormat('dd MMM yyyy').format(_selectedDate!),
                    style: GoogleFonts.inter(
                      fontSize: 14,
                      color: _selectedDate == null
                          ? Colors.grey[600]
                          : const Color(0xFF1E293B),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Takaran
              TextFormField(
                controller: _takaranController,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: _inputDecoration(
                  'Takaran (Kg)',
                  Icons.scale_rounded,
                ),
                style: GoogleFonts.inter(fontSize: 14),
                validator: (val) {
                  if (val == null || val.isEmpty) return 'Masukkan takaran';
                  if (double.tryParse(val) == null) return 'Angka tidak valid';
                  return null;
                },
              ),
              const SizedBox(height: 32),

              // Submit Button
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green[800],
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2.5,
                          ),
                        )
                      : Text(
                          'Simpan Pemupukan',
                          style: GoogleFonts.inter(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: GoogleFonts.inter(fontSize: 14, color: Colors.grey[600]),
      prefixIcon: Icon(icon, color: Colors.grey[500], size: 20),
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF166534), width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    );
  }
}
