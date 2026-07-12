import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/auth_service.dart';
import '../core/network/api_client.dart';

class AdminKomunitasScreen extends StatefulWidget {
  const AdminKomunitasScreen({super.key});

  @override
  State<AdminKomunitasScreen> createState() => _AdminKomunitasScreenState();
}

class _AdminKomunitasScreenState extends State<AdminKomunitasScreen> {
  bool _isLoading = true;
  List<dynamic> _komunitasList = [];
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchKomunitas();
  }

  Future<void> _fetchKomunitas() async {
    try {
      final authService = AuthService(ApiClient());
      final res = await authService.getKomunitas();
      if (mounted) {
        setState(() {
          _komunitasList = (res['data'] as List<dynamic>?) ?? [];
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: Text(
          'Manajemen Komunitas',
          style: GoogleFonts.poppins(
            color: const Color(0xFF0F172A),
            fontWeight: FontWeight.bold,
            fontSize: 20,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF0F172A)),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.teal),
      );
    }
    
    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: Colors.red),
              const SizedBox(height: 16),
              Text(
                'Gagal memuat data komunitas\n$_errorMessage',
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(color: Colors.red[700]),
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
                style: ElevatedButton.styleFrom(backgroundColor: Colors.teal),
                child: const Text('Coba Lagi'),
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
          style: GoogleFonts.poppins(color: Colors.grey[600]),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchKomunitas,
      color: Colors.teal,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _komunitasList.length,
        itemBuilder: (context, index) {
          final k = _komunitasList[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
                          k['nama'] ?? '-',
                          style: GoogleFonts.poppins(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: const Color(0xFF0F172A),
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: (k['tipe'] == 'Kelompok Tani' ? Colors.green : Colors.blue).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          k['tipe'] ?? '-',
                          style: GoogleFonts.poppins(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: k['tipe'] == 'Kelompok Tani' ? Colors.green[700] : Colors.blue[700],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _buildInfoRow(Icons.person_rounded, 'Ketua: ${k['nama_ketua'] ?? '-'}'),
                  const SizedBox(height: 4),
                  _buildInfoRow(Icons.location_on_rounded, 'Kecamatan: ${k['kecamatan']?['nama'] ?? k['kecamatan_id'] ?? '-'}'),
                  const SizedBox(height: 4),
                  _buildInfoRow(Icons.pin_drop_rounded, 'Kelurahan: ${k['kelurahan']?['nama'] ?? k['kelurahan_id'] ?? '-'}'),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.poppins(fontSize: 13, color: Colors.grey[800]),
          ),
        ),
      ],
    );
  }
}
