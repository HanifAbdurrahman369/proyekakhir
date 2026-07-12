const { expect } = require('@wdio/globals')

describe('Petani Dashboard Mobile E2E', () => {
    it('should login and display the dashboard UI components correctly', async () => {
        // Find email and password inputs and login button
        const emailInput = await $('//android.widget.EditText[1]')
        const passInput = await $('//android.widget.EditText[2]')
        const loginBtn = await $('//android.widget.Button[@content-desc="Masuk"]')
        
        await emailInput.setValue('petani@example.com')
        await passInput.setValue('password')
        await loginBtn.click()

        // Give it time to load the dashboard
        await driver.pause(3000)

        // Verify Dashboard UI components exist
        const dashboardTitle = await $('//android.widget.TextView[@text="Dashboard aktivitas pertanian"]')
        await expect(dashboardTitle).toExist()

        const btnTambahLahan = await $('//android.widget.TextView[@text="Tambah Lahan"]')
        await expect(btnTambahLahan).toExist()

        const btnLaporTanam = await $('//android.widget.TextView[contains(@text, "Lapor Tanam")]')
        await expect(btnLaporTanam).toExist()

        const btnLaporPanen = await $('//android.widget.TextView[@text="Lapor Hasil Panen"]')
        await expect(btnLaporPanen).toExist()

        // Validate stats cards
        const textLahanTerdaftar = await $('//android.widget.TextView[@text="LAHAN TERDAFTAR"]')
        await expect(textLahanTerdaftar).toExist()

        const textProduksi = await $('//android.widget.TextView[@text="PRODUKSI TAHUN INI"]')
        await expect(textProduksi).toExist()

        // Validate sections
        const textMasaTanam = await $('//android.widget.TextView[@text="Padi dalam masa tanam"]')
        await expect(textMasaTanam).toExist()

        const textDaftarLahan = await $('//android.widget.TextView[contains(@text, "Daftar lahan")]')
        await expect(textDaftarLahan).toExist()
    })
})
