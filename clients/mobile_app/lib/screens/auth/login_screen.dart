import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    try {
      await authProvider.login(
        _emailController.text.trim(),
        _passwordController.text,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Selamat datang kembali, ${authProvider.currentUser?.namaLengkap}!'),
            backgroundColor: Colors.green[800],
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Text(
              'Login Gagal',
              style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
            ),
            content: Text(
              e.toString().replaceAll('Exception: ', ''),
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
  void _showRegisterDialog() {
    final registerFormKey = GlobalKey<FormState>();
    final nameController = TextEditingController();
    final regEmailController = TextEditingController();
    final regPasswordController = TextEditingController();
    final regConfirmPasswordController = TextEditingController();
    final hpController = TextEditingController();
    final alamatController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
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
                      'Pendaftaran Petani Baru',
                      style: GoogleFonts.outfit(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF1B3D2F),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Silakan isi form di bawah untuk membuat akun baru.',
                      style: GoogleFonts.inter(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                    ),
                    const SizedBox(height: 24),
                    TextFormField(
                      controller: nameController,
                      style: GoogleFonts.inter(fontSize: 14),
                      decoration: _inputDecoration('Nama Lengkap', Icons.person_outline_rounded),
                      validator: (v) => v == null || v.trim().isEmpty ? 'Nama lengkap wajib diisi' : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: regEmailController,
                      style: GoogleFonts.inter(fontSize: 14),
                      keyboardType: TextInputType.emailAddress,
                      decoration: _inputDecoration('Alamat Email', Icons.mail_outline_rounded),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return 'Email wajib diisi';
                        if (!v.contains('@')) return 'Format email tidak valid';
                        return null;
                      },
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: hpController,
                      style: GoogleFonts.inter(fontSize: 14),
                      keyboardType: TextInputType.phone,
                      decoration: _inputDecoration('No. Handphone (Opsional)', Icons.phone_outlined),
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: alamatController,
                      style: GoogleFonts.inter(fontSize: 14),
                      decoration: _inputDecoration('Alamat Tinggal (Opsional)', Icons.home_outlined),
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: regPasswordController,
                      style: GoogleFonts.inter(fontSize: 14),
                      obscureText: true,
                      decoration: _inputDecoration('Kata Sandi', Icons.lock_outline_rounded),
                      validator: (v) => v == null || v.length < 6 ? 'Sandi minimal 6 karakter' : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: regConfirmPasswordController,
                      style: GoogleFonts.inter(fontSize: 14),
                      obscureText: true,
                      decoration: _inputDecoration('Konfirmasi Kata Sandi', Icons.lock_clock_outlined),
                      validator: (v) => v != regPasswordController.text ? 'Konfirmasi kata sandi tidak cocok' : null,
                    ),
                    const SizedBox(height: 28),
                    Consumer<AuthProvider>(
                      builder: (context, auth, _) {
                        return ElevatedButton(
                          onPressed: auth.isLoading
                              ? null
                              : () async {
                                  if (!registerFormKey.currentState!.validate()) return;
                                  try {
                                    await auth.register(
                                      namaLengkap: nameController.text.trim(),
                                      email: regEmailController.text.trim(),
                                      password: regPasswordController.text,
                                      passwordConfirmation: regConfirmPasswordController.text,
                                      noHp: hpController.text.trim(),
                                      alamat: alamatController.text.trim(),
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
                                          title: Text('Registrasi Gagal', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
                                          content: Text(e.toString().replaceAll('Exception: ', ''), style: GoogleFonts.inter()),
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
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green[800],
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
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
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
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
  }

  InputDecoration _inputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: TextStyle(color: Colors.green[800]),
      prefixIcon: Icon(icon, color: Colors.green[700]),
      filled: true,
      fillColor: Colors.grey[50],
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide(color: Colors.green.shade100, width: 1.5),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide(color: Colors.green.shade700, width: 2),
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
      backgroundColor: const Color(0xFFF4F9F4),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 28.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Icon(
                    Icons.eco_rounded,
                    size: 80,
                    color: Colors.green[800],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'SITANI - SIGPALA',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.outfit(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF1B3D2F),
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Sistem Informasi Pertanian & Geografis Pala',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.inter(
                      fontSize: 14,
                      color: Colors.green[900]?.withValues(alpha: 0.7),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 40),
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    style: GoogleFonts.inter(fontSize: 15),
                    decoration: _inputDecoration('Alamat Email', Icons.mail_outline_rounded),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Email tidak boleh kosong';
                      }
                      if (!value.contains('@')) {
                        return 'Format email tidak valid';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 18),
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    style: GoogleFonts.inter(fontSize: 15),
                    decoration: InputDecoration(
                      labelText: 'Kata Sandi',
                      labelStyle: TextStyle(color: Colors.green[800]),
                      prefixIcon: Icon(Icons.lock_outline_rounded, color: Colors.green[700]),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                          color: Colors.green[700],
                        ),
                        onPressed: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                      filled: true,
                      fillColor: Colors.white,
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide(color: Colors.green.shade100, width: 1.5),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide(color: Colors.green.shade700, width: 2),
                      ),
                      errorBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: const BorderSide(color: Colors.redAccent, width: 1.5),
                      ),
                      focusedErrorBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: const BorderSide(color: Colors.redAccent, width: 2),
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Kata sandi tidak boleh kosong';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 30),
                  ElevatedButton(
                    onPressed: isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green[800],
                      foregroundColor: Colors.white,
                      elevation: 2,
                      shadowColor: Colors.green.shade200,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: isLoading
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            'Masuk',
                            style: GoogleFonts.inter(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.5,
                            ),
                          ),
                  ),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Belum punya akun? ',
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          color: Colors.grey[700],
                        ),
                      ),
                      GestureDetector(
                        onTap: isLoading ? null : _showRegisterDialog,
                        child: Text(
                          'Daftar di sini',
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: Colors.green[800],
                            decoration: TextDecoration.underline,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
