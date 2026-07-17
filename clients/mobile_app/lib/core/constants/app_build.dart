class AppBuild {
  const AppBuild._();

  static const String version = String.fromEnvironment(
    'SIPETANI_VERSION',
    defaultValue: '1.2.2',
  );

  static const String buildNumber = String.fromEnvironment(
    'SIPETANI_BUILD',
    defaultValue: '8',
  );

  static const String releaseIdentity = 'Versi 1.2.2 • Build 8';
}
