import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import 'forgot_password_screen.dart';


class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _loginIdController = TextEditingController();
  final _passwordController = TextEditingController();
  final _captchaController = TextEditingController();
  bool _obscurePassword = true;
  int _num1 = 0;
  int _num2 = 0;

  @override
  void initState() {
    super.initState();
    _generateCaptcha();
  }

  void _generateCaptcha() {
    final random = math.Random();
    setState(() {
      _num1 = random.nextInt(15) + 1;
      _num2 = random.nextInt(15) + 1;
      _captchaController.clear();
    });
  }

  @override
  void dispose() {
    _loginIdController.dispose();
    _passwordController.dispose();
    _captchaController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final captchaAnswer = int.tryParse(_captchaController.text.trim());
    if (captchaAnswer != _num1 + _num2) {
      _generateCaptcha();
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Text(
              'Verifikasi Gagal',
              style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
            ),
            content: Text(
              'penjumlahan salah',
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
      return;
    }

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    try {
      await authProvider.login(
        _loginIdController.text.trim(),
        _passwordController.text,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Selamat datang kembali, ${authProvider.currentUser?.namaLengkap}!'),
            backgroundColor: Colors.green[800],
          ),
        );
        Navigator.pop(context); // Menutup halaman login agar AuthWrapper langsung memuat HomeScreen
      }
    } catch (e) {
      _generateCaptcha();
      if (mounted) {
        String errorMsg = e.toString().replaceAll('Exception: ', '');
        if (errorMsg.toLowerCase().contains('email tidak ditemukan') || errorMsg.toLowerCase().contains('tidak ditemukan di sistem')) {
          errorMsg = 'gmail salah';
        } else if (errorMsg.toLowerCase().contains('password yang anda masukkan salah') || errorMsg.toLowerCase().contains('password salah') || errorMsg.toLowerCase().contains('salah')) {
          errorMsg = 'password salah';
        }

        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Text(
              'Login Gagal',
              style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
            ),
            content: Text(
              errorMsg,
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
  }



  // Menampilkan Sheet Dialog untuk pendaftaran akun Petani Baru
  // Menampilkan Sheet Dialog untuk pendaftaran akun Petani Baru (Buat Akun Baru)
  void _showRegisterDialog() {
    final registerFormKey = GlobalKey<FormState>();
    final nikController = TextEditingController();
    final regEmailController = TextEditingController();
    final regPasswordController = TextEditingController();
    final regConfirmPasswordController = TextEditingController();
    String? selectedJenisKelompok;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (BuildContext context, StateSetter setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
                ),
                padding: const EdgeInsets.all(24),
                child: SingleChildScrollView(
                  child: Form(
                    key: registerFormKey,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Center(
                          child: Container(
                            width: 50,
                            height: 5,
                            decoration: BoxDecoration(
                              color: Colors.grey[300],
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          'Buat Akun Baru',
                          style: GoogleFonts.outfit(
                            fontSize: 22,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Kelola data pertanian secara lebih aman, rapi, dan terintegrasi.',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: const Color(0xFF64748B),
                          ),
                        ),
                        const SizedBox(height: 24),
                        
                        // NIK
                        Text(
                          'Nomor Induk Kependudukan (NIK)',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF334155),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: nikController,
                          keyboardType: TextInputType.number,
                          maxLength: 16,
                          style: GoogleFonts.inter(fontSize: 14),
                          decoration: _inputDecoration(
                            hint: 'Masukkan 16 digit NIK Anda',
                            icon: Icons.badge_outlined,
                          ).copyWith(counterText: ''),
                          validator: (v) => v == null || v.trim().length != 16 ? 'NIK wajib 16 digit' : null,
                        ),
                        const SizedBox(height: 16),

                        // Alamat Email
                        Text(
                          'Gmail',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF334155),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: regEmailController,
                          style: GoogleFonts.inter(fontSize: 14),
                          keyboardType: TextInputType.emailAddress,
                          decoration: _inputDecoration(
                            hint: 'nama@gmail.com',
                            icon: Icons.mail_outline_rounded,
                          ),
                          validator: (v) {
                            if (v == null || v.trim().isEmpty) return 'Email wajib diisi';
                            if (!v.contains('@')) return 'Format email tidak valid';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Terdaftar Sebagai
                        Text(
                          'Terdaftar Sebagai',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF334155),
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue: selectedJenisKelompok,
                          dropdownColor: Colors.white,
                          icon: const Icon(Icons.keyboard_arrow_down_rounded, color: Color(0xFF94A3B8)),
                          style: GoogleFonts.inter(fontSize: 14, color: const Color(0xFF0F172A)),
                          items: [
                            DropdownMenuItem(
                              value: 'kelompok_tani',
                              child: Text('Kelompok Tani', style: GoogleFonts.inter(fontSize: 14, color: const Color(0xFF0F172A))),
                            ),
                            DropdownMenuItem(
                              value: 'brigade_pangan',
                              child: Text('Brigade Pangan', style: GoogleFonts.inter(fontSize: 14, color: const Color(0xFF0F172A))),
                            ),
                          ],
                          onChanged: (val) {
                            setModalState(() {
                              selectedJenisKelompok = val;
                            });
                          },
                          decoration: _inputDecoration(
                            hint: 'Pilih sumber data petani',
                            icon: Icons.group_outlined,
                          ),
                          validator: (v) => v == null || v.isEmpty ? 'Sumber data wajib dipilih' : null,
                        ),
                        const SizedBox(height: 16),

                        // Kata Sandi
                        Text(
                          'Kata Sandi',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF334155),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: regPasswordController,
                          style: GoogleFonts.inter(fontSize: 14),
                          obscureText: true,
                          decoration: _inputDecoration(
                            hint: 'Kata sandi minimal 6 karakter',
                            icon: Icons.lock_outline_rounded,
                          ),
                          validator: (v) => v == null || v.length < 6 ? 'Sandi minimal 6 karakter' : null,
                        ),
                        const SizedBox(height: 16),

                        // Konfirmasi Kata Sandi
                        Text(
                          'Konfirmasi Kata Sandi',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF334155),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: regConfirmPasswordController,
                          style: GoogleFonts.inter(fontSize: 14),
                          obscureText: true,
                          decoration: _inputDecoration(
                            hint: 'Ulangi kata sandi di atas',
                            icon: Icons.lock_clock_outlined,
                          ),
                          validator: (v) => v != regPasswordController.text ? 'Konfirmasi kata sandi tidak cocok' : null,
                        ),
                        const SizedBox(height: 28),

                        // Submit Register Button with Gradient
                        Consumer<AuthProvider>(
                          builder: (context, auth, _) {
                            return Container(
                              height: 52,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(16),
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
                                  onTap: auth.isLoading
                                      ? null
                                      : () async {
                                          if (!registerFormKey.currentState!.validate()) return;
                                          try {
                                            await auth.register(
                                              nik: nikController.text.trim(),
                                              email: regEmailController.text.trim(),
                                              password: regPasswordController.text,
                                              passwordConfirmation: regConfirmPasswordController.text,
                                              jenisKelompok: selectedJenisKelompok!,
                                            );
                                            if (context.mounted) {
                                              Navigator.pop(context);
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                SnackBar(
                                                  content: const Text('Registrasi berhasil! Silakan melakukan login.'),
                                                  backgroundColor: Colors.green[800],
                                                ),
                                              );
                                            }
                                          } catch (e) {
                                            if (context.mounted) {
                                              showDialog(
                                                context: context,
                                                builder: (ctx) => AlertDialog(
                                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                                  title: Text(
                                                    'Registrasi Gagal',
                                                    style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
                                                  ),
                                                  content: Text(
                                                    e.toString().replaceAll('Exception: ', ''),
                                                    style: GoogleFonts.inter(),
                                                  ),
                                                  actions: [
                                                    TextButton(
                                                      onPressed: () => Navigator.pop(ctx),
                                                      child: Text('OK', style: TextStyle(color: Colors.green[800])),
                                                    ),
                                                  ],
                                                ),
                                              );
                                            }
                                          }
                                        },
                                  borderRadius: BorderRadius.circular(16),
                                  child: Center(
                                    child: auth.isLoading
                                        ? const SizedBox(
                                            height: 20,
                                            width: 20,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                              color: Colors.white,
                                            ),
                                          )
                                        : Text(
                                            'Daftar Sekarang',
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
                            );
                          },
                        ),
                        const SizedBox(height: 16),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  InputDecoration _inputDecoration({
    required String hint,
    required IconData icon,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: GoogleFonts.inter(
        color: const Color(0xFFCBD5E1),
        fontSize: 14,
      ),
      prefixIcon: Icon(icon, color: const Color(0xFF94A3B8)),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0), width: 1.5),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Color(0xFF66A80F), width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent, width: 1.5),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent, width: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = context.watch<AuthProvider>().isLoading;

    return Scaffold(
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
            child: Center(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const SizedBox(height: 16),
                    // Logo, title, subtitle (centered like web, clickable to go back)
                    Column(
                      children: [
                        GestureDetector(
                          onTap: () {
                            Navigator.pop(context);
                          },
                          child: MouseRegion(
                            cursor: SystemMouseCursors.click,
                            child: Container(
                              width: 64,
                              height: 64,
                              alignment: Alignment.center,
                              child: Image.asset(
                                'assets/images/logo.png',
                                fit: BoxFit.contain,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Sistem Informasi Pemetaan Padi Dinas Pertanian Batola',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.poppins(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF497D00),
                            letterSpacing: 0.5,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Masuk Akun',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.outfit(
                            fontSize: 26,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Kelola data pertanian secara lebih aman, rapi, dan terintegrasi.',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            color: const Color(0xFF64748B),
                            height: 1.4,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 32),
                    
                    // Auth Card
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.95),
                        borderRadius: BorderRadius.circular(28),
                        border: Border.all(color: Colors.white, width: 1.5),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF35530E).withValues(alpha: 0.12),
                            offset: const Offset(0, 20),
                            blurRadius: 50,
                          ),
                        ],
                      ),
                      padding: const EdgeInsets.all(24.0),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            // Email Label
                            Text(
                              'NIK / NIP',
                              style: GoogleFonts.inter(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: const Color(0xFF334155),
                              ),
                            ),
                            const SizedBox(height: 8),
                            // Login ID Input
                            TextFormField(
                              controller: _loginIdController,
                              keyboardType: TextInputType.number,
                              style: GoogleFonts.inter(fontSize: 14),
                              decoration: _inputDecoration(
                                hint: 'Masukkan 16 digit NIK atau 18 digit NIP',
                                icon: Icons.badge_outlined,
                              ),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return 'NIK / NIP tidak boleh kosong';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 20),
                            // Password Label Row
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Password',
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: const Color(0xFF334155),
                                  ),
                                ),
                                GestureDetector(
                                  onTap: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => const ForgotPasswordScreen(),
                                      ),
                                    );
                                  },
                                  child: Text(
                                    'Lupa password?',
                                    style: GoogleFonts.inter(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: const Color(0xFF497D00),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            // Password Input
                            TextFormField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              style: GoogleFonts.inter(fontSize: 14),
                              decoration: _inputDecoration(
                                hint: 'Masukkan password',
                                icon: Icons.lock_outline_rounded,
                                suffixIcon: IconButton(
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_outlined
                                        : Icons.visibility_off_outlined,
                                    color: const Color(0xFF94A3B8),
                                    size: 20,
                                  ),
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                ),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Kata sandi tidak boleh kosong';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 20),
                            // Verifikasi Keamanan (Math Captcha)
                            Text(
                              'Verifikasi Keamanan',
                              style: GoogleFonts.inter(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: const Color(0xFF334155),
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              decoration: BoxDecoration(
                                color: const Color(0xFFF7FCED),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: const Color(0xFFDFECCC), width: 1.5),
                              ),
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        width: 44,
                                        height: 44,
                                        decoration: BoxDecoration(
                                          borderRadius: BorderRadius.circular(12),
                                          gradient: const LinearGradient(
                                            colors: [Color(0xFF5EA500), Color(0xFF3E7D00)],
                                            begin: Alignment.topLeft,
                                            end: Alignment.bottomRight,
                                          ),
                                        ),
                                        alignment: Alignment.center,
                                        child: const Icon(
                                          Icons.add_rounded,
                                          color: Colors.white,
                                          size: 20,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'Jawab pertanyaan berikut:',
                                              style: GoogleFonts.inter(
                                                fontSize: 12,
                                                fontWeight: FontWeight.w500,
                                                color: const Color(0xFF64748B),
                                              ),
                                            ),
                                            const SizedBox(height: 2),
                                            Text(
                                              '$_num1 + $_num2 = ?',
                                              style: GoogleFonts.outfit(
                                                fontSize: 20,
                                                fontWeight: FontWeight.w800,
                                                color: const Color(0xFF14280B),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 14),
                                  TextFormField(
                                    controller: _captchaController,
                                    keyboardType: TextInputType.number,
                                    textAlign: TextAlign.center,
                                    style: GoogleFonts.inter(
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      color: const Color(0xFF0F172A),
                                    ),
                                    decoration: InputDecoration(
                                      hintText: 'Masukkan jawaban Anda',
                                      hintStyle: GoogleFonts.inter(
                                        color: const Color(0xFFCBD5E1),
                                        fontSize: 14,
                                      ),
                                      filled: true,
                                      fillColor: Colors.white,
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                      errorStyle: const TextStyle(height: 0, fontSize: 0),
                                      enabledBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(color: Color(0xFFE2E8F0), width: 1.5),
                                      ),
                                      focusedBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(color: Color(0xFF66A80F), width: 2),
                                      ),
                                      errorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(color: Colors.redAccent, width: 1.5),
                                      ),
                                      focusedErrorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                        borderSide: const BorderSide(color: Colors.redAccent, width: 2),
                                      ),
                                    ),
                                    validator: (value) {
                                      if (value == null || value.trim().isEmpty) {
                                        return 'Wajib diisi';
                                      }
                                      if (int.tryParse(value.trim()) == null) {
                                        return 'Angka';
                                      }
                                      return null;
                                    },
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 24),
                            // Submit button with Gradient and Shadow
                            Container(
                              height: 52,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(16),
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
                                  onTap: isLoading ? null : _submit,
                                  borderRadius: BorderRadius.circular(16),
                                  child: Center(
                                    child: isLoading
                                        ? const SizedBox(
                                            height: 20,
                                            width: 20,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2.5,
                                              color: Colors.white,
                                            ),
                                          )
                                        : Text(
                                            'Masuk',
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
                            const SizedBox(height: 24),
                            // Register link
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  'Belum punya akun? ',
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    color: const Color(0xFF64748B),
                                  ),
                                ),
                                  GestureDetector(
                                   onTap: isLoading ? null : _showRegisterDialog,
                                   child: Text(
                                     'Daftar sekarang',
                                     style: GoogleFonts.inter(
                                       fontSize: 14,
                                       fontWeight: FontWeight.bold,
                                       color: const Color(0xFF497D00),
                                     ),
                                   ),
                                 ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),
                    // Footer
                    Center(
                      child: Text(
                        '2026 - SiPetani',
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
