import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import '../../providers/farming_provider.dart';

class PetugasSpasialScreen extends StatefulWidget {
  const PetugasSpasialScreen({super.key});

  @override
  State<PetugasSpasialScreen> createState() => _PetugasSpasialScreenState();
}

class _PetugasSpasialScreenState extends State<PetugasSpasialScreen> {
  final MapController _mapController = MapController();
  final _formKey = GlobalKey<FormState>();
  final _namaController = TextEditingController();
  final _alamatController = TextEditingController();
  final _luasController = TextEditingController();
  final _latController = TextEditingController();
  final _lngController = TextEditingController();

  String _source = 'baru';
  Map<String, dynamic>? _selectedLahan;
  LatLng? _centerPoint;
  List<LatLng> _polygonPoints = [];
  bool _drawPolygonMode = false;
  int? _kecamatanId;
  int? _kelurahanId;
  int? _tipeLahanId;
  String _tahunLbs = '2024';

  static const LatLng _batolaCenter = LatLng(-3.120, 114.600);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchPetugasSpasialData();
    });
  }

  @override
  void dispose() {
    _namaController.dispose();
    _alamatController.dispose();
    _luasController.dispose();
    _latController.dispose();
    _lngController.dispose();
    super.dispose();
  }

  Future<void> _refresh() =>
      context.read<FarmingProvider>().fetchPetugasSpasialData();

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();
    final rows = provider.petugasSpasialRows
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final belum = rows
        .where((row) => _statusSpasial(row) == 'BELUM_DIPETAKAN')
        .toList();
    final sudah = rows
        .where((row) => _statusSpasial(row) == 'SUDAH_DIPETAKAN')
        .toList();
    final list = _source == 'baru' ? belum : sudah;

    return Scaffold(
      backgroundColor: const Color(0xFFF4F9F4),
      appBar: AppBar(
        title: Text(
          'Manajemen Data Spasial',
          style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF047857),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: _refresh,
            color: const Color(0xFF047857),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _buildSummary(provider.petugasSpasialSummary),
                const SizedBox(height: 14),
                _buildSourceSwitch(belum.length, sudah.length),
                const SizedBox(height: 12),
                if (provider.isPetugasSpasialLoading && rows.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 80),
                    child: Center(
                      child: CircularProgressIndicator(
                        color: Color(0xFF047857),
                      ),
                    ),
                  )
                else ...[
                  _buildLahanPicker(list),
                  const SizedBox(height: 14),
                  _buildMap(provider, rows),
                  const SizedBox(height: 12),
                  _buildMapTools(),
                  const SizedBox(height: 14),
                  _buildSpatialForm(provider.petugasSpasialReferensi),
                ],
                if (provider.errorMessage != null) ...[
                  const SizedBox(height: 14),
                  _buildErrorBox(provider.errorMessage!),
                ],
                const SizedBox(height: 24),
              ],
            ),
          ),
          if (provider.isPetugasActionLoading)
            const Positioned(
              left: 0,
              right: 0,
              top: 0,
              child: LinearProgressIndicator(
                color: Color(0xFF047857),
                minHeight: 3,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildSummary(Map<String, dynamic> summary) {
    final total = summary['total'] ?? 0;
    final belum = summary['belum_dipetakan'] ?? 0;
    final sudah = summary['sudah_dipetakan'] ?? 0;
    final lengkap = summary['persentase_lengkap'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Pemetaan Lahan Sawah',
            style: GoogleFonts.poppins(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: const Color(0xFF14280B),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Pilih lahan, atur titik tengah, gambar batas polygon minimal 3 titik, lalu simpan data spasial.',
            style: GoogleFonts.poppins(
              fontSize: 12,
              color: const Color(0xFF64748B),
              height: 1.45,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              _buildSummaryPill('Total', '$total', const Color(0xFF14280B)),
              const SizedBox(width: 8),
              _buildSummaryPill('Baru', '$belum', const Color(0xFFD97706)),
              const SizedBox(width: 8),
              _buildSummaryPill('Dipetakan', '$sudah', const Color(0xFF047857)),
              const SizedBox(width: 8),
              _buildSummaryPill(
                'Lengkap',
                '${_formatNumber(lengkap)}%',
                const Color(0xFF0F766E),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryPill(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label.toUpperCase(),
              style: GoogleFonts.poppins(
                fontSize: 8,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
            const SizedBox(height: 3),
            Text(
              value,
              style: GoogleFonts.poppins(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: color,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSourceSwitch(int belum, int sudah) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          _buildSourceButton('baru', 'Belum Dipetakan', belum),
          _buildSourceButton('lama', 'Sudah Dipetakan', sudah),
        ],
      ),
    );
  }

  Widget _buildSourceButton(String value, String label, int count) {
    final selected = _source == value;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => setState(() => _source = value),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF203C10) : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '$label ($count)',
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: selected ? Colors.white : const Color(0xFF334155),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLahanPicker(List<Map<String, dynamic>> rows) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Pilih Lahan',
                        style: GoogleFonts.poppins(
                          fontSize: 17,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF14280B),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _source == 'baru'
                            ? 'Lahan disetujui yang belum punya polygon.'
                            : 'Data yang sudah memiliki titik atau polygon.',
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: const Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (rows.isEmpty)
            Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'Tidak ada data pada kategori ini.',
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: const Color(0xFF64748B),
                ),
              ),
            )
          else
            ...rows.map((row) {
              final selected =
                  _selectedLahan != null &&
                  _selectedLahan!['id'].toString() == row['id'].toString();
              return InkWell(
                onTap: () => _selectLahan(row),
                child: Container(
                  color: selected
                      ? const Color(0xFFEDF8DC)
                      : Colors.transparent,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 13,
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 34,
                        height: 34,
                        decoration: BoxDecoration(
                          color: selected
                              ? const Color(0xFF047857)
                              : const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          Icons.landscape_rounded,
                          size: 18,
                          color: selected
                              ? Colors.white
                              : const Color(0xFF64748B),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _text(row['nama_lahan'], 'Lahan Sawah'),
                              style: GoogleFonts.poppins(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF14280B),
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${_text(row['nama_kelurahan'], '-')} / ${_text(row['nama_kecamatan'], '-')} - ${_formatNumber(row['luas_lahan_hektar'])} Ha',
                              style: GoogleFonts.poppins(
                                fontSize: 11,
                                color: const Color(0xFF64748B),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.chevron_right_rounded,
                        color: Color(0xFF94A3B8),
                      ),
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _buildMap(
    FarmingProvider provider,
    List<Map<String, dynamic>> allRows,
  ) {
    final kabupatenPolygons = _parsePolygons(
      provider.kabupatenBoundary,
      borderColor: const Color(0xFF203C10),
      fillColor: Colors.transparent,
      borderWidth: 2,
    );
    final kecamatanPolygons = _parsePolygons(
      provider.kecamatanBoundaries,
      borderColor: const Color(0xFF334155).withValues(alpha: 0.6),
      fillColor: const Color(0xFF94A3B8).withValues(alpha: 0.08),
      borderWidth: 0.9,
      usePropertyColor: true,
    );

    final lahanPolygons = <Polygon>[];
    final lahanMarkers = <Marker>[];
    for (final row in allRows) {
      final geometry = _decodeGeometry(
        row['polygon_geojson'] ?? row['geojson'],
      );
      final isSelected =
          _selectedLahan != null &&
          _selectedLahan!['id'].toString() == row['id'].toString();
      if (geometry != null) {
        lahanPolygons.addAll(
          _parsePolygons(
            geometry,
            borderColor: isSelected
                ? const Color(0xFF0F766E)
                : const Color(0xFF047857),
            fillColor:
                (isSelected ? const Color(0xFF0F766E) : const Color(0xFF047857))
                    .withValues(alpha: isSelected ? 0.24 : 0.14),
            borderWidth: isSelected ? 2.2 : 1.2,
          ),
        );
      }
      final lat = double.tryParse(row['latitude']?.toString() ?? '');
      final lng = double.tryParse(row['longitude']?.toString() ?? '');
      if (lat != null && lng != null) {
        lahanMarkers.add(
          Marker(
            point: LatLng(lat, lng),
            width: 34,
            height: 34,
            child: GestureDetector(
              onTap: () => _selectLahan(row),
              child: Container(
                decoration: BoxDecoration(
                  color: isSelected
                      ? const Color(0xFF0F766E)
                      : const Color(0xFF22C55E),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 3),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.15),
                      blurRadius: 10,
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }
    }

    final drawingLayers = <Widget>[];
    if (_polygonPoints.length >= 3) {
      drawingLayers.add(
        PolygonLayer(
          polygons: [
            Polygon(
              points: _polygonPoints,
              color: const Color(0xFF0F766E).withValues(alpha: 0.22),
              borderColor: const Color(0xFF0F766E),
              borderStrokeWidth: 2.4,
              isFilled: true,
            ),
          ],
        ),
      );
    } else if (_polygonPoints.length >= 2) {
      drawingLayers.add(
        PolylineLayer(
          polylines: [
            Polyline(
              points: _polygonPoints,
              color: const Color(0xFF0F766E),
              strokeWidth: 2.4,
            ),
          ],
        ),
      );
    }

    final drawingMarkers = <Marker>[
      if (_centerPoint != null)
        Marker(
          point: _centerPoint!,
          width: 42,
          height: 42,
          child: const Icon(
            Icons.location_on_rounded,
            color: Color(0xFFDC2626),
            size: 40,
          ),
        ),
      ..._polygonPoints.asMap().entries.map(
        (entry) => Marker(
          point: entry.value,
          width: 28,
          height: 28,
          child: Container(
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFF0F766E),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
            ),
            child: Text(
              '${entry.key + 1}',
              style: GoogleFonts.poppins(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ),
        ),
      ),
    ];

    return Container(
      height: 390,
      decoration: BoxDecoration(
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      clipBehavior: Clip.antiAlias,
      child: FlutterMap(
        mapController: _mapController,
        options: MapOptions(
          initialCenter: _centerPoint ?? _batolaCenter,
          initialZoom: _centerPoint == null ? 10 : 15,
          minZoom: 9,
          maxZoom: 18,
          onTap: (_, point) {
            if (_selectedLahan == null) {
              _snack('Pilih lahan terlebih dahulu.');
              return;
            }
            setState(() {
              if (_drawPolygonMode) {
                _polygonPoints.add(point);
              } else {
                _centerPoint = point;
                _latController.text = point.latitude.toStringAsFixed(7);
                _lngController.text = point.longitude.toStringAsFixed(7);
              }
            });
          },
        ),
        children: [
          TileLayer(
            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            userAgentPackageName: 'com.sigpala.batola.mobile_app',
          ),
          PolygonLayer(polygons: kecamatanPolygons),
          PolygonLayer(polygons: kabupatenPolygons),
          PolygonLayer(polygons: lahanPolygons),
          ...drawingLayers,
          MarkerLayer(markers: lahanMarkers),
          MarkerLayer(markers: drawingMarkers),
          RichAttributionWidget(
            attributions: [
              TextSourceAttribution(
                'OpenStreetMap',
                textStyle: GoogleFonts.poppins(fontSize: 10),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMapTools() {
    final enabled = _selectedLahan != null;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            enabled
                ? (_drawPolygonMode
                      ? 'Mode gambar batas aktif. Tap peta untuk menambah titik.'
                      : 'Mode titik tengah aktif. Tap peta untuk memindahkan titik.')
                : 'Pilih lahan dulu untuk mengaktifkan peta kerja.',
            style: GoogleFonts.poppins(
              fontSize: 12,
              color: const Color(0xFF475569),
              height: 1.4,
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _toolButton(
                'Titik Tengah',
                Icons.my_location_rounded,
                enabled && !_drawPolygonMode,
                enabled ? () => setState(() => _drawPolygonMode = false) : null,
              ),
              _toolButton(
                'Gambar Batas',
                Icons.polyline_rounded,
                enabled && _drawPolygonMode,
                enabled ? () => setState(() => _drawPolygonMode = true) : null,
              ),
              _toolButton(
                'Urungkan Titik',
                Icons.undo_rounded,
                false,
                enabled && _polygonPoints.isNotEmpty
                    ? () => setState(() => _polygonPoints.removeLast())
                    : null,
              ),
              _toolButton(
                'Kosongkan Batas',
                Icons.delete_outline_rounded,
                false,
                enabled && _polygonPoints.isNotEmpty
                    ? () => setState(() => _polygonPoints.clear())
                    : null,
                danger: true,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _toolButton(
    String label,
    IconData icon,
    bool active,
    VoidCallback? onPressed, {
    bool danger = false,
  }) {
    final color = danger ? const Color(0xFFDC2626) : const Color(0xFF047857);
    return OutlinedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 16),
      label: Text(label),
      style: OutlinedButton.styleFrom(
        foregroundColor: active ? Colors.white : color,
        backgroundColor: active ? const Color(0xFF203C10) : Colors.white,
        side: BorderSide(
          color: active
              ? const Color(0xFF203C10)
              : color.withValues(alpha: 0.35),
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        textStyle: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.bold),
      ),
    );
  }

  Widget _buildSpatialForm(Map<String, dynamic> referensi) {
    final locked = _selectedLahan == null;
    final kecamatan = (referensi['kecamatan'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final kelurahanAll = (referensi['kelurahan'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final tipeLahan = (referensi['tipe_lahan'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final kelurahan = _kecamatanId == null
        ? kelurahanAll
        : kelurahanAll
              .where(
                (item) =>
                    item['kecamatan_id']?.toString() == _kecamatanId.toString(),
              )
              .toList();

    final kecValue = _valueIfExists(_kecamatanId, kecamatan);
    final kelValue = _valueIfExists(_kelurahanId, kelurahan);
    final tipeValue = _valueIfExists(_tipeLahanId, tipeLahan);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFE2E8F0)),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Form(
        key: _formKey,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Informasi Titik dan Batas Area',
                          style: GoogleFonts.poppins(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: const Color(0xFF14280B),
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          locked
                              ? 'Pilih lahan dari daftar untuk membuka formulir.'
                              : _text(
                                  _selectedLahan?['nama_lahan'],
                                  'Lahan terpilih',
                                ),
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: const Color(0xFF64748B),
                          ),
                        ),
                      ],
                    ),
                  ),
                  _buildStatusChip(
                    locked ? 'TERKUNCI' : _statusSpasial(_selectedLahan!),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _textField(
                _namaController,
                'Nama Lahan',
                enabled: !locked,
                required: true,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _textField(
                      _luasController,
                      'Luas (Ha)',
                      enabled: !locked,
                      required: true,
                      keyboardType: TextInputType.number,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      key: ValueKey(
                        'tahun-${_selectedLahan?['id']}-$_tahunLbs',
                      ),
                      initialValue: _tahunLbs,
                      decoration: _inputDecoration('Tahun Basis'),
                      items: const [
                        DropdownMenuItem(value: '2024', child: Text('2024')),
                        DropdownMenuItem(value: '2017', child: Text('2017')),
                      ],
                      onChanged: locked
                          ? null
                          : (value) =>
                                setState(() => _tahunLbs = value ?? '2024'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                key: ValueKey('kecamatan-${_selectedLahan?['id']}-$kecValue'),
                initialValue: kecValue,
                decoration: _inputDecoration('Kecamatan'),
                items: kecamatan
                    .where(
                      (item) => int.tryParse(item['id'].toString()) != null,
                    )
                    .map(
                      (item) => DropdownMenuItem<int>(
                        value: int.parse(item['id'].toString()),
                        child: Text(
                          _text(item['nama_kecamatan'] ?? item['nama'], '-'),
                        ),
                      ),
                    )
                    .toList(),
                validator: (value) =>
                    value == null ? 'Kecamatan wajib dipilih.' : null,
                onChanged: locked
                    ? null
                    : (value) => setState(() {
                        _kecamatanId = value;
                        _kelurahanId = null;
                      }),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                key: ValueKey('kelurahan-${_selectedLahan?['id']}-$kelValue'),
                initialValue: kelValue,
                decoration: _inputDecoration('Kelurahan'),
                items: kelurahan
                    .where(
                      (item) => int.tryParse(item['id'].toString()) != null,
                    )
                    .map(
                      (item) => DropdownMenuItem<int>(
                        value: int.parse(item['id'].toString()),
                        child: Text(
                          _text(item['nama_kelurahan'] ?? item['nama'], '-'),
                        ),
                      ),
                    )
                    .toList(),
                onChanged: locked
                    ? null
                    : (value) => setState(() => _kelurahanId = value),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                key: ValueKey('tipe-${_selectedLahan?['id']}-$tipeValue'),
                initialValue: tipeValue,
                decoration: _inputDecoration('Tipe Lahan'),
                items: tipeLahan
                    .where(
                      (item) => int.tryParse(item['id'].toString()) != null,
                    )
                    .map(
                      (item) => DropdownMenuItem<int>(
                        value: int.parse(item['id'].toString()),
                        child: Text(
                          _text(item['nama_tipe'] ?? item['nama'], '-'),
                        ),
                      ),
                    )
                    .toList(),
                onChanged: locked
                    ? null
                    : (value) => setState(() => _tipeLahanId = value),
              ),
              const SizedBox(height: 12),
              _textField(
                _alamatController,
                'Alamat Detail',
                enabled: !locked,
                maxLines: 2,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _textField(
                      _latController,
                      'Latitude',
                      enabled: !locked,
                      required: true,
                      keyboardType: TextInputType.number,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _textField(
                      _lngController,
                      'Longitude',
                      enabled: !locked,
                      required: true,
                      keyboardType: TextInputType.number,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.polyline_rounded,
                      color: Color(0xFF047857),
                      size: 18,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _polygonPoints.length >= 3
                            ? 'Polygon siap disimpan dengan ${_polygonPoints.length} titik.'
                            : 'Minimal 3 titik batas area diperlukan. Saat ini ${_polygonPoints.length} titik.',
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: const Color(0xFF475569),
                          height: 1.35,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: locked ? null : _saveSpatial,
                icon: const Icon(Icons.save_rounded),
                label: Text(
                  'Simpan Data Spasial',
                  style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF047857),
                  foregroundColor: Colors.white,
                  disabledBackgroundColor: const Color(0xFFE2E8F0),
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
              if (!locked &&
                  _statusSpasial(_selectedLahan!) == 'SUDAH_DIPETAKAN') ...[
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: _deleteSpatial,
                  icon: const Icon(Icons.delete_outline_rounded),
                  label: Text(
                    'Kosongkan Batas Area',
                    style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFDC2626),
                    side: const BorderSide(color: Color(0xFFFECACA)),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _textField(
    TextEditingController controller,
    String label, {
    bool enabled = true,
    bool required = false,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      enabled: enabled,
      maxLines: maxLines,
      keyboardType: keyboardType,
      decoration: _inputDecoration(label),
      style: GoogleFonts.poppins(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: const Color(0xFF14280B),
      ),
      validator: (value) {
        if (required && (value ?? '').trim().isEmpty) {
          return '$label wajib diisi.';
        }
        return null;
      },
    );
  }

  InputDecoration _inputDecoration(String label) {
    return InputDecoration(
      labelText: label,
      labelStyle: GoogleFonts.poppins(
        fontSize: 12,
        color: const Color(0xFF64748B),
      ),
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF047857), width: 1.4),
      ),
      disabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
    );
  }

  Widget _buildStatusChip(String text) {
    final mapped = text == 'SUDAH_DIPETAKAN'
        ? ('SUDAH DIPETAKAN', const Color(0xFF16A34A), const Color(0xFFDCFCE7))
        : text == 'BELUM_DIPETAKAN'
        ? ('BELUM DIPETAKAN', const Color(0xFFD97706), const Color(0xFFFFFBEB))
        : ('TERKUNCI', const Color(0xFF64748B), const Color(0xFFF1F5F9));
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: mapped.$3,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        mapped.$1,
        style: GoogleFonts.poppins(
          fontSize: 9,
          fontWeight: FontWeight.w800,
          color: mapped.$2,
        ),
      ),
    );
  }

  void _selectLahan(Map<String, dynamic> row) {
    final lat = double.tryParse(row['latitude']?.toString() ?? '');
    final lng = double.tryParse(row['longitude']?.toString() ?? '');
    final point = lat != null && lng != null ? LatLng(lat, lng) : null;
    final polygon = _pointsFromGeometry(
      row['polygon_geojson'] ?? row['geojson'],
    );

    setState(() {
      _selectedLahan = row;
      _namaController.text = _text(row['nama_lahan'], '');
      _alamatController.text = _text(row['alamat_detail'], '');
      _luasController.text = _formatNumber(
        row['luas_lahan_hektar'],
      ).replaceAll(',', '.');
      _centerPoint = point;
      _latController.text = point?.latitude.toStringAsFixed(7) ?? '';
      _lngController.text = point?.longitude.toStringAsFixed(7) ?? '';
      _polygonPoints = polygon;
      _drawPolygonMode = polygon.isEmpty;
      _kecamatanId = int.tryParse(row['kecamatan_id']?.toString() ?? '');
      _kelurahanId = int.tryParse(row['kelurahan_id']?.toString() ?? '');
      _tipeLahanId = int.tryParse(row['tipe_lahan_id']?.toString() ?? '');
      _tahunLbs = row['tahun_lbs']?.toString() == '2017' ? '2017' : '2024';
    });

    final target =
        point ?? (polygon.isNotEmpty ? polygon.first : _batolaCenter);
    Future.microtask(() {
      try {
        _mapController.move(target, point == null && polygon.isEmpty ? 10 : 15);
      } catch (_) {}
    });
  }

  Future<void> _saveSpatial() async {
    if (_selectedLahan == null) return;
    if (_formKey.currentState?.validate() != true) return;

    final lat = double.tryParse(_latController.text.trim());
    final lng = double.tryParse(_lngController.text.trim());
    if (lat == null || lng == null) {
      _snack('Latitude dan longitude wajib valid.', error: true);
      return;
    }
    if (_polygonPoints.length < 3) {
      _snack('Polygon minimal harus memiliki 3 titik.', error: true);
      return;
    }

    final id = int.tryParse(_selectedLahan!['id'].toString());
    if (id == null) {
      _snack('ID lahan tidak valid.', error: true);
      return;
    }

    final payload = <String, dynamic>{
      'user_id': _selectedLahan!['user_id'] ?? _selectedLahan!['pemilik_id'],
      'petani_id':
          _selectedLahan!['petani_id'] ??
          _selectedLahan!['user_id'] ??
          _selectedLahan!['pemilik_id'],
      'kecamatan_id': _kecamatanId,
      'kelurahan_id': _kelurahanId,
      'tipe_lahan_id': _tipeLahanId,
      'nama_lahan': _namaController.text.trim(),
      'tahun_lbs': _tahunLbs,
      'luas_lahan_hektar':
          double.tryParse(_luasController.text.trim().replaceAll(',', '.')) ??
          0,
      'alamat_detail': _alamatController.text.trim(),
      'latitude': lat,
      'longitude': lng,
      'polygon_geojson': _buildPolygonGeoJson(),
    };

    final success = await context.read<FarmingProvider>().savePetugasSpasial(
      id,
      payload,
    );
    if (!mounted) return;
    _snack(
      success
          ? 'Data spasial berhasil disimpan.'
          : (context.read<FarmingProvider>().errorMessage ??
                'Gagal menyimpan data.'),
      error: !success,
    );
  }

  Future<void> _deleteSpatial() async {
    if (_selectedLahan == null) return;
    final id = int.tryParse(_selectedLahan!['id'].toString());
    if (id == null) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'Kosongkan batas area?',
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.bold,
            color: const Color(0xFF991B1B),
          ),
        ),
        content: Text(
          'Polygon dan titik lahan akan dikosongkan, tetapi data administrasi lahan tetap tersimpan.',
          style: GoogleFonts.poppins(fontSize: 13, height: 1.45),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(
              'Batal',
              style: GoogleFonts.poppins(color: const Color(0xFF64748B)),
            ),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFDC2626),
              foregroundColor: Colors.white,
            ),
            child: Text(
              'Kosongkan',
              style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    final success = await context.read<FarmingProvider>().deletePetugasSpasial(
      id,
    );
    if (!mounted) return;
    if (success) {
      setState(() {
        _centerPoint = null;
        _polygonPoints.clear();
        _latController.clear();
        _lngController.clear();
      });
    }
    _snack(
      success
          ? 'Polygon lahan berhasil dikosongkan.'
          : (context.read<FarmingProvider>().errorMessage ??
                'Gagal mengosongkan polygon.'),
      error: !success,
    );
  }

  String _buildPolygonGeoJson() {
    final coords = _polygonPoints
        .map((point) => [point.longitude, point.latitude])
        .toList();
    if (coords.isNotEmpty) {
      final first = coords.first;
      final last = coords.last;
      if (first[0] != last[0] || first[1] != last[1]) {
        coords.add([first[0], first[1]]);
      }
    }
    return jsonEncode({
      'type': 'Polygon',
      'coordinates': [coords],
    });
  }

  Map<String, dynamic>? _decodeGeometry(dynamic raw) {
    if (raw == null) return null;
    try {
      final data = raw is String ? jsonDecode(raw) : raw;
      if (data is Map<String, dynamic>) {
        if (data['type'] == 'Feature') {
          return Map<String, dynamic>.from(data['geometry'] as Map);
        }
        return data;
      }
    } catch (_) {}
    return null;
  }

  List<LatLng> _pointsFromGeometry(dynamic raw) {
    final geometry = _decodeGeometry(raw);
    if (geometry == null) return [];
    final type = geometry['type']?.toString() ?? '';
    final coords = geometry['coordinates'];
    if (type == 'Polygon' && coords is List && coords.isNotEmpty) {
      return _parseRing(coords[0]);
    }
    if (type == 'MultiPolygon' &&
        coords is List &&
        coords.isNotEmpty &&
        coords[0] is List &&
        (coords[0] as List).isNotEmpty) {
      return _parseRing((coords[0] as List)[0]);
    }
    return [];
  }

  List<Polygon> _parsePolygons(
    Map<String, dynamic>? geojson, {
    required Color borderColor,
    required Color fillColor,
    required double borderWidth,
    bool usePropertyColor = false,
  }) {
    if (geojson == null) return [];
    final features = <dynamic>[];
    final type = geojson['type']?.toString();
    if (type == 'FeatureCollection') {
      features.addAll(geojson['features'] as List<dynamic>? ?? []);
    } else if (type == 'Feature') {
      features.add(geojson);
    } else if (type == 'Polygon' || type == 'MultiPolygon') {
      features.add({'type': 'Feature', 'geometry': geojson, 'properties': {}});
    }

    final polygons = <Polygon>[];
    for (final feature in features) {
      if (feature is! Map) continue;
      final geometry = feature['geometry'];
      if (geometry is! Map) continue;
      final geometryType = geometry['type']?.toString() ?? '';
      final coords = geometry['coordinates'];
      if (coords is! List) continue;

      final props = feature['properties'] is Map
          ? feature['properties'] as Map
          : {};
      var currentBorder = borderColor;
      var currentFill = fillColor;
      if (usePropertyColor &&
          (props['fill_color'] ?? props['warna_peta']) != null) {
        final parsed = _hexColor(
          (props['fill_color'] ?? props['warna_peta']).toString(),
        );
        if (parsed != null) {
          currentBorder = const Color(0xFF334155).withValues(alpha: 0.62);
          currentFill = parsed.withValues(alpha: 0.14);
        }
      }

      void addRing(dynamic ring) {
        final points = _parseRing(ring);
        if (points.isNotEmpty) {
          polygons.add(
            Polygon(
              points: points,
              color: currentFill,
              borderColor: currentBorder,
              borderStrokeWidth: borderWidth,
              isFilled: currentFill != Colors.transparent,
            ),
          );
        }
      }

      if (geometryType == 'Polygon' && coords.isNotEmpty) {
        addRing(coords[0]);
      } else if (geometryType == 'MultiPolygon') {
        for (final poly in coords) {
          if (poly is List && poly.isNotEmpty) addRing(poly[0]);
        }
      }
    }
    return polygons;
  }

  List<LatLng> _parseRing(dynamic ring) {
    final points = <LatLng>[];
    if (ring is! List) return points;
    for (final pt in ring) {
      if (pt is! List || pt.length < 2) continue;
      final a = double.tryParse(pt[0].toString());
      final b = double.tryParse(pt[1].toString());
      if (a == null || b == null) continue;
      var lat = b;
      var lng = a;
      if (a >= -10 && a <= 10 && b >= 90 && b <= 150) {
        lat = a;
        lng = b;
      }
      points.add(
        LatLng(lat.clamp(-90, 90).toDouble(), lng.clamp(-180, 180).toDouble()),
      );
    }
    if (points.length > 1 && points.first == points.last) {
      return points.sublist(0, points.length - 1);
    }
    return points;
  }

  Color? _hexColor(String value) {
    try {
      var hex = value.replaceAll('#', '');
      if (hex.length == 6) hex = 'FF$hex';
      return Color(int.parse(hex, radix: 16));
    } catch (_) {
      return null;
    }
  }

  int? _valueIfExists(int? value, List<Map<String, dynamic>> items) {
    if (value == null) return null;
    return items.any((item) => item['id']?.toString() == value.toString())
        ? value
        : null;
  }

  String _statusSpasial(Map<String, dynamic> row) {
    final status = row['status_spasial']?.toString();
    if (status != null && status.isNotEmpty) return status;
    return (row['polygon_geojson'] ?? row['geojson']) == null
        ? 'BELUM_DIPETAKAN'
        : 'SUDAH_DIPETAKAN';
  }

  String _text(dynamic value, String fallback) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? fallback : text;
  }

  String _formatNumber(dynamic value) {
    final parsed = double.tryParse(value?.toString() ?? '') ?? 0;
    final result = parsed.toStringAsFixed(
      parsed.truncateToDouble() == parsed ? 0 : 2,
    );
    return result.replaceAll('.', ',');
  }

  void _snack(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error
            ? const Color(0xFFB91C1C)
            : const Color(0xFF047857),
      ),
    );
  }

  Widget _buildErrorBox(String message) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF2F2),
        border: Border.all(color: const Color(0xFFFEE2E2)),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(
        message,
        style: GoogleFonts.poppins(
          fontSize: 12,
          color: const Color(0xFFB91C1C),
          height: 1.4,
        ),
      ),
    );
  }
}
