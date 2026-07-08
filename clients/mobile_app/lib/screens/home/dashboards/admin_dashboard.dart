import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../../models/user.dart';
import '../../../services/auth_service.dart';
import '../../../providers/auth_provider.dart';
import '../../../core/network/api_client.dart';
import '../../admin_komunitas_screen.dart';

class AdminDashboard extends StatefulWidget {
  final User? user;

  const AdminDashboard({super.key, required this.user});

  @override
  State<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends State<AdminDashboard> {
  bool _isLoading = true;
  int _totalUsers = 0;
  int _totalKomunitas = 0;

  @override
  void initState() {
    super.initState();
    _fetchStats();
  }

  Future<void> _fetchStats() async {
    try {
      final authService = AuthService(ApiClient());
      final usersRes = await authService.getUsers();
      final komunitasRes = await authService.getKomunitas();
      
      if (mounted) {
        setState(() {
          // get total from meta if paginated, else count data length
          _totalUsers = usersRes['meta']?['total'] ?? (usersRes['data'] as List?)?.length ?? 0;
          _totalKomunitas = komunitasRes['meta']?['total'] ?? (komunitasRes['data'] as List?)?.length ?? 0;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Status Sistem SiTani',
            style: GoogleFonts.outfit(
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: _buildStatCard('Total Pengguna', _totalUsers.toString(), Icons.people),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard('Total Komunitas', _totalKomunitas.toString(), Icons.groups),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(10),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: Colors.green, size: 32),
          const SizedBox(height: 12),
          Text(
            value,
            style: GoogleFonts.inter(
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: GoogleFonts.inter(
              fontSize: 14,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }
}
