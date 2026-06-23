import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'auth/login_screen.dart';

class LandingScreen extends StatelessWidget {
  const LandingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      // NAVBAR (AppBar) di bagian atas (Glassmorphic)
      appBar: AppBar(
        backgroundColor: Colors.white.withValues(alpha: 0.8),
        elevation: 0,
        scrolledUnderElevation: 0,
        flexibleSpace: ClipRect(
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
            child: Container(color: Colors.transparent),
          ),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(
            color: const Color(0xFFE2E8F0).withValues(alpha: 0.5), // Slate 200 bottom border line
            height: 1,
          ),
        ),
        title: Row(
          children: [
            Image.asset(
              'assets/images/logo.png',
              width: 32,
              height: 32,
              fit: BoxFit.contain,
            ),
            const SizedBox(width: 8),
            RichText(
              text: TextSpan(
                style: GoogleFonts.outfit(
                  fontWeight: FontWeight.w900,
                  fontSize: 18,
                  color: const Color(0xFF0F172A),
                ),
                children: const [
                  TextSpan(text: 'SIG'),
                  TextSpan(
                    text: '-PALA',
                    style: TextStyle(color: Color(0xFF10B981)),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          // Button Login di Navbar yang mengarah ke Halaman Login
          Padding(
            padding: const EdgeInsets.only(right: 16.0),
            child: Container(
              height: 34,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(17),
                gradient: const LinearGradient(
                  colors: [Color(0xFF5EA500), Color(0xFF3E7D00)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF5EA500).withValues(alpha: 0.2),
                    offset: const Offset(0, 4),
                    blurRadius: 10,
                  ),
                ],
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => const LoginScreen()),
                    );
                  },
                  borderRadius: BorderRadius.circular(17),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    child: Center(
                      child: Text(
                        'Masuk',
                        style: GoogleFonts.inter(
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      body: Stack(
        children: [
          // 1. Background gradient
          Positioned.fill(
            child: Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0xFFF7FBF2),
                    Color(0xFFEDF7E6),
                    Color(0xFFF9FCF7),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  stops: [0.0, 0.48, 1.0],
                ),
              ),
            ),
          ),
          // 2. Grid pattern
          const Positioned.fill(
            child: CustomPaint(
              painter: GridPainter(),
            ),
          ),
          // 3. Radial lighting glows (top-left & bottom-right)
          Positioned(
            left: -150,
            top: -150,
            child: Container(
              width: 400,
              height: 400,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFFBBF451).withValues(alpha: 0.22),
                    Colors.transparent,
                  ],
                  center: Alignment.center,
                  radius: 0.5,
                ),
              ),
            ),
          ),
          Positioned(
            right: -150,
            bottom: -150,
            child: Container(
              width: 400,
              height: 400,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF3E7D00).withValues(alpha: 0.18),
                    Colors.transparent,
                  ],
                  center: Alignment.center,
                  radius: 0.5,
                ),
              ),
            ),
          ),
          // 4. Main content
          SafeArea(
            child: SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Hero Section Card (Glassmorphic)
                  Container(
                    margin: const EdgeInsets.only(top: 12, bottom: 24),
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(color: Colors.white, width: 1.5),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF35530E).withValues(alpha: 0.08),
                          offset: const Offset(0, 16),
                          blurRadius: 40,
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        // Tag Badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEDF7E6),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: const Color(0xFFDFECCC)),
                          ),
                          child: Text(
                            '🌱 PLATFORM GEOSPASIAL PERTANIAN',
                            textAlign: TextAlign.center,
                            style: GoogleFonts.inter(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF497D00),
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                        // Hero Logo
                        Container(
                          width: 88,
                          height: 88,
                          alignment: Alignment.center,
                          child: Image.asset(
                            'assets/images/logo.png',
                            fit: BoxFit.contain,
                          ),
                        ),
                        const SizedBox(height: 24),
                        // Title
                        RichText(
                          textAlign: TextAlign.center,
                          text: TextSpan(
                            style: GoogleFonts.outfit(
                              fontSize: 26,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF0F172A),
                              letterSpacing: 0.5,
                            ),
                            children: const [
                              TextSpan(text: 'Selamat Datang di '),
                              TextSpan(text: 'SIG'),
                              TextSpan(
                                text: '-PALA',
                                style: TextStyle(color: Color(0xFF10B981)),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 8),
                        // Subtitle
                        Text(
                          'Sistem Informasi Geografis Produktivitas Lahan Rawa',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.inter(
                            fontSize: 13,
                            color: const Color(0xFF497D00),
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 16),
                        // Description
                        Text(
                          'Pantau ketersediaan lahan sawah rawa secara spasial, riwayat aktivitas tanam, dan produktivitas hasil panen di Kabupaten Barito Kuala.',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.inter(
                            fontSize: 13,
                            color: const Color(0xFF64748B),
                            height: 1.5,
                          ),
                        ),
                        const SizedBox(height: 28),
                        // CTA Button with Gradient
                        Container(
                          height: 50,
                          width: double.infinity,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(25),
                            gradient: const LinearGradient(
                              colors: [Color(0xFF5EA500), Color(0xFF3E7D00)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFF5EA500).withValues(alpha: 0.25),
                                offset: const Offset(0, 8),
                                blurRadius: 16,
                              ),
                            ],
                          ),
                          child: Material(
                            color: Colors.transparent,
                            child: InkWell(
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(builder: (context) => const LoginScreen()),
                                );
                              },
                              borderRadius: BorderRadius.circular(25),
                              child: Center(
                                child: Text(
                                  'Mulai Sekarang',
                                  style: GoogleFonts.inter(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Fitur Section Title
                  Padding(
                    padding: const EdgeInsets.only(left: 4.0, bottom: 16.0),
                    child: Text(
                      'Fitur Unggulan',
                      style: GoogleFonts.outfit(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF0F172A),
                      ),
                    ),
                  ),

                  // Fitur Items List
                  _buildFeatureItem(
                    icon: Icons.map_rounded,
                    title: 'Peta Interaktif Geospasial',
                    description: 'Eksplorasi batas wilayah hingga detail blok lahan sawah rawa secara visual dan real-time.',
                    iconColor: Colors.teal.shade700,
                  ),
                  const SizedBox(height: 14),
                  _buildFeatureItem(
                    icon: Icons.bar_chart_rounded,
                    title: 'Analisis Statistik Transparan',
                    description: 'Akumulasi luasan sawah dan perkembangan pertanian disajikan transparan dalam grafik visual.',
                    iconColor: Colors.orange.shade800,
                  ),
                  const SizedBox(height: 14),
                  _buildFeatureItem(
                    icon: Icons.trending_up_rounded,
                    title: 'Informasi Produktivitas Akurat',
                    description: 'Melacak potensi dan realisasi hasil panen lahan sawah rawa dalam satuan Ton/Ha.',
                    iconColor: Colors.green.shade800,
                  ),
                  const SizedBox(height: 32),

                  // Footer
                  Center(
                    child: Text(
                      '🌾 2026 — SIG-PALA',
                      style: GoogleFonts.inter(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: const Color(0xFF497D00),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFeatureItem({
    required IconData icon,
    required String title,
    required String description,
    required Color iconColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF35530E).withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: iconColor.withValues(alpha: 0.1),
            radius: 24,
            child: Icon(icon, color: iconColor, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.outfit(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  description,
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    color: const Color(0xFF64748B),
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class GridPainter extends CustomPainter {
  const GridPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x0C487D00)
      ..strokeWidth = 1.0;

    const double step = 28.0;

    for (double y = 0; y < size.height; y += step) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), paint);
    }

    for (double x = 0; x < size.width; x += step) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
