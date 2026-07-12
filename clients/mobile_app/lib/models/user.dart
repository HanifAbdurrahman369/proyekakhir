class User {
  final int id;
  final String namaLengkap;
  final String email;
  final int? roleId;
  final String? noHp;
  final String? alamat;
  final int? wilayahKecamatanId;
  final String? wilayahKecamatanNama;
  final List<int> wilayahKelurahanIds;
  final List<String> wilayahKelurahanNama;
  final String? instansiAsal;
  final String? namaBpp;

  User({
    required this.id,
    required this.namaLengkap,
    required this.email,
    this.roleId,
    this.noHp,
    this.alamat,
    this.wilayahKecamatanId,
    this.wilayahKecamatanNama,
    this.wilayahKelurahanIds = const [],
    this.wilayahKelurahanNama = const [],
    this.instansiAsal,
    this.namaBpp,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      namaLengkap: (json['nama_lengkap'] ?? json['name'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      roleId: json['role_id'] != null
          ? int.tryParse(json['role_id'].toString()) ?? json['role_id'] as int
          : null,
      noHp: json['no_hp'] as String?,
      alamat: json['alamat'] as String?,
      wilayahKecamatanId: json['wilayah_kecamatan_id'] != null
          ? int.tryParse(json['wilayah_kecamatan_id'].toString())
          : null,
      wilayahKecamatanNama: json['wilayah_kecamatan_nama'] as String?,
      wilayahKelurahanIds: _parseIntList(json['wilayah_kelurahan_ids']),
      wilayahKelurahanNama: _parseStringList(json['wilayah_kelurahan_nama']),
      instansiAsal: json['instansi_asal'] as String?,
      namaBpp: json['nama_bpp'] as String?,
    );
  }

  static List<int> _parseIntList(dynamic value) {
    if (value is List) {
      return value
          .map((item) => int.tryParse(item.toString()))
          .whereType<int>()
          .toList();
    }
    return const [];
  }

  static List<String> _parseStringList(dynamic value) {
    if (value is List) {
      return value.map((item) => item.toString()).toList();
    }
    return const [];
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nama_lengkap': namaLengkap,
      'email': email,
      'role_id': roleId,
      'no_hp': noHp,
      'alamat': alamat,
      'wilayah_kecamatan_id': wilayahKecamatanId,
      'wilayah_kecamatan_nama': wilayahKecamatanNama,
      'wilayah_kelurahan_ids': wilayahKelurahanIds,
      'wilayah_kelurahan_nama': wilayahKelurahanNama,
      'instansi_asal': instansiAsal,
      'nama_bpp': namaBpp,
    };
  }
}
