describe('Petugas Dashboard E2E', () => {
  beforeEach(() => {
    // Setup login for petugas
    cy.visit('/login');
    cy.get('input[name="email"]').type('petugas@example.com');
    cy.get('input[name="password"]').type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard-petugas');
  });

  it('displays the dashboard UI components correctly', () => {
    // Check main headings
    cy.contains('Dashboard Petugas').should('be.visible');
    
    // Check Top Navigation Links
    cy.contains('Dashboard').should('be.visible');
    cy.contains('Data Spasial Lahan').should('be.visible');
    cy.contains('Lahan Termonitor').should('be.visible');
    cy.contains('Verifikasi').should('be.visible');
    cy.contains('Komunitas').should('be.visible');
    
    // Check Statistics Cards
    cy.contains('Total Antrean').should('be.visible');
    cy.contains('Pengajuan Lahan').should('be.visible');
    cy.contains('Laporan Panen').should('be.visible');
    
    // Check Ringkasan Tugas
    cy.contains('Ringkasan Tugas Petugas').should('be.visible');
    cy.contains('Verifikasi Data Petani').should('be.visible');
    cy.contains('Manajemen Data Spasial').should('be.visible');
  });
});
