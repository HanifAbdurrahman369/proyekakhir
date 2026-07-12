describe('Petani Dashboard E2E', () => {
  beforeEach(() => {
    // Setup login for petani
    cy.visit('/login');
    cy.get('input[name="email"]').type('petani@example.com');
    cy.get('input[name="password"]').type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard-petani');
  });

  it('displays the dashboard UI components correctly', () => {
    // Check main headings
    cy.contains('Dashboard aktivitas pertanian').should('be.visible');
    cy.contains('Tambah Lahan').should('be.visible');
    cy.contains('Lapor Tanam').should('be.visible');
    cy.contains('Lapor Hasil Panen').should('be.visible');
    
    // Check Statistics Cards
    cy.contains('LAHAN TERDAFTAR').should('be.visible');
    cy.contains('PRODUKSI TAHUN INI').should('be.visible');
    cy.contains('ATURAN MASA TANAM').should('be.visible');
    
    // Check sections
    cy.contains('Padi dalam masa tanam').should('be.visible');
    cy.contains('Daftar lahan milik Kelompok Tani').should('be.visible');
  });
});
