import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../services/auth_service.dart';
import '../../core/network/api_client.dart';

class PetugasKomunitasScreen extends StatefulWidget {
  const PetugasKomunitasScreen({super.key});

  @override
  State<PetugasKomunitasScreen> createState() => _PetugasKomunitasScreenState();
}

class _PetugasKomunitasScreenState extends State<PetugasKomunitasScreen> {
  bool _isLoading = true;
  List<dynamic> _komunitasList = [];
  String? _errorMessage;
  final AuthService _authService = AuthService(ApiClient());

  @override
  void initState() {
    super.initState();
    _fetchKomunitas();
  }

  Future<void> _fetchKomunitas() async {
    try {
      final res = await _authService.getKomunitas(page: 1);
      if (mounted) {
        setState(() {
          _komunitasList = (res['data'] as List<dynamic>?) ?? [];
          if (_komunitasList.isEmpty && res.containsKey('komunitas')) {
            _komunitasList = (res['komunitas'] as List<dynamic>?) ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _deleteKomunitas(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Komunitas'),
        content: const Text('Yakin ingin menghapus komunitas ini?'),
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
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Komunitas berhasil dihapus'),
            backgroundColor: Colors.green,
          ),
        );
        _fetchKomunitas();
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString()), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _showFormDialog({Map<String, dynamic>? komunitas}) {
    final isEdit = komunitas != null;
    final namaController = TextEditingController(
      text: isEdit ? komunitas['nama_komunitas'] ?? komunitas['nama'] : '',
    );
    String? selectedJenis = isEdit
        ? (komunitas['jenis_komunitas'] ?? komunitas['tipe'])
        : null;
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              title: Text(
                isEdit ? 'Edit Komunitas' : 'Tambah Komunitas',
                style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
              ),
              content: Form(
                key: formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DropdownButtonFormField<String>(
                      initialValue: selectedJenis,
                      decoration: const InputDecoration(
                        labelText: 'Jenis Komunitas',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(
                          value: 'kelompok_tani',
                          child: Text('Kelompok Tani'),
                        ),
                        DropdownMenuItem(
                          value: 'brigade_pangan',
                          child: Text('Brigade Pangan'),
                        ),
                        DropdownMenuItem(
                          value: 'gapoktan',
                          child: Text('Gapoktan'),
                        ),
                      ],
                      onChanged: (val) {
                        setModalState(() => selectedJenis = val);
                      },
                      validator: (value) =>
                          value == null ? 'Pilih jenis' : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: namaController,
                      decoration: const InputDecoration(
                        labelText: 'Nama Komunitas',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) =>
                          value == null || value.isEmpty ? 'Wajib diisi' : null,
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Batal'),
                ),
                ElevatedButton(
                  onPressed: () async {
                    if (!formKey.currentState!.validate()) return;
                    Navigator.pop(context);
                    setState(() => _isLoading = true);

                    final payload = {
                      'jenis_komunitas': selectedJenis,
                      // Backend web mewajibkan kolom `nama`; kirim alias tampilan
                      // sekaligus agar data web dan mobile membaca record yang sama.
                      'nama': namaController.text.trim(),
                      'nama_komunitas': namaController.text,
                    };

                    final messenger = ScaffoldMessenger.of(context);
                    try {
                      if (isEdit) {
                        await _authService.updateKomunitas(
                          komunitas['id'],
                          payload,
                        );
                      } else {
                        await _authService.createKomunitas(payload);
                      }
                      messenger.showSnackBar(
                        SnackBar(
                          content: Text(
                            'Komunitas berhasil ${isEdit ? 'diedit' : 'ditambah'}',
                          ),
                          backgroundColor: Colors.green,
                        ),
                      );
                      _fetchKomunitas();
                    } catch (e) {
                      setState(() => _isLoading = false);
                      messenger.showSnackBar(
                        SnackBar(
                          content: Text(e.toString()),
                          backgroundColor: Colors.red,
                        ),
                      );
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green[800],
                  ),
                  child: Text(
                    'Simpan',
                    style: const TextStyle(color: Colors.white),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
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
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showFormDialog(),
        backgroundColor: Colors.green[800],
        child: const Icon(Icons.add, color: Colors.white),
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
                  _fetchKomunitas();
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[800],
                ),
                child: const Text(
                  'Coba Lagi',
                  style: TextStyle(color: Colors.white),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (_komunitasList.isEmpty) {
      return Center(
        child: Text(
          'Tidak ada data komunitas.',
          style: GoogleFonts.inter(color: Colors.grey[600]),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchKomunitas,
      color: Colors.green,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _komunitasList.length,
        itemBuilder: (context, index) {
          final k = _komunitasList[index];
          String nama = k['nama_komunitas'] ?? k['nama'] ?? '-';
          String jenis = k['jenis_komunitas'] ?? k['tipe'] ?? '-';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
            elevation: 2,
            shadowColor: Colors.black12,
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
                          nama,
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
                          color: Colors.green.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          jenis.toUpperCase(),
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: Colors.green[800],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        onPressed: () => _showFormDialog(komunitas: k),
                        icon: const Icon(Icons.edit, size: 16),
                        label: const Text('Edit'),
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.blue,
                        ),
                      ),
                      TextButton.icon(
                        onPressed: () => _deleteKomunitas(k['id']),
                        icon: const Icon(Icons.delete, size: 16),
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
}
