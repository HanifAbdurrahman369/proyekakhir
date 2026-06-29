import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../providers/farming_provider.dart';

class SebaranLahanScreen extends StatefulWidget {
  const SebaranLahanScreen({super.key});

  @override
  State<SebaranLahanScreen> createState() => _SebaranLahanScreenState();
}

class _SebaranLahanScreenState extends State<SebaranLahanScreen> {
  final MapController _mapController = MapController();
  final TextEditingController _searchController = TextEditingController();

  String _searchQuery = '';
  Map<String, dynamic>? _selectedEntity;

  // Konfigurasi URL Tile Server dinamis
  final String _osmUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  final String _satelliteUrl =
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
  final String _topoUrl = 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png';

  String _selectedBaseMap = '🗺️ Peta Standar'; // 'Peta Standar', 'Citra Satelit', 'Peta Topografi'
  bool _showKabupatenLayer = true;
  bool _showKecamatanLayer = true;
  bool _showLahanSawahLayer = true;
  bool _showLayerPanel = false;

  @override
  void initState() {
    super.initState();
    _searchController.addListener(() {
      setState(() {
        _searchQuery = _searchController.text;
      });
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FarmingProvider>().fetchMapData();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  // Helper untuk membuka peta eksternal
  Future<void> _openExternalMap(double lat, double lng, String label) async {
    final uri = Uri.parse(
      'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
    );
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tidak dapat membuka aplikasi Peta.')),
        );
      }
    }
  }

  String _formatNumber(double val) {
    return val
        .toStringAsFixed(2)
        .replaceAll('.', ',')
        .replaceFirst(RegExp(r',00$'), '');
  }

  String _cleanKecamatanName(String name) {
    return name
        .toLowerCase()
        .replaceAll('kecamatan', '')
        .replaceAll('kec.', '')
        .trim();
  }

  String _cleanKelurahanName(String name) {
    return name
        .toLowerCase()
        .replaceAll('kelurahan', '')
        .replaceAll('kel.', '')
        .replaceAll('desa', '')
        .trim();
  }

  // Menghitung centroid untuk teks nama kecamatan
  LatLng _calculateCentroid(dynamic coordinates, String type) {
    double sumLat = 0.0;
    double sumLng = 0.0;
    int count = 0;

    void processRing(dynamic ring) {
      if (ring is! List) return;
      for (var pt in ring) {
        if (pt is! List || pt.length < 2) continue;
        double? val1;
        double? val2;

        if (pt[0] is num) {
          val1 = (pt[0] as num).toDouble();
        } else {
          val1 = double.tryParse(pt[0].toString());
        }

        if (pt[1] is num) {
          val2 = (pt[1] as num).toDouble();
        } else {
          val2 = double.tryParse(pt[1].toString());
        }

        if (val1 != null && val2 != null) {
          // Barito Kuala: Longitude ~114, Latitude ~ -3
          double lat = val2;
          double lng = val1;

          // Jika koordinat terbalik: index 0 bernilai latitude (-10 s/d 10)
          // dan index 1 bernilai longitude (90 s/d 150)
          if (val1 >= -10 && val1 <= 10 && val2 >= 90 && val2 <= 150) {
            lat = val1;
            lng = val2;
          }

          sumLat += lat;
          sumLng += lng;
          count++;
        }
      }
    }

    if (type == 'Polygon') {
      if (coordinates is List && coordinates.isNotEmpty) {
        processRing(coordinates[0]);
      }
    } else if (type == 'MultiPolygon') {
      if (coordinates is List) {
        for (var poly in coordinates) {
          if (poly is List && poly.isNotEmpty) {
            processRing(poly[0]);
          }
        }
      }
    }

    if (count > 0) {
      return LatLng(sumLat / count, sumLng / count);
    }
    return const LatLng(-3.120, 114.600); // default center
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FarmingProvider>();

    // Parsing data poligon
    List<Polygon> kabupatenPolygons = [];
    List<Polygon> kecamatanPolygons = [];
    List<Polygon> lahanPolygons = [];
    List<Marker> lahanMarkers = [];
    List<Marker> kecamatanLabelMarkers = [];

    // Filtered data sawah
    List<dynamic> rawFeatures = provider.lahanMapFeatures?['features'] ?? [];
    List<dynamic> filteredFeatures = [];

    // Hitung statistik wilayah jika kecamatan atau kelurahan dipilih
    int totalLahan = 0;
    double totalLuas = 0.0;
    double totalPanen = 0.0;
    double avgProduktivitas = 0.0;

    if (_selectedEntity != null &&
        (_selectedEntity!['type'] == 'kecamatan' ||
            _selectedEntity!['type'] == 'kelurahan')) {
      final selType = _selectedEntity!['type'];
      final selName = _selectedEntity!['name'].toString();

      final matched = rawFeatures.where((feat) {
        if (feat is! Map) return false;
        final p = feat['properties'] as Map? ?? {};
        if (selType == 'kecamatan') {
          final k = (p['nama_kecamatan'] ?? p['kecamatan'])?.toString() ?? '';
          return _cleanKecamatanName(k) == _cleanKecamatanName(selName);
        } else {
          final k = (p['nama_kelurahan'] ?? p['kelurahan'])?.toString() ?? '';
          return _cleanKelurahanName(k) == _cleanKelurahanName(selName);
        }
      }).toList();

      totalLahan = matched.length;
      for (var feat in matched) {
        final p = feat['properties'] as Map? ?? {};
        totalLuas +=
            double.tryParse(p['luas_lahan_hektar']?.toString() ?? '0') ?? 0.0;
        totalPanen +=
            double.tryParse(p['hasil_panen_ton']?.toString() ?? '0') ?? 0.0;
        avgProduktivitas +=
            double.tryParse(p['produktivitas_ton_ha']?.toString() ?? '0') ??
            0.0;
      }
      if (totalLahan > 0) {
        avgProduktivitas = avgProduktivitas / totalLahan;
      }
    }

    // Terapkan filtering data untuk lahan sawah berdasarkan query pencarian saja (lahan, pemilik, kecamatan, kelurahan)
    for (var feat in rawFeatures) {
      if (feat is! Map) continue;
      final props = (feat['properties'] is Map)
          ? feat['properties'] as Map
          : {};
      final namaLahan = props['nama_lahan']?.toString() ?? '';
      final pemilik = props['pemilik_lahan']?.toString() ?? '';
      final kec =
          (props['nama_kecamatan'] ?? props['kecamatan'])?.toString() ?? '';
      final kel =
          (props['nama_kelurahan'] ?? props['kelurahan'])?.toString() ?? '';

      final query = _searchQuery.toLowerCase();
      final matchSearch =
          query.isEmpty ||
          namaLahan.toLowerCase().contains(query) ||
          pemilik.toLowerCase().contains(query) ||
          kec.toLowerCase().contains(query) ||
          kel.toLowerCase().contains(query);

      if (matchSearch) {
        filteredFeatures.add(feat);
      }
    }

    // Build Suggestions List if query is not empty
    List<Map<String, dynamic>> allSuggestions = [];
    if (_searchQuery.trim().length >= 2) {
      final query = _searchQuery.toLowerCase();

      // 1. Kecamatan Suggestions
      final kecFeatures =
          provider.kecamatanBoundaries?['features'] as List<dynamic>? ?? [];
      for (var feat in kecFeatures) {
        if (feat is! Map) continue;
        final props = feat['properties'] as Map? ?? {};
        final name = props['nama_kecamatan']?.toString() ?? '';
        if (name.isNotEmpty && name.toLowerCase().contains(query)) {
          final geom = feat['geometry'] as Map?;
          if (geom != null) {
            final centroid = _calculateCentroid(
              geom['coordinates'],
              geom['type']?.toString() ?? '',
            );
            allSuggestions.add({
              'type': 'kecamatan',
              'name': name,
              'location': centroid,
            });
          }
        }
      }

      // 2. Kelurahan Suggestions
      Map<String, List<LatLng>> kelurahanCoords = {};
      Map<String, String> kelurahanKecamatan = {};
      for (var feat in rawFeatures) {
        if (feat is! Map) continue;
        final props = feat['properties'] as Map? ?? {};
        final kelName = props['nama_kelurahan']?.toString() ?? '';
        final kecName = props['nama_kecamatan']?.toString() ?? '';
        final lat = double.tryParse(props['latitude']?.toString() ?? '');
        final lng = double.tryParse(props['longitude']?.toString() ?? '');

        if (kelName.isNotEmpty) {
          if (lat != null && lng != null) {
            kelurahanCoords
                .putIfAbsent(kelName, () => [])
                .add(LatLng(lat, lng));
          }
          if (kecName.isNotEmpty) {
            kelurahanKecamatan[kelName] = kecName;
          }
        }
      }
      kelurahanCoords.forEach((kelName, latLngList) {
        if (kelName.toLowerCase().contains(query)) {
          double sumLat = 0;
          double sumLng = 0;
          for (var latLng in latLngList) {
            sumLat += latLng.latitude;
            sumLng += latLng.longitude;
          }
          final centroid = LatLng(
            sumLat / latLngList.length,
            sumLng / latLngList.length,
          );
          allSuggestions.add({
            'type': 'kelurahan',
            'name': kelName,
            'kecamatan': kelurahanKecamatan[kelName] ?? '',
            'location': centroid,
          });
        }
      });

      // 3. Lahan Suggestions
      for (var feat in rawFeatures) {
        if (feat is! Map) continue;
        final props = feat['properties'] as Map? ?? {};
        final namaLahan = props['nama_lahan']?.toString() ?? '';
        final pemilik = props['pemilik_lahan']?.toString() ?? '';
        final kec = props['nama_kecamatan']?.toString() ?? '';
        final kel = props['nama_kelurahan']?.toString() ?? '';
        final lat = double.tryParse(props['latitude']?.toString() ?? '');
        final lng = double.tryParse(props['longitude']?.toString() ?? '');

        if (namaLahan.isNotEmpty &&
            (namaLahan.toLowerCase().contains(query) ||
                pemilik.toLowerCase().contains(query))) {
          if (lat != null && lng != null) {
            allSuggestions.add({
              'type': 'lahan',
              'name': namaLahan,
              'pemilik': pemilik,
              'kecamatan': kec,
              'kelurahan': kel,
              'location': LatLng(lat, lng),
              'properties': props,
            });
          }
        }
      }
    }

    if (!provider.isMapLoading) {
      // 1. Parse Kabupaten Batola Boundary (Outline warna gelap)
      kabupatenPolygons = GeoJsonParser.parsePolygons(
        provider.kabupatenBoundary,
        defaultBorderColor: const Color(0xFF203C10),
        defaultFillColor: Colors.transparent,
        borderThickness: 2.0,
      );

      // 2. Parse Kecamatan Boundaries (Warna-warni tipis)
      kecamatanPolygons = GeoJsonParser.parsePolygons(
        provider.kecamatanBoundaries,
        defaultBorderColor: Colors.black26,
        defaultFillColor: Colors.transparent,
        borderThickness: 1.0,
        usePropertyColors: true,
      );

      // 3. Render label nama kecamatan di tengah poligon masing-masing
      final kecBoundaries =
          provider.kecamatanBoundaries?['features'] as List<dynamic>? ?? [];
      for (var feat in kecBoundaries) {
        if (feat is! Map) continue;
        final geom = feat['geometry'] as Map?;
        final props = (feat['properties'] is Map)
            ? feat['properties'] as Map
            : {};
        if (geom == null) continue;

        final type = geom['type']?.toString() ?? '';
        final coords = geom['coordinates'];
        final namaKecamatan = props['nama_kecamatan']?.toString() ?? '';

        if (coords != null && namaKecamatan.isNotEmpty) {
          final centroid = _calculateCentroid(coords, type);
          kecamatanLabelMarkers.add(
            Marker(
              point: centroid,
              width: 120,
              height: 40,
              alignment: Alignment.center,
              child: GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedEntity = {
                      'type': 'kecamatan',
                      'name': namaKecamatan,
                      'location': centroid,
                      'properties': {'nama_kecamatan': namaKecamatan},
                    };
                  });
                  _mapController.move(centroid, 12.5);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.85),
                    borderRadius: BorderRadius.circular(6),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.1),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                    border: Border.all(
                      color: const Color(0xFF3E7D00).withValues(alpha: 0.3),
                      width: 0.5,
                    ),
                  ),
                  child: Text(
                    namaKecamatan,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.inter(
                      fontSize: 9,
                      fontWeight: FontWeight.w800,
                      color: const Color(0xFF203C10),
                    ),
                  ),
                ),
              ),
            ),
          );
        }
      }

      // 4. Parse Lahan Sawah Polygons & Markers
      for (var feat in filteredFeatures) {
        final props = feat['properties'] as Map<String, dynamic>? ?? {};

        // Parse Poligon Lahan Sawah (Hijau SiTani)
        final parsedLahanPolys = GeoJsonParser.parsePolygons(
          feat,
          defaultBorderColor: const Color(0xFF3E7D00),
          defaultFillColor: const Color(0xFF3E7D00).withValues(alpha: 0.35),
          borderThickness: 1.5,
        );
        lahanPolygons.addAll(parsedLahanPolys);

        // Parse koordinat marker (centroid)
        final lat = double.tryParse(props['latitude']?.toString() ?? '');
        final lng = double.tryParse(props['longitude']?.toString() ?? '');

        if (lat != null && lng != null) {
          lahanMarkers.add(
            Marker(
              point: LatLng(lat, lng),
              width: 40,
              height: 40,
              child: GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedEntity = {
                      'type': 'lahan',
                      'name': props['nama_lahan'] ?? 'Lahan Tanpa Nama',
                      'location': LatLng(lat, lng),
                      'properties': props,
                    };
                  });
                },
                child: const Icon(
                  Icons.location_on_rounded,
                  color: Color(0xFF3E7D00),
                  size: 32,
                ),
              ),
            ),
          );
        }
      }
      debugPrint('GIS DEBUG: rawFeatures count = ${rawFeatures.length}');
      debugPrint(
        'GIS DEBUG: filteredFeatures count = ${filteredFeatures.length}',
      );
      debugPrint(
        'GIS DEBUG: parsed kabupatenPolygons count = ${kabupatenPolygons.length}',
      );
      debugPrint(
        'GIS DEBUG: parsed kecamatanPolygons count = ${kecamatanPolygons.length}',
      );
      debugPrint(
        'GIS DEBUG: parsed lahanPolygons count = ${lahanPolygons.length}',
      );
      debugPrint(
        'GIS DEBUG: parsed lahanMarkers count = ${lahanMarkers.length}',
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Sebaran Lahan Spasial',
          style: GoogleFonts.outfit(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF3E7D00),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          // 1. Widget Peta Utama
          provider.isMapLoading
              ? const Center(
                  child: CircularProgressIndicator(color: Color(0xFF3E7D00)),
                )
              : FlutterMap(
                  mapController: _mapController,
                  options: MapOptions(
                    initialCenter: const LatLng(-3.120, 114.600), // Kab. Barito Kuala
                    initialZoom: 9.3,
                    maxZoom: 18.0,
                    minZoom: 8.0,
                    onTap: (position, point) {
                      // Tutup info card jika mengetuk bagian peta kosong
                      if (_selectedEntity != null) {
                        setState(() {
                          _selectedEntity = null;
                        });
                      }
                    },
                  ),
                  children: [
                    // Layer Peta Dasar Dinamis
                    TileLayer(
                      urlTemplate: _selectedBaseMap.contains('Citra Satelit')
                          ? _satelliteUrl
                          : _selectedBaseMap.contains('Peta Topografi')
                              ? _topoUrl
                              : _osmUrl,
                      userAgentPackageName: 'com.sigpala.batola.mobile_app',
                    ),

                    // Layer Batas Kecamatan (Warna-warni transparan)
                    if (_showKecamatanLayer)
                      PolygonLayer(polygons: kecamatanPolygons),

                    // Layer Batas Kabupaten Batola (Outline gelap)
                    if (_showKabupatenLayer)
                      PolygonLayer(polygons: kabupatenPolygons),

                    // Layer Poligon Lahan Sawah (Hijau)
                    if (_showLahanSawahLayer)
                      PolygonLayer(polygons: lahanPolygons),

                    // Layer Teks Label Nama Kecamatan
                    if (_showKecamatanLayer)
                      MarkerLayer(markers: kecamatanLabelMarkers),

                    // Layer Pin Lahan Sawah
                    if (_showLahanSawahLayer)
                      MarkerLayer(markers: lahanMarkers),

                    // Layer Pin Pencarian Terpilih (Kecamatan/Kelurahan)
                    if (_selectedEntity != null &&
                        (_selectedEntity!['type'] == 'kecamatan' ||
                            _selectedEntity!['type'] == 'kelurahan'))
                      MarkerLayer(
                        markers: [
                          Marker(
                            point: _selectedEntity!['location'] as LatLng,
                            width: 50,
                            height: 50,
                            child: const Icon(
                              Icons.location_on_rounded,
                              color: Colors.redAccent,
                              size: 45,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),

          // 2. Panel Pencarian & Tombol Layer (Tanpa Filter Dropdown)
          Positioned(
            top: 16,
            left: 16,
            right: 16,
            child: Card(
              elevation: 6,
              shadowColor: Colors.black26,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              color: Colors.white,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: TextField(
                          controller: _searchController,
                          style: GoogleFonts.inter(fontSize: 13),
                          decoration: InputDecoration(
                            hintText:
                                'Cari lahan, pemilik, kecamatan, kelurahan...',
                            hintStyle: GoogleFonts.inter(
                              fontSize: 13,
                              color: Colors.grey[500],
                            ),
                            prefixIcon: const Icon(
                              Icons.search,
                              color: Color(0xFF3E7D00),
                              size: 18,
                            ),
                            border: InputBorder.none,
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 10,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    GestureDetector(
                      onTap: () {
                        setState(() {
                          _showLayerPanel = !_showLayerPanel;
                        });
                      },
                      child: Container(
                        height: 38,
                        width: 38,
                        decoration: BoxDecoration(
                          color: _showLayerPanel
                              ? const Color(0xFF3E7D00)
                              : const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          Icons.layers_rounded,
                          color: _showLayerPanel
                              ? Colors.white
                              : const Color(0xFF3E7D00),
                          size: 20,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // 2a. Dropdown Hasil Pencarian (Lahan, Kecamatan, Kelurahan)
          if (_searchQuery.trim().length >= 2 && allSuggestions.isNotEmpty)
            Positioned(
              top: 80,
              left: 16,
              right: 16,
              child: Card(
                elevation: 8,
                shadowColor: Colors.black26,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                color: Colors.white,
                child: Container(
                  constraints: const BoxConstraints(maxHeight: 220),
                  child: ListView.builder(
                    padding: EdgeInsets.zero,
                    shrinkWrap: true,
                    itemCount: allSuggestions.length,
                    itemBuilder: (context, index) {
                      final item = allSuggestions[index];
                      IconData iconData;
                      String titleText = item['name'];
                      String subtitleText = '';

                      if (item['type'] == 'lahan') {
                        iconData = Icons.landscape_outlined;
                        subtitleText =
                            'Lahan Sawah • Pemilik: ${item['pemilik']} (${item['kecamatan']})';
                      } else if (item['type'] == 'kecamatan') {
                        iconData = Icons.business_outlined;
                        subtitleText = 'Kecamatan di Barito Kuala';
                      } else {
                        iconData = Icons.home_outlined;
                        subtitleText = 'Kelurahan di Kec. ${item['kecamatan']}';
                      }

                      return ListTile(
                        dense: true,
                        leading: CircleAvatar(
                          backgroundColor: const Color(0xFFF1F5F9),
                          child: Icon(
                            iconData,
                            color: const Color(0xFF3E7D00),
                            size: 18,
                          ),
                        ),
                        title: Text(
                          titleText,
                          style: GoogleFonts.inter(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF1E293B),
                          ),
                        ),
                        subtitle: Text(
                          subtitleText,
                          style: GoogleFonts.inter(
                            fontSize: 10,
                            color: Colors.grey[600],
                          ),
                        ),
                        onTap: () {
                          // Zoom ke koordinat item hasil pencarian
                          final LatLng loc = item['location'];
                          double zoomLevel = 14.5;
                          if (item['type'] == 'kecamatan') {
                            zoomLevel = 12.5;
                          } else if (item['type'] == 'lahan') {
                            zoomLevel = 16.5;
                          }

                          _mapController.move(loc, zoomLevel);

                          setState(() {
                            _selectedEntity = {
                              'type': item['type'],
                              'name': item['name'],
                              'location': loc,
                              'properties': item['type'] == 'lahan'
                                  ? item['properties']
                                  : (item['type'] == 'kecamatan'
                                        ? {'nama_kecamatan': item['name']}
                                        : {
                                            'nama_kelurahan': item['name'],
                                            'nama_kecamatan':
                                                item['kecamatan'] ?? '',
                                          }),
                            };
                          });

                          // Bersihkan pencarian & tutup keyboard
                          _searchController.clear();
                          FocusScope.of(context).unfocus();
                        },
                      );
                    },
                  ),
                ),
              ),
            ),

          // 3. Panel Detail Lahan Melayang (Bottom Panel)
          if (_selectedEntity != null)
            Positioned(
              bottom: 16,
              left: 16,
              right: 16,
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.15),
                      blurRadius: 16,
                      offset: const Offset(0, -4),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _selectedEntity!['type'] == 'lahan'
                                    ? ((_selectedEntity!['properties']
                                                  as Map?)?['nama_lahan']
                                              ?.toString() ??
                                          'Lahan Tanpa Nama')
                                    : (_selectedEntity!['type'] == 'kecamatan'
                                          ? 'Kecamatan ${_selectedEntity!['name']}'
                                          : 'Kelurahan ${_selectedEntity!['name']}'),
                                style: GoogleFonts.outfit(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF14280B),
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _selectedEntity!['type'] == 'lahan'
                                    ? 'Pemilik: ${(_selectedEntity!['properties'] as Map?)?['pemilik_lahan']?.toString() ?? '-'}'
                                    : (_selectedEntity!['type'] == 'kecamatan'
                                          ? 'Wilayah Kecamatan di Barito Kuala'
                                          : 'Kecamatan: ${(_selectedEntity!['properties'] as Map?)?['nama_kecamatan'] ?? '-'}'),
                                style: GoogleFonts.inter(
                                  fontSize: 12,
                                  color: Colors.grey[600],
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            setState(() {
                              _selectedEntity = null;
                            });
                          },
                          icon: const Icon(
                            Icons.close_rounded,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 20, color: Color(0xFFE2E8F0)),

                    if (_selectedEntity!['type'] == 'lahan') ...[
                      // Informasi Detail Spasial Lahan
                      Row(
                        children: [
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.layers_outlined,
                              label: 'Tipe Lahan',
                              value:
                                  (_selectedEntity!['properties']
                                          as Map?)?['tipe_lahan']
                                      ?.toString() ??
                                  '-',
                            ),
                          ),
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.landscape_outlined,
                              label: 'Luas Lahan',
                              value:
                                  '${_formatNumber(double.tryParse((_selectedEntity!['properties'] as Map?)?['luas_lahan_hektar']?.toString() ?? '0') ?? 0.0)} Ha',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.grass_outlined,
                              label: 'Hasil Panen',
                              value:
                                  '${_formatNumber(double.tryParse((_selectedEntity!['properties'] as Map?)?['hasil_panen_ton']?.toString() ?? '0') ?? 0.0)} Ton',
                            ),
                          ),
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.trending_up_rounded,
                              label: 'Produktivitas',
                              value:
                                  '${_formatNumber(double.tryParse((_selectedEntity!['properties'] as Map?)?['produktivitas_ton_ha']?.toString() ?? '0') ?? 0.0)} Ton/Ha',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_outlined,
                            color: Colors.grey,
                            size: 14,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              '${(_selectedEntity!['properties'] as Map?)?['nama_kelurahan'] ?? '-'}, ${(_selectedEntity!['properties'] as Map?)?['nama_kecamatan'] ?? '-'}',
                              style: GoogleFonts.inter(
                                fontSize: 11,
                                color: Colors.grey[600],
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ] else ...[
                      // Informasi Detail Wilayah Kecamatan/Kelurahan
                      Row(
                        children: [
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.landscape_outlined,
                              label: 'Jumlah Lahan Sawah',
                              value: '$totalLahan Lahan',
                            ),
                          ),
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.area_chart_outlined,
                              label: 'Total Luas Wilayah',
                              value: '${_formatNumber(totalLuas)} Ha',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.grass_outlined,
                              label: 'Total Hasil Panen',
                              value: '${_formatNumber(totalPanen)} Ton',
                            ),
                          ),
                          Expanded(
                            child: _buildDetailItem(
                              icon: Icons.trending_up_rounded,
                              label: 'Rata-rata Produktivitas',
                              value:
                                  '${_formatNumber(avgProduktivitas)} Ton/Ha',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(
                            Icons.info_outline_rounded,
                            color: Colors.grey,
                            size: 14,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              'Data dikalkulasi berdasarkan sebaran spasial aktif.',
                              style: GoogleFonts.inter(
                                fontSize: 11,
                                color: Colors.grey[600],
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 16),

                    // Tombol Navigasi Google Maps Eksternal
                    ElevatedButton.icon(
                      onPressed: () {
                        if (_selectedEntity!['type'] == 'lahan') {
                          final props = _selectedEntity!['properties'] as Map?;
                          final lat = double.tryParse(
                            props?['latitude']?.toString() ?? '',
                          );
                          final lng = double.tryParse(
                            props?['longitude']?.toString() ?? '',
                          );
                          if (lat != null && lng != null) {
                            _openExternalMap(
                              lat,
                              lng,
                              props?['nama_lahan']?.toString() ?? 'Lahan',
                            );
                          }
                        } else {
                          final LatLng loc =
                              _selectedEntity!['location'] as LatLng;
                          _openExternalMap(
                            loc.latitude,
                            loc.longitude,
                            _selectedEntity!['type'] == 'kecamatan'
                                ? 'Kecamatan ${_selectedEntity!['name']}'
                                : 'Kelurahan ${_selectedEntity!['name']}',
                          );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF3E7D00),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.directions_rounded, size: 18),
                      label: Text(
                        'Petunjuk Arah (Peta Google)',
                        style: GoogleFonts.inter(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

          // 4. Panel Kontrol Layer Peta (Disandingkan di bawah area filter pencarian)
          if (_showLayerPanel)
            Positioned(
              top: 92,
              right: 16,
              child: Card(
                elevation: 8,
                shadowColor: Colors.black38,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                color: Colors.white.withValues(alpha: 0.95),
                child: Container(
                  width: 210,
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              const Icon(
                                Icons.layers_outlined,
                                size: 18,
                                color: Color(0xFF3E7D00),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                'LAYER PETA',
                                style: GoogleFonts.outfit(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: const Color(0xFF1E293B),
                                ),
                              ),
                            ],
                          ),
                          IconButton(
                            constraints: const BoxConstraints(),
                            padding: EdgeInsets.zero,
                            icon: const Icon(
                              Icons.close,
                              size: 16,
                              color: Colors.grey,
                            ),
                            onPressed: () {
                              setState(() {
                                _showLayerPanel = false;
                              });
                            },
                          ),
                        ],
                      ),
                      const Divider(height: 16),
                      Text(
                        'PETA DASAR',
                        style: GoogleFonts.inter(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 4),
                      _buildBaseMapRadio('🗺️ Peta Standar'),
                      _buildBaseMapRadio('🛰️ Citra Satelit'),
                      _buildBaseMapRadio('⛰️ Peta Topografi'),
                      const Divider(height: 16),
                      Text(
                        'LAPISAN DATA',
                        style: GoogleFonts.inter(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 4),
                      _buildLayerCheckbox(
                        '🏙️ Kabupaten',
                        _showKabupatenLayer,
                        (val) {
                          setState(() {
                            _showKabupatenLayer = val ?? false;
                          });
                        },
                      ),
                      _buildLayerCheckbox('🏢 Kecamatan', _showKecamatanLayer, (
                        val,
                      ) {
                        setState(() {
                          _showKecamatanLayer = val ?? false;
                        });
                      }),
                      _buildLayerCheckbox(
                        '🌾 Lahan Sawah',
                        _showLahanSawahLayer,
                        (val) {
                          setState(() {
                            _showLahanSawahLayer = val ?? false;
                          });
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildBaseMapRadio(String title) {
    final isSelected = _selectedBaseMap == title;
    return InkWell(
      onTap: () {
        setState(() {
          _selectedBaseMap = title;
        });
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4.0),
        child: Row(
          children: [
            Icon(
              isSelected
                  ? Icons.radio_button_checked_rounded
                  : Icons.radio_button_unchecked_rounded,
              size: 20,
              color: isSelected
                  ? const Color(0xFF3E7D00)
                  : const Color(0xFF94A3B8),
            ),
            const SizedBox(width: 8),
            Text(
              title,
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                color: isSelected ? const Color(0xFF1E293B) : Colors.grey[700],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLayerCheckbox(
    String title,
    bool value,
    ValueChanged<bool?> onChanged,
  ) {
    return InkWell(
      onTap: () => onChanged(!value),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4.0),
        child: Row(
          children: [
            SizedBox(
              height: 24,
              width: 24,
              child: Checkbox(
                value: value,
                activeColor: const Color(0xFF3E7D00),
                onChanged: onChanged,
              ),
            ),
            const SizedBox(width: 8),
            Text(
              title,
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: value ? FontWeight.bold : FontWeight.normal,
                color: value ? const Color(0xFF1E293B) : Colors.grey[700],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailItem({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: const Color(0xFF3E7D00), size: 16),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: GoogleFonts.inter(
                  fontSize: 10,
                  color: Colors.grey,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                value,
                style: GoogleFonts.inter(
                  fontSize: 13,
                  color: const Color(0xFF1E293B),
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

// Model Parser GeoJSON murni untuk poligon Kabupaten & Kecamatan
class GeoJsonParser {
  static List<Polygon> parsePolygons(
    Map<String, dynamic>? geojson, {
    required Color defaultBorderColor,
    required Color defaultFillColor,
    required double borderThickness,
    bool usePropertyColors = false,
  }) {
    if (geojson == null) return [];
    List<Polygon> polygons = [];

    List<dynamic> features = [];
    if (geojson['type'] == 'FeatureCollection') {
      features = geojson['features'] ?? [];
    } else if (geojson['type'] == 'Feature') {
      features = [geojson];
    } else if (geojson['type'] == 'Polygon' ||
        geojson['type'] == 'MultiPolygon') {
      features = [
        {'type': 'Feature', 'geometry': geojson, 'properties': {}},
      ];
    }

    for (var feature in features) {
      if (feature is! Map) continue;
      final geometry = feature['geometry'];
      if (geometry is! Map) continue;
      final type = geometry['type']?.toString() ?? '';
      final coords = geometry['coordinates'];
      if (coords is! List) continue;

      final props = (feature['properties'] is Map)
          ? feature['properties'] as Map
          : {};
      Color borderColor = defaultBorderColor;
      Color fillColor = defaultFillColor;

      if (usePropertyColors) {
        final colorHex = props['warna_peta'] ?? props['fill_color'];
        if (colorHex != null) {
          final color = _parseHexColor(colorHex.toString());
          if (color != null) {
            borderColor = color.withValues(alpha: 0.8);
            fillColor = color.withValues(
              alpha: 0.12,
            ); // Transparansi untuk kecamatan
          }
        }
      }

      if (type == 'Polygon') {
        if (coords.isNotEmpty) {
          final pts = _parseRing(coords[0]);
          if (pts.isNotEmpty) {
            polygons.add(
              Polygon(
                points: pts,
                borderColor: borderColor,
                borderStrokeWidth: borderThickness,
                color: fillColor,
                isFilled: fillColor != Colors.transparent,
              ),
            );
          }
        }
      } else if (type == 'MultiPolygon') {
        for (var polyCoords in coords) {
          if (polyCoords is! List || polyCoords.isEmpty) continue;
          final pts = _parseRing(polyCoords[0]);
          if (pts.isNotEmpty) {
            polygons.add(
              Polygon(
                points: pts,
                borderColor: borderColor,
                borderStrokeWidth: borderThickness,
                color: fillColor,
                isFilled: fillColor != Colors.transparent,
              ),
            );
          }
        }
      }
    }

    return polygons;
  }

  static List<LatLng> _parseRing(dynamic ring) {
    List<LatLng> points = [];
    if (ring is! List) return points;
    for (var pt in ring) {
      if (pt is! List || pt.length < 2) continue;
      double? val1;
      double? val2;

      if (pt[0] is num) {
        val1 = (pt[0] as num).toDouble();
      } else {
        val1 = double.tryParse(pt[0].toString());
      }

      if (pt[1] is num) {
        val2 = (pt[1] as num).toDouble();
      } else {
        val2 = double.tryParse(pt[1].toString());
      }

      if (val1 != null && val2 != null) {
        // Barito Kuala: Longitude ~114, Latitude ~ -3
        double lat = val2;
        double lng = val1;

        // Jika koordinat terbalik: index 0 bernilai latitude (-10 s/d 10)
        // dan index 1 bernilai longitude (90 s/d 150)
        if (val1 >= -10 && val1 <= 10 && val2 >= 90 && val2 <= 150) {
          lat = val1;
          lng = val2;
        }

        // Batasi range LatLng agar valid dan tidak crash pada latlong2
        if (lat < -90) lat = -90;
        if (lat > 90) lat = 90;
        if (lng < -180) lng = -180;
        if (lng > 180) lng = 180;

        points.add(LatLng(lat, lng));
      }
    }
    return points;
  }

  static Color? _parseHexColor(String hex) {
    try {
      hex = hex.replaceAll('#', '');
      if (hex.length == 6) {
        hex = 'FF$hex';
      }
      return Color(int.parse(hex, radix: 16));
    } catch (_) {
      return null;
    }
  }
}
