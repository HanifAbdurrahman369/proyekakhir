import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';
import 'petugas_lahan_termonitor_content.dart';

class PetugasLahanTermonitorScreen extends StatefulWidget {
  const PetugasLahanTermonitorScreen({super.key});

  @override
  State<PetugasLahanTermonitorScreen> createState() =>
      _PetugasLahanTermonitorScreenState();
}

class _PetugasLahanTermonitorScreenState
    extends State<PetugasLahanTermonitorScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchLahanTermonitorData();
    });
  }

  Future<void> _refresh() =>
      context.read<FarmingProvider>().fetchLahanTermonitorData();

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Lahan Termonitor (IoT)',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF3E7D00),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: _refresh,
            color: const Color(0xFF3E7D00),
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: const PetugasLahanTermonitorContent(),
            ),
          ),
          if (provider.isPetugasActionLoading)
            const Positioned(
              left: 0,
              right: 0,
              top: 0,
              child: LinearProgressIndicator(
                color: Color(0xFF3E7D00),
                minHeight: 3,
              ),
            ),
        ],
      ),
    );
  }
}
