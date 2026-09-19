package com.example

import com.example.data.repository.WalletRepository
import org.junit.Assert.assertEquals
import org.junit.Test

class WalletFormatTest {

    @Test
    fun testFormatMoneyStandard() {
        val formatted = WalletRepository.formatMoney(50000.0)
        assertEquals("50,000.00", formatted)
    }

    @Test
    fun testFormatMoneyDecimal() {
        val formatted = WalletRepository.formatMoney(1234.56)
        assertEquals("1,234.56", formatted)
    }

    @Test
    fun testFormatMoneyZero() {
        val formatted = WalletRepository.formatMoney(0.0)
        assertEquals("0.00", formatted)
    }
}
