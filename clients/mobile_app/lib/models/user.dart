class User {
  final int id;
  final String namaLengkap;
  final String email;
  final int? roleId;
  final String? noHp;
  final String? alamat;

  User({
    required this.id,
    required this.namaLengkap,
    required this.email,
    this.roleId,
    this.noHp,
    this.alamat,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      namaLengkap: (json['nama_lengkap'] ?? json['name'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      roleId: json['role_id'] != null ? int.tryParse(json['role_id'].toString()) ?? json['role_id'] as int : null,
      noHp: json['no_hp'] as String?,
      alamat: json['alamat'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nama_lengkap': namaLengkap,
      'email': email,
      'role_id': roleId,
      'no_hp': noHp,
      'alamat': alamat,
    };
  }
}
